# Utilisation d'une image PHP de base
FROM php:8.1-apache

# Installation des extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_mysql zip

# Copie des fichiers du projet
COPY . /var/www/html/

# Ajustement des permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Activation du module Apache rewrite
RUN a2enmod rewrite

# Configuration d'Apache
COPY .htaccess /var/www/html/.htaccess

# Port exposé
EXPOSE 80

# Commande de démarrage
CMD ["apache2-foreground"]
