# Test de l'endpoint POST /api/v1/comptes

## Note importante
Le solde d'un compte est calculé automatiquement : **Solde = Total dépôts - Total retraits**
Il n'y a pas de solde initial à fournir lors de la création.

## Test 1 : Créer un compte avec un nouveau client

```bash
curl -X POST http://127.0.0.1:8000/api/v1/comptes \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{
  "type": "cheque",
  "devise": "FCFA",
  "client": {
    "id": null,
    "titulaire": "Hawa BB Wane",
    "nci": "1234567890123",
    "email": "hawa.wane@example.com",
    "telephone": "+221771234567",
    "adresse": "Dakar, Sénégal"
  }
}'
```

## Test 2 : Créer un compte avec un client existant

```bash
curl -X POST http://127.0.0.1:8000/api/v1/comptes \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{
  "type": "epargne",
  "devise": "FCFA",
  "client": {
    "id": "REMPLACER_PAR_ID_CLIENT_EXISTANT"
  }
}'
```

## Test 3 : Erreur de validation (téléphone invalide)

```bash
curl -X POST http://127.0.0.1:8000/api/v1/comptes \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{
  "type": "cheque",
  "devise": "FCFA",
  "client": {
    "titulaire": "Test User",
    "nci": "1234567890123",
    "email": "test2@example.com",
    "telephone": "+221123456789",
    "adresse": "Dakar"
  }
}'
```

## Test 4 : Erreur de validation (NCI invalide)

```bash
curl -X POST http://127.0.0.1:8000/api/v1/comptes \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{
  "type": "courant",
  "devise": "FCFA",
  "client": {
    "titulaire": "Test User 3",
    "nci": "123",
    "email": "test3@example.com",
    "telephone": "+221771234568",
    "adresse": "Dakar"
  }
}'
```
