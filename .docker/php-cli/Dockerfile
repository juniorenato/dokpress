FROM node:24-alpine AS node

FROM php:8.4-cli-alpine

# Start as root
USER root

# Install development and deployment tools + WordPress dependencies
RUN apk add --no-cache \
    bash \
    git \
    curl \
    curl-dev \
    unzip \
    zip \
    su-exec \
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

# Create user with UID 1000 if it doesn't exist
RUN if ! getent passwd 1000 > /dev/null 2>&1; then \
        addgroup -g 1000 user && \
        adduser -D -u 1000 -G user -h /home/user -s /bin/bash user; \
    fi

# Create directories and set permissions for UID 1000
RUN mkdir -p /var/www /home/user/.composer

# Node 24 LTS (copied from node:24-alpine) and Yarn
RUN apk add --no-cache libstdc++
COPY --from=node /usr/local/bin/node /usr/local/bin/
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
    && npm install --unsafe-perm -g yarn

# Install Composer
ENV COMPOSER_VERSION=2.10.3
ENV COMPOSER_HOME=/home/user/.composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --version=$COMPOSER_VERSION

# Install WP-CLI for WordPress management
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp \
    && wp --info --allow-root

# Copy entrypoint script
COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

RUN git config --system --add safe.directory /var/www \
    && git config --system --add safe.directory '*' \
    && chown -R 1000:1000 /home/user /var/www

WORKDIR /var/www

USER root
ENV HOME=/home/user

ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["tail", "-f", "/dev/null"]
