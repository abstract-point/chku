#!/usr/bin/env sh
set -eu

cd "$(dirname "$0")/../.."

CHKU_BACKUP_DIR="${CHKU_BACKUP_DIR:-/var/backups/chku}"
CHKU_BACKUP_ENV="${CHKU_BACKUP_ENV:-apps/chku-backend/.env}"
CHKU_BACKUP_COMPOSE="${CHKU_BACKUP_COMPOSE:-infra/docker/prod/docker-compose.yml}"
CHKU_STORAGE_CONTAINER_PATH="${CHKU_STORAGE_CONTAINER_PATH:-/var/www/backend/storage/app/public}"

if [ "$#" -gt 0 ]; then
  OUTPUT_FILE="$1"
else
  TIMESTAMP=$(date +%Y-%m-%d_%H-%M-%S)
  OUTPUT_FILE="${CHKU_BACKUP_DIR}/snapshots/${TIMESTAMP}/storage/backend_storage_public.tar.gz"
fi

BACKEND_CONTAINER_ID=$(docker compose --env-file "$CHKU_BACKUP_ENV" \
  -f "$CHKU_BACKUP_COMPOSE" \
  ps -q backend)

if [ -z "$BACKEND_CONTAINER_ID" ]; then
  echo "Could not find a running backend container. Start production containers first." >&2
  exit 1
fi

STORAGE_VOLUME_NAME=$(docker inspect \
  --format "{{range .Mounts}}{{if eq .Destination \"${CHKU_STORAGE_CONTAINER_PATH}\"}}{{.Name}}{{end}}{{end}}" \
  "$BACKEND_CONTAINER_ID")

if [ -z "$STORAGE_VOLUME_NAME" ]; then
  echo "Could not resolve Docker volume mounted at ${CHKU_STORAGE_CONTAINER_PATH}." >&2
  exit 1
fi

mkdir -p "$(dirname "$OUTPUT_FILE")"
TMP_FILE="${OUTPUT_FILE}.tmp"
rm -f "$TMP_FILE"

docker compose --env-file "$CHKU_BACKUP_ENV" \
  -f "$CHKU_BACKUP_COMPOSE" \
  run --rm --no-deps -T \
  --entrypoint sh \
  -v "${STORAGE_VOLUME_NAME}:/backup-source:ro" \
  backend \
  -c 'tar -C /backup-source -czf - .' \
  > "$TMP_FILE"

mv "$TMP_FILE" "$OUTPUT_FILE"

echo "Storage volume backup saved to ${OUTPUT_FILE}"
