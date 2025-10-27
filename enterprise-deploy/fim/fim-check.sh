#!/bin/bash
# Simple File Integrity Monitoring - Check
# Compares current file checksums against baseline

set -e

FIM_DIR="/var/lib/fim"
FIM_DB="$FIM_DIR/checksums.db"
FIM_LOG="/var/log/fim/fim.log"
TEMP_CHECK="/tmp/fim-check-$$.txt"
TEMP_CURRENT="/tmp/fim-current-$$.txt"

# Cleanup on exit
trap 'rm -f "$TEMP_CHECK" "$TEMP_CURRENT"' EXIT

# Check if database exists
if [ ! -f "$FIM_DB" ]; then
    echo "ERROR: FIM database not found. Run fim-init first."
    logger -t fim-check -p local6.err "FIM database not found"
    exit 1
fi

echo "=== FIM: File Integrity Check ==="
echo "Database: $FIM_DB"
echo "Checking $(grep -c '^[a-f0-9]' "$FIM_DB") files..."
echo ""

# Extract just the file paths from database
grep '^[a-f0-9]' "$FIM_DB" | awk '{print $2}' | sort > "$TEMP_CHECK"

# Track changes
ADDED=0
REMOVED=0
CHANGED=0
CRITICAL=0

ALERT_LEVEL="INFO"

# Check for removed files
while IFS= read -r filepath; do
    if [ ! -e "$filepath" ]; then
        echo "REMOVED: $filepath"
        logger -t fim-check -p local6.alert "REMOVED: $filepath"
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] REMOVED: $filepath" >> "$FIM_LOG"
        ((REMOVED++))
        ALERT_LEVEL="ALERT"
    fi
done < "$TEMP_CHECK"

# Check for changed files
while IFS= read -r line; do
    CHECKSUM=$(echo "$line" | awk '{print $1}')
    FILEPATH=$(echo "$line" | awk '{print $2}')

    if [ -f "$FILEPATH" ]; then
        CURRENT_CHECKSUM=$(sha256sum "$FILEPATH" 2>/dev/null | awk '{print $1}' || echo "ERROR")

        if [ "$CURRENT_CHECKSUM" != "$CHECKSUM" ] && [ "$CURRENT_CHECKSUM" != "ERROR" ]; then
            echo "CHANGED: $FILEPATH"
            logger -t fim-check -p local6.alert "CHANGED: $FILEPATH (checksum mismatch)"
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] CHANGED: $FILEPATH" >> "$FIM_LOG"
            ((CHANGED++))
            ALERT_LEVEL="ALERT"

            # Check if it's a critical file
            case "$FILEPATH" in
                /etc/passwd|/etc/shadow|/etc/sudoers|/etc/ssh/*|*/\.env|/etc/pam.d/*)
                    echo "  ⚠️  CRITICAL SECURITY FILE"
                    logger -t fim-check -p local6.crit "CRITICAL: Security-sensitive file changed: $FILEPATH"
                    ((CRITICAL++))
                    ALERT_LEVEL="CRITICAL"
                    ;;
            esac
        fi
    fi
done < <(grep '^[a-f0-9]' "$FIM_DB")

# Summary
echo ""
echo "=== FIM Check Summary ==="
echo "Changed: $CHANGED"
echo "Removed: $REMOVED"
echo "Critical: $CRITICAL"

if [ $CHANGED -eq 0 ] && [ $REMOVED -eq 0 ]; then
    echo "✓ No integrity violations detected"
    logger -t fim-check -p local6.info "FIM check passed - no changes detected"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] FIM check passed - no changes" >> "$FIM_LOG"
    exit 0
else
    echo "⚠️  File integrity violations detected!"
    logger -t fim-check -p local6.alert "FIM violations: $CHANGED changed, $REMOVED removed, $CRITICAL critical"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] FIM violations: $CHANGED changed, $REMOVED removed" >> "$FIM_LOG"

    if [ $CRITICAL -gt 0 ]; then
        logger -t fim-check -p local6.crit "CRITICAL: $CRITICAL security-sensitive files modified"
    fi

    echo ""
    echo "To update baseline after legitimate changes:"
    echo "  fim-init"
    exit 1
fi
