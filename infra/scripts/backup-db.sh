#!/usr/bin/env sh
set -eu

cd "$(dirname "$0")/../.."

CHKU_BACKUP_DIR="${CHKU_BACKUP_DIR:-/var/backups/chku}"
CHKU_BACKUP_ENV="${CHKU_BACKUP_ENV:-apps/chku-backend/.env}"
CHKU_BACKUP_COMPOSE="${CHKU_BACKUP_COMPOSE:-infra/docker/prod/docker-compose.yml}"

. "$CHKU_BACKUP_ENV"

if [ "$#" -gt 0 ]; then
  OUTPUT_FILE="$1"
else
  TIMESTAMP=$(date +%Y-%m-%d_%H-%M-%S)
  OUTPUT_FILE="${CHKU_BACKUP_DIR}/snapshots/${TIMESTAMP}/database/chku.dump"
fi

mkdir -p "$(dirname "$OUTPUT_FILE")"
TMP_FILE="${OUTPUT_FILE}.tmp"
rm -f "$TMP_FILE"

docker compose --env-file "$CHKU_BACKUP_ENV" \
  -f "$CHKU_BACKUP_COMPOSE" \
  exec -T db \
  pg_dump -U "${DB_USERNAME}" -d "${DB_DATABASE}" \
    --format=custom \
    --no-owner \
    --no-privileges \
  > "$TMP_FILE"

mv "$TMP_FILE" "$OUTPUT_FILE"

echo "Database backup saved to ${OUTPUT_FILE}"
