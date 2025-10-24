<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\Compte;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer tous les comptes
        $comptes = Compte::all();

        if ($comptes->isEmpty()) {
            $this->command->warn('Aucun compte trouvé. Veuillez d\'abord créer des comptes.');
            return;
        }

        $this->command->info('Génération des transactions...');

        foreach ($comptes as $compte) {
            // Créer des transactions variées pour chaque compte
            $this->createTransactionsForCompte($compte);
        }

        $this->command->info('Transactions générées avec succès !');
    }

    private function createTransactionsForCompte(Compte $compte): void
    {
        // Suivi du solde pour éviter les négatifs
        $solde = 0;

        // Dépôt initial important
        $depotInitial = 500000;
        Transaction::create([
            'compte_id' => $compte->id,
            'type' => 'depot',
            'montant' => $depotInitial,
            'statut' => 'complete',
            'date' => Carbon::parse('2023-10-13'),
        ]);
        $solde += $depotInitial;

        // Retrait
        $retrait1 = 100000;
        Transaction::create([
            'compte_id' => $compte->id,
            'type' => 'retrait',
            'montant' => $retrait1,
            'statut' => 'complete',
            'date' => Carbon::parse('2023-10-14'),
        ]);
        $solde -= $retrait1;

        // Dépôt
        $depot2 = 250000;
        Transaction::create([
            'compte_id' => $compte->id,
            'type' => 'depot',
            'montant' => $depot2,
            'statut' => 'complete',
            'date' => Carbon::parse('2023-10-15'),
        ]);
        $solde += $depot2;

        // Retrait
        $retrait2 = 75000;
        Transaction::create([
            'compte_id' => $compte->id,
            'type' => 'retrait',
            'montant' => $retrait2,
            'statut' => 'complete',
            'date' => Carbon::parse('2023-10-15'),
        ]);
        $solde -= $retrait2;

        // Dépôt
        $depot3 = 150000;
        Transaction::create([
            'compte_id' => $compte->id,
            'type' => 'depot',
            'montant' => $depot3,
            'statut' => 'complete',
            'date' => Carbon::parse('2023-10-16'),
        ]);
        $solde += $depot3;

        // Transactions aléatoires supplémentaires (garantir solde positif)
        for ($i = 0; $i < 3; $i++) {
            // Alterner dépôt/retrait en gardant solde positif
            if ($i % 2 == 0 || $solde < 100000) {
                // Dépôt
                $montant = fake()->numberBetween(50000, 200000);
                Transaction::create([
                    'compte_id' => $compte->id,
                    'type' => 'depot',
                    'montant' => $montant,
                    'statut' => 'complete',
                    'date' => fake()->dateTimeBetween('-2 months', 'now'),
                ]);
                $solde += $montant;
            } else {
                // Retrait (maximum 30% du solde actuel)
                $montant = fake()->numberBetween(10000, min(100000, (int)($solde * 0.3)));
                Transaction::create([
                    'compte_id' => $compte->id,
                    'type' => 'retrait',
                    'montant' => $montant,
                    'statut' => 'complete',
                    'date' => fake()->dateTimeBetween('-2 months', 'now'),
                ]);
                $solde -= $montant;
            }
        }

        $this->command->info("Transactions créées pour le compte {$compte->numeroCompte} - Solde: {$solde} FCFA");
    }
}
