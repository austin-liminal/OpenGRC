# YARA Malware Scanning for OpenGRC

This directory contains YARA malware scanning configuration for OpenGRC enterprise deployments.

## Overview

YARA is a pattern-matching tool designed to help malware researchers identify and classify malware samples. This implementation scans the OpenGRC webroot (`/var/www/html`) for malicious code patterns including:

- PHP webshells (C99, WSO, generic shells)
- Obfuscated malicious code
- Reverse shells
- File upload exploits
- Crypto miners
- Laravel-specific attack patterns
- Suspicious database operations
- File inclusion exploits

## Components

### Scripts

- **`yara-scan.sh`** - Main scanning script that runs YARA against the webroot
- **`setup-yara-cron.sh`** - Sets up nightly cron job at 11:00 PM
- **`install-yara.sh`** - Installation script for YARA and rules

### YARA Rules

All rules are stored in the `rules/` directory:

- **`webshells.yar`** - Detects common webshell patterns (C99, WSO, generic PHP shells)
- **`malicious_code.yar`** - Detects obfuscated code, reverse shells, crypto miners
- **`laravel_specific.yar`** - Laravel framework-specific attack patterns

## Installation

The YARA scanner is automatically installed during Docker build. For manual installation:

```bash
cd enterprise-deploy/yara
sudo ./install-yara.sh
sudo ./setup-yara-cron.sh
```

## Manual Scanning

To run a manual scan:

```bash
/usr/local/bin/yara-scan
```

## Scan Schedule

- **Frequency**: Daily at 11:00 PM (23:00)
- **Target**: `/var/www/html` (webroot only)
- **Exclusions**:
  - `vendor/`
  - `node_modules/`
  - `storage/framework/cache/`
  - `storage/framework/sessions/`
  - `storage/framework/views/`
  - `.git/`
  - Image files (jpg, png, gif, svg, ico)
  - Font files (woff, woff2, ttf, eot)
  - Files larger than 5MB

## Exception Filtering

YARA scans can produce false positives on legitimate code. The exceptions system allows you to filter known false positives.

**Exceptions File**: `/etc/yara/yara-exceptions.conf`

### Exception Format

```
rule_name:file_path_pattern
```

- **rule_name**: Exact YARA rule name to filter (use `*` for any rule)
- **file_path_pattern**: Regex pattern to match file paths

### Example Exceptions

```conf
# Filter specific rule on vendor files
Malicious_Laravel_Blade_Template:vendor/aws/.*

# Filter any rule on a specific path
*:vendor/monolog/.*

# Filter specific file
Suspicious_Laravel_Artisan_Command:app/Console/Commands/Deploy\.php
```

### Managing Exceptions

1. **View current exceptions**:
   ```bash
   cat /etc/yara/yara-exceptions.conf
   ```

2. **Add new exception**:
   ```bash
   echo "RuleName:path/pattern/.*" >> /etc/yara/yara-exceptions.conf
   ```

3. **Test exceptions** - Run a scan and check the "Filtered matches" count in the report

**Note**: Pre-configured exceptions filter common false positives in Laravel framework code and vendor libraries.

## Logs

- **Scan logs**: `/var/log/yara/scan.log`
- **Scan reports**: `/var/log/yara/scan-report.txt`
- **Cron logs**: `/var/log/yara/cron.log`
- **Syslog**: Tagged with `yara-scan`

Scan reports include:
- Total files scanned
- Files skipped (images/fonts)
- **Matches filtered (false positives)**
- Threats detected

## Log Levels

- **INFO**: Scan started, scan completed successfully
- **WARN**: Definition update failed, rule compilation warnings
- **CRIT**: Malware detected, threats found

## Alerts

YARA alerts are sent to syslog with the program name `yara-scan`. Configure your log aggregation to monitor for:

- `THREAT DETECTED` - Critical priority
- Pattern matches with file paths
- Match counts and severity levels

## Adding Custom Rules

To add your own YARA rules:

1. Create a `.yar` or `.yara` file in the `rules/` directory
2. Follow YARA rule syntax (see examples in existing rules)
3. Test your rules:
   ```bash
   yara /etc/yara/rules/your-rule.yar /path/to/test/file
   ```
4. Rebuild the Docker container or copy to `/etc/yara/rules/`

### Example Rule

```yara
rule My_Custom_Rule
{
    meta:
        description = "Detects custom threat pattern"
        author = "Your Name"
        severity = "high"

    strings:
        $pattern1 = "suspicious_function(" nocase
        $pattern2 = "dangerous_code" nocase

    condition:
        all of them
}
```

## YARA Rule Metadata

All rules include metadata for context:

- **description**: What the rule detects
- **author**: Rule creator
- **severity**: `low`, `medium`, `high`, or `critical`
- **date**: Creation date (optional)

## Scan Performance

- **Scanned files**: Typically 5,000-15,000 files (excluding vendor/node_modules)
- **Scan duration**: 2-5 minutes depending on codebase size
- **Resource usage**: Low CPU/memory impact
- **File size limit**: 5MB per file (configurable in script)

## False Positives

YARA rules may trigger false positives on legitimate code. To reduce false positives:

1. Review the scan report at `/var/log/yara/scan-report.txt`
2. Examine the matched strings and context
3. Adjust rules if needed by:
   - Making patterns more specific
   - Adding exception conditions
   - Increasing match thresholds

## Troubleshooting

### No rules found

```bash
# Check rules directory
ls -la /etc/yara/rules/

# Verify rules are valid
yara /etc/yara/rules /usr/local/bin/yara-scan
```

### Scan not running

```bash
# Check cron job exists
cat /etc/cron.d/yara

# Check cron logs
tail -f /var/log/yara/cron.log

# Run manually
/usr/local/bin/yara-scan
```

### Rule compilation errors

```bash
# Test individual rules
yara -w /etc/yara/rules/webshells.yar /path/to/file

# Check YARA version
yara --version
```

## Security Considerations

- YARA scans are signature-based and may not detect zero-day threats
- Rules should be updated regularly with new threat patterns
- Combine with other security measures (Trivy, FIM, etc.)
- Review and respond to alerts promptly
- Consider using additional YARA rule repositories

## Integration with OpenGRC

YARA scanning is part of the OpenGRC security stack:

1. **Trivy** - Container and dependency vulnerability scanning (3 AM daily)
2. **YARA** - Webroot malware scanning (11 PM daily)
3. **FIM** - File integrity monitoring (hourly)
4. **Fluent Bit** - Log aggregation and forwarding
5. **OpenSearch** - Centralized logging and alerting

## Additional Resources

- [YARA Documentation](https://yara.readthedocs.io/)
- [YARA Rule Writing Guide](https://yara.readthedocs.io/en/stable/writingrules.html)
- [YARA Rules Repository](https://github.com/Yara-Rules/rules)
- [VirusTotal YARA](https://github.com/virustotal/yara)

## Support

For issues or questions:

1. Check `/var/log/yara/` logs
2. Review scan reports
3. Test rules manually
4. Consult YARA documentation
5. Open an issue on the OpenGRC repository
