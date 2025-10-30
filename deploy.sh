# Mise à jour de la config et redémarrage de la queue
php artisan config:cache
php artisan queue:restart
echo "Starting queue worker in background..."
php artisan queue:work --verbose --tries=3 --timeout=90 --sleep=3 --max-jobs=1000 &
