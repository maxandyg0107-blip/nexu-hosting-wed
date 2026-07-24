# ============================================================
# NEXU HOSTING - Dockerfile para Render.com
# PHP 8.2 + Apache2 con mod_rewrite habilitado
# ============================================================
FROM php:8.2-apache

# ── Sistema y extensiones necesarias ─────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        unzip \
        curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        gd \
        mbstring \
        fileinfo \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ── Apache: habilitar mod_rewrite + headers ───────────────────
RUN a2enmod rewrite headers

# ── PHP production config ─────────────────────────────────────
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

# ── Ajustes PHP críticos ──────────────────────────────────────
RUN echo "upload_max_filesize = 12M"       >> /usr/local/etc/php/php.ini \
 && echo "post_max_size = 14M"             >> /usr/local/etc/php/php.ini \
 && echo "memory_limit = 128M"             >> /usr/local/etc/php/php.ini \
 && echo "max_execution_time = 60"         >> /usr/local/etc/php/php.ini \
 && echo "session.cookie_httponly = 1"     >> /usr/local/etc/php/php.ini \
 && echo "session.cookie_samesite = Strict" >> /usr/local/etc/php/php.ini \
 && echo "expose_php = Off"                >> /usr/local/etc/php/php.ini \
 && echo "display_errors = Off"            >> /usr/local/etc/php/php.ini \
 && echo "log_errors = On"                 >> /usr/local/etc/php/php.ini

# ── Configurar Apache para que el .htaccess funcione ─────────
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# ── Copiar el código fuente al document root ──────────────────
COPY . /var/www/html/

# ── Crear directorios requeridos con permisos correctos ───────
RUN mkdir -p /var/www/html/uploads/vouchers \
    && mkdir -p /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/logs \
    && chmod -R 755 /var/www/html/uploads \
    && chmod -R 755 /var/www/html/logs

# ── Render usa el puerto 10000 por defecto ────────────────────
EXPOSE 80

CMD ["apache2-foreground"]
