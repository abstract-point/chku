DEV_COMPOSE := infra/docker/dev/docker-compose.yml
PROD_COMPOSE := infra/docker/prod/docker-compose.yml
BACKEND_ENV := apps/chku-backend/.env
DOCKER_COMPOSE := docker compose --env-file $(BACKEND_ENV)
DEV_DOCKER_COMPOSE := $(DOCKER_COMPOSE) -f $(DEV_COMPOSE)
PROD_DOCKER_COMPOSE := $(DOCKER_COMPOSE) -f $(PROD_COMPOSE)

.PHONY: dev dev-build dev-down dev-logs prod prod-build prod-down prod-logs backend-install backend-key prod-backend-key backend-migrate backend-shell backend-test deploy backup-db restore-db

dev:
	$(DEV_DOCKER_COMPOSE) up -d --build

dev-build:
	$(DEV_DOCKER_COMPOSE) build

dev-down:
	$(DEV_DOCKER_COMPOSE) down

dev-logs:
	$(DEV_DOCKER_COMPOSE) logs -f

prod:
	$(PROD_DOCKER_COMPOSE) up -d --build

prod-build:
	$(PROD_DOCKER_COMPOSE) build

prod-down:
	$(PROD_DOCKER_COMPOSE) down

prod-logs:
	$(PROD_DOCKER_COMPOSE) logs -f

backend-install:
	$(DEV_DOCKER_COMPOSE) run --rm backend composer install

backend-key:
	$(DEV_DOCKER_COMPOSE) run --rm backend php artisan key:generate

prod-backend-key:
	@if grep -qE '^APP_KEY=.+' $(BACKEND_ENV) 2>/dev/null; then \
		echo "APP_KEY is already set."; \
	elif [ -f $(BACKEND_ENV) ] && grep -q '^APP_KEY=' $(BACKEND_ENV); then \
		sed -i "s|^APP_KEY=.*|APP_KEY=base64:$$(openssl rand -base64 32)|" $(BACKEND_ENV); \
		echo "APP_KEY generated."; \
	else \
		[ -f $(BACKEND_ENV) ] || cp apps/chku-backend/.env.example apps/chku-backend/.env; \
		echo "APP_KEY=base64:$$(openssl rand -base64 32)" >> $(BACKEND_ENV); \
		echo "APP_KEY generated."; \
	fi

backend-migrate:
	$(DEV_DOCKER_COMPOSE) exec backend php artisan migrate

backend-fresh:
	$(DEV_DOCKER_COMPOSE) exec backend php artisan migrate:fresh --seed

backend-shell:
	$(DEV_DOCKER_COMPOSE) exec backend sh

backend-test:
	$(DEV_DOCKER_COMPOSE) exec backend php artisan test

deploy:
	infra/deploy/deploy.sh

backup-db:
	infra/scripts/backup-db.sh

restore-db:
	infra/scripts/restore-db.sh
