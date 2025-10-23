# 🚀 Déploiement Rapide sur Render

## 📋 Checklist en 5 minutes

### ✅ Étape 1 : Préparer le projet
```bash
# Exécuter le script de préparation
./prepare-deploy.sh

# Ou manuellement :
git add .
git commit -m "Add Render deployment configuration"
git push origin dev/v1.0.0
```

### ✅ Étape 2 : Créer un compte Render
1. Allez sur [render.com](https://render.com)
2. Inscrivez-vous (gratuit)
3. Connectez votre compte GitHub/GitLab

### ✅ Étape 3 : Déployer via Blueprint
1. Dans le Dashboard Render, cliquez sur **"New +"**
2. Sélectionnez **"Blueprint"**
3. Connectez le dépôt : `Bayebaradiop/Project_Bancaire_laravel`
4. Branch : `dev/v1.0.0`
5. Render détectera automatiquement `render.yaml`
6. Cliquez sur **"Apply"**

### ✅ Étape 4 : Configurer les variables (Optionnel)
Après création, dans les paramètres du service, ajoutez :
- `APP_URL` : https://votre-app.onrender.com
- Autres variables spécifiques à votre projet

### ✅ Étape 5 : Attendre le déploiement
- Durée : 5-10 minutes
- Surveillez les logs dans le Dashboard
- Une fois terminé, votre API sera accessible !

## 🎯 URLs importantes

Après le déploiement, vous aurez accès à :
- **API** : https://votre-app.onrender.com/api
- **Documentation Swagger** : https://votre-app.onrender.com/api/documentation
- **Logs** : Dashboard Render → Votre service → Logs

## 🔧 Choix de la base de données

### Option A : MySQL (Configuration actuelle)
- Fichiers utilisés : `render.yaml`, `Dockerfile`
- Pas de modification nécessaire

### Option B : PostgreSQL (Recommandé par Render)
```bash
# Remplacer les fichiers
mv render.yaml render.yaml.mysql
mv render.yaml.postgres render.yaml
mv Dockerfile Dockerfile.mysql
mv Dockerfile.postgres Dockerfile

# Committer et pousser
git add .
git commit -m "Switch to PostgreSQL"
git push
```

## 🐛 Dépannage rapide

### Erreur : "App key not set"
Dans le Dashboard Render → Environment → Ajouter :
```
APP_KEY=base64:votrecléici
```

### Erreur : "Database connection failed"
Vérifiez que la base de données est bien créée et connectée dans `render.yaml`

### Le service est lent
- Plan gratuit : Le service se met en veille après 15 min d'inactivité
- Première requête après veille : ~30 secondes
- Solution : Passer au plan payant ($7/mois)

## 💰 Coûts

| Service | Plan Gratuit | Plan Payant |
|---------|--------------|-------------|
| Web Service | ✅ Gratuit (limitations) | $7/mois |
| Base de données PostgreSQL | 90 jours gratuits | $7/mois |
| Base de données MySQL | Via service externe | Variable |

## 📱 Tester l'API déployée

```bash
# Tester un endpoint
curl https://votre-app.onrender.com/api/health

# Avec authentification
curl -H "Authorization: Bearer YOUR_TOKEN" \
     https://votre-app.onrender.com/api/comptes
```

## 🔄 Mises à jour automatiques

Render déploie automatiquement à chaque push sur la branche configurée !
```bash
git add .
git commit -m "Update feature"
git push origin dev/v1.0.0
# Render redéploie automatiquement
```

## 📚 Ressources

- [Documentation complète](./DEPLOYMENT.md)
- [Dashboard Render](https://dashboard.render.com)
- [Documentation Render](https://render.com/docs)
- [Laravel Deployment Guide](https://laravel.com/docs/10.x/deployment)

---

**Besoin d'aide ?** Consultez le fichier `DEPLOYMENT.md` pour des instructions détaillées.
