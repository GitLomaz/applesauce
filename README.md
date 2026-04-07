# Applesauce API - Cloud Run Ready

This PHP application has been refactored and optimized for deployment on Google Cloud Run.

## What Changed

### ✅ Configuration Improvements
- **Centralized configuration** in `config.php` - all settings in one place
- **Environment variables** instead of hardcoded credentials
- **Cloud Run logging** - proper stdout/stderr logging for Cloud Run
- **Connection pooling** - database connections are reused efficiently
- **Security** - credentials removed from source code

### ✅ Cloud Infrastructure
- **Dockerfile** - optimized PHP 8.2 + Apache container
- **Health endpoint** - `/health.php` for monitoring
- **.dockerignore** - optimized container builds
- **Deployment guide** - complete instructions in `DEPLOYMENT.md`

### ✅ Code Updates
All PHP files updated to:
- Use `config.php` for configuration
- Remove hardcoded database credentials
- Use Cloud Run-compatible logging
- Support environment variables

## Quick Start

### Local Testing

1. Copy environment variables:
```bash
cp .env.example .env
# Edit .env with your database credentials
```

2. Build and run locally:
```bash
docker build -t applesauce-api .
docker run -p 8080:8080 \
  -e PORT=8080 \
  -e DB_HOST=your-host \
  -e DB_USER=your-user \
  -e DB_PASS=your-password \
  -e DB_NAME=kalrul \
  applesauce-api
```

3. Test health check:
```bash
curl http://localhost:8080/health.php
```

### Deploy to Cloud Run

```bash
# Login and set project
gcloud auth login
gcloud config set project YOUR_PROJECT_ID

# Deploy
gcloud run deploy applesauce-api \
  --source . \
  --platform managed \
  --region us-central1 \
  --allow-unauthenticated \
  --set-env-vars "DB_HOST=YOUR_HOST,DB_USER=YOUR_USER,DB_NAME=kalrul" \
  --set-secrets "DB_PASS=db-password:latest"
```

See `DEPLOYMENT.md` for complete deployment instructions.

## Required Environment Variables

| Variable | Description | Required | Default |
|----------|-------------|----------|---------|
| `DB_HOST` | Database hostname | Yes | - |
| `DB_USER` | Database username | Yes | - |
| `DB_PASS` | Database password | Yes | - |
| `DB_NAME` | Database name | Yes | kalrul |
| `DB_PORT` | Database port | No | 3306 |
| `DB_UNIX_SOCKET` | Unix socket for Cloud SQL | No | - |
| `APP_ENV` | Environment (production/development) | No | production |
| `APP_DEBUG` | Enable debug mode | No | false |
| `SESSION_TIMEOUT_MINUTES` | Session timeout | No | 15 |

## Files Added/Modified

### New Files
- `config.php` - Centralized configuration
- `health.php` - Health check endpoint
- `Dockerfile` - Container definition
- `.dockerignore` - Build optimization
- `.env.example` - Environment variable template
- `DEPLOYMENT.md` - Complete deployment guide
- `README.md` - This file

### Modified Files
- `library.php` - Uses config.php
- `class_lib.php` - Uses config.php
- `common_lib.php` - Uses config.php
- `AJAX.php` - Uses config.php
- `combat_lib.php` - Uses config.php
- `kong_library.php` - Uses config.php
- `kong_AJAX.php` - Uses config.php
- `nightly.php` - Uses config.php and improved logging

## Database Connection

The app now supports two connection methods:

### 1. Standard TCP Connection
Set these environment variables:
```
DB_HOST=your-db-host
DB_USER=your-user
DB_PASS=your-password
DB_NAME=kalrul
DB_PORT=3306
```

### 2. Cloud SQL Unix Socket (Recommended for Cloud Run)
Set these environment variables:
```
DB_UNIX_SOCKET=/cloudsql/PROJECT:REGION:INSTANCE
DB_USER=your-user
DB_PASS=your-password
DB_NAME=kalrul
```

## Security Notes

🔒 **Important Security Changes:**
1. All database credentials removed from source code
2. Passwords now use environment variables or Secret Manager
3. Logging configured for Cloud Run (no sensitive data in logs)
4. Error display disabled in production mode

## Monitoring

### View Logs
```bash
gcloud run services logs tail applesauce-api --region us-central1
```

### Check Health
```bash
curl https://your-service-url.run.app/health.php
```

### Monitor Performance
Navigate to Cloud Console > Cloud Run > applesauce-api > Metrics

## Next Steps

1. **Review Configuration**: Check `config.php` settings
2. **Test Locally**: Use Docker to test before deploying
3. **Set Up Database**: Create Cloud SQL instance or configure external DB
4. **Deploy**: Follow instructions in `DEPLOYMENT.md`
5. **Monitor**: Set up Cloud Run logging and monitoring
6. **Optimize**: Adjust memory, CPU, and concurrency settings

## Troubleshooting

### Container won't start
- Check Cloud Run logs
- Verify environment variables are set correctly
- Test health endpoint

### Database connection fails
- Verify credentials
- Check network connectivity
- Review CloudSQL configuration for Cloud Run

### 503 Errors
- Check health endpoint status
- Verify database is accessible
- Review Cloud Run logs for errors

## Additional Resources

- [Full Deployment Guide](DEPLOYMENT.md)
- [Cloud Run Documentation](https://cloud.google.com/run/docs)
- [Cloud SQL Best Practices](https://cloud.google.com/sql/docs/mysql/best-practices)

## Support

For deployment issues:
1. Check `DEPLOYMENT.md` for detailed instructions
2. Review Cloud Run logs
3. Verify all environment variables are set
4. Test health endpoint
