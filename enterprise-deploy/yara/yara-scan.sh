#!/bin/bash
# YARA Daily Malware Scan
# Scans webroot directory and alerts via syslog

set -e

SCAN_LOG="/var/log/yara/scan.log"
SCAN_REPORT="/var/log/yara/scan-report.txt"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
WEBROOT="/var/www/html"
YARA_RULES="/etc/yara/rules"
EXCEPTIONS_FILE="/etc/yara/yara-exceptions.conf"

echo "=========================================="
echo "YARA Malware Scan"
echo "Started: $TIMESTAMP"
echo "=========================================="

# Ensure log directory exists
mkdir -p /var/log/yara
chmod 755 /var/log/yara

# Verify YARA is installed
if ! command -v yara &> /dev/null; then
    echo "ERROR: YARA is not installed"
    logger -t yara-scan -p local6.err "YARA scan failed: YARA not installed"
    exit 1
fi

# Verify rules directory exists
if [ ! -d "$YARA_RULES" ]; then
    echo "ERROR: YARA rules directory not found: $YARA_RULES"
    logger -t yara-scan -p local6.err "YARA scan failed: Rules directory not found"
    exit 1
fi

# Count available rules
RULE_COUNT=$(find "$YARA_RULES" -name "*.yar" -o -name "*.yara" | wc -l)
if [ "$RULE_COUNT" -eq 0 ]; then
    echo "WARNING: No YARA rules found in $YARA_RULES"
    logger -t yara-scan -p local6.warn "YARA scan warning: No rules found"
fi

echo ""
echo "Starting malware scan..."
echo "Scanning directory: $WEBROOT"
echo "YARA rules directory: $YARA_RULES"
echo "Active rules: $RULE_COUNT"
echo ""

logger -t yara-scan -p local6.info "Starting YARA scan on $WEBROOT with $RULE_COUNT rules"

# Function to check if a match should be excluded
is_exception() {
    local rule_name="$1"
    local file_path="$2"

    # If exceptions file doesn't exist, don't exclude anything
    [ ! -f "$EXCEPTIONS_FILE" ] && return 1

    # Read exceptions file and check for matches
    while IFS=: read -r pattern_rule pattern_path || [ -n "$pattern_rule" ]; do
        # Skip comments and empty lines
        [[ "$pattern_rule" =~ ^[[:space:]]*# ]] && continue
        [[ -z "$pattern_rule" ]] && continue

        # Trim whitespace
        pattern_rule=$(echo "$pattern_rule" | xargs)
        pattern_path=$(echo "$pattern_path" | xargs)

        # Check if rule matches (exact match or wildcard *)
        if [ "$pattern_rule" = "*" ] || [ "$pattern_rule" = "$rule_name" ]; then
            # Check if file path matches the regex pattern
            if echo "$file_path" | grep -qE "$pattern_path"; then
                return 0  # Is an exception, should be excluded
            fi
        fi
    done < "$EXCEPTIONS_FILE"

    return 1  # Not an exception, should be reported
}

# Start scan report
cat > "$SCAN_REPORT" << EOF
YARA Scan Report
==================
Date: $TIMESTAMP
Scanned Path: $WEBROOT
Rules Directory: $YARA_RULES
Active Rules: $RULE_COUNT

EOF

# Track matches
MATCH_COUNT=0
SCANNED_COUNT=0
SKIPPED_COUNT=0
FILTERED_COUNT=0

# Directories to exclude
EXCLUDE_DIRS=(
    "vendor"
    "node_modules"
    "storage/framework/cache"
    "storage/framework/sessions"
    "storage/framework/views"
    ".git"
)

# Build find command to exclude directories
FIND_EXCLUDES=""
for dir in "${EXCLUDE_DIRS[@]}"; do
    FIND_EXCLUDES="$FIND_EXCLUDES -path */$dir -prune -o"
done

# Create temporary file for matches
TEMP_MATCHES=$(mktemp)

# Scan files with YARA
echo "Scanning files..."
echo "" >> "$SCAN_REPORT"
echo "Scan Results:" >> "$SCAN_REPORT"
echo "-------------" >> "$SCAN_REPORT"

# Find all files and scan with YARA
# Exclude large files (>5MB) and binary files where appropriate
find "$WEBROOT" $FIND_EXCLUDES -type f -size -5M -print 2>/dev/null | while read -r file; do
    # Skip certain file types
    case "$file" in
        *.jpg|*.jpeg|*.png|*.gif|*.ico|*.svg|*.woff|*.woff2|*.ttf|*.eot)
            ((SKIPPED_COUNT++))
            continue
            ;;
    esac

    ((SCANNED_COUNT++))

    # Run YARA scan on the file
    # Options:
    #   -r = recursive (when scanning directories)
    #   -s = print matching strings
    #   -w = disable warnings
    #   -f = fast matching mode

    YARA_OUTPUT=$(yara -r -s -w "$YARA_RULES" "$file" 2>&1 || true)

    if [ -n "$YARA_OUTPUT" ]; then
        # Extract rule name from YARA output (first word on each line)
        RULE_NAME=$(echo "$YARA_OUTPUT" | head -n1 | awk '{print $1}')

        # Check if this match is in the exceptions list
        if is_exception "$RULE_NAME" "$file"; then
            # This is a known false positive, filter it out
            ((FILTERED_COUNT++))
        else
            # This is a real threat, report it
            echo "$YARA_OUTPUT" >> "$TEMP_MATCHES"
            echo "⚠️  THREAT DETECTED: $file"
            echo "$YARA_OUTPUT"
            logger -t yara-scan -p local6.crit "THREAT DETECTED: $file - $YARA_OUTPUT"
            ((MATCH_COUNT++))
        fi
    fi

    # Progress indicator every 100 files
    if [ $((SCANNED_COUNT % 100)) -eq 0 ]; then
        echo -n "."
    fi
done

echo ""
echo ""

# Add matches to report
if [ -s "$TEMP_MATCHES" ]; then
    cat "$TEMP_MATCHES" >> "$SCAN_REPORT"
    echo "" >> "$SCAN_REPORT"
else
    echo "No threats detected." >> "$SCAN_REPORT"
    echo "" >> "$SCAN_REPORT"
fi

# Cleanup temp file
rm -f "$TEMP_MATCHES"

# Summary
echo "========================================" >> "$SCAN_REPORT"
echo "Scan Summary" >> "$SCAN_REPORT"
echo "========================================" >> "$SCAN_REPORT"
echo "Total files scanned: $SCANNED_COUNT" >> "$SCAN_REPORT"
echo "Files skipped (images/fonts): $SKIPPED_COUNT" >> "$SCAN_REPORT"
echo "Matches filtered (false positives): $FILTERED_COUNT" >> "$SCAN_REPORT"
echo "Threats detected: $MATCH_COUNT" >> "$SCAN_REPORT"
echo "Completed: $(date '+%Y-%m-%d %H:%M:%S')" >> "$SCAN_REPORT"

# Display summary
echo ""
echo "=========================================="
echo "Scan Complete"
echo "=========================================="
echo "Files scanned: $SCANNED_COUNT"
echo "Files skipped: $SKIPPED_COUNT"
echo "Filtered matches: $FILTERED_COUNT"
echo "Threats detected: $MATCH_COUNT"
echo ""

# Log results
if [ "$MATCH_COUNT" -eq 0 ]; then
    echo "✓ No threats detected"
    logger -t yara-scan -p local6.info "YARA scan completed: $SCANNED_COUNT files scanned, no threats found"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Scan completed: $SCANNED_COUNT files, 0 threats" >> "$SCAN_LOG"
else
    echo "⚠️  WARNING: $MATCH_COUNT threat(s) detected!"
    echo "Review the scan report: $SCAN_REPORT"
    logger -t yara-scan -p local6.crit "CRITICAL: YARA detected $MATCH_COUNT threat(s)"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ALERT: $MATCH_COUNT threats detected!" >> "$SCAN_LOG"
fi

echo "Full report: $SCAN_REPORT"
echo "Log file: $SCAN_LOG"
echo ""

# Exit with error if threats found
if [ "$MATCH_COUNT" -gt 0 ]; then
    exit 1
else
    exit 0
fi
