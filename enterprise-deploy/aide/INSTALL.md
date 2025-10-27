# AIDE Installation Guide for OpenGRC

Quick installation guide for setting up AIDE file integrity monitoring with syslog alerting.

## Prerequisites

- Ubuntu/Debian Linux system
- Root/sudo access
- OpenGRC application installed at `/var/www/html`
- Syslog daemon (rsyslog) running
- Optional: Fluent Bit configured for log forwarding

## Installation Steps

### 1. Run the Installation Script

```bash
sudo bash /var/www/html/enterprise-deploy/aide/install-aide.sh
```

**What this does:**
- Installs AIDE package
- Copies configuration files
- Initializes AIDE database (takes 5-15 minutes)
- Configures syslog integration
- Sets up daily cron job at 3:15 AM
- Configures log rotation

### 2. Verify Installation

```bash
# Check AIDE is installed
aide --version

# Check database exists
ls -lh /var/lib/aide/aide.db

# Check configuration
cat /etc/aide/aide.conf

# Check cron job
cat /etc/cron.d/aide
```

### 3. Run First Manual Check

```bash
sudo /usr/local/bin/aide-check
```

You should see: `✓ No changes detected - System integrity verified`

### 4. Verify Syslog Integration

```bash
# Check syslog configuration
cat /etc/rsyslog.d/30-aide.conf

# Check AIDE logs
tail -f /var/log/aide/aide-check.log

# Check syslog
tail -f /var/log/syslog | grep aide
```

### 5. Test Alert Functionality

Create a test file to trigger an alert:

```bash
# Create a monitored test file
sudo touch /var/www/html/app/test-aide.php

# Run AIDE check
sudo /usr/local/bin/aide-check

# You should see an alert about the added file
# Check syslog
grep "aide-check" /var/log/syslog

# Remove test file
sudo rm /var/www/html/app/test-aide.php

# Update AIDE database to clear the alert
sudo aide --update && sudo mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
```

## Integration with Fluent Bit

If you're using Fluent Bit for log forwarding (recommended), the configuration has been updated to include AIDE logs.

### Restart Fluent Bit

```bash
# Check if Fluent Bit is running
systemctl status fluent-bit

# Restart to pick up new configuration
sudo systemctl restart fluent-bit

# Verify AIDE inputs are loaded
curl http://localhost:2020/api/v1/config | jq '.inputs[] | select(.name=="tail" and (.path | contains("aide")))'
```

### Verify Logs in OpenSearch

AIDE logs will be sent to the `security-aide-logs` index in OpenSearch.

Search for:
- Index: `security-aide-logs`
- Tags: `security.aide`, `security.aide-check`
- Fields: `event.dataset: aide.integrity`

## Post-Installation Configuration

### Adjust Monitored Files

Edit `/etc/aide/aide.conf` to add or exclude files:

```bash
sudo nano /etc/aide/aide.conf
```

**To add a directory:**
```
/path/to/monitor CONFIGS
```

**To exclude a file:**
```
!/path/to/exclude
```

After changes, update the database:
```bash
sudo aide --update && sudo mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
```

### Change Check Schedule

Edit `/etc/cron.d/aide`:

```bash
sudo nano /etc/cron.d/aide
```

Example schedules:
```
# Every 6 hours
0 */6 * * * root /usr/local/bin/aide-check >> /var/log/aide/aide-check.log 2>&1

# Twice daily (6 AM and 6 PM)
0 6,18 * * * root /usr/local/bin/aide-check >> /var/log/aide/aide-check.log 2>&1

# Hourly
0 * * * * root /usr/local/bin/aide-check >> /var/log/aide/aide-check.log 2>&1
```

## Common Post-Deployment Tasks

### After Code Deployment

```bash
# Deploy your code
cd /var/www/html
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Update AIDE database
sudo aide --update && sudo mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
```

### After System Updates

```bash
# Update system packages
sudo apt-get update && sudo apt-get upgrade -y

# Update AIDE database
sudo aide --update && sudo mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
```

### After Configuration Changes

```bash
# Make configuration changes
sudo nano /var/www/html/.env
# or
sudo nano /etc/apache2/sites-available/opengrc.conf

# Reload services
sudo systemctl reload apache2

# Update AIDE database
sudo aide --update && sudo mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
```

## Monitoring & Alerts

### View Recent Alerts

```bash
# AIDE check logs
tail -100 /var/log/aide/aide-check.log

# AIDE report
tail -100 /var/log/aide/aide.log

# Syslog AIDE entries
grep aide /var/log/syslog | tail -50
```

### Search for Critical Alerts

```bash
# Critical security alerts
grep "CRITICAL" /var/log/aide/aide-check.log

# All file integrity violations
grep "ALERT" /var/log/aide/aide-check.log

# Changed files only
grep "CHANGED:" /var/log/syslog | grep aide
```

## Troubleshooting

### AIDE Check Fails

```bash
# Check AIDE status
sudo aide --check

# Verbose output
sudo aide --check --verbose

# Check database integrity
sudo aide --compare
```

### No Syslog Entries

```bash
# Verify rsyslog is running
sudo systemctl status rsyslog

# Check rsyslog AIDE configuration
cat /etc/rsyslog.d/30-aide.conf

# Restart rsyslog
sudo systemctl restart rsyslog

# Test logging
logger -t aide-check -p local6.alert "Test AIDE message"
grep "Test AIDE message" /var/log/syslog
```

### Database Too Old

```bash
# Check database age
stat /var/lib/aide/aide.db

# Rebuild database
sudo aide --update
sudo mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
```

## Security Best Practices

1. **Protect the database**: The AIDE database should be protected with strict permissions (already configured)
2. **Regular updates**: Update the database after each legitimate system change
3. **Off-system backups**: Consider backing up `/var/lib/aide/aide.db` to secure offline storage
4. **Review alerts promptly**: Don't ignore AIDE alerts - investigate all file changes
5. **Automated response**: Consider integrating with incident response workflows

## Uninstallation (if needed)

```bash
# Remove cron job
sudo rm /etc/cron.d/aide

# Remove rsyslog configuration
sudo rm /etc/rsyslog.d/30-aide.conf
sudo systemctl restart rsyslog

# Remove AIDE package
sudo apt-get remove --purge aide aide-common

# Remove files
sudo rm -rf /var/lib/aide
sudo rm -rf /var/log/aide
sudo rm /usr/local/bin/aide-check
```

## Support

For issues or questions:
- Review the main README: [README.md](README.md)
- Check AIDE documentation: https://aide.github.io/
- OpenGRC issues: https://github.com/lmangold/OpenGRC/issues
