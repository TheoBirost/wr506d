# On utilise PHP 8.3 avec Apache (plus simple et compatible avec votre projet)
FROM php:8.3-apache

# Installation des dépendances système et des extensions PHP
# On utilise l'image par défaut (Debian) qui est plus rapide à construire que Alpine
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install \
    intl \
    pdo_mysql \
    zip \
    opcache

# Activation du module rewrite d'Apache (indispensable pour Symfony)
RUN a2enmod rewrite

# Configuration du DocumentRoot vers le dossier public/
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définition du dossier de travail
WORKDIR /var/www/html

# Copie des fichiers du projet
COPY . .

# Installation des dépendances via Composer
# On autorise le superuser car on est dans un conteneur Docker isolé
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader

# Permissions pour le cache et les logs
RUN chown -R www-data:www-data var && chmod -R 777 var

# Exposition du port
EXPOSE 80
