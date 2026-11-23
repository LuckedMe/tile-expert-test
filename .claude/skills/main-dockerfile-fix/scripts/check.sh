#!/usr/bin/env bash
# Checks the main (production) Dockerfile against the current project. Read-only.
# Usage: check.sh [project-root]   (default: .)
# Output: "RULE  location  message" per finding, then a per-rule summary.
#         INFO lines need a manual look and do not fail the check.
# Exit: 0 = clean, 1 = findings, 2 = bad project root.
# Keep the rule table in ../SKILL.md in sync with this file.
# Rules (F3/F8 of the Node based projects do not exist here: this image has no frontend build): F1 PHP version,
# F2 extensions, F4 COPY sources, F5 stages vs compose targets, F6 .dockerignore, F7 build hygiene, F9 prod compose/env.

set -u

ROOT="${1:-.}"
cd "$ROOT" 2>/dev/null || { echo "error: cannot enter $ROOT" >&2; exit 2; }

DF="Dockerfile"
if [[ ! -f "$DF" || ! -f composer.json ]]; then
    echo "error: $DF or composer.json not found in $(pwd) (run from the repo root or pass it)" >&2
    exit 2
fi

RULES=(F1 F2 F4 F5 F6 F7 F9)
declare -A COUNT
declare -A TITLE=(
    [F1]="PHP version does not match composer.json / compose"
    [F2]="PHP extension missing in the image"
    [F4]="COPY source does not exist"
    [F5]="Stage / compose target mismatch"
    [F6]=".dockerignore does not exclude a heavy or secret path"
    [F7]="Build hygiene (root user, dev packages, APP_ENV)"
    [F9]="Production compose / env file mismatch"
)
TOTAL=0

report() { # rule location message
    printf '%-3s  %s  %s\n' "$1" "$2" "$3"
    COUNT[$1]=$(( ${COUNT[$1]:-0} + 1 ))
    TOTAL=$(( TOTAL + 1 ))
}
info() { printf 'INFO %s  %s\n' "$1" "$2"; }

# Dockerfile without comment lines, with line numbers: "N:content"
DF_BODY="$(grep -n -v '^[[:space:]]*#' "$DF")"

# ---------------------------------------------------------------- F1
df_php="$(sed -n 's/^ARG PHP_VERSION=\([0-9.]*\).*/\1/p' "$DF" | head -1)"
req_php="$(sed -n 's/.*"php": *"[^0-9]*\([0-9]\+\(\.[0-9]\+\)\?\).*/\1/p' composer.json | head -1)"
if [[ -z "$df_php" ]]; then
    report F1 "$DF" "no 'ARG PHP_VERSION=<x.y>' found"
elif [[ -n "$req_php" ]] && [[ "$(printf '%s\n%s\n' "$req_php" "$df_php" | sort -V | head -1)" != "$req_php" ]]; then
    report F1 "$DF" "PHP $df_php is lower than composer.json require.php ($req_php)"
fi
if [[ -n "$df_php" && -f docker-compose.yaml ]]; then
    dev_php="$(sed -n 's/.*PHP_VERSION:-\([0-9.]*\)}.*/\1/p' docker-compose.yaml | head -1)"
    [[ -n "$dev_php" && "$dev_php" != "$df_php" ]] \
        && report F1 "$DF" "PHP $df_php differs from the dev default $dev_php in docker-compose.yaml"
fi

# ---------------------------------------------------------------- F2
img_exts="$(grep -h 'install-php-extensions' "$DF" | grep -v '^[[:space:]]*#' \
    | sed 's/.*install-php-extensions//; s/\\//g; s/&&.*//' | tr -s ' ' '\n' | sed '/^$/d')"
# present in the official php image (no need to install)
builtin=" ctype curl date dom fileinfo filter hash iconv json libxml mbstring openssl pcre pdo pdo_sqlite phar posix random readline reflection session simplexml sodium spl sqlite3 standard tokenizer xml xmlreader xmlwriter zlib "
# true when an uncommented line of the env files contains the pattern
env_uses() { cat .env .env.prod.dist 2>/dev/null | grep -v '^[[:space:]]*#' | grep -q "$1"; }
has_ext() { grep -qx "$1" <<<"$img_exts" || [[ "$builtin" == *" $1 "* ]]; }

# only hard requirements: root "require" and the "require" of installed packages (not suggest/conflict/require-dev)
need="$(python3 -I - <<'PY'
import json
out = set()
d = json.load(open("composer.json"))
out |= {k for k in d.get("require", {}) if k.startswith("ext-")}
try:
    for pkg in json.load(open("composer.lock")).get("packages", []):
        out |= {k for k in pkg.get("require", {}) if k.startswith("ext-")}
except FileNotFoundError:
    pass
print(" ".join(sorted(k[4:] for k in out)))
PY
)"
for e in $(printf '%s\n' $need | sort -u); do
    has_ext "$e" || report F2 "composer.json" "ext-$e is required but not installed in the image"
done

# what the dev image installs: the core set must be in the production image too, the rest is reported as INFO
# (tooling, or extensions this app does not use: gd exif xsl amqp redis pdo_pgsql excimer pcov ...)
core=" zip opcache intl pdo_mysql bcmath "
if [[ -f .docker/php-fpm/Dockerfile ]]; then
    dev_exts="$(grep -h 'install-php-extensions' .docker/php-fpm/Dockerfile \
        | grep -v '^[[:space:]]*\(ADD\|COPY\)' \
        | sed 's/.*install-php-extensions//; s/\\//g; s/&&.*//' | tr -s ' ' '\n' | sed '/^$/d')"
    for e in $dev_exts; do
        case "$e" in @composer|excimer|xdebug|pcov) continue ;; esac
        has_ext "$e" && continue
        if [[ "$core" == *" $e "* ]]; then
            report F2 "$DF" "extension '$e' is installed in the dev image and is part of the core set, but is missing in the production image"
        else
            info F2 "$e is in the dev image only; the application does not need it in production, skipped"
        fi
    done
fi
env_uses 'mysql' && ! has_ext pdo_mysql && report F2 "$DF" "DATABASE_URL uses mysql but pdo_mysql is not installed"

# ---------------------------------------------------------------- F4
while IFS= read -r line; do
    n="${line%%:*}"; body="${line#*:}"
    [[ "$body" =~ ^[[:space:]]*(COPY|ADD)[[:space:]] ]] || continue
    [[ "$body" == *"--from="* ]] && continue
    args="$(sed -E 's/^[[:space:]]*(COPY|ADD)[[:space:]]+//; s/--[a-z]+=[^ ]+[[:space:]]*//g' <<<"$body")"
    read -ra parts <<<"$args"
    (( ${#parts[@]} < 2 )) && continue
    for src in "${parts[@]:0:${#parts[@]}-1}"; do
        [[ "$src" == http* || "$src" == "." || "$src" == "./" ]] && continue
        src="${src#./}"
        compgen -G "$src" >/dev/null || [[ -e "$src" ]] || report F4 "$DF:$n" "'$src' does not exist"
    done
done <<<"$DF_BODY"

# ---------------------------------------------------------------- F5
stages="$(sed -n 's/^FROM .* AS \([A-Za-z0-9_-]*\)$/\1/Ip' "$DF")"
targets=""
for f in docker-compose*.y*ml; do
    [[ -f $f ]] && targets+=" $(sed -n 's/^[[:space:]]*target:[[:space:]]*\([A-Za-z0-9_-]*\).*/\1/p' "$f" | tr '\n' ' ')"
done
for t in $(printf '%s\n' $targets | sort -u); do
    grep -qx "$t" <<<"$stages" || report F5 "docker-compose*.yaml" "target '$t' is not a stage in $DF"
done
for s in $stages; do
    used=0
    grep -qE "(--from=$s( |$)|^FROM $s( |$))" "$DF" && used=1
    [[ " $targets " == *" $s "* ]] && used=1
    grep -qsE "target[ =]+$s( |$)|--target[ =]$s" makefile Makefile docs/*.md && used=1
    (( used )) || info F5 "stage '$s' is not used by another stage, compose or makefile"
done

# ---------------------------------------------------------------- F6
if [[ ! -f .dockerignore ]]; then
    report F6 ".dockerignore" "file is missing: the whole working tree (vendor, var, .git, secrets) goes into the build context"
else
    # a path counts as excluded when any .dockerignore line matches it as a glob (e.g. ".env.*.local", "config/jwt/*.pem")
    ignored() {
        local pat
        while IFS= read -r pat; do
            pat="${pat%$'\r'}"; pat="${pat#/}"; pat="${pat%/}"
            [[ -z "$pat" || "$pat" == \#* ]] && continue
            # shellcheck disable=SC2053
            [[ "$1" == $pat ]] && return 0
        done < .dockerignore
        return 1
    }
    for p in .git vendor var .env.local .env.prod.local config/secrets/prod/prod.decrypt.private.php; do
        ignored "$p" || report F6 ".dockerignore" "'$p' is not excluded"
    done
fi

# ---------------------------------------------------------------- F7
app_block="$(awk 'BEGIN{IGNORECASE=1} /^FROM .* AS app$/{f=1;next} /^FROM /{f=0} f' "$DF")"
if [[ -n "$app_block" ]]; then
    last_user="$(grep -E '^USER ' <<<"$app_block" | tail -1 | awk '{print $2}')"
    if [[ -z "$last_user" || "$last_user" == root* || "$last_user" == 0* ]]; then
        report F7 "$DF" "the 'app' stage runs as root (add 'USER app:app' after the COPY steps)"
    fi
    # APP_ENV may be set in the stage the app stage is based on (php-base)
    grep -q 'APP_ENV=prod' <<<"$DF_BODY" || report F7 "$DF" "APP_ENV=prod is not set (ENV in php-base or app)"
else
    report F5 "$DF" "no 'app' stage found"
fi
grep -q 'composer install' "$DF" && ! grep 'composer install' "$DF" | grep -q -- '--no-dev' \
    && report F7 "$DF" "composer install without --no-dev puts dev packages into the image"
grep -q 'dump-autoload' "$DF" || info F7 "no composer dump-autoload (an optimized autoloader is faster in prod)"

# ---------------------------------------------------------------- F9
PROD_COMPOSE="docker-compose.prod.yaml"
ENV_DIST=".env.prod.local.dist"
if [[ ! -f "$PROD_COMPOSE" ]]; then
    report F9 "$PROD_COMPOSE" "file is missing: the production stack is built from it (docs/deploy.md)"
elif [[ ! -f "$ENV_DIST" ]]; then
    report F9 "$ENV_DIST" "file is missing: it is the template of .env.prod.local"
else
    # services that must not publish ports (Docker published ports bypass ufw; Traefik uses the reverse-proxy network)
    svc_block() { awk -v s="$1" '$0 ~ "^    "s":$"{f=1;next} f && /^    [A-Za-z0-9_-]+:/{f=0} f' "$PROD_COMPOSE"; }
    for s in php-fpm nginx mysql manticore; do
        block="$(svc_block "$s")"
        [[ -z "$block" ]] && { report F9 "$PROD_COMPOSE" "service '$s' not found"; continue; }
        grep -qE '^        ports:' <<<"$block" && report F9 "$PROD_COMPOSE" "service '$s' publishes ports (remove 'ports:'; Traefik reaches nginx through the reverse-proxy network)"
    done
    nginx_block="$(svc_block nginx | grep -v '^[[:space:]]*#')"
    grep -q 'reverse-proxy' <<<"$nginx_block" || report F9 "$PROD_COMPOSE" "nginx is not in the reverse-proxy network"
    grep -q 'traefik.enable=true' <<<"$nginx_block" || report F9 "$PROD_COMPOSE" "nginx has no Traefik labels"
    grep -qE 'redirectscheme|certresolver' <<<"$nginx_block" && report F9 "$PROD_COMPOSE" "redirectscheme/certresolver behind the Cloudflare Tunnel loops: Cloudflare does TLS and the https redirect"
    grep -q 'target: app' "$PROD_COMPOSE" && grep -q 'target: nginx' "$PROD_COMPOSE" \
        || report F9 "$PROD_COMPOSE" "php-fpm / nginx must be built with 'target: app' / 'target: nginx'"
    # every ${VAR} without a default must exist in the template
    for v in $(grep -oE '\$\{[A-Z_][A-Z0-9_]*\}' "$PROD_COMPOSE" | tr -d '${}' | sort -u); do
        grep -qE "^$v=" "$ENV_DIST" || report F9 "$ENV_DIST" "$v is used by $PROD_COMPOSE without a default but is not defined here"
    done
    # the template holds placeholders only: no real-looking secret
    for v in APP_SECRET MYSQL_PASSWORD MYSQL_ROOT_PASSWORD; do
        val="$(sed -n "s/^$v=//p" "$ENV_DIST" | head -1)"
        [[ "$val" == '!ChangeMe!' ]] || report F9 "$ENV_DIST" "$v must stay the placeholder !ChangeMe!, never a real secret"
    done
    git check-ignore -q .env.prod.local 2>/dev/null || report F9 ".gitignore" ".env.prod.local is not git-ignored"
fi

# ---------------------------------------------------------------- summary
echo
echo "Summary:"
for r in "${RULES[@]}"; do
    printf '  %-4s %3s  %s\n' "$r" "${COUNT[$r]:-0}" "${TITLE[$r]}"
done
printf '  %-4s %3s\n' ALL "$TOTAL"

(( TOTAL == 0 ))
