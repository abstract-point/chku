#!/usr/bin/env sh
set -eu

cd "$(dirname "$0")/../.."

SNAPSHOT_PATH=$(infra/scripts/backup-local.sh)

infra/scripts/backup-restic.sh "$SNAPSHOT_PATH"

echo "Backup snapshot saved locally and uploaded to restic: ${SNAPSHOT_PATH}"
