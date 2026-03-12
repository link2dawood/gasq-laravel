# GASQ Laravel — Make targets for artisan, Docker, and common tasks
# Use Docker: make migrate  (default, uses docker compose)
# Local PHP:  make migrate DOCKER=0

DOCKER ?= 1
COMPOSE = docker compose
APP_SVC = app

ifeq ($(DOCKER),1)
  ARTISAN = $(COMPOSE) exec $(APP_SVC) php artisan
  COMPOSER = $(COMPOSE) exec $(APP_SVC) composer
  NPM_RUN = $(COMPOSE) run --rm $(APP_SVC) npm
else
  ARTISAN = php artisan
  COMPOSER = composer
  NPM_RUN = npm run
endif

.PHONY: help install key-generate migrate migrate-fresh migrate-rollback migrate-status \
	optimize optimize-clear config-clear cache-clear view-clear route-clear \
	queue-work queue-restart db-seed test serve dusk \
	up down build shell composer-install composer-update npm-install npm-dev npm-build \
	storage-link horizon tinker route-list event-list artisan

# Default target
help:
	@echo "GASQ Laravel — available targets (DOCKER=1 by default; use DOCKER=0 for local PHP):"
	@echo ""
	@echo "  Setup & install"
	@echo "    make install          - composer install + key-generate + migrate (Docker)"
	@echo "    make key-generate     - php artisan key:generate"
	@echo "    make composer-install"
	@echo "    make composer-update  - composer update (e.g. after adding a package)"
	@echo "    make npm-install      - npm install (in container or host)"
	@echo "    make storage-link     - php artisan storage:link"
	@echo ""
	@echo "  Database"
	@echo "    make migrate          - php artisan migrate"
	@echo "    make migrate-fresh    - migrate:fresh (drops all tables)"
	@echo "    make migrate-rollback - migrate:rollback"
	@echo "    make migrate-status   - migrate:status"
	@echo "    make db-seed          - db:seed"
	@echo ""
	@echo "  Optimize & cache"
	@echo "    make optimize        - config + route + view cache"
	@echo "    make optimize-clear  - clear all caches"
	@echo "    make config-clear"
	@echo "    make cache-clear"
	@echo "    make view-clear"
	@echo "    make route-clear"
	@echo ""
	@echo "  Queue & dev"
	@echo "    make queue-work      - queue:work"
	@echo "    make queue-restart   - queue:restart"
	@echo "    make serve           - php artisan serve"
	@echo "    make tinker          - php artisan tinker"
	@echo ""
	@echo "  Lists & info"
	@echo "    make route-list      - route:list"
	@echo "    make event-list      - event:list"
	@echo ""
	@echo "  Docker"
	@echo "    make up              - docker compose up -d (app :8082, phpMyAdmin :8083)"
	@echo "    make down            - docker compose down"
	@echo "    make build           - docker compose build"
	@echo "    make shell           - shell into app container"
	@echo ""
	@echo "  Tests"
	@echo "    make test            - php artisan test"
	@echo "    make dusk            - php artisan dusk (if installed)"
	@echo ""
	@echo "  Any Artisan command"
	@echo "    make artisan cmd='migrate:status'"
	@echo "    make artisan cmd='make:controller Foo'"
	@echo ""

# --- Setup & install ---
install: composer-install key-generate migrate
	@echo "Done: install (composer, key, migrate)"

key-generate:
	$(ARTISAN) key:generate

composer-install:
	$(COMPOSER) install --no-interaction

composer-update:
	$(COMPOSER) update --no-interaction

npm-install:
	$(NPM_RUN) install

npm-dev:
	$(NPM_RUN) run dev

npm-build:
	$(NPM_RUN) run prod

storage-link:
	$(ARTISAN) storage:link

# --- Database ---
migrate:
	$(ARTISAN) migrate

migrate-fresh:
	$(ARTISAN) migrate:fresh

migrate-fresh-seed: migrate-fresh db-seed

migrate-rollback:
	$(ARTISAN) migrate:rollback

migrate-status:
	$(ARTISAN) migrate:status

db-seed:
	$(ARTISAN) db:seed

# --- Optimize & cache ---
optimize:
	$(ARTISAN) optimize

optimize-clear:
	$(ARTISAN) optimize:clear

config-clear:
	$(ARTISAN) config:clear

cache-clear:
	$(ARTISAN) cache:clear

view-clear:
	$(ARTISAN) view:clear

route-clear:
	$(ARTISAN) route:clear

# Combined clear (common dev shortcut)
clear: config-clear cache-clear view-clear route-clear
	@echo "Cleared config, cache, view, route"

# --- Queue ---
queue-work:
	$(ARTISAN) queue:work

queue-restart:
	$(ARTISAN) queue:restart

# --- Serve & shells ---
serve:
	$(ARTISAN) serve

tinker:
	$(ARTISAN) tinker

# --- Lists ---
route-list:
	$(ARTISAN) route:list

event-list:
	$(ARTISAN) event:list

# --- Docker ---
up:
	$(COMPOSE) up -d

down:
	$(COMPOSE) down

build:
	$(COMPOSE) build

shell:
	$(COMPOSE) exec $(APP_SVC) sh

# --- Tests ---
test:
	$(ARTISAN) test

dusk:
	$(ARTISAN) dusk

# --- Any Artisan command (use: make artisan cmd="migrate:status") ---
artisan:
ifndef cmd
	@echo "Usage: make artisan cmd=\"<artisan command>\""
	@echo "Example: make artisan cmd=\"migrate:status\""
	@exit 1
endif
	$(ARTISAN) $(cmd)

# --- Optional Laravel commands (uncomment if you use them)
# horizon:
# 	$(ARTISAN) horizon
# schedule-run:
# 	$(ARTISAN) schedule:run
# schedule-list:
# 	$(ARTISAN) schedule:list
