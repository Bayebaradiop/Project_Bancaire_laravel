# US 2.0 : Lister tous les comptes - Implémentation Complète

## 📋 Spécification US 2.0

### Acteurs
- **Admin** : Peut récupérer la liste de tous les comptes
- **Client** : Peut récupérer la liste de ses comptes uniquement

### Règle Métier Principale
> **"Liste compte non supprimés type cheque ou compte Epargne Actif"**

**Traduction technique :**
- ✅ Comptes **CHÈQUE** : Tous les statuts (actif, bloqué, fermé) NON archivés
- ✅ Comptes **ÉPARGNE** : Statut ACTIF uniquement NON archivés
- ❌ Comptes supprimés (soft delete) : Exclus
- ❌ Comptes archivés dans Neon : Exclus

## 🏗️ Implémentation

### 1. Global Scope : ActiveCompteScope

**Fichier :** `app/Models/Scopes/ActiveCompteScope.php`

**Logique :**
```php
// Filtrer automatiquement :
whereNull('archived_at')
->where(function ($query) {
    // Comptes CHÈQUE : tous les statuts
    $query->where('type', 'cheque')
        // OU Comptes ÉPARGNE : ACTIFS uniquement
        ->orWhere(function ($q) {
            $q->where('type', 'epargne')
              ->where('statut', 'actif');
        });
});
```

**Effet :** Appliqué automatiquement à toutes les requêtes `Compte::...`

### 2. Endpoint : GET /api/v1/comptes

**Route :** `routes/api.php`
```php
Route::get('/comptes', [CompteController::class, 'index'])
    ->middleware(['auth:api', 'scopes:compte-list'])
    ->name('comptes.index');
```

**Controller :** `app/Http/Controllers/Api/V1/CompteController.php`
```php
public function index(ListCompteRequest $request): JsonResponse
{
    $response = $this->compteService->getComptesList($request);
    return response()->json($response);
}
```

**Service :** `app/Services/CompteService.php`
```php
public function getComptesList(ListCompteRequest $request, ?User $user = null): array
{
    // Le Global Scope filtre automatiquement selon US 2.0
    $query = Compte::with(['client.user']);
    
    // Autorisation : Client voit uniquement ses comptes
    if ($user && $user->role === 'client') {
        $query->where('client_id', $user->client->id);
    }
    
    // Filtres, pagination, etc.
}
```

### 3. Query Parameters

Tous les filtres disponibles :

| Paramètre | Type | Description | Valeurs | Défaut |
|-----------|------|-------------|---------|--------|
| `page` | integer | Numéro de page | >= 1 | 1 |
| `limit` | integer | Éléments par page | 1-100 | 10 |
| `type` | string | Filtrer par type | epargne, cheque | - |
| `statut` | string | Filtrer par statut | actif, bloque, ferme | - |
| `search` | string | Recherche texte | - | - |
| `sort` | string | Champ de tri | dateCreation, solde, titulaire | dateCreation |
| `order` | string | Ordre de tri | asc, desc | desc |

### 4. Exemples de Requêtes

#### Tous les comptes (Admin)
```bash
GET /api/v1/comptes
Authorization: Bearer {admin_token}
```

**Résultat :**
```json
{
  "success": true,
  "data": [
    {"type": "cheque", "statut": "actif", "numeroCompte": "CP1111111111"},
    {"type": "cheque", "statut": "bloque", "numeroCompte": "CP2222222222"},
    {"type": "cheque", "statut": "ferme", "numeroCompte": "CP3333333333"},
    {"type": "epargne", "statut": "actif", "numeroCompte": "CP4444444444"}
  ]
}
```

#### Filtrer par type chèque
```bash
GET /api/v1/comptes?type=cheque
Authorization: Bearer {admin_token}
```

**Résultat :** Tous les comptes chèque (actif, bloqué, fermé)

#### Filtrer par type épargne
```bash
GET /api/v1/comptes?type=epargne
Authorization: Bearer {admin_token}
```

**Résultat :** Comptes épargne ACTIFS uniquement

#### Mes comptes (Client)
```bash
GET /api/v1/comptes
Authorization: Bearer {client_token}
```

**Résultat :** Comptes du client selon la règle US 2.0

## 🧪 Matrice de Tests

### Scénarios de test

| Type Compte | Statut | Archivé | Supprimé | Affiché ? | Raison |
|------------|--------|---------|----------|-----------|--------|
| Chèque | Actif | Non | Non | ✅ OUI | Règle US 2.0 |
| Chèque | Bloqué | Non | Non | ✅ OUI | Règle US 2.0 |
| Chèque | Fermé | Non | Non | ✅ OUI | Règle US 2.0 |
| Chèque | Actif | Oui | Non | ❌ NON | Archivé (Neon) |
| Chèque | Actif | Non | Oui | ❌ NON | Soft delete |
| Épargne | Actif | Non | Non | ✅ OUI | Règle US 2.0 |
| Épargne | Bloqué | Non | Non | ❌ NON | Pas actif + archivé Neon |
| Épargne | Fermé | Non | Non | ❌ NON | Pas actif |
| Épargne | Actif | Oui | Non | ❌ NON | Archivé (Neon) |
| Épargne | Actif | Non | Oui | ❌ NON | Soft delete |

### Tests CURL

**Test 1 : Lister tous les comptes (Admin)**
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/comptes" \
  -H "Authorization: Bearer ${ADMIN_TOKEN}" \
  -H "Accept: application/json"
```

**Attendu :** 
- Comptes chèque (tous statuts)
- Comptes épargne (actifs uniquement)

**Test 2 : Lister mes comptes (Client)**
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/comptes" \
  -H "Authorization: Bearer ${CLIENT_TOKEN}" \
  -H "Accept: application/json"
```

**Attendu :** Comptes du client selon règle US 2.0

**Test 3 : Filtrer par type chèque**
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/comptes?type=cheque" \
  -H "Authorization: Bearer ${ADMIN_TOKEN}" \
  -H "Accept: application/json"
```

**Attendu :** Tous les comptes chèque (actif, bloqué, fermé)

**Test 4 : Filtrer par type épargne**
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/comptes?type=epargne" \
  -H "Authorization: Bearer ${ADMIN_TOKEN}" \
  -H "Accept: application/json"
```

**Attendu :** Comptes épargne actifs uniquement

## 📊 Réponse API Complète

```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "numeroCompte": "CP1234567890",
      "titulaire": "Amadou Diallo",
      "type": "cheque",
      "solde": 1250000,
      "devise": "FCFA",
      "dateCreation": "2023-03-15T00:00:00Z",
      "statut": "bloque",
      "motifBlocage": "Vérification en cours",
      "metadata": {
        "derniereModification": "2023-06-10T14:30:00Z",
        "version": 1
      }
    },
    {
      "id": "660e8400-e29b-41d4-a716-446655440111",
      "numeroCompte": "CP0987654321",
      "titulaire": "Fatou Sall",
      "type": "epargne",
      "solde": 500000,
      "devise": "FCFA",
      "dateCreation": "2023-05-20T00:00:00Z",
      "statut": "actif",
      "motifBlocage": null,
      "metadata": {
        "derniereModification": "2023-07-15T10:20:00Z",
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
    "last": "/api/v1/comptes?page=3&limit=10"
  }
}
```

## 🔐 Autorisations

### Admin
- ✅ Voir tous les comptes (conformément à US 2.0)
- ✅ Filtrer par type, statut, recherche
- ✅ Pagination complète

### Client
- ✅ Voir uniquement ses propres comptes
- ✅ Filtrer ses comptes (type, statut, recherche)
- ✅ Pagination sur ses comptes
- ❌ Accès aux comptes d'autres clients (403 Forbidden)

## 📝 Checklist de Réalisation US 2.0

### ✅ Complété

- [x] **Controller** pour récupérer la request et retourner la response
- [x] **Routes** dans `routes/api.php` groupées par version (v1)
- [x] **Resource** `CompteResource` pour formater les données
- [x] **CORS** configuré dans `config/cors.php`
- [x] **Trait Global** `Cacheable` pour format de response
- [x] **Scope Global** `ActiveCompteScope` pour filtrer selon US 2.0
- [x] **Scope local** `scopeNumero` pour recherche par numéro
- [x] **Scope local** `scopeClient` pour recherche par client (téléphone)
- [x] **Exceptions Personnalisées** définies
- [x] **Documentation** Swagger/L5-Swagger complète
- [x] **Middleware RatingLimit** pour limiter les requêtes
- [x] **Consultation archives** depuis Neon (`GET /comptes/archive`)

## 🎯 Conformité US 2.0

### ✅ Règle Métier Respectée

> **"Liste compte non supprimés type cheque ou compte Epargne Actif"**

**Implémentation :**
```php
// Dans ActiveCompteScope::apply()
$builder->whereNull('archived_at')
    ->where(function ($query) {
        // Type CHÈQUE : tous statuts
        $query->where('type', 'cheque')
            // OU Type ÉPARGNE : ACTIFS uniquement
            ->orWhere(function ($q) {
                $q->where('type', 'epargne')
                  ->where('statut', 'actif');
            });
    });
```

**Résultat :**
- ✅ Comptes chèque : actif, bloqué, fermé (NON archivés)
- ✅ Comptes épargne : actif uniquement (NON archivés)
- ✅ Exclusion automatique des comptes supprimés
- ✅ Exclusion automatique des comptes archivés dans Neon

## 🚀 Validation

Pour valider que l'US 2.0 est correctement implémentée :

1. Créer des comptes de test avec différents types/statuts
2. Vérifier que `GET /api/v1/comptes` retourne selon la règle
3. Vérifier l'isolation des comptes par client
4. Tester les filtres et la pagination

**Script de test disponible :** `./test_blocage_neon.sh`

## 📞 Support

Pour toute question sur l'US 2.0 :
- Consulter `app/Models/Scopes/ActiveCompteScope.php`
- Consulter `app/Services/CompteService.php::getComptesList()`
- Consulter `BLOCAGE_ARCHIVAGE_NEON_DOCUMENTATION.md`
