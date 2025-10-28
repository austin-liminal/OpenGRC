#!/bin/bash
# Configure ModSecurity WAF based on WAF_ENABLED environment variable
# Default: enabled (WAF_ENABLED=true)

set -e

WAF_ENABLED=${WAF_ENABLED:-true}

echo "Configuring ModSecurity WAF..."
echo "WAF_ENABLED: $WAF_ENABLED"

if [ "$WAF_ENABLED" = "true" ] || [ "$WAF_ENABLED" = "1" ] || [ "$WAF_ENABLED" = "yes" ]; then
    echo "✓ ModSecurity WAF is ENABLED"

    # Create symlink to enabled configuration
    ln -sf /etc/apache2/modsecurity-enabled.conf /etc/apache2/modsecurity-waf.conf

    # Ensure ModSecurity module is loaded
    a2enmod security2 || true

    # Create ModSecurity temporary directories
    mkdir -p /tmp/modsecurity/upload
    chmod 755 /tmp/modsecurity
    chmod 755 /tmp/modsecurity/upload
    chown -R www-data:www-data /tmp/modsecurity

    # Create log directory
    mkdir -p /var/log/apache2
    touch /var/log/apache2/modsec_audit.log
    touch /var/log/apache2/modsec_debug.log
    chown www-data:www-data /var/log/apache2/modsec_*.log
    chmod 644 /var/log/apache2/modsec_*.log

    logger -t modsecurity -p local6.info "ModSecurity WAF enabled"
else
    echo "⚠️  ModSecurity WAF is DISABLED"

    # Create symlink to disabled configuration
    ln -sf /etc/apache2/modsecurity-disabled.conf /etc/apache2/modsecurity-waf.conf

    logger -t modsecurity -p local6.warn "ModSecurity WAF disabled via environment variable"
fi

echo "ModSecurity configuration complete"
