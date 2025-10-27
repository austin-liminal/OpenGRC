#!/bin/bash
# AIDE Check Script with Syslog Alerting
# Performs integrity checks and sends detailed alerts to syslog

set -o pipefail

# Disable AIDE capabilities for container environment
export AIDE_NO_CAPSNG=1

# Configuration
AIDE_BIN="/usr/bin/aide"
AIDE_DB="/var/lib/aide/aide.db"
AIDE_LOG="/var/log/aide/aide.log"
TEMP_REPORT="/tmp/aide-report-$$.txt"
LOCK_FILE="/var/run/aide/aide-check.lock"
SYSLOG_PRIORITY="local6.alert"
SYSLOG_TAG="aide-check"

# Colors for output (disabled in cron)
if [ -t 1 ]; then
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    NC='\033[0m'
else
    RED=''
    GREEN=''
    YELLOW=''
    NC=''
fi

# Logging function
log() {
    local level="$1"
    shift
    local message="$*"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [$level] $message"
    logger -t "$SYSLOG_TAG" -p "$SYSLOG_PRIORITY" "$level: $message"
}

# Error handler
error_exit() {
    log "ERROR" "$1"
    cleanup
    exit 1
}

# Cleanup function
cleanup() {
    rm -f "$TEMP_REPORT" "$LOCK_FILE"
}

# Signal handlers
trap cleanup EXIT
trap 'error_exit "Script interrupted"' INT TERM

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    error_exit "This script must be run as root"
fi

# Check if AIDE is installed
if [ ! -x "$AIDE_BIN" ]; then
    error_exit "AIDE is not installed or not executable at $AIDE_BIN"
fi

# Check if database exists
if [ ! -f "$AIDE_DB" ]; then
    error_exit "AIDE database not found at $AIDE_DB. Run 'aideinit' first."
fi

# Create lock file to prevent concurrent runs
mkdir -p /var/run/aide
if [ -f "$LOCK_FILE" ]; then
    # Check if the process is still running
    if kill -0 $(cat "$LOCK_FILE" 2>/dev/null) 2>/dev/null; then
        log "WARN" "Another AIDE check is already running (PID: $(cat $LOCK_FILE))"
        exit 0
    else
        log "WARN" "Removing stale lock file"
        rm -f "$LOCK_FILE"
    fi
fi
echo $$ > "$LOCK_FILE"

# Start the check
log "INFO" "Starting AIDE integrity check"
echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}AIDE File Integrity Check${NC}"
echo -e "${GREEN}=========================================${NC}"
echo ""

# Run AIDE check and capture output
if ! $AIDE_BIN --check > "$TEMP_REPORT" 2>&1; then
    AIDE_EXIT_CODE=$?

    # Exit codes:
    # 0 = no changes
    # 1-6 = changes detected
    # 7+ = errors

    if [ $AIDE_EXIT_CODE -ge 7 ]; then
        log "ERROR" "AIDE check failed with exit code $AIDE_EXIT_CODE"
        cat "$TEMP_REPORT"
        error_exit "AIDE execution error"
    fi

    # Changes detected
    log "ALERT" "FILE INTEGRITY VIOLATIONS DETECTED"

    # Parse and categorize changes
    ADDED_FILES=$(grep -c "^added:" "$TEMP_REPORT" 2>/dev/null || echo 0)
    REMOVED_FILES=$(grep -c "^removed:" "$TEMP_REPORT" 2>/dev/null || echo 0)
    CHANGED_FILES=$(grep -c "^changed:" "$TEMP_REPORT" 2>/dev/null || echo 0)

    # Alert summary
    SUMMARY="AIDE Alert: $ADDED_FILES added, $REMOVED_FILES removed, $CHANGED_FILES changed"
    log "ALERT" "$SUMMARY"

    # Send detailed alerts to syslog for each change
    echo ""
    echo -e "${RED}=========================================${NC}"
    echo -e "${RED}CHANGES DETECTED${NC}"
    echo -e "${RED}=========================================${NC}"
    echo -e "${YELLOW}Added files: $ADDED_FILES${NC}"
    echo -e "${YELLOW}Removed files: $REMOVED_FILES${NC}"
    echo -e "${YELLOW}Changed files: $CHANGED_FILES${NC}"
    echo ""

    # Log added files
    if [ $ADDED_FILES -gt 0 ]; then
        log "ALERT" "=== Added Files ==="
        while IFS= read -r line; do
            filename=$(echo "$line" | sed 's/^added: //')
            log "ALERT" "ADDED: $filename"
            echo -e "${RED}+ $filename${NC}"
        done < <(grep "^added:" "$TEMP_REPORT")
    fi

    # Log removed files
    if [ $REMOVED_FILES -gt 0 ]; then
        log "ALERT" "=== Removed Files ==="
        while IFS= read -r line; do
            filename=$(echo "$line" | sed 's/^removed: //')
            log "ALERT" "REMOVED: $filename"
            echo -e "${RED}- $filename${NC}"
        done < <(grep "^removed:" "$TEMP_REPORT")
    fi

    # Log changed files with details
    if [ $CHANGED_FILES -gt 0 ]; then
        log "ALERT" "=== Changed Files ==="

        # Extract changed files and their modifications
        awk '
        /^changed:/ {
            file=$2
            getline
            changes=""
            while ($0 ~ /^ / && NF > 0) {
                changes = changes " " $0
                getline
            }
            print file ":::" changes
        }' "$TEMP_REPORT" | while IFS=':::' read -r file changes; do
            log "ALERT" "CHANGED: $file |$changes"
            echo -e "${YELLOW}~ $file${NC}"
            echo -e "  ${changes}"
        done
    fi

    # Save full report
    echo ""
    echo -e "${YELLOW}Full report saved to: $AIDE_LOG${NC}"
    cat "$TEMP_REPORT" >> "$AIDE_LOG"

    # Send critical alert for sensitive files
    CRITICAL_PATTERNS=(
        "/etc/passwd"
        "/etc/shadow"
        "/etc/sudoers"
        "/etc/ssh/sshd_config"
        "/var/www/html/.env"
        "/var/www/html/app/Providers"
        "/var/www/html/app/Http/Middleware"
        "/etc/pam.d"
        "/etc/cron"
    )

    for pattern in "${CRITICAL_PATTERNS[@]}"; do
        if grep -q "$pattern" "$TEMP_REPORT"; then
            log "CRITICAL" "SECURITY ALERT: Critical file modified: $pattern"
            echo -e "${RED}!!! CRITICAL: Security-sensitive file changed: $pattern${NC}"
        fi
    done

    # Suggest database update
    echo ""
    echo -e "${YELLOW}=========================================${NC}"
    echo -e "${YELLOW}Next Steps:${NC}"
    echo -e "${YELLOW}=========================================${NC}"
    echo "1. Review all changes above"
    echo "2. Investigate any unauthorized modifications"
    echo "3. If changes are legitimate, update the AIDE database:"
    echo "   sudo aide --update && sudo mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db"
    echo ""

else
    # No changes detected
    log "INFO" "No file integrity violations detected"
    echo -e "${GREEN}✓ No changes detected - System integrity verified${NC}"
    echo ""
fi

# Database age check
DB_AGE_DAYS=$(( ($(date +%s) - $(stat -c %Y "$AIDE_DB")) / 86400 ))
if [ $DB_AGE_DAYS -gt 30 ]; then
    log "WARN" "AIDE database is $DB_AGE_DAYS days old - consider updating if system has been patched"
    echo -e "${YELLOW}⚠ Warning: AIDE database is $DB_AGE_DAYS days old${NC}"
fi

# Log completion
log "INFO" "AIDE integrity check completed"

echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}Check completed at $(date)${NC}"
echo -e "${GREEN}=========================================${NC}"

exit 0
