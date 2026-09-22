FROM php:8.4-fpm-alpine

# Start as root
USER root

# Install runtime dependencies for WordPress
RUN apk add --no-cache \
        curl \
        curl-dev \
        imagemagick \
        libzip \
        libpng \
        libjpeg-turbo \
        freetype \
        libwebp \
        libxml2 \
        icu-libs \
        oniguruma \
        libxslt \
        msmtp \
        ca-certificates \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        imagemagick-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libwebp-dev \
        libxml2-dev \
        icu-dev \
        oniguruma-dev \
        libxslt-dev \
        openssl-dev \
    # Configure GD with image format support
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    # Install PHP extensions required for WordPress
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        exif \
        fileinfo \
        gd \
        intl \
        mbstring \
        mysqli \
        opcache \
        pdo \
        pdo_mysql \
        xml \
        xsl \
        zip \
    # Install PECL extensions (PHP 8.4)
    && pecl install redis-6.3.0 imagick-3.8.0 \
    && docker-php-ext-enable redis imagick \
    # Cleanup
    && apk del .build-deps \
    && ln -sf /usr/bin/msmtp /usr/sbin/sendmail \
    && rm -rf /tmp/* /var/cache/apk/*

# Configure user permissions for runtime
ARG PUID=1000
ENV PUID=${PUID}
ARG PGID=1000
ENV PGID=${PGID}

# Change www-data UID/GID to match host user (Alpine default is 82)
RUN deluser www-data 2>/dev/null || true \
    && delgroup www-data 2>/dev/null || true \
    && addgroup -g ${PGID} www-data \
    && adduser -D -u ${PUID} -G www-data -s /sbin/nologin www-data \
    && mkdir -p /var/www \
    && chown www-data:www-data /var/www

# Set working directory
WORKDIR /var/www

# Install su-exec for running as non-root
RUN apk add --no-cache su-exec

# Copy PHP-FPM pool configuration
COPY www.conf /usr/local/etc/php-fpm.d/www.conf

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
