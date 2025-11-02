<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\Info(
 *     version="2.0.0",
 *     title="API Bancaire Faysany - Architecture Hybride MongoDB Atlas + PostgreSQL",
 *     description="Documentation complète de l'API RESTful de gestion bancaire avec architecture microservices.

**🏗️ ARCHITECTURE TECHNIQUE :**

**Base de données hybride :**
- 🐘 **PostgreSQL Neon** : Users, Clients, Comptes (relationnelles)
- 🍃 **MongoDB Atlas** : Transactions (scalabilité + performances)
- 🚀 **Render Hosting** : Docker + PHP 8.3 + Nginx + Supervisor

**Calculs cross-database :**
- Les soldes sont calculés en temps réel depuis MongoDB
- Conversion automatique Decimal128 → Float
- Validation des retraits/transferts avec solde MongoDB

**COMMENT UTILISER L'AUTHENTIFICATION DANS SWAGGER UI :**

**Étape 1 : Se connecter**
- Allez à l'endpoint POST /v1/auth/login
- Utilisez les identifiants de test (voir ci-dessous)
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
 *     url="https://baye-bara-diop-project-bancaire-laravel.onrender.com/api",
 *     description="Production Render + MongoDB Atlas + PostgreSQL Neon"
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api",
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
 *     description="🐘 Gestion des comptes bancaires stockés dans PostgreSQL Neon : création, consultation, listing avec filtres, archivage cloud. Soldes calculés depuis MongoDB."
 * )
 * 
 * @OA\Tag(
 *     name="Archives",
 *     description="Gestion des comptes archivés dans le cloud (Neon). Permet de consulter les comptes fermés ou bloqués qui ont été transférés vers le système d'archivage."
 * )
 */
class SwaggerAnnotations
{
    // Ce fichier contient uniquement les annotations Swagger globales
}
