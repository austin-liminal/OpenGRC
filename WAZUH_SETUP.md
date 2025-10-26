# Wazuh Setup Instructions

This guide will help you set up Wazuh Manager and Agents to forward all logs to OpenSearch.

## Architecture

- **Wazuh Manager**: Runs on the host VM, receives logs from agents
- **Wazuh Agent (VM)**: Runs on the host VM, monitors the VM itself
- **Wazuh Agent (Docker)**: Runs inside the Docker container, monitors the container
- **Fluent Bit (Docker)**: Single instance in container forwards ALL logs to OpenSearch

**Note**: Only ONE Fluent Bit instance is needed!
- Container Fluent Bit reads logs from:
  - Container: Laravel, Apache, PHP-FPM, syslog
  - Host (via volume mount): Wazuh security alerts from both agents
- Everything goes to the same OpenSearch server

## Installation Steps

### 1. Install Wazuh Manager on the VM

```bash
cd /home/lmangold/fork/OpenGRC
sudo chmod +x install-wazuh-manager.sh
sudo ./install-wazuh-manager.sh
```

This will:
- Install Wazuh Manager
- Start the manager service
- Manager listens on port 1514 for agent connections

### 2. Install Wazuh Agent on the VM

```bash
sudo chmod +x install-wazuh-agent-vm.sh
sudo ./install-wazuh-agent-vm.sh
```

This will:
- Install Wazuh Agent on the VM
- Configure it to connect to the local manager (127.0.0.1)
- Start monitoring the VM

### 3. Enable JSON Output in Wazuh Manager

Enable JSON formatted logs so Fluent Bit can read them:

```bash
sudo chmod +x enable-wazuh-json.sh
sudo ./enable-wazuh-json.sh
```

This will:
- Enable JSON output in Wazuh Manager configuration
- Restart Wazuh Manager
- Wazuh alerts will be written to `/var/ossec/logs/alerts/alerts.json`

### 4. Build and Deploy Docker Container with Wazuh Agent

**IMPORTANT**: You must mount the Wazuh logs directory into the container so Fluent Bit can read them.

For DigitalOcean App Platform, add to your `app-spec.yaml`:

```yaml
services:
  - name: opengrc
    # ... other config ...
    volumes:
      - name: wazuh-logs
        host_path: /var/ossec/logs
        mount_path: /host/var/ossec/logs
        read_only: true
```

For `docker run`:

```bash
docker run -v /var/ossec/logs:/host/var/ossec/logs:ro opengrc
```

For `docker-compose.yml`:

```yaml
services:
  opengrc:
    volumes:
      - /var/ossec/logs:/host/var/ossec/logs:ro
```

The container will:
1. Auto-detect the Docker gateway IP (usually `172.17.0.1`)
2. Configure the Wazuh agent to connect to the manager on host
3. Start the Wazuh agent automatically
4. Fluent Bit will read Wazuh alerts from `/host/var/ossec/logs/alerts/alerts.json`
5. Forward everything (app logs + Wazuh alerts) to OpenSearch

## Port Requirements

Make sure these ports are open:

- **1514/tcp**: Wazuh agent → manager communication
- **1515/tcp**: Wazuh cluster communication (if using cluster)
- **55000/tcp**: Wazuh API (optional, for management)

## Verify Installation

### Check Wazuh Manager Status
```bash
sudo systemctl status wazuh-manager
```

### Check Wazuh Agent Status (VM)
```bash
sudo systemctl status wazuh-agent
```

### Check Fluent Bit Status (in Docker Container)
```bash
docker logs <container-name> | grep -i fluent
```

### List Connected Agents
```bash
sudo /var/ossec/bin/agent_control -l
```

You should see both:
- The VM agent (localhost)
- The Docker container agent

### Check OpenSearch Indices

In your OpenSearch dashboard, check for indices matching:
```
wazuh-alerts-4.x-*
```

## Logs to Monitor

Wazuh will automatically monitor:

### From VM Agent:
- System logs (`/var/log/syslog`, `/var/log/auth.log`)
- Security events
- File integrity monitoring
- Rootkit detection
- Vulnerability detection

### From Docker Agent:
- Laravel application logs
- Apache access/error logs
- PHP-FPM logs
- Container security events
- File integrity monitoring inside container

## Configuration Files

- **Wazuh Manager Config**: `/var/ossec/etc/ossec.conf`
- **Wazuh Agent Config (VM)**: `/var/ossec/etc/ossec.conf`
- **Wazuh Agent Config (Docker)**: `/var/ossec/etc/ossec.conf` (inside container)
- **Fluent Bit Config**: `/etc/fluent-bit/fluent-bit.conf`

## Troubleshooting

### Agent not connecting to manager

Check the manager logs:
```bash
sudo tail -f /var/ossec/logs/ossec.log
```

Check the agent logs (VM):
```bash
sudo tail -f /var/ossec/logs/ossec.log
```

Check the agent logs (Docker):
```bash
docker logs <container-name> | grep -i wazuh
```

### Logs not appearing in OpenSearch

Check Fluent Bit logs (in container):
```bash
docker logs <container-name> | grep -i fluent
```

Check if Wazuh alerts are being generated:
```bash
sudo tail -f /var/ossec/logs/alerts/alerts.json
```

Verify volume mount is working:
```bash
docker exec <container-name> ls -la /host/var/ossec/logs/alerts/
```

Test OpenSearch connection:
```bash
curl -k -u username:password https://opensearch-host:9200
```

### Register Docker Agent Manually

If the Docker agent doesn't auto-register, you can register it manually:

1. On the manager (VM):
```bash
sudo /var/ossec/bin/manage_agents
# Select option 'A' to add agent
# Enter name: opengrc-container
# Enter IP: any (or specific container IP)
# Copy the key provided
```

2. Inside the Docker container:
```bash
docker exec -it <container-name> /var/ossec/bin/manage_agents
# Select option 'I' to import key
# Paste the key from step 1
# Restart agent: /var/ossec/bin/wazuh-control restart
```

## Additional Configuration

### Monitor Specific Files in Docker

Edit `/var/ossec/etc/ossec.conf` inside the container to add specific file monitoring:

```xml
<syscheck>
  <directories check_all="yes">/var/www/html/app</directories>
  <directories check_all="yes">/var/www/html/config</directories>
  <directories check_all="yes">/var/www/html/database</directories>
</syscheck>
```

### Custom Rules

Create custom rules in `/var/ossec/etc/rules/local_rules.xml` on the manager.

## Dashboard in OpenSearch

Once data is flowing to OpenSearch, you can:
1. Create index pattern: `wazuh-alerts-4.x-*`
2. Import Wazuh dashboards (optional)
3. View security alerts, compliance data, and more

## Support

- Wazuh Documentation: https://documentation.wazuh.com/
- Wazuh Community: https://groups.google.com/g/wazuh