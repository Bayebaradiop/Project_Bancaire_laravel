<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="API Bancaire - Documentation Complète",
*     description="Documentation de l'API RESTful de gestion bancaire incluant l'authentification JWT avec cookies HttpOnly. Les endpoints protégés nécessitent un client API qui gère les cookies (Postman, Insomnia, cURL). Swagger UI ne permet pas de tester les cookies HttpOnly.",
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
 *     url="http://localhost:8000/api",
 *     description="Serveur de développement local"
 * )
 * 
 * @OA\Server(
 *     url="https://baye-bara-diop-project-bancaire-laravel.onrender.com/api",
 *     description="Serveur de production Render"
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
