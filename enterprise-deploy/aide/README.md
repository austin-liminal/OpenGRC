# AIDE File Integrity Monitoring for OpenGRC

This directory contains the AIDE (Advanced Intrusion Detection Environment) configuration for monitoring critical files in the OpenGRC application and alerting via syslog when changes are detected.

## Overview

AIDE monitors file integrity by creating a cryptographic database of file checksums, permissions, ownership, and other attributes. When files change, AIDE detects the modifications and sends detailed alerts to syslog for centralized logging and monitoring.

## Quick Start

### Installation

Run the installation script as root:

```bash
sudo bash /var/www/html/enterprise-deploy/aide/install-aide.sh
```

This script will:
- Install AIDE and dependencies
- Configure AIDE with OpenGRC-specific rules
- Initialize the AIDE database
- Set up syslog integration
- Configure daily automated checks via cron
- Set up log rotation

### Manual Check

To manually run an integrity check:

```bash
sudo /usr/local/bin/aide-check
```

## What Files Are Monitored

### Critical System Files
- **System binaries**: `/bin`, `/sbin`, `/usr/bin`, `/usr/sbin`
- **System libraries**: `/lib`, `/usr/lib`
- **Boot files**: `/boot`
- **System configuration**: `/etc` (including SSH, PAM, sudoers)
- **Cron jobs**: All cron directories and crontab files

### OpenGRC Application Files
- **Application code**: `/var/www/html/app` (all Laravel code)
- **Configuration**: `/var/www/html/config`
- **Routes**: `/var/www/html/routes`
- **Public files**: `/var/www/html/public`
- **Environment file**: `/var/www/html/.env` (CRITICAL - contains secrets)
- **Composer dependencies**: `/var/www/html/vendor`
- **Artisan console**: `/var/www/html/artisan`

### Security-Critical Files (High Priority Alerts)
- `/etc/passwd`, `/etc/shadow` - User accounts
- `/etc/sudoers` - Privilege escalation
- `/etc/ssh/sshd_config` - SSH configuration
- `/var/www/html/.env` - Application secrets
- `/var/www/html/app/Providers` - Laravel service providers
- `/var/www/html/app/Http/Middleware` - Authentication/authorization
- `/etc/pam.d` - Authentication modules

### Excluded Files
- Temporary files and caches
- Log files (monitored separately)
- Database files (change frequently)
- User session data
- Framework cache files

## Syslog Integration

AIDE sends alerts to syslog with the following configuration:

- **Facility**: `local6`
- **Priority**: `alert` for file changes, `info` for normal operations
- **Tag**: `aide-check`
- **Log destination**: `/var/log/aide/aide.log`

### Syslog Message Format

```
[TIMESTAMP] [LEVEL] MESSAGE
```

Example alert messages:
```
aide-check: ALERT: FILE INTEGRITY VIOLATIONS DETECTED
aide-check: ALERT: ADDED: /var/www/html/app/Models/NewModel.php
aide-check: ALERT: CHANGED: /var/www/html/.env | SHA512 mtime
aide-check: CRITICAL: SECURITY ALERT: Critical file modified: /etc/sudoers
```

## Automated Checks

AIDE checks run automatically via cron:

- **Schedule**: Daily at 3:15 AM
- **Cron file**: `/etc/cron.d/aide`
- **Log output**: `/var/log/aide/aide-check.log`

To modify the schedule, edit `/etc/cron.d/aide`:

```bash
sudo nano /etc/cron.d/aide
```

## Fluent Bit Integration

AIDE logs are captured by Fluent Bit and forwarded to OpenSearch for centralized monitoring and alerting.

To view AIDE alerts in your logging system, search for:
- Tag: `system.aide`
- Program: `aide-check`
- Severity: `alert` or `critical`

## Operations

### After Legitimate Changes

When you make legitimate changes to monitored files (e.g., deploying code, updating configuration), you must update the AIDE database:

```bash
# Update the database
sudo aide --update

# Replace the old database with the new one
sudo mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
```

### Investigating Alerts

When AIDE detects changes:

1. **Review the alert** in syslog or `/var/log/aide/aide.log`
2. **Identify the changed files** - Check if changes were expected
3. **Investigate unauthorized changes** - Look for security incidents
4. **Update the database** if changes are legitimate

### Common Scenarios

#### After Application Deployment
```bash
# Deploy your code
git pull
composer install
php artisan migrate

# Update AIDE database
sudo aide --update && sudo mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
```

#### After System Updates
```bash
# Apply system updates
sudo apt-get update && sudo apt-get upgrade

# Update AIDE database
sudo aide --update && sudo mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
```

#### After Configuration Changes
```bash
# Modify configuration
sudo nano /etc/apache2/sites-available/opengrc.conf
sudo systemctl reload apache2

# Update AIDE database
sudo aide --update && sudo mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
```

## Monitoring & Alerting

### Alert Severity Levels

- **CRITICAL**: Changes to security-sensitive files (passwords, sudoers, SSH, .env)
- **ALERT**: Any file additions, removals, or modifications
- **WARN**: Database age warnings, stale lock files
- **INFO**: Normal operation, check start/completion

### Integration with Security Tools

AIDE logs can be integrated with:
- **OpenSearch**: Full-text search and visualization
- **SIEM systems**: Correlation with other security events
- **Alerting platforms**: PagerDuty, Slack, email notifications
- **Compliance tools**: Evidence for audit trails

## Troubleshooting

### Database Initialization Fails
```bash
# Check disk space
df -h /var/lib/aide

# Check permissions
ls -la /var/lib/aide

# Re-initialize
sudo aideinit -y -f
```

### Too Many False Positives
Edit `/etc/aide/aide.conf` and add exclusions:
```bash
# Exclude a specific file
!/var/www/html/storage/app/cache-file.txt

# Exclude a directory
!/var/www/html/some-dynamic-dir
```

### Missing Alerts
Check syslog configuration:
```bash
# Verify rsyslog is running
sudo systemctl status rsyslog

# Check AIDE syslog config
cat /etc/rsyslog.d/30-aide.conf

# Restart rsyslog
sudo systemctl restart rsyslog
```

### Manual Database Update
```bash
# Create new database
sudo aide --update

# Compare databases
sudo aide --compare

# Replace database
sudo mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
```

## Files in This Directory

- **install-aide.sh**: Installation and setup script
- **aide.conf**: AIDE configuration with file monitoring rules
- **aide-check.sh**: Automated check script with syslog integration
- **README.md**: This documentation

## Security Considerations

1. **Protect the AIDE database**: Stored in `/var/lib/aide/` with restricted permissions (0700)
2. **Secure the configuration**: `/etc/aide/aide.conf` has 0600 permissions
3. **Monitor AIDE itself**: Configuration files are monitored for tampering
4. **Offline backups**: Consider backing up the AIDE database to offline storage
5. **Regular updates**: Update the database after legitimate changes to prevent alert fatigue

## Performance Impact

AIDE checks are I/O intensive but have minimal runtime impact:
- **Initial database creation**: 5-15 minutes (one-time)
- **Daily checks**: 2-5 minutes (runs during low-traffic hours)
- **Database size**: ~50-200 MB depending on monitored files
- **Memory usage**: ~100-300 MB during checks

## Compliance & Auditing

AIDE helps meet compliance requirements for:
- **PCI-DSS**: Requirement 11.5 (File Integrity Monitoring)
- **HIPAA**: Security Rule (Integrity Controls)
- **NIST 800-53**: SI-7 (Software, Firmware, and Information Integrity)
- **ISO 27001**: A.12.2.1 (Controls against malware)
- **SOC 2**: CC7.1 (System monitoring)

## References

- [AIDE Official Documentation](https://aide.github.io/)
- [AIDE Manual](https://aide.github.io/doc/)
- [Linux File Integrity Monitoring Best Practices](https://www.linux.com/training-tutorials/monitoring-file-integrity-aide/)
