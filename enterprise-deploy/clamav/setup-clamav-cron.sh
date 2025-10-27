#!/bin/bash
# Setup ClamAV cron job for nightly malware scans

set -e

echo "Setting up ClamAV nightly scan cron job..."

# Create cron job for ClamAV scans at 11 PM daily
cat > /etc/cron.d/clamav << 'EOF'
# ClamAV malware scan - runs daily at 11:00 PM
# Updates virus definitions and scans critical directories

0 23 * * * root /usr/local/bin/clamav-scan >> /var/log/clamav/cron.log 2>&1
EOF

chmod 0644 /etc/cron.d/clamav

echo "ClamAV cron job installed: runs daily at 11:00 PM"
echo "Schedule: 0 23 * * * (11:00 PM every night)"
echo "Log file: /var/log/clamav/cron.log"
