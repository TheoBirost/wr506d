FROM php:8.2-fpm-alpine

# Install dependencies and Nginx
RUN apk add --no-cache \
    acl \
    fcgi \
    file \
    gettext \
    git \
    gnu-libiconv \
    icu-dev \
    libzip-dev \
    zip \
    nginx \
    && docker-php-ext-install \
    intl \
    pdo_mysql \
    zip \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application files
COPY . .

# Configure Nginx
COPY nginx.conf /etc/nginx/http.d/default.conf

# Run composer scripts (cache:clear, etc.)
RUN composer run-script post-install-cmd

# Set permissions
RUN chown -R www-data:www-data var && \
    chmod +x start.sh

EXPOSE 80

CMD ["./start.sh"]
