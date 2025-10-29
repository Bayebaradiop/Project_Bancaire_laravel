# 🚀 Guide de déploiement en production - Render

## 📋 Étapes de déploiement

### 1. Connexion SSH à Render (si disponible)

Si vous avez accès SSH, connectez-vous à votre instance Render et exécutez :

```bash
# Se connecter au conteneur
# (Render ne fournit généralement pas d'accès SSH direct)
```

### 2. Via le Dashboard Render (Méthode recommandée)

#### Option A : Shell manuel dans Render
1. Allez sur https://dashboard.render.com
2. Cliquez sur votre service "baye-bara-diop-project-bancaire-laravel"
3. Cliquez sur l'onglet **"Shell"**
4. Exécutez les commandes suivantes :

```bash
# 1. Migrations de la base de données
php artisan migrate --force

# 2. Seeders (créer admin et données de test)
php artisan db:seed --force

# 3. Cache config (optionnel mais recommandé)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Vérifier que tout fonctionne
php artisan about
```

#### Option B : Via le fichier render.yaml (Automatique)

Votre fichier `render.yaml` devrait déjà avoir une section `buildCommand` :

```yaml
services:
  - type: web
    name: baye-bara-diop-project-bancaire-laravel
    env: docker
    buildCommand: |
      composer install --no-dev --optimize-autoloader
      php artisan migrate --force
      php artisan db:seed --force
      php artisan config:cache
      php artisan route:cache
```

Si ce n'est pas le cas, les migrations s'exécutent automatiquement au démarrage via le `startCommand`.

### 3. Variables d'environnement à vérifier

Assurez-vous que ces variables sont configurées dans **Render Dashboard → Environment** :

```properties
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://baye-bara-diop-project-bancaire-laravel.onrender.com

# Database PostgreSQL Render
DB_CONNECTION=pgsql
DB_HOST=<VOTRE_DB_HOST>
DB_PORT=5432
DB_DATABASE=db_ati7
DB_USERNAME=db_ati7_user
DB_PASSWORD=<VOTRE_DB_PASSWORD>

# SendGrid Email
MAIL_MAILER=sendgrid
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=<VOTRE_SENDGRID_API_KEY>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=bayebara2000@gmail.com
MAIL_FROM_NAME=Faysany Banque
SENDGRID_API_KEY=<VOTRE_SENDGRID_API_KEY>
MAIL_DISABLE_ON_RENDER=false

# Neon Database (Archives)
NEON_DB_HOST=ep-crimson-river-afrihxt0-pooler.us-west-2.aws.neon.tech
NEON_DB_PORT=5432
NEON_DB_DATABASE=neondb
NEON_DB_USERNAME=neondb_owner
NEON_DB_PASSWORD=<VOTRE_NEON_PASSWORD>

# JWT
JWT_SECRET=<VOTRE_JWT_SECRET>
JWT_ALGO=HS256

# Twilio SMS
TWILIO_ACCOUNT_SID=<VOTRE_TWILIO_SID>
TWILIO_AUTH_TOKEN=<VOTRE_TWILIO_TOKEN>
TWILIO_PHONE_NUMBER=<VOTRE_TWILIO_PHONE>
```

### 4. Vérifier le déploiement

Une fois les migrations et seeders exécutés, testez l'API :

```bash
# Test connexion admin
curl -X POST https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@banque.sn","password":"Admin@2025"}' | jq

# Test Swagger documentation
curl https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/documentation

# Test santé de l'application
curl https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/health
```

### 5. Comptes créés par les seeders

Après l'exécution de `php artisan db:seed`, vous aurez :

**Admin :**
- Email : `admin@banque.sn`
- Password : `Admin@2025`

**Client test :**
- Email : `client@banque.sn`
- Password : `Client@2025`

### 6. Logs en cas de problème

Pour voir les logs sur Render :
1. Dashboard Render → Votre service
2. Onglet **"Logs"**
3. Filtrer par "Error" ou "Exception"

### 7. Commandes de maintenance

```bash
# Effacer le cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Réexécuter les migrations (ATTENTION: efface les données)
php artisan migrate:fresh --seed --force

# Voir l'état des migrations
php artisan migrate:status
```

## ✅ Checklist de vérification

- [ ] Migrations exécutées : `php artisan migrate --force`
- [ ] Seeders exécutés : `php artisan db:seed --force`
- [ ] Cache optimisé : `php artisan config:cache`
- [ ] Variables d'environnement configurées
- [ ] Test connexion admin réussi
- [ ] Test création compte avec email réussi
- [ ] Swagger accessible : `/api/documentation`
- [ ] SendGrid configuré et emails envoyés

## 🎯 Résultat attendu

Après ces étapes, votre application doit :
- ✅ Accepter les connexions API
- ✅ Créer des comptes avec envoi d'email automatique
- ✅ Bloquer/débloquer des comptes avec archivage Neon
- ✅ Afficher la documentation Swagger
- ✅ Valider toutes les opérations DELETE

**🚀 Votre application bancaire Faysany est en production !**
