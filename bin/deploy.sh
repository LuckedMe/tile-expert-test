#!/usr/bin/env bash
#
# Deploys tile-expert-test on the production VPS. Run it ON THE SERVER, from anywhere,
# or from your machine with --remote (it then runs itself on the server over ssh):
#
#   bin/deploy.sh                 # git pull (fast-forward) + build + restart + checks
#   bin/deploy.sh --ref v1.2.0    # deploy a tag / branch / commit instead of the current branch head
#   bin/deploy.sh --no-pull       # deploy the code already checked out
#   bin/deploy.sh --no-prune      # keep dangling images
#   bin/deploy.sh --no-backup     # skip the database backup before the update
#   bin/deploy.sh --reindex       # rebuild the Manticore search index after the update (app:order:reindex)
#   bin/deploy.sh --remote        # deploy from your machine: runs this script on the server over ssh
#   bin/deploy.sh --remote --host dad@other-host --dir /srv/tile-expert-test
#   bin/deploy.sh --help
#
# Environment (all optional):
#   DEPLOY_HOST     ssh target for --remote           (default: dad@100.91.57.78, Tailscale IP)
#   DEPLOY_DIR      project folder on the server      (default: /home/dad/workspace/www/tile-expert-test)
#   BACKUP_SCRIPT   database backup script            (default: /home/dad/workspace/backups/backup-tile-expert-test.sh)
#   APP_URL         page checked at the end           (default: https://tile-expert-test.lucked.me/api/doc; empty = no check)
#
# With --remote the server pulls from GitHub, so push your commits first. The script on the
# server must already exist (it is part of the repo); BACKUP_SCRIPT, APP_URL and all options
# are passed through to it.
#
# Migrations run automatically when php-fpm starts (RUN_MIGRATIONS=1 in .env.prod.local).
# The search index is created on the first order and is rebuilt only with --reindex.
# See docs/deploy.md (sections 7 and 9).

set -Eeuo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE_FILE="docker-compose.prod.yaml"
ENV_FILE=".env.prod.local"
REQUIRED_SERVICES=(php-fpm nginx mysql manticore)

PULL=1
PRUNE=1
BACKUP=1
REINDEX=0
REF=""
BACKUP_SCRIPT_SET="${BACKUP_SCRIPT+x}"
APP_URL_SET="${APP_URL+x}"
REMOTE=0
DEPLOY_HOST="${DEPLOY_HOST:-dad@100.91.57.78}"
DEPLOY_DIR="${DEPLOY_DIR:-/home/dad/workspace/www/tile-expert-test}"
BACKUP_SCRIPT="${BACKUP_SCRIPT:-/home/dad/workspace/backups/backup-tile-expert-test.sh}"
APP_URL="${APP_URL-https://tile-expert-test.lucked.me/api/doc}"
PASS_ARGS=()

log()  { printf '\033[36m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[33mWARN: %s\033[0m\n' "$*" >&2; }
die()  { printf '\033[31mERROR: %s\033[0m\n' "$*" >&2; exit 1; }

usage() {
    sed -n '3,29p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//'
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --ref)         [[ $# -ge 2 ]] || die "--ref needs a value"; REF="$2"; PASS_ARGS+=(--ref "$2"); shift ;;
        --no-pull)     PULL=0; PASS_ARGS+=(--no-pull) ;;
        --no-prune)    PRUNE=0; PASS_ARGS+=(--no-prune) ;;
        --no-backup)   BACKUP=0; PASS_ARGS+=(--no-backup) ;;
        --reindex)     REINDEX=1; PASS_ARGS+=(--reindex) ;;
        --remote)      REMOTE=1 ;;
        --host)        [[ $# -ge 2 ]] || die "--host needs user@host"; DEPLOY_HOST="$2"; shift ;;
        --dir)         [[ $# -ge 2 ]] || die "--dir needs a directory"; DEPLOY_DIR="$2"; shift ;;
        -h|--help)     usage; exit 0 ;;
        *)             die "unknown option: $1 (see --help)" ;;
    esac
    shift
done

trap 'die "deploy failed at line $LINENO (command: $BASH_COMMAND)"' ERR

cd "$PROJECT_DIR"

# --- remote: run the same script on the server over ssh ------------------------
if [[ $REMOTE -eq 1 ]]; then
    command -v ssh >/dev/null || die "ssh is not installed"

    if [[ -z "$REF" && "$(git rev-parse --is-inside-work-tree 2>/dev/null)" == "true" ]]; then
        branch="$(git symbolic-ref --short -q HEAD || true)"
        if [[ -n "$branch" ]] && git rev-parse -q --verify "origin/$branch" >/dev/null; then
            git fetch -q origin "$branch" || warn "could not fetch origin/$branch"
            ahead="$(git rev-list --count "origin/$branch..HEAD")"
            [[ "$ahead" -eq 0 ]] || warn "$ahead local commit(s) are not pushed to origin/$branch: the server will not get them"
        fi
    fi

    # settings explicitly set on this machine are handed to the server
    remote_env=""
    [[ -z "${BACKUP_SCRIPT_SET:-}" ]] || remote_env+="BACKUP_SCRIPT=$(printf '%q' "$BACKUP_SCRIPT") "
    [[ -z "${APP_URL_SET:-}" ]] || remote_env+="APP_URL=$(printf '%q' "$APP_URL") "

    log "deploying on $DEPLOY_HOST:$DEPLOY_DIR"
    # -t: a tty, so docker/git colours and progress show up; values are quoted for the remote shell
    exec ssh -t "$DEPLOY_HOST" "cd $(printf '%q' "$DEPLOY_DIR") && ${remote_env}bin/deploy.sh ${PASS_ARGS[*]@Q}"
fi

# --- preflight ---------------------------------------------------------------
command -v docker >/dev/null || die "docker is not installed"
docker compose version >/dev/null 2>&1 || die "the docker compose plugin is not installed"
[[ -f "$COMPOSE_FILE" ]] || die "$COMPOSE_FILE not found in $PROJECT_DIR"
[[ -f "$ENV_FILE" ]] || die "$ENV_FILE not found: cp .env.prod.local.dist $ENV_FILE and fill it in (docs/deploy.md, section 5)"

if grep -q 'ChangeMe' "$ENV_FILE"; then
    die "$ENV_FILE still contains !ChangeMe! placeholders"
fi

compose() {
    docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" "$@"
}

log "validating $COMPOSE_FILE"
compose config >/dev/null

# --- code --------------------------------------------------------------------
if [[ $PULL -eq 1 ]]; then
    git diff --quiet && git diff --cached --quiet \
        || die "the working tree has local changes; commit/stash them or use --no-pull"

    log "fetching code"
    git fetch --tags --prune origin

    if [[ -n "$REF" ]]; then
        log "checking out $REF"
        git checkout --detach "$REF"
    else
        branch="$(git symbolic-ref --short -q HEAD || true)"
        [[ -n "$branch" ]] || die "detached HEAD: pass --ref <branch|tag|commit> or check out a branch first"
        log "pulling origin/$branch (fast-forward only)"
        git pull --ff-only origin "$branch"
    fi
elif [[ -n "$REF" ]]; then
    die "--ref cannot be combined with --no-pull"
fi

log "deploying $(git log -1 --format='%h %s')"

# --- backup ------------------------------------------------------------------
if [[ $BACKUP -eq 1 ]]; then
    if [[ -x "$BACKUP_SCRIPT" ]]; then
        log "backing up the database ($BACKUP_SCRIPT)"
        "$BACKUP_SCRIPT"
    else
        warn "backup script $BACKUP_SCRIPT not found or not executable: no backup made (--no-backup silences this)"
    fi
fi

# --- build and restart -------------------------------------------------------
log "building images and restarting the stack (the first build takes 5-15 minutes)"
compose up -d --build --remove-orphans

# --- checks ------------------------------------------------------------------
log "waiting for services"
ok=0
missing=("${REQUIRED_SERVICES[@]}")
for _ in $(seq 1 30); do
    running="$(compose ps --services --status running)"
    missing=()
    for s in "${REQUIRED_SERVICES[@]}"; do
        grep -qx "$s" <<<"$running" || missing+=("$s")
    done
    if [[ ${#missing[@]} -eq 0 ]]; then
        ok=1
        break
    fi
    sleep 2
done

compose ps

if [[ $ok -ne 1 ]]; then
    compose logs --tail 50 "${missing[@]}" || true
    die "not running after deploy: ${missing[*]}"
fi

log "last php-fpm log lines (migrations, errors)"
compose logs --tail 20 php-fpm

# --- search index ------------------------------------------------------------
if [[ $REINDEX -eq 1 ]]; then
    log "rebuilding the Manticore search index"
    # php-fpm runs the migrations first (entrypoint); wait until the console command can start
    reindexed=0
    for _ in $(seq 1 15); do
        if compose exec -T php-fpm bin/console app:order:reindex --no-interaction; then
            reindexed=1
            break
        fi
        sleep 4
    done
    [[ $reindexed -eq 1 ]] || die "app:order:reindex failed (Manticore not ready?): run it again with 'make prod-reindex'"
fi

# --- public check ------------------------------------------------------------
if [[ -n "$APP_URL" ]]; then
    command -v curl >/dev/null || die "curl is not installed (needed to check $APP_URL; set APP_URL= to skip)"
    log "checking $APP_URL"
    code=000
    for _ in $(seq 1 10); do
        code="$(curl -sS -o /dev/null -w '%{http_code}' --max-time 10 "$APP_URL" 2>/dev/null || true)"
        [[ "$code" =~ ^[23] ]] && break
        sleep 3
    done
    [[ "$code" =~ ^[23] ]] || die "$APP_URL answers HTTP $code after the deploy"
    echo "HTTP $code"
fi

if [[ $PRUNE -eq 1 ]]; then
    log "removing dangling images"
    docker image prune -f >/dev/null
fi

log "deploy finished"
