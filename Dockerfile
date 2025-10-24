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

# Install Apache2, PHP and required extensions
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
    libapache2-mod-php${PHP_VERSION} \
    zip \
    unzip \
    git \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js and npm
RUN curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION} | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache modules
RUN a2enmod rewrite \
    && a2enmod headers \
    && a2enmod expires \
    && a2enmod ssl \
    && a2dismod mpm_event \
    && a2enmod mpm_prefork \
    && a2enmod php${PHP_VERSION}

# Configure Apache to listen on port 443 only
RUN echo 'Listen 443' > /etc/apache2/ports.conf

# Configure SSL with TLS 1.3+
RUN echo '# SSL Protocol Configuration - TLS 1.3+ Only\n\
SSLProtocol -all +TLSv1.3\n\
SSLCipherSuite TLS_AES_256_GCM_SHA384:TLS_AES_128_GCM_SHA256:TLS_CHACHA20_POLY1305_SHA256\n\
SSLHonorCipherOrder off\n\
SSLSessionTickets off\n\
SSLOptions +StrictRequire\n\
SSLCompression off\n\
SSLUseStapling on\n\
SSLStaplingCache "shmcb:logs/stapling-cache(150000)"\n' > /etc/apache2/conf-available/ssl-params.conf

RUN a2enconf ssl-params

# Configure Apache SSL virtual host
RUN echo '<VirtualHost *:443>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/public\n\
    \n\
    SSLEngine on\n\
    SSLCertificateFile /etc/ssl/certs/opengrc.crt\n\
    SSLCertificateKeyFile /etc/ssl/private/opengrc.key\n\
    \n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    \n\
    # Security headers\n\
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"\n\
    Header always set X-Frame-Options "SAMEORIGIN"\n\
    Header always set X-Content-Type-Options "nosniff"\n\
    Header always set Referrer-Policy "strict-origin-when-cross-origin"\n\
    \n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/default-ssl.conf

# Enable SSL site and disable default
RUN a2dissite 000-default.conf && a2ensite default-ssl.conf

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

# Install Node dependencies
RUN npm ci --only=production

# Copy application code
COPY . .

# Complete Composer installation with autoloader optimization
RUN composer dump-autoload --optimize --classmap-authoritative

# Build frontend assets
RUN npm run build

# Clean up Node modules after build
RUN rm -rf node_modules

# Create necessary directories and set permissions
RUN mkdir -p storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    database \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache database

# Generate self-signed SSL certificate (will be replaced by real certs in production)
RUN mkdir -p /etc/ssl/private \
    && openssl req -x509 -nodes -days 365 -newkey rsa:4096 \
    -keyout /etc/ssl/private/opengrc.key \
    -out /etc/ssl/certs/opengrc.crt \
    -subj "/C=US/ST=State/L=City/O=OpenGRC/CN=localhost" \
    && chmod 600 /etc/ssl/private/opengrc.key

# Copy and set permissions for entrypoint script
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Expose port 443 for HTTPS
EXPOSE 443

# Switch to www-data user for security
USER www-data

# Health check using HTTPS
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -fk https://localhost:443/ || exit 1

# Use entrypoint script to handle migrations and start Apache
ENTRYPOINT ["/entrypoint.sh"]
