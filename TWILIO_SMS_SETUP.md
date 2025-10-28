# Configuration Twilio SMS pour l'envoi de notifications

## Vue d'ensemble

Ce projet utilise l'API Twilio pour envoyer des SMS de notification aux clients lors de la création de leur compte. L'intégration est **non-bloquante** : si l'envoi du SMS échoue, la création du compte se poursuit normalement.

## Prérequis

1. Compte Twilio actif (https://www.twilio.com/)
2. Numéro de téléphone Twilio configuré
3. Account SID et Auth Token de votre console Twilio

## Configuration

### 1. Variables d'environnement

Ajouter les variables suivantes dans votre fichier `.env` :

```env
TWILIO_ACCOUNT_SID=your_account_sid_here
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_PHONE_NUMBER=your_twilio_phone_number
MAIL_DISABLE_ON_RENDER=false
```

**Important** : Ne **jamais** commiter ces valeurs dans Git. Le fichier `.env` est dans `.gitignore`.

### 2. Récupérer vos identifiants Twilio

1. Connectez-vous à votre console Twilio : https://console.twilio.com/
2. Sur le Dashboard, copiez :
   - **Account SID** : identifiant de votre compte (commence par "AC...")
   - **Auth Token** : cliquez sur "Show" pour révéler le token
3. Allez dans **Phone Numbers** → **Manage** → **Active numbers**
4. Copiez votre numéro Twilio (format international : +1234567890)

### 3. Configuration sur Render

Si vous déployez sur Render, ajoutez ces variables d'environnement dans le dashboard :

1. Allez dans votre service Render
2. Onglet **Environment**
3. Ajoutez les variables :
   - `TWILIO_ACCOUNT_SID`
   - `TWILIO_AUTH_TOKEN`
   - `TWILIO_PHONE_NUMBER`
   - `MAIL_DISABLE_ON_RENDER` = `false`

## Utilisation

### Envoi automatique lors de la création de compte

Lorsqu'un compte est créé via l'endpoint `POST /api/v1/comptes`, le système :

1. Crée le compte et génère un code client
2. Déclenche l'événement `CompteCreated`
3. Le listener `SendClientNotification` envoie :
   - Un **SMS** via Twilio (si téléphone fourni)
   - Un **Email** via le serveur mail configuré (si email fourni)

**Comportement non-bloquant** :
- Si l'envoi SMS échoue (problème Twilio, crédits insuffisants, etc.), l'erreur est loggée mais la création du compte continue
- Idem pour l'email

### Test manuel avec curl

Pour tester l'API Twilio directement :

```bash
curl -X POST https://api.twilio.com/2010-04-01/Accounts/{YOUR_ACCOUNT_SID}/Messages.json \
  --data-urlencode "To=+221771234567" \
  --data-urlencode "From={YOUR_TWILIO_PHONE}" \
  --data-urlencode "Body=Test message" \
  -u {YOUR_ACCOUNT_SID}:{YOUR_AUTH_TOKEN}
```

Remplacez `{YOUR_ACCOUNT_SID}`, `{YOUR_AUTH_TOKEN}`, et `{YOUR_TWILIO_PHONE}` par vos vraies valeurs.

## Code implémenté

### Listener : SendClientNotification.php

```php
private function envoyerSMS($client, $code): void
{
    try {
        $twilioSid = env('TWILIO_ACCOUNT_SID');
        $twilioToken = env('TWILIO_AUTH_TOKEN');
        $twilioPhone = env('TWILIO_PHONE_NUMBER');
        
        if (!$twilioSid || !$twilioToken || !$twilioPhone) {
            Log::warning('Configuration Twilio incomplète');
            return;
        }

        $telephone = $client->telephone;
        if (!$telephone) {
            Log::info("Client {$client->id} sans téléphone, SMS non envoyé");
            return;
        }

        $message = "Bienvenue ! Votre code client est : {$code}";

        $response = Http::withBasicAuth($twilioSid, $twilioToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$twilioSid}/Messages.json", [
                'To' => $telephone,
                'From' => $twilioPhone,
                'Body' => $message,
            ]);

        if ($response->successful()) {
            Log::info("SMS envoyé au client {$client->id}");
        } else {
            Log::error("Erreur envoi SMS", ['response' => $response->json()]);
        }
    } catch (\Exception $e) {
        Log::error("Exception envoi SMS : " . $e->getMessage());
    }
}
```

## Logs

Les logs d'envoi SMS sont enregistrés dans `storage/logs/laravel.log` :

- **Success** : `SMS envoyé au client {id}`
- **Warning** : `Configuration Twilio incomplète`
- **Info** : `Client {id} sans téléphone, SMS non envoyé`
- **Error** : `Erreur envoi SMS` ou `Exception envoi SMS`

## Dépannage

### Erreur 401 Unauthorized
- Vérifiez que `TWILIO_ACCOUNT_SID` et `TWILIO_AUTH_TOKEN` sont corrects
- Assurez-vous qu'il n'y a pas d'espaces avant/après dans le fichier .env

### Erreur 21608 : The number is unverified
- En mode Trial, Twilio ne peut envoyer qu'à des numéros vérifiés
- Ajoutez le numéro destinataire dans **Phone Numbers** → **Verified Caller IDs**
- Ou passez à un compte payant

### SMS non reçu mais pas d'erreur
- Vérifiez les logs Twilio : https://console.twilio.com/us1/monitor/logs/sms
- Vérifiez que le format du numéro est international (+221...)
- Vérifiez que vous avez des crédits Twilio restants

### Configuration incomplète
- Si les logs montrent "Configuration Twilio incomplète", vérifiez que toutes les variables sont définies dans `.env`
- Rechargez la configuration : `php artisan config:clear`

## Sécurité

⚠️ **Ne jamais commiter les fichiers suivants** :
- `.env` (contient les secrets)
- Fichiers avec des valeurs hardcodées d'identifiants

✅ **Bonnes pratiques** :
- Utiliser `env()` pour récupérer les identifiants
- Garder `.env.example` avec des valeurs vides
- Ajouter les vraies valeurs uniquement dans les environnements de déploiement (Render, production)
- Utiliser des secrets managers en production (AWS Secrets Manager, HashiCorp Vault, etc.)

## Ressources

- Documentation Twilio API : https://www.twilio.com/docs/sms/api
- Console Twilio : https://console.twilio.com/
- Logs SMS : https://console.twilio.com/us1/monitor/logs/sms
- Pricing : https://www.twilio.com/sms/pricing
