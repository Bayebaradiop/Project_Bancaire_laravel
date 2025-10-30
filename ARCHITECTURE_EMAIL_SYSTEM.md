# 📧 Architecture du Système d'Envoi d'Emails - Faysany Banque

> **Documentation complète du système d'envoi automatique d'emails et SMS lors de la création de comptes**

---

## 🏗️ Architecture Globale

```
┌─────────────────────────────────────────────────────────────────┐
│                    CRÉATION D'UN COMPTE                          │
│                  POST /api/v1/comptes                            │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│              CompteController@store()                            │
│  • Valide les données                                            │
│  • Stocke password/code en session                               │
│  • Appelle CompteService→creerCompte()                          │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│              CompteService→creerCompte()                        │
│  • Crée ou récupère le Client                                    │
│  • Crée le Compte (Eloquent)                                     │
│  • Déclenche automatiquement CompteObserver                      │
│  • Lance l'Event CompteCreated                                   │
└────────────────────────┬────────────────────────────────────────┘
                         │
           ┌─────────────┴──────────────┐
           │                            │
           ▼                            ▼
┌──────────────────────┐    ┌──────────────────────────┐
│  CompteObserver      │    │   Event: CompteCreated   │
│  →creating()         │    │   Transporte:            │
│  Génère numéro       │    │   • Compte               │
│  de compte           │    │   • Password             │
│                      │    │   • Code SMS             │
│  →created()          │    └─────────┬────────────────┘
│  Archivage si        │              │
│  statut fermé/bloqué │              │
└──────────────────────┘              │
                                      ▼
                    ┌────────────────────────────────┐
                    │ Listener: SendClientNotification│
                    │ implements ShouldQueue         │
                    │                                │
                    │ • Queue: default               │
                    │ • Tries: 3                     │
                    │ • Non-bloquant                 │
                    └─────────┬──────────────────────┘
                              │
                ┌─────────────┴──────────────┐
                │                            │
                ▼                            ▼
    ┌─────────────────────┐    ┌─────────────────────┐
    │  envoyerEmail()     │    │  envoyerSMS()       │
    │                     │    │                     │
    │  CompteCreatedMail  │    │  Twilio API         │
    │  Vue: emails/       │    │  +221XXXXXXXXX      │
    │  compte-created     │    │  Code: XXXX         │
    │                     │    │                     │
    │  Gmail SMTP         │    │  Non-bloquant       │
    │  bayebara2000@      │    │  Gestion d'erreur   │
    │  gmail.com          │    │                     │
    └─────────────────────┘    └─────────────────────┘
```

---

## 📁 Structure des Fichiers

### 1️⃣ **Observer** : `app/Observers/CompteObserver.php`

**Rôle** : Écouter les événements du modèle `Compte`

**Méthodes** :
- `creating()` : Génère automatiquement le numéro de compte avant la création
- `created()` : Archive automatiquement si statut = "fermé" ou "bloqué"
- `updated()` : Archive automatiquement si changement de statut vers "fermé" ou "bloqué"

**Enregistrement** : `App\Providers\AppServiceProvider::boot()`
```php
Compte::observe(CompteObserver::class);
```

---

### 2️⃣ **Event** : `app/Events/CompteCreated.php`

**Rôle** : Transporter les données du compte créé vers les listeners

**Propriétés publiques** :
```php
public $compte;    // Instance du modèle Compte
public $password;  // Mot de passe temporaire du client
public $code;      // Code de validation SMS
```

**Déclenchement** : Dans `CompteService@creerCompte()` après création réussie
```php
event(new CompteCreated($compte, $password, $code));
```

---

### 3️⃣ **Listener** : `app/Listeners/SendClientNotification.php`

**Rôle** : Envoyer automatiquement Email + SMS de manière asynchrone

**Caractéristiques** :
- ✅ Implémente `ShouldQueue` (exécution en arrière-plan)
- ✅ Queue : `default`
- ✅ Retry : 3 tentatives en cas d'échec
- ✅ Non-bloquant : si email/SMS échoue, la création du compte continue
- ✅ Gestion d'erreurs avec logs détaillés

**Méthodes** :
- `handle(CompteCreated $event)` : Point d'entrée principal
- `envoyerEmail()` : Envoie l'email avec le mot de passe
- `envoyerSMS()` : Envoie le code SMS via Twilio

**Enregistrement** : `App\Providers\EventServiceProvider::$listen`
```php
CompteCreated::class => [
    SendClientNotification::class,
],
```

---

### 4️⃣ **Mailable** : `app/Mail/CompteCreatedMail.php`

**Rôle** : Construire l'email de bienvenue

**Données transmises à la vue** :
- `$compte` : Objet Compte complet (numeroCompte, solde, type, etc.)
- `$password` : Mot de passe temporaire
- `$code` : Code de validation SMS (optionnel)

**Configuration** :
- **Sujet** : "Bienvenue sur Faysany Banque - Votre compte a été créé"
- **Vue** : `resources/views/emails/compte-created.blade.php`
- **Expéditeur** : Configuré dans `.env` (MAIL_FROM_ADDRESS)

---

### 5️⃣ **Vue Email** : `resources/views/emails/compte-created.blade.php`

**Contenu de l'email** :
- Salutation personnalisée avec le nom du client
- Numéro de compte créé
- Mot de passe temporaire (à changer)
- Code de validation SMS
- Instructions de connexion
- Coordonnées de la banque

**Accès aux variables** :
```blade
{{ $compte->numeroCompte }}
{{ $compte->client->user->nomComplet }}
{{ $password }}
{{ $code }}
```

---

## ⚙️ Configuration (.env)

### Email (Gmail SMTP)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-email@gmail.com
MAIL_PASSWORD=votre-app-password-gmail
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=votre-email@gmail.com
MAIL_FROM_NAME="Faysany Banque"
```

### SMS (Twilio)
```env
TWILIO_ACCOUNT_SID=votre-twilio-account-sid
TWILIO_AUTH_TOKEN=votre-twilio-auth-token
TWILIO_PHONE_NUMBER=+221XXXXXXXXX
```

### Queue
```env
QUEUE_CONNECTION=database
```

---

## 🔄 Flux d'Exécution Détaillé

### Étape 1 : Requête API
```bash
POST /api/v1/comptes
Authorization: Bearer {token}

{
  "type": "epargne",
  "devise": "FCFA",
  "client": {
    "titulaire": "Jean Dupont",
    "nci": "1234567890123",
    "email": "jean@example.com",
    "telephone": "+221771234567"
  }
}
```

### Étape 2 : Controller (Synchrone)
```php
CompteController@store():
1. Valide les données (FormRequest)
2. Génère password aléatoire
3. Génère code SMS 4 chiffres
4. Stocke en session temporaire
5. Appelle CompteService→creerCompte()
```

### Étape 3 : Service (Synchrone)
```php
CompteService@creerCompte():
1. Crée ou récupère le Client (findOrCreateClient)
2. Crée le User associé avec password hashé
3. Crée le Compte avec relation Client
4. Lance Event: CompteCreated
5. Retourne le compte créé
```

### Étape 4 : Observer (Synchrone - avant/après save)
```php
CompteObserver:
→ creating() : Génère numeroCompte si absent
→ created()  : Archive si statut = fermé/bloqué
```

### Étape 5 : Event → Listener (Asynchrone via Queue)
```php
CompteCreated dispatché
→ SendClientNotification ajouté à la queue 'default'
→ Le controller retourne la réponse HTTP immédiatement
```

### Étape 6 : Queue Worker (Asynchrone)
```bash
# Sur le serveur (Render ou local)
php artisan queue:work

→ Lit la table 'jobs'
→ Exécute SendClientNotification::handle()
  ├─ envoyerEmail() via Gmail SMTP
  └─ envoyerSMS() via Twilio API
```

---

## 🧪 Comment Tester

### Test Local (avec Mailtrap ou Log)

1. **Configurer .env pour test**
```env
MAIL_MAILER=log
QUEUE_CONNECTION=sync  # Pas de queue pour debug
```

2. **Créer un compte via Swagger**
```
POST /api/v1/comptes
```

3. **Vérifier les logs**
```bash
tail -f storage/logs/laravel.log
```

Vous devriez voir :
```
📧 Email de bienvenue envoyé avec succès
📱 SMS envoyé avec succès via Twilio
```

### Test Production (Render)

1. **Vérifier que le queue worker tourne**
```bash
# Via Render Shell
ps aux | grep queue:work
```

2. **Créer un compte réel**
```bash
./test_email_production.sh
```

3. **Vérifier la réception**
- Email arrive dans la boîte `bayebara2000@gmail.com`
- SMS arrive sur le numéro +221XXXXXXXXX

4. **Monitorer les jobs**
```sql
-- Jobs en attente
SELECT * FROM jobs;

-- Jobs échoués
SELECT * FROM failed_jobs;
```

---

## 🐛 Debugging

### Email ne part pas

**Vérifier** :
1. Queue worker actif : `ps aux | grep queue:work`
2. Configuration SMTP : `php artisan config:cache`
3. Logs Laravel : `tail -f storage/logs/laravel.log`
4. Failed jobs : `php artisan queue:failed`

**Solutions** :
```bash
# Relancer queue worker
php artisan queue:restart

# Réessayer les jobs échoués
php artisan queue:retry all

# Vider la queue
php artisan queue:flush
```

### SMS ne part pas

**Vérifier** :
1. Credentials Twilio dans `.env`
2. Numéro de téléphone au format international : `+221XXXXXXXXX`
3. Logs Twilio : https://console.twilio.com

---

## 📊 Tables de la Queue

### Table `jobs`
Stocke les jobs en attente d'exécution

```sql
SELECT id, queue, payload, attempts, created_at 
FROM jobs 
ORDER BY id DESC 
LIMIT 10;
```

### Table `failed_jobs`
Stocke les jobs qui ont échoué après 3 tentatives

```sql
SELECT id, connection, queue, exception, failed_at 
FROM failed_jobs 
ORDER BY id DESC;
```

---

## 🔐 Sécurité

### Bonnes Pratiques Appliquées

✅ **Mot de passe hashé** : Jamais stocké en clair (bcrypt)
✅ **Code SMS temporaire** : Non stocké en base (session temporaire)
✅ **Gestion d'erreurs** : Try/catch avec logs détaillés
✅ **Non-bloquant** : Email/SMS n'empêchent pas la création
✅ **Retry automatique** : 3 tentatives en cas d'échec
✅ **HTTPS** : Toutes les communications sécurisées
✅ **App Password Gmail** : Pas de mot de passe principal

---

## 🚀 Déploiement Production (Render)

### Configuration Supervisor

Fichier : `docker/supervisor/supervisord.conf`

```ini
[program:queue-worker]
process_name=%(program_name)s
command=php /var/www/html/artisan queue:work database --sleep=3 --tries=3 --max-time=1800 --memory=128 --max-jobs=100
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=1
user=www-data
stdout_logfile=/var/www/html/storage/logs/queue-worker.log
stderr_logfile=/var/www/html/storage/logs/queue-worker-error.log
```

**Optimisations** :
- `--memory=128` : Limite mémoire (Render free tier)
- `--max-jobs=100` : Restart après 100 jobs (évite memory leaks)
- `--max-time=1800` : Restart après 30 min
- `numprocs=1` : 1 seul worker (économie de RAM)

---

## 📈 Monitoring

### Commandes Utiles

```bash
# Voir les jobs en cours
php artisan queue:work --once --verbose

# Statistiques de la queue
php artisan queue:monitor default

# Nettoyer les jobs échoués
php artisan queue:flush

# Relancer tous les jobs échoués
php artisan queue:retry all

# Voir les logs du worker
tail -f storage/logs/queue-worker.log
```

### Logs à Surveiller

```bash
# Logs Laravel
storage/logs/laravel.log

# Logs Queue Worker (Render)
storage/logs/queue-worker.log
storage/logs/queue-worker-error.log
```

---

## 🎯 Checklist de Vérification

Avant de déployer en production :

- [ ] `.env` configuré avec vraies credentials Gmail
- [ ] `.env` configuré avec vraies credentials Twilio
- [ ] Queue connection = `database`
- [ ] Supervisor configuré et actif sur Render
- [ ] Migration `jobs` et `failed_jobs` exécutée
- [ ] Test email envoyé et reçu
- [ ] Test SMS envoyé et reçu
- [ ] Logs accessibles et lisibles
- [ ] Failed jobs table vide
- [ ] Queue worker ne crash pas (mémoire OK)

---

## 🔄 Évolution Future

### Améliorations Possibles

1. **Email HTML plus riche**
   - Logo de la banque
   - Styles CSS modernes
   - Bouton CTA "Se connecter"

2. **Notifications multiples**
   - Email de confirmation après 1ère connexion
   - Email de changement de mot de passe
   - SMS pour transactions importantes

3. **Monitoring avancé**
   - Dashboard Laravel Horizon (Redis)
   - Alertes si queue trop longue
   - Métriques de performance

4. **Tests automatisés**
   - Tests unitaires pour Mailable
   - Tests d'intégration pour Listener
   - Mock Twilio pour CI/CD

---

## 📚 Ressources

- [Laravel Events](https://laravel.com/docs/10.x/events)
- [Laravel Queues](https://laravel.com/docs/10.x/queues)
- [Laravel Mail](https://laravel.com/docs/10.x/mail)
- [Twilio PHP SDK](https://www.twilio.com/docs/libraries/php)
- [Gmail SMTP Setup](https://support.google.com/mail/answer/7126229)

---

**📝 Dernière mise à jour** : 30 octobre 2025  
**👨‍💻 Mainteneur** : Équipe Faysany Banque  
**🔖 Version** : 2.0 (System unifié Event/Listener)
