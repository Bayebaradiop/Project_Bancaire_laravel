# Documentation Système de Blocage/Déblocage avec Archivage Neon

## 📋 Vue d'ensemble

Ce système gère le blocage et le déblocage automatique des comptes bancaires avec archivage dans une base de données cloud (Neon) séparée de la base principale (PostgreSQL).

## 🏗️ Architecture

### Bases de données

1. **PostgreSQL (Base principale - Render)**
   - Stocke les comptes actifs (statut: actif)
   - Comptes épargne et chèque en cours d'utilisation

2. **Neon (Base d'archivage - Cloud)**
   - Stocke les comptes bloqués archivés
   - Table: `comptes_archives`

### Flux de données

```
PostgreSQL (Actif) ←→ Neon (Archivé)
      ↓                    ↑
  Blocage              Déblocage
```

## ⚙️ Règles de gestion

### 1. Blocage de compte

#### Blocage immédiat (date = aujourd'hui)

Lorsqu'un admin bloque un compte avec `dateDebutBlocage` = aujourd'hui :

1. ✅ Le compte passe au statut "bloqué"
2. ✅ Le compte est archivé dans Neon
3. ✅ Le compte est supprimé de PostgreSQL (soft delete)
4. ✅ Message: "Compte bloqué avec succès et archivé dans Neon"

**Endpoint:**
```bash
POST /api/v1/comptes/{id}/bloquer
{
  "dateDebutBlocage": "2025-10-28",  # Aujourd'hui
  "dateFinBlocage": "2025-11-04",     # Optionnel
  "raison": "Inactivité prolongée"
}
```

#### Blocage programmé (date future)

Lorsqu'un admin bloque un compte avec `dateDebutBlocage` dans le futur :

1. ✅ Le compte reste au statut "actif"
2. ✅ Le champ `blocage_programme` passe à `true`
3. ✅ Le compte reste dans PostgreSQL
4. ✅ Message: "Ce compte sera bloqué le DD/MM/YYYY"

**Endpoint:**
```bash
POST /api/v1/comptes/{id}/bloquer
{
  "dateDebutBlocage": "2025-11-05",  # Date future
  "dateFinBlocage": "2025-11-12",
  "raison": "Blocage planifié"
}
```

### 2. Déblocage automatique

Lorsque la `dateFinBlocage` d'un compte archivé arrive :

1. ✅ Le compte est restauré dans PostgreSQL
2. ✅ Le statut passe à "actif"
3. ✅ Le compte est supprimé de Neon
4. ✅ Tous les champs de blocage sont remis à `null`

**Job:** `DebloquerComptesJob` (exécuté quotidiennement à minuit)

### 3. Validations

#### ❌ Tentative de bloquer un compte déjà bloqué

Réponse:
```json
{
  "success": false,
  "message": "Ce compte est déjà bloqué et se trouve dans la base d'archivage (Neon)",
  "http_code": 400
}
```

#### ❌ Tentative de bloquer un compte inexistant

Réponse:
```json
{
  "success": false,
  "message": "Ce compte n'existe pas",
  "http_code": 404
}
```

#### ❌ Tentative de bloquer un compte chèque

Réponse:
```json
{
  "success": false,
  "message": "Seuls les comptes épargne peuvent être bloqués. Les comptes chèque ne peuvent pas être bloqués.",
  "http_code": 400
}
```

## 🤖 Jobs automatiques

### BloquerComptesEpargneJob

**Fréquence:** Quotidiennement à minuit

**Fonction:** Bloquer et archiver les comptes dont la date de blocage programmé est arrivée

**Processus:**
1. Trouve tous les comptes avec `blocage_programme = true` et `dateDebutBlocage <= aujourd'hui`
2. Met à jour le statut à "bloqué"
3. Archive dans Neon via `CompteArchiveService`
4. Supprime de PostgreSQL (soft delete)

**Log:**
```
✅ Compte épargne bloqué automatiquement et archivé
   - compte_id: xxx
   - numeroCompte: CPxxxxxxxxxx
   - dateDebutBlocage: 2025-10-28
```

### DebloquerComptesJob

**Fréquence:** Quotidiennement à minuit

**Fonction:** Débloquer et restaurer les comptes dont la date de fin de blocage est arrivée

**Processus:**
1. Trouve tous les comptes dans Neon avec `statut = bloque` et `dateFinBlocage <= aujourd'hui`
2. Restaure le compte dans PostgreSQL
3. Met à jour le statut à "actif"
4. Supprime de Neon

**Log:**
```
✅ Compte débloqué et restauré depuis Neon
   - compte_id: xxx
   - numeroCompte: CPxxxxxxxxxx
   - dateFinBlocage: 2025-11-04
```

## 📡 Endpoints API

### Afficher les comptes actifs

```bash
GET /api/v1/comptes
Authorization: Bearer {token}
```

**Règle de filtrage (US 2.0) :**
> "Liste compte non supprimés type cheque ou compte Epargne Actif"

**Résultat:** 
- ✅ Tous les comptes **CHÈQUE** (actif, bloqué, fermé) NON archivés
- ✅ Comptes **ÉPARGNE ACTIFS** uniquement NON archivés

**Exemples de comptes affichés :**
```json
{
  "success": true,
  "data": [
    // ✅ Compte chèque ACTIF
    {"type": "cheque", "statut": "actif", "numeroCompte": "CP1234567890"},
    
    // ✅ Compte chèque BLOQUÉ (visible car type = chèque)
    {"type": "cheque", "statut": "bloque", "numeroCompte": "CP0987654321"},
    
    // ✅ Compte chèque FERMÉ (visible car type = chèque)
    {"type": "cheque", "statut": "ferme", "numeroCompte": "CP5555555555"},
    
    // ✅ Compte épargne ACTIF
    {"type": "epargne", "statut": "actif", "numeroCompte": "CP1111111111"}
    
    // ❌ Compte épargne BLOQUÉ (NON visible - archivé dans Neon)
    // ❌ Compte épargne FERMÉ (NON visible)
  ]
}
```

### Afficher les comptes archivés

```bash
GET /api/v1/comptes/archive
Authorization: Bearer {token}
```

**Résultat:** Liste les comptes archivés depuis Neon

### Récupérer un compte par ID

```bash
GET /api/v1/comptes/{id}
Authorization: Bearer {token}
```

**Recherche:**
1. D'abord dans PostgreSQL
2. Si non trouvé, dans Neon
3. Affiche le compte dans les deux cas

## 🔧 Configuration Scheduler

Dans `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Bloquer les comptes programmés
    $schedule->job(new BloquerComptesEpargneJob())
        ->daily()
        ->withoutOverlapping()
        ->runInBackground();

    // Débloquer les comptes
    $schedule->job(new DebloquerComptesJob())
        ->daily()
        ->withoutOverlapping()
        ->runInBackground();
}
```

**Pour activer le scheduler en production:**
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## 🧪 Tests

Exécuter le script de test:
```bash
chmod +x test_blocage_neon.sh
./test_blocage_neon.sh
```

**Tests couverts:**
- ✅ Blocage immédiat avec archivage Neon
- ✅ Vérification retrait de PostgreSQL
- ✅ Vérification présence dans Neon
- ✅ Récupération compte archivé par ID
- ✅ Protection re-blocage
- ✅ Blocage programmé
- ✅ Validation comptes chèque

## 📊 Modèle de données

### Table `comptes` (PostgreSQL)

```sql
- id (uuid)
- numeroCompte (varchar)
- type (enum: epargne, cheque)
- statut (enum: actif, bloque, ferme)
- blocage_programme (boolean)
- dateDebutBlocage (timestamp nullable)
- dateFinBlocage (timestamp nullable)
- dateBlocage (timestamp nullable)
- motifBlocage (text nullable)
- archived_at (timestamp nullable)
- deleted_at (timestamp nullable)
```

### Table `comptes_archives` (Neon)

```sql
- id (uuid)
- numerocompte (varchar)
- statut (varchar)
- motifblocage (text)
- dateFinBlocage (timestamp nullable)
- archived_at (timestamp)
- archived_by (uuid nullable)
- archive_reason (text)
```

## 🔐 Permissions

- **Admin:** Peut bloquer/débloquer n'importe quel compte
- **Client:** Peut uniquement consulter ses propres comptes

## 📝 Notes importantes

1. **Seuls les comptes épargne** peuvent être bloqués
2. Les comptes **chèque ne peuvent jamais être bloqués**
3. Le blocage avec date future **ne change pas le statut** (reste actif)
4. Un compte bloqué **n'apparaît plus dans `/api/v1/comptes`**
5. Un compte bloqué **apparaît dans `/api/v1/comptes/archive`**
6. Un compte peut être récupéré par ID **même s'il est archivé**

## 🚀 Déploiement

1. Migrer les tables dans Neon
2. Configurer les variables d'environnement Neon
3. Tester les endpoints manuellement
4. Activer le cron pour le scheduler
5. Vérifier les logs quotidiennement

## 📞 Support

Pour toute question sur ce système, consulter:
- `app/Services/CompteService.php::bloquerCompte()`
- `app/Services/CompteArchiveService.php`
- `app/Jobs/BloquerComptesEpargneJob.php`
- `app/Jobs/DebloquerComptesJob.php`
