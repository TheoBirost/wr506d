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

# Copier et activer la configuration Apache personnalisée
COPY docker-apache.conf /etc/apache2/sites-available/000-default.conf

# Supprimer le warning ServerName
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configuration PHP pour production
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'realpath_cache_size=4096K'; \
    echo 'realpath_cache_ttl=600'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers composer en premier (pour cache)
COPY composer.json composer.lock symfony.lock ./

# Installer les dépendances (sans dev)
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copier tout le code source
COPY . .

# Copier le script d'entrypoint
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Variables d'environnement pour la production
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Finaliser l'installation Composer avec les scripts
RUN composer dump-autoload --optimize --classmap-authoritative

# Créer les dossiers nécessaires et définir les permissions
RUN mkdir -p var/cache/prod var/log var/sessions public/uploads public/bundles public/assets public/media public/media/director public/media/movies public/media/other public/media/profiles && \
    chown -R www-data:www-data var/ public/uploads public/bundles public/assets public/media public/media/director public/media/movies public/media/other public/media/profiles && \
    chmod -R 777 var/cache var/log var/sessions && \
    chmod -R 775 public/uploads public/bundles public/assets

# Exposer le port 80
EXPOSE 80

# Utiliser le script d'entrypoint
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
