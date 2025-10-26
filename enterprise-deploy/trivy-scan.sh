#!/bin/bash
# Daily Trivy vulnerability scan
# Runs at 1 AM daily via cron
# Outputs HIGH and CRITICAL vulnerabilities to public/ops/vuln.json

set -e

# Configuration
SCAN_TARGET="/"
OUTPUT_FILE="/var/www/html/public/ops/vuln.json"
CONFIG_FILE="/var/www/html/enterprise-deploy/trivy.yaml"
LOG_FILE="/var/log/trivy-scan.log"

# Ensure output directory exists
mkdir -p "$(dirname "$OUTPUT_FILE")"

# Log scan start
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting Trivy vulnerability scan" >> "$LOG_FILE"

# Run Trivy scan
trivy rootfs "$SCAN_TARGET" \
    --config "$CONFIG_FILE" \
    --scanners vuln \
    --pkg-types os \
    --skip-dirs /home,/var,/tmp,/proc,/sys,/mnt,/dev,/run \
    --severity HIGH,CRITICAL \
    --format json \
    --output "$OUTPUT_FILE" 2>&1 | tee -a "$LOG_FILE"

# Set proper permissions for web access
chown www-data:www-data "$OUTPUT_FILE"
chmod 644 "$OUTPUT_FILE"

# Log completion
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Trivy scan completed. Results written to $OUTPUT_FILE" >> "$LOG_FILE"

# Optional: Print summary to stdout
VULN_COUNT=$(jq '.Results[]?.Vulnerabilities | length' "$OUTPUT_FILE" 2>/dev/null | awk '{s+=$1} END {print s}')
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Found $VULN_COUNT vulnerabilities (HIGH/CRITICAL)" >> "$LOG_FILE"
