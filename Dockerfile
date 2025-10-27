FROM ubuntu:24.04

# Prevent interactive prompts during package installation
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

# Set versions
ENV PHP_VERSION=8.3
ENV NODE_VERSION=20.x

# Install repository management tools and add custom repositories
# Step 1: Update base Ubuntu repos and install tools needed to add repos
# Step 2: Add PHP and Node.js repos (both scripts do their own apt-get update)
# Step 3: Clean up to minimize layer size
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        software-properties-common \
        curl \
        ca-certificates \
        gnupg \
    && add-apt-repository ppa:ondrej/php \
    && curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION} | bash - \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install all application packages (repos already configured above)
RUN apt-get update && apt-get install -y \
    # Apache2
    apache2 \
    # PHP and extensions
    php${PHP_VERSION} \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-common \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-sqlite3 \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-dom \
    # Node.js (from NodeSource repository)
    nodejs \
    # System utilities
    zip \
    cron \
    wget \
    unzip \
    git \
    vim \
    openssl \
    sudo \
    rsyslog \
    net-tools \
    jq \
    # Install Fluent Bit
    && curl https://raw.githubusercontent.com/fluent/fluent-bit/master/install.sh | sh \
    # Install Trivy vulnerability scanner
    && curl -sfL https://raw.githubusercontent.com/aquasecurity/trivy/main/contrib/install.sh | sh -s -- -b /usr/local/bin \
    # Cleanup
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure PHP-FPM pool for performance (optimized for 1GB container)
RUN sed -i 's/pm = dynamic/pm = ondemand/' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf \
    && sed -i 's/pm.max_children = .*/pm.max_children = 20/' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf \
    && sed -i 's/;pm.process_idle_timeout = .*/pm.process_idle_timeout = 10s/' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf \
    && sed -i 's/;pm.max_requests = .*/pm.max_requests = 500/' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf \
    && sed -i 's/memory_limit = .*/memory_limit = 256M/' /etc/php/${PHP_VERSION}/fpm/php.ini \
    && sed -i 's/upload_max_filesize = .*/upload_max_filesize = 20M/' /etc/php/${PHP_VERSION}/fpm/php.ini \
    && sed -i 's/post_max_size = .*/post_max_size = 20M/' /etc/php/${PHP_VERSION}/fpm/php.ini \
    && sed -i 's/max_execution_time = .*/max_execution_time = 60/' /etc/php/${PHP_VERSION}/fpm/php.ini

# Configure PHP-FPM to log to file (for rsyslog forwarding)
RUN sed -i 's|;error_log = log/php8.3-fpm.log|error_log = /var/log/php8.3-fpm.log|' /etc/php/${PHP_VERSION}/fpm/php-fpm.conf \
    && sed -i 's|;catch_workers_output = yes|catch_workers_output = yes|' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf

# Enable Apache modules for PHP-FPM
RUN a2enmod rewrite \
    && a2enmod headers \
    && a2enmod expires \
    && a2enmod ssl \
    && a2enmod proxy \
    && a2enmod proxy_fcgi \
    && a2enmod setenvif \
    && a2enmod remoteip \
    && a2dismod mpm_prefork \
    && a2enmod mpm_event \
    && a2enconf php${PHP_VERSION}-fpm

# Configure RemoteIP to trust DigitalOcean load balancer
RUN echo '# Trust DigitalOcean load balancer for X-Forwarded-For\n\
RemoteIPHeader X-Forwarded-For\n\
RemoteIPTrustedProxy 10.0.0.0/8\n\
RemoteIPTrustedProxy 172.16.0.0/12\n\
RemoteIPTrustedProxy 192.168.0.0/16\n\
RemoteIPTrustedProxy 100.64.0.0/10\n\
RemoteIPInternalProxy 10.0.0.0/8\n\
RemoteIPInternalProxy 172.16.0.0/12\n\
RemoteIPInternalProxy 192.168.0.0/16\n\
RemoteIPInternalProxy 100.64.0.0/10' > /etc/apache2/conf-available/remoteip.conf

RUN a2enconf remoteip

# Configure Apache to listen on port 80
RUN echo 'Listen 80' > /etc/apache2/ports.conf

# Overwrite the default Apache site with OpenGRC configuration
COPY enterprise-deploy/apache/opengrc.conf /etc/apache2/sites-available/000-default.conf


# Set ServerName to suppress warnings
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies (without dev dependencies for production)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy package files
COPY package*.json ./

# Install Node dependencies (including dev dependencies needed for build)
RUN npm ci

# Copy application code
COPY . .

# Complete Composer installation with autoloader optimization
RUN composer dump-autoload --optimize --classmap-authoritative

# Build frontend assets
RUN npm run build

# Clean up Node modules after build
RUN rm -rf node_modules

# Create necessary directories and set permissions
# Note: SSL is handled by DigitalOcean load balancer, no certificates needed in container
RUN mkdir -p storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    database \
    && touch storage/logs/laravel.log \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache database \
    && chmod 664 storage/logs/laravel.log

# Copy enterprise deployment scripts
COPY enterprise-deploy/ /var/www/html/enterprise-deploy/
RUN chmod +x /var/www/html/enterprise-deploy/*.sh

# Copy Fluent Bit configuration files (must be after enterprise-deploy is copied)
RUN mkdir -p /etc/fluent-bit
COPY enterprise-deploy/fluent-bit/*.conf /etc/fluent-bit/
COPY enterprise-deploy/fluent-bit/*.lua /etc/fluent-bit/

# Copy FIM (File Integrity Monitoring) scripts
RUN mkdir -p /var/lib/fim /var/log/fim
COPY enterprise-deploy/fim/fim-init.sh /usr/local/bin/fim-init
COPY enterprise-deploy/fim/fim-check.sh /usr/local/bin/fim-check
COPY enterprise-deploy/fim/setup-fim-cron.sh /var/www/html/enterprise-deploy/setup-fim-cron.sh
RUN chmod 0755 /usr/local/bin/fim-init /usr/local/bin/fim-check \
    && chmod 0755 /var/www/html/enterprise-deploy/setup-fim-cron.sh \
    && chmod 0700 /var/lib/fim \
    && chmod 0755 /var/log/fim

# Configure rsyslog for FIM alerts
RUN echo '# FIM alerts\n\
:programname, isequal, "fim-init" /var/log/fim/fim.log\n\
:programname, isequal, "fim-check" /var/log/fim/fim.log\n\
\n\
# Stop processing if it'"'"'s a FIM message to prevent duplicates\n\
:programname, isequal, "fim-init" stop\n\
:programname, isequal, "fim-check" stop' > /etc/rsyslog.d/30-fim.conf

# Set up cron jobs (Trivy vulnerability scans and FIM checks)
RUN /var/www/html/enterprise-deploy/setup-cron.sh \
    && /var/www/html/enterprise-deploy/setup-fim-cron.sh

# Expose port 80 (DigitalOcean load balancer forwards to this port)
EXPOSE 80

# Health check using HTTP on port 80
HEALTHCHECK --interval=30s --timeout=3s --start-period=30s --retries=5 \
    CMD curl -f http://localhost/ || exit 1

# Use entrypoint script to handle migrations and start Apache
ENTRYPOINT ["/var/www/html/enterprise-deploy/entrypoint.sh"]
