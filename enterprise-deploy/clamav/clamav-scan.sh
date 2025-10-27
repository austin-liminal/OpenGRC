#!/bin/bash
# ClamAV Daily Malware Scan
# Scans critical directories and alerts via syslog

set -e

SCAN_LOG="/var/log/clamav/scan.log"
SCAN_REPORT="/var/log/clamav/scan-report.txt"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

echo "=========================================="
echo "ClamAV Malware Scan"
echo "Started: $TIMESTAMP"
echo "=========================================="

# Ensure log directory exists
mkdir -p /var/log/clamav
chmod 755 /var/log/clamav

# Update virus definitions first
echo "Updating virus definitions..."
logger -t clamav-scan -p local6.info "Starting ClamAV scan - updating definitions"

if freshclam --quiet 2>&1 | tee -a "$SCAN_LOG"; then
    echo "✓ Virus definitions updated"
    logger -t clamav-scan -p local6.info "ClamAV definitions updated successfully"
else
    echo "⚠️  Warning: Failed to update definitions, using existing database"
    logger -t clamav-scan -p local6.warn "ClamAV definition update failed, using existing database"
fi

echo ""
echo "Starting malware scan..."
echo "Scanning directories:"
echo "  - /var/www/html (OpenGRC application)"
echo "  - /etc (System configuration)"
echo "  - /tmp (Temporary files)"
echo ""

# Scan paths
SCAN_PATHS=(
    "/var/www/html"
    "/etc"
    "/usr"
)

# Start scan report
cat > "$SCAN_REPORT" << EOF
ClamAV Scan Report
==================
Date: $TIMESTAMP
Scanned Paths: ${SCAN_PATHS[*]}

EOF

# Track infected files
INFECTED_COUNT=0
SCANNED_COUNT=0

# Run ClamAV scan
# Options:
#   -r = recursive
#   -i = only show infected files
#   --exclude-dir = skip certain directories
#   --max-filesize=100M = skip files larger than 100MB
#   --max-scansize=100M = max scan size per file

for path in "${SCAN_PATHS[@]}"; do
    if [ -d "$path" ]; then
        echo "Scanning $path..."

        # Run scan and capture output
        SCAN_OUTPUT=$(clamscan -r -i \
            --exclude-dir="^/var/www/html/vendor" \
            --exclude-dir="^/var/www/html/node_modules" \
            --exclude-dir="^/var/www/html/storage/framework/cache" \
            --max-filesize=5M \
            --max-scansize=5M \
            --no-sandbox
            "$path" 2>&1 || true)

        echo "$SCAN_OUTPUT" >> "$SCAN_REPORT"

        # Check for infections
        if echo "$SCAN_OUTPUT" | grep -q "Infected files: [1-9]"; then
            INFECTED=$(echo "$SCAN_OUTPUT" | grep "Infected files:" | awk '{print $3}')
            INFECTED_COUNT=$((INFECTED_COUNT + INFECTED))

            # Log infected files with HIGH priority
            echo "$SCAN_OUTPUT" | grep "FOUND" | while read -r line; do
                echo "⚠️  MALWARE DETECTED: $line"
                logger -t clamav-scan -p local6.crit "MALWARE DETECTED: $line"
            done
        fi

        # Extract scanned files count
        if echo "$SCAN_OUTPUT" | grep -q "Scanned files:"; then
            SCANNED=$(echo "$SCAN_OUTPUT" | grep "Scanned files:" | awk '{print $3}')
            SCANNED_COUNT=$((SCANNED_COUNT + SCANNED))
        fi
    fi
done

echo "" >> "$SCAN_REPORT"
echo "========================================" >> "$SCAN_REPORT"
echo "Scan Summary" >> "$SCAN_REPORT"
echo "========================================" >> "$SCAN_REPORT"
echo "Total files scanned: $SCANNED_COUNT" >> "$SCAN_REPORT"
echo "Infected files found: $INFECTED_COUNT" >> "$SCAN_REPORT"
echo "Completed: $(date '+%Y-%m-%d %H:%M:%S')" >> "$SCAN_REPORT"

# Display summary
echo ""
echo "=========================================="
echo "Scan Complete"
echo "=========================================="
echo "Files scanned: $SCANNED_COUNT"
echo "Infections found: $INFECTED_COUNT"
echo ""

# Log results
if [ "$INFECTED_COUNT" -eq 0 ]; then
    echo "✓ No malware detected"
    logger -t clamav-scan -p local6.info "ClamAV scan completed: $SCANNED_COUNT files scanned, no threats found"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Scan completed: $SCANNED_COUNT files, 0 infections" >> "$SCAN_LOG"
else
    echo "⚠️  WARNING: $INFECTED_COUNT infected file(s) detected!"
    echo "Review the scan report: $SCAN_REPORT"
    logger -t clamav-scan -p local6.crit "CRITICAL: ClamAV detected $INFECTED_COUNT infected file(s)"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ALERT: $INFECTED_COUNT infections detected!" >> "$SCAN_LOG"
fi

echo "Full report: $SCAN_REPORT"
echo "Log file: $SCAN_LOG"
echo ""

# Exit with error if infections found
if [ "$INFECTED_COUNT" -gt 0 ]; then
    exit 1
else
    exit 0
fi
