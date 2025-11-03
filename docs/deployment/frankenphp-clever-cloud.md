# FrankenPHP Deployment on Clever Cloud

## Prerequisites
- Clever Cloud PHP application configured for the FrankenPHP runtime (`php_native_nginx` stack).
- Dedicated environment variables for all secrets (Mailjet, D7 Network, Neon Postgres, MongoDB, gRPC endpoint).
- Redis addon (or compatible queue backend) for Horizon/queue workers.

## Build & Deploy Hooks
```bash
# .clever.json example snippet
{
  "deploy": [
    "php artisan migrate --force",
    "php artisan horizon:assets --ansi || true"
  ],
  "postdeploy": [
    "php artisan config:cache",
    "php artisan route:cache"
  ]
}
```
- Provision a secondary worker instance running `php artisan horizon` (or `php artisan queue:work --queue=default,contact-status-webhooks,contact-status-updates`).
- Enable the `grpc` and `mongodb` extensions via `clever addon create add-on-php-ext --plan <plan> --link <app>` or the console UI.

## Environment Variables
| Key | Notes |
| --- | --- |
| `APP_ENV`, `APP_DEBUG`, `APP_URL` | Standard Laravel configuration. |
| `DB_CONNECTION=pgsql` | Use Neon Postgres; prefer setting `DATABASE_URL` or `NEON_DATABASE_URL`. |
| `GRPC_CONTACT_ENDPOINT` | Host:port of the gRPC service for contact status updates. Leave blank to disable. |
| `GRPC_CONTACT_TIMEOUT` | Float seconds (default `2.0`). |
| `MONGODB_URI`, `MONGODB_DATABASE`, `MONGODB_CONTACTS_COLLECTION` | Enables MongoDB lookups when gRPC is disabled. |
| `D7_API_KEY`, `D7_WEBHOOK_SECRET` | Placeholder for upcoming D7 integration. |
| `QUEUE_CONNECTION=redis` | Recommended for Horizon. |
| `HORIZON_PREFIX` | Optional prefix per Clever Cloud app. |

## Mailjet Webhooks
- Point Mailjet event webhooks to `https://<app-domain>/api/v1/webhooks/mailjet`.
- Contact status updates are queued via `DispatchContactStatusUpdate` and processed by the `contact-status-*` queues.

## Queue Topology
- `sendportal-webhook-process` (core SendPortal listener).
- `contact-status-webhooks` (new Mailjet ingest listener).
- `contact-status-updates` (gRPC / Mongo pipeline).
- Ensure the worker command processes all queues: `php artisan horizon` or `php artisan queue:work --queue=sendportal-webhook-process,contact-status-webhooks,contact-status-updates,default`.

## Future D7 Integration
- D7 webhook endpoint available at `/api/v1/webhooks/d7-network` (returns `202` until implemented).
- Reuse the contact status pipeline by dispatching `ProcessContactStatusUpdate` jobs once D7 payload mapping is finalised.
