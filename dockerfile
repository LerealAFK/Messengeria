# Utilisation d'une image officielle PHP avec Apache
FROM php:8.1-apache

# Installation des extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_mysql zip

# Activation du module Apache rewrite
RUN a2enmod rewrite

# Copie des fichiers du projet
COPY . /var/www/html/

# Correction des permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Port exposé
EXPOSE 80

# Démarrage d'Apache
CMD ["apache2-foreground"]
