#!/bin/bash
# Simple File Integrity Monitoring - Initialization
# Creates baseline checksums for critical files

set -e

FIM_DIR="/var/lib/fim"
FIM_DB="$FIM_DIR/checksums.db"
FIM_LOG="/var/log/fim/fim.log"

echo "=== FIM: Creating file integrity baseline ==="

# Create directories
mkdir -p "$FIM_DIR" /var/log/fim
chmod 0700 "$FIM_DIR"

# Critical files to monitor
MONITOR_PATHS=(
    "/etc/passwd"
    "/etc/shadow"
    "/etc/group"
    "/etc/sudoers"
    "/etc/ssh/sshd_config"
    "/etc/pam.d"
    "/etc/cron.d"
    "/etc/crontab"
    "/etc/apache2/sites-available"
    "/etc/php"
    "/var/www/html/.env"
    "/var/www/html/app"
    "/var/www/html/config"
    "/var/www/html/routes"
    "/var/www/html/artisan"
    "/var/www/html/composer.json"
    "/var/www/html/composer.lock"
)

# Create new checksum database
echo "# FIM Baseline created: $(date)" > "$FIM_DB.new"
echo "# Format: SHA256 | PATH" >> "$FIM_DB.new"

TOTAL=0
for path in "${MONITOR_PATHS[@]}"; do
    if [ -e "$path" ]; then
        if [ -d "$path" ]; then
            # For directories, hash all files recursively
            find "$path" -type f 2>/dev/null | while read -r file; do
                sha256sum "$file" >> "$FIM_DB.new" 2>/dev/null || true
                ((TOTAL++)) || true
            done
        else
            # For files, hash directly
            sha256sum "$path" >> "$FIM_DB.new" 2>/dev/null || true
            ((TOTAL++)) || true
        fi
    fi
done

# Move new database to active
mv "$FIM_DB.new" "$FIM_DB"
chmod 0600 "$FIM_DB"

# Count entries
ENTRIES=$(grep -c '^[a-f0-9]' "$FIM_DB" || echo 0)

echo "FIM baseline created: $ENTRIES files monitored"
echo "Database: $FIM_DB"

# Log to syslog
logger -t fim-init -p local6.info "FIM baseline created with $ENTRIES files"

# Log to file
echo "[$(date '+%Y-%m-%d %H:%M:%S')] FIM baseline created: $ENTRIES files" >> "$FIM_LOG"
