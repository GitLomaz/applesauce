#!/bin/bash

# Cloud Run Deployment Helper Script
# This script helps with common deployment tasks

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PROJECT_ID="${GCP_PROJECT_ID:-}"
REGION="${GCP_REGION:-us-central1}"
SERVICE_NAME="applesauce-api"

# Functions
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

check_prerequisites() {
    print_info "Checking prerequisites..."
    
    if ! command -v gcloud &> /dev/null; then
        print_error "gcloud CLI not found. Please install: https://cloud.google.com/sdk/docs/install"
        exit 1
    fi
    
    if ! command -v docker &> /dev/null; then
        print_error "Docker not found. Please install: https://docs.docker.com/get-docker/"
        exit 1
    fi
    
    print_success "Prerequisites check passed"
}

setup_project() {
    if [ -z "$PROJECT_ID" ]; then
        print_info "No GCP_PROJECT_ID set. Please enter your project ID:"
        read -r PROJECT_ID
    fi
    
    gcloud config set project "$PROJECT_ID"
    print_success "Project set to: $PROJECT_ID"
    
    print_info "Enabling required APIs..."
    gcloud services enable run.googleapis.com
    gcloud services enable containerregistry.googleapis.com
    gcloud services enable sqladmin.googleapis.com
    print_success "APIs enabled"
}

build_local() {
    print_info "Building Docker image..."
    docker build -t "$SERVICE_NAME" .
    print_success "Docker image built successfully"
}

test_local() {
    print_info "Starting local server..."
    print_info "Make sure to set your environment variables in the docker run command"
    
    docker run -p 8080:8080 \
        -e PORT=8080 \
        -e DB_HOST="${DB_HOST:-localhost}" \
        -e DB_USER="${DB_USER:-root}" \
        -e DB_PASS="${DB_PASS:-}" \
        -e DB_NAME="${DB_NAME:-kalrul}" \
        -e APP_DEBUG=true \
        "$SERVICE_NAME"
}

test_health() {
    local url="${1:-http://localhost:8080}"
    print_info "Testing health endpoint..."
    
    if curl -f "$url/health.php" > /dev/null 2>&1; then
        print_success "Health check passed"
        curl -s "$url/health.php" | python3 -m json.tool
    else
        print_error "Health check failed"
        exit 1
    fi
}

deploy() {
    print_info "Deploying to Cloud Run..."
    
    if [ -z "$PROJECT_ID" ]; then
        print_error "PROJECT_ID not set. Run 'setup' first or set GCP_PROJECT_ID"
        exit 1
    fi
    
    print_info "This will deploy using environment variables from your shell"
    print_info "Make sure you have set: DB_HOST, DB_USER, DB_PASS, DB_NAME"
    
    gcloud run deploy "$SERVICE_NAME" \
        --source . \
        --platform managed \
        --region "$REGION" \
        --allow-unauthenticated \
        --set-env-vars "DB_HOST=${DB_HOST},DB_USER=${DB_USER},DB_NAME=${DB_NAME}" \
        --set-env-vars "DB_PASS=${DB_PASS}"
    
    SERVICE_URL=$(gcloud run services describe "$SERVICE_NAME" --region "$REGION" --format='value(status.url)')
    print_success "Deployed successfully to: $SERVICE_URL"
    
    print_info "Testing deployed service..."
    test_health "$SERVICE_URL"
}

deploy_with_secrets() {
    print_info "Deploying with Secret Manager..."
    
    local secret_name="db-password"
    
    # Check if secret exists
    if ! gcloud secrets describe "$secret_name" &> /dev/null; then
        print_info "Creating secret: $secret_name"
        echo -n "$DB_PASS" | gcloud secrets create "$secret_name" --data-file=-
    else
        print_info "Updating secret: $secret_name"
        echo -n "$DB_PASS" | gcloud secrets versions add "$secret_name" --data-file=-
    fi
    
    gcloud run deploy "$SERVICE_NAME" \
        --source . \
        --platform managed \
        --region "$REGION" \
        --allow-unauthenticated \
        --set-env-vars "DB_HOST=${DB_HOST},DB_USER=${DB_USER},DB_NAME=${DB_NAME}" \
        --set-secrets "DB_PASS=${secret_name}:latest"
    
    SERVICE_URL=$(gcloud run services describe "$SERVICE_NAME" --region "$REGION" --format='value(status.url)')
    print_success "Deployed successfully to: $SERVICE_URL"
}

view_logs() {
    print_info "Streaming logs from Cloud Run..."
    gcloud run services logs tail "$SERVICE_NAME" --region "$REGION"
}

get_service_url() {
    SERVICE_URL=$(gcloud run services describe "$SERVICE_NAME" --region "$REGION" --format='value(status.url)' 2>/dev/null)
    if [ -n "$SERVICE_URL" ]; then
        print_success "Service URL: $SERVICE_URL"
        echo "$SERVICE_URL"
    else
        print_error "Service not found. Have you deployed yet?"
        exit 1
    fi
}

# Main menu
show_help() {
    cat << EOF
Cloud Run Deployment Helper

Usage: $0 [command]

Commands:
    setup           - Set up GCP project and enable APIs
    build           - Build Docker image locally
    test            - Run container locally for testing
    health [url]    - Test health endpoint (default: http://localhost:8080)
    deploy          - Deploy to Cloud Run
    deploy-secret   - Deploy to Cloud Run using Secret Manager for passwords
    logs            - View Cloud Run logs
    url             - Get service URL
    help            - Show this help message

Environment Variables:
    GCP_PROJECT_ID  - Google Cloud Project ID
    GCP_REGION      - Deployment region (default: us-central1)
    DB_HOST         - Database host
    DB_USER         - Database user
    DB_PASS     - Database password
    DB_NAME         - Database name (default: kalrul)

Examples:
    # Initial setup
    $0 setup

    # Test locally
    export DB_HOST=your-db-host
    export DB_USER=your-user
    export DB_PASS=your-password
    $0 build
    $0 test

    # Deploy
    export GCP_PROJECT_ID=your-project-id
    $0 deploy

    # View logs
    $0 logs

EOF
}

# Process command
case "${1:-help}" in
    setup)
        check_prerequisites
        setup_project
        ;;
    build)
        build_local
        ;;
    test)
        build_local
        test_local
        ;;
    health)
        test_health "${2:-http://localhost:8080}"
        ;;
    deploy)
        check_prerequisites
        deploy
        ;;
    deploy-secret)
        check_prerequisites
        deploy_with_secrets
        ;;
    logs)
        view_logs
        ;;
    url)
        get_service_url
        ;;
    help|*)
        show_help
        ;;
esac
