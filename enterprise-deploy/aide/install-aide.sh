#!/bin/bash
# AIDE Installation and Configuration Script for OpenGRC
# Advanced Intrusion Detection Environment with syslog alerting

set -e

echo "=========================================="
echo "AIDE Installation for OpenGRC"
echo "=========================================="

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "ERROR: This script must be run as root"
    exit 1
fi

# Install AIDE
echo "Installing AIDE..."
apt-get update
apt-get install -y aide aide-common

# Backup original config if exists
if [ -f /etc/aide/aide.conf ]; then
    echo "Backing up existing AIDE configuration..."
    cp /etc/aide/aide.conf /etc/aide/aide.conf.bak.$(date +%Y%m%d-%H%M%S)
fi

# Copy custom AIDE configuration
echo "Installing OpenGRC AIDE configuration..."
cp /var/www/html/enterprise-deploy/aide/aide.conf /etc/aide/aide.conf

# Create directories for AIDE
echo "Creating AIDE directories..."
mkdir -p /var/lib/aide
mkdir -p /var/log/aide
mkdir -p /var/run/aide

# Set proper permissions
chmod 0600 /etc/aide/aide.conf
chmod 0700 /var/lib/aide
chmod 0755 /var/log/aide

# Initialize AIDE database
echo "Initializing AIDE database (this may take several minutes)..."
aideinit -y -f

# Move the new database to the proper location
if [ -f /var/lib/aide/aide.db.new ]; then
    mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
    echo "AIDE database initialized successfully"
else
    echo "ERROR: AIDE database initialization failed"
    exit 1
fi

# Install the check script
echo "Installing AIDE check script..."
cp /var/www/html/enterprise-deploy/aide/aide-check.sh /usr/local/bin/aide-check
chmod 0755 /usr/local/bin/aide-check

# Create logrotate configuration for AIDE logs
echo "Configuring log rotation for AIDE..."
cat > /etc/logrotate.d/aide << 'EOF'
/var/log/aide/aide.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 0640 root adm
    sharedscripts
}

/var/log/aide/aide-check.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 0640 root adm
    sharedscripts
}
EOF

# Add cron job for daily checks
echo "Setting up daily AIDE checks..."
cat > /etc/cron.d/aide << 'EOF'
# AIDE file integrity checks
# Runs daily at 3:15 AM
15 3 * * * root /usr/local/bin/aide-check >> /var/log/aide/aide-check.log 2>&1
EOF

chmod 0644 /etc/cron.d/aide

# Configure syslog to capture AIDE alerts
echo "Configuring rsyslog for AIDE alerts..."
cat > /etc/rsyslog.d/30-aide.conf << 'EOF'
# AIDE alerts with high priority
:programname, isequal, "aide" /var/log/aide/aide.log
:programname, isequal, "aide-check" /var/log/aide/aide-check.log

# Stop processing if it's an AIDE message to prevent duplicates
:programname, isequal, "aide" stop
:programname, isequal, "aide-check" stop
EOF

# Restart rsyslog to apply configuration
systemctl restart rsyslog

echo ""
echo "=========================================="
echo "AIDE Installation Complete!"
echo "=========================================="
echo ""
echo "Configuration file: /etc/aide/aide.conf"
echo "Database location: /var/lib/aide/aide.db"
echo "Log files: /var/log/aide/"
echo "Check script: /usr/local/bin/aide-check"
echo ""
echo "Daily checks scheduled at 3:15 AM"
echo "Run manually: /usr/local/bin/aide-check"
echo ""
echo "To update the AIDE database after legitimate changes:"
echo "  aide --update && mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db"
echo ""
