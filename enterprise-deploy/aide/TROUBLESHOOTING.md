# AIDE Troubleshooting Guide

Common issues and solutions when running AIDE in Docker containers.

## Capabilities Error

### Error Message
```
Unable to set capabilities [--caps=cap_dac_read_search,cap_audit_write+eip cap_setpcap,cap_setuid,cap_setgid+ep]
```

### Cause
AIDE attempts to set Linux capabilities to access protected files. In containerized environments (especially managed platforms like DigitalOcean App Platform), containers run with restricted capabilities and cannot set these.

### Solution (Already Implemented)
The AIDE configuration has been updated to work without capabilities:

1. **Environment Variable**: `AIDE_NO_CAPSNG=1` is set in:
   - `/etc/default/aide` (system-wide)
   - [entrypoint.sh](../entrypoint.sh) (runtime)
   - [aide-check.sh](aide-check.sh) (manual checks)

2. **Modified aideinit**: The `/usr/bin/aideinit` script exports this variable

3. **No Impact**: AIDE will still function correctly for file integrity monitoring without capabilities

### Verification
Check that the environment variable is set:
```bash
# Inside container
echo $AIDE_NO_CAPSNG  # Should output: 1

# Check aide default config
cat /etc/default/aide  # Should contain: AIDE_NO_CAPSNG=1
```

### Manual Fix (if needed)
If you still see capability errors:

```bash
# Set environment variable before running AIDE
export AIDE_NO_CAPSNG=1

# Then run AIDE commands
aideinit -y -f
aide --check
```

---

## Database Initialization Hangs

### Symptoms
- Container startup takes >10 minutes
- `aideinit` process appears stuck
- No progress messages

### Cause
- Large filesystem with many files
- I/O throttling on platform
- Insufficient memory

### Solutions

**1. Check Progress**
```bash
# Watch the process
ps aux | grep aide

# Check if database is being written
watch -n 1 'ls -lh /var/lib/aide/'

# Monitor I/O
iostat -x 1
```

**2. Reduce Monitored Files**
Edit [aide.conf](aide.conf) to exclude more directories:

```bash
# Add exclusions
!/var/www/html/vendor
!/var/www/html/node_modules
!/usr/share/doc
!/usr/share/man
```

**3. Increase Memory**
In `app-spec.yaml`:
```yaml
instance_size_slug: professional-xs  # or larger
```

**4. Run in Background (Not Recommended)**
Modify [entrypoint.sh](../entrypoint.sh) to initialize in background:
```bash
# NOT RECOMMENDED - loses startup feedback
aideinit -y -f &
```

---

## Database Corruption

### Error Message
```
AIDE database corrupt or incompatible
Unable to read database
```

### Causes
- Incomplete initialization (container killed during init)
- Disk full
- Permissions issue
- Version mismatch

### Solutions

**1. Remove and Reinitialize**
```bash
# Inside container
rm -f /var/lib/aide/aide.db /var/lib/aide/aide.db.new
aideinit -y -f
mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
```

**2. Check Disk Space**
```bash
df -h /var/lib/aide
```

**3. Check Permissions**
```bash
ls -la /var/lib/aide/
# Should be: drwx------ root root

# Fix if needed
chmod 0700 /var/lib/aide
chown root:root /var/lib/aide/aide.db
```

**4. Check AIDE Version**
```bash
aide --version
# Ensure it matches the version that created the database
```

---

## Permission Denied Errors

### Error Message
```
Permission denied: /var/lib/aide/aide.db
Cannot write to /var/log/aide/
```

### Causes
- Incorrect directory permissions
- Running as non-root user
- SELinux/AppArmor restrictions

### Solutions

**1. Check Permissions**
```bash
ls -la /var/lib/aide
ls -la /var/log/aide
ls -la /etc/aide
```

**2. Fix Permissions**
```bash
# AIDE database directory
chmod 0700 /var/lib/aide
chown root:root /var/lib/aide

# Log directory
chmod 0755 /var/log/aide
chown root:root /var/log/aide

# Config file
chmod 0600 /etc/aide/aide.conf
chown root:root /etc/aide/aide.conf
```

**3. Verify Running as Root**
AIDE must run as root:
```bash
whoami  # Should output: root
```

---

## No Logs in Syslog

### Symptoms
- AIDE runs but no logs in `/var/log/syslog`
- AIDE logs not appearing in OpenSearch

### Causes
- rsyslog not running
- Incorrect rsyslog configuration
- Fluent Bit not capturing logs

### Solutions

**1. Check rsyslog**
```bash
# Verify running
pgrep rsyslogd

# Start if needed
/usr/sbin/rsyslogd

# Check configuration
cat /etc/rsyslog.d/30-aide.conf
```

**2. Test Logging**
```bash
# Send test message
logger -t aide-check -p local6.alert "Test AIDE message"

# Check if it appears
grep "Test AIDE message" /var/log/syslog
tail -f /var/log/aide/aide-check.log
```

**3. Restart rsyslog**
```bash
pkill rsyslogd
/usr/sbin/rsyslogd
```

**4. Check Fluent Bit**
```bash
# Verify running
pgrep fluent-bit

# Check configuration
cat /etc/fluent-bit/fluent-bit.conf | grep -A 10 "security.aide"

# Check Fluent Bit logs
curl http://localhost:2020/api/v1/metrics
```

---

## False Positives on Every Startup

### Symptoms
- AIDE reports file changes on every container restart
- Same files reported changed repeatedly

### Causes
- Files legitimately change during container lifecycle
- Timestamps update on startup
- Cache files regenerated

### Solutions

**1. Identify Patterns**
```bash
# Review repeated changes
grep "CHANGED:" /var/log/aide/aide-check.log | sort | uniq -c | sort -rn
```

**2. Exclude Dynamic Files**
Edit [aide.conf](aide.conf):
```bash
# Exclude cache files
!/var/www/html/bootstrap/cache
!/var/www/html/storage/framework/cache
!/var/www/html/storage/framework/sessions
!/var/www/html/storage/framework/views

# Exclude timestamp-only changes
# Use different rule for files that only change mtime
DYNAMIC = p+u+g+s+b+sha512
/some/dynamic/path DYNAMIC
```

**3. Update Database After Startup**
For persistent volume setups, update database after first successful startup:
```bash
aide --update && mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
```

---

## Check Takes Too Long

### Symptoms
- `aide-check` runs for >5 minutes
- Container startup delayed
- Timeouts during checks

### Causes
- Too many monitored files
- Slow I/O
- Large files being checksummed

### Solutions

**1. Optimize AIDE Config**
Edit [aide.conf](aide.conf) to use faster checksums:

```bash
# Use SHA256 instead of SHA512 for speed
BINARIES = p+i+n+u+g+s+b+m+c+sha256

# Or skip checksums for some files
PERMS_ONLY = p+i+n+u+g

# Use for non-critical directories
/usr/share PERMS_ONLY
```

**2. Exclude Large Directories**
```bash
!/var/www/html/vendor
!/usr/lib/node_modules
!/usr/share/doc
```

**3. Run Checks Less Frequently**
For non-critical environments, disable startup checks:

In [entrypoint.sh](../entrypoint.sh), comment out:
```bash
# Skip startup check to speed up container launch
# if /usr/local/bin/aide-check; then
#     ...
# fi
```

---

## Memory Issues During Initialization

### Error Message
```
Out of memory
Killed (OOM)
Container restart loop
```

### Causes
- Large filesystem
- Insufficient container memory
- Memory limit too low

### Solutions

**1. Increase Container Memory**
In `app-spec.yaml`:
```yaml
instance_size_slug: professional-s  # 1GB -> 2GB
```

**2. Reduce Database Size**
Exclude more files in [aide.conf](aide.conf):
```bash
!/var/www/html/vendor
!/usr/share
!/usr/lib/x86_64-linux-gnu
```

**3. Monitor Memory During Init**
```bash
# Watch memory usage
watch -n 1 'free -h'

# Check AIDE memory usage
top -p $(pgrep aide)
```

**4. Split Initialization**
For very large filesystems, consider initializing database offline:
```bash
# On a larger instance
aideinit -y -f

# Copy database to persistent storage
# Use in production containers
```

---

## Container Crashes During AIDE Init

### Symptoms
- Container exits during first startup
- Process killed before database created
- Health check failures

### Causes
- Platform timeout during long initialization
- Health check failing during init
- OOM killer

### Solutions

**1. Increase Health Check Grace Period**
In Dockerfile, increase start-period:
```dockerfile
HEALTHCHECK --interval=30s --timeout=3s --start-period=300s --retries=5 \
    CMD curl -f http://localhost/ || exit 1
```

**2. Background Initialization**
Modify [entrypoint.sh](../entrypoint.sh):
```bash
# Start services first, init AIDE after
# Start Apache first
exec /usr/sbin/apache2ctl -D FOREGROUND &

# Then initialize AIDE in background
(
    sleep 30  # Wait for services to be healthy
    aideinit -y -f
    mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
) &

# Wait for Apache
wait
```

**3. Pre-build Database**
Create database during image build (not recommended for security):
```dockerfile
# In Dockerfile (after copying all files)
RUN aideinit -y -f && mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
```

---

## Getting Help

If you encounter issues not covered here:

1. **Check Logs**
   ```bash
   # Container logs
   doctl apps logs <app-id> --component <component-name> --type run

   # AIDE specific logs
   docker exec <container-id> cat /var/log/aide/aide-check.log
   ```

2. **Enable Debug Mode**
   ```bash
   # Run AIDE with verbose output
   aide --check --verbose

   # Check with increased logging
   AIDE_DEBUG=1 aideinit -y -f
   ```

3. **Review Configuration**
   ```bash
   # Test AIDE configuration
   aide --config-check

   # List what will be monitored
   aide --config-check --verbose
   ```

4. **Community Resources**
   - [AIDE GitHub Issues](https://github.com/aide/aide/issues)
   - [AIDE Documentation](https://aide.github.io/)
   - [OpenGRC Issues](https://github.com/lmangold/OpenGRC/issues)

## Reference Documents

- [Main README](README.md) - Complete AIDE documentation
- [Installation Guide](INSTALL.md) - Setup instructions
- [Docker Integration](DOCKER.md) - Container-specific docs
- [Configuration File](aide.conf) - AIDE rules and settings
