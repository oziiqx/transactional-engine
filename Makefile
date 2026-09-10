# Transactional Engine — developer task runner.
# Every target runs inside the container so the host only needs Docker + Compose.

DC := docker compose
PHP := $(DC) exec -T php
CONSOLE := $(PHP) php bin/console

.DEFAULT_GOAL := help
.PHONY: help build up down restart logs shell install \
        db-create db-migrate db-diff db-fixtures db-reset \
        consume cs cs-fix stan psalm rector test test-unit test-integration coverage qa

help: ## List available targets
	@grep -hE '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

## ─────────────────────────────  Environment  ─────────────────────────────

build: ## Build the PHP image
	$(DC) build --pull

up: ## Start the full stack (php-fpm, nginx, postgres, redis, rabbitmq, workers)
	$(DC) up -d --wait

down: ## Stop and remove containers
	$(DC) down --remove-orphans

restart: down up ## Recycle the stack

logs: ## Tail application logs
	$(DC) logs -f php messenger_worker nginx

shell: ## Open a shell inside the PHP container
	$(PHP) sh

install: ## Install Composer dependencies
	$(PHP) composer install

## ─────────────────────────────  Database  ────────────────────────────────

db-create: ## Create the database if it does not exist
	$(CONSOLE) doctrine:database:create --if-not-exists

db-migrate: ## Apply outstanding migrations
	$(CONSOLE) doctrine:migrations:migrate --no-interaction --allow-no-migration

db-diff: ## Generate a migration from mapping changes
	$(CONSOLE) doctrine:migrations:diff --formatted

db-fixtures: ## Load development fixtures
	$(CONSOLE) app:fixtures:load --no-interaction

db-reset: ## Drop, recreate and migrate the database
	$(CONSOLE) doctrine:database:drop --force --if-exists
	$(MAKE) db-create db-migrate

## ─────────────────────────────  Messaging  ───────────────────────────────

consume: ## Run the message workers in the foreground
	$(CONSOLE) messenger:consume async_domain_events async_webhooks -vv

## ─────────────────────────────  Quality gates  ───────────────────────────

cs: ## Check coding standard (EasyCodingStandard)
	$(PHP) vendor/bin/ecs check

cs-fix: ## Fix coding standard violations
	$(PHP) vendor/bin/ecs check --fix

stan: ## Static analysis (PHPStan level 10)
	$(PHP) vendor/bin/phpstan analyse --memory-limit=-1

psalm: ## Static analysis (Psalm errorLevel 1)
	$(PHP) vendor/bin/psalm --no-cache --threads=4

rector: ## Report automated refactorings (dry run)
	$(PHP) vendor/bin/rector process --dry-run

test: ## Run the whole test suite (Pest)
	$(PHP) vendor/bin/pest --colors=always

test-unit: ## Run the framework-free unit suite
	$(PHP) vendor/bin/pest --testsuite=unit

test-integration: ## Run the integration + functional suites
	$(PHP) vendor/bin/pest --testsuite=integration --testsuite=functional

coverage: ## Run tests with coverage and a 90% floor
	$(PHP) env XDEBUG_MODE=coverage vendor/bin/pest --coverage --min=90

qa: cs stan psalm test ## Run every quality gate the CI would run
