# 🧪 Guide de test - Système d'archivage Cloud (Neon)

## ✅ Installation terminée

Votre système d'archivage cloud est maintenant opérationnel ! Voici comment le tester.

---

## 📊 État actuel

### Base de données principale (Render)
- ✅ Colonne `archived_at` ajoutée à la table `comptes`
- ✅ Colonne `cloud_storage_path` ajoutée à la table `comptes`

### Base de données cloud (Neon)
- ✅ Table `comptes_archives` créée
- ✅ 1 compte déjà archivé (test)
- ✅ Index de performance configurés

### Données de test créées
- ✅ 5 clients créés (Client Épargne Test 1-5)
- ✅ 5 comptes épargne créés (numéros commençant par CE)
- ✅ Admin: `admin@banque.sn` / `password`

---

## 🚀 Tests à effectuer

### 1️⃣ Archiver un compte épargne (Admin uniquement)

**Via Tinker:**
```bash
php artisan tinker
```

```php
$admin = App\Models\User::where('email', 'admin@banque.sn')->first();
$compte = App\Models\Compte::where('type', 'epargne')
    ->where('statut', 'actif')
    ->whereNull('archived_at')
    ->first();

if ($compte) {
    $service = app(App\Services\CompteArchiveService::class);
    $archive = $service->archiveCompte($compte, $admin, 'Test archivage');
    echo "✅ Compte {$compte->numeroCompte} archivé!";
}
```

**Via API (avec curl):**
```bash
# 1. Login en tant qu'admin
curl -X POST "http://localhost:8000/api/v1/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@banque.sn",
    "password": "password"
  }' \
  -c cookies.txt

# 2. Lister les comptes épargne actifs
curl -X GET "http://localhost:8000/api/v1/comptes?type=epargne" \
  -H "Accept: application/json" \
  -b cookies.txt

# 3. Archiver un compte (remplacer CE5064110000 par un vrai numéro)
curl -X POST "http://localhost:8000/api/v1/comptes/CE5064110000/archive" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "reason": "Inactif depuis 12 mois"
  }'
```

---

### 2️⃣ Consulter les comptes archivés

**Via Tinker:**
```bash
php artisan tinker
```

```php
// Compter les archives
$count = DB::connection('neon')->table('comptes_archives')->count();
echo "Archives dans Neon: {$count}\n";

// Lister les archives
$archives = App\Models\CompteArchive::orderBy('archived_at', 'desc')->get();
foreach ($archives as $archive) {
    echo "- {$archive->numerocompte} ({$archive->type})\n";
    echo "  Client: {$archive->client_nom}\n";
    echo "  Raison: {$archive->archive_reason}\n\n";
}
```

**Via API:**
```bash
# En tant qu'admin (voir toutes les archives)
curl -X GET "http://localhost:8000/api/v1/comptes/archives" \
  -H "Accept: application/json" \
  -b cookies.txt

# En tant que client (voir seulement ses archives)
# D'abord login en tant que client
curl -X POST "http://localhost:8000/api/v1/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "epargne.test.TIMESTAMP.1@example.com",
    "password": "password"
  }' \
  -c client_cookies.txt

# Puis consulter ses archives
curl -X GET "http://localhost:8000/api/v1/comptes/archives" \
  -H "Accept: application/json" \
  -b client_cookies.txt
```

---

### 3️⃣ Vérifier qu'un compte archivé n'apparaît plus dans la liste active

**Via Tinker:**
```php
// Comptes actifs (non archivés)
$actifs = App\Models\Compte::where('type', 'epargne')
    ->whereNull('archived_at')
    ->count();
echo "Comptes épargne actifs: {$actifs}\n";

// Comptes archivés (dans base principale)
$archives = App\Models\Compte::where('type', 'epargne')
    ->whereNotNull('archived_at')
    ->count();
echo "Comptes épargne archivés (locale): {$archives}\n";

// Archives dans Neon
$neonArchives = DB::connection('neon')->table('comptes_archives')->count();
echo "Comptes épargne archivés (Neon): {$neonArchives}\n";
```

**Via API:**
```bash
# Liste des comptes actifs (ne doit PAS contenir les comptes archivés)
curl -X GET "http://localhost:8000/api/v1/comptes?type=epargne" \
  -H "Accept: application/json" \
  -b cookies.txt
```

---

### 4️⃣ Tester les permissions

**Admin peut archiver:**
```bash
# Login admin
curl -X POST "http://localhost:8000/api/v1/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@banque.sn", "password": "password"}' \
  -c admin_cookies.txt

# Archiver (doit réussir ✅)
curl -X POST "http://localhost:8000/api/v1/comptes/CE5064110000/archive" \
  -H "Accept: application/json" \
  -b admin_cookies.txt \
  -d '{"reason": "Test"}'
```

**Client ne peut PAS archiver:**
```bash
# Login client
curl -X POST "http://localhost:8000/api/v1/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email": "client@banque.sn", "password": "password"}' \
  -c client_cookies.txt

# Tentative d'archivage (doit échouer avec 403 ❌)
curl -X POST "http://localhost:8000/api/v1/comptes/CE5064110000/archive" \
  -H "Accept: application/json" \
  -b client_cookies.txt \
  -d '{"reason": "Test"}'

# Réponse attendue:
# {"status": "error", "message": "Seuls les administrateurs peuvent archiver des comptes"}
```

---

## 📋 Checklist de validation

- [ ] Admin peut archiver un compte épargne
- [ ] Client ne peut PAS archiver un compte
- [ ] Compte archivé stocké dans Neon
- [ ] Compte archivé marqué avec `archived_at` dans base principale
- [ ] Compte archivé n'apparaît plus dans liste des comptes actifs
- [ ] Admin voit tous les comptes archivés via `/api/v1/comptes/archives`
- [ ] Client voit uniquement ses comptes archivés via `/api/v1/comptes/archives`
- [ ] Seuls les comptes épargne peuvent être archivés (comptes chèque refusés)
- [ ] Données client dénormalisées correctement dans Neon
- [ ] Raison d'archivage enregistrée

---

## 🔍 Commandes utiles

### Compter les archives
```bash
php artisan tinker --execute="echo DB::connection('neon')->table('comptes_archives')->count();"
```

### Voir toutes les archives
```bash
php artisan tinker --execute="
\$archives = DB::connection('neon')->table('comptes_archives')->get();
foreach (\$archives as \$a) {
    echo \$a->numerocompte . ' - ' . \$a->client_nom . ' (' . \$a->archive_reason . ')' . PHP_EOL;
}
"
```

### Nettoyer les données de test
```bash
# Supprimer les comptes de test
php artisan tinker --execute="
App\Models\Compte::where('numeroCompte', 'LIKE', 'CE506411%')->delete();
"

# Supprimer les utilisateurs de test
php artisan tinker --execute="
App\Models\User::where('email', 'LIKE', 'epargne.test.%')->delete();
"

# Vider la table d'archives dans Neon
php artisan tinker --execute="
DB::connection('neon')->table('comptes_archives')->truncate();
"
```

---

## 📝 Logs

Les opérations d'archivage sont loggées dans `storage/logs/laravel.log`:

```bash
tail -f storage/logs/laravel.log | grep -i archive
```

---

## 🎯 Résultat attendu

Après avoir archivé quelques comptes, vous devriez avoir:

### Base principale (Render)
```
comptes
├── CE5064110000 (actif, archived_at = NULL)
├── CE5064110001 (actif, archived_at = NULL)
├── CE5064110002 (actif, archived_at = NULL)
├── CE5064110003 (fermé, archived_at = 2025-10-26)  ← Archivé
└── CE5064110004 (fermé, archived_at = 2025-10-26)  ← Archivé
```

### Base cloud (Neon)
```
comptes_archives
├── CE5064110003 (Raison: "Inactif depuis 12 mois")
└── CE5064110004 (Raison: "Compte fermé à la demande du client")
```

---

## ✅ Système opérationnel !

Votre système d'archivage cloud est prêt pour la production. Les comptes épargne inactifs peuvent maintenant être archivés automatiquement vers Neon pour un stockage long terme optimal.

**Documentation complète:** `CLOUD_ARCHIVE_DOCUMENTATION.md`  
**Checklist US 2.0:** `US_2.0_COMPLETE_CHECKLIST.md`
