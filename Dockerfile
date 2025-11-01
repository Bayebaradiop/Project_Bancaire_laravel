FROM php:8.3-fpm-alpine

# Installer les dépendances système
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    netcat-openbsd \
    zip \
    unzip \
    nginx \
    supervisor \
    build-base

# Installer les extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Créer le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers du projet
COPY . .

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Créer les répertoires requis et configurer les permissions
RUN mkdir -p storage/framework/{sessions,views,cache} \
    storage/logs \
    bootstrap/cache


# Copier les configurations
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Correction permissions avant démarrage
RUN mkdir -p storage/framework/views \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 0777 storage bootstrap/cache

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
