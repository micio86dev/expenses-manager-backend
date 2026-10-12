SHELL := /bin/bash
.DEFAULT_GOAL := help

.PHONY: help setup test coverage lint typecheck format

help: ## Show available targets
	@awk 'BEGIN {FS = ":.*## "} /^[a-zA-Z_-]+:.*## / {printf "  %-12s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

setup: ## Install dependencies, create .env, generate the app key and migrate
	composer install
	@test -f .env || sed -e 's/^APP_ENV=.*/APP_ENV=local/' -e 's/^APP_DEBUG=.*/APP_DEBUG=true/' .env.example > .env
	php artisan key:generate --ansi
	@mkdir -p database && touch database/database.sqlite
	php artisan migrate --force

test: ## Run the Pest suite
	composer test

coverage: ## Run the suite with PCOV coverage and fail below 90%
	composer coverage

lint: ## Check code style with Pint (no changes written)
	composer lint

typecheck: ## Run Larastan at max level
	composer analyse

format: ## Apply Pint fixes
	composer format
