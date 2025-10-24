#!/bin/bash
set -e

echo "=== OpenGRC Container Starting ==="

#############################################
# EVERY RESTART: SSL Certificate Management
#############################################

echo "Checking SSL certificates..."

# Ensure SSL directories exist
mkdir -p /etc/ssl/certs /etc/ssl/private

# Check if SSL certificates exist, if not create them
if [ ! -f "/etc/ssl/private/opengrc.key" ] || [ ! -f "/etc/ssl/certs/opengrc.crt" ]; then
    echo "SSL certificates not found, generating self-signed certificate..."
    openssl req -x509 -nodes -days 365 -newkey rsa:4096 \
        -keyout /etc/ssl/private/opengrc.key \
        -out /etc/ssl/certs/opengrc.crt \
        -subj "/C=US/ST=State/L=City/O=OpenGRC/CN=localhost"
    chmod 644 /etc/ssl/certs/opengrc.crt
    chmod 600 /etc/ssl/private/opengrc.key
    echo "Self-signed SSL certificate generated."
fi

# Replace SSL certificates if provided via environment
if [ -n "$SSL_CERT" ] && [ -n "$SSL_KEY" ]; then
    echo "Installing custom SSL certificates from environment..."
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

# Execute the deploy command
echo "=== Running OpenGRC Deployment ==="
echo "Executing deployment command..."
eval $DEPLOY_CMD

# Check if deployment was successful
if [ $? -eq 0 ]; then
    echo "Deployment completed successfully."
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

# Start PHP-FPM
echo "Starting PHP-FPM..."
mkdir -p /var/run/php
/usr/sbin/php-fpm8.3 --daemonize --fpm-config /etc/php/8.3/fpm/php-fpm.conf

# Wait for PHP-FPM socket to be ready
echo "Waiting for PHP-FPM socket..."
for i in {1..30}; do
    if [ -S /var/run/php/php8.3-fpm.sock ]; then
        echo "PHP-FPM socket is ready"
        break
    fi
    if [ $i -eq 30 ]; then
        echo "ERROR: PHP-FPM socket not available after 30 seconds"
        echo "Checking PHP-FPM status..."
        ps aux | grep php-fpm || true
        echo "Checking socket directory..."
        ls -la /var/run/php/ || true
        echo "Checking PHP-FPM logs..."
        tail -20 /var/log/php8.3-fpm.log || true
        exit 1
    fi
    sleep 1
done

# Test Apache configuration
echo "Testing Apache configuration..."
/usr/sbin/apache2ctl configtest

# Enable error logging
echo "Apache error log will be available at /var/log/apache2/error.log"
echo "PHP-FPM error log will be available at /var/log/php8.3-fpm.log"

# Start Apache in foreground
exec /usr/sbin/apache2ctl -D FOREGROUND
