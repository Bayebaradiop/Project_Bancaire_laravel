# 📋 Documentation Trello - Endpoints Transactions

---

## US 3.0 : Lister toutes les transactions

**Étiquettes** : `EndPoint`, `Realisation`, `Acteur:Admin`, `Acteur:Client`

**Description** :
1. Admin peut récupérer la liste de toutes les transactions
2. Client peut récupérer la liste de ses propres transactions (comptes qui lui appartiennent)

**Base URL** : `http://api.banque.example.com/api/v1`

### Endpoint : GET /api/v1/transactions

**Query Parameters** :
- `page` : Numéro de page (default: 1)
- `limit` : Nombre d'éléments par page (default: 10, max: 100)
- `type` : Filtrer par type (DEPOT, RETRAIT, TRANSFERT)
- `compte` : Filtrer par numéro de compte
- `date_debut` : Date de début (format: YYYY-MM-DD)
- `date_fin` : Date de fin (format: YYYY-MM-DD)
- `statut` : Filtrer par statut (effectue, annule, en_attente)

**Request Header** :
```json
{
  "Authorization": "Bearer {jwt_token}",
  "Accept": "application/json"
}
```

**Response (200 OK)** :
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "type": "DEPOT",
      "montant": 50000,
      "devise": "FCFA",
      "compte_source": "CP1234567890",
      "compte_destinataire": null,
      "reference": "TRX-2024-001",
      "statut": "effectue",
      "date_transaction": "2024-10-23T10:30:00Z",
      "description": "Dépôt en espèces"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 45,
    "last_page": 5
  }
}
```

---

## US 3.1 : Effectuer un dépôt

**Étiquettes** : `EndPoint`, `Realisation`, `Acteur:Admin`

**Description** :
Seul l'admin peut effectuer un dépôt sur un compte.

**Base URL** : `http://api.banque.example.com/api/v1`

### Endpoint : POST /api/v1/transactions/depot

**Request Header** :
```json
{
  "Authorization": "Bearer {jwt_token}",
  "Accept": "application/json",
  "Content-Type": "application/json"
}
```

**Request Body** :
```json
{
  "compte_destinataire": "CP1234567890",
  "montant": 50000,
  "devise": "FCFA",
  "description": "Dépôt en espèces"
}
```

**Règles de Validation** :
- `compte_destinataire` : required|string|exists:comptes,numeroCompte|compte actif
- `montant` : required|numeric|min:500|max:10000000
- `devise` : required|string|in:FCFA,EUR,USD
- `description` : nullable|string|max:255

**Response (201 Created)** :
```json
{
  "success": true,
  "message": "Dépôt effectué avec succès",
  "data": {
    "id": "uuid",
    "type": "DEPOT",
    "montant": 50000,
    "devise": "FCFA",
    "compte_destinataire": "CP1234567890",
    "reference": "TRX-2024-001",
    "statut": "effectue",
    "nouveau_solde": 150000,
    "date_transaction": "2024-10-23T10:30:00Z"
  }
}
```

**Response (400 Bad Request)** :
```json
{
  "success": false,
  "message": "Les données fournies sont invalides",
  "errors": {
    "montant": ["Le montant minimum est de 500 FCFA"],
    "compte_destinataire": ["Le compte n'existe pas ou est inactif"]
  }
}
```

---

## US 3.2 : Effectuer un retrait

**Étiquettes** : `EndPoint`, `Realisation`, `Acteur:Admin`, `Acteur:Client`

**Description** :
- Admin peut effectuer un retrait sur n'importe quel compte
- Client peut effectuer un retrait sur ses propres comptes

**Base URL** : `http://api.banque.example.com/api/v1`

### Endpoint : POST /api/v1/transactions/retrait

**Request Header** :
```json
{
  "Authorization": "Bearer {jwt_token}",
  "Accept": "application/json",
  "Content-Type": "application/json"
}
```

**Request Body** :
```json
{
  "compte_source": "CP1234567890",
  "montant": 25000,
  "devise": "FCFA",
  "description": "Retrait guichet"
}
```

**Règles de Validation** :
- `compte_source` : required|string|exists:comptes,numeroCompte|compte actif|appartient au client
- `montant` : required|numeric|min:500|max:solde_disponible
- `devise` : required|string|in:FCFA,EUR,USD
- `description` : nullable|string|max:255

**Contraintes** :
- Le solde du compte doit être suffisant
- Le compte ne doit pas être bloqué
- Le montant maximum de retrait par jour : 500,000 FCFA (configurable)

**Response (201 Created)** :
```json
{
  "success": true,
  "message": "Retrait effectué avec succès",
  "data": {
    "id": "uuid",
    "type": "RETRAIT",
    "montant": 25000,
    "devise": "FCFA",
    "compte_source": "CP1234567890",
    "reference": "TRX-2024-002",
    "statut": "effectue",
    "nouveau_solde": 125000,
    "date_transaction": "2024-10-23T11:00:00Z"
  }
}
```

**Response (400 Bad Request)** :
```json
{
  "success": false,
  "message": "Opération impossible",
  "errors": {
    "montant": ["Solde insuffisant. Solde disponible : 20000 FCFA"]
  }
}
```

---

## US 3.3 : Effectuer un transfert

**Étiquettes** : `EndPoint`, `Realisation`, `Acteur:Client`

**Description** :
Le client peut transférer de l'argent entre ses propres comptes ou vers un autre compte.

**Base URL** : `http://api.banque.example.com/api/v1`

### Endpoint : POST /api/v1/transactions/transfert

**Request Header** :
```json
{
  "Authorization": "Bearer {jwt_token}",
  "Accept": "application/json",
  "Content-Type": "application/json"
}
```

**Request Body** :
```json
{
  "compte_source": "CP1234567890",
  "compte_destinataire": "CP0987654321",
  "montant": 75000,
  "devise": "FCFA",
  "description": "Virement mensuel"
}
```

**Règles de Validation** :
- `compte_source` : required|string|exists:comptes,numeroCompte|appartient au client|actif
- `compte_destinataire` : required|string|exists:comptes,numeroCompte|different:compte_source|actif
- `montant` : required|numeric|min:1000|max:solde_disponible
- `devise` : required|string|in:FCFA,EUR,USD
- `description` : nullable|string|max:255

**Contraintes** :
- Les deux comptes doivent être actifs
- Le compte source doit avoir un solde suffisant
- Le compte source doit appartenir au client authentifié
- Les comptes doivent avoir la même devise (ou conversion automatique)
- Frais de transfert : 0.5% (min 100 FCFA, max 5000 FCFA)

**Response (201 Created)** :
```json
{
  "success": true,
  "message": "Transfert effectué avec succès",
  "data": {
    "id": "uuid",
    "type": "TRANSFERT",
    "montant": 75000,
    "frais": 375,
    "montant_total": 75375,
    "devise": "FCFA",
    "compte_source": "CP1234567890",
    "compte_destinataire": "CP0987654321",
    "reference": "TRX-2024-003",
    "statut": "effectue",
    "nouveau_solde_source": 49625,
    "date_transaction": "2024-10-23T12:00:00Z"
  }
}
```

**Response (400 Bad Request)** :
```json
{
  "success": false,
  "message": "Transfert impossible",
  "errors": {
    "compte_source": ["Ce compte ne vous appartient pas"],
    "montant": ["Solde insuffisant (incluant les frais de 375 FCFA)"]
  }
}
```

---

## US 3.4 : Consulter une transaction

**Étiquettes** : `EndPoint`, `Realisation`, `Acteur:Admin`, `Acteur:Client`

**Description** :
- Admin peut consulter n'importe quelle transaction
- Client peut consulter uniquement ses propres transactions

**Base URL** : `http://api.banque.example.com/api/v1`

### Endpoint : GET /api/v1/transactions/{id}

**Request Header** :
```json
{
  "Authorization": "Bearer {jwt_token}",
  "Accept": "application/json"
}
```

**Response (200 OK)** :
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "type": "TRANSFERT",
    "montant": 75000,
    "frais": 375,
    "montant_total": 75375,
    "devise": "FCFA",
    "compte_source": {
      "numeroCompte": "CP1234567890",
      "titulaire": "Jean Dupont",
      "type": "epargne"
    },
    "compte_destinataire": {
      "numeroCompte": "CP0987654321",
      "titulaire": "Marie Martin",
      "type": "cheque"
    },
    "reference": "TRX-2024-003",
    "statut": "effectue",
    "description": "Virement mensuel",
    "date_transaction": "2024-10-23T12:00:00Z",
    "effectue_par": {
      "id": "uuid",
      "nom": "Jean Dupont",
      "role": "client"
    }
  }
}
```

**Response (404 Not Found)** :
```json
{
  "success": false,
  "message": "Transaction non trouvée"
}
```

**Response (403 Forbidden)** :
```json
{
  "success": false,
  "message": "Accès non autorisé à cette transaction"
}
```

---

## US 3.5 : Annuler une transaction

**Étiquettes** : `EndPoint`, `Realisation`, `Acteur:Admin`

**Description** :
Seul l'admin peut annuler une transaction (dans un délai de 24h).

**Base URL** : `http://api.banque.example.com/api/v1`

### Endpoint : DELETE /api/v1/transactions/{id}

**Request Header** :
```json
{
  "Authorization": "Bearer {jwt_token}",
  "Accept": "application/json"
}
```

**Request Body (optionnel)** :
```json
{
  "motif": "Erreur de saisie - Remboursement client"
}
```

**Contraintes** :
- Transaction de moins de 24h
- Transaction au statut "effectue"
- Les comptes source et destinataire doivent être actifs

**Comportement** :
- Créer une transaction inverse (contre-passation)
- Mettre à jour les soldes des comptes
- Marquer la transaction originale comme "annule"

**Response (200 OK)** :
```json
{
  "success": true,
  "message": "Transaction annulée avec succès",
  "data": {
    "transaction_annulee": {
      "id": "uuid-original",
      "statut": "annule",
      "date_annulation": "2024-10-23T14:00:00Z"
    },
    "transaction_inverse": {
      "id": "uuid-nouveau",
      "type": "ANNULATION",
      "reference": "ANN-TRX-2024-003",
      "montant": 75000,
      "date_transaction": "2024-10-23T14:00:00Z"
    }
  }
}
```

**Response (400 Bad Request)** :
```json
{
  "success": false,
  "message": "Annulation impossible",
  "errors": {
    "transaction": ["Le délai d'annulation de 24h est dépassé"],
    "statut": ["Cette transaction a déjà été annulée"]
  }
}
```

---

## Récapitulatif des Endpoints

| Méthode | Endpoint | Description | Acteur |
|---------|----------|-------------|--------|
| GET | /api/v1/transactions | Lister les transactions | Admin, Client |
| POST | /api/v1/transactions/depot | Effectuer un dépôt | Admin |
| POST | /api/v1/transactions/retrait | Effectuer un retrait | Admin, Client |
| POST | /api/v1/transactions/transfert | Effectuer un transfert | Client |
| GET | /api/v1/transactions/{id} | Consulter une transaction | Admin, Client |
| DELETE | /api/v1/transactions/{id} | Annuler une transaction | Admin |

---

## Notes d'implémentation

### Calcul du solde
Le solde est calculé dynamiquement :
```
Solde = Solde initial + ∑(DEPOT) - ∑(RETRAIT) - ∑(TRANSFERT sortant) + ∑(TRANSFERT entrant)
```

### Frais de transaction
- **Dépôt** : Gratuit
- **Retrait** : Gratuit
- **Transfert** : 0.5% (min 100 FCFA, max 5000 FCFA)

### Sécurité
- JWT Authentication obligatoire
- Validation des permissions (client ne peut agir que sur ses comptes)
- Logs de toutes les transactions
- Transactions atomiques (rollback en cas d'erreur)

### Performance
- Index sur : `compte_source`, `compte_destinataire`, `date_transaction`, `statut`, `type`
- Pagination obligatoire sur la liste
- Cache des soldes de compte
