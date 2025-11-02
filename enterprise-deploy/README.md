# Enterprise Deployment Scripts

This directory contains deployment scripts and configurations for the OpenGRC Docker container.

## Files

### Core Scripts

- **`entrypoint.sh`** - Container startup script that runs migrations, starts services (Apache, PHP-FPM, rsyslog, Fluent Bit)
- **`setup-cron.sh`** - Sets up cron jobs during container build (called by Dockerfile)

### Security Scanning

- **`trivy.yaml`** - Trivy vulnerability scanner configuration
- **`trivy-scan.sh`** - Daily vulnerability scan script
  - **Schedule**: Runs daily at 1:00 AM via cron
  - **Scan Target**: Root filesystem (`/`)
  - **Output**: `/var/www/html/public/ops/vuln.json`
  - **Severity**: HIGH and CRITICAL vulnerabilities only
  - **Package Types**: OS packages only
  - **Log**: `/var/log/trivy-scan.log`

## Trivy Scanning

The Trivy vulnerability scanner runs automatically every day at 1 AM to check for HIGH and CRITICAL vulnerabilities in OS packages.

### Manual Scan

To run a manual scan:

```bash
docker exec <container-name> /var/www/html/enterprise-deploy/trivy-scan.sh
```

### View Results

Scan results are available at:
- **JSON Output**: `https://your-domain.com/ops/vuln.json`
- **Scan Logs**: `/var/log/trivy-scan.log` (inside container)

### Configuration

Modify `trivy.yaml` to adjust:
- Severity levels (`HIGH`, `CRITICAL`, `MEDIUM`, `LOW`)
- Package types to scan
- Directories to skip
- Output format

## Directory Structure

```
enterprise-deploy/
├── README.md              # This file
├── entrypoint.sh          # Container startup script
├── setup-cron.sh          # Cron job setup (runs during build)
├── trivy.yaml             # Trivy scanner configuration
└── trivy-scan.sh          # Daily vulnerability scan script
```

## Maintenance

### Updating Trivy Configuration

1. Edit `trivy.yaml` to change scan parameters
2. Rebuild the Docker image
3. Redeploy the container

### Changing Scan Schedule

1. Edit the cron schedule in `setup-cron.sh` (line: `0 1 * * *`)
2. Rebuild the Docker image
3. Redeploy the container

### Viewing Scan Logs

```bash
docker exec <container-name> tail -f /var/log/trivy-scan.log
```

## Security Notes

- Vulnerability scan results are publicly accessible at `/ops/vuln.json`
- Consider adding authentication if this is sensitive information
- The scan only checks OS packages, not application dependencies
- Scan excludes `/var`, `/tmp`, `/proc`, `/sys`, `/mnt`, `/dev`, `/run`
