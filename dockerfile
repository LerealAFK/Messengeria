# Utilise une image PHP officielle avec Apache
FROM php:8.2-apache

# Copie les fichiers de votre projet dans le répertoire du serveur
COPY . /var/www/html/

# Définir le répertoire de travail
WORKDIR /var/www/html/

# Donne les permissions nécessaires au serveur
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Installe les extensions PHP nécessaires (si vous utilisez PDO pour MySQL)
RUN docker-php-ext-install pdo pdo_mysql

# Expose le port 80 pour le serveur web
EXPOSE 80

# Commande de démarrage par défaut
CMD ["apache2-foreground"]
