<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="API Bancaire - Gestion des Comptes",
 *     description="API REST pour la gestion des comptes bancaires. Permet de créer, lister et consulter des comptes avec validation complète.",
 *     @OA\Contact(
 *         email="support@banque.sn"
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
 *     url="https://project-bancaire-laravel.onrender.com/api",
 *     description="Serveur de production"
 * )
 *
 * @OA\Tag(
 *     name="Comptes",
 *     description="Opérations sur les comptes bancaires"
 * )
 *
 * @OA\Tag(
 *     name="Health",
 *     description="Vérification de l'état de l'API"
 * )
 */
class SwaggerController extends Controller
{
    // Ce contrôleur sert uniquement pour les annotations Swagger
    // Aucune méthode n'est nécessaire
}
