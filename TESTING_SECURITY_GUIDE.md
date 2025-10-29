# 🧪 Guide de Test Sécurisé

## ⚠️ Règles de Sécurité

**JAMAIS** commiter des fichiers contenant :
- ❌ Mots de passe réels
- ❌ Tokens d'authentification
- ❌ Clés API
- ❌ Cookies de session

## 📝 Utilisation du Template de Test

### 1. Créer votre fichier de test local

```bash
# Copier le template
cp test_example.sh.template test_local.sh

# Éditer avec vos credentials de test
nano test_local.sh

# Rendre exécutable
chmod +x test_local.sh
```

### 2. Utiliser des variables d'environnement

```bash
# Créer un fichier .env.testing (ignoré par git)
cat > .env.testing << EOF
ADMIN_EMAIL=admin@banque.sn
ADMIN_PASSWORD=YourSecurePassword
CLIENT_EMAIL=client@banque.sn
CLIENT_PASSWORD=YourSecurePassword
API_URL=http://localhost:8000
EOF

# Charger et exécuter
source .env.testing && ./test_local.sh
```

### 3. Fichiers ignorés par Git

Les patterns suivants sont automatiquement ignorés :
- `test_*.sh` - Scripts de test
- `*_test.sh` - Scripts de test alternatifs
- `cookies*.txt` - Fichiers de cookies
- `.env.testing` - Variables d'environnement de test

## 🎯 Bonnes Pratiques

### ✅ À FAIRE
- Utiliser des variables d'environnement
- Utiliser le template fourni
- Tester sur des données factices
- Garder les credentials localement

### ❌ À NE PAS FAIRE
- Hardcoder les mots de passe dans les scripts
- Commiter les fichiers de test personnalisés
- Partager les credentials dans la documentation
- Utiliser les credentials de production pour les tests

## 📚 Exemples de Tests

### Test Login Admin
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"${ADMIN_EMAIL}\",\"password\":\"${ADMIN_PASSWORD}\"}"
```

### Test Création Compte avec Auto-création
```bash
curl -X POST http://localhost:8000/api/v1/comptes \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "epargne",
    "solde": 50000,
    "client": {
      "nomComplet": "Test Client",
      "email": "test@example.com",
      "telephone": "+221770000000",
      "adresse": "Dakar"
    }
  }'
```

## 🔒 Que faire si des credentials sont exposés ?

1. **Révoquer immédiatement** les credentials exposés
2. **Supprimer le fichier** du repository :
   ```bash
   git rm --cached fichier_sensible.sh
   git commit -m "security: Remove exposed credentials"
   git push --force
   ```
3. **Changer les mots de passe** concernés
4. **Vérifier** qu'aucun autre fichier ne contient de secrets

## 📧 Contact

Pour toute question de sécurité, contacter l'équipe de développement.
