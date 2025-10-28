# ModSecurity Web Application Firewall (WAF)

This directory contains ModSecurity WAF configuration for OpenGRC enterprise deployments.

## Overview

ModSecurity is an open-source Web Application Firewall (WAF) that provides protection against common web attacks including:

- SQL Injection (SQLi)
- Cross-Site Scripting (XSS)
- Remote File Inclusion (RFI)
- Local File Inclusion (LFI)
- Remote Code Execution (RCE)
- Session Hijacking
- CSRF attacks
- Directory Traversal
- Protocol violations
- And more...

## Components

### Configuration Files

- **`modsecurity.conf`** - Main ModSecurity configuration
- **`laravel-exclusions.conf`** - Laravel-specific rule exclusions to prevent false positives
- **CRS Setup** - Uses the default OWASP CRS `crs-setup.conf.example` from the distribution

### Apache Configuration

- **`modsecurity-enabled.conf`** - Loads ModSecurity with full protection (default)
- **`modsecurity-disabled.conf`** - Disables ModSecurity engine

### Scripts

- **`configure-waf.sh`** - Configures ModSecurity based on `WAF_ENABLED` environment variable

## Environment Variable Control

### WAF_ENABLED

Controls whether ModSecurity is active. Default: **true** (enabled)

**Enable WAF** (default):
```yaml
# app-spec.yaml
- key: WAF_ENABLED
  scope: RUN_TIME
  value: "true"
```

**Disable WAF**:
```yaml
# app-spec.yaml
- key: WAF_ENABLED
  scope: RUN_TIME
  value: "false"
```

Accepted values for enabled: `true`, `1`, `yes`
Accepted values for disabled: `false`, `0`, `no`, or any other value

## OWASP Core Rule Set (CRS)

OpenGRC uses the [OWASP ModSecurity Core Rule Set](https://github.com/coreruleset/coreruleset) for attack detection.

### Paranoia Level

**Current Setting: Level 1** (Recommended for production)

- **Level 1** - Basic protection, fewer false positives (default)
- **Level 2** - Moderate protection
- **Level 3** - High protection
- **Level 4** - Maximum protection, more false positives

To change paranoia level, edit `/etc/modsecurity/crs-setup.conf` in the running container or modify the Dockerfile:
```apache
setvar:tx.paranoia_level=2
```

### Anomaly Scoring

ModSecurity uses anomaly scoring to determine if a request is malicious.

**Current Thresholds:**
- **Inbound** (requests): 5 (lower = stricter)
- **Outbound** (responses): 4

Each rule match adds to the anomaly score. If the total score exceeds the threshold, the request is blocked.

## Laravel-Specific Exclusions

The [`laravel-exclusions.conf`](laravel-exclusions.conf) file contains rules to prevent false positives common in Laravel applications:

### Excluded Patterns

1. **CSRF Tokens** - Laravel `_token` parameters excluded from SQL injection rules
2. **API Routes** - JSON payloads in `/api/*` routes have relaxed validation
3. **Filament Admin Panel** - `/admin/*` and `/app/*` routes exclude complex JSON payloads
4. **Livewire Components** - Component state and serverMemo parameters excluded
5. **Session Cookies** - `laravel_session` and `XSRF-TOKEN` cookies excluded
6. **Rich Text Editors** - Content fields (TinyMCE, CKEditor) allow HTML
7. **File Uploads** - Legitimate uploads in admin routes
8. **Search Parameters** - Search/filter/query parameters have relaxed rules
9. **JSON Payloads** - `Content-Type: application/json` requests

### Adding Custom Exclusions

To add your own exclusions, edit [`laravel-exclusions.conf`](laravel-exclusions.conf):

```apache
# Example: Exclude specific parameter from SQL injection rules
SecRule REQUEST_FILENAME "@beginsWith /my-route" \
    "id:9002000,\
    phase:2,\
    pass,\
    nolog,\
    ctl:ruleRemoveTargetById=942100;ARGS:my_param"
```

## Logs

### Audit Log

**Location**: `/var/log/apache2/modsec_audit.log`

Contains detailed information about blocked requests including:
- Request headers and body
- Matched rules
- Anomaly scores
- Response details

### Debug Log

**Location**: `/var/log/apache2/modsec_debug.log`

Detailed ModSecurity processing information (disabled by default).

To enable, edit [`modsecurity.conf`](modsecurity.conf):
```apache
SecDebugLogLevel 3
```

### Syslog

ModSecurity events are logged to syslog with tag `modsecurity`:
- **INFO** - WAF enabled/disabled status
- **WARN** - WAF disabled via environment variable

## Monitoring & Alerts

### View Blocked Requests

```bash
# View audit log
tail -f /var/log/apache2/modsec_audit.log

# Count blocked requests today
grep $(date '+%Y-%m-%d') /var/log/apache2/modsec_audit.log | wc -l

# View specific rule triggers
grep "id \"942100\"" /var/log/apache2/modsec_audit.log
```

### Common Rule IDs

| Rule ID | Description |
|---------|-------------|
| 920xxx | Protocol Enforcement |
| 921xxx | Protocol Attack |
| 930xxx | Application Attack (LFI) |
| 931xxx | Application Attack (RFI) |
| 932xxx | Application Attack (RCE) |
| 933xxx | Application Attack (PHP Injection) |
| 941xxx | XSS Attack Detection |
| 942xxx | SQL Injection Detection |
| 943xxx | Session Fixation |

## Testing ModSecurity

### Test SQL Injection Detection

```bash
# This should be blocked by WAF (if enabled)
curl "https://your-domain.com/?id=1' OR '1'='1"
```

### Test XSS Detection

```bash
# This should be blocked by WAF (if enabled)
curl "https://your-domain.com/?search=<script>alert(1)</script>"
```

### DetectionOnly Mode (Testing)

To log attacks without blocking (useful for testing):

Edit [`crs-setup.conf`](crs-setup.conf):
```apache
# Change from On to DetectionOnly
SecRuleEngine DetectionOnly
```

## Performance Considerations

ModSecurity adds processing overhead to each request:

- **Typical overhead**: 1-5ms per request
- **Under load**: 5-15ms per request
- **Memory**: ~10-20MB per Apache worker

### Optimization Tips

1. **Lower paranoia level** - Level 1 is fastest
2. **Exclude static files** - Already configured in CRS
3. **Disable response body inspection** - If not needed
4. **Tune exclusions** - Add specific exclusions to skip unnecessary checks

## Troubleshooting

### False Positives

If legitimate requests are being blocked:

1. **Check audit log** for rule ID:
   ```bash
   tail -100 /var/log/apache2/modsec_audit.log | grep "id \""
   ```

2. **Add exclusion** to [`laravel-exclusions.conf`](laravel-exclusions.conf)

3. **Reload Apache**:
   ```bash
   apache2ctl graceful
   ```

### WAF Not Loading

```bash
# Check if ModSecurity module is loaded
apache2ctl -M | grep security

# Check configuration syntax
apache2ctl configtest

# Check which config is active
ls -la /etc/apache2/modsecurity-waf.conf

# Check environment variable
echo $WAF_ENABLED
```

### High Memory Usage

```bash
# Check ModSecurity memory usage
ps aux | grep apache2 | awk '{sum+=$6} END {print sum/1024 " MB"}'

# Reduce SecResponseBodyLimit in modsecurity.conf
SecResponseBodyLimit 131072  # Reduce from 524288
```

## Security Recommendations

1. **Keep CRS Updated** - Regularly update OWASP CRS rules
2. **Monitor Audit Logs** - Set up alerts for blocked requests
3. **Tune for Your App** - Add specific exclusions as needed
4. **Test Before Deploy** - Use DetectionOnly mode first
5. **Enable in Production** - Always run with `WAF_ENABLED=true`
6. **Review Regularly** - Check for false positives weekly

## Integration with OpenGRC Security Stack

ModSecurity complements other security layers:

1. **Trivy** - Container/dependency vulnerability scanning
2. **YARA** - Malware detection in webroot
3. **FIM** - File integrity monitoring
4. **ModSecurity** - Web application firewall (runtime protection)
5. **Fluent Bit** - Log aggregation
6. **OpenSearch** - Security event correlation

## Additional Resources

- [ModSecurity Documentation](https://github.com/SpiderLabs/ModSecurity/wiki)
- [OWASP ModSecurity CRS](https://owasp.org/www-project-modsecurity-core-rule-set/)
- [CRS Documentation](https://coreruleset.org/docs/)
- [Rule ID Reference](https://github.com/coreruleset/coreruleset/tree/v4.0/dev/rules)

## Support

For issues or questions:

1. Check audit logs: `/var/log/apache2/modsec_audit.log`
2. Review syslog: `grep modsecurity /var/log/syslog`
3. Test configuration: `apache2ctl configtest`
4. Check environment: `echo $WAF_ENABLED`
5. Open an issue on the OpenGRC repository
