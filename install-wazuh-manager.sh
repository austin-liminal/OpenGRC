#!/bin/bash
set -e

echo "=== Installing Wazuh Manager ==="

# Import GPG key
curl -s https://packages.wazuh.com/key/GPG-KEY-WAZUH | gpg --no-default-keyring --keyring gnupg-ring:/usr/share/keyrings/wazuh.gpg --import && chmod 644 /usr/share/keyrings/wazuh.gpg

# Add Wazuh repository
echo "deb [signed-by=/usr/share/keyrings/wazuh.gpg] https://packages.wazuh.com/4.x/apt/ stable main" | tee -a /etc/apt/sources.list.d/wazuh.list

# Update package information
apt-get update

# Install Wazuh Manager
apt-get install -y wazuh-manager

# Enable and start Wazuh Manager
systemctl daemon-reload
systemctl enable wazuh-manager
systemctl start wazuh-manager

echo "Wazuh Manager installation complete!"
echo "Manager is running on port 1514 (agent connections) and 1515 (cluster)"

# Check status
systemctl status wazuh-manager --no-pager