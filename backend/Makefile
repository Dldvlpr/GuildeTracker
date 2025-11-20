# GuildTracker Backend - Makefile
# Quick commands for development

.PHONY: help install db-init db-reset db-import-wow db-status serve test clean traefik-start traefik-stop

# Colors
YELLOW := \033[1;33m
GREEN := \033[1;32m
RED := \033[1;31m
RESET := \033[0m

help: ## Show this help message
	@echo "$(GREEN)GuildTracker Backend - Available Commands$(RESET)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(YELLOW)%-20s$(RESET) %s\n", $$1, $$2}'
	@echo ""

install: ## Install dependencies
	@echo "$(GREEN)Installing Composer dependencies...$(RESET)"
	composer install

db-init: ## Initialize database with all data
	@echo "$(GREEN)Initializing database...$(RESET)"
	php bin/console app:init-database

db-reset: ## Reset database (⚠️ destroys all data)
	@echo "$(RED)⚠️  WARNING: This will destroy all data!$(RESET)"
	@read -p "Are you sure? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		php bin/console app:init-database --drop; \
	else \
		echo "Operation cancelled."; \
	fi

db-import-wow: ## Import WoW raids & bosses data
	@echo "$(GREEN)Importing WoW data...$(RESET)"
	php bin/console app:import-wow-raids-db --truncate

db-status: ## Show database status
	@echo "$(GREEN)Database Status:$(RESET)"
	@echo ""
	@php bin/console doctrine:migrations:status
	@echo ""
	@echo "$(YELLOW)Raid Data:$(RESET)"
	@php bin/console dbal:run-sql "SELECT expansion, COUNT(*) as raids FROM wow_raids GROUP BY expansion ORDER BY expansion" 2>/dev/null || echo "No data yet"
	@echo ""
	@echo "$(YELLOW)Boss Count:$(RESET)"
	@php bin/console dbal:run-sql "SELECT COUNT(*) as total_bosses FROM wow_bosses" 2>/dev/null || echo "No data yet"

db-migrate: ## Run pending migrations
	@echo "$(GREEN)Running migrations...$(RESET)"
	php bin/console doctrine:migrations:migrate --no-interaction

db-create: ## Create database
	@echo "$(GREEN)Creating database...$(RESET)"
	php bin/console doctrine:database:create --if-not-exists

serve: ## Start Symfony development server
	@echo "$(GREEN)Starting Symfony server on https://localhost:8000$(RESET)"
	symfony server:start

serve-bg: ## Start Symfony server in background
	@echo "$(GREEN)Starting Symfony server in background...$(RESET)"
	symfony server:start -d
	@echo "Server running at: $(YELLOW)https://localhost:8000$(RESET)"

serve-tls: ## Start Symfony server with mkcert p12 (api.localhost, localhost, 127.0.0.1)
	@echo "$(GREEN)Starting Symfony server on https://api.localhost:8000 with mkcert p12$(RESET)"
	symfony server:start --port=8000 --p12=certs/api.localhost.nopass.p12

stop: ## Stop Symfony server
	@echo "$(YELLOW)Stopping Symfony server...$(RESET)"
	symfony server:stop

test: ## Run tests
	@echo "$(GREEN)Running tests...$(RESET)"
	php bin/phpunit

cache-clear: ## Clear Symfony cache
	@echo "$(GREEN)Clearing cache...$(RESET)"
	php bin/console cache:clear

clean: ## Clean cache and logs
	@echo "$(GREEN)Cleaning cache and logs...$(RESET)"
	rm -rf var/cache/*
	rm -rf var/log/*
	@echo "$(GREEN)✓ Cleaned$(RESET)"

logs: ## Show Symfony logs
	@tail -f var/log/dev.log

traefik-start: ## Start Traefik reverse proxy
	@echo "$(GREEN)Starting Traefik...$(RESET)"
	@bash ../infra/traefik/start-traefik.sh

traefik-stop: ## Stop Traefik reverse proxy
	@echo "$(YELLOW)Stopping Traefik...$(RESET)"
	@bash ../infra/traefik/stop-traefik.sh

.DEFAULT_GOAL := help
