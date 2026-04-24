DEV_COMPOSE := infra/docker/dev/docker-compose.yml
PROD_COMPOSE := infra/docker/prod/docker-compose.yml

.PHONY: dev dev-build dev-down dev-logs prod prod-build prod-down prod-logs deploy backup-db restore-db

dev:
	docker compose -f $(DEV_COMPOSE) up -d

dev-build:
	docker compose -f $(DEV_COMPOSE) build

dev-down:
	docker compose -f $(DEV_COMPOSE) down

dev-logs:
	docker compose -f $(DEV_COMPOSE) logs -f

prod:
	docker compose -f $(PROD_COMPOSE) up -d --build

prod-build:
	docker compose -f $(PROD_COMPOSE) build

prod-down:
	docker compose -f $(PROD_COMPOSE) down

prod-logs:
	docker compose -f $(PROD_COMPOSE) logs -f

deploy:
	infra/deploy/deploy.sh

backup-db:
	infra/scripts/backup-db.sh

restore-db:
	infra/scripts/restore-db.sh
