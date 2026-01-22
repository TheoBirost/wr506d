FROM php:8.3-apache

# Installation des dépendances système et extensions PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    intl \
    zip \
    gd \
    opcache \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration Apache
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        FallbackResource /index.php\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configuration PHP pour production
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'realpath_cache_size=4096K'; \
    echo 'realpath_cache_ttl=600'; \
    echo 'memory_limit=512M'; \
    echo 'post_max_size=50M'; \
    echo 'upload_max_filesize=50M'; \
} > /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html

# Copier les fichiers composer
COPY composer.json composer.lock symfony.lock ./

# Installer les dépendances
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copier le code source
COPY . .

# Variables d'environnement par défaut
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Finaliser l'installation
RUN composer dump-autoload --optimize --classmap-authoritative

# Créer les dossiers et permissions
RUN mkdir -p var/cache/prod var/log var/sessions \
    public/uploads public/bundles public/assets \
    public/media/director public/media/movies public/media/other public/media/profiles \
    config/jwt && \
    chown -R www-data:www-data var/ public/ config/jwt && \
    chmod -R 777 var/cache var/log var/sessions && \
    chmod -R 775 public/uploads public/bundles public/assets public/media

# Script d'entrypoint
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
echo "🚀 Starting Symfony application..."\n\
\n\
# Attendre la base de données\n\
echo "⏳ Waiting for database..."\n\
for i in {1..30}; do\n\
    if php bin/console dbal:run-sql "SELECT 1" > /dev/null 2>&1; then\n\
        echo "✅ Database is ready!"\n\
        break\n\
    fi\n\
    echo "⏳ Attempt $i/30: Database not ready yet..."\n\
    sleep 2\n\
done\n\
\n\
# Générer les clés JWT si elles n'\''existent pas\n\
if [ ! -f config/jwt/private.pem ]; then\n\
    echo "🔐 Generating JWT keys..."\n\
    php bin/console lexik:jwt:generate-keypair --skip-if-exists || true\n\
fi\n\
\n\
# Migrations en production\n\
if [ "$APP_ENV" = "prod" ]; then\n\
    echo "📊 Running migrations..."\n\
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true\n\
fi\n\
\n\
# Vider et réchauffer le cache\n\
echo "🔥 Clearing cache..."\n\
php bin/console cache:clear --no-warmup\n\
echo "♨️  Warming up cache..."\n\
php bin/console cache:warmup\n\
\n\
# Installer les assets\n\
echo "📦 Installing assets..."\n\
php bin/console assets:install public --symlink --relative || php bin/console assets:install public\n\
\n\
# Permissions finales\n\
chown -R www-data:www-data var/ public/\n\
\n\
echo "✅ Application ready!"\n\
\n\
# Démarrer Apache\n\
exec apache2-foreground\n\
' > /usr/local/bin/docker-entrypoint.sh && \
    chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
