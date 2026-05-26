#!/usr/bin/env sh
set -eu

cd "$(dirname "$0")/../.."

echo "[deploy] Fetching latest code..."
git fetch --all --prune
git reset --hard origin/main

echo "[deploy] Building and starting production containers..."
make prod

echo "[deploy] Running migrations..."
docker compose --env-file apps/chku-backend/.env \
  -f infra/docker/prod/docker-compose.yml \
  exec -T backend php artisan migrate --force

echo "[deploy] Optimizing Laravel..."
docker compose --env-file apps/chku-backend/.env \
  -f infra/docker/prod/docker-compose.yml \
  exec -T backend php artisan optimize

echo "[deploy] Restarting queue workers..."
docker compose --env-file apps/chku-backend/.env \
  -f infra/docker/prod/docker-compose.yml \
  exec -T backend php artisan queue:restart

echo "[deploy] Complete."
