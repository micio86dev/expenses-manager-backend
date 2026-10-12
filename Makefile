SHELL := /bin/bash
.DEFAULT_GOAL := help

.PHONY: help setup test coverage lint typecheck format review memory-sync

help: ## Show available targets
	@awk 'BEGIN {FS = ":.*## "} /^[a-zA-Z_-]+:.*## / {printf "  %-12s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

setup: ## Install dependencies, create .env, generate the app key and migrate
	git config core.hooksPath .githooks
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

review: ## Guardian Angel review of every PHP file changed in the branch (needs gga)
	gga run --pr-mode

memory-sync: ## Engram memory is project-wide: syncs it from the superproject root
	@echo "Engram memory is shared by the whole project: running 'engram sync' from the superproject root."
	cd .. && engram sync
