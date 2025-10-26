#!/bin/bash
set -e

echo "=== Enabling JSON Output in Wazuh Manager ==="

# Enable JSON output in Wazuh Manager
echo "Enabling JSON output in Wazuh Manager..."
if grep -q "<jsonout_output>" /var/ossec/etc/ossec.conf; then
    echo "JSON output already enabled in Wazuh config"
else
    # Add JSON output configuration
    sed -i '/<global>/a\    <jsonout_output>yes</jsonout_output>' /var/ossec/etc/ossec.conf
    echo "Enabled JSON output in Wazuh config"
fi

# Restart Wazuh Manager to apply JSON output
echo "Restarting Wazuh Manager..."
systemctl restart wazuh-manager

echo ""
echo "=== Configuration Complete ==="
echo "Wazuh Manager is now outputting alerts in JSON format to:"
echo "  /var/ossec/logs/alerts/alerts.json"
echo ""
echo "The Docker container will automatically read these logs via volume mount."
echo "Make sure to start your Docker container with:"
echo "  -v /var/ossec/logs:/host/var/ossec/logs:ro"