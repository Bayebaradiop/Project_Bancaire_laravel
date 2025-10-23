#!/bin/sh
set -e

echo "Starting Laravel application..."

# Ensure required directories exist and are writable
echo "Preparing storage and cache directories..."
mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/storage/framework/{sessions,views,cache} /var/www/html/storage/logs || true

# Use a permissive umask so created files are group-writable
umask 0002

# Set ownership if www-data exists (common in php-fpm images)
if id -u www-data >/dev/null 2>&1; then
    echo "Setting ownership to www-data:www-data..."
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true
else
    echo "User www-data not found, skipping chown."
fi

# Ensure permissions allow php-fpm to write (ensure widest compatibility on hosted runtimes)
echo "Setting permissions..."
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true
chmod -R g+s /var/www/html/storage /var/www/html/bootstrap/cache || true
# Fallback: make world-writable so containers with different runtime users can still write
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

# Optimiser l'application
echo "Optimizing application..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Créer le lien symbolique du storage
php artisan storage:link || true

# Publier les assets Swagger
php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider" --force || true

# Générer la documentation Swagger
php artisan l5-swagger:generate || true

echo "Application ready!"

# Démarrer PHP-FPM en arrière-plan
php-fpm -D

# Démarrer Nginx en premier plan (Render attend sur port 10000)
exec nginx -g 'daemon off;'
