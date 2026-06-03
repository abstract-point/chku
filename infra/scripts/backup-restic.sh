#!/usr/bin/env sh
set -eu

cd "$(dirname "$0")/../.."

CHKU_RESTIC_ENV="${CHKU_RESTIC_ENV:-/etc/chku/restic.env}"
CHKU_RESTIC_TAG="${CHKU_RESTIC_TAG:-chku}"
CHKU_RESTIC_KEEP_DAILY="${CHKU_RESTIC_KEEP_DAILY:-14}"
CHKU_RESTIC_KEEP_WEEKLY="${CHKU_RESTIC_KEEP_WEEKLY:-8}"
CHKU_RESTIC_KEEP_MONTHLY="${CHKU_RESTIC_KEEP_MONTHLY:-12}"

if [ "$#" -ne 1 ]; then
  echo "Usage: $0 /path/to/local/snapshot" >&2
  exit 1
fi

SNAPSHOT_PATH="$1"

if [ ! -d "$SNAPSHOT_PATH" ]; then
  echo "Backup snapshot directory does not exist: ${SNAPSHOT_PATH}" >&2
  exit 1
fi

if ! command -v restic >/dev/null 2>&1; then
  echo "restic is not installed on this host." >&2
  exit 1
fi

if [ ! -f "$CHKU_RESTIC_ENV" ]; then
  echo "Restic env file does not exist: ${CHKU_RESTIC_ENV}" >&2
  exit 1
fi

. "$CHKU_RESTIC_ENV"

if [ -z "${RESTIC_REPOSITORY:-}" ]; then
  echo "RESTIC_REPOSITORY is not set in ${CHKU_RESTIC_ENV}." >&2
  exit 1
fi

if [ -z "${RESTIC_PASSWORD:-}" ]; then
  echo "RESTIC_PASSWORD is not set in ${CHKU_RESTIC_ENV}." >&2
  exit 1
fi

echo "Checking restic repository ${RESTIC_REPOSITORY}..."
restic snapshots >/dev/null

echo "Uploading backup snapshot to restic: ${SNAPSHOT_PATH}"
restic backup "$SNAPSHOT_PATH" --tag "$CHKU_RESTIC_TAG"

echo "Applying restic retention policy for tag ${CHKU_RESTIC_TAG}..."
restic forget \
  --tag "$CHKU_RESTIC_TAG" \
  --keep-daily "$CHKU_RESTIC_KEEP_DAILY" \
  --keep-weekly "$CHKU_RESTIC_KEEP_WEEKLY" \
  --keep-monthly "$CHKU_RESTIC_KEEP_MONTHLY" \
  --prune

echo "Restic backup complete for ${SNAPSHOT_PATH}"
