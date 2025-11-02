<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\Info(
 *     version="2.1.0",
 *     title="API Bancaire Faysany - Architecture Hybride MongoDB Atlas + PostgreSQL Neon",
 *     description="Documentation complète de l'API RESTful de gestion bancaire avec architecture microservices distribuée.

**🏗️ ARCHITECTURE TECHNIQUE :**

**Base de données hybride distribuée :**
- 🐘 **PostgreSQL Neon (Production)** : Users, Clients, Comptes bancaires (données relationnelles)
  * Serveur : dpg-d3t5riu3jp1c738hsgrg-a.oregon-postgres.render.com
  * Base : db_ati7
  * Features : Serverless, Auto-scaling, Point-in-time recovery
  
- 🍃 **MongoDB Atlas (Cloud)** : Transactions financières (scalabilité + performances)
  * Cluster : Cluster0 (M0 Free tier)
  * Région : eu-south-2 (Spain)
  * Base : transactions_db
  * Features : Sharding ready, Time-series optimization
  
- ☁️ **Neon Archive (Cloud)** : Comptes archivés (fermés/bloqués)
  * Serveur : ep-crimson-river-afrihxt0-pooler.c-2.us-west-2.aws.neon.tech
  * Base : neondb
  * Purpose : Long-term storage des comptes inactifs
  
- 🚀 **Render Hosting** : Application Laravel
  * Docker + PHP 8.3-fpm + Nginx + Supervisor
  * Extensions : MongoDB 1.20.1, PostgreSQL PDO
  * Queue Worker pour jobs asynchrones

**📧 SYSTÈME EMAIL (Brevo API) :**
- ✅ **Brevo API** pour contourner le blocage des ports SMTP (587/465) sur Render
- ✅ Email automatique de bienvenue lors de la création de compte
- ✅ Contenu : Numéro de compte, mot de passe auto-généré, code de sécurité
- ✅ Emails envoyés de manière asynchrone (non-bloquant)

**🔐 SÉCURITÉ PREMIÈRE CONNEXION CLIENT :**
- ✅ Code de sécurité à 6 chiffres requis pour la 1ère connexion client
- ✅ Le code est envoyé par email avec le mot de passe
- ✅ Le code est supprimé automatiquement après la 1ère connexion réussie
- ⚠️ Erreur 403 si le code est manquant ou invalide

**💰 INITIALISATION DES COMPTES :**
- ✅ Dépôt initial automatique de 50000 FCFA à la création du compte
- ✅ Transaction MongoDB créée avec type 'depot' et statut 'complete'
- ✅ Le solde est immédiatement disponible

**Calculs cross-database (PostgreSQL ↔ MongoDB) :**
- Les soldes sont calculés en temps réel depuis MongoDB Atlas
- Conversion automatique MongoDB Decimal128 → PHP Float
- Validation des retraits/transferts avec solde calculé depuis MongoDB
- Transactions cross-DB avec gestion d'erreurs robuste

**COMMENT UTILISER L'AUTHENTIFICATION DANS SWAGGER UI :**

**Étape 1 : Se connecter**
- Allez à l'endpoint POST /auth/login
- **Pour un client (1ère connexion)** : email + password + **code de sécurité**
- **Pour un admin** : email + password uniquement
- Cliquez sur 'Execute'
- Copiez le access_token de la réponse

**Étape 2 : Autoriser Swagger UI**
- Cliquez sur le bouton 'Authorize' 🔒 (en haut à droite de la page)
- Collez votre token dans le champ 'Value'
- Cliquez sur 'Authorize' puis 'Close'

**Étape 3 : Tester les endpoints protégés**
- Maintenant tous vos appels incluront automatiquement le Bearer token
- Le cadenas 🔒 à côté de chaque endpoint sera verrouillé

**IDENTIFIANTS DE TEST (créés par le seeder) :**

Admin :
- Email : admin@banque.sn
- Password : Admin@2025
- Accès : Tous les comptes et opérations

Client :
- Email : client@banque.sn
- Password : Client@2025
- Accès : Uniquement ses propres comptes

**📊 DONNÉES DE TEST :**
- 10 comptes actifs dans PostgreSQL
- 269+ transactions dans MongoDB Atlas
- Soldes calculés en temps réel depuis MongoDB

**NOTE TECHNIQUE :**
- Les clients web utilisent un cookie HttpOnly sécurisé
- Pour Swagger UI, utilisez le bouton 'Authorize' avec le Bearer Token JWT
- Pour automatiser vos tests : Postman, Insomnia ou cURL
- Les transactions retournent l'ID MongoDB (_id) au format ObjectId",
 *     @OA\Contact(
 *         email="bayebara2000@gmail.com",
 *         name="Support Faysany Banque"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1",
 *     description="Production Render + MongoDB Atlas + PostgreSQL Neon"
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Développement local"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="cookieAuth",
 *     type="apiKey",
 *     in="cookie",
 *     name="token",
 *     description="Authentification JWT stockée dans un cookie HttpOnly sécurisé. Le token est automatiquement envoyé avec chaque requête."
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="CLIQUEZ SUR 'Authorize' 🔒 EN HAUT → Collez votre token (sans 'Bearer') → Validez. Pour obtenir un token : POST /v1/auth/login avec admin@banque.sn / Admin@2025"
 * )
 * 
 * @OA\Tag(
 *     name="Authentification",
 *     description="Endpoints pour l'authentification des utilisateurs (Admin/Client). Utilise JWT avec cookies HttpOnly pour une sécurité renforcée."
 * )
 * 
 * @OA\Tag(
 *     name="Transactions MongoDB",
 *     description="🍃 Gestion des transactions stockées dans MongoDB Atlas - Dépôts, Retraits, Transferts avec calcul automatique des frais (0.5%). Total: 274+ transactions."
 * )
 * 
 * @OA\Tag(
 *     name="Comptes PostgreSQL",
 *     description="🐘 Gestion des comptes bancaires actifs stockés dans PostgreSQL Neon (Production) : création, consultation, listing avec filtres, blocage/déblocage, archivage automatique. Soldes calculés en temps réel depuis MongoDB Atlas."
 * )
 * 
 * @OA\Tag(
 *     name="Archives",
 *     description="☁️ Gestion des comptes archivés dans Neon Cloud Archive. Système de sauvegarde long-terme pour les comptes fermés ou bloqués définitivement. Permet la consultation historique et la restauration si nécessaire."
 * )
 */
class SwaggerAnnotations
{
    // Ce fichier contient uniquement les annotations Swagger globales
}
