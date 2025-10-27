<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="API Bancaire - Documentation Complète",
 *     description="Documentation de l'API RESTful de gestion bancaire.\n\n\n**Authentification :**\n- Les clients web utilisent un cookie HttpOnly sécurisé (invisible pour Swagger UI).\n- Pour tester les endpoints protégés dans Swagger UI, utilisez le bouton 'Authorize' et collez le Bearer Token JWT retourné par l'endpoint /v1/auth/login.\n- Le cookie HttpOnly continue de fonctionner pour les vraies applications web.\n\n**Limite :** Swagger UI ne peut pas tester les cookies HttpOnly, mais tous les endpoints protégés acceptent aussi le header Authorization: Bearer <token>.\n\nPour automatiser vos tests, privilégiez Postman, Insomnia ou cURL si vous souhaitez tester le flux cookie.",
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
 *     description="Authentification via Bearer Token JWT pour Swagger UI."
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
