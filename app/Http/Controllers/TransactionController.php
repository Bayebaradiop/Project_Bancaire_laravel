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
     * Lister les transactions avec filtres.
     *
     * @param Request $request
     * @return JsonResponse
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
     * Effectuer un dépôt (Admin uniquement).
     *
     * @param DepotRequest $request
     * @return JsonResponse
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
     * Effectuer un retrait.
     *
     * @param RetraitRequest $request
     * @return JsonResponse
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
     * Effectuer un transfert.
     *
     * @param TransfertRequest $request
     * @return JsonResponse
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
     * Afficher une transaction spécifique.
     *
     * @param string $id
     * @return JsonResponse
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
     * Annuler une transaction.
     *
     * @param string $numeroTransaction
     * @return JsonResponse
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
