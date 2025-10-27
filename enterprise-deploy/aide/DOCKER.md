# AIDE in Docker Container

This document describes how AIDE file integrity monitoring is integrated into the OpenGRC Docker container.

## Overview

AIDE is installed and configured in the Docker image and automatically initializes on container startup. This provides immediate file integrity monitoring for deployed containers.

## How It Works

### Build Time (Dockerfile)

1. **AIDE Installation**: AIDE and aide-common packages are installed during image build
2. **Configuration Copy**: AIDE configuration (`aide.conf`) is copied to `/etc/aide/aide.conf`
3. **Script Installation**: The `aide-check.sh` script is copied to `/usr/local/bin/aide-check`
4. **Rsyslog Configuration**: Rsyslog is configured to route AIDE logs to `/var/log/aide/`
5. **Fluent Bit Configuration**: Fluent Bit is configured to forward AIDE logs to OpenSearch

### Runtime (Container Startup)

When a container starts, the entrypoint script ([entrypoint.sh](../entrypoint.sh)) performs:

#### First Launch (No Database)
```
1. Detect missing AIDE database
2. Initialize AIDE database with aideinit (takes 3-5 minutes)
3. Move aide.db.new to aide.db
4. Run initial integrity check to establish baseline
5. Log initialization to syslog
```

#### Subsequent Launches (Database Exists)
```
1. Detect existing AIDE database
2. Run integrity check against current files
3. Alert if any changes detected since last database update
4. Log results to syslog
```

## Container Startup Sequence

The full startup sequence is:

```
1. Environment validation
2. OpenGRC deployment (opengrc:deploy)
3. Cache optimization
4. Start rsyslog
5. ✓ Initialize/check AIDE ← NEW
6. Start Fluent Bit
7. Start PHP-FPM
8. Start Apache
```

## AIDE Database Persistence

### Important: Database Storage

The AIDE database is stored at `/var/lib/aide/aide.db` inside the container.

**Without Volume Mounting:**
- Database is created on first container launch
- Database is lost when container is destroyed
- New container will reinitialize database (taking 3-5 minutes)

**With Volume Mounting (Recommended):**
```yaml
# In your docker-compose.yml or App Spec
volumes:
  - aide-data:/var/lib/aide

volumes:
  aide-data:
```

**Benefits of persistent storage:**
- Database survives container restarts
- Faster container startup (no reinitialization)
- Historical integrity tracking across deployments
- Detects changes between deployments

## DigitalOcean App Platform Integration

For DigitalOcean App Platform deployment:

### Option 1: Without Persistent Storage (Simpler)

The AIDE database will be recreated on each deployment. This is acceptable for:
- Frequent deployments where filesystem is expected to change
- Stateless containers
- When startup time is not critical

**Pros:**
- No additional configuration needed
- Clean baseline on each deployment

**Cons:**
- 3-5 minute initialization delay on first container start
- Cannot track changes between deployments

### Option 2: With Persistent Storage (Recommended for Production)

Add to your `app-spec.yaml`:

```yaml
services:
  - name: opengrc-web
    # ... existing configuration ...

    # Add persistent storage for AIDE database
    volumes:
      - name: aide-db
        path: /var/lib/aide
        size_gb: 1

# Define the volume
volumes:
  - name: aide-db
    storage_size_gb: 1
```

**Pros:**
- Fast container startup (database already exists)
- Tracks changes across deployments
- Better security monitoring

**Cons:**
- Requires manual database update after legitimate deployments
- Additional storage costs (minimal)

## Managing AIDE in Containers

### View AIDE Logs

```bash
# SSH into running container
doctl apps exec <app-id> <component-name> --interactive -- /bin/bash

# View AIDE check logs
tail -f /var/log/aide/aide-check.log

# View AIDE report
tail -f /var/log/aide/aide.log

# View syslog AIDE entries
grep aide /var/log/syslog
```

### Run Manual Check

```bash
# Inside container
aide-check

# Or directly
aide --check
```

### Update Database After Deployment

After deploying new code or making configuration changes:

```bash
# Inside container
aide --update
mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
```

### Check Database Status

```bash
# Inside container
ls -lh /var/lib/aide/aide.db
stat /var/lib/aide/aide.db
```

## Monitoring AIDE Alerts

### In Container Logs

AIDE initialization and check results appear in container startup logs:

```
=== AIDE File Integrity Monitoring Setup ===
AIDE database not found. Initializing AIDE database...
This will take a few minutes on first launch...
AIDE database initialized successfully at /var/lib/aide/aide.db
Initial AIDE check completed - baseline established
AIDE database size: 45M (created: 2025-10-26)
AIDE monitoring active - logs: /var/log/aide/
```

### In OpenSearch

AIDE logs are forwarded to OpenSearch via Fluent Bit:

- **Index**: `security-aide-logs`
- **Tags**: `security.aide`, `security.aide-check`
- **Fields**:
  - `service.type`: `security`
  - `event.dataset`: `aide.integrity`
  - `event.category`: `file`
  - `event.kind`: `alert`
  - `security.tool`: `aide`

### Alert Examples

```json
{
  "service.type": "security",
  "event.dataset": "aide.integrity",
  "event.kind": "alert",
  "message": "ALERT: FILE INTEGRITY VIOLATIONS DETECTED",
  "security.tool": "aide",
  "app_name": "OpenGRC",
  "environment": "production"
}
```

## Deployment Workflow

### Standard Deployment

1. **Deploy new code** → Container restarts
2. **AIDE detects changes** → Alerts triggered
3. **Review changes** → Verify they're from your deployment
4. **Update database** (if using persistent storage):
   ```bash
   doctl apps exec <app-id> <component-name> --interactive -- \
     bash -c "aide --update && mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db"
   ```

### Automated Database Update

Create a post-deployment script:

```bash
#!/bin/bash
# post-deploy-aide-update.sh

APP_ID="your-app-id"
COMPONENT="opengrc-web"

echo "Updating AIDE database after deployment..."
doctl apps exec $APP_ID $COMPONENT --interactive -- \
  bash -c "aide --update && mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db && echo 'AIDE database updated'"

echo "AIDE database update complete"
```

## Performance Impact

### Image Build
- **Additional packages**: ~15 MB (aide, aide-common)
- **Configuration files**: ~20 KB
- **Build time increase**: ~30 seconds

### Container Startup

**First Launch (No Database):**
- **Initialization time**: 3-5 minutes (one-time)
- **Database size**: ~40-60 MB
- **Memory usage during init**: ~200-300 MB peak

**Subsequent Launches (Database Exists):**
- **Check time**: 30-60 seconds
- **Memory usage**: ~100-150 MB during check
- **No noticeable delay** to application availability

### Runtime
- **CPU**: Negligible (checks only run on startup)
- **Memory**: No ongoing impact
- **Disk**: ~50 MB for database
- **Network**: AIDE logs forwarded via Fluent Bit (minimal)

## Troubleshooting

### AIDE Initialization Fails

Check container logs:
```bash
doctl apps logs <app-id> --component <component-name> --type run
```

Look for:
```
ERROR: AIDE database initialization failed
ERROR: aideinit command failed
```

**Solutions:**
- Verify AIDE packages installed correctly
- Check disk space: `df -h /var/lib/aide`
- Check permissions: `ls -la /var/lib/aide`

### Database Too Old Warning

```
⚠ Warning: AIDE database is 45 days old
```

**Solution:** Update the database after verifying changes are legitimate:
```bash
aide --update && mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
```

### False Positives on Every Startup

If using persistent storage and getting alerts every startup:

**Cause:** Some files naturally change between runs (timestamps, cache files)

**Solution:** Update `aide.conf` to exclude those files:
```bash
# Add to /etc/aide/aide.conf
!/var/www/html/storage/framework/cache
!/var/www/html/bootstrap/cache
```

Then rebuild the image.

### Check Not Running

**Verify AIDE is installed:**
```bash
which aide
aide --version
```

**Check script exists:**
```bash
ls -la /usr/local/bin/aide-check
```

**Run manually:**
```bash
aide-check
```

## Security Considerations

### Database Integrity

The AIDE database itself is critical:
- Stored with restricted permissions (0700)
- Configuration is read-only (0600)
- Database should be backed up to secure location

### Container Immutability

Containers should be immutable in production:
- Application code should not change after deployment
- AIDE alerts indicate potential compromise if not from deployment
- Investigate all unexpected file changes

### Monitoring Recommendations

1. **Alert on CRITICAL events** in OpenSearch
2. **Dashboard AIDE alerts** by severity
3. **Automated response** to file integrity violations
4. **Regular database updates** after deployments
5. **Backup database** to object storage

## References

- Main AIDE Documentation: [README.md](README.md)
- Installation Guide: [INSTALL.md](INSTALL.md)
- AIDE Configuration: [aide.conf](aide.conf)
- Check Script: [aide-check.sh](aide-check.sh)
- Entrypoint Script: [../entrypoint.sh](../entrypoint.sh)
- Dockerfile: [../../Dockerfile](../../Dockerfile)
