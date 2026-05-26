#!/usr/bin/env sh
set -eu

cd "$(dirname "$0")/../.."

echo "[deploy] Fetching latest code..."
git fetch --all --prune
git reset --hard origin/main

if [ -f apps/chku-backend/.env ] && grep -q '^APP_KEY=' apps/chku-backend/.env; then
  if ! grep -qE '^APP_KEY=.+' apps/chku-backend/.env; then
    echo "[deploy] APP_KEY is empty, generating..."
    sed -i "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" apps/chku-backend/.env
  fi
else
  echo "[deploy] APP_KEY not found, generating..."
  [ -f apps/chku-backend/.env ] || cp apps/chku-backend/.env.example apps/chku-backend/.env
  echo "APP_KEY=base64:$(openssl rand -base64 32)" >> apps/chku-backend/.env
fi

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
