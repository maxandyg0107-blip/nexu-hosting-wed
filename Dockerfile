FROM php:8.2-apache

# Instalar librerías de PostgreSQL y extensiones PHP (PDO MySQL y PDO PgSQL)
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Copiar archivos
COPY . /var/www/html/

EXPOSE 80