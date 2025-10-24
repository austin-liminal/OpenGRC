# OpenGRC Docker Deployment Guide for DigitalOcean App Platform

This guide explains how to deploy OpenGRC using Docker on DigitalOcean App Platform with Ubuntu 24.04, Apache2, and TLS 1.3+.

## Overview

The new Docker deployment replaces the Heroku buildpack approach with a modern containerized setup:

- **Base Image**: Ubuntu 24.04 LTS
- **Web Server**: Apache2 with SSL/TLS 1.3+ only
- **PHP**: 8.3 with all required extensions
- **Database**: External MySQL (DigitalOcean Managed) with SSL
- **Storage**: DigitalOcean Spaces
- **Email**: SMTP (AWS SES or similar)
- **Port**: 443 (HTTPS only)

## Key Differences from Buildpack Deployment

### What Changed
1. ✅ **Ubuntu 24.04** instead of 22.04 (fewer vulnerabilities)
2. ✅ **TLS 1.3+ only** (stronger security)
3. ✅ **Docker containerization** (better isolation and reproducibility)
4. ✅ **Direct Apache SSL** (no external SSL termination needed)
5. ✅ **Same deployment logic** (uses `php artisan opengrc:deploy`)

### What Stayed the Same
1. ✅ **Database**: Same MySQL configuration
2. ✅ **Storage**: Same DigitalOcean Spaces
3. ✅ **SMTP**: Same email configuration
4. ✅ **Deployment process**: Same `opengrc:deploy` command
5. ✅ **Environment variables**: Same names and values

## Files

- **[Dockerfile](Dockerfile)** - Container build configuration
- **[entrypoint.sh](entrypoint.sh)** - Container startup script (runs `opengrc:deploy`)
- **[.dockerignore](.dockerignore)** - Files excluded from Docker build
- **[app-spec.docker.yaml](app-spec.docker.yaml)** - DigitalOcean App Platform spec
- **[.env.docker.example](.env.docker.example)** - Environment variable reference

## Required Environment Variables

### Database (MySQL with SSL)
```bash
DB_CONNECTION=mysql
DB_HOST=your-db-host.db.ondigitalocean.com
DB_PORT=25060
DB_DATABASE=opengrc
DB_USERNAME=opengrc_user
DB_PASSWORD=your-secure-password
DB_SSL_MODE=require
```

### Application
```bash
APP_KEY=base64:YourGeneratedApplicationKeyHere
APP_NAME=OpenGRC
APP_URL=https://your-domain.com
APP_ENV=production
APP_DEBUG=false
```

### Admin User
```bash
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=secure-admin-password
```

### DigitalOcean Spaces (Optional but Recommended)
```bash
DO_BUCKET=your-space-name
DO_REGION=nyc3
DO_ACCESS_KEY_ID=DO00XXXXXXXXXXXX
DO_SECRET_ACCESS_KEY=your-secret-access-key
```

### SMTP Email (Optional)
```bash
SMTP_HOST=email-smtp.us-east-1.amazonaws.com
SMTP_PORT=465
SMTP_USER=AKIAXXXXXXXXXXXXXXXX
SMTP_PASSWORD=your-smtp-password
SMTP_ENCRYPTION=tls
SMTP_FROM=no-reply@example.com
```

### Storage Lock (Optional)
```bash
STORAGE_LOCK=true  # Prevents UI changes to storage settings
```

## Deployment Process

### 1. Container Startup Sequence

When the container starts, [entrypoint.sh](entrypoint.sh) performs these steps:

1. **SSL Certificate Check** - Installs custom certs if provided, otherwise uses self-signed
2. **Environment Validation** - Ensures all required variables are set
3. **Deploy Command** - Runs `php artisan opengrc:deploy` with all parameters:
   - Database configuration
   - Admin user creation
   - Site settings
   - DigitalOcean Spaces setup
   - SMTP configuration
   - Storage lock settings
4. **Cache Optimization** - Rebuilds config, route, and view cache
5. **Storage Link** - Creates public storage symlink
6. **Start Apache** - Launches Apache on port 443 with TLS 1.3+

### 2. First Run vs. Subsequent Runs

**First Run:**
- Detects no flag file at `/var/www/html/storage/.container_initialized`
- Runs full fresh deployment with migrations and seeding
- Creates admin user
- Sets up all configurations
- Creates flag file

**Subsequent Runs:**
- Detects existing flag file
- Runs update deployment (migrations only, no fresh)
- Preserves existing data
- Updates configurations if changed

### 3. Deployment to DigitalOcean App Platform

#### Option A: Using App Spec File

1. Update [app-spec.docker.yaml](app-spec.docker.yaml) with your values:
   - Replace `your-domain.opengrc.net` with your domain
   - Update database credentials (use your existing DB)
   - Set your `APP_KEY` (must persist across deployments)
   - Configure Spaces and SMTP credentials

2. Deploy using `doctl`:
   ```bash
   doctl apps create --spec app-spec.docker.yaml
   ```

   Or update existing app:
   ```bash
   doctl apps update YOUR_APP_ID --spec app-spec.docker.yaml
   ```

#### Option B: Using DigitalOcean Dashboard

1. Go to App Platform in DigitalOcean dashboard
2. Create new app from GitHub repo: `LeeMangold/OpenGRC`
3. Select "Dockerfile" as the build method
4. Set Dockerfile path: `Dockerfile`
5. Configure environment variables from [.env.docker.example](.env.docker.example)
6. Set HTTP port to `443`
7. Deploy

## Security Features

### TLS Configuration
- **Protocol**: TLS 1.3+ only (older versions disabled)
- **Ciphers**: Strong modern ciphers only
  - `TLS_AES_256_GCM_SHA384`
  - `TLS_AES_128_GCM_SHA256`
  - `TLS_CHACHA20_POLY1305_SHA256`
- **HSTS**: Enabled with 2-year max-age
- **SSL Stapling**: Enabled for performance

### Security Headers
- `Strict-Transport-Security`: Force HTTPS
- `X-Frame-Options`: Prevent clickjacking
- `X-Content-Type-Options`: Prevent MIME sniffing
- `Referrer-Policy`: Privacy protection

### Database Security
- MySQL SSL required (`DB_SSL_MODE=require`)
- Managed database with automatic backups
- VPC isolation

## Monitoring & Logging

### Health Checks
- Endpoint: `https://your-domain/`
- Initial delay: 60 seconds
- Interval: 30 seconds
- Timeout: 5 seconds

### Alerts
- CPU utilization > 75% (5-minute window)
- Memory utilization > 75% (5-minute window)
- Deployment failures
- Domain failures

### Logging
- Application logs sent to OpenSearch cluster
- Cluster: `og-search-1`
- Index: `logs`
- Apache access and error logs available

## Troubleshooting

### Container Fails to Start

**Check environment variables:**
```bash
doctl apps logs YOUR_APP_ID --type run
```

Look for: `ERROR: Missing required environment variables`

**Solution:** Ensure all required variables are set in App Platform

### Database Connection Fails

**Check SSL mode:**
- Verify `DB_SSL_MODE=require` is set
- Verify database allows connections from App Platform VPC

### Deployment Command Fails

**Check logs for specific error:**
```bash
doctl apps logs YOUR_APP_ID --type run --follow
```

Common issues:
- Database credentials incorrect
- Admin password too weak (< 8 chars)
- DigitalOcean Spaces credentials invalid

### Site Loads but Shows Errors

**Clear cache:**
Set environment variable temporarily:
```bash
REBUILD_CACHE=true
```

Then restart the app.

## Migrating from Buildpack to Docker

### Step 1: Backup
1. Backup your MySQL database
2. Backup DigitalOcean Spaces content
3. Note your current `APP_KEY` (critical!)

### Step 2: Prepare App Spec
1. Copy [app-spec.docker.yaml](app-spec.docker.yaml)
2. Use **same database** credentials
3. Use **same `APP_KEY`** (do not generate new one!)
4. Use same Spaces and SMTP credentials
5. Update domain name

### Step 3: Deploy New App
1. Create new App Platform app (don't delete old one yet)
2. Deploy with Docker configuration
3. Test thoroughly

### Step 4: Switch Traffic
1. Update DNS to point to new app
2. Monitor logs and errors
3. Once stable, delete old buildpack app

## Advanced Configuration

### Custom SSL Certificates

If you want to use your own SSL certificate instead of the self-signed one:

```bash
SSL_CERT="-----BEGIN CERTIFICATE-----
Your certificate here
-----END CERTIFICATE-----"

SSL_KEY="-----BEGIN PRIVATE KEY-----
Your private key here
-----END PRIVATE KEY-----"
```

**Note:** DigitalOcean App Platform typically handles SSL termination automatically. Custom certs are only needed for special cases.

### Force Re-initialization

To force the container to re-run first-time setup:

1. Delete the flag file by exec'ing into container:
   ```bash
   rm /var/www/html/storage/.container_initialized
   ```
2. Restart the app

**Warning:** This will re-run migrations. Use with caution.

### Storage Configuration Lock

Set `STORAGE_LOCK=true` to prevent users from changing storage settings via the UI. This is recommended for production to prevent accidental misconfiguration.

## Performance Optimization

### Resource Allocation
- Current: 1 vCPU, 1GB RAM
- For higher traffic: Scale to `apps-s-2vcpu-2gb-fixed` or higher
- Enable auto-scaling for variable load

### Caching
The deployment automatically configures:
- Config cache
- Route cache
- View cache
- OpCache (PHP)

### Database Connection Pooling
Consider upgrading to DigitalOcean Managed Database with connection pooler for better performance under load.

## Support

For issues specific to:
- **OpenGRC application**: https://github.com/LeeMangold/OpenGRC/issues
- **Docker deployment**: Check container logs
- **DigitalOcean platform**: https://docs.digitalocean.com/products/app-platform/

## Changelog

### Version 1.0 (Docker Migration)
- Migrated from Heroku buildpack to Docker
- Upgraded to Ubuntu 24.04
- Implemented TLS 1.3+ only
- Added comprehensive environment variable validation
- Maintained compatibility with existing `opengrc:deploy` command
- Improved security headers and SSL configuration
