# Guide: Configuration du Queue Worker pour les Emails

## Problème Résolu ✅

Les emails n'étaient pas envoyés car le **queue worker n'était pas configuré correctement** dans le conteneur Docker sur Render.

## Solution Appliquée

### 1. Correction du chemin supervisord

**Avant:**
```dockerfile
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
```

**Après:**
```dockerfile
COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
```

### 2. Configuration Supervisord

Le fichier `docker/supervisor/supervisord.conf` configure 3 processus:
- ✅ **PHP-FPM** - Traite les requêtes PHP
- ✅ **Nginx** - Serveur web
- ✅ **Laravel Queue Worker** - Traite les emails en queue

```ini
[program:laravel-queue-worker]
command=php /var/www/html/artisan queue:work database --verbose --sleep=3 --tries=3 --max-jobs=1000 --timeout=90
autostart=true
autorestart=true
numprocs=1
```

## Déploiement en Production

### 1. Push vers Render
```bash
git push origin production
```

### 2. Render va automatiquement:
- Rebuilder l'image Docker
- Lancer supervisord qui démarre le queue worker
- Les emails seront traités automatiquement

### 3. Vérification

Une fois déployé, testez l'envoi d'email:

```bash
curl -X POST "https://votre-app.onrender.com/api/v1/comptes" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "epargne",
    "devise": "FCFA",
    "client": {
      "titulaire": "Test User",
      "nci": "1234567890123",
      "email": "test@example.com",
      "telephone": "+221771234567",
      "adresse": "Dakar"
    }
  }'
```

L'email devrait arriver en quelques secondes !

## Test en Local

### Option 1: Avec Queue Worker (Recommandé)

Terminal 1:
```bash
php artisan serve
```

Terminal 2:
```bash
php artisan queue:work --verbose
```

### Option 2: Sans Queue (Synchrone)

Modifier `.env`:
```env
QUEUE_CONNECTION=sync
```

## Vérifier les Logs

### En production (Render):
- Dashboard Render → Logs → Rechercher "queue"
- Rechercher "Email envoyé"

### En local:
```bash
tail -f storage/logs/laravel.log | grep -i "email\|queue"
```

## Vérifier les Jobs en Échec

```bash
php artisan queue:failed
```

Pour réessayer:
```bash
php artisan queue:retry all
```

## Configuration SendGrid

Assurez-vous que ces variables sont définies dans Render:

```env
MAIL_MAILER=sendgrid
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=YOUR_SENDGRID_API_KEY
MAIL_FROM_ADDRESS=your-verified-sender@domain.com
MAIL_FROM_NAME="Faysany Banque"
```

## Troubleshooting

### Les emails ne partent toujours pas?

1. **Vérifier que le worker tourne:**
   ```bash
   # Sur Render, dans les logs, chercher:
   "Starting Supervisor (PHP-FPM + Nginx + Queue Worker)"
   ```

2. **Vérifier la queue:**
   ```bash
   php artisan tinker
   >>> DB::table('jobs')->count()  # Doit être 0 si tout est traité
   ```

3. **Vérifier les failed jobs:**
   ```bash
   php artisan queue:failed
   ```

4. **Tester SendGrid directement:**
   ```bash
   php artisan tinker
   >>> Mail::raw('Test', function($m) { $m->to('test@example.com')->subject('Test'); });
   ```

## Résumé

✅ **Avant:** Queue worker ne démarrait pas → Emails en attente indéfiniment  
✅ **Après:** Queue worker démarre automatiquement → Emails envoyés en quelques secondes

---

**Dernière mise à jour:** 1 novembre 2025  
**Commit:** Fix supervisord configuration for queue worker
