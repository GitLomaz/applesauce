# Cloud Run Deployment Guide

This guide explains how to deploy your PHP application to Google Cloud Run.

## Prerequisites

1. **Google Cloud Account**: Ensure you have a Google Cloud Platform account
2. **gcloud CLI**: Install the [Google Cloud SDK](https://cloud.google.com/sdk/docs/install)
3. **Docker**: Install [Docker](https://docs.docker.com/get-docker/) for local testing
4. **Database**: Set up a Cloud SQL MySQL instance or use an external database

## Configuration Changes Made

### 1. Centralized Configuration (`config.php`)
- All database credentials now use environment variables
- Logging configured for Cloud Run (stdout/stderr)
- Support for both TCP and Unix socket connections (Cloud SQL)
- Connection pooling with static connection reuse

### 2. Updated Files
All PHP files have been updated to:
- Include `config.php` instead of hardcoded settings
- Use `app_log()` for Cloud Run-compatible logging
- Remove hardcoded database credentials
- Use environment variables for all configuration

### 3. Cloud Run Infrastructure
- **Dockerfile**: Optimized PHP 8.2 + Apache container
- **Health Check**: `/health.php` endpoint for monitoring
- **.dockerignore**: Excludes unnecessary files from container
- **.env.example**: Template for required environment variables

## Environment Variables

Configure these in Cloud Run:

### Required Variables
```bash
DB_HOST=your-database-host
DB_USER=your-database-user
DB_PASS=your-secure-password
DB_NAME=kalrul
```

### Optional Variables
```bash
DB_PORT=3306                      # Default: 3306
DB_UNIX_SOCKET=/cloudsql/...      # For Cloud SQL
SESSION_TIMEOUT_MINUTES=15        # Default: 15
APP_ENV=production                # Default: production
APP_DEBUG=false                   # Default: false
```

## Deployment Steps

### Step 1: Set Up Google Cloud Project

```bash
# Login to Google Cloud
gcloud auth login

# Set your project
gcloud config set project YOUR_PROJECT_ID

# Enable required APIs
gcloud services enable run.googleapis.com
gcloud services enable containerregistry.googleapis.com
gcloud services enable sqladmin.googleapis.com
```

### Step 2: Set Up Cloud SQL (If Using)

```bash
# Create Cloud SQL instance
gcloud sql instances create kalrul-db \
  --database-version=MYSQL_8_0 \
  --tier=db-f1-micro \
  --region=us-central1

# Set root password
gcloud sql users set-password root \
  --host=% \
  --instance=kalrul-db \
  --password=YOUR_SECURE_PASSWORD

# Create database
gcloud sql databases create kalrul --instance=kalrul-db
```

### Step 3: Build and Deploy

```bash
# Navigate to your project directory
cd /path/to/applesauce

# Build and deploy in one command
gcloud run deploy applesauce-api \
  --source . \
  --platform managed \
  --region us-central1 \
  --allow-unauthenticated \
  --set-env-vars "DB_HOST=YOUR_DB_HOST,DB_USER=YOUR_DB_USER,DB_NAME=kalrul" \
  --set-secrets "DB_PASS=db-password:latest" \
  --max-instances 10 \
  --memory 512Mi \
  --timeout 300
```

### Step 4: Configure Secrets (Recommended)

For sensitive data like database passwords, use Secret Manager:

```bash
# Create secret
echo -n "your-db-password" | gcloud secrets create db-password --data-file=-

# Grant Cloud Run access
gcloud secrets add-iam-policy-binding db-password \
  --member="serviceAccount:YOUR_PROJECT_NUMBER-compute@developer.gserviceaccount.com" \
  --role="roles/secretmanager.secretAccessor"

# Deploy with secret
gcloud run deploy applesauce-api \
  --source . \
  --set-secrets=DB_PASS=db-password:latest
```

### Step 5: Connect to Cloud SQL

If using Cloud SQL, add the connection:

```bash
gcloud run deploy applesauce-api \
  --source . \
  --add-cloudsql-instances YOUR_PROJECT_ID:us-central1:kalrul-db \
  --set-env-vars "DB_UNIX_SOCKET=/cloudsql/YOUR_PROJECT_ID:us-central1:kalrul-db"
```

## Alternative: Using Artifact Registry

For better control, push to Artifact Registry first:

```bash
# Create repository
gcloud artifacts repositories create applesauce-repo \
  --repository-format=docker \
  --location=us-central1

# Configure Docker
gcloud auth configure-docker us-central1-docker.pkg.dev

# Build image
docker build -t us-central1-docker.pkg.dev/YOUR_PROJECT_ID/applesauce-repo/api:latest .

# Push image
docker push us-central1-docker.pkg.dev/YOUR_PROJECT_ID/applesauce-repo/api:latest

# Deploy from Artifact Registry
gcloud run deploy applesauce-api \
  --image us-central1-docker.pkg.dev/YOUR_PROJECT_ID/applesauce-repo/api:latest \
  --platform managed \
  --region us-central1
```

## Local Testing

Test your Docker container locally before deploying:

```bash
# Build the image
docker build -t applesauce-api .

# Run locally with environment variables
docker run -p 8080:8080 \
  -e PORT=8080 \
  -e DB_HOST=your-db-host \
  -e DB_USER=your-user \
  -e DB_PASS=your-password \
  -e DB_NAME=kalrul \
  -e APP_DEBUG=true \
  applesauce-api

# Test health check
curl http://localhost:8080/health.php

# Test your API endpoints
curl http://localhost:8080/AJAX.php
```

## Monitoring and Logs

### View Logs
```bash
# Stream logs
gcloud run services logs tail applesauce-api --region us-central1

# View logs in Console
# Navigate to: Cloud Run > applesauce-api > Logs
```

### Check Health
```bash
# Get service URL
SERVICE_URL=$(gcloud run services describe applesauce-api --region us-central1 --format='value(status.url)')

# Check health
curl $SERVICE_URL/health.php
```

## Scaling Configuration

Cloud Run auto-scales, but you can control it:

```bash
gcloud run services update applesauce-api \
  --region us-central1 \
  --min-instances 0 \
  --max-instances 10 \
  --concurrency 80 \
  --cpu 1 \
  --memory 512Mi
```

## Security Best Practices

1. **Never commit credentials**: Use Secret Manager for passwords
2. **Restrict access**: Use IAM to control who can invoke your service
3. **Use HTTPS**: Cloud Run provides free SSL certificates
4. **Enable Cloud Armor**: For DDoS protection
5. **Set up VPC**: For private database connections

## Troubleshooting

### Container won't start
- Check logs: `gcloud run services logs tail applesauce-api`
- Verify environment variables are set
- Test health endpoint returns 200

### Database connection fails
- Verify Cloud SQL instance is running
- Check that service account has Cloud SQL Client role
- Verify connection string format
- Test with Cloud SQL Proxy locally

### Slow performance
- Increase memory/CPU allocation
- Enable request/response compression
- Use connection pooling (already configured in config.php)
- Check database query performance

### 503 Errors
- Check health endpoint status
- Verify max instances isn't reached
- Look for database connection issues
- Check timeout settings

## Updating the Service

```bash
# Deploy new version
gcloud run deploy applesauce-api --source .

# Rollback if needed
gcloud run services update-traffic applesauce-api \
  --to-revisions PREVIOUS_REVISION=100
```

## Cost Optimization

1. **Set min-instances to 0**: Only pay when handling requests
2. **Use appropriate tier**: Start with db-f1-micro for Cloud SQL
3. **Monitor usage**: Use Cloud Monitoring dashboards
4. **Set concurrency high**: 80-100 for PHP applications
5. **Use caching**: Implement Redis/Memcached for frequently accessed data

## Additional Resources

- [Cloud Run Documentation](https://cloud.google.com/run/docs)
- [Cloud SQL Documentation](https://cloud.google.com/sql/docs)
- [Secret Manager Documentation](https://cloud.google.com/secret-manager/docs)
- [Best Practices for PHP on Cloud Run](https://cloud.google.com/run/docs/tips/php)

## Support

For issues or questions:
1. Check Cloud Run logs
2. Verify environment variables
3. Test health endpoint
4. Review this documentation
