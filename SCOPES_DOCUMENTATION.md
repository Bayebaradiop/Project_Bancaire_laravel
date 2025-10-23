# 📚 Documentation des Scopes - Modèle Compte

## 🎯 Distinction : Global Scopes vs Local Scopes

### 🌍 **Global Scopes**
Les Global Scopes sont appliqués **automatiquement** à toutes les requêtes du modèle.

#### ✅ Implémenté dans le modèle Compte :
- **SoftDeletes** (trait Laravel) : Exclut automatiquement les comptes supprimés (soft deleted)
  - Appliqué via : `use SoftDeletes;`
  - Résultat : `Compte::all()` → Ne retourne que les comptes non archivés
  - Pour inclure les archivés : `Compte::withTrashed()->get()`
  - Pour seulement les archivés : `Compte::onlyTrashed()->get()`

### 🔍 **Local Scopes**  
Les Local Scopes sont appliqués **manuellement** dans vos requêtes.

#### ✅ Implémentés dans le modèle Compte :

| Scope | Syntaxe | Description | Exemple |
|-------|---------|-------------|---------|
| **scopeNumero** | `->numero($numero)` | Recherche par numéro de compte | `Compte::numero('CP0123456789')->first()` |
| **scopeClient** | `->client($telephone)` | Filtre par téléphone du client | `Compte::client('+221 77 123 45 67')->get()` |
| **scopeType** | `->type($type)` | Filtre par type (cheque/epargne) | `Compte::type('epargne')->get()` |
| **scopeStatut** | `->statut($statut)` | Filtre par statut (actif/bloque/ferme) | `Compte::statut('actif')->get()` |
| **scopeSearch** | `->search($search)` | Recherche globale (numéro ou nom) | `Compte::search('Diallo')->get()` |
| **scopeSortBy** | `->sortBy($sort, $order)` | Tri personnalisé | `Compte::sortBy('dateCreation', 'desc')->get()` |

---

## 📖 Détails des Local Scopes

### 1. **scopeNumero($numero)** 
```php
Compte::numero('CP0123456789')->first();
```
- **But** : Récupérer un compte par son numéro unique
- **Utilisation** : Dans l'endpoint `GET /api/v1/comptes/numero/{numero}`

---

### 2. **scopeClient($telephone)**
```php
Compte::client('+221 77 123 45 67')->get();
```
- **But** : Récupérer tous les comptes d'un client via son téléphone
- **Relation** : `Compte -> Client -> User (telephone)`
- **Utilisation** : Lister les comptes d'un client spécifique

---

### 3. **scopeType($type)**
```php
Compte::type('epargne')->get();
Compte::type('cheque')->get();
```
- **But** : Filtrer par type de compte
- **Valeurs** : `'epargne'` ou `'cheque'`
- **Utilisation** : Dans le query param `?type=epargne`

---

### 4. **scopeStatut($statut)**
```php
Compte::statut('actif')->get();
Compte::statut('bloque')->get();
Compte::statut('ferme')->get();
```
- **But** : Filtrer par statut du compte
- **Valeurs** : `'actif'`, `'bloque'`, `'ferme'`
- **Utilisation** : Dans le query param `?statut=actif`

---

### 5. **scopeSearch($search)**
```php
Compte::search('Diallo')->get();
Compte::search('CP0123')->get();
```
- **But** : Recherche globale
- **Champs recherchés** :
  - Numéro de compte (LIKE)
  - Nom complet du titulaire (via relation)
- **Utilisation** : Dans le query param `?search=Diallo`

---

### 6. **scopeSortBy($sort, $order)**
```php
Compte::sortBy('dateCreation', 'desc')->get();
Compte::sortBy('numeroCompte', 'asc')->get();
```
- **But** : Trier les résultats
- **Champs autorisés** :
  - `dateCreation`
  - `derniereModification`
  - `numeroCompte`
- **Ordre** : `'asc'` ou `'desc'`
- **Utilisation** : Dans les query params `?sort=dateCreation&order=desc`

---

## 🔗 Combinaison des Scopes

Les scopes peuvent être **chaînés** ensemble :

```php
// Comptes épargne actifs d'un client spécifique, triés par date
Compte::type('epargne')
    ->statut('actif')
    ->client('+221 77 123 45 67')
    ->sortBy('dateCreation', 'desc')
    ->get();

// Recherche dans les comptes chèques actifs
Compte::type('cheque')
    ->statut('actif')
    ->search('Amadou')
    ->paginate(10);
```

---

## 🎛️ Utilisation dans le Controller

```php
public function index(ListCompteRequest $request): JsonResponse
{
    $query = Compte::with(['client.user']);

    // Filtres conditionnels
    if ($type = $request->getType()) {
        $query->type($type);
    }

    if ($statut = $request->getStatut()) {
        $query->statut($statut);
    }

    if ($search = $request->getSearch()) {
        $query->search($search);
    }

    // Tri
    $query->sortBy($request->getSort(), $request->getOrder());

    // Pagination
    $comptes = $query->paginate($request->getLimit());

    return $this->paginated($comptes, ...);
}
```

---

## 📊 Performance et Indexation

### Index créés dans la migration :
```php
$table->index('numeroCompte');    // Pour scopeNumero
$table->index('client_id');       // Pour scopeClient + relations
$table->index('statut');          // Pour scopeStatut
$table->index('type');            // Pour scopeType
$table->index('deleted_at');      // Pour SoftDeletes (Global Scope)
```

---

## 🧪 Tests des Scopes

```bash
# Test scopeNumero
php artisan tinker
>>> Compte::numero('CP0123456789')->first();

# Test scopeType
>>> Compte::type('epargne')->count();

# Test scopeStatut
>>> Compte::statut('actif')->count();

# Test scopeSearch
>>> Compte::search('Amadou')->get();

# Test combiné
>>> Compte::type('epargne')->statut('actif')->sortBy('dateCreation', 'desc')->paginate(5);
```

---

## ✅ Résumé

| Type | Nombre | Application | Fichier |
|------|--------|-------------|---------|
| **Global Scope** | 1 | Automatique (SoftDeletes) | `app/Models/Compte.php` |
| **Local Scopes** | 6 | Manuelle (chaînage) | `app/Models/Compte.php` |
| **Total** | 7 scopes | - | - |

**Global Scope** = Appliqué partout automatiquement  
**Local Scope** = Appliqué uniquement quand vous le demandez
