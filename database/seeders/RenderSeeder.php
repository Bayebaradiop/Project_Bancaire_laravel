<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Compte;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RenderSeeder extends Seeder
{
    /**
     * Seeder pour la base RENDER (PostgreSQL - Base principale)
     * Crée les utilisateurs et comptes pour la production
     */
    public function run(): void
    {
        DB::connection('pgsql')->beginTransaction();

        try {
            $this->command->info('🚀 Seeding RENDER (PostgreSQL) - Base principale');
            
            // 1. Créer Admin
            $this->seedAdmin();
            
            // 2. Créer Clients de test
            $this->seedClients();
            
            // 3. Créer Comptes
            $this->seedComptes();
            
            DB::connection('pgsql')->commit();
            
            $this->command->info('✅ RENDER seeding completed!');
            $this->command->info('📊 Total users: ' . User::count());
            $this->command->info('📊 Total clients: ' . Client::count());
            $this->command->info('📊 Total comptes: ' . Compte::count());
            
        } catch (\Exception $e) {
            DB::connection('pgsql')->rollBack();
            $this->command->error('❌ Erreur: ' . $e->getMessage());
            throw $e;
        }
    }

    private function seedAdmin(): void
    {
        if (!User::where('email', 'admin@banque.sn')->exists()) {
            User::create([
                'nomComplet' => 'Administrateur Système',
                'nci' => '1234567890123456',
                'email' => 'admin@banque.sn',
                'telephone' => '+221771234567',
                'adresse' => 'Dakar, Sénégal',
                'password' => Hash::make('Admin@2025'),
                'role' => 'admin',
                'statut' => 'actif',
            ]);
            $this->command->info('  ✅ Admin créé: admin@banque.sn');
        } else {
            $this->command->info('  ⚠️  Admin existe déjà');
        }

        if (!User::where('email', 'client@banque.sn')->exists()) {
            User::create([
                'nomComplet' => 'Client Test',
                'nci' => '9876543210987654',
                'email' => 'client@banque.sn',
                'telephone' => '+221779876543',
                'adresse' => 'Dakar, Sénégal',
                'password' => Hash::make('Client@2025'),
                'role' => 'client',
                'statut' => 'actif',
            ]);
            $this->command->info('  ✅ Client test créé: client@banque.sn');
        } else {
            $this->command->info('  ⚠️  Client test existe déjà');
        }
    }

    private function seedClients(): void
    {
        // Créer 5 clients avec Factory
        $clients = Client::factory(5)->create();
        $this->command->info('  ✅ 5 clients créés avec Factory');
    }

    private function seedComptes(): void
    {
        $clients = Client::all();
        
        if ($clients->count() === 0) {
            $this->command->warn('  ⚠️  Aucun client disponible pour créer des comptes');
            return;
        }

        // Pour chaque client, créer 2 comptes ACTIFS (1 chèque et 1 épargne)
        foreach ($clients as $client) {
            Compte::factory()->cheque()->create([
                'client_id' => $client->id,
                'statut' => 'actif',  // ACTIF pour Render
            ]);

            Compte::factory()->epargne()->create([
                'client_id' => $client->id,
                'statut' => 'actif',  // ACTIF pour Render
            ]);
        }

        $this->command->info('  ✅ Comptes ACTIFS créés (chèque et épargne) pour Render');
    }
}
