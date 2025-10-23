#!/bin/bash
set -e

# Générer la clé d'application si elle n'existe pas
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Exécuter les migrations
php artisan migrate --force

# Nettoyer et optimiser le cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Créer le lien symbolique pour le storage
php artisan storage:link || true

# Générer la documentation Swagger si nécessaire
php artisan l5-swagger:generate || true

# Démarrer Apache
exec "$@"
