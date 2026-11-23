COMPOSE = docker compose
PHP     = $(COMPOSE) exec -T php-fpm
PHP_TTY = $(COMPOSE) exec -it php-fpm
MYSQL   = $(COMPOSE) exec -it mysql mysql

.DEFAULT_GOAL := help

## --- Docker ---------------------------------------------------------------
docker-build: ## Build all Docker images
	$(COMPOSE) build

docker-up: ## Build (if needed) and start all containers in the background
	$(COMPOSE) up --build -d

docker-stop: ## Stop all containers without removing them
	$(COMPOSE) stop

docker-down: ## Stop and remove all containers
	$(COMPOSE) down

docker-restart: ## Restart all containers
	$(COMPOSE) restart

docker-ps: ## List container status
	$(COMPOSE) ps

docker-logs: ## Follow logs from all containers
	$(COMPOSE) logs -f

## --- Shells -----------------------------------------------------------------

php-console: ## Open a shell in the php-fpm container
	$(PHP_TTY) /bin/bash

mysql-console: ## Open the MySQL client in the mysql container
	$(MYSQL) -u root -p"password"

## --- Logs -----------------------------------------------------------------

php-logs: ## Follow php-fpm logs
	$(COMPOSE) logs -f php-fpm

nginx-logs: ## Follow nginx logs
	$(COMPOSE) logs -f nginx

mysql-logs: ## Follow MySQL logs
	$(COMPOSE) logs -f mysql

manticore-logs: ## Follow Manticore logs
	$(COMPOSE) logs -f manticore

## --- Backend (PHP/Symfony) --------------------------------------------------

setup: docker-up install migrate migrate-test search-reindex ## First start: containers, dependencies, migrations (dev + test), search index

install: ## Install PHP dependencies via Composer
	$(PHP) composer install

update: ## Update PHP dependencies via Composer
	$(PHP) composer update

migrate: ## Run Doctrine migrations
	$(PHP) ./bin/console doctrine:migrations:migrate --no-interaction

migrate-test: ## Create the test database and run the migrations on it
	$(PHP) ./bin/console doctrine:database:create --env=test --if-not-exists
	$(PHP) ./bin/console doctrine:migrations:migrate --env=test --no-interaction

cache-clear: ## Clear the Symfony cache
	$(PHP) ./bin/console cache:clear

search-reindex: ## Rebuild the Manticore orders index from MySQL
	$(PHP) ./bin/console app:order:reindex

api-doc: ## Dump the OpenAPI specification (Swagger UI: /api/doc)
	$(PHP) ./bin/console nelmio:apidoc:dump --format=json

## --- Test --------------------------------------------------

test: ## Run the PHPUnit test suite
	$(PHP) php bin/phpunit

lint: ## Check the code style
	$(PHP) composer run lint

lint-fix: ## Fix the code style
	$(PHP) composer run lint:fix

## --- Production (VPS), see docs/deploy.md ------------------------------------

PROD     = docker compose -f docker-compose.prod.yaml --env-file .env.prod.local
PROD_PHP = $(PROD) exec -T php-fpm php bin/console

prod-deploy: ## [prod] Run bin/deploy.sh: pull, backup, build, restart, checks - on the VPS
	bin/deploy.sh

prod-deploy-remote: ## [prod] Deploy from this machine: bin/deploy.sh --remote (push first)
	bin/deploy.sh --remote

prod-build: ## [prod] Build the production images
	$(PROD) build

prod-up: ## [prod] Build and (re)start the production stack
	$(PROD) up -d --build --remove-orphans

prod-down: ## [prod] Stop and remove the production containers (volumes are kept)
	$(PROD) down

prod-ps: ## [prod] List the production containers
	$(PROD) ps

prod-logs: ## [prod] Follow the production logs
	$(PROD) logs -f --tail=200

prod-console: ## [prod] Open a shell in the production php-fpm container
	$(PROD) exec php-fpm /bin/bash

prod-reindex: ## [prod] Rebuild the Manticore index in the production stack
	$(PROD_PHP) app:order:reindex

## --- Help ---------------------------------------------------------------------

help: ## Show this help message
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

.PHONY: api-doc cache-clear \
	docker-build docker-down docker-logs docker-ps docker-restart docker-stop docker-up \
	help install lint lint-fix \
	manticore-logs migrate migrate-test mysql-console mysql-logs nginx-logs \
	php-console php-logs \
	prod-build prod-console prod-deploy prod-deploy-remote prod-down prod-logs prod-ps prod-reindex prod-up \
	search-reindex setup test update
