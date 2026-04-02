# Bolakaz

Bolakaz is a PHP storefront and admin system with offline-first sales support, sync tooling, tracked database migrations, and a plain-English admin guide for staff.

## Official Setup Flow

1. Copy `.env.example` to `.env`.
2. Fill in the real environment values.
3. Run `composer install`.
4. Run one of the setup commands:
   - Local client machine: `php bolakaz setup --role=client`
   - Live server: `php bolakaz setup --role=server`
5. Run `php bolakaz doctor` to confirm everything is healthy.

## Quickstart: Local Client Machine

Use this for the local-first machine in the shop.

1. Clone the repo into your local web root.
2. Copy `.env.example` to `.env`.
3. Set the local database values:
   - `DB_HOST`
   - `DB_PORT`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`
4. Set the local app values:
   - `APP_ENV=local` for local development, or another non-local value if you want captcha enforced locally.
   - `APP_URL` to the local base URL, for example `http://bolakaz.test`
5. Set local sync values:
   - `SYNC_ROLE=client`
   - `SYNC_ENABLED=true` only when the live server is ready
   - `SYNC_DEVICE_ID=mom-pc-01`
   - `SYNC_DEVICE_NAME="Mom PC 01"`
   - `SYNC_SERVER_URL=https://your-live-domain.com/bolakaz`
   - `SYNC_TOKEN=the-shared-secret-used-on-both-sides`
6. Set live integrations you need locally:
   - `HCAPTCHA_SITE_KEY`
   - `HCAPTCHA_SECRET_KEY`
   - mail values if you want email working
7. Install dependencies:

```bash
composer install
```

8. Build the schema:

```bash
php bolakaz setup --role=client
```

9. If this is a brand new demo or blank database and you want starter data:

```bash
php bolakaz setup --role=client --seed-minimum
```

10. Verify the install:

```bash
php bolakaz doctor
```

## Quickstart: Live Server

Use this for the hosted copy.

1. Clone or pull the latest code onto the server.
2. Copy `.env.example` to `.env` if the file does not exist yet.
3. Set production values:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://your-live-domain.com/bolakaz`
4. Set database credentials for the live database.
5. Set sync values:
   - `SYNC_ROLE=server`
   - `SYNC_ENABLED=true`
   - `SYNC_DEVICE_ID=bolakaz-live-01`
   - `SYNC_DEVICE_NAME="Bolakaz Live Server"`
   - `SYNC_SERVER_URL=https://your-live-domain.com/bolakaz`
   - `SYNC_TOKEN=the-same-shared-token-used-on-the-client`
6. Set production integrations:
   - `HCAPTCHA_SITE_KEY`
   - `HCAPTCHA_SECRET_KEY`
   - SMTP or other mail values
7. Install dependencies:

```bash
composer install --no-dev
```

8. Build or update the schema:

```bash
php bolakaz setup --role=server
```

9. Verify the install:

```bash
php bolakaz doctor
```

## Post-Pull Update Flow

After `git pull`, use this order:

```bash
composer install --no-dev
php bolakaz migrate
php bolakaz doctor
```

If the pull included demo data changes you explicitly want in a blank environment, run:

```bash
php bolakaz seed:minimum
```

## Command Matrix

| Command | What it does | When to run it | Safe to rerun | How to know it was already done |
| --- | --- | --- | --- | --- |
| `composer install` | Installs PHP dependencies. | First setup and after dependency changes. | Yes. | Composer says nothing new was installed or updated. |
| `php bolakaz doctor` | Checks `.env`, dependencies, DB, migrations, sync, and key settings. | Any time you want a health check. | Yes. | It reports `[PASS]` or tells you exactly what still needs fixing. |
| `php bolakaz setup --role=client` | Prepares metadata and runs pending migrations for the local client machine. | First setup on the local machine. | Yes. | It prints `Setup already completed. Nothing to do.` when nothing new is needed. |
| `php bolakaz setup --role=server` | Prepares metadata and runs pending migrations for the live server. | First setup on the live server. | Yes. | It prints `Setup already completed. Nothing to do.` when nothing new is needed. |
| `php bolakaz migrate` | Runs only pending tracked migrations and adopts old databases into Doctrine tracking. | After `git pull` or any schema change. | Yes. | It prints `[SKIPPED] No pending migrations to apply.` |
| `php bolakaz migrate:status` | Shows executed and pending migration versions without changing data. | When you want to inspect migration state. | Yes. | It prints `Pending migrations: 0.` when fully up to date. |
| `php bolakaz seed:minimum` | Creates or refreshes starter users, sample catalog rows, and starter sales data. | Only when you want starter/demo data. | Yes. | It reports existing rows as updated or reused instead of endlessly duplicating them. |
| `php bolakaz migrate:fresh` | Drops app tables and rebuilds everything from tracked migrations. | Only on disposable databases or when you intentionally want a clean reset. | No for real data. Yes only if you truly want another full wipe. | It asks for `CONFIRM` unless you use `--force`. This is the destructive command. |
| `php bolakaz migrate:fresh --seed-minimum` | Fresh rebuild plus starter seed. | Blank demos, test databases, or intentional full resets. | Same warning as above. | It is only “already done” if you want to wipe and rebuild again. |

## Migration Rules

- Normal schema changes must be added as new tracked Doctrine migration files in `database/migrations`.
- `php bolakaz migrate` is the normal update path.
- `php bolakaz migrate` does not drop data.
- `php bolakaz migrate:fresh` is destructive and should only be used when you intentionally want a clean rebuild.
- Older standalone scripts under `database/` are now legacy references, not the official day-to-day migration flow.
- `database/repair_sync_backfill.php` is a manual repair tool for special sync trouble. Do not use it as part of normal setup.

## New-Machine Notes

### If the database is completely blank

Use:

```bash
php bolakaz setup --role=client
```

or:

```bash
php bolakaz setup --role=server
```

This will create migration metadata and run the full tracked migration list.

### If the database already exists from an older install

Use:

```bash
php bolakaz migrate
```

The CLI will:

1. Create Doctrine metadata if it is missing.
2. Mark the baseline schema as already present.
3. Run only the later idempotent tracked migrations that still need to be applied.

This means old data is not supposed to be rebuilt from scratch just because tracking was added later.

## Sync Environment Notes

Use the detailed examples in [`sync/ENV-SETUP.md`](sync/ENV-SETUP.md).

Short version:

- Local shop machine:
  - `SYNC_ROLE=client`
  - `SYNC_DEVICE_ID` and `SYNC_DEVICE_NAME` should be specific to that device
  - `SYNC_SERVER_URL` should point to the live hosted app
  - `SYNC_TOKEN` must match the server
- Live hosted app:
  - `SYNC_ROLE=server`
  - `SYNC_DEVICE_ID` and `SYNC_DEVICE_NAME` should identify the live server
  - `SYNC_SERVER_URL` should also point to the live hosted app
  - `SYNC_TOKEN` must match the client

## Troubleshooting

### App URL or host is not loading

- Check that your local host or web server points to the project folder.
- Check `APP_URL` in `.env`.
- On local Laragon setups, make sure the local host name actually exists.
- Run `php bolakaz doctor` and fix any `APP_URL` or dependency warnings.

### Database connection failed

- Recheck `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS`.
- Make sure MySQL is running.
- Run:

```bash
php bolakaz doctor
```

- The command will print a direct fix hint under the failure.

### Pending migrations remain

- Run:

```bash
php bolakaz migrate:status
php bolakaz migrate
```

- If the database is from an old install, `php bolakaz migrate` will adopt it into Doctrine tracking automatically.

### Sync is not working

- Confirm `SYNC_ENABLED=true`.
- Confirm `SYNC_ROLE` is correct for that machine.
- Confirm `SYNC_SERVER_URL` is the live hosted base URL.
- Confirm `SYNC_TOKEN` matches on both systems.
- Run:

```bash
php bolakaz doctor
```

- If you still have UUID/backfill trouble after migrations are already current, the manual repair path is:

```bash
php database/repair_sync_backfill.php
```

Only use that when you are troubleshooting sync specifically.

### Camera or barcode scan is not working

- On the offline sale screen, try <strong>Fallback Picker</strong>.
- Camera scanning usually needs HTTPS or localhost.
- If scan still fails, type the SKU directly, for example `BLKZ-000123`, or only the number part like `123`.

## Staff Guide

For the non-technical user guide inside the app, sign in to admin and open:

- `Help`
- `How To Use App`

That page explains the real admin workflow in plain English for staff.
