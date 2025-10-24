#!/bin/sh
set -e

echo "Starting Laravel application..."

# Ensure required directories exist and are writable
echo "Preparing storage and cache directories..."
mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/storage/framework/{sessions,views,cache} /var/www/html/storage/logs || true

# Use a permissive umask so created files are group-writable
umask 0002

# Set ownership to the current user (works in any environment)
echo "Setting ownership to current user ($(id -u):$(id -g))..."
chown -R $(id -u):$(id -g) /var/www/html/storage /var/www/html/bootstrap/cache || true

# Ensure permissions allow writing (world-writable for compatibility)
echo "Setting permissions..."
chmod -R 0777 /var/www/html/storage /var/www/html/bootstrap/cache || true

# Attendre que la base de données soit prête (si configurée)
if [ -n "$DB_HOST" ] && [ -n "$DB_PORT" ]; then
    echo "Waiting for database at $DB_HOST:$DB_PORT ..."
    for i in $(seq 1 60); do
        if nc -z "$DB_HOST" "$DB_PORT"; then
            echo "Database is ready!"
            break
        fi
        sleep 1
        if [ "$i" -eq 60 ]; then
            echo "Database not reachable after 60s, continuing without blocking startup."
        fi
    done
fi

# Générer la clé d'application si nécessaire
if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Exécuter les migrations (non bloquant si échec)
echo "Running migrations..."
if ! php artisan migrate --force; then
    echo "Migrations failed or database unavailable. Continuing startup."
fi

# Optimiser l'application avec les bonnes variables d'env
echo "Caching configs with production environment..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Créer le lien symbolique du storage
php artisan storage:link || true

# Publier les assets Swagger
echo "Publishing Swagger assets..."
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider" --force || true

# Générer la documentation Swagger
echo "Generating Swagger documentation..."
php artisan l5-swagger:generate || true

echo "Application ready!"

# Démarrer PHP-FPM en arrière-plan
php-fpm -D

# Démarrer Nginx en premier plan (Render attend sur port 10000)
exec nginx -g 'daemon off;'
