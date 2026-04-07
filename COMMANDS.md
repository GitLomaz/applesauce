# Quick Command Reference

## 🚀 Deployment

### First Time Setup
```bash
# Set your project
export GCP_PROJECT_ID="your-project-id"
export GCP_REGION="us-central1"

# Login and configure
gcloud auth login
gcloud config set project $GCP_PROJECT_ID

# Enable APIs
gcloud services enable run.googleapis.com containerregistry.googleapis.com sqladmin.googleapis.com
```

### Deploy to Cloud Run
```bash
# With environment variables (quick deploy)
gcloud run deploy applesauce-api \
  --source . \
  --region us-central1 \
  --allow-unauthenticated \
  --set-env-vars="DB_HOST=YOUR_HOST,DB_USER=YOUR_USER,DB_PASS=YOUR_PASS,DB_NAME=kalrul"

# With Secret Manager (recommended)
echo -n "your-password" | gcloud secrets create db-password --data-file=-
gcloud run deploy applesauce-api \
  --source . \
  --region us-central1 \
  --allow-unauthenticated \
  --set-env-vars="DB_HOST=YOUR_HOST,DB_USER=YOUR_USER,DB_NAME=kalrul" \
  --set-secrets="DB_PASS=db-password:latest"

# With Cloud SQL
gcloud run deploy applesauce-api \
  --source . \
  --region us-central1 \
  --add-cloudsql-instances=PROJECT:REGION:INSTANCE \
  --set-env-vars="DB_UNIX_SOCKET=/cloudsql/PROJECT:REGION:INSTANCE,DB_USER=root,DB_NAME=kalrul" \
  --set-secrets="DB_PASS=db-password:latest"
```

### Using Helper Script
```bash
# Make script executable
chmod +x deploy.sh

# Setup project
./deploy.sh setup

# Deploy
export DB_HOST="your-host"
export DB_USER="your-user"
export DB_PASS="your-password"
./deploy.sh deploy-secret

# View logs
./deploy.sh logs

# Get service URL
./deploy.sh url
```

## 🧪 Local Testing

### Using Docker
```bash
# Build image
docker build -t applesauce-api .

# Run with environment variables
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
```

### Using Docker Compose
```bash
# Start all services (app + database + phpmyadmin)
docker-compose up

# Start in background
docker-compose up -d

# View logs
docker-compose logs -f

# Stop services
docker-compose down

# URLs:
# - App: http://localhost:8080
# - Health: http://localhost:8080/health.php
# - phpMyAdmin: http://localhost:8081
```

## 📊 Monitoring

### View Logs
```bash
# Stream logs
gcloud run services logs tail applesauce-api --region us-central1

# Read recent logs
gcloud run services logs read applesauce-api --region us-central1 --limit 50
```

### Check Service Status
```bash
# Get service details
gcloud run services describe applesauce-api --region us-central1

# Get service URL
gcloud run services describe applesauce-api --region us-central1 --format='value(status.url)'

# List all revisions
gcloud run revisions list --service applesauce-api --region us-central1
```

### Health Check
```bash
# Local
curl http://localhost:8080/health.php | python3 -m json.tool

# Production
SERVICE_URL=$(gcloud run services describe applesauce-api --region us-central1 --format='value(status.url)')
curl $SERVICE_URL/health.php | python3 -m json.tool
```

## 🔧 Configuration

### Update Environment Variables
```bash
gcloud run services update applesauce-api \
  --region us-central1 \
  --set-env-vars="DB_HOST=new-host,APP_DEBUG=false"

# Update single variable
gcloud run services update applesauce-api \
  --region us-central1 \
  --update-env-vars="APP_DEBUG=true"

# Remove variable
gcloud run services update applesauce-api \
  --region us-central1 \
  --remove-env-vars="APP_DEBUG"
```

### Update Secrets
```bash
# Create new secret version
echo -n "new-password" | gcloud secrets versions add db-password --data-file=-

# Update service to use latest
gcloud run services update applesauce-api \
  --region us-central1 \
  --update-secrets="DB_PASS=db-password:latest"
```

### Scaling Configuration
```bash
gcloud run services update applesauce-api \
  --region us-central1 \
  --min-instances=0 \
  --max-instances=10 \
  --concurrency=80 \
  --cpu=1 \
  --memory=512Mi \
  --timeout=300
```

## 🗄️ Cloud SQL Management

### Create Instance
```bash
gcloud sql instances create kalrul-db \
  --database-version=MYSQL_8_0 \
  --tier=db-f1-micro \
  --region=us-central1

# Set password
gcloud sql users set-password root \
  --host=% \
  --instance=kalrul-db \
  --password=YOUR_PASSWORD

# Create database
gcloud sql databases create kalrul --instance=kalrul-db
```

### Connect to Cloud SQL
```bash
# Using Cloud SQL Proxy (for local development)
cloud-sql-proxy PROJECT:REGION:INSTANCE

# Direct connection
gcloud sql connect kalrul-db --user=root
```

## 🔄 Updates & Rollbacks

### Deploy New Version
```bash
gcloud run deploy applesauce-api --source .
```

### Rollback
```bash
# List revisions
gcloud run revisions list --service applesauce-api --region us-central1

# Rollback to previous revision
gcloud run services update-traffic applesauce-api \
  --region us-central1 \
  --to-revisions=REVISION_NAME=100
```

### Gradual Rollout
```bash
# Split traffic 50/50
gcloud run services update-traffic applesauce-api \
  --region us-central1 \
  --to-revisions=REVISION_NEW=50,REVISION_OLD=50

# Gradually increase to 100%
gcloud run services update-traffic applesauce-api \
  --region us-central1 \
  --to-revisions=REVISION_NEW=100
```

## 🔐 Security

### Make Service Private
```bash
gcloud run services update applesauce-api \
  --region us-central1 \
  --no-allow-unauthenticated
```

### Grant Access to Specific Users
```bash
gcloud run services add-iam-policy-binding applesauce-api \
  --region us-central1 \
  --member="user:email@example.com" \
  --role="roles/run.invoker"
```

### Service Account Permissions
```bash
# Get service account
SA=$(gcloud run services describe applesauce-api --region us-central1 --format='value(spec.template.spec.serviceAccount)')

# Grant Cloud SQL access
gcloud projects add-iam-policy-binding PROJECT_ID \
  --member="serviceAccount:$SA" \
  --role="roles/cloudsql.client"
```

## 🧹 Cleanup

### Delete Service
```bash
gcloud run services delete applesauce-api --region us-central1
```

### Delete Cloud SQL Instance
```bash
gcloud sql instances delete kalrul-db
```

### Delete Secrets
```bash
gcloud secrets delete db-password
```

### Delete Container Images
```bash
# List images
gcloud container images list --repository=gcr.io/PROJECT_ID

# Delete image
gcloud container images delete gcr.io/PROJECT_ID/applesauce-api:TAG
```

## 💰 Cost Management

### Check Current Costs
```bash
# Via Cloud Console
# Navigate to: Billing > Reports

# Set budget alerts
gcloud billing budgets create \
  --billing-account=BILLING_ACCOUNT_ID \
  --display-name="Cloud Run Budget" \
  --budget-amount=50USD
```

### Optimize Costs
```bash
# Reduce to minimal resources
gcloud run services update applesauce-api \
  --region us-central1 \
  --min-instances=0 \
  --max-instances=1 \
  --cpu=1 \
  --memory=256Mi

# Set execution timeout
gcloud run services update applesauce-api \
  --region us-central1 \
  --timeout=60
```

## 📚 Useful Links

- Service Dashboard: `https://console.cloud.google.com/run/detail/REGION/applesauce-api`
- Logs: `https://console.cloud.google.com/logs`
- Cloud SQL: `https://console.cloud.google.com/sql`
- Secret Manager: `https://console.cloud.google.com/security/secret-manager`

## 🆘 Troubleshooting

### Service Won't Start
```bash
# Check logs
gcloud run services logs tail applesauce-api --region us-central1

# Check latest deployment
gcloud run revisions describe REVISION_NAME --region us-central1
```

### Database Connection Issues
```bash
# Test from local container
docker run -it --rm applesauce-api php -r "
  \$conn = new mysqli('HOST', 'USER', 'PASS', 'DB');
  echo \$conn->ping() ? 'Connected!' : 'Failed!';
"

# Check Cloud SQL connectivity
gcloud sql operations list --instance=kalrul-db
```

### Performance Issues
```bash
# Check metrics
gcloud run services describe applesauce-api --region us-central1

# View in console with graphs
# Navigate to: Cloud Run > applesauce-api > Metrics
```

---

**Tip**: Save commonly used commands as shell aliases or scripts!
