<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\Compte;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('💰 Génération des transactions de test...');
        
        // Récupérer tous les comptes actifs
        $comptes = Compte::where('statut', 'actif')->get();

        if ($comptes->isEmpty()) {
            $this->command->warn('⚠️  Aucun compte actif trouvé. Veuillez d\'abord créer des comptes.');
            return;
        }

        foreach ($comptes as $compte) {
            $this->createTransactionsForCompte($compte);
        }

        $totalTransactions = Transaction::count();
        $this->command->info("✅ $totalTransactions transactions générées avec succès !");
    }

    private function createTransactionsForCompte(Compte $compte): void
    {
        // Dépôt initial important (1 à 3 millions FCFA)
        $depotInitial = rand(1000000, 3000000);
                $this->createTransaction([
            'compte_id' => $compte->id,
            'type' => 'depot',
            'montant' => $depotInitial,
            'statut' => 'validee',
            'description' => 'Dépôt initial - Ouverture de compte',
            'created_at' => Carbon::now()->subMonths(6),
        ]);

        // Générer 15-25 transactions variées sur 6 mois
        $nombreTransactions = rand(15, 25);
        
        for ($i = 0; $i < $nombreTransactions; $i++) {
            $type = $this->randomTransactionType();
            $montant = $this->randomMontant($type);
            $daysAgo = rand(1, 180); // 6 mois
            
            $this->createTransaction([
                'compte_id' => $compte->id,
                'type' => $type,
                'montant' => $montant,
                'statut' => rand(1, 100) > 95 ? 'en_attente' : 'validee', // 5% en attente
                'description' => $this->getDescription($type),
                'created_at' => Carbon::now()->subDays($daysAgo),
            ]);
        }

        // Ajouter quelques transactions récentes (dernière semaine)
        for ($i = 0; $i < rand(3, 5); $i++) {
            $type = $this->randomTransactionType();
            $montant = $this->randomMontant($type);
            
            $this->createTransaction([
                'compte_id' => $compte->id,
                'type' => $type,
                'montant' => $montant,
                'statut' => 'validee',
                'description' => $this->getDescription($type),
                'created_at' => Carbon::now()->subDays(rand(0, 7)),
            ]);
        }
    }

    private function createTransfertsEntreComptes($comptes): void
    {
        $this->command->info('🔄 Création de transferts entre comptes...');
        
        // Créer 3-5 transferts aléatoires
        $nombreTransferts = rand(3, 5);
        
        for ($i = 0; $i < $nombreTransferts; $i++) {
            $compteSource = $comptes->random();
            $compteDestination = $comptes->where('id', '!=', $compteSource->id)->random();
            
            if (!$compteDestination) continue;
            
            $montant = rand(10000, 500000);
            $frais = max(100, min(5000, $montant * 0.005)); // 0.5%, min 100, max 5000
            $numeroTransaction = 'TRF' . strtoupper(Str::random(10));
            $daysAgo = rand(1, 90);
            
            // Transaction de débit (source)
            $this->createTransaction([
                'numeroTransaction' => $numeroTransaction,
                'compte_id' => $compteSource->id,
                'compte_source_id' => $compteSource->id,
                'compte_destinataire_id' => $compteDestination->id,
                'type' => 'transfert',
                'montant' => $montant,
                'frais' => $frais,
                'statut' => 'validee',
                'description' => "Transfert vers compte {$compteDestination->numero_compte}",
                'created_at' => Carbon::now()->subDays($daysAgo),
            ]);
            
            // Transaction de crédit (destination)
            $this->createTransaction([
                'numeroTransaction' => $numeroTransaction,
                'compte_id' => $compteDestination->id,
                'compte_source_id' => $compteSource->id,
                'compte_destinataire_id' => $compteDestination->id,
                'type' => 'transfert',
                'montant' => $montant,
                'frais' => 0,
                'statut' => 'validee',
                'description' => "Transfert reçu de compte {$compteSource->numero_compte}",
                'created_at' => Carbon::now()->subDays($daysAgo),
            ]);
        }
    }

    private function createTransaction(array $data): void
    {
        // Générer un numéro de transaction si non fourni
        if (!isset($data['numeroTransaction'])) {
            $prefix = strtoupper(substr($data['type'], 0, 3));
            $data['numeroTransaction'] = $prefix . strtoupper(Str::random(10));
        }

        // Ajouter la devise par défaut
        if (!isset($data['devise'])) {
            $data['devise'] = 'FCFA';
        }

        // Définir les frais si non fournis
        if (!isset($data['frais'])) {
            $data['frais'] = 0;
        }

        Transaction::create($data);
    }

    private function randomTransactionType(): string
    {
        $types = ['depot', 'depot', 'depot', 'retrait', 'retrait']; // Plus de dépôts
        return $types[array_rand($types)];
    }

    private function randomMontant(string $type): int
    {
        if ($type === 'depot') {
            // Dépôts: 10,000 à 2,000,000 FCFA
            return rand(10000, 2000000);
        } else {
            // Retraits: 5,000 à 500,000 FCFA (plus petits)
            return rand(5000, 500000);
        }
    }

    private function getDescription(string $type): string
    {
        $descriptions = [
            'depot' => [
                'Dépôt en espèces',
                'Virement reçu',
                'Versement salaire',
                'Dépôt chèque',
                'Remise espèces',
                'Transfert entrant',
                'Dépôt guichet',
            ],
            'retrait' => [
                'Retrait DAB',
                'Retrait guichet',
                'Retrait espèces',
                'Paiement facture',
                'Retrait carte bancaire',
                'Prélèvement automatique',
                'Retrait distributeur',
            ],
        ];

        $typeDescriptions = $descriptions[$type] ?? ['Transaction'];
        return $typeDescriptions[array_rand($typeDescriptions)];
    }
}
