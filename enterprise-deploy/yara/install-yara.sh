#!/bin/bash
# Install and configure YARA for malware detection

set -e

echo "=========================================="
echo "YARA Installation & Configuration"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "ERROR: This script must be run as root"
    exit 1
fi

# Install YARA
echo "Installing YARA..."
apt-get update -qq
apt-get install -y yara

# Verify installation
if ! command -v yara &> /dev/null; then
    echo "ERROR: YARA installation failed"
    exit 1
fi

YARA_VERSION=$(yara --version 2>&1 | head -n1 || echo "unknown")
echo "✓ YARA installed: $YARA_VERSION"
echo ""

# Create YARA directories
echo "Creating YARA directories..."
mkdir -p /etc/yara/rules
mkdir -p /var/log/yara
chmod 755 /etc/yara
chmod 755 /etc/yara/rules
chmod 755 /var/log/yara

echo "✓ Directories created:"
echo "  - /etc/yara/rules (YARA rules)"
echo "  - /var/log/yara (scan logs)"
echo ""

# Copy YARA rules
echo "Installing YARA rules..."
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [ -d "$SCRIPT_DIR/rules" ]; then
    cp -r "$SCRIPT_DIR/rules/"*.yar /etc/yara/rules/ 2>/dev/null || \
    cp -r "$SCRIPT_DIR/rules/"*.yara /etc/yara/rules/ 2>/dev/null || \
    echo "  No .yar or .yara files found"

    RULE_COUNT=$(find /etc/yara/rules -name "*.yar" -o -name "*.yara" | wc -l)
    echo "✓ Installed $RULE_COUNT YARA rule file(s)"
else
    echo "  Warning: Rules directory not found at $SCRIPT_DIR/rules"
    echo "  Creating empty rules directory - you'll need to add rules manually"
fi
echo ""

# Copy exceptions file
echo "Installing exceptions configuration..."
if [ -f "$SCRIPT_DIR/yara-exceptions.conf" ]; then
    cp "$SCRIPT_DIR/yara-exceptions.conf" /etc/yara/yara-exceptions.conf
    chmod 644 /etc/yara/yara-exceptions.conf
    echo "✓ Exceptions file installed: /etc/yara/yara-exceptions.conf"
else
    echo "  Warning: Exceptions file not found, creating empty file"
    touch /etc/yara/yara-exceptions.conf
    chmod 644 /etc/yara/yara-exceptions.conf
fi
echo ""

# Install scan script
echo "Installing YARA scan script..."
if [ -f "$SCRIPT_DIR/yara-scan.sh" ]; then
    cp "$SCRIPT_DIR/yara-scan.sh" /usr/local/bin/yara-scan
    chmod 755 /usr/local/bin/yara-scan
    echo "✓ Scan script installed: /usr/local/bin/yara-scan"
else
    echo "  ERROR: yara-scan.sh not found at $SCRIPT_DIR"
    exit 1
fi
echo ""

# Test YARA rules
echo "Testing YARA rules compilation..."
if [ "$(find /etc/yara/rules -name '*.yar' -o -name '*.yara' | wc -l)" -gt 0 ]; then
    if yara -w /etc/yara/rules /usr/local/bin/yara-scan > /dev/null 2>&1; then
        echo "✓ YARA rules compiled successfully"
    else
        echo "⚠️  Warning: Some YARA rules may have compilation errors"
        echo "  Run: yara /etc/yara/rules /path/to/test/file"
        echo "  to check for errors"
    fi
else
    echo "  No rules to test"
fi
echo ""

# Create initial log files
touch /var/log/yara/scan.log
touch /var/log/yara/scan-report.txt
chmod 644 /var/log/yara/scan.log
chmod 644 /var/log/yara/scan-report.txt

echo "=========================================="
echo "YARA Installation Complete"
echo "=========================================="
echo ""
echo "Next steps:"
echo "  1. Review YARA rules in /etc/yara/rules/"
echo "  2. Run a test scan: /usr/local/bin/yara-scan"
echo "  3. Set up cron job: ./setup-yara-cron.sh"
echo ""
echo "YARA rule files:"
find /etc/yara/rules -name "*.yar" -o -name "*.yara" 2>/dev/null | while read -r rule; do
    echo "  - $(basename "$rule")"
done
echo ""
