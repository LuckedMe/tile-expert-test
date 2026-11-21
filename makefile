COMPOSE = docker compose
PHP     = $(COMPOSE) exec -it php-fpm
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
	$(PHP) /bin/bash

mysql-console:
	#docker compose -f ./.docker/docker-compose.yaml exec -it mysql mysql -u root -p"password"
	$(MYSQL) -u root -p"password"

## --- Logs -----------------------------------------------------------------

php-logs:
	$(COMPOSE) logs -f php-fpm

mysql-logs:
	$(COMPOSE) logs -f mysql

## --- Backend (PHP/Symfony) --------------------------------------------------

install: ## Install PHP dependencies via Composer
	$(PHP) composer install

update: ## Update PHP dependencies via Composer
	$(PHP) composer update

migrate: ## Run Doctrine migrations (dev and test environments)
	$(PHP) ./bin/console doctrine:migrations:migrate --no-interaction
	$(PHP) ./bin/console doctrine:migrations:migrate --env=test --no-interaction

cache-clear: ## Clear the Symfony cache
	$(PHP) ./bin/console cache:clear --no-warmup
	$(PHP) ./bin/console cache:clear --env=test --no-interaction


## --- Test --------------------------------------------------

test-api: ## Run only the API Codeception test suite
	$(PHP) composer run test:api

test-coverage: ## Run all suites with HTML coverage
	$(PHP) composer run test:coverage

test-coverage-html: ## Run all suites except acceptance and web with HTML coverage (tests/_output/coverage)
	$(PHP) composer run test:coverage-html

lint:
	$(PHP) composer run lint

lint-fix:
	$(PHP) composer run lint:fix

## --- Production (VPS) -------------------------------------------------------

PROD     = docker compose -f docker-compose.prod.yaml --env-file .env.prod.local
PROD_PHP = $(PROD) exec -T php-fpm php bin/console

prod-deploy: ## [prod] Run bin/deploy.sh (git pull, build, restart, checks) - on the VPS
	./bin/deploy.sh

prod-build: ## [prod] Build production images
	$(PROD) build

prod-up: ## [prod] Build and (re)start the production stack
	$(PROD) up -d --build --remove-orphans

prod-down: ## [prod] Stop the production stack
	$(PROD) down

prod-migrate: ## [prod] Run DB migrations and set up messenger transports
	$(PROD_PHP) doctrine:migrations:migrate --no-interaction --allow-no-migration
	$(PROD_PHP) messenger:setup-transports --no-interaction

prod-logs: ## [prod] Follow production logs
	$(PROD) logs -f --tail=200

prod-ps: ## [prod] List production containers
	$(PROD) ps

prod-console: ## [prod] Open a shell in the production php-fpm container
	$(PROD) exec php-fpm /bin/bash

## --- Help ---------------------------------------------------------------------

help: ## Show this help message
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-16s\033[0m %s\n", $$1, $$2}'

.PHONY: cache-clear \
	docker-build docker-down docker-logs docker-ps docker-restart docker-stop docker-up \
	help \
	install \
	migrate \
	php-console \
	prod-assets prod-build prod-console prod-deploy prod-down prod-jwt-keys prod-logs prod-migrate prod-ps prod-up \
	test-api test-coverage-html test-coverage test test-unit test-web \
	update
