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

# APP_KEY doit être définie dans les variables d'environnement Render
# Ne pas générer de clé ici car il n'y a pas de fichier .env
if [ -z "$APP_KEY" ]; then
    echo "WARNING: APP_KEY is not set! Please set it in Render environment variables."
    exit 1
fi
echo "APP_KEY is configured ✓"

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

echo "Application ready!"

# Démarrer Supervisor qui va gérer PHP-FPM, Nginx ET le queue worker
echo "Starting Supervisor (PHP-FPM + Nginx + Queue Worker)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
