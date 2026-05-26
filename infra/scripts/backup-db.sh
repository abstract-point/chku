#!/usr/bin/env sh
set -eu

cd "$(dirname "$0")/../.."

. apps/chku-backend/.env

BACKUP_DIR="backups"
mkdir -p "$BACKUP_DIR"

TIMESTAMP=$(date +%Y-%m-%d_%H-%M-%S)
FILENAME="chku_${TIMESTAMP}.sql"

docker compose --env-file apps/chku-backend/.env \
  -f infra/docker/prod/docker-compose.yml \
  exec -T db \
  pg_dump -U "${DB_USERNAME}" -d "${DB_DATABASE}" \
  > "${BACKUP_DIR}/${FILENAME}"

echo "Backup saved to ${BACKUP_DIR}/${FILENAME}"
