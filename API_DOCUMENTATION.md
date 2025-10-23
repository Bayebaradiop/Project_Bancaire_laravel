# API Banque - Documentation Endpoint Lister Comptes

## ✅ US 2.0 : Lister tous les comptes - IMPLÉMENTÉ

### 📋 Fonctionnalités implémentées

#### 1. **Trait ApiResponse** ✅
- Format standardisé pour toutes les réponses API
- Méthodes : `successResponse()`, `errorResponse()`, `validationErrorResponse()`, etc.
- Gestion automatique de la pagination et des liens
- Fichier : `app/Traits/ApiResponse.php`

#### 2. **Modèle Compte avec Scopes** ✅
- **SoftDeletes** : Les comptes peuvent être archivés (soft delete)
- **Scopes personnalisés** :
  - `scopeNumero($numero)` : Recherche par numéro de compte
  - `scopeClient($telephone)` : Recherche par téléphone du client
  - `scopeType($type)` : Filtrer par type (cheque/epargne)
  - `scopeStatut($statut)` : Filtrer par statut (actif/bloque/ferme)
  - `scopeSearch($search)` : Recherche globale (numéro ou nom)
  - `scopeSortBy($sort, $order)` : Tri personnalisé
- Fichier : `app/Models/Compte.php`

#### 3. **Resource CompteResource** ✅
- Formatte les données de réponse (SANS le champ solde)
- Inclut : id, numeroCompte, titulaire, type, devise, dateCreation, statut, motifBlocage, metadata
- Fichier : `app/Http/Resources/CompteResource.php`

#### 4. **Controller CompteController** ✅
- **Route 1** : `GET /api/v1/comptes` - Liste paginée avec filtres
- **Route 2** : `GET /api/v1/comptes/{id}` - Détails par ID
- **Route 3** : `GET /api/v1/comptes/numero/{numero}` - Détails par numéro
- Annotations Swagger complètes
- Fichier : `app/Http/Controllers/Api/V1/CompteController.php`

#### 5. **Routes API V1** ✅
- Groupées par version `/api/v1`
- Protégées par `auth:sanctum`
- Health check endpoint : `GET /api/v1/health`
- Fichier : `routes/api.php`

#### 6. **Exceptions personnalisées** ✅
- `CompteNotFoundException` - 404
- `CompteBloquedException` - 403
- `InsufficientBalanceException` - 400
- `RateLimitExceededException` - 429
- Fichiers : `app/Exceptions/*.php`

#### 7. **Configuration CORS** ✅
- Headers exposés pour pagination
- Support credentials
- Cache 24h
- Fichier : `config/cors.php`

#### 8. **RateLimitMiddleware** ✅
- Limite configurable par endpoint
- Logging des utilisateurs dépassant la limite
- Headers `X-RateLimit-Limit` et `X-RateLimit-Remaining`
- Fichier : `app/Http/Middleware/RateLimitMiddleware.php`

---

## 🚀 Endpoints disponibles

### Base URL
```
http://localhost:8000/api/v1
```

### 1. Health Check
```http
GET /api/v1/health
```
**Réponse :**
```json
{
  "success": true,
  "message": "API is running",
  "version": "v1",
  "timestamp": "2025-10-23T10:30:00Z"
}
```

---

### 2. Lister tous les comptes
```http
GET /api/v1/comptes
Authorization: Bearer {token}
```

#### Query Parameters
| Paramètre | Type | Description | Défaut |
|-----------|------|-------------|--------|
| `page` | integer | Numéro de page | 1 |
| `limit` | integer | Éléments par page (max: 100) | 10 |
| `type` | string | Filtrer par type (epargne, cheque) | - |
| `statut` | string | Filtrer par statut (actif, bloque, ferme) | - |
| `search` | string | Recherche par titulaire ou numéro | - |
| `sort` | string | Tri (dateCreation, derniereModification, numeroCompte) | dateCreation |
| `order` | string | Ordre (asc, desc) | desc |

#### Exemple de requête
```bash
GET /api/v1/comptes?page=1&limit=10&type=epargne&statut=actif&sort=dateCreation&order=desc
```

#### Réponse succès (200)
```json
{
  "success": true,
  "message": "Liste des comptes récupérée avec succès",
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "numeroCompte": "CP0123456789",
      "titulaire": "Amadou Diallo",
      "type": "epargne",
      "devise": "FCFA",
      "dateCreation": "2023-03-15T00:00:00Z",
      "statut": "actif",
      "motifBlocage": null,
      "metadata": {
        "derniereModification": "2023-06-10T14:30:00Z",
        "version": 1
      }
    }
  ],
  "pagination": {
    "currentPage": 1,
    "totalPages": 3,
    "totalItems": 25,
    "itemsPerPage": 10,
    "hasNext": true,
    "hasPrevious": false
  },
  "links": {
    "self": "/api/v1/comptes?page=1&limit=10",
    "next": "/api/v1/comptes?page=2&limit=10",
    "first": "/api/v1/comptes?page=1&limit=10",
    "last": "/api/v1/comptes?page=3&limit=10",
    "previous": null
  }
}
```

---

### 3. Obtenir un compte par ID
```http
GET /api/v1/comptes/{id}
Authorization: Bearer {token}
```

#### Réponse succès (200)
```json
{
  "success": true,
  "message": "Compte récupéré avec succès",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "numeroCompte": "CP0123456789",
    "titulaire": "Amadou Diallo",
    "type": "epargne",
    "devise": "FCFA",
    "dateCreation": "2023-03-15T00:00:00Z",
    "statut": "actif",
    "motifBlocage": null,
    "metadata": {
      "derniereModification": "2023-06-10T14:30:00Z",
      "version": 1
    }
  }
}
```

#### Réponse erreur (404)
```json
{
  "success": false,
  "message": "Compte non trouvé"
}
```

---

### 4. Obtenir un compte par numéro
```http
GET /api/v1/comptes/numero/{numeroCompte}
Authorization: Bearer {token}
```

---

## 📊 Codes de réponse HTTP

| Code | Description |
|------|-------------|
| 200 | Succès |
| 401 | Non autorisé (token manquant/invalide) |
| 403 | Accès interdit |
| 404 | Ressource non trouvée |
| 422 | Erreur de validation |
| 429 | Trop de requêtes (rate limit) |
| 500 | Erreur serveur |

---

## 🔐 Authentification

L'API utilise **Laravel Sanctum** pour l'authentification par token Bearer.

```http
Authorization: Bearer {votre_token_ici}
```

---

## 🧪 Tests

Pour tester l'API localement :

```bash
# Démarrer le serveur
php artisan serve

# Tester le health check
curl http://localhost:8000/api/v1/health

# Lister les comptes (nécessite un token)
curl -H "Authorization: Bearer {token}" \
     http://localhost:8000/api/v1/comptes
```

---

## 📝 Notes importantes

- **Pas de champ solde** : Le solde n'est pas inclus dans les réponses (comme demandé)
- **Soft Deletes** : Les comptes supprimés sont archivés, pas définitivement supprimés
- **Pagination** : Limite maximale de 100 éléments par page
- **Rate Limiting** : Headers `X-RateLimit-Limit` et `X-RateLimit-Remaining` dans chaque réponse

---

## 📦 Prochaines étapes

1. **Authentification complète** : Endpoints login/register
2. **Swagger UI** : Interface visuelle de documentation
3. **Tests unitaires** : PHPUnit pour tous les endpoints
4. **Déploiement** : Docker ou Render

---

## 🎯 Checklist US 2.0

- ✅ Trait ApiResponse global
- ✅ Scopes dans le modèle Compte
- ✅ Resource CompteResource (sans solde)
- ✅ Controller avec index(), show(), showByNumero()
- ✅ Routes API v1
- ✅ Exceptions personnalisées
- ✅ Configuration CORS
- ✅ RateLimitMiddleware avec logging
- ✅ Migration avec soft deletes
- ✅ Pagination avec metadata et links
- ✅ Filtres (type, statut, search)
- ✅ Tri personnalisé (sort, order)
