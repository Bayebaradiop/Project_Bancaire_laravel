# 🎯 Guide de Configuration Render pour votre Projet

## ✅ Configuration Actuelle

Votre projet est configuré avec :
- **Base de données** : PostgreSQL ✅
- **PHP** : 8.1+
- **Laravel** : 10.x
- **Environnement local** : PostgreSQL (port 5433)

## 📋 Variables d'environnement configurées automatiquement

Les variables suivantes sont **automatiquement injectées** par Render via `render.yaml` :

### ✅ Configurées automatiquement (NE PAS ajouter manuellement)
```bash
DB_CONNECTION=pgsql
DB_HOST=<généré-par-render>
DB_PORT=<généré-par-render>
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=<généré-par-render>
APP_KEY=<généré-par-render>
```

### 📝 À configurer manuellement sur Render Dashboard

Après le déploiement, ajoutez ces variables dans **Render Dashboard → Environment** :

#### 1. URL de l'application
```bash
APP_URL=https://votre-app.onrender.com
```
**Remplacez** `votre-app` par le nom réel de votre service Render.

#### 2. Swagger Documentation
```bash
L5_SWAGGER_CONST_HOST=https://votre-app.onrender.com
```

#### 3. Sanctum (Authentification API)
Si vous utilisez Sanctum pour l'authentification :
```bash
SANCTUM_STATEFUL_DOMAINS=votre-app.onrender.com
SESSION_DOMAIN=.onrender.com
```

#### 4. Email (Optionnel)
Si vous devez envoyer des emails, configurez un service comme Mailtrap, SendGrid, etc. :
```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=votre_username
MAIL_PASSWORD=votre_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votre-app.com
MAIL_FROM_NAME="Project Bancaire Laravel"
```

## 🚀 Étapes de Déploiement

### Étape 1 : Préparer le projet
```bash
# Vérifier que tout est bon
./prepare-deploy.sh

# Ou manuellement :
git status
```

### Étape 2 : Committer et pousser
```bash
git add .
git commit -m "Configure PostgreSQL for Render deployment"
git push origin dev/v1.0.0
```

### Étape 3 : Créer le service sur Render

1. **Aller sur** [https://dashboard.render.com](https://dashboard.render.com)
2. **Cliquer sur** "New +" → "Blueprint"
3. **Connecter** votre dépôt : `Bayebaradiop/Project_Bancaire_laravel`
4. **Sélectionner** la branche : `dev/v1.0.0`
5. **Render détecte** automatiquement `render.yaml`
6. **Cliquer** sur "Apply"

### Étape 4 : Attendre le déploiement
- ⏱️ Durée : 5-10 minutes
- 📊 Surveillez les logs dans le Dashboard
- ✅ Une fois terminé : `https://votre-app.onrender.com`

### Étape 5 : Configurer les variables supplémentaires
1. Aller dans **votre service → Environment**
2. Ajouter les variables listées ci-dessus (APP_URL, etc.)
3. Sauvegarder → Render redéploie automatiquement

## 🔍 Vérification du déploiement

### Test 1 : Vérifier que l'application fonctionne
```bash
curl https://votre-app.onrender.com/api
```

### Test 2 : Vérifier la base de données
Le script `docker-entrypoint.sh` exécute automatiquement :
- ✅ Les migrations
- ✅ Le cache de configuration
- ✅ La génération Swagger

### Test 3 : Consulter les logs
Dans le Dashboard Render → Logs, vous devriez voir :
```
Running migrations...
Caching configuration...
Generating Swagger documentation...
Apache started successfully
```

## 📊 Structure de la base de données sur Render

Render créera automatiquement :
- **Nom de la base** : `laravel`
- **Utilisateur** : `laravel`
- **Type** : PostgreSQL 15+
- **Plan gratuit** : 90 jours gratuits, puis $7/mois

## 🐛 Troubleshooting

### Problème : "App key not set"
**Solution** : Dans Render Dashboard → Environment, ajoutez :
```bash
APP_KEY=base64:/0NHaUg5A+twwjwa45GPqJM7IlYU5S+dfiQDcGGw2xk=
```
Ou laissez Render générer automatiquement (déjà configuré dans render.yaml).

### Problème : "Database connection failed"
**Solution** : Vérifiez dans Render Dashboard :
1. Que la base de données est bien créée
2. Que le service web est connecté à la base
3. Dans les logs, vérifiez les variables DB_*

### Problème : Migrations ne s'exécutent pas
**Solution** : 
```bash
# Dans Render Shell (Dashboard → Shell)
php artisan migrate --force
php artisan config:clear
```

### Problème : Swagger ne se génère pas
**Solution** :
```bash
# Dans Render Shell
php artisan l5-swagger:generate
```

## 🎨 URLs importantes après déploiement

- **API** : `https://votre-app.onrender.com/api`
- **Swagger** : `https://votre-app.onrender.com/api/documentation`
- **Health Check** : `https://votre-app.onrender.com/health` (à créer)

## 💰 Coûts estimés

| Service | Plan | Coût |
|---------|------|------|
| Web Service | Starter | Gratuit (avec limitations) |
| PostgreSQL | Free | 90 jours gratuits |
| PostgreSQL | Standard | $7/mois après période gratuite |

### Limitations du plan gratuit :
- ⚠️ Service se met en veille après 15 min d'inactivité
- ⚠️ Première requête après veille : ~30 secondes
- ⚠️ 750 heures/mois (suffisant pour un site 24/7)

## 🔄 Mises à jour automatiques

À chaque push sur `dev/v1.0.0`, Render redéploie automatiquement ! 🎉

```bash
# Faire des modifications
git add .
git commit -m "Update feature"
git push origin dev/v1.0.0
# Render redéploie automatiquement en 5-10 minutes
```

## 📞 Support

- **Documentation Render** : https://render.com/docs
- **Dashboard** : https://dashboard.render.com
- **Logs en temps réel** : Dashboard → Votre service → Logs

## ✅ Checklist finale

- [ ] `render.yaml` configuré avec PostgreSQL
- [ ] `Dockerfile` optimisé pour PostgreSQL
- [ ] Code poussé sur GitHub
- [ ] Service créé sur Render
- [ ] Base de données PostgreSQL créée
- [ ] Variables d'environnement configurées
- [ ] Déploiement réussi
- [ ] API accessible
- [ ] Swagger documenté

---

**Besoin d'aide ?** Consultez les logs dans le Dashboard Render !
