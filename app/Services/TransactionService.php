<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Compte;
use App\Repositories\TransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TransactionService
{
    protected TransactionRepositoryInterface $transactionRepository;

    public function __construct(TransactionRepositoryInterface $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Effectuer un dépôt.
     *
     * @param array $data
     * @return Transaction
     * @throws \Exception
     */
    public function depot(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $compte = Compte::where('numeroCompte', $data['compte_destinataire'])->firstOrFail();

            // Vérifier que le compte est actif
            if (!$compte->isActif()) {
                throw new \Exception('Le compte destinataire n\'est pas actif');
            }

            // Créer la transaction
            $transaction = $this->transactionRepository->create([
                'numeroTransaction' => $this->generateNumeroTransaction(),
                'type' => 'depot',
                'montant' => $data['montant'],
                'devise' => $data['devise'] ?? $compte->devise,
                'frais' => 0,
                'statut' => 'validee',
                'compte_id' => $compte->id,
                'compte_destinataire_id' => $compte->id,
                'description' => $data['description'] ?? 'Dépôt sur le compte',
            ]);

            return $transaction;
        });
    }

    /**
     * Effectuer un retrait.
     *
     * @param array $data
     * @return Transaction
     * @throws \Exception
     */
    public function retrait(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $compte = Compte::where('numeroCompte', $data['compte_source'])->firstOrFail();

            // Vérifier que le compte est actif
            if (!$compte->isActif()) {
                throw new \Exception('Le compte source n\'est pas actif');
            }

            // Vérifier le solde
            if ($compte->getSolde() < $data['montant']) {
                throw new \Exception('Solde insuffisant');
            }

            // Créer la transaction
            $transaction = $this->transactionRepository->create([
                'numeroTransaction' => $this->generateNumeroTransaction(),
                'type' => 'retrait',
                'montant' => $data['montant'],
                'devise' => $data['devise'] ?? $compte->devise,
                'frais' => 0,
                'statut' => 'validee',
                'compte_id' => $compte->id,
                'compte_source_id' => $compte->id,
                'description' => $data['description'] ?? 'Retrait du compte',
            ]);

            return $transaction;
        });
    }

    /**
     * Effectuer un transfert.
     *
     * @param array $data
     * @return Transaction
     * @throws \Exception
     */
    public function transfert(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $compteSource = Compte::where('numeroCompte', $data['compte_source'])->firstOrFail();
            $compteDestinataire = Compte::where('numeroCompte', $data['compte_destinataire'])->firstOrFail();

            // Vérifier que les comptes sont différents
            if ($compteSource->id === $compteDestinataire->id) {
                throw new \Exception('Les comptes source et destinataire doivent être différents');
            }

            // Vérifier que les comptes sont actifs
            if (!$compteSource->isActif()) {
                throw new \Exception('Le compte source n\'est pas actif');
            }
            if (!$compteDestinataire->isActif()) {
                throw new \Exception('Le compte destinataire n\'est pas actif');
            }

            // Calculer les frais (0.5%, min 100 FCFA, max 5000 FCFA)
            $frais = $this->calculateFrais($data['montant']);

            // Vérifier le solde (montant + frais)
            $montantTotal = $data['montant'] + $frais;
            if ($compteSource->getSolde() < $montantTotal) {
                throw new \Exception("Solde insuffisant. Montant: {$data['montant']}, Frais: {$frais}, Total: {$montantTotal}");
            }

            // Créer la transaction
            $transaction = $this->transactionRepository->create([
                'numeroTransaction' => $this->generateNumeroTransaction(),
                'type' => 'transfert',
                'montant' => $data['montant'],
                'devise' => $data['devise'] ?? $compteSource->devise,
                'frais' => $frais,
                'statut' => 'validee',
                'compte_id' => $compteSource->id,
                'compte_source_id' => $compteSource->id,
                'compte_destinataire_id' => $compteDestinataire->id,
                'description' => $data['description'] ?? "Transfert vers {$compteDestinataire->numeroCompte}",
            ]);

            return $transaction;
        });
    }

    /**
     * Annuler une transaction.
     *
     * @param string $numeroTransaction
     * @return Transaction
     * @throws \Exception
     */
    public function annuler(string $numeroTransaction): Transaction
    {
        return DB::transaction(function () use ($numeroTransaction) {
            $transaction = $this->transactionRepository->findByNumero($numeroTransaction);

            if (!$transaction) {
                throw new \Exception('Transaction non trouvée');
            }

            // Vérifier que la transaction est validée
            if ($transaction->statut !== 'validee') {
                throw new \Exception('Seules les transactions validées peuvent être annulées');
            }

            // Vérifier que la transaction a moins de 24h
            if ($transaction->created_at->diffInHours(now()) > 24) {
                throw new \Exception('Les transactions de plus de 24h ne peuvent pas être annulées');
            }

            // Vérifier le type de transaction
            if (!in_array($transaction->type, ['depot', 'retrait', 'transfert'])) {
                throw new \Exception('Ce type de transaction ne peut pas être annulé');
            }

            // Pour les retraits et transferts, vérifier le solde pour l'annulation
            if ($transaction->type === 'retrait') {
                // Un retrait annulé = remboursement du montant
                // Pas besoin de vérifier le solde
            } elseif ($transaction->type === 'transfert') {
                // Un transfert annulé = remboursement du montant + frais au compte source
                // et débit du montant au compte destinataire
                if ($transaction->compte_destinataire_id) {
                    $compteDestinataire = \App\Models\Compte::find($transaction->compte_destinataire_id);
                    if ($compteDestinataire && $compteDestinataire->getSolde() < $transaction->montant) {
                        throw new \Exception('Le compte destinataire n\'a pas un solde suffisant pour annuler le transfert');
                    }
                }
            } elseif ($transaction->type === 'depot') {
                // Un dépôt annulé = débit du montant
                if ($transaction->compte_destinataire_id) {
                    $compteDestinataire = \App\Models\Compte::find($transaction->compte_destinataire_id);
                    if ($compteDestinataire && $compteDestinataire->getSolde() < $transaction->montant) {
                        throw new \Exception('Le compte n\'a pas un solde suffisant pour annuler le dépôt');
                    }
                }
            }

            // Créer la transaction d'annulation
            $annulation = $this->transactionRepository->create([
                'numeroTransaction' => $this->generateNumeroTransaction(),
                'type' => 'annulation',
                'montant' => $transaction->montant,
                'devise' => $transaction->devise,
                'frais' => -$transaction->frais, // Remboursement des frais
                'statut' => 'validee',
                'compte_id' => $transaction->compte_id,
                'compte_source_id' => $transaction->compte_destinataire_id, // Inversion pour l'annulation
                'compte_destinataire_id' => $transaction->compte_source_id, // Inversion pour l'annulation
                'description' => "Annulation de la transaction {$transaction->numeroTransaction}",
                'transaction_parent_id' => $transaction->id,
            ]);

            // Mettre à jour le statut de la transaction originale
            $this->transactionRepository->update($transaction, [
                'statut' => 'annulee',
            ]);

            return $annulation;
        });
    }

    /**
     * Calculer les frais de transfert.
     *
     * @param float $montant
     * @return float
     */
    protected function calculateFrais(float $montant): float
    {
        $frais = $montant * 0.005; // 0.5%
        return max(100, min(5000, $frais));
    }

    /**
     * Générer un numéro de transaction unique.
     *
     * @return string
     */
    protected function generateNumeroTransaction(): string
    {
        do {
            $numero = 'TR' . now()->format('YmdHis') . strtoupper(Str::random(4));
        } while (Transaction::where('numeroTransaction', $numero)->exists());

        return $numero;
    }
}
