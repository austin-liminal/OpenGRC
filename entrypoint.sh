#!/bin/bash
set -e

echo "=== OpenGRC Container Starting ==="

# Flag file to track if this is the first run
FIRST_RUN_FLAG="/var/www/html/storage/.container_initialized"

#############################################
# EVERY RESTART: SSL Certificate Management
#############################################

echo "Checking SSL certificates..."
# Replace SSL certificates if provided via environment
if [ -n "$SSL_CERT" ] && [ -n "$SSL_KEY" ]; then
    echo "Installing custom SSL certificates..."
    echo "$SSL_CERT" > /etc/ssl/certs/opengrc.crt
    echo "$SSL_KEY" > /etc/ssl/private/opengrc.key
    chmod 644 /etc/ssl/certs/opengrc.crt
    chmod 600 /etc/ssl/private/opengrc.key
    echo "Custom SSL certificates installed."
else
    echo "Using default self-signed certificate."
fi

#############################################
# VALIDATE REQUIRED ENVIRONMENT VARIABLES
#############################################

echo "Validating required environment variables..."

REQUIRED_VARS=(
    "DB_CONNECTION"
    "DB_HOST"
    "DB_PORT"
    "DB_DATABASE"
    "DB_USERNAME"
    "DB_PASSWORD"
    "APP_KEY"
    "APP_NAME"
    "APP_URL"
    "ADMIN_EMAIL"
    "ADMIN_PASSWORD"
)

MISSING_VARS=()
for var in "${REQUIRED_VARS[@]}"; do
    if [ -z "${!var}" ]; then
        MISSING_VARS+=("$var")
    fi
done

if [ ${#MISSING_VARS[@]} -ne 0 ]; then
    echo "ERROR: Missing required environment variables:"
    for var in "${MISSING_VARS[@]}"; do
        echo "  - $var"
    done
    echo ""
    echo "Please set all required environment variables and restart the container."
    exit 1
fi

echo "All required environment variables are set."

#############################################
# DEPLOYMENT: Run opengrc:deploy command
#############################################

# Build the deploy command with all required parameters
DEPLOY_CMD="php artisan opengrc:deploy"
DEPLOY_CMD="$DEPLOY_CMD --db-driver=\"${DB_CONNECTION}\""
DEPLOY_CMD="$DEPLOY_CMD --db-host=\"${DB_HOST}\""
DEPLOY_CMD="$DEPLOY_CMD --db-port=\"${DB_PORT}\""
DEPLOY_CMD="$DEPLOY_CMD --db-name=\"${DB_DATABASE}\""
DEPLOY_CMD="$DEPLOY_CMD --db-user=\"${DB_USERNAME}\""
DEPLOY_CMD="$DEPLOY_CMD --db-password=\"${DB_PASSWORD}\""
DEPLOY_CMD="$DEPLOY_CMD --admin-email=\"${ADMIN_EMAIL}\""
DEPLOY_CMD="$DEPLOY_CMD --admin-password=\"${ADMIN_PASSWORD}\""
DEPLOY_CMD="$DEPLOY_CMD --site-name=\"${APP_NAME}\""
DEPLOY_CMD="$DEPLOY_CMD --app-key=\"${APP_KEY}\""
DEPLOY_CMD="$DEPLOY_CMD --site-url=\"${APP_URL}\""

# Add DigitalOcean Spaces configuration if provided
if [ -n "$DO_BUCKET" ] && [ -n "$DO_REGION" ] && [ -n "$DO_ACCESS_KEY_ID" ] && [ -n "$DO_SECRET_ACCESS_KEY" ]; then
    echo "DigitalOcean Spaces configuration detected."
    DEPLOY_CMD="$DEPLOY_CMD --digitalocean"
    DEPLOY_CMD="$DEPLOY_CMD --do-bucket=\"${DO_BUCKET}\""
    DEPLOY_CMD="$DEPLOY_CMD --do-region=\"${DO_REGION}\""
    DEPLOY_CMD="$DEPLOY_CMD --do-key=\"${DO_ACCESS_KEY_ID}\""
    DEPLOY_CMD="$DEPLOY_CMD --do-secret=\"${DO_SECRET_ACCESS_KEY}\""
fi

# Add SMTP configuration if provided
if [ -n "$SMTP_HOST" ] && [ -n "$SMTP_PORT" ] && [ -n "$SMTP_USER" ] && [ -n "$SMTP_PASSWORD" ]; then
    echo "SMTP configuration detected."
    DEPLOY_CMD="$DEPLOY_CMD --smtp"
    DEPLOY_CMD="$DEPLOY_CMD --smtp-host=\"${SMTP_HOST}\""
    DEPLOY_CMD="$DEPLOY_CMD --smtp-port=\"${SMTP_PORT}\""
    DEPLOY_CMD="$DEPLOY_CMD --smtp-username=\"${SMTP_USER}\""
    DEPLOY_CMD="$DEPLOY_CMD --smtp-password=\"${SMTP_PASSWORD}\""

    if [ -n "$SMTP_ENCRYPTION" ]; then
        DEPLOY_CMD="$DEPLOY_CMD --smtp-encryption=\"${SMTP_ENCRYPTION}\""
    fi

    if [ -n "$SMTP_FROM" ]; then
        DEPLOY_CMD="$DEPLOY_CMD --smtp-from=\"${SMTP_FROM}\""
    fi
fi

# Add storage lock flag if set
if [ "$STORAGE_LOCK" = "true" ]; then
    echo "Storage lock enabled."
    DEPLOY_CMD="$DEPLOY_CMD --lock"
fi

# Add accept flag to auto-accept deployment
DEPLOY_CMD="$DEPLOY_CMD --accept"

# Determine if this is first run or update
if [ ! -f "$FIRST_RUN_FLAG" ]; then
    echo "=== First Run Detected - Running Fresh Deployment ==="
    echo "Executing deployment command..."
else
    echo "=== Subsequent Run - Running Update Deployment ==="
    echo "Executing deployment command..."
fi

# Execute the deploy command
eval $DEPLOY_CMD

# Check if deployment was successful
if [ $? -eq 0 ]; then
    echo "Deployment completed successfully."

    # Create flag file if first run
    if [ ! -f "$FIRST_RUN_FLAG" ]; then
        touch "$FIRST_RUN_FLAG"
        echo "First run flag created."
    fi
else
    echo "ERROR: Deployment failed!"
    exit 1
fi

#############################################
# POST-DEPLOYMENT: Cache and Optimization
#############################################

echo "Running post-deployment optimizations..."

# Clear and rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Cache optimization complete."

# Link storage (if not already linked)
if [ ! -L "/var/www/html/public/storage" ]; then
    echo "Linking public storage..."
    php artisan storage:link
fi

#############################################
# START APPLICATION
#############################################

echo "=== Starting Apache on port 443 (HTTPS) ==="
echo "OpenGRC is ready!"
echo "Site URL: ${APP_URL}"
echo "Admin Email: ${ADMIN_EMAIL}"

# Start Apache in foreground
exec /usr/sbin/apache2ctl -D FOREGROUND
