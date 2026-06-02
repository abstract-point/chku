# Backups

Production backups are created locally on the VPS first. Restic should back up
the completed local snapshots to Selectel S3.

## Local snapshot

Create a full local snapshot:

```sh
cd /var/www/chku
make backup
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
CHKU_BACKUP_RETENTION_DAYS=14 \
make backup
```

Local snapshot directories older than `CHKU_BACKUP_RETENTION_DAYS` are removed
after a successful new backup. The default retention is 14 days.

## Cron

Example daily local backup at 02:30:

```cron
30 2 * * * cd /var/www/chku && make backup >> /var/log/chku-backup.log 2>&1
```

## Restic to Selectel S3

Keep restic credentials outside git, for example in
`/etc/chku/restic.env`:

```sh
export RESTIC_REPOSITORY="s3:https://s3.storage.selcloud.ru/YOUR_BUCKET/chku"
export AWS_ACCESS_KEY_ID="YOUR_SELECTEL_KEY"
export AWS_SECRET_ACCESS_KEY="YOUR_SELECTEL_SECRET"
export RESTIC_PASSWORD="YOUR_RESTIC_PASSWORD"
```

Example backup command:

```sh
. /etc/chku/restic.env
restic backup /var/backups/chku/snapshots
```

Example retention command:

```sh
. /etc/chku/restic.env
restic forget --keep-daily 14 --keep-weekly 8 --keep-monthly 12 --prune
```

## Restore smoke checks

Check a database dump:

```sh
pg_restore --list /var/backups/chku/snapshots/YYYY-mm-dd_HH-MM-SS/database/chku.dump
```

Check the storage archive:

```sh
tar -tzf /var/backups/chku/snapshots/YYYY-mm-dd_HH-MM-SS/storage/backend_storage_public.tar.gz
```
