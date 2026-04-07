# Applesauce API - Cloud Run

PHP API running on Google Cloud Run with automatic deployment from repository. Cloud SQL instance `static-lens-268201:us-central1:kalrul` is attached.

## Cloud Run Configuration

Set these environment variables in Cloud Run Console:

| Variable | Value | Description |
|----------|-------|-------------|
| `DB_UNIX_SOCKET` | `/cloudsql/static-lens-268201:us-central1:kalrul` | Cloud SQL connection (default) |
| `DB_USER` | `root` | Database username |
| `DB_PASS` | (use Secret Manager) | Database password |
| `DB_NAME` | `kalrul` | Database name |
| `APP_ENV` | `production` | Environment |

**Note:** Use Secret Manager for `DB_PASS` instead of plain text.

## Local Development

### Docker Compose (Recommended)
```bash
docker-compose up
```
- App: http://localhost:8080
- Health: http://localhost:8080/health.php
- phpMyAdmin: http://localhost:8081

### Docker
```bash
docker build -t applesauce-api .
docker run -p 8080:8080 \
  -e PORT=8080 \
  -e DB_HOST=your-db-host \
  -e DB_USER=root \
  -e DB_PASS=your-password \
  -e DB_NAME=kalrul \
  applesauce-api
```

## What Changed for Cloud Run

### Configuration
- **config.php** - Centralized configuration with environment variables
- Database credentials removed from source code
- Cloud SQL Unix socket support (uses `static-lens-268201:us-central1:kalrul` by default)
- Cloud Run logging (stdout/stderr)
- Connection pooling

### Updated PHP Files
All files now use `config.php`:
- library.php, class_lib.php, common_lib.php
- AJAX.php, kong_AJAX.php
- combat_lib.php, kong_library.php
- nightly.php

### Infrastructure
- **Dockerfile** - PHP 8.2 + Apache optimized for Cloud Run
- **health.php** - Health check endpoint for monitoring
- **docker-compose.yml** - Local development environment

## Deployment

Automatic deployment via Cloud Run repository integration.
- Push to repository → triggers automatic deployment
- Cloud SQL instance attached automatically

## Monitoring

**Cloud Run Console:** https://console.cloud.google.com/run

**Health Check:**
```bash
# Production
curl https://your-service-url.run.app/health.php

# Local
curl http://localhost:8080/health.php
```

**Logs:** View in Cloud Run console under the Logs tab.

## Database Connection

Default connection uses Cloud SQL Unix socket:
```
/cloudsql/static-lens-268201:us-central1:kalrul
```

Falls back to TCP (DB_HOST) for local development.

## Security

✅ No credentials in source code
✅ Environment variables for all configuration
✅ Secret Manager recommended for passwords
✅ Cloud Run logging (no sensitive data)
✅ Error display disabled in production
