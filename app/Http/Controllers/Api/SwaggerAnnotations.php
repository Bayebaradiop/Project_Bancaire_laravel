<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="API Bancaire - Documentation Complète",
 *     description="Documentation de l'API RESTful de gestion bancaire.

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

**NOTE TECHNIQUE :**
- Les clients web utilisent un cookie HttpOnly sécurisé (invisible pour Swagger UI)
- Pour Swagger UI, utilisez le bouton 'Authorize' avec le Bearer Token JWT
- Pour automatiser vos tests : Postman, Insomnia ou cURL",
 *     @OA\Contact(
 *         email="support@banque.sn",
 *         name="Support API Bancaire"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="https://baye-bara-diop-project-bancaire-laravel.onrender.com/api",
 *     description="Serveur de production Render"
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Serveur de développement local"
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
 *     name="Comptes",
 *     description="Gestion complète des comptes bancaires : création de nouveaux comptes, consultation, listing avec filtres et pagination, archivage dans le cloud."
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
