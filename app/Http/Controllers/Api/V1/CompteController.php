    /**
     * @OA\Get(
     *     path="/v1/comptes/{id}",
     *     summary="Obtenir un compte par ID",
     *     description="Récupère les détails d'un compte bancaire par son ID. Cherche d'abord dans la base locale (Render), puis dans les archives (Neon) si non trouvé. Admin peut voir tous les comptes, Client uniquement ses propres comptes.",
     *     operationId="getCompteById",
     *     tags={"Comptes"},
     *     security={{"cookieAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID UUID du compte",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte récupéré avec succès",
     *                     @OA\Property(property="type", type="string", example="epargne"),
     *                     @OA\Property(property="solde", type="number", example=150000),
     *                     @OA\Property(property="devise", type="string", example="FCFA")
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
        $user = $request->user();
        
        // Déléguer toute la logique au service avec autorisation
        $response = $this->compteService->getComptesList($request, $user);
        
        // Retourner la réponse
        return response()->json($response);
    }

    /**
     * @OA\Get(
     *     path="/v1/comptes/numero/{numero}",
     *     summary="Obtenir un compte par numéro",
     *     description="Récupère les détails complets d'un compte bancaire en utilisant son numéro de compte. Cherche automatiquement dans la base principale (Render) et dans les archives (Neon) si le compte est fermé, bloqué ou archivé.",
     *     operationId="getCompteByNumero",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="numero",
     *         in="path",
     *         description="Numéro du compte (format: CPxxxxxxxxxx)",
     *         required=true,
     *         @OA\Schema(type="string", example="CP3105472638")
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
     *                 @OA\Property(property="numeroCompte", type="string", example="CP3105472638"),
     *                 @OA\Property(property="titulaire", type="string", example="Mamadou Diop"),
     *                 @OA\Property(property="type", type="string", example="epargne"),
     *                 @OA\Property(property="solde", type="number", example=150000),
     *                 @OA\Property(property="devise", type="string", example="FCFA"),
     *                 @OA\Property(property="dateCreation", type="string", format="date-time"),
     *                 @OA\Property(property="statut", type="string", example="actif"),
     *                 @OA\Property(property="archived", type="boolean", example=false, description="Indique si le compte est archivé dans Neon")
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
     *     )
     * )
     */
    public function showByNumero(string $numero): JsonResponse
    {
        try {
            $user = auth()->user();
            $result = $this->compteService->getCompteByNumero($numero, $user);

            // Gérer les erreurs
            if (isset($result['error'])) {
                return match($result['code']) {
                    403 => $this->error($result['message'], 403),
                    404 => $this->notFound($result['message']),
                    default => $this->serverError($result['message'])
                };
            }

            // Succès
            return $this->success($result['data'], $result['message']);

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
     *     summary="Créer un nouveau compte bancaire",
     *     description="Crée un nouveau compte bancaire avec validation complète (NCI, téléphone, email). Le mot de passe est généré automatiquement et envoyé par email.",
     *     tags={"Comptes"},
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
            $result = $this->compteService->createCompte($request->validated());

            // Invalider le cache de la liste des comptes
            $this->forgetPaginatedCache('comptes:list');

            return $this->created($result['data'], $result['message']);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), 'Les données fournies sont invalides');

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug') 
                    ? 'Une erreur est survenue : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors de la création du compte'
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/v1/comptes/archives",
     *     summary="Lister les comptes archivés",
     *     description="Récupère la liste des comptes épargne archivés depuis le cloud (Neon). Les administrateurs voient tous les comptes archivés, les clients ne voient que leurs propres comptes archivés.",
     *     operationId="getArchivedComptes",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes archivés récupérée avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Comptes archivés récupérés avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="numeroCompte", type="string"),
     *                     @OA\Property(property="type", type="string", example="epargne"),
     *                     @OA\Property(property="solde", type="number", format="float"),
     *                     @OA\Property(property="archived_at", type="string", format="date-time"),
     *                     @OA\Property(property="archive_reason", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
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
     * @OA\Post(
     *     path="/v1/comptes/{numeroCompte}/archive",
     *     summary="Archiver un compte épargne",
     *     description="Archive un compte épargne vers le cloud (Neon). Seuls les administrateurs peuvent archiver des comptes.",
     *     operationId="archiveCompte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="numeroCompte",
     *         in="path",
     *         description="Numéro du compte à archiver",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="reason", type="string", example="Inactif depuis 12 mois")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte archivé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Compte archivé avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="archived_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Non autorisé - seuls les administrateurs peuvent archiver",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Seuls les administrateurs peuvent archiver des comptes")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Compte non trouvé")
     *         )
     *     )
     * )
     */
    public function archive(string $numeroCompte, Request $request): JsonResponse
    {
        try {
            $reason = $request->input('reason');
            $result = $this->compteService->archiveCompte($numeroCompte, $reason);

            // Gérer les erreurs
            if (isset($result['error'])) {
                return match($result['code']) {
                    404 => $this->notFound($result['message']),
                    default => $this->serverError($result['message'])
                };
            }

            return $this->success($result['data'], $result['message']);

        } catch (\Exception $e) {
            return $this->serverError(
                config('app.debug') 
                    ? 'Une erreur est survenue : ' . $e->getMessage() 
                    : 'Une erreur est survenue lors de l\'archivage du compte'
            );
        }
    }
}
