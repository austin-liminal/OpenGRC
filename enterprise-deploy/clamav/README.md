# ClamAV Malware Scanning

Automated malware scanning for OpenGRC using ClamAV antivirus engine.

## Overview

ClamAV provides nightly malware scans of critical directories with automatic virus definition updates and syslog alerting.

## Features

- **Nightly Scans**: Automatic scans at 11:00 PM daily
- **Auto-Update**: Virus definitions updated before each scan
- **Critical Detection**: Immediate CRITICAL alerts for infected files
- **Syslog Integration**: All events logged to syslog for centralized monitoring
- **OpenSearch Ready**: Logs forwarded to OpenSearch automatically
- **Efficient Scanning**: Excludes vendor/node_modules, limits file size

## Quick Start

### Automatic Scans

ClamAV runs automatically every night at 11:00 PM via cron:

- **Schedule**: 11:00 PM daily (0 23 * * *)
- **Command**: `/usr/local/bin/clamav-scan`
- **Logs**: `/var/log/clamav/scan.log`
- **Reports**: `/var/log/clamav/scan-report.txt`

### Manual Scan

```bash
# Run manual scan
clamav-scan

# Update virus definitions only
freshclam

# Check ClamAV version
clamscan --version
```

## Scanned Directories

ClamAV scans the following paths:

1. **`/var/www/html`** - OpenGRC application
   - Excludes: `vendor/`, `node_modules/`, `storage/framework/cache/`
   - Max file size: 100MB

2. **`/etc`** - System configuration files

3. **`/tmp`** - Temporary files

## Scan Output

### Clean Scan
```
==========================================
ClamAV Malware Scan
Started: 2025-10-27 23:00:00
==========================================

✓ Virus definitions updated

Starting malware scan...
Scanning directories:
  - /var/www/html (OpenGRC application)
  - /etc (System configuration)
  - /tmp (Temporary files)

Scanning /var/www/html...
Scanning /etc...
Scanning /tmp...

==========================================
Scan Complete
==========================================
Files scanned: 1,247
Infections found: 0

✓ No malware detected
Full report: /var/log/clamav/scan-report.txt
```

### Malware Detected
```
==========================================
ClamAV Malware Scan
Started: 2025-10-27 23:00:00
==========================================

✓ Virus definitions updated

Starting malware scan...

Scanning /var/www/html...
⚠️  MALWARE DETECTED: /var/www/html/public/uploads/evil.php: Php.Webshell.Generic FOUND
⚠️  MALWARE DETECTED: /tmp/backdoor.sh: Unix.Trojan.Generic FOUND

==========================================
Scan Complete
==========================================
Files scanned: 1,247
Infections found: 2

⚠️  WARNING: 2 infected file(s) detected!
Review the scan report: /var/log/clamav/scan-report.txt
```

## Syslog Integration

All ClamAV events are logged to syslog with facility `local6`:

```bash
# INFO level - scan start/completion
clamav-scan: Starting ClamAV scan - updating definitions
clamav-scan: ClamAV scan completed: 1247 files scanned, no threats found

# WARN level - definition update failures
clamav-scan: ClamAV definition update failed, using existing database

# CRITICAL level - malware detected
clamav-scan: MALWARE DETECTED: /var/www/html/public/uploads/evil.php: Php.Webshell.Generic FOUND
clamav-scan: CRITICAL: ClamAV detected 2 infected file(s)
```

View logs:
```bash
grep clamav /var/log/syslog
tail -f /var/log/clamav/scan.log
```

## OpenSearch Integration

ClamAV logs are forwarded to OpenSearch via Fluent Bit:

- **Index**: `security-clamav-logs`
- **Tag**: `security.clamav`
- **Fields**:
  - `service.type`: `security`
  - `event.dataset`: `clamav.malware`
  - `event.category`: `malware`
  - `event.kind`: `alert`
  - `security.tool`: `clamav`

## Virus Definition Updates

Virus definitions are automatically updated before each scan using `freshclam`.

### Manual Update
```bash
# Update virus definitions
freshclam

# Check database version
sigtool --info /var/lib/clamav/main.cvd
sigtool --info /var/lib/clamav/daily.cvd
```

### Database Location
- **Main database**: `/var/lib/clamav/main.cvd`
- **Daily updates**: `/var/lib/clamav/daily.cvd`
- **Bytecode**: `/var/lib/clamav/bytecode.cvd`

## Cron Schedule

View the cron configuration:
```bash
cat /etc/cron.d/clamav
```

Output:
```
# ClamAV malware scan - runs daily at 11:00 PM
0 23 * * * root /usr/local/bin/clamav-scan >> /var/log/clamav/cron.log 2>&1
```

## Container Startup

ClamAV is configured during container build but does not run on startup. The first scan runs at 11:00 PM.

```
Starting cron daemon...
cron started successfully - scheduled tasks active
  - Trivy vulnerability scans: daily at 2 AM
  - FIM integrity checks: hourly
  - ClamAV malware scans: daily at 11 PM
```

## Log Files

- **`/var/log/clamav/scan.log`** - Syslog events
- **`/var/log/clamav/scan-report.txt`** - Latest scan report (detailed)
- **`/var/log/clamav/cron.log`** - Cron execution log
- **`/var/log/syslog`** - System-wide log (includes ClamAV)

## Performance

- **Scan Duration**: 1-5 minutes (depending on files)
- **Memory Usage**: ~200-300 MB during scan
- **CPU Usage**: Low-medium (scheduled during off-hours)
- **Database Size**: ~200 MB (virus definitions)

## Exclusions

The following are excluded from scans for performance:

- `/var/www/html/vendor/*` - Composer dependencies
- `/var/www/html/node_modules/*` - NPM packages
- `/var/www/html/storage/framework/cache/*` - Laravel cache
- Files larger than 100 MB

## Response to Malware Detection

When malware is detected:

1. **Immediate Alert**: CRITICAL syslog message sent
2. **OpenSearch Alert**: Event forwarded to security index
3. **Scan Report**: Detailed report in `/var/log/clamav/scan-report.txt`
4. **Manual Action Required**: ClamAV detects but does not auto-delete

### Recommended Response Steps

```bash
# 1. Review the scan report
cat /var/log/clamav/scan-report.txt

# 2. Examine the infected file
ls -la /path/to/infected/file

# 3. Remove or quarantine the file
rm /path/to/infected/file
# OR
mv /path/to/infected/file /var/quarantine/

# 4. Investigate how it got there
grep "infected-filename" /var/log/apache2/access.log
grep "infected-filename" /var/log/fim/fim.log

# 5. Run another scan to verify
clamav-scan
```

## Changing Scan Schedule

To modify the scan time, edit the cron file:

```bash
# Edit cron schedule (requires image rebuild)
# In Dockerfile or setup-clamav-cron.sh, change:
0 23 * * *  # Current: 11:00 PM

# Examples:
0 2 * * *   # 2:00 AM
0 */6 * * * # Every 6 hours
0 12 * * 0  # Noon on Sundays
```

Then rebuild the Docker image.

## Troubleshooting

### Scan Not Running

```bash
# Check cron is running
pgrep cron

# Check cron job exists
cat /etc/cron.d/clamav

# Check cron logs
tail -f /var/log/clamav/cron.log

# Run manual scan
clamav-scan
```

### Definition Update Fails

```bash
# Check network connectivity
ping database.clamav.net

# Manual update with verbose output
freshclam -v

# Check database directory permissions
ls -la /var/lib/clamav/
chown -R clamav:clamav /var/lib/clamav
```

### High Memory Usage

```bash
# Check memory during scan
watch -n 1 'free -h'

# Reduce scan scope by adding exclusions in clamav-scan.sh
--exclude-dir="^/path/to/exclude"
```

### Scan Takes Too Long

Reduce scope or increase file size limits in `clamav-scan.sh`:

```bash
# Increase max file size
--max-filesize=200M  # Default is 100M

# Add more exclusions
--exclude-dir="^/usr/share"
```

## Files

- **[clamav-scan.sh](clamav-scan.sh)** - Main scan script
- **[setup-clamav-cron.sh](setup-clamav-cron.sh)** - Cron setup script
- **Cron file**: `/etc/cron.d/clamav`
- **Logs**: `/var/log/clamav/`

## Security Best Practices

1. **Monitor Alerts**: Set up OpenSearch alerts for malware detection
2. **Regular Scans**: Keep nightly schedule enabled
3. **Update Definitions**: Ensure freshclam is working
4. **Investigate Detections**: Always investigate source of malware
5. **Access Controls**: Restrict file upload directories
6. **FIM Integration**: Use with FIM to detect unauthorized file changes

## References

- [ClamAV Official Documentation](https://docs.clamav.net/)
- [ClamAV Signatures](https://www.clamav.net/documents/clamav-virus-database-faq)
- Integrated into: [Dockerfile](../../Dockerfile)
- Startup: [entrypoint.sh](../entrypoint.sh)
- Logging: [fluent-bit.conf](../fluent-bit/fluent-bit.conf)
