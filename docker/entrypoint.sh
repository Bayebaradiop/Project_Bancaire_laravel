#!/bin/sh
set -e

echo "Starting Laravel application..."

# Définir les permissions correctes pour storage et bootstrap/cache
echo "Setting permissions..."
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

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
