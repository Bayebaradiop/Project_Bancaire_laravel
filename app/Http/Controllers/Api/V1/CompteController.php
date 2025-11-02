<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompteResource;
use App\Http\Requests\ListCompteRequest;
use App\Http\Requests\StoreCompteRequest;
use App\Models\Compte;
use App\Models\Client;
use App\Models\User;
use App\Services\CompteService;
use App\Services\CompteArchiveService;
use App\Traits\ApiResponseFormat;
use App\Traits\Cacheable;
use App\Exceptions\CompteNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompteController extends Controller
{
    use ApiResponseFormat, Cacheable;

    protected CompteService $compteService;
    protected CompteArchiveService $archiveService;

    public function __construct(CompteService $compteService, CompteArchiveService $archiveService)
    {
        $this->compteService = $compteService;
        $this->archiveService = $archiveService;
    }

    /**
     * @OA\Get(
     *     path="/v1/comptes",
     *     summary="Lister les comptes actifs",
     *     description="Récupère la liste des comptes ACTIFS non archivés avec pagination et filtres optionnels. 

**AUTHENTIFICATION REQUISE :**
1. Connectez-vous d'abord via POST /v1/auth/login
2. Copiez le access_token de la réponse
3. Cliquez sur 'Authorize' (cadenas en haut à droite)
4. Collez le token et validez

Les administrateurs voient tous les comptes actifs, les clients ne voient que leurs propres comptes actifs. 

NOTE: Seuls les comptes avec statut 'actif' sont retournés - les comptes bloqués et fermés sont exclus.",
     *     operationId="getComptes",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page pour la pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page (maximum 100)",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=100, example=10)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type de compte (laisser vide pour tous les types)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"epargne", "cheque"})
     *     ),
     *     @OA\Parameter(
     *         name="devise",
     *         in="query",
     *         description="Filtrer par devise (laisser vide pour toutes les devises)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="numeroCompte",
     *         in="query",
     *         description="Filtrer par numéro de compte exact (format: CPxxxxxxxxxx, laisser vide pour tous)",
     *         required=false,
     *         @OA\Schema(type="string", pattern="^CP\d{10}$")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Rechercher par nom du titulaire ou numéro de compte (laisser vide pour tous)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Champ de tri (laisser vide pour tri par défaut: dateCreation)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"dateCreation", "derniereModification", "numeroCompte"})
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Ordre de tri (laisser vide pour tri décroissant par défaut)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Comptes récupérés avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="a032f0ea-25e7-4b17-a7c4-e0a1aa6aa289"),
     *                     @OA\Property(property="numeroCompte", type="string", example="CP3105472638"),
     *                     @OA\Property(property="titulaire", type="string", example="Mamadou Diop"),
     *                     @OA\Property(property="type", type="string", example="epargne"),
     *                     @OA\Property(property="solde", type="number", example=150000),
     *                     @OA\Property(property="devise", type="string", example="FCFA"),
     *                     @OA\Property(property="statut", type="string", example="actif"),
     *                     @OA\Property(
     *                         property="blocage_info",
     *                         type="object",
     *                         nullable=true,
     *                         description="Informations sur le blocage programmé (null si aucun blocage)",
     *                         @OA\Property(property="en_cours", type="boolean", example=true),
     *                         @OA\Property(property="message", type="string", example="Ce compte sera bloqué le 29/10/2025 jusqu'au 30/11/2025"),
     *                         @OA\Property(property="dateDebutBlocage", type="string", example="29/10/2025"),
     *                         @OA\Property(property="dateFinBlocage", type="string", nullable=true, example="30/11/2025"),
     *                         @OA\Property(property="motif", type="string", example="Blocage administratif")
     *                     ),
     *                     @OA\Property(
     *                         property="metadata",
     *                         type="object",
     *                         @OA\Property(property="derniereModification", type="string", example="2025-10-28T21:38:16+00:00"),
     *                         @OA\Property(property="version", type="integer", example=4),
     *                         @OA\Property(property="location", type="string", example="PostgreSQL")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=45),
     *                 @OA\Property(property="count", type="integer", example=10),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="total_pages", type="integer", example=5)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422, 
     *         description="Erreur de validation - Paramètres invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="limit",
     *                     type="array",
     *                     @OA\Items(type="string", example="Le limit ne peut pas dépasser 100")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(ListCompteRequest $request): JsonResponse
    {
        // Récupérer l'utilisateur authentifié
        $user = auth()->user();
        
        // Déléguer toute la logique au service avec l'utilisateur
        $response = $this->compteService->getComptesList($request, $user);
        
        // Retourner la réponse
        return response()->json($response);
    }

    /**
     * @OA\Get(
     *     path="/v1/comptes/{id}",
     *     summary="Récupérer un compte spécifique par ID (US 2.1)",
     *     description="Récupère les détails complets d'un compte bancaire par son ID UUID.

**AUTHENTIFICATION REQUISE :** Utilisez le bouton 'Authorize' avec votre Bearer Token obtenu via /v1/auth/login

Implémente une stratégie de recherche dual-database : 
- Cherche d'abord dans PostgreSQL (comptes actifs, bloqués, fermés)
- Puis dans Neon (comptes archivés) si non trouvé

Admin peut récupérer n'importe quel compte. Client peut récupérer uniquement ses propres comptes.",
     *     operationId="getCompteById",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID UUID du compte",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="a038f679-7eac-46cc-b036-7ca130facf09")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte récupéré avec succès (depuis PostgreSQL ou Neon)",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="a038f679-7eac-46cc-b036-7ca130facf09"),
     *                 @OA\Property(property="numeroCompte", type="string", example="C00123456"),
     *                 @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
     *                 @OA\Property(property="type", type="string", enum={"epargne", "cheque"}, example="epargne"),
     *                 @OA\Property(property="solde", type="number", format="float", example=1250000),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
     *                 @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, example="actif"),
     *                 @OA\Property(property="motifBlocage", type="string", nullable=true, example="Blocage administratif"),
     *                 @OA\Property(
     *                     property="blocage_info",
     *                     type="object",
     *                     nullable=true,
     *                     description="Informations sur le blocage programmé (null si aucun blocage programmé)",
     *                     @OA\Property(property="en_cours", type="boolean", example=true),
     *                     @OA\Property(property="message", type="string", example="Ce compte sera bloqué le 29/10/2025 jusqu'au 30/11/2025"),
     *                     @OA\Property(property="dateDebutBlocage", type="string", example="29/10/2025"),
     *                     @OA\Property(property="dateFinBlocage", type="string", nullable=true, example="30/11/2025"),
     *                     @OA\Property(property="motif", type="string", example="Blocage administratif")
     *                 ),
     *                 @OA\Property(
     *                     property="metadata",
     *                     type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time", example="2023-06-10T14:30:00Z"),
     *                     @OA\Property(property="version", type="integer", example=1),
     *                     @OA\Property(property="location", type="string", example="PostgreSQL", description="PostgreSQL pour comptes actifs, Neon pour comptes archivés/bloqués"),
     *                     @OA\Property(property="archived", type="boolean", example=false, description="true si récupéré depuis Neon, false si depuis PostgreSQL")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé - Client tentant d'accéder à un compte qui ne lui appartient pas",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="error",
     *                 type="object",
     *                 @OA\Property(property="code", type="string", example="ACCESS_DENIED"),
     *                 @OA\Property(property="message", type="string", example="Accès non autorisé à ce compte"),
     *                 @OA\Property(
     *                     property="details",
     *                     type="object",
     *                     @OA\Property(property="compteId", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé dans PostgreSQL ni dans Neon",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="error",
     *                 type="object",
     *                 @OA\Property(property="code", type="string", example="COMPTE_NOT_FOUND"),
     *                 @OA\Property(property="message", type="string", example="Le compte avec l'ID spécifié n'existe pas"),
     *                 @OA\Property(
     *                     property="details",
     *                     type="object",
     *                     @OA\Property(property="compteId", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Une erreur est survenue lors de la récupération du compte")
     *         )
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            // Récupérer l'utilisateur authentifié
            $user = auth()->user();
            
            // Déléguer la logique au service
            $result = $this->compteService->getCompteById($id, $user);

            // Gérer les erreurs
            if (!$result['success']) {
                return response()->json($result, $result['http_code'] ?? 500);
            }

            // Succès - retourner les données
            return response()->json([
                'success' => true,
                'data' => $result['data']
            ]);

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug') 
                    ? 'Erreur lors de la récupération du compte : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors de la récupération du compte'
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/v1/comptes/numero/{numero}",
     *     summary="Obtenir un compte par numéro",
     *     description="Récupère les détails complets d'un compte bancaire en utilisant son numéro de compte.

**AUTHENTIFICATION REQUISE :** Utilisez le bouton 'Authorize' avec votre Bearer Token obtenu via /v1/auth/login

Cherche automatiquement dans la base principale (PostgreSQL) et dans les archives (Neon) si le compte est fermé, bloqué ou archivé.",
     *     operationId="getCompteByNumero",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="numero",
     *         in="path",
     *         description="Numéro du compte (format: CPxxxxxxxxxx)",
     *         required=true,
     *         @OA\Schema(type="string", example="CP5342804805")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte récupéré avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="a032f0ea-25e7-4b17-a7c4-e0a1aa6aa289"),
     *                 @OA\Property(property="numeroCompte", type="string", example="CP5342804805"),
     *                 @OA\Property(property="titulaire", type="string", example="Mamadou Diop"),
     *                 @OA\Property(property="type", type="string", example="epargne"),
     *                 @OA\Property(property="solde", type="number", example=150000),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time"),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(
     *                     property="blocage_info",
     *                     type="object",
     *                     nullable=true,
     *                     description="Informations sur le blocage programmé (null si aucun blocage)",
     *                     @OA\Property(property="en_cours", type="boolean", example=true),
     *                     @OA\Property(property="message", type="string", example="Ce compte sera bloqué le 29/10/2025 jusqu'au 30/11/2025"),
     *                     @OA\Property(property="dateDebutBlocage", type="string", example="29/10/2025"),
     *                     @OA\Property(property="dateFinBlocage", type="string", nullable=true, example="30/11/2025"),
     *                     @OA\Property(property="motif", type="string", example="Blocage administratif")
     *                 ),
     *                 @OA\Property(
     *                     property="metadata",
     *                     type="object",
     *                     @OA\Property(property="derniereModification", type="string", format="date-time"),
     *                     @OA\Property(property="version", type="integer", example=4),
     *                     @OA\Property(property="location", type="string", example="PostgreSQL"),
     *                     @OA\Property(property="archived", type="boolean", example=false, description="Indique si le compte est archivé dans Neon")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404, 
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Compte non trouvé"),
     *             @OA\Property(property="error", type="string", example="Le compte avec le numéro CP9999999999 n'existe pas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function showByNumero(string $numero): JsonResponse
    {
        try {
            // 1. Chercher d'abord dans la base principale (Render) - comptes actifs uniquement
            $compte = Compte::where('numeroCompte', $numero)
                ->whereNull('archived_at')
                ->where('statut', 'actif')
                ->with(['client.user'])
                ->first();

            if ($compte) {
                // Compte actif trouvé dans la base principale
                return $this->success(
                    new CompteResource($compte),
                    'Compte actif récupéré avec succès'
                );
            }

            // 2. Si non trouvé ou archivé, chercher dans Neon (comptes fermés/bloqués/archivés)
            $archived = $this->archiveService->getArchivedCompte($numero);

            if ($archived) {
                // Compte trouvé dans les archives Neon
                return $this->success(
                    [
                        'id' => $archived->id,
                        'numeroCompte' => $archived->numerocompte,
                        'titulaire' => $archived->client_nom,
                        'type' => $archived->type,
                        'solde' => $archived->solde,
                        'devise' => $archived->devise,
                        'dateCreation' => $archived->created_at,
                        'statut' => $archived->statut,
                        'motifBlocage' => $archived->motifblocage,
                        'archived' => true,
                        'archived_at' => $archived->archived_at,
                        'archive_reason' => $archived->archive_reason,
                        'metadata' => [
                            'source' => 'neon',
                            'client_email' => $archived->client_email,
                            'client_telephone' => $archived->client_telephone,
                        ]
                    ],
                    'Compte archivé récupéré depuis Neon'
                );
            }

            // 3. Compte introuvable dans les deux bases
            return $this->notFound(
                "Le compte avec le numéro {$numero} n'existe pas"
            );

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug') 
                    ? 'Erreur lors de la récupération du compte : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors de la récupération du compte'
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/v1/comptes",
     *     summary="Créer un nouveau compte bancaire avec auto-création du client",
     *     description="**🎯 FONCTIONNALITÉ AUTO-CRÉATION :**
Cette API crée automatiquement un nouveau compte bancaire. Si le client n'existe pas :
- ✅ Un nouveau client est créé automatiquement
- ✅ Un mot de passe aléatoire est généré
- ✅ Un code de sécurité est généré
- ✅ Un numéro de compte unique est généré (format: CPxxxxxxxxxx)
- ✅ **Un email de bienvenue est envoyé automatiquement** avec :
  - Le mot de passe (en clair, avant hashage)
  - Le code de sécurité
  - Le numéro de compte
  - Les instructions de connexion

**📧 EMAIL AUTOMATIQUE :**
L'email est envoyé via SendGrid avec un design professionnel incluant :
- Toutes les informations de connexion
- Conseils de sécurité
- Avertissement pour changer le mot de passe à la première connexion

**AUTHENTIFICATION REQUISE :**
Si vous voyez 'Unauthenticated', suivez ces étapes :
1. Allez à POST /v1/auth/login et connectez-vous avec admin@banque.sn / Admin@2025
2. Copiez le access_token de la réponse
3. Cliquez sur 'Authorize' (cadenas en haut)
4. Collez : Bearer VOTRE_TOKEN (n'oubliez pas 'Bearer ' avec l'espace)
5. Cliquez Authorize puis Close
6. Réessayez cette requête",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du compte à créer",
     *         @OA\JsonContent(
     *             required={"type", "devise", "client"},
     *             @OA\Property(
     *                 property="type", 
     *                 type="string", 
     *                 enum={"epargne", "cheque"}, 
     *                 description="Type de compte (epargne ou cheque uniquement)",
     *                 example="epargne"
     *             ),
     *             @OA\Property(
     *                 property="devise", 
     *                 type="string", 
     *                 enum={"FCFA", "USD", "EUR"}, 
     *                 description="Devise du compte",
     *                 example="FCFA"
     *             ),
     *             @OA\Property(
     *                 property="client",
     *                 type="object",
     *                 description="Informations du client",
     *                 required={"titulaire", "nci", "email", "telephone", "adresse"},
     *                 @OA\Property(property="id", type="string", nullable=true, description="ID du client existant (optionnel)", example=null),
     *                 @OA\Property(property="titulaire", type="string", description="Nom complet du titulaire", example="Mamadou Diop"),
     *                 @OA\Property(property="nci", type="string", description="Numéro NCI sénégalais (13 chiffres commençant par 1 ou 2)", example="1234567890123"),
     *                 @OA\Property(property="email", type="string", format="email", description="Adresse email unique", example="mamadou.diop@example.com"),
     *                 @OA\Property(property="telephone", type="string", description="Téléphone sénégalais (+221 suivi de 70/75/76/77/78)", example="+221771234567"),
     *                 @OA\Property(property="adresse", type="string", description="Adresse complète", example="Dakar, Plateau")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte créé avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="a032f0ea-25e7-4b17-a7c4-e0a1aa6aa289"),
     *                 @OA\Property(property="numeroCompte", type="string", example="CP3105472638"),
     *                 @OA\Property(property="titulaire", type="string", example="Mamadou Diop"),
     *                 @OA\Property(property="type", type="string", example="epargne"),
     *                 @OA\Property(property="solde", type="number", example=0),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time", example="2025-10-25T15:35:06+00:00"),
     *                 @OA\Property(property="statut", type="string", example="actif")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation - Données invalides ou champs requis manquants",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 description="Exemples de tous les cas d'erreur possibles. Chaque champ peut avoir plusieurs types d'erreurs (requis, format, unicité).",
     *                 @OA\Property(
     *                     property="type",
     *                     type="array",
     *                     description="Erreurs possibles : champ manquant, valeur invalide",
     *                     @OA\Items(type="string", example="Le type de compte est requis")
     *                 ),
     *                 @OA\Property(
     *                     property="devise",
     *                     type="array",
     *                     description="Erreurs possibles : champ manquant, valeur invalide",
     *                     @OA\Items(type="string", example="La devise est requise")
     *                 ),
     *                 @OA\Property(
     *                     property="client",
     *                     type="array",
     *                     description="Erreur si l'objet client est manquant",
     *                     @OA\Items(type="string", example="Les informations du client sont requises")
     *                 ),
     *                 @OA\Property(
     *                     property="client.titulaire",
     *                     type="array",
     *                     description="Erreur : champ manquant",
     *                     @OA\Items(type="string", example="Le nom du titulaire est requis")
     *                 ),
     *                 @OA\Property(
     *                     property="client.nci",
     *                     type="array",
     *                     description="Erreurs possibles : 1) champ manquant 'Le NCI est requis', 2) format invalide 'Le NCI doit être un numéro NCI sénégalais valide (13 chiffres commençant par 1 ou 2)', 3) déjà utilisé 'Ce NCI est déjà utilisé'",
     *                     @OA\Items(type="string", example="Ce NCI est déjà utilisé")
     *                 ),
     *                 @OA\Property(
     *                     property="client.email",
     *                     type="array",
     *                     description="Erreurs possibles : 1) champ manquant 'L'email est requis', 2) format invalide 'L'email doit être valide', 3) déjà utilisé 'Cet email est déjà utilisé'",
     *                     @OA\Items(type="string", example="Cet email est déjà utilisé")
     *                 ),
     *                 @OA\Property(
     *                     property="client.telephone",
     *                     type="array",
     *                     description="Erreurs possibles : 1) champ manquant 'Le téléphone est requis', 2) format invalide 'Le téléphone doit être un numéro de téléphone sénégalais valide (+221 suivi de 70/75/76/77/78)', 3) déjà utilisé 'Ce numéro de téléphone est déjà utilisé'",
     *                     @OA\Items(type="string", example="Ce numéro de téléphone est déjà utilisé")
     *                 ),
     *                 @OA\Property(
     *                     property="client.adresse",
     *                     type="array",
     *                     description="Erreur : champ manquant",
     *                     @OA\Items(type="string", example="L'adresse est requise")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de format - NCI ou téléphone invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="client.nci",
     *                     type="array",
     *                     @OA\Items(type="string", example="Le NCI doit être un numéro NCI sénégalais valide (13 chiffres commençant par 1 ou 2)")
     *                 ),
     *                 @OA\Property(
     *                     property="client.telephone",
     *                     type="array",
     *                     @OA\Items(type="string", example="Le téléphone doit être un numéro de téléphone sénégalais valide (+221 suivi de 70/75/76/77/78)")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur interne",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Une erreur est survenue : [détails de l'erreur]")
     *         )
     *     )
     * )
     */
    public function store(StoreCompteRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $password = null;
            $code = null;

            // 1. Vérifier l'existence du client
            if (!empty($request->client['id'])) {
                $client = Client::findOrFail($request->client['id']);
            } else {
                // 2. Créer l'utilisateur et le client s'il n'existe pas
                $password = Client::generatePassword();
                $code = Client::generateCode();

                // Créer l'utilisateur
                $user = User::create([
                    'nomComplet' => $request->client['titulaire'],
                    'nci' => $request->client['nci'],
                    'email' => $request->client['email'],
                    'telephone' => $request->client['telephone'],
                    'adresse' => $request->client['adresse'],
                    'password' => Hash::make($password),
                    'code' => $code,
                ]);

                // Créer le client
                $client = Client::create([
                    'user_id' => $user->id,
                    'titulaire' => $request->client['titulaire'],
                    'nci' => $request->client['nci'],
                    'email' => $request->client['email'],
                    'telephone' => $request->client['telephone'],
                    'adresse' => $request->client['adresse'],
                ]);

                // Stocker temporairement pour l'observer
                session([
                    'temp_client_password' => $password,
                    'temp_client_code' => $code,
                ]);
            }

            // 3. Créer le compte (le numéro sera généré automatiquement par CompteObserver)
            $compte = Compte::create([
                'type' => $request->type,
                'devise' => $request->devise,
                'statut' => 'actif',
                'client_id' => $client->id,
            ]);

            // 4. Créer une transaction de dépôt initial de 50000 FCFA
            $transactionInitiale = \App\Models\Transaction::create([
                'numeroCompte' => $compte->numeroCompte,
                'type' => 'depot',
                'montant' => new \MongoDB\BSON\Decimal128('50000'),
                'frais' => new \MongoDB\BSON\Decimal128('0'),
                'devise' => $request->devise,
                'statut' => 'complete',
                'description' => 'Dépôt initial à l\'ouverture du compte',
                'initiateur_id' => auth()->id(),
            ]);

            // Charger les relations
            $compte->load(['client.user', 'transactions']);

            // Invalider le cache de la liste des comptes
            $this->forgetPaginatedCache('comptes:list');

            DB::commit();

            // Dispatcher l'événement pour l'envoi d'email si c'est un nouveau client
            if ($password && $code) {
                event(new \App\Events\CompteCreated($compte, $password, $code));
            }

            // Utiliser le trait pour formater la réponse
            return $this->created([
                'id' => $compte->id,
                'numeroCompte' => $compte->numeroCompte,
                'titulaire' => $compte->client->user->nomComplet ?? 'N/A',
                'type' => $compte->type,
                'solde' => $compte->solde,
                'devise' => $compte->devise,
                'dateCreation' => $compte->dateCreation->toIso8601String(),
                'statut' => $compte->statut,
                'metadata' => [
                    'derniereModification' => $compte->derniereModification->toIso8601String(),
                    'version' => 1,
                ],
            ], 'Compte créé avec succès');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->validationError($e->errors(), 'Les données fournies sont invalides');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur création compte', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->serverError('Une erreur est survenue : ' . $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/v1/comptes/archives",
     *     summary="Lister les comptes archivés dans Neon",
     *     description="Récupère les comptes archivés stockés dans Neon (base serverless). Admin voit tous les comptes, Client voit uniquement les siens. Authentification requise via Bearer token.",
     *     operationId="getArchivedComptes",
     *     tags={"Suppression"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes archivés récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Liste des comptes archivés récupérée avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 description="Liste des comptes archivés dans Neon",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid", example="a03902aa-a03a-4213-b865-0a05f77dee48"),
     *                     @OA\Property(property="numeroCompte", type="string", example="CP4287048035"),
     *                     @OA\Property(property="type", type="string", example="epargne"),
     *                     @OA\Property(property="statut", type="string", example="bloque"),
     *                     @OA\Property(property="solde", type="number", format="float", example=5000.00),
     *                     @OA\Property(property="devise", type="string", example="FCFA"),
     *                     @OA\Property(property="archived_at", type="string", format="date-time", example="2025-10-28T17:11:22Z"),
     *                     @OA\Property(property="archived_by", type="string", format="uuid", description="ID de l'utilisateur qui a archivé"),
     *                     @OA\Property(property="archive_reason", type="string", example="Blocage immédiat - Activité suspecte"),
     *                     @OA\Property(property="dateDebutBlocage", type="string", format="date", example="2025-10-28"),
     *                     @OA\Property(property="dateFinBlocage", type="string", format="date", example="2025-11-28", nullable=true),
     *                     @OA\Property(property="motifBlocage", type="string", example="Activité suspecte détectée"),
     *                     @OA\Property(
     *                         property="client",
     *                         type="object",
     *                         @OA\Property(property="nom", type="string", example="DIOP"),
     *                         @OA\Property(property="prenom", type="string", example="Fatou"),
     *                         @OA\Property(property="email", type="string", example="fatou@example.com"),
     *                         @OA\Property(property="telephone", type="string", example="+221 77 123 45 67")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     )
     * )
     */
    public function archives(): JsonResponse
    {
        try {
            // Récupérer tous les comptes archivés (sans restriction)
            $archives = $this->archiveService->getAllArchivedComptes();

            return $this->success(
                $archives,
                'Liste de tous les comptes archivés récupérée avec succès'
            );

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug') 
                    ? 'Une erreur est survenue : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors de la récupération des comptes archivés'
            );
        }
    }

    /**
     * Méthode archive() - Utilisée uniquement en interne par les Jobs automatiques
     * Pas d'endpoint public exposé dans les routes
     */
    public function archive(string $numeroCompte): JsonResponse
    {
        try {
            $compte = Compte::where('numeroCompte', $numeroCompte)->first();

            if (!$compte) {
                throw new CompteNotFoundException("Le compte {$numeroCompte} n'existe pas");
            }

            // Archiver vers Neon (sans vérification de rôle)
            $reason = request()->input('reason');
            $archive = $this->archiveService->archiveCompte($compte, null, $reason);

            return $this->success([
                'numeroCompte' => $compte->numeroCompte,
                'archived_at' => $archive->archived_at,
                'archive_reason' => $archive->archive_reason,
            ], 'Compte archivé avec succès dans le cloud');

        } catch (CompteNotFoundException $e) {
            return $this->notFound($e->getMessage());

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug') 
                    ? 'Une erreur est survenue : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors de l\'archivage du compte'
            );
        }
    }

    /**
     * @OA\Patch(
     *     path="/v1/comptes/{compteId}",
     *     summary="Mettre à jour un compte (US 2.3)",
     *     description="Permet à un administrateur de mettre à jour les informations d'un compte bancaire. Seuls les administrateurs peuvent utiliser cet endpoint.",
     *     operationId="updateCompte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="UUID du compte à mettre à jour",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="a032f0ea-25e7-4b17-a7c4-e0a1aa6aa289")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données à mettre à jour (au moins un champ requis)",
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", enum={"epargne", "cheque"}, example="cheque"),
     *             @OA\Property(property="solde", type="number", format="float", example=50000),
     *             @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, example="actif"),
     *             @OA\Property(property="devise", type="string", enum={"FCFA", "USD", "EUR"}, example="FCFA")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte mis à jour avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="numeroCompte", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="solde", type="number"),
     *                 @OA\Property(property="statut", type="string"),
     *                 @OA\Property(property="devise", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Accès refusé - Admin uniquement"),
     *     @OA\Response(response=404, description="Compte non trouvé"),
     *     @OA\Response(response=422, description="Données invalides")
     * )
     */
    public function update(string $compteId): JsonResponse
    {
        try {
            $data = request()->validate([
                'type' => 'sometimes|in:epargne,cheque',
                'solde' => 'sometimes|numeric|min:0',
                'statut' => 'sometimes|in:actif,bloque,ferme',
                'devise' => 'sometimes|in:FCFA,USD,EUR',
            ]);

            $result = $this->compteService->updateCompte($compteId, $data);

            return $this->success($result, 'Compte mis à jour avec succès');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());

        } catch (CompteNotFoundException $e) {
            return $this->notFound($e->getMessage());

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug') 
                    ? 'Une erreur est survenue : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors de la mise à jour du compte'
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/v1/comptes/{compteId}/bloquer",
     *     summary="Bloquer un compte épargne (Immédiat ou Programmé)",
     *     description="Bloque un compte épargne de manière immédiate (date=aujourd'hui → archivé dans Neon) ou programmée (date future → reste dans PostgreSQL jusqu'à la date). Authentification requise.",
     *     operationId="bloquerCompte",
     *     tags={"Comptes - Blocage/Déblocage"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="UUID du compte épargne à bloquer",
     *         required=true,
     *         @OA\Schema(
     *             type="string", 
     *             format="uuid",
     *             example="a03902aa-a03a-4213-b865-0a05f77dee48"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         description="Paramètres du blocage (tous optionnels)",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="dateDebutBlocage", 
     *                 type="string", 
     *                 format="date", 
     *                 example="2025-11-15", 
     *                 description="Date de début du blocage (YYYY-MM-DD). Si omise ou = aujourd'hui → blocage immédiat. Si future → blocage programmé"
     *             ),
     *             @OA\Property(
     *                 property="dateFinBlocage", 
     *                 type="string", 
     *                 format="date", 
     *                 example="2025-12-15", 
     *                 description="Date de fin du blocage (YYYY-MM-DD). Le compte sera automatiquement débloqué à cette date par un Job"
     *             ),
     *             @OA\Property(
     *                 property="raison", 
     *                 type="string", 
     *                 example="Activité suspecte détectée",
     *                 description="📝 Motif du blocage (max 500 caractères)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bloqué avec succès (immédiat ou programmé)",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="message", 
     *                 type="string", 
     *                 example="Compte bloqué avec succès et archivé dans Neon",
     *                 description="Message varie selon le type : immédiat ou programmé"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Détails du compte après blocage",
     *                 @OA\Property(property="id", type="string", format="uuid", example="a03902aa-a03a-4213-b865-0a05f77dee48"),
     *                 @OA\Property(property="numeroCompte", type="string", example="CP4287048035"),
     *                 @OA\Property(
     *                     property="statut", 
     *                     type="string", 
     *                     example="bloque",
     *                     description="'bloque' si immédiat, 'actif' si programmé"
     *                 ),
     *                 @OA\Property(property="motifBlocage", type="string", example="Activité suspecte détectée"),
     *                 @OA\Property(property="dateDebutBlocage", type="string", format="date-time", example="2025-11-15T00:00:00+00:00"),
     *                 @OA\Property(property="dateFinBlocage", type="string", format="date-time", example="2025-12-15T00:00:00+00:00", nullable=true),
     *                 @OA\Property(property="dateBlocage", type="string", format="date-time", example="2025-10-28T17:11:22+00:00", nullable=true, description="Date effective du blocage (null si programmé)"),
     *                 @OA\Property(
     *                     property="blocage_programme", 
     *                     type="boolean", 
     *                     example=true,
     *                     description="true si blocage programmé, false si immédiat"
     *                 ),
     *                 @OA\Property(
     *                     property="location", 
     *                     type="string", 
     *                     example="PostgreSQL",
     *                     description="'PostgreSQL' si programmé, 'Neon' si immédiat"
     *                 ),
     *                 @OA\Property(
     *                     property="archived", 
     *                     type="boolean", 
     *                     example=false,
     *                     description="true si archivé dans Neon, false si dans PostgreSQL"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de validation métier",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="message", 
     *                 type="string", 
     *                 example="Seuls les comptes épargne peuvent être bloqués",
     *                 description="Messages possibles : 'Seuls les comptes épargne...', 'Le compte ne peut pas être bloqué. Statut actuel: ...', 'Ce compte est déjà bloqué et se trouve dans Neon'"
     *             ),
     *             @OA\Property(property="http_code", type="integer", example=400)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ce compte n'existe pas"),
     *             @OA\Property(property="http_code", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation des données",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="dateDebutBlocage",
     *                     type="array",
     *                     @OA\Items(type="string", example="La date de début doit être supérieure ou égale à aujourd'hui")
     *                 ),
     *                 @OA\Property(
     *                     property="dateFinBlocage",
     *                     type="array",
     *                     @OA\Items(type="string", example="La date de fin doit être après la date de début")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function bloquer(string $compteId): JsonResponse
    {
        try {
            $data = request()->validate([
                'dateDebutBlocage' => 'nullable|date|after_or_equal:today',
                'dateFinBlocage' => 'nullable|date|after:dateDebutBlocage',
                'raison' => 'nullable|string|max:500',
            ]);

            $result = $this->compteService->bloquerCompte($compteId, $data);

            // Le service retourne déjà un array structuré avec success, message, data
            if (isset($result['http_code'])) {
                return response()->json([
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'data' => $result['data'] ?? null
                ], $result['http_code']);
            }

            return response()->json($result);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());

        } catch (CompteNotFoundException $e) {
            return $this->notFound($e->getMessage());

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug') 
                    ? 'Une erreur est survenue : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors du blocage du compte'
            );
        }
    }

    /**
     * Débloquer un compte épargne (non documenté dans Swagger)
     * Restaure depuis Neon vers PostgreSQL ou annule un blocage programmé
     */
    public function debloquer(string $compteId): JsonResponse
    {
        try {
            $data = request()->validate([
                'dateDeblocagePrevue' => 'nullable|date|after_or_equal:today',
            ]);

            $result = $this->compteService->debloquerCompte($compteId, $data);

            return $this->success($result, 'Compte débloqué avec succès');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());

        } catch (CompteNotFoundException $e) {
            return $this->notFound($e->getMessage());

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug') 
                    ? 'Une erreur est survenue : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors du déblocage du compte'
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/v1/comptes/{numeroCompte}",
     *     summary="Supprimer un compte épargne (Soft Delete + Archive)",
     *     description="**🗑️ SUPPRESSION SÉCURISÉE :**
Supprime un compte épargne avec soft delete dans PostgreSQL et archivage automatique dans Neon.

**⚠️ VALIDATIONS AUTOMATIQUES :**
- ✅ Seuls les comptes **épargne** peuvent être supprimés (les comptes chèque sont protégés)
- ✅ Le compte ne doit PAS avoir un **blocage programmé** en cours
- ✅ Le compte ne doit PAS être actuellement **bloqué** (statut='bloque')
- ✅ Le compte ne doit PAS être déjà supprimé
- ✅ Le compte ne doit PAS être déjà archivé

**📧 Si validation échoue :**
- Blocage programmé → Message : 'Ce compte ne peut pas être supprimé car il a un blocage programmé prévu le {date}. Veuillez d'abord annuler le blocage ou attendre son exécution.'
- Compte bloqué → Message : 'Ce compte est actuellement bloqué. Veuillez d'abord le débloquer avant de le supprimer.'
- Compte chèque → Message : 'Les comptes chèque ne peuvent pas être supprimés'

**♻️ RESTAURATION :**
Restauration possible via POST /v1/comptes/restore/{id}

Authentification requise (admin uniquement).",
     *     operationId="deleteCompte",
     *     tags={"Suppression"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="numeroCompte",
     *         in="path",
     *         description="**Numéro du compte** à supprimer (format : CPxxxxxxxxxx)",
     *         required=true,
     *         @OA\Schema(type="string", example="CP3105472638")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte supprimé et archivé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte supprimé et archivé avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="b12345aa-bb12-4c3d-9876-abc123def456"),
     *                 @OA\Property(property="numeroCompte", type="string", example="CP3105472638"),
     *                 @OA\Property(property="type", type="string", example="epargne"),
     *                 @OA\Property(property="solde", type="number", format="float", example=12500.00),
     *                 @OA\Property(property="deleted_at", type="string", format="date-time", example="2025-10-28T18:45:00Z"),
     *                 @OA\Property(property="archived_at", type="string", format="date-time", example="2025-10-28T18:45:01Z"),
     *                 @OA\Property(property="archive_reason", type="string", example="Suppression à la demande du client"),
     *                 @OA\Property(
     *                     property="client",
     *                     type="object",
     *                     @OA\Property(property="nom", type="string", example="SARR"),
     *                     @OA\Property(property="prenom", type="string", example="Mamadou"),
     *                     @OA\Property(property="email", type="string", example="mamadou@example.com")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreurs de validation - Compte protégé contre la suppression",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="message", 
     *                 type="string", 
     *                 description="Message d'erreur selon le cas",
     *                 example="Ce compte ne peut pas être supprimé car il a un blocage programmé prévu le 15/11/2025. Veuillez d'abord annuler le blocage ou attendre son exécution."
     *             ),
     *             @OA\Property(property="code", type="integer", example=400),
     *             @OA\Property(
     *                 property="examples",
     *                 type="object",
     *                 description="Exemples de messages d'erreur possibles",
     *                 @OA\Property(
     *                     property="blocage_programme",
     *                     type="string",
     *                     example="Ce compte ne peut pas être supprimé car il a un blocage programmé prévu le 15/11/2025. Veuillez d'abord annuler le blocage ou attendre son exécution."
     *                 ),
     *                 @OA\Property(
     *                     property="compte_bloque",
     *                     type="string",
     *                     example="Ce compte est actuellement bloqué. Veuillez d'abord le débloquer avant de le supprimer."
     *                 ),
     *                 @OA\Property(
     *                     property="type_cheque",
     *                     type="string",
     *                     example="Les comptes chèque ne peuvent pas être supprimés"
     *                 ),
     *                 @OA\Property(
     *                     property="deja_supprime",
     *                     type="string",
     *                     example="Le compte CP3105472638 est déjà supprimé"
     *                 ),
     *                 @OA\Property(
     *                     property="deja_archive",
     *                     type="string",
     *                     example="Le compte CP3105472638 est déjà archivé"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le compte CP3105472638 n'existe pas"),
     *             @OA\Property(property="http_code", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     )
     * )
     */
    public function destroy(string $numeroCompte): JsonResponse
    {
        try {
            $result = $this->compteService->deleteAndArchive($numeroCompte);

            // Vérifier si le service a retourné une erreur
            if (isset($result['success']) && $result['success'] === false) {
                $code = $result['code'] ?? 400;
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'http_code' => $code
                ], $code);
            }

            return $this->success($result['data'], $result['message']);

        } catch (CompteNotFoundException $e) {
            return $this->notFound($e->getMessage());

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug') 
                    ? 'Une erreur est survenue : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors de la suppression du compte'
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/v1/comptes/restore/{id}",
     *     summary="Restaurer un compte depuis Neon",
     *     description="Restaure un compte supprimé en le récupérant depuis Neon vers PostgreSQL. Le compte devient actif et utilisable. Admin uniquement. Authentification requise.",
     *     operationId="restoreCompte",
     *     tags={"Suppression"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="**UUID du compte** à restaurer depuis les archives Neon",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="b12345aa-bb12-4c3d-9876-abc123def456")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte restauré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte restauré avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid", example="b12345aa-bb12-4c3d-9876-abc123def456"),
     *                 @OA\Property(property="numeroCompte", type="string", example="CP3105472638"),
     *                 @OA\Property(property="type", type="string", example="epargne"),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="solde", type="number", format="float", example=12500.00),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="restored_at", type="string", format="date-time", example="2025-10-28T20:15:30Z"),
     *                 @OA\Property(property="restored_by", type="string", format="uuid", description="UUID de l'admin qui a restauré"),
     *                 @OA\Property(property="deleted_at", type="string", nullable=true, example=null, description="NULL après restauration"),
     *                 @OA\Property(
     *                     property="client",
     *                     type="object",
     *                     @OA\Property(property="nom", type="string", example="SARR"),
     *                     @OA\Property(property="prenom", type="string", example="Mamadou"),
     *                     @OA\Property(property="email", type="string", example="mamadou@example.com"),
     *                     @OA\Property(property="telephone", type="string", example="+221 77 555 66 77")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Compte déjà actif ou validation échouée",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="message", 
     *                 type="string", 
     *                 example="Ce compte est déjà actif et n'a pas besoin d'être restauré"
     *             ),
     *             @OA\Property(property="http_code", type="integer", example=400)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé dans les archives",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="message", 
     *                 type="string", 
     *                 example="Le compte avec l'ID b12345aa-bb12-4c3d-9876-abc123def456 n'existe pas dans les archives"
     *             ),
     *             @OA\Property(property="http_code", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié ou non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Vous n'avez pas les droits pour restaurer des comptes")
     *         )
     *     )
     * )
     */
    public function restore(string $id): JsonResponse
    {
        try {
            $result = $this->compteService->restore($id);

            return $this->success($result, 'Compte restauré avec succès');

        } catch (CompteNotFoundException $e) {
            return $this->notFound($e->getMessage());

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug') 
                    ? 'Une erreur est survenue : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors de la restauration du compte'
            );
        }
    }
}
