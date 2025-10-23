# Guide de Déploiement sur Render

Ce guide vous explique comment déployer votre application Laravel sur Render.

## Prérequis

1. Un compte sur [Render.com](https://render.com)
2. Votre code poussé sur GitHub, GitLab ou Bitbucket
3. Les fichiers de configuration déjà créés dans ce projet

## Étapes de Déploiement

### 1. Préparer votre dépôt Git

Assurez-vous que tous les fichiers sont commités et poussés sur votre dépôt :

```bash
git add .
git commit -m "Add Render deployment configuration"
git push origin production
```

### 2. Créer un nouveau service Web sur Render

1. Connectez-vous à [Render Dashboard](https://dashboard.render.com)
2. Cliquez sur **"New +"** puis **"Blueprint"**
3. Connectez votre dépôt GitHub/GitLab
4. Sélectionnez le dépôt **Project_Bancaire_laravel**
5. Render détectera automatiquement le fichier `render.yaml`

### 3. Configuration de la base de données

Le fichier `render.yaml` créera automatiquement une base de données MySQL. Vous pouvez aussi :

1. Créer manuellement une base de données PostgreSQL (recommandé pour Render) :
   - Cliquez sur **"New +"** puis **"PostgreSQL"**
   - Nommez-la `project-laravel-db`
   - Choisissez la région la plus proche
   - Plan gratuit disponible

2. Si vous utilisez PostgreSQL, modifiez votre `composer.json` pour ajouter :
   ```json
   "require": {
       "php": "^8.1",
       "ext-pgsql": "*"
   }
   ```

### 4. Variables d'environnement

Les variables suivantes sont déjà configurées dans `render.yaml` :

- `APP_KEY` - Généré automatiquement
- `APP_ENV` - production
- `APP_DEBUG` - false
- `DB_*` - Connecté automatiquement à la base de données

Variables supplémentaires à ajouter manuellement sur Render :

1. Allez dans **Environment** de votre service
2. Ajoutez :
   - `APP_URL` : Votre URL Render (ex: https://votre-app.onrender.com)
   - `SANCTUM_STATEFUL_DOMAINS` : Votre domaine Render
   - `SESSION_DOMAIN` : Votre domaine Render
   - Toute autre variable spécifique à votre application

### 5. Alternative : Déploiement sans Docker (Blueprint simple)

Si vous préférez ne pas utiliser Docker, créez un fichier `render.yaml` simplifié :

```yaml
services:
  - type: web
    name: project-laravel
    env: php
    buildCommand: |
      composer install --no-dev --optimize-autoloader
      php artisan key:generate --force
      php artisan migrate --force
      php artisan config:cache
      php artisan route:cache
      php artisan view:cache
    startCommand: php artisan serve --host=0.0.0.0 --port=$PORT
    envVars:
      - key: APP_KEY
        generateValue: true
      - key: APP_ENV
        value: production
      - key: APP_DEBUG
        value: false
```

### 6. Déploiement

1. Une fois la configuration validée, cliquez sur **"Create Web Service"**
2. Render va automatiquement :
   - Construire l'image Docker
   - Créer la base de données
   - Exécuter les migrations
   - Démarrer votre application

### 7. Vérification

1. Attendez que le déploiement soit terminé (généralement 5-10 minutes)
2. Visitez l'URL fournie par Render
3. Testez votre API avec les endpoints configurés

## Configuration de la Base de Données PostgreSQL (Recommandé)

Si vous utilisez PostgreSQL au lieu de MySQL sur Render :

1. Installez le driver PostgreSQL :
```bash
composer require ext-pgsql
```

2. Modifiez votre fichier `render.yaml` :
```yaml
- key: DB_CONNECTION
  value: pgsql
```

3. Assurez-vous que vos migrations sont compatibles avec PostgreSQL

## Troubleshooting

### Erreur de clé APP_KEY

Si vous obtenez une erreur de clé d'application :
```bash
php artisan key:generate --show
```
Copiez la clé et ajoutez-la manuellement dans les variables d'environnement Render.

### Erreur de permissions

Les permissions sont gérées automatiquement dans le Dockerfile. Si vous avez des problèmes :
- Vérifiez les logs dans le dashboard Render
- Assurez-vous que les dossiers `storage` et `bootstrap/cache` sont inscriptibles

### Erreur de migration

Vérifiez que :
- La base de données est bien créée
- Les variables d'environnement DB_* sont correctement configurées
- Vos migrations sont compatibles avec le système de base de données choisi

### Logs

Accédez aux logs via :
1. Dashboard Render → Votre service → **Logs**
2. Ou utilisez la commande : `php artisan log:clear` pour nettoyer les logs

## Optimisations Production

Une fois déployé, considérez ces optimisations :

1. **CDN** : Utilisez un CDN pour les assets statiques
2. **Cache** : Configurez Redis pour le cache (plan payant sur Render)
3. **Queue** : Configurez un worker pour les queues
4. **Monitoring** : Activez le monitoring Render

## Support

Pour plus d'informations :
- [Documentation Render](https://render.com/docs)
- [Documentation Laravel Deployment](https://laravel.com/docs/10.x/deployment)
- [Issues GitHub du projet](https://github.com/Bayebaradiop/Project_Bancaire_laravel/issues)

## Coûts

- **Plan gratuit Render** :
  - Web Service : Gratuit (avec limitations)
  - Base de données : 90 jours gratuits, puis $7/mois pour PostgreSQL
  - Le service peut se mettre en veille après 15 minutes d'inactivité

- **Plan payant** recommandé pour la production
