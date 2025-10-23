<?php

namespace Database\Seeders;

use App\Models\Compte;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // Créer 5 clients avec leurs utilisateurs
            $clients = Client::factory(5)->create();

            // Pour chaque client, créer 2 comptes (1 chèque et 1 épargne)
            foreach ($clients as $client) {
                // Compte chèque
                Compte::factory()->cheque()->create([
                    'client_id' => $client->id,
                ]);

                // Compte épargne
                Compte::factory()->epargne()->create([
                    'client_id' => $client->id,
                ]);
            }

            // Créer quelques comptes avec différents statuts
            $clientTest = $clients->first();

            // Compte bloqué
            Compte::factory()->bloque()->create([
                'client_id' => $clientTest->id,
            ]);

            // Compte fermé
            Compte::factory()->ferme()->create([
                'client_id' => $clientTest->id,
            ]);

            DB::commit();

            $this->command->info('Comptes créés avec succès!');
            $this->command->info('Total clients: ' . Client::count());
            $this->command->info('Total comptes: ' . Compte::count());
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Erreur lors de la création des comptes: ' . $e->getMessage());
        }
    }
}
