<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

/**
 * @OA\Schema(
 *     schema="Compte",
 *     type="object",
 *     title="Compte",
 *     description="Modèle de compte bancaire",
 *     @OA\Property(
 *         property="id",
 *         type="string",
 *         format="uuid",
 *         description="Identifiant unique du compte",
 *         example="a02ea57f-907e-4894-acab-de01af9d4163"
 *     ),
 *     @OA\Property(
 *         property="numeroCompte",
 *         type="string",
 *         description="Numéro unique du compte (format: CP + 10 chiffres)",
 *         example="CP0241262525",
 *         maxLength=20
 *     ),
 *     @OA\Property(
 *         property="titulaire",
 *         type="string",
 *         description="Nom complet du titulaire du compte",
 *         example="Prof. Reta Lesch"
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         enum={"cheque", "epargne"},
 *         description="Type de compte",
 *         example="cheque"
 *     ),
 *     @OA\Property(
 *         property="devise",
 *         type="string",
 *         description="Devise du compte",
 *         default="FCFA",
 *         example="FCFA"
 *     ),
 *     @OA\Property(
 *         property="dateCreation",
 *         type="string",
 *         format="date-time",
 *         description="Date de création du compte",
 *         example="2025-10-23T12:20:55+00:00"
 *     ),
 *     @OA\Property(
 *         property="statut",
 *         type="string",
 *         enum={"actif", "bloque", "ferme"},
 *         description="Statut du compte",
 *         example="actif"
 *     ),
 *     @OA\Property(
 *         property="motifBlocage",
 *         type="string",
 *         nullable=true,
 *         description="Motif du blocage si le compte est bloqué",
 *         example=null
 *     ),
 *     @OA\Property(
 *         property="metadata",
 *         type="object",
 *         description="Métadonnées du compte",
 *         @OA\Property(
 *             property="derniereModification",
 *             type="string",
 *             format="date-time",
 *             description="Date de dernière modification",
 *             example="2025-10-23T12:20:55+00:00"
 *         ),
 *         @OA\Property(
 *             property="version",
 *             type="integer",
 *             description="Version pour le contrôle de concurrence optimiste",
 *             example=1
 *         )
 *     )
 * )
 */
class CompteSwaggerSchema
{
    // Ce fichier contient uniquement les annotations Swagger pour le schéma Compte
}
