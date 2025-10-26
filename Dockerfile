FROM ubuntu:24.04

# Prevent interactive prompts during package installation
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

# Set versions
ENV PHP_VERSION=8.3
ENV NODE_VERSION=20.x

# Install system dependencies and Apache2
RUN apt-get update && apt-get install -y \
    software-properties-common \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    && add-apt-repository ppa:ondrej/php \
    && apt-get update

# Install Apache2, PHP-FPM and required extensions
RUN apt-get install -y \
    apache2 \
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
    zip \
    cron \
    wget \
    unzip \
    git \
    vim \
    openssl \
    sudo \
    ca-certificates \
    rsyslog \
    && curl https://raw.githubusercontent.com/fluent/fluent-bit/master/install.sh | sh \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js and npm
RUN curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION} | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Trivy vulnerability scanner
RUN curl -sfL https://raw.githubusercontent.com/aquasecurity/trivy/main/contrib/install.sh | sh -s -- -b /usr/local/bin

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure Fluent Bit for OpenSearch log forwarding
RUN mkdir -p /etc/fluent-bit && \
    echo '[SERVICE]\n\
    Flush        5\n\
    Daemon       Off\n\
    Log_Level    info\n\
    Parsers_File parsers.conf\n\
\n\
[INPUT]\n\
    Name              tail\n\
    Path              /var/www/html/storage/logs/laravel.log\n\
    Tag               laravel\n\
    Refresh_Interval  5\n\
    Skip_Empty_Lines  On\n\
\n\
[INPUT]\n\
    Name              tail\n\
    Path              /var/log/php8.3-fpm.log\n\
    Tag               php-fpm\n\
    Refresh_Interval  5\n\
    Skip_Empty_Lines  On\n\
\n\
[INPUT]\n\
    Name              tail\n\
    Path              /var/log/apache2/access.log\n\
    Tag               apache-access\n\
    Refresh_Interval  5\n\
    Skip_Empty_Lines  On\n\
\n\
[INPUT]\n\
    Name              tail\n\
    Path              /var/log/apache2/error.log\n\
    Tag               apache-error\n\
    Refresh_Interval  5\n\
    Skip_Empty_Lines  On\n\
\n\
[INPUT]\n\
    Name              tail\n\
    Path              /var/log/syslog\n\
    Tag               syslog\n\
    Refresh_Interval  5\n\
    Skip_Empty_Lines  On\n\
\n\
[OUTPUT]\n\
    Name              opensearch\n\
    Match             *\n\
    Host              ${OPENSEARCH_HOST}\n\
    Port              ${OPENSEARCH_PORT}\n\
    HTTP_User         ${OPENSEARCH_USER}\n\
    HTTP_Passwd       ${OPENSEARCH_PASSWORD}\n\
    Index             logs\n\
    Type              _doc\n\
    tls               On\n\
    tls.verify        Off\n\
    Suppress_Type_Name On\n' > /etc/fluent-bit/fluent-bit.conf

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

# Configure Apache to listen on port 443 (HTTPS) and 8080 (HTTP health checks)
RUN echo 'Listen 443\nListen 8080' > /etc/apache2/ports.conf

# Configure HTTP virtual host on port 443 (DigitalOcean handles SSL termination)
RUN echo '<VirtualHost *:443>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/public\n\
    \n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    \n\
    # PHP-FPM Configuration\n\
    <FilesMatch \\.php$>\n\
        SetHandler "proxy:unix:/var/run/php/php8.3-fpm.sock|fcgi://localhost"\n\
    </FilesMatch>\n\
    \n\
    # Security headers (DigitalOcean load balancer handles HTTPS)\n\
    Header always set X-Frame-Options "SAMEORIGIN"\n\
    Header always set X-Content-Type-Options "nosniff"\n\
    Header always set Referrer-Policy "strict-origin-when-cross-origin"\n\
    \n\
    # Custom log format using X-Forwarded-For as source IP when available\n\
    LogFormat "%{X-Forwarded-For}i %l %u %t \\"%r\\" %>s %b \\"%{Referer}i\\" \\"%{User-Agent}i\\"" forwarded\n\
    LogFormat "%a %l %u %u %t \\"%r\\" %>s %b \\"%{Referer}i\\" \\"%{User-Agent}i\\"" combined\n\
    SetEnvIf X-Forwarded-For "^.*\\..*" forwarded\n\
    \n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log forwarded env=forwarded\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined env=!forwarded\n\
</VirtualHost>' > /etc/apache2/sites-available/default-443.conf

# Configure HTTP virtual host on port 8080 for health checks
RUN echo '<VirtualHost *:8080>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/public\n\
    \n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    \n\
    # PHP-FPM Configuration\n\
    <FilesMatch \\.php$>\n\
        SetHandler "proxy:unix:/var/run/php/php8.3-fpm.sock|fcgi://localhost"\n\
    </FilesMatch>\n\
    \n\
    ErrorLog ${APACHE_LOG_DIR}/health-error.log\n\
    CustomLog ${APACHE_LOG_DIR}/health-access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/health-check.conf

# Enable sites
RUN a2dissite 000-default.conf && a2ensite default-443.conf && a2ensite health-check.conf

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

# Generate self-signed SSL certificate (will be replaced by real certs in production)
# Must be done as root before switching to www-data user
RUN mkdir -p /etc/ssl/private \
    && openssl req -x509 -nodes -days 365 -newkey rsa:4096 \
    -keyout /etc/ssl/private/opengrc.key \
    -out /etc/ssl/certs/opengrc.crt \
    -subj "/C=US/ST=FL/L=Orlando/O=OpenGRC/CN=localhost" \
    && chmod 644 /etc/ssl/certs/opengrc.crt \
    && chmod 600 /etc/ssl/private/opengrc.key

# Create necessary directories and set permissions
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

# Copy and set permissions for entrypoint script
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Expose ports: 443 for HTTPS, 8080 for HTTP health checks
EXPOSE 443 8080

# Health check using HTTP on port 8080
HEALTHCHECK --interval=30s --timeout=3s --start-period=50s --retries=5 \
    CMD curl -f http://localhost:8080/ || exit 1

# Use entrypoint script to handle migrations and start Apache
ENTRYPOINT ["/entrypoint.sh"]
