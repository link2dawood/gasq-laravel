# GASQ Laravel — Make targets for artisan, Docker, and common tasks
# Use Docker: make migrate  (default, uses docker compose)
# Local PHP:  make migrate DOCKER=0
#
# React UI (gasq-calculator-project) lives in its own repo.
# Point REACT_DIST at that project's dist/ folder to sync built assets here:
#   make sync-react-ui REACT_DIST=../gasq-calculator/dist
REACT_DIST ?= ../gasq-calculator/dist

DOCKER ?= 1
COMPOSE = docker compose
APP_SVC = app

ifeq ($(DOCKER),1)
  ARTISAN = $(COMPOSE) exec $(APP_SVC) php artisan
  COMPOSER = $(COMPOSE) exec $(APP_SVC) composer
else
  ARTISAN = php artisan
  COMPOSER = composer
endif

.PHONY: help install key-generate migrate migrate-fresh migrate-rollback migrate-status \
	optimize optimize-clear config-clear cache-clear view-clear route-clear \
	queue-work queue-restart db-seed test serve dusk \
	up down build rebuild fresh-start logs shell \
	composer-install composer-update sync-react-ui \
	storage-link tinker route-list event-list artisan

# Default target
help:
	@echo "GASQ Laravel — available targets (DOCKER=1 by default; use DOCKER=0 for local PHP):"
	@echo ""
	@echo "  Setup & install"
	@echo "    make install          - composer install + key-generate + migrate (Docker)"
	@echo "    make key-generate     - php artisan key:generate"
	@echo "    make composer-install"
	@echo "    make composer-update  - composer update (e.g. after adding a package)"
	@echo "    make storage-link     - php artisan storage:link"
	@echo "    make sync-react-ui    - copy gasq-calculator dist/ into public/react-ui/"
	@echo "                           (override path: make sync-react-ui REACT_DIST=/path/to/dist)"
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
	@echo "    make build           - docker compose build --no-cache"
	@echo "    make rebuild         - down + build + up"
	@echo "    make fresh-start     - full first-time setup (build + up + composer + key + migrate)"
	@echo "    make logs            - follow app container logs"
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

# Sync the built React UI into Laravel's public folder.
# Build the React project first (cd gasq-calculator && npm run build), then run this.
sync-react-ui:
ifndef REACT_DIST
	$(error REACT_DIST is not set. Example: make sync-react-ui REACT_DIST=../gasq-calculator/dist)
endif
	@test -d "$(REACT_DIST)" || (echo "ERROR: $(REACT_DIST) does not exist"; exit 1)
	@echo "Syncing $(REACT_DIST) → public/react-ui/ ..."
	rsync -a --delete "$(REACT_DIST)/" public/react-ui/
	@echo "Done. Commit public/react-ui/ if you want to track the updated build."

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
	$(COMPOSE) build --no-cache

rebuild: down build up
	@echo "Done: rebuild and restart"

# First-time setup: build image, start, then run install (composer + key + migrate)
fresh-start: down build up install
	@echo "Done: fresh-start"

logs:
	$(COMPOSE) logs -f $(APP_SVC)

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
