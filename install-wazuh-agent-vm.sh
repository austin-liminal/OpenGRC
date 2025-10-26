#!/bin/bash
set -e

echo "=== Installing Wazuh Agent on VM ==="

# Import GPG key (if not already done)
curl -s https://packages.wazuh.com/key/GPG-KEY-WAZUH | gpg --no-default-keyring --keyring gnupg-ring:/usr/share/keyrings/wazuh.gpg --import 2>/dev/null && chmod 644 /usr/share/keyrings/wazuh.gpg || true

# Add Wazuh repository (if not already added)
if [ ! -f /etc/apt/sources.list.d/wazuh.list ]; then
    echo "deb [signed-by=/usr/share/keyrings/wazuh.gpg] https://packages.wazuh.com/4.x/apt/ stable main" | tee -a /etc/apt/sources.list.d/wazuh.list
fi

# Update package information
apt-get update

# Install Wazuh Agent
# WAZUH_MANAGER sets the manager IP (localhost since manager is on same VM)
WAZUH_MANAGER="127.0.0.1" apt-get install -y wazuh-agent

# Enable and start Wazuh Agent
systemctl daemon-reload
systemctl enable wazuh-agent
systemctl start wazuh-agent

echo "Wazuh Agent installation complete!"
echo "Agent is connected to manager at 127.0.0.1"

# Check status
systemctl status wazuh-agent --no-pager