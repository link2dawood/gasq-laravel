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
	storage-link tinker route-list event-list artisan \
	deploy deploy-run deploy-status deploy-watch

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
	@echo "  Deploy (deploy-run needs GITHUB_TOKEN with repo+workflow scope)"
	@echo "    make deploy          - push main and follow the deploy it triggers"
	@echo "    make deploy-run      - re-deploy current main without a new commit"
	@echo "    make deploy-status   - last five deploy runs"
	@echo "    make deploy-watch    - follow the newest deploy run"
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

# --- Deploy (GitHub Actions -> server beta folder) ---
# Pushing main auto-deploys; see .github/workflows/deploy.yml and
# .github/DEPLOYMENT.md. These targets drive that workflow from the terminal.
# Triggering a deploy needs a GitHub token with `repo` + `workflow` scope:
#   export GITHUB_TOKEN=ghp_xxx     (GH_TOKEN also works)
# Reading run status works without one while the repo is public.
REPO ?= link2dawood/gasq-laravel
DEPLOY_WORKFLOW ?= deploy.yml
DEPLOY_BRANCH ?= main
BETA_URL ?= https://beta.getasecurityquotenow.com
GH_API = https://api.github.com/repos/$(REPO)
TOKEN = $(or $(GITHUB_TOKEN),$(GH_TOKEN))
CURL_GH = curl -sS -H "Accept: application/vnd.github+json" $(if $(TOKEN),-H "Authorization: Bearer $(TOKEN)",)

# Ship what is committed: push main, then follow the deploy it triggers.
deploy:
	@test "$$(git rev-parse --abbrev-ref HEAD)" = "$(DEPLOY_BRANCH)" \
		|| (echo "ERROR: on branch $$(git rev-parse --abbrev-ref HEAD), not $(DEPLOY_BRANCH)"; exit 1)
	@test -z "$$(git status --porcelain)" \
		|| (echo "ERROR: working tree is dirty — commit or stash first"; git status --short; exit 1)
	git push origin $(DEPLOY_BRANCH)
	@$(MAKE) --no-print-directory deploy-watch

# Re-deploy the current main without a new commit (manual workflow_dispatch).
deploy-run:
	@test -n "$(TOKEN)" || (echo "ERROR: set GITHUB_TOKEN (or GH_TOKEN) to a token with repo+workflow scope"; exit 1)
	@$(CURL_GH) -X POST "$(GH_API)/actions/workflows/$(DEPLOY_WORKFLOW)/dispatches" \
		-d '{"ref":"$(DEPLOY_BRANCH)"}' \
		&& echo "Deploy requested on $(DEPLOY_BRANCH)."
	@sleep 5
	@$(MAKE) --no-print-directory deploy-watch

# Last five deploy runs: when, status, commit.
deploy-status:
	@$(CURL_GH) "$(GH_API)/actions/workflows/$(DEPLOY_WORKFLOW)/runs?per_page=5" \
		| jq -r '.workflow_runs[] | "\(.created_at)  \(.status)/\(.conclusion // "-")  \(.head_sha[0:7])  \(.head_commit.message | split("\n")[0])"'

# Poll the newest deploy run until it finishes.
# The JSON goes to a temp file rather than a shell variable: `echo` mangles the
# escaped newlines inside commit messages and jq then chokes on the result.
deploy-watch:
	@tmp=$$(mktemp); \
	while :; do \
		$(CURL_GH) "$(GH_API)/actions/workflows/$(DEPLOY_WORKFLOW)/runs?per_page=1" -o "$$tmp"; \
		status=$$(jq -r '.workflow_runs[0].status' "$$tmp"); \
		concl=$$(jq -r '.workflow_runs[0].conclusion // "-"' "$$tmp"); \
		url=$$(jq -r '.workflow_runs[0].html_url' "$$tmp"); \
		echo "  $$status/$$concl  $$url"; \
		if [ "$$status" = "completed" ]; then \
			rm -f "$$tmp"; \
			[ "$$concl" = "success" ] || { echo "Deploy failed — see the run above."; exit 1; }; \
			break; \
		fi; \
		sleep 15; \
	done; \
	echo "Live: $(BETA_URL)"

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
