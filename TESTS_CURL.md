# TESTS ENDPOINTS API BANCAIRE - CURL

Base URL: `https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1`

---

## 1. HEALTH CHECK ✅

```bash
curl -X GET "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/health" \
  -H "Accept: application/json" \
  -s | jq '.'
```

---

## 2. LOGIN ADMIN 🔐

```bash
curl -X POST "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "admin@banque.sn",
    "password": "Admin@2024"
  }' \
  -s | jq '.'
```

**Récupérer le token:**
```bash
TOKEN=$(curl -X POST "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@banque.sn","password":"Admin@2024"}' \
  -s | jq -r '.data.access_token')

echo "Token: $TOKEN"
```

---

## 3. CRÉER UN COMPTE 📝

```bash
curl -X POST "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/comptes" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "type": "epargne",
    "devise": "FCFA",
    "client": {
      "titulaire": "Moussa Diallo",
      "nci": "1234567890123",
      "email": "moussa.diallo@example.com",
      "telephone": "+221771234567",
      "adresse": "Dakar, Plateau"
    }
  }' \
  -s | jq '.'
```

**Sauvegarder ID et Numéro:**
```bash
RESPONSE=$(curl -X POST "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/comptes" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "epargne",
    "devise": "FCFA",
    "client": {
      "titulaire": "Test User",
      "nci": "1987654321098",
      "email": "test.user@example.com",
      "telephone": "+221771234568",
      "adresse": "Dakar"
    }
  }' -s)

COMPTE_ID=$(echo $RESPONSE | jq -r '.data.id')
NUMERO_COMPTE=$(echo $RESPONSE | jq -r '.data.numeroCompte')

echo "Compte ID: $COMPTE_ID"
echo "Numéro: $NUMERO_COMPTE"
```

---

## 4. LISTER LES COMPTES 📋

```bash
curl -X GET "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/comptes?page=1&limit=10" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -s | jq '.'
```

**Avec filtres:**
```bash
curl -X GET "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/comptes?type=epargne&statut=actif&sort=dateCreation&order=desc&limit=5" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -s | jq '.'
```

---

## 5. RÉCUPÉRER COMPTE PAR ID 🔍

```bash
curl -X GET "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/comptes/$COMPTE_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -s | jq '.'
```

---

## 6. RÉCUPÉRER COMPTE PAR NUMÉRO 🔍

```bash
curl -X GET "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/comptes/numero/$NUMERO_COMPTE" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -s | jq '.'
```

---

## 7. METTRE À JOUR UN COMPTE ✏️

```bash
curl -X PATCH "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/comptes/$COMPTE_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "devise": "USD"
  }' \
  -s | jq '.'
```

---

## 8. BLOQUER UN COMPTE 🔒

**Blocage immédiat:**
```bash
curl -X POST "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/comptes/$COMPTE_ID/bloquer" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "raison": "Blocage test"
  }' \
  -s | jq '.'
```

**Blocage programmé (dans 2 jours):**
```bash
curl -X POST "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/comptes/$COMPTE_ID/bloquer" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{
    \"dateDebutBlocage\": \"$(date -d '+2 days' +%Y-%m-%d)\",
    \"raison\": \"Blocage programmé test\"
  }" \
  -s | jq '.'
```

---

## 9. DÉBLOQUER UN COMPTE 🔓

```bash
curl -X POST "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/comptes/$COMPTE_ID/debloquer" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{}' \
  -s | jq '.'
```

**Déblocage programmé:**
```bash
curl -X POST "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/comptes/$COMPTE_ID/debloquer" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{
    \"dateDeblocagePrevue\": \"$(date -d '+5 days' +%Y-%m-%d)\"
  }" \
  -s | jq '.'
```

---

## 10. LISTER LES COMPTES ARCHIVÉS 📦

```bash
curl -X GET "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/comptes/archives" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -s | jq '.'
```

---

## 11. SUPPRIMER UN COMPTE (Soft Delete) 🗑️

```bash
curl -X DELETE "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/comptes/$NUMERO_COMPTE" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -s | jq '.'
```

---

## 12. RESTAURER UN COMPTE 🔄

```bash
curl -X POST "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/comptes/restore/$COMPTE_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -s | jq '.'
```

---

## 13. REFRESH TOKEN 🔄

```bash
curl -X POST "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/auth/refresh" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -s | jq '.'
```

**Mettre à jour le token:**
```bash
NEW_TOKEN=$(curl -X POST "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/auth/refresh" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -s | jq -r '.data.access_token // .token')

TOKEN=$NEW_TOKEN
echo "Nouveau token: $TOKEN"
```

---

## 14. LOGOUT 👋

```bash
curl -X POST "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/auth/logout" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -s | jq '.'
```

---

## TESTS SPÉCIAUX

### Tester blocage compte CHÈQUE (doit échouer)

```bash
# 1. Créer un compte chèque
RESPONSE=$(curl -X POST "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/comptes" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "cheque",
    "devise": "FCFA",
    "client": {
      "titulaire": "Test Cheque",
      "nci": "1111111111111",
      "email": "test.cheque@example.com",
      "telephone": "+221771234569",
      "adresse": "Dakar"
    }
  }' -s)

COMPTE_CHEQUE_ID=$(echo $RESPONSE | jq -r '.data.id')

# 2. Essayer de bloquer (doit retourner erreur 400)
curl -X POST "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/comptes/$COMPTE_CHEQUE_ID/bloquer" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"raison": "Test blocage cheque"}' \
  -s | jq '.'
```

### Tester recherche avec filtres

```bash
curl -X GET "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/comptes?search=Diallo" \
  -H "Authorization: Bearer $TOKEN" \
  -s | jq '.'
```

---

## SÉQUENCE RAPIDE DE TEST

Copiez-collez cette séquence complète :

```bash
# 1. Login et récupération token
TOKEN=$(curl -X POST "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@banque.sn","password":"Admin@2024"}' \
  -s | jq -r '.data.access_token')

echo "✓ Token récupéré"

# 2. Health check
curl -X GET "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/health" -s | jq '.'
echo "✓ Health check OK"

# 3. Lister comptes
curl -X GET "https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1/comptes?limit=5" \
  -H "Authorization: Bearer $TOKEN" -s | jq '.data | length'
echo "✓ Liste comptes OK"

echo "Tests terminés!"
```
