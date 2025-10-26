#!/bin/bash
set -e

echo "=== Configuring Wazuh Manager to Forward to OpenSearch ==="

# Prompt for OpenSearch details
read -p "Enter OpenSearch Host (e.g., opensearch.example.com): " OPENSEARCH_HOST
read -p "Enter OpenSearch Port (default 9200): " OPENSEARCH_PORT
OPENSEARCH_PORT=${OPENSEARCH_PORT:-9200}
read -p "Enter OpenSearch Username: " OPENSEARCH_USER
read -sp "Enter OpenSearch Password: " OPENSEARCH_PASSWORD
echo

# Backup original filebeat config
cp /etc/filebeat/filebeat.yml /etc/filebeat/filebeat.yml.backup

# Configure Filebeat to output to OpenSearch
cat > /etc/filebeat/filebeat.yml <<EOF
# Wazuh - Filebeat configuration file
output.elasticsearch:
  hosts: ["${OPENSEARCH_HOST}:${OPENSEARCH_PORT}"]
  username: "${OPENSEARCH_USER}"
  password: "${OPENSEARCH_PASSWORD}"
  protocol: https
  ssl.verification_mode: none
  indices:
    - index: "wazuh-alerts-4.x-%{+yyyy.MM.dd}"

setup.template.json.enabled: true
setup.template.json.path: '/etc/filebeat/wazuh-template.json'
setup.template.json.name: 'wazuh'
setup.ilm.overwrite: true
setup.ilm.enabled: false

filebeat.modules:
  - module: wazuh
    alerts:
      enabled: true
    archives:
      enabled: false

logging.level: info
logging.to_files: true
logging.files:
  path: /var/log/filebeat
  name: filebeat
  keepfiles: 7
  permissions: 0644

logging.metrics.enabled: false
EOF

# Install Filebeat if not already installed
if ! command -v filebeat &> /dev/null; then
    echo "Installing Filebeat..."
    curl -L -O https://artifacts.elastic.co/downloads/beats/filebeat/filebeat-oss-7.10.2-amd64.deb
    dpkg -i filebeat-oss-7.10.2-amd64.deb
    rm filebeat-oss-7.10.2-amd64.deb
fi

# Download Wazuh Filebeat module
curl -s https://packages.wazuh.com/4.x/filebeat/wazuh-filebeat-0.4.tar.gz | tar -xvz -C /usr/share/filebeat/module

# Download Wazuh template
curl -so /etc/filebeat/wazuh-template.json https://raw.githubusercontent.com/wazuh/wazuh/v4.7.0/extensions/elasticsearch/7.x/wazuh-template.json

# Set correct permissions
chmod go+r /etc/filebeat/wazuh-template.json

# Enable and restart Filebeat
systemctl daemon-reload
systemctl enable filebeat
systemctl restart filebeat

echo "Filebeat configuration complete!"
echo "Logs will be forwarded to OpenSearch at ${OPENSEARCH_HOST}:${OPENSEARCH_PORT}"
echo "Index pattern: wazuh-alerts-4.x-*"

# Check status
systemctl status filebeat --no-pager
