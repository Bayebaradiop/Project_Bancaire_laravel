# 📚 Documentation API avec Swagger

Cette application utilise **Swagger/OpenAPI** pour documenter automatiquement l'API REST.

## 🌐 Accéder à la documentation

Une fois le serveur Laravel démarré, accédez à la documentation interactive :

```
http://127.0.0.1:8001/api/documentation
```

## 📖 Fonctionnalités de Swagger UI

### 1. **Interface Interactive**
- Liste de tous les endpoints disponibles
- Description détaillée de chaque endpoint
- Exemples de requêtes et réponses
- Possibilité de tester directement les endpoints

### 2. **Tester les endpoints**
1. Cliquez sur un endpoint (ex: `GET /comptes`)
2. Cliquez sur "Try it out"
3. Remplissez les paramètres si nécessaire
4. Cliquez sur "Execute"
5. Consultez la réponse en bas

### 3. **Paramètres disponibles**

#### **GET /comptes** - Lister tous les comptes
- `page` (integer): Numéro de page (défaut: 1)
- `limit` (integer): Éléments par page (défaut: 10, max: 100)
- `type` (string): Filtrer par type (`cheque` ou `epargne`)
- `statut` (string): Filtrer par statut (`actif`, `bloque`, `ferme`)
- `search` (string): Rechercher par numéro ou titulaire
- `sort` (string): Trier par champ (`dateCreation`, `numeroCompte`, etc.)
- `order` (string): Ordre de tri (`asc` ou `desc`)

#### **GET /comptes/{id}** - Obtenir un compte par ID
- `id` (uuid): Identifiant unique du compte

#### **GET /comptes/numero/{numero}** - Obtenir un compte par numéro
- `numero` (string): Numéro du compte (ex: CP0241262525)

#### **GET /health** - Vérifier l'état de l'API
- Aucun paramètre requis

## 🔄 Régénérer la documentation

Si vous modifiez les annotations Swagger dans le code :

```bash
php artisan l5-swagger:generate
```

## 📝 Annotations Swagger dans le code

Les annotations se trouvent dans :
- **Controllers** : `app/Http/Controllers/Api/V1/`
  - `SwaggerController.php` : Configuration générale
  - `CompteController.php` : Endpoints des comptes
  - `HealthController.php` : Endpoint health check

- **Schemas** : `app/Models/CompteSwaggerSchema.php`
  - Définition du modèle `Compte`

## 🎯 Exemples de requêtes

### 1. Lister tous les comptes (page 1, 10 éléments)
```bash
curl http://127.0.0.1:8001/api/v1/comptes
```

### 2. Filtrer par type épargne avec pagination
```bash
curl "http://127.0.0.1:8001/api/v1/comptes?type=epargne&page=1&limit=5"
```

### 3. Rechercher un titulaire
```bash
curl "http://127.0.0.1:8001/api/v1/comptes?search=Reta"
```

### 4. Combiner filtres + tri + pagination
```bash
curl "http://127.0.0.1:8001/api/v1/comptes?type=cheque&statut=actif&sort=numeroCompte&order=asc&page=1&limit=3"
```

### 5. Obtenir un compte par ID
```bash
curl http://127.0.0.1:8001/api/v1/comptes/a02ea57f-907e-4894-acab-de01af9d4163
```

### 6. Obtenir un compte par numéro
```bash
curl http://127.0.0.1:8001/api/v1/comptes/numero/CP0241262525
```

### 7. Health check
```bash
curl http://127.0.0.1:8001/api/v1/health
```

## 📦 Format de réponse standard

Toutes les réponses API suivent ce format :

```json
{
  "success": true,
  "message": "Description du résultat",
  "data": {...},
  "pagination": {...},  // Pour les listes paginées
  "links": {...}        // Pour les listes paginées
}
```

## 🔒 Authentification (désactivée en développement)

L'authentification Sanctum est configurée mais désactivée pour faciliter le développement.

Pour l'activer en production :
1. Décommenter `auth:sanctum` dans `routes/api.php`
2. Implémenter les endpoints `/auth/login` et `/auth/register`
3. Utiliser le token dans les requêtes : `Authorization: Bearer {token}`

## 🛠️ Configuration

Le fichier de configuration Swagger se trouve dans :
```
config/l5-swagger.php
```

Paramètres importants :
- `'generate_always' => true` : Régénère la doc à chaque requête (désactiver en production)
- `'api.title' => 'API Documentation'` : Titre de la documentation
- `'routes.api' => 'api/documentation'` : URL de la documentation

## 📚 Ressources

- [Documentation L5 Swagger](https://github.com/DarkaOnLine/L5-Swagger)
- [Spécification OpenAPI 3.0](https://swagger.io/specification/)
- [Swagger Editor en ligne](https://editor.swagger.io/)

---

**Note** : La documentation Swagger est automatiquement mise à jour lors des modifications du code avec les annotations `@OA\*`.
