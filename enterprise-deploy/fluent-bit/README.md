# Fluent Bit Configuration for OpenGRC

This directory contains Fluent Bit configuration files for collecting and forwarding logs to OpenSearch with ECS (Elastic Common Schema) formatting.

## Files

### `fluent-bit.conf`
Main Fluent Bit configuration file that defines:
- **Inputs**: Log sources (Laravel, PHP-FPM, Apache access/error, syslog)
- **Filters**: Metadata enrichment and ECS transformation
- **Outputs**: OpenSearch destination

### `parsers.conf`
Parser definitions for structured log parsing:
- **apache**: Parses Apache Combined Log Format into structured fields

### `ecs-transform.lua`
Lua script that transforms parsed Apache logs into ECS-compliant format.

Maps Apache log fields to ECS fields:
- `client_ip` → `source.ip`, `client.ip`
- `response_code` → `http.response.status_code`
- `http_method` → `http.request.method`
- `http_version` → `http.version`
- `url` → `url.path`, `url.original`
- `referrer` → `http.request.referrer`
- `user_agent` → `user_agent.original`
- `bytes` → `http.response.body.bytes`

## Log Sources

| Source | Path | Tag | Parser | Description |
|--------|------|-----|--------|-------------|
| Laravel | `/var/www/html/storage/logs/laravel.log` | `laravel` | None | Laravel application logs |
| PHP-FPM | `/var/log/php8.3-fpm.log` | `php-fpm` | None | PHP-FPM process logs |
| Apache Access | `/var/log/apache2/access.log` | `apache-access` | `apache` | Apache HTTP access logs |
| Apache Error | `/var/log/apache2/error.log` | `apache-error` | None | Apache HTTP error logs |
| Syslog | `/var/log/syslog` | `syslog` | None | System logs |

## ECS Metadata

All logs are enriched with the following ECS metadata:
- `ecs_version`: `8.11.0`
- `service_name`: `opengrc`
- `service_type`: `application`

Each log source also gets specific metadata:
- `dataset`: Identifies the log type (e.g., `apache.access`, `laravel.log`)
- `logger`: Identifies the logging component (e.g., `apache`, `laravel`)
- `category`: Log category (e.g., `web`)
- `type`: Log type (e.g., `access`, `error`)

## Environment Variables

The OpenSearch output requires the following environment variables:
- `OPENSEARCH_HOST`: OpenSearch server hostname
- `OPENSEARCH_PORT`: OpenSearch server port
- `OPENSEARCH_USER`: Authentication username
- `OPENSEARCH_PASSWORD`: Authentication password

## Testing Configuration

To test the parser locally:
```bash
# Test Apache parser with sample log line
echo '192.168.1.1 - - [26/Oct/2025:16:54:06 +0000] "GET / HTTP/1.1" 200 11051 "-" "kube-probe/1.33"' | \
  fluent-bit -c fluent-bit.conf -i stdin -o stdout
```

## References

- [Fluent Bit Documentation](https://docs.fluentbit.io/)
- [ECS Field Reference](https://www.elastic.co/guide/en/ecs/current/ecs-field-reference.html)
- [Apache Log Format](https://httpd.apache.org/docs/current/mod/mod_log_config.html)