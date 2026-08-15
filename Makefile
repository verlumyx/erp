# ERP - PostgreSQL Development Database
.PHONY: help db-up db-down db-restart db-logs db-shell backup-db restore-db clean app-shell migrate dev install assets watch test

# Default target
help: ## Show this help message
	@echo "ERP - PostgreSQL Commands"
	@echo "==============================="
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# Database Commands
db-up: ## Start PostgreSQL database
	docker compose up -d postgres
	@echo "PostgreSQL started on localhost:5435"
	@echo "Database: erp_dev"
	@echo "User: erp_user"
	@echo "Password: erp_password"

db-down: ## Stop PostgreSQL database
	docker compose down

db-restart: ## Restart PostgreSQL database
	docker compose restart postgres

db-logs: ## Show PostgreSQL logs
	docker compose logs -f postgres

db-shell: ## Access PostgreSQL shell
	docker compose exec postgres psql -U erp_user -d erp_dev

# Application Commands
dev: ## Start the full stack in development (live source + OPcache off)
	docker compose up -d --build
	@echo "App running at http://localhost:8080 (Vite HMR on 5174)."
	@echo "Build the frontend with 'make assets' (or 'make watch'). Run tests with 'make test'."

app-shell: ## Access the application container shell
	docker compose exec app sh

migrate: ## Run database migrations inside the app container
	docker compose exec app php artisan migrate --force

test: ## Run the test suite (usage: make test [args="--filter=Role"])
	docker compose exec \
		-e APP_ENV=testing \
		-e DB_CONNECTION=sqlite \
		-e DB_DATABASE=:memory: \
		app php artisan test $(args)

install: ## Install PHP + frontend dependencies inside the app container
	docker compose exec app composer install
	docker compose exec app pnpm install
	@echo "Dependencies installed."

assets: ## Build frontend assets once (inside the app container)
	docker compose exec app rm -f public/hot
	docker compose exec app pnpm run build
	@echo "Frontend assets rebuilt."

watch: ## Rebuild frontend assets on every change (inside the app container)
	docker compose exec app pnpm run dev

# Maintenance Commands
backup-db: ## Backup database
	docker compose exec postgres pg_dump -U erp_user erp_dev > backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "Database backup created: backup_$(shell date +%Y%m%d_%H%M%S).sql"

restore-db: ## Restore database (usage: make restore-db file=backup.sql)
	docker compose exec -T postgres psql -U erp_user -d erp_dev < $(file)
	@echo "Database restored from $(file)"

clean: ## Clean up Docker resources
	docker compose down -v
	docker system prune -f
	docker volume prune -f
	@echo "Docker resources cleaned"

# Status Commands
status: ## Show container status
	docker compose ps

# Quick aliases
up: db-up ## Alias for db-up
down: db-down ## Alias for db-down
logs: db-logs ## Alias for db-logs
