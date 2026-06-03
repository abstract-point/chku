# Backups

Production backups are created locally on the VPS first, then uploaded to an
external S3-compatible restic repository.

## Full Backup

Run the full production backup flow:

```sh
cd /var/www/chku
make backup
```

This command:

1. creates a local snapshot with the database dump and storage archive;
2. uploads that completed snapshot to restic/S3;
3. applies the restic retention policy.

If restic upload or retention fails, `make backup` exits with a non-zero status
so cron or monitoring can report the problem. The local snapshot remains on the
VPS.

## Local Snapshot

Create only the local snapshot without S3 upload:

```sh
cd /var/www/chku
make backup-local
```

By default, snapshots are stored in:

```text
/var/backups/chku/snapshots/YYYY-mm-dd_HH-MM-SS/
```

Each snapshot contains:

- `database/chku.dump` - PostgreSQL custom-format dump.
- `storage/backend_storage_public.tar.gz` - archive of the Laravel public storage Docker volume.
- `manifest.env` - backup metadata.
- `SHA256SUMS` - checksums for the snapshot files.

The local defaults can be overridden:

```sh
CHKU_BACKUP_DIR=/var/backups/chku \
CHKU_BACKUP_RETENTION_DAYS=30 \
make backup-local
```

Local snapshot directories older than `CHKU_BACKUP_RETENTION_DAYS` are removed
after a successful new local backup. The default retention is 30 days.

## Restic to S3

Restic runs on the VPS host, not inside Docker. Keep restic credentials outside
git, for example in `/etc/chku/restic.env`:

```sh
export RESTIC_REPOSITORY="s3:https://s3.storage.selcloud.ru/YOUR_BUCKET/chku"
export AWS_ACCESS_KEY_ID="YOUR_SELECTEL_KEY"
export AWS_SECRET_ACCESS_KEY="YOUR_SELECTEL_SECRET"
export RESTIC_PASSWORD="YOUR_RESTIC_PASSWORD"
```

Initialize the repository once on the VPS:

```sh
. /etc/chku/restic.env
restic init
```

Upload an existing local snapshot manually:

```sh
make backup-restic SNAPSHOT=/var/backups/chku/snapshots/YYYY-mm-dd_HH-MM-SS
```

The restic defaults can be overridden:

```sh
CHKU_RESTIC_ENV=/etc/chku/restic.env \
CHKU_RESTIC_TAG=chku \
CHKU_RESTIC_KEEP_DAILY=14 \
CHKU_RESTIC_KEEP_WEEKLY=8 \
CHKU_RESTIC_KEEP_MONTHLY=12 \
make backup-restic SNAPSHOT=/var/backups/chku/snapshots/YYYY-mm-dd_HH-MM-SS
```

Check uploaded snapshots:

```sh
. /etc/chku/restic.env
restic snapshots --tag chku
```

## Cron

Example daily full backup at 02:30:

```cron
30 2 * * * cd /var/www/chku && make backup >> /var/log/chku-backup.log 2>&1
```

## Restore Smoke Checks

Check a database dump:

```sh
pg_restore --list /var/backups/chku/snapshots/YYYY-mm-dd_HH-MM-SS/database/chku.dump
```

Check the storage archive:

```sh
tar -tzf /var/backups/chku/snapshots/YYYY-mm-dd_HH-MM-SS/storage/backend_storage_public.tar.gz
```

Check the restic repository when practical:

```sh
. /etc/chku/restic.env
restic check
```
