#!/usr/bin/env sh
set -eu

cd "$(dirname "$0")/../.."

docker compose --env-file apps/chku-backend/.env -f infra/docker/prod/docker-compose.yml up -d --build
