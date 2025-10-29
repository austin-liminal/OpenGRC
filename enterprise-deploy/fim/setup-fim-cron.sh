#!/bin/bash
# Setup FIM cron job for hourly integrity checks

set -e

echo "Setting up FIM hourly cron job..."

# Create cron job for FIM checks
cat > /etc/cron.d/fim << 'EOF'
# FIM (File Integrity Monitoring) hourly checks
# Runs every hour to detect file changes

0 * * * * root /usr/local/bin/fim-check >> /var/log/fim/fim-check.log 2>&1
EOF

chmod 0644 /etc/cron.d/fim

echo "FIM cron job installed: runs every hour"
echo "Schedule: 0 * * * * (at the top of every hour)"
echo "Log file: /var/log/fim/fim-check.log"
