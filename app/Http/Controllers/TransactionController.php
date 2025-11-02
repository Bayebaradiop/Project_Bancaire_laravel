<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use App\Services\TransactionService;
use App\Repositories\TransactionRepositoryInterface;
use App\Http\Requests\DepotRequest;
use App\Http\Requests\RetraitRequest;
use App\Http\Requests\TransfertRequest;
use App\Http\Resources\TransactionResource;

class TransactionController extends Controller
{
    protected TransactionService $transactionService;
    protected TransactionRepositoryInterface $transactionRepository;

    public function __construct(
        TransactionService $transactionService,
        TransactionRepositoryInterface $transactionRepository
    ) {
        $this->transactionService = $transactionService;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * @OA\Get(
     *     path="/transactions",
     *     summary="Lister les transactions depuis MongoDB Atlas",
     *     description="Récupère la liste des transactions stockées dans MongoDB Atlas avec pagination et filtres. Les clients ne voient que leurs transactions, les admins voient tout.",
     *     operationId="getTransactions",
     *     tags={"Transactions MongoDB"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre de transactions par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, example=5)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type de transaction",
     *         required=false,
     *         @OA\Schema(type="string", enum={"depot", "retrait", "transfert"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"validee", "en_attente", "annulee"})
     *     ),
     *     @OA\Parameter(
     *         name="date_debut",
     *         in="query",
     *         description="Date de début (format: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-11-01")
     *     ),
     *     @OA\Parameter(
     *         name="date_fin",
     *         in="query",
     *         description="Date de fin (format: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-11-30")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des transactions MongoDB récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Liste des transactions récupérée avec succès"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="_id", type="string", example="690768971a41d958a1079d62", description="ID MongoDB"),
     *                 @OA\Property(property="numeroTransaction", type="string", example="TR202511021331342CVZ"),
     *                 @OA\Property(property="type", type="string", example="depot"),
     *                 @OA\Property(property="montant", type="number", example=50000),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="statut", type="string", example="validee"),
     *                 @OA\Property(property="compte_source_id", type="string", nullable=true),
     *                 @OA\Property(property="compte_destinataire_id", type="string", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="total", type="integer", example=274),
     *                 @OA\Property(property="per_page", type="integer", example=15)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=500, description="Erreur serveur")
     * )
     */
    public function index(Request $request): HttpJsonResponse
    {
        try {
            $filters = $request->only([
                'type',
                'statut',
                'compte',
                'date_debut',
                'date_fin',
                'montant_min',
                'montant_max'
            ]);

            $perPage = $request->input('per_page', 15);

            // Si l'utilisateur est un client, filtrer par ses comptes uniquement
            if (auth()->user()->role === 'client') {
                $transactions = $this->transactionRepository->getByClient(
                    auth()->user()->id,
                    $perPage
                );
            } else {
                // Admin peut voir toutes les transactions avec filtres
                $transactions = $this->transactionRepository->filter($filters, $perPage);
            }

            return response()->json([
                'success' => true,
                'message' => 'Liste des transactions récupérée avec succès',
                'data' => TransactionResource::collection($transactions),
                'meta' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/transactions/depot",
     *     summary="Effectuer un dépôt dans MongoDB Atlas",
     *     description="Crée une transaction de dépôt stockée dans MongoDB Atlas. Réservé aux administrateurs uniquement.",
     *     operationId="createDepot",
     *     tags={"Transactions MongoDB"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"compte_destinataire", "montant"},
     *             @OA\Property(property="compte_destinataire", type="string", example="CP7865908264", description="Numéro du compte destinataire"),
     *             @OA\Property(property="montant", type="number", example=200000, description="Montant du dépôt en FCFA"),
     *             @OA\Property(property="devise", type="string", example="FCFA", default="FCFA"),
     *             @OA\Property(property="description", type="string", example="Dépôt d'espèces", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Dépôt effectué avec succès dans MongoDB",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dépôt effectué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="_id", type="string", example="690768971a41d958a1079d62"),
     *                 @OA\Property(property="numeroTransaction", type="string", example="TR202511021416354Y1U"),
     *                 @OA\Property(property="type", type="string", example="depot"),
     *                 @OA\Property(property="montant", type="number", example=200000),
     *                 @OA\Property(property="compte_destinataire_id", type="string", example="a042d544-af20-4981-85d7-7b49fd7dcf4d"),
     *                 @OA\Property(property="statut", type="string", example="validee")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Erreur de validation ou compte bloqué"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Réservé aux administrateurs")
     * )
     */
    public function depot(DepotRequest $request): HttpJsonResponse
    {
        try {
            $transaction = $this->transactionService->depot($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Dépôt effectué avec succès',
                'data' => new TransactionResource($transaction)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du dépôt',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/transactions/retrait",
     *     summary="Effectuer un retrait avec calcul de solde cross-database",
     *     description="Crée une transaction de retrait dans MongoDB Atlas. Le solde est calculé en temps réel depuis MongoDB avant validation.",
     *     operationId="createRetrait",
     *     tags={"Transactions MongoDB"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"compte_source", "montant"},
     *             @OA\Property(property="compte_source", type="string", example="CP7865908264", description="Numéro du compte source"),
     *             @OA\Property(property="montant", type="number", example=95000, description="Montant du retrait en FCFA"),
     *             @OA\Property(property="devise", type="string", example="FCFA", default="FCFA"),
     *             @OA\Property(property="description", type="string", example="Retrait d'espèces", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Retrait effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Retrait effectué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="_id", type="string", example="690768971a41d958a1079d63"),
     *                 @OA\Property(property="numeroTransaction", type="string", example="TR20251102141703BVNU"),
     *                 @OA\Property(property="type", type="string", example="retrait"),
     *                 @OA\Property(property="montant", type="number", example=95000),
     *                 @OA\Property(property="compte_source_id", type="string", example="a042d544-af20-4981-85d7-7b49fd7dcf4d")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Solde insuffisant ou compte bloqué"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function retrait(RetraitRequest $request): HttpJsonResponse
    {
        try {
            $transaction = $this->transactionService->retrait($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Retrait effectué avec succès',
                'data' => new TransactionResource($transaction)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du retrait',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/transactions/transfert",
     *     summary="Effectuer un transfert avec calcul automatique des frais",
     *     description="Crée une transaction de transfert dans MongoDB Atlas avec calcul automatique des frais (0.5% du montant). Crée 2 transactions liées.",
     *     operationId="createTransfert",
     *     tags={"Transactions MongoDB"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"compte_source", "compte_destinataire", "montant"},
     *             @OA\Property(property="compte_source", type="string", example="CP7865908264", description="Numéro du compte source"),
     *             @OA\Property(property="compte_destinataire", type="string", example="CP9518897764", description="Numéro du compte destinataire"),
     *             @OA\Property(property="montant", type="number", example=125000, description="Montant du transfert en FCFA"),
     *             @OA\Property(property="devise", type="string", example="FCFA", default="FCFA"),
     *             @OA\Property(property="description", type="string", example="Transfert entre comptes", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transfert effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transfert effectué avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="_id", type="string", example="690768971a41d958a1079d64"),
     *                 @OA\Property(property="numeroTransaction", type="string", example="TR20251102141937DCPS"),
     *                 @OA\Property(property="type", type="string", example="transfert"),
     *                 @OA\Property(property="montant", type="number", example=125000),
     *                 @OA\Property(property="frais", type="number", example=625, description="Frais calculés automatiquement (0.5%)"),
     *                 @OA\Property(property="compte_source_id", type="string"),
     *                 @OA\Property(property="compte_destinataire_id", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Solde insuffisant, compte bloqué ou comptes identiques"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    public function transfert(TransfertRequest $request): HttpJsonResponse
    {
        try {
            $transaction = $this->transactionService->transfert($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Transfert effectué avec succès',
                'data' => new TransactionResource($transaction)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du transfert',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/transactions/{id}",
     *     summary="Afficher une transaction MongoDB par ID",
     *     description="Récupère les détails d'une transaction stockée dans MongoDB Atlas par son ID (_id MongoDB).",
     *     operationId="getTransaction",
     *     tags={"Transactions MongoDB"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID MongoDB de la transaction (_id ou numeroTransaction)",
     *         required=true,
     *         @OA\Schema(type="string", example="690768971a41d958a1079d62")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la transaction MongoDB",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction récupérée avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="_id", type="string", example="690768971a41d958a1079d62"),
     *                 @OA\Property(property="numeroTransaction", type="string", example="TR202511021331342CVZ"),
     *                 @OA\Property(property="type", type="string", example="depot"),
     *                 @OA\Property(property="montant", type="number", example=50000),
     *                 @OA\Property(property="statut", type="string", example="validee"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Transaction non trouvée dans MongoDB"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès interdit")
     * )
     */
    public function show(string $id): HttpJsonResponse
    {
        try {
            $transaction = $this->transactionRepository->find($id);

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction non trouvée'
                ], 404);
            }

            // Vérifier les permissions
            if (auth()->user()->role === 'client') {
                $clientId = auth()->user()->id;
                $hasAccess = false;

                // Vérifier si le client a accès à cette transaction
                if ($transaction->compte && $transaction->compte->client_id === $clientId) {
                    $hasAccess = true;
                }
                if ($transaction->compteSource && $transaction->compteSource->client_id === $clientId) {
                    $hasAccess = true;
                }
                if ($transaction->compteDestinataire && $transaction->compteDestinataire->client_id === $clientId) {
                    $hasAccess = true;
                }

                if (!$hasAccess) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Accès non autorisé à cette transaction'
                    ], 403);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Transaction récupérée avec succès',
                'data' => new TransactionResource($transaction)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/transactions/{numeroTransaction}",
     *     summary="Annuler une transaction MongoDB",
     *     description="Annule une transaction stockée dans MongoDB Atlas et effectue les opérations inverses sur les comptes PostgreSQL.",
     *     operationId="deleteTransaction",
     *     tags={"Transactions MongoDB"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="numeroTransaction",
     *         in="path",
     *         description="Numéro de la transaction à annuler",
     *         required=true,
     *         @OA\Schema(type="string", example="TR20251102141937DCPS")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction annulée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction annulée avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="statut", type="string", example="annulee")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Transaction déjà annulée ou non annulable"),
     *     @OA\Response(response=404, description="Transaction non trouvée dans MongoDB"),
     *     @OA\Response(response=401, description="Non authentifié"),
     *     @OA\Response(response=403, description="Accès interdit")
     * )
     */
    public function annuler(string $numeroTransaction): HttpJsonResponse
    {
        try {
            $transaction = $this->transactionRepository->findByNumero($numeroTransaction);

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction non trouvée'
                ], 404);
            }

            // Vérifier les permissions
            if (auth()->user()->role === 'client') {
                $clientId = auth()->user()->id;
                
                // Le client peut annuler seulement ses propres transactions
                if ($transaction->compteSource && $transaction->compteSource->client_id !== $clientId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous ne pouvez annuler que vos propres transactions'
                    ], 403);
                }
            }

            $annulation = $this->transactionService->annuler($numeroTransaction);

            return response()->json([
                'success' => true,
                'message' => 'Transaction annulée avec succès',
                'data' => new TransactionResource($annulation)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation de la transaction',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
