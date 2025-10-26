#!/bin/bash
set -e

echo "=== Installing and Configuring Fluent Bit to Read Wazuh Alerts ==="

# Check if Fluent Bit is installed on the host
if ! command -v fluent-bit &> /dev/null && ! command -v td-agent-bit &> /dev/null; then
    echo "Fluent Bit not found. Installing Fluent Bit on host VM..."
    curl https://raw.githubusercontent.com/fluent/fluent-bit/master/install.sh | sh
    echo "Fluent Bit installed successfully"
else
    echo "Fluent Bit is already installed"
fi

# Prompt for OpenSearch details (if not already set)
if [ -z "$OPENSEARCH_HOST" ]; then
    read -p "Enter OpenSearch Host (e.g., opensearch.example.com): " OPENSEARCH_HOST
fi
if [ -z "$OPENSEARCH_PORT" ]; then
    read -p "Enter OpenSearch Port (default 9200): " OPENSEARCH_PORT
    OPENSEARCH_PORT=${OPENSEARCH_PORT:-9200}
fi
if [ -z "$OPENSEARCH_USER" ]; then
    read -p "Enter OpenSearch Username: " OPENSEARCH_USER
fi
if [ -z "$OPENSEARCH_PASSWORD" ]; then
    read -sp "Enter OpenSearch Password: " OPENSEARCH_PASSWORD
    echo
fi

# Backup existing Fluent Bit config if it exists
if [ -f /etc/fluent-bit/fluent-bit.conf ]; then
    cp /etc/fluent-bit/fluent-bit.conf /etc/fluent-bit/fluent-bit.conf.backup
    echo "Backed up existing config to /etc/fluent-bit/fluent-bit.conf.backup"
fi

# Add Wazuh alerts input to Fluent Bit config
cat >> /etc/fluent-bit/fluent-bit.conf <<EOF

# Wazuh Alerts Input
[INPUT]
    Name              tail
    Path              /var/ossec/logs/alerts/alerts.json
    Tag               wazuh-alerts
    Parser            json
    Refresh_Interval  5
    Read_from_Head    true
    Skip_Empty_Lines  On

# Wazuh Archives Input (optional - all events, not just alerts)
[INPUT]
    Name              tail
    Path              /var/ossec/logs/archives/archives.json
    Tag               wazuh-archives
    Parser            json
    Refresh_Interval  5
    Read_from_Head    false
    Skip_Empty_Lines  On

# Add Wazuh-specific fields
[FILTER]
    Name              modify
    Match             wazuh-*
    Add               data_source wazuh
    Add               observer.type wazuh
    Add               observer.vendor wazuh

# Output to OpenSearch for Wazuh data
[OUTPUT]
    Name              opensearch
    Match             wazuh-*
    Host              ${OPENSEARCH_HOST}
    Port              ${OPENSEARCH_PORT}
    HTTP_User         ${OPENSEARCH_USER}
    HTTP_Passwd       ${OPENSEARCH_PASSWORD}
    Index             wazuh-alerts
    Type              _doc
    tls               On
    tls.verify        Off
    Suppress_Type_Name On
    Logstash_Format   On
    Logstash_Prefix   wazuh-alerts
    Logstash_DateFormat %Y.%m.%d
EOF

# Enable JSON output in Wazuh Manager
echo "Enabling JSON output in Wazuh Manager..."
if grep -q "<jsonout_output>" /var/ossec/etc/ossec.conf; then
    echo "JSON output already enabled in Wazuh config"
else
    # Add JSON output configuration
    sed -i '/<global>/a\    <jsonout_output>yes</jsonout_output>' /var/ossec/etc/ossec.conf
    echo "Enabled JSON output in Wazuh config"
fi

# Set permissions for Fluent Bit to read Wazuh logs
echo "Setting permissions for Fluent Bit to read Wazuh logs..."
usermod -a -G ossec fluent-bit 2>/dev/null || usermod -a -G ossec td-agent-bit 2>/dev/null || echo "Note: Could not add fluent-bit user to ossec group - you may need to do this manually"

# Restart Wazuh Manager to apply JSON output
echo "Restarting Wazuh Manager..."
systemctl restart wazuh-manager

# Restart Fluent Bit to apply new configuration
echo "Restarting Fluent Bit..."
systemctl restart fluent-bit 2>/dev/null || systemctl restart td-agent-bit 2>/dev/null || echo "Please restart Fluent Bit manually"

echo ""
echo "=== Configuration Complete ==="
echo "Fluent Bit is now configured to:"
echo "  - Read Wazuh alerts from: /var/ossec/logs/alerts/alerts.json"
echo "  - Forward to OpenSearch: ${OPENSEARCH_HOST}:${OPENSEARCH_PORT}"
echo "  - Index pattern: wazuh-alerts-YYYY.MM.DD"
echo ""
echo "Check Fluent Bit logs: journalctl -u fluent-bit -f"
echo "Check Wazuh Manager logs: tail -f /var/ossec/logs/ossec.log"