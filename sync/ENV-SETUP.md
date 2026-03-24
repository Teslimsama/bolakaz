## Local Client `.env`

Use this on your mom's PC / local-first machine:

```dotenv
SYNC_ROLE=client
SYNC_ENABLED=true
SYNC_DEVICE_ID=mom-pc-01
SYNC_DEVICE_NAME="Mom PC 01"
SYNC_SERVER_URL=https://your-live-domain.com/bolakaz
SYNC_TOKEN=use-the-same-token-from-the-local-dotenv
SYNC_PING_ENDPOINT=/sync/ping
SYNC_PUSH_ENDPOINT=/sync/push
SYNC_PUSH_MEDIA_ENDPOINT=/sync/push-media
SYNC_PULL_ENDPOINT=/sync/pull
SYNC_BATCH_SIZE=20
SYNC_TIMEOUT_SECONDS=10
SYNC_MAX_ATTEMPTS=10
SYNC_RETRY_BACKOFF_MINUTES=5
```

## Live Server `.env`

Use this on the hosted/server copy of the same app:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-live-domain.com/bolakaz

SYNC_ROLE=server
SYNC_ENABLED=true
SYNC_DEVICE_ID=bolakaz-live-01
SYNC_DEVICE_NAME="Bolakaz Live Server"
SYNC_SERVER_URL=https://your-live-domain.com/bolakaz
SYNC_TOKEN=use-the-same-token-from-the-local-dotenv
SYNC_PING_ENDPOINT=/sync/ping
SYNC_PUSH_ENDPOINT=/sync/push
SYNC_PUSH_MEDIA_ENDPOINT=/sync/push-media
SYNC_PULL_ENDPOINT=/sync/pull
SYNC_BATCH_SIZE=20
SYNC_TIMEOUT_SECONDS=10
SYNC_MAX_ATTEMPTS=10
SYNC_RETRY_BACKOFF_MINUTES=5
```

## What To Replace

- Replace `https://your-live-domain.com/bolakaz` with the real public base URL of the hosted app.
- Use the exact same `SYNC_TOKEN` on both client and server.
- Leave the endpoint paths as-is unless you move the sync PHP files.

## Current Local State

The local [`.env`](C:/laragon/www/bolakaz/.env) already has:

- `SYNC_ROLE=client`
- `SYNC_DEVICE_ID=mom-pc-01`
- `SYNC_DEVICE_NAME="Mom PC 01"`
- a generated `SYNC_TOKEN`
- `SYNC_ENABLED=false` for safety until `SYNC_SERVER_URL` is filled in
