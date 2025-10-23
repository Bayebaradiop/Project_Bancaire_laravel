#!/bin/sh
set -e

echo "Starting Laravel application..."

# Attendre que la base de données soit prête (optionnel)
# if [ -n "$DB_HOST" ]; then
#     echo "Waiting for database..."
#     while ! nc -z $DB_HOST $DB_PORT; do
#         sleep 0.1
#     done
#     echo "Database is ready!"
# fi

# Générer la clé d'application si nécessaire
if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Exécuter les migrations
echo "Running migrations..."
php artisan migrate --force

# Optimiser l'application
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Créer le lien symbolique du storage
php artisan storage:link || true

# Générer la documentation Swagger (optionnel)
php artisan l5-swagger:generate || true

echo "Application ready!"

# Démarrer supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
