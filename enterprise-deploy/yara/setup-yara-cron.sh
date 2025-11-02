#!/bin/bash
# Setup YARA cron job for nightly malware scans

set -e

echo "Setting up YARA nightly scan cron job..."

# Create cron job for YARA scans at 11 PM daily
cat > /etc/cron.d/yara << 'EOF'
# YARA malware scan - runs daily at 11:00 PM
# Scans /var/www/html webroot with custom YARA rules

0 23 * * * root /usr/local/bin/yara-scan >> /var/log/yara/cron.log 2>&1
EOF

chmod 0644 /etc/cron.d/yara

echo "YARA cron job installed: runs daily at 11:00 PM"
echo "Schedule: 0 23 * * * (11:00 PM every night)"
echo "Target: /var/www/html (webroot only)"
echo "Log file: /var/log/yara/cron.log"
