#!/usr/bin/env sh
set -eu

cd "$(dirname "$0")/../.."

CHKU_BACKUP_DIR="${CHKU_BACKUP_DIR:-/var/backups/chku}"
CHKU_BACKUP_RETENTION_DAYS="${CHKU_BACKUP_RETENTION_DAYS:-30}"
CHKU_BACKUP_ENV="${CHKU_BACKUP_ENV:-apps/chku-backend/.env}"
CHKU_BACKUP_COMPOSE="${CHKU_BACKUP_COMPOSE:-infra/docker/prod/docker-compose.yml}"

export CHKU_BACKUP_DIR
export CHKU_BACKUP_ENV
export CHKU_BACKUP_COMPOSE

TIMESTAMP=$(date +%Y-%m-%d_%H-%M-%S)
SNAPSHOT_ROOT="${CHKU_BACKUP_DIR}/snapshots"
TMP_ROOT="${CHKU_BACKUP_DIR}/tmp"
TMP_SNAPSHOT="${TMP_ROOT}/${TIMESTAMP}.$$"
FINAL_SNAPSHOT="${SNAPSHOT_ROOT}/${TIMESTAMP}"

cleanup_tmp() {
  if [ -d "$TMP_SNAPSHOT" ]; then
    rm -rf "$TMP_SNAPSHOT"
  fi
}

trap cleanup_tmp EXIT INT TERM

mkdir -p "$SNAPSHOT_ROOT" "$TMP_ROOT"
mkdir -p "$TMP_SNAPSHOT/database" "$TMP_SNAPSHOT/storage"

infra/scripts/backup-db.sh "$TMP_SNAPSHOT/database/chku.dump" >&2
infra/scripts/backup-storage.sh "$TMP_SNAPSHOT/storage/backend_storage_public.tar.gz" >&2

GIT_COMMIT=$(git rev-parse --short HEAD 2>/dev/null || printf "unknown")
HOSTNAME_VALUE=$(hostname 2>/dev/null || printf "unknown")

cat > "$TMP_SNAPSHOT/manifest.env" <<EOF
BACKUP_TIMESTAMP=${TIMESTAMP}
BACKUP_HOST=${HOSTNAME_VALUE}
GIT_COMMIT=${GIT_COMMIT}
BACKUP_ENV=${CHKU_BACKUP_ENV}
BACKUP_COMPOSE=${CHKU_BACKUP_COMPOSE}
DATABASE_DUMP=database/chku.dump
STORAGE_ARCHIVE=storage/backend_storage_public.tar.gz
STORAGE_VOLUME=backend_storage_public
RETENTION_DAYS=${CHKU_BACKUP_RETENTION_DAYS}
EOF

(
  cd "$TMP_SNAPSHOT"
  sha256sum database/chku.dump storage/backend_storage_public.tar.gz manifest.env > SHA256SUMS
)

if [ -e "$FINAL_SNAPSHOT" ]; then
  echo "Backup snapshot already exists: ${FINAL_SNAPSHOT}" >&2
  exit 1
fi

mv "$TMP_SNAPSHOT" "$FINAL_SNAPSHOT"
trap - EXIT INT TERM

find "$SNAPSHOT_ROOT" \
  -mindepth 1 \
  -maxdepth 1 \
  -type d \
  -name "????-??-??_??-??-??" \
  -mtime "+${CHKU_BACKUP_RETENTION_DAYS}" \
  -exec rm -rf {} \;

echo "Local backup snapshot saved to ${FINAL_SNAPSHOT}" >&2
printf "%s\n" "$FINAL_SNAPSHOT"
