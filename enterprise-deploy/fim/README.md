# Simple File Integrity Monitoring (FIM)

A lightweight, container-friendly file integrity monitoring solution using SHA256 checksums.

## Overview

This FIM implementation monitors critical files for unauthorized changes and alerts via syslog. It's designed to work reliably in containerized environments without requiring special capabilities.

## Features

- **Simple & Fast**: Uses standard `sha256sum` - no complex dependencies
- **Container-Friendly**: No special Linux capabilities required
- **Syslog Integration**: All alerts sent to syslog for centralized logging
- **Critical File Detection**: Flags security-sensitive files (passwords, SSH config, .env)
- **Fluent Bit Ready**: Logs forwarded to OpenSearch automatically

## Quick Start

### Automatic (On Container Launch)

FIM runs automatically when the container starts:

1. **First launch**: Creates baseline checksums (~1-2 seconds)
2. **Subsequent launches**: Checks files against baseline

### Manual Commands

```bash
# Create/update baseline
fim-init

# Check for changes
fim-check
```

## Monitored Files

### System Files
- `/etc/passwd`, `/etc/shadow`, `/etc/group`
- `/etc/sudoers`
- `/etc/ssh/sshd_config`
- `/etc/pam.d/*`
- `/etc/cron.d/*`, `/etc/crontab`

### Web Server
- `/etc/apache2/sites-available/*`
- `/etc/php/*`

### OpenGRC Application
- `/var/www/html/.env` ⚠️ **CRITICAL**
- `/var/www/html/app/**`
- `/var/www/html/config/**`
- `/var/www/html/routes/**`
- `/var/www/html/artisan`
- `/var/www/html/composer.json`
- `/var/www/html/composer.lock`

## How It Works

### Baseline Creation (`fim-init`)

```bash
# Creates SHA256 checksums of all monitored files
sha256sum /etc/passwd > /var/lib/fim/checksums.db
sha256sum /etc/shadow >> /var/lib/fim/checksums.db
# ... etc for all monitored paths
```

Stores in: `/var/lib/fim/checksums.db`

### Integrity Check (`fim-check`)

```bash
# For each file in baseline:
1. Calculate current SHA256 checksum
2. Compare with baseline
3. Alert if different
4. Flag if critical security file
```

## Output Examples

### No Changes
```
=== FIM: File Integrity Check ===
Checking 247 files...

=== FIM Check Summary ===
Changed: 0
Removed: 0
Critical: 0
✓ No integrity violations detected
```

### Changes Detected
```
=== FIM: File Integrity Check ===
Checking 247 files...

CHANGED: /var/www/html/.env
  ⚠️  CRITICAL SECURITY FILE
CHANGED: /var/www/html/app/Models/User.php
REMOVED: /etc/cron.d/old-job

=== FIM Check Summary ===
Changed: 2
Removed: 1
Critical: 1
⚠️  File integrity violations detected!
```

## Syslog Integration

All FIM events are logged to syslog with facility `local6`:

```bash
# INFO level - normal operations
fim-init: FIM baseline created with 247 files

# ALERT level - file changes
fim-check: CHANGED: /var/www/html/app/Models/User.php (checksum mismatch)

# CRITICAL level - security files
fim-check: CRITICAL: Security-sensitive file changed: /var/www/html/.env
```

View in syslog:
```bash
grep fim /var/log/syslog
tail -f /var/log/fim/fim.log
```

## OpenSearch Integration

FIM logs are forwarded to OpenSearch via Fluent Bit:

- **Index**: `security-fim-logs`
- **Tag**: `security.fim`
- **Fields**:
  - `service.type`: `security`
  - `event.dataset`: `fim.integrity`
  - `event.category`: `file`
  - `event.kind`: `alert`
  - `security.tool`: `fim`

## After Code Deployment

When you deploy new code, files will change legitimately. Update the baseline:

```bash
# Inside container
fim-init

# Or from host
docker exec <container-id> fim-init
```

## Database Persistence

### Ephemeral (Default)
- Database recreated on each deployment
- Clean baseline every time
- ~1-2 second initialization

### Persistent Volume (Optional)
```yaml
# In app-spec.yaml
volumes:
  - name: fim-db
    path: /var/lib/fim
    size_gb: 1
```

**Benefits:**
- Detect changes across deployments
- Faster startup (skip init)

**Note:** Must manually update baseline after deployments

## Performance

- **Initialization**: 1-2 seconds (monitors ~200-300 files)
- **Check**: <1 second
- **Database size**: ~20-50 KB
- **Memory**: Negligible
- **CPU**: Only during init/check

## Troubleshooting

### Database Missing
```bash
# Check if database exists
ls -la /var/lib/fim/checksums.db

# Recreate
fim-init
```

### Permission Denied
```bash
# Fix permissions
chmod 0700 /var/lib/fim
chmod 0600 /var/lib/fim/checksums.db
```

### Too Many False Positives

Edit the scripts to exclude certain paths or adjust monitored files in `fim-init.sh`

### No Syslog Output
```bash
# Check rsyslog is running
pgrep rsyslogd

# Check configuration
cat /etc/rsyslog.d/30-fim.conf

# Test
logger -t fim-check -p local6.alert "Test FIM message"
grep "Test FIM message" /var/log/syslog
```

## Comparison with AIDE

| Feature | Simple FIM | AIDE |
|---------|-----------|------|
| Setup Time | 1-2 sec | 3-5 min |
| Dependencies | None (built-in tools) | aide package |
| Capabilities | None required | Requires CAP_DAC_READ_SEARCH |
| Database Size | 20-50 KB | 40-60 MB |
| Container-Friendly | ✅ Yes | ⚠️ Requires config |
| Complexity | Very Simple | Advanced |

## Files

- **[fim-init.sh](fim-init.sh)** - Create baseline checksums
- **[fim-check.sh](fim-check.sh)** - Check for file changes
- **Database**: `/var/lib/fim/checksums.db`
- **Logs**: `/var/log/fim/fim.log`

## Security Considerations

1. **Database Protection**: Stored in `/var/lib/fim` with 0700 permissions
2. **Critical Files**: Security-sensitive files trigger CRITICAL alerts
3. **Checksum Algorithm**: SHA256 (strong cryptographic hash)
4. **Immutable Containers**: Changes should only occur during deployment
5. **Audit Trail**: All changes logged to syslog and forwarded to OpenSearch

## References

- Integrated into: [Dockerfile](../../Dockerfile)
- Startup: [entrypoint.sh](../entrypoint.sh)
- Logging: [fluent-bit.conf](../fluent-bit/fluent-bit.conf)
