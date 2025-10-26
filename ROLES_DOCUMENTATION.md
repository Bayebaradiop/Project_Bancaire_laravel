# 🔐 Documentation des Rôles et Authentification

## Vue d'ensemble

Le système d'authentification distingue maintenant deux types d'utilisateurs :
- **Client** : Utilisateur standard avec accès limité
- **Admin** : Administrateur avec tous les privilèges

## 🎭 Rôles disponibles

### 1. Client (`role: 'client'`)
- Rôle par défaut pour les nouveaux utilisateurs
- Accès aux endpoints publics et à leurs propres ressources
- Peut consulter ses propres comptes

### 2. Admin (`role: 'admin'`)
- Accès complet à tous les endpoints
- Peut gérer tous les utilisateurs et comptes
- Accès aux fonctionnalités d'administration

## 📋 Structure de la base de données

### Champ `role` dans la table `users`
```sql
role ENUM('client', 'admin') DEFAULT 'client'
```

## 🔑 Authentification et identification du rôle

### 1. Login - POST `/api/v1/auth/login`

**Request:**
```json
{
  "email": "amadou.diallo@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "id": "uuid-123",
      "nomComplet": "Amadou Diallo",
      "email": "amadou.diallo@example.com",
      "telephone": "+221771234567",
      "role": "admin",
      "isAdmin": true
    }
  }
}
```

### 2. Récupérer l'utilisateur authentifié - GET `/api/v1/auth/me`

**Response:**
```json
{
  "success": true,
  "message": "Utilisateur récupéré avec succès",
  "data": {
    "id": "uuid-123",
    "nomComplet": "Amadou Diallo",
    "email": "amadou.diallo@example.com",
    "telephone": "+221771234567",
    "nci": "1234567890123456",
    "adresse": "Dakar, Sénégal",
    "role": "admin",
    "isAdmin": true
  }
}
```

## 🛡️ Middleware de protection

### Middleware `admin`

Utilisé pour protéger les routes réservées aux administrateurs.

**Utilisation dans les routes:**
```php
// Route accessible uniquement aux admins
Route::middleware(['auth:api', 'admin'])->group(function () {
    Route::get('/admin/users', [AdminController::class, 'index']);
    Route::delete('/admin/users/{id}', [AdminController::class, 'destroy']);
});
```

**Réponse si non-admin (403):**
```json
{
  "success": false,
  "message": "Accès refusé. Seuls les administrateurs peuvent accéder à cette ressource."
}
```

## 🧪 Utilisateurs de test

### Admin
```
Email: amadou.diallo@example.com
Password: password123
Role: admin
```

### Clients
Tous les autres utilisateurs ont le rôle `client` par défaut.

## 💻 Utilisation dans le code

### Modèle User

Le modèle `User` dispose de méthodes helper :

```php
// Vérifier si l'utilisateur est admin
if ($user->isAdmin()) {
    // Code pour admin
}

// Vérifier si l'utilisateur est client
if ($user->isClient()) {
    // Code pour client
}

// Accéder au rôle
$role = $user->role; // 'admin' ou 'client'
```

### Dans un contrôleur

```php
public function someAction(Request $request)
{
    $user = $request->user();
    
    if ($user->isAdmin()) {
        // Logique pour admin
        return $this->success($allData);
    }
    
    // Logique pour client (données filtrées)
    return $this->success($userSpecificData);
}
```

## 🔄 Scénarios d'utilisation

### Scénario 1 : Login en tant qu'admin

1. **Login**
   ```bash
   curl -X POST http://localhost:8000/api/v1/auth/login \
     -H "Content-Type: application/json" \
     -d '{
       "email": "amadou.diallo@example.com",
       "password": "password123"
     }' \
     -c cookies.txt
   ```

2. **Vérifier le rôle**
   ```bash
   curl -X GET http://localhost:8000/api/v1/auth/me \
     -b cookies.txt
   ```

3. **Accéder à une ressource protégée admin**
   ```bash
   curl -X GET http://localhost:8000/api/v1/admin/users \
     -b cookies.txt
   ```

### Scénario 2 : Login en tant que client

1. **Login**
   ```bash
   curl -X POST http://localhost:8000/api/v1/auth/login \
     -H "Content-Type: application/json" \
     -d '{
       "email": "fatou.sall@example.com",
       "password": "password123"
     }' \
     -c cookies.txt
   ```

2. **Tenter d'accéder à une ressource admin** (échouera)
   ```bash
   curl -X GET http://localhost:8000/api/v1/admin/users \
     -b cookies.txt
   # Response: 403 Forbidden
   ```

## 📊 Exemples d'intégration Frontend

### React / Vue.js / Angular

```javascript
// Login
const response = await fetch('http://localhost:8000/api/v1/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  credentials: 'include', // Important pour les cookies HTTP-only
  body: JSON.stringify({
    email: 'amadou.diallo@example.com',
    password: 'password123'
  })
});

const data = await response.json();

// Stocker les informations utilisateur (pas le token, il est dans le cookie)
localStorage.setItem('user', JSON.stringify(data.data.user));

// Vérifier le rôle
const user = JSON.parse(localStorage.getItem('user'));
if (user.isAdmin) {
  // Afficher le menu admin
  showAdminMenu();
} else {
  // Afficher le menu client
  showClientMenu();
}

// Faire une requête authentifiée
const comptesResponse = await fetch('http://localhost:8000/api/v1/comptes', {
  method: 'GET',
  credentials: 'include', // Le cookie est automatiquement envoyé
});
```

## 🔒 Bonnes pratiques de sécurité

1. **Ne jamais exposer les tokens dans localStorage** ✅
   - Les tokens sont dans des cookies HTTP-only
   - Protection contre XSS

2. **Vérifier le rôle côté serveur** ✅
   - Toujours utiliser le middleware `admin`
   - Ne jamais se fier uniquement au frontend

3. **Limiter les tentatives de login** ✅
   - Rate limiting: 10 tentatives/minute
   - Protection contre brute force

4. **Utiliser HTTPS en production** ⚠️
   - Les cookies `secure` nécessitent HTTPS
   - Protection contre MITM

5. **Expiration des tokens** ✅
   - Access token: 1 heure
   - Refresh token: 30 jours

## 🛠️ Commandes utiles

### Créer un admin
```bash
php artisan tinker --execute="
\$user = \App\Models\User::where('email', 'email@example.com')->first();
\$user->role = 'admin';
\$user->save();
echo 'User is now admin';
"
```

### Lister tous les admins
```bash
php artisan tinker --execute="
\App\Models\User::where('role', 'admin')->get(['nomComplet', 'email'])->each(function(\$u) {
  echo \$u->nomComplet . ' - ' . \$u->email . PHP_EOL;
});
"
```

### Révoquer les privilèges admin
```bash
php artisan tinker --execute="
\$user = \App\Models\User::where('email', 'email@example.com')->first();
\$user->role = 'client';
\$user->save();
echo 'User is now client';
"
```

## 📝 Notes importantes

1. **Compatibilité CORS** : Le frontend doit utiliser `credentials: 'include'` pour que les cookies soient envoyés
2. **SameSite=strict** : Les cookies ne sont envoyés que depuis le même domaine
3. **Migration automatique** : Tous les utilisateurs existants ont été migrés avec le rôle `client` par défaut
4. **Pas de tokens dans les headers** : L'authentification se fait uniquement via cookies HTTP-only

## 🎯 Prochaines étapes recommandées

1. ✅ Tester l'authentification avec admin et client
2. ✅ Vérifier que les cookies sont correctement définis
3. ⏳ Créer des endpoints réservés aux admins
4. ⏳ Implémenter la gestion des utilisateurs (CRUD) pour les admins
5. ⏳ Ajouter des logs d'audit pour les actions admin
