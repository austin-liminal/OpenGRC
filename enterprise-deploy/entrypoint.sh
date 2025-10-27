#!/bin/bash
set -e

echo "=== OpenGRC Container Starting ==="
echo "DigitalOcean load balancer handles SSL termination"

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

echo "=== Starting Apache on port 80 (HTTP) ==="
echo "OpenGRC is ready!"
echo "Site URL: ${APP_URL}"
echo "Admin Email: ${ADMIN_EMAIL}"
echo "Note: DigitalOcean load balancer handles SSL/TLS termination"

# Start rsyslog for system logging
echo "Starting rsyslog..."
/usr/sbin/rsyslogd
sleep 1

# Verify rsyslog is running
if pgrep rsyslogd > /dev/null; then
    echo "rsyslog started successfully - system logs will be written to /var/log/syslog"
else
    echo "WARNING: rsyslog failed to start"
fi

#############################################
# AIDE: Initialize database and run check
#############################################

echo "=== AIDE File Integrity Monitoring Setup ==="

# Disable AIDE capabilities for container environment
export AIDE_NO_CAPSNG=1

# Check if AIDE database exists
if [ ! -f /var/lib/aide/aide.db ]; then
    echo "AIDE database not found. Initializing AIDE database..."
    echo "This will take a few minutes on first launch..."

    # Initialize AIDE database (with capabilities disabled for containers)
    if aideinit -y -f; then
        # Move the new database to the proper location
        if [ -f /var/lib/aide/aide.db.new ]; then
            mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
            echo "AIDE database initialized successfully at /var/lib/aide/aide.db"

            # Log to syslog
            logger -t aide-init -p local6.info "AIDE database initialized successfully on container startup"

            # Run initial check to establish baseline
            echo "Running initial AIDE integrity check..."
            if /usr/local/bin/aide-check; then
                echo "Initial AIDE check completed - baseline established"
            else
                echo "WARNING: Initial AIDE check reported changes (expected on first run)"
            fi
        else
            echo "ERROR: AIDE database initialization failed - aide.db.new not found"
            logger -t aide-init -p local6.err "AIDE database initialization failed"
        fi
    else
        echo "ERROR: aideinit command failed"
        logger -t aide-init -p local6.err "aideinit command failed"
    fi
else
    echo "AIDE database found at /var/lib/aide/aide.db"

    # Run integrity check on startup
    echo "Running AIDE integrity check on container startup..."
    if /usr/local/bin/aide-check; then
        echo "AIDE integrity check passed - no changes detected"
        logger -t aide-startup -p local6.info "AIDE startup check passed - no changes detected"
    else
        echo "WARNING: AIDE detected file changes since last database update"
        echo "Review /var/log/aide/aide-check.log for details"
        logger -t aide-startup -p local6.alert "AIDE startup check detected file changes"
    fi
fi

# Display database info
if [ -f /var/lib/aide/aide.db ]; then
    DB_SIZE=$(du -h /var/lib/aide/aide.db | cut -f1)
    DB_DATE=$(stat -c %y /var/lib/aide/aide.db | cut -d' ' -f1)
    echo "AIDE database size: $DB_SIZE (created: $DB_DATE)"
fi

echo "AIDE monitoring active - logs: /var/log/aide/"
echo ""

# Start Fluent Bit for log forwarding to OpenSearch
echo "Starting Fluent Bit for OpenSearch log forwarding..."
/opt/fluent-bit/bin/fluent-bit -c /etc/fluent-bit/fluent-bit.conf &
FLUENT_BIT_PID=$!
sleep 2

# Verify Fluent Bit is running
if kill -0 $FLUENT_BIT_PID 2>/dev/null; then
    echo "Fluent Bit started successfully (PID: $FLUENT_BIT_PID) - logs will be forwarded to OpenSearch"
else
    echo "WARNING: Fluent Bit failed to start - logs will not be forwarded"
fi

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
