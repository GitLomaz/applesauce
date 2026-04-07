# Cloud SQL Configuration

Your API is configured to use Cloud SQL instance: `static-lens-268201:us-central1:kalrul`

## Cloud Run Environment Variables

Set in Cloud Run console:

```
DB_UNIX_SOCKET=/cloudsql/static-lens-268201:us-central1:kalrul
DB_USER=root
DB_PASS=[use Secret Manager]
DB_NAME=kalrul
APP_ENV=production
```

## How It Works

1. **config.php** defaults to Cloud SQL Unix socket: `/cloudsql/static-lens-268201:us-central1:kalrul`
2. For Cloud Run, this socket is automatically available when the Cloud SQL instance is attached
3. For local development, it falls back to using `DB_HOST` (TCP connection)

## Local Development

Use docker-compose.yml which provides a local MySQL instance:
```bash
docker-compose up
```

This sets `DB_HOST=db` to connect to the local MySQL container instead of Cloud SQL.

## Connection Logic

```php
// From config.php
if (DB_UNIX_SOCKET && file_exists(dirname(DB_UNIX_SOCKET))) {
    // Use Cloud SQL Unix socket (Cloud Run)
    $conn = new mysqli(null, DB_USER, DB_PASS, DB_NAME, null, DB_UNIX_SOCKET);
} else {
    // Use TCP connection (local development)
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
}
```

## Deployment

Automatic deployment is configured via Cloud Run repository integration.
- Push to repository triggers deployment
- Cloud SQL instance `static-lens-268201:us-central1:kalrul` is attached
- No manual deployment commands needed
