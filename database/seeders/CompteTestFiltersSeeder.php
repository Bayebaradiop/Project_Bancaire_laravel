<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;
use App\Models\Compte;
use Illuminate\Support\Facades\Hash;

class CompteTestFiltersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Crée des comptes de test pour tester les filtres
     */
    public function run(): void
    {
        // Créer plusieurs clients avec différents comptes
        $clients = [];
        
        $timestamp = time();
        
        // Client 1 - Abdoulaye DIOP
        $user1 = User::create([
            'nomComplet' => 'Abdoulaye DIOP',
            'nci' => '1234567890' . rand(100, 999),
            'email' => 'abdoulaye.diop.' . $timestamp . '@example.com',
            'telephone' => '+22177' . rand(1000000, 9999999),
            'adresse' => 'Dakar, Plateau',
            'password' => Hash::make('password123'),
            'code' => strtoupper(substr(md5(uniqid()), 0, 8)),
        ]);
        $clients[] = Client::create(['user_id' => $user1->id]);

        // Client 2 - Fatou SALL
        $user2 = User::create([
            'nomComplet' => 'Fatou SALL',
            'nci' => '2234567890' . rand(100, 999),
            'email' => 'fatou.sall.' . $timestamp . '@example.com',
            'telephone' => '+22177' . rand(1000000, 9999999),
            'adresse' => 'Dakar, Almadies',
            'password' => Hash::make('password123'),
            'code' => strtoupper(substr(md5(uniqid()), 0, 8)),
        ]);
        $clients[] = Client::create(['user_id' => $user2->id]);

        // Client 3 - Moussa NDIAYE
        $user3 = User::create([
            'nomComplet' => 'Moussa NDIAYE',
            'nci' => '3234567890' . rand(100, 999),
            'email' => 'moussa.ndiaye.' . $timestamp . '@example.com',
            'telephone' => '+22177' . rand(1000000, 9999999),
            'adresse' => 'Thiès',
            'password' => Hash::make('password123'),
            'code' => strtoupper(substr(md5(uniqid()), 0, 8)),
        ]);
        $clients[] = Client::create(['user_id' => $user3->id]);

        // Client 4 - Aissatou BA
        $user4 = User::create([
            'nomComplet' => 'Aissatou BA',
            'nci' => '4234567890' . rand(100, 999),
            'email' => 'aissatou.ba.' . $timestamp . '@example.com',
            'telephone' => '+22177' . rand(1000000, 9999999),
            'adresse' => 'Saint-Louis',
            'password' => Hash::make('password123'),
            'code' => strtoupper(substr(md5(uniqid()), 0, 8)),
        ]);
        $clients[] = Client::create(['user_id' => $user4->id]);

        // Client 5 - Mamadou FALL
        $user5 = User::create([
            'nomComplet' => 'Mamadou FALL',
            'nci' => '5234567890' . rand(100, 999),
            'email' => 'mamadou.fall.' . $timestamp . '@example.com',
            'telephone' => '+22177' . rand(1000000, 9999999),
            'adresse' => 'Kaolack',
            'password' => Hash::make('password123'),
            'code' => strtoupper(substr(md5(uniqid()), 0, 8)),
        ]);
        $clients[] = Client::create(['user_id' => $user5->id]);

        // Créer des comptes variés pour tester les filtres
        $comptesData = [
            // Comptes ÉPARGNE en FCFA
            [
                'client_id' => $clients[0]->id,
                'type' => 'epargne',
                'devise' => 'FCFA',
                'statut' => 'actif',
            ],
            [
                'client_id' => $clients[0]->id,
                'type' => 'epargne',
                'devise' => 'FCFA',
                'statut' => 'actif',
            ],
            [
                'client_id' => $clients[1]->id,
                'type' => 'epargne',
                'devise' => 'FCFA',
                'statut' => 'actif',
            ],
            [
                'client_id' => $clients[1]->id,
                'type' => 'epargne',
                'devise' => 'FCFA',
                'statut' => 'actif',
            ],
            [
                'client_id' => $clients[2]->id,
                'type' => 'epargne',
                'devise' => 'FCFA',
                'statut' => 'actif',
            ],
            
            // Comptes CHÈQUE en FCFA
            [
                'client_id' => $clients[0]->id,
                'type' => 'cheque',
                'devise' => 'FCFA',
                'statut' => 'actif',
            ],
            [
                'client_id' => $clients[2]->id,
                'type' => 'cheque',
                'devise' => 'FCFA',
                'statut' => 'actif',
            ],
            [
                'client_id' => $clients[3]->id,
                'type' => 'cheque',
                'devise' => 'FCFA',
                'statut' => 'actif',
            ],
            [
                'client_id' => $clients[3]->id,
                'type' => 'cheque',
                'devise' => 'FCFA',
                'statut' => 'actif',
            ],
            [
                'client_id' => $clients[4]->id,
                'type' => 'cheque',
                'devise' => 'FCFA',
                'statut' => 'actif',
            ],
            [
                'client_id' => $clients[4]->id,
                'type' => 'cheque',
                'devise' => 'FCFA',
                'statut' => 'actif',
            ],
            
            // Comptes en EURO pour tester le filtre devise
            [
                'client_id' => $clients[1]->id,
                'type' => 'epargne',
                'devise' => 'EUR',
                'statut' => 'actif',
            ],
            [
                'client_id' => $clients[2]->id,
                'type' => 'cheque',
                'devise' => 'EUR',
                'statut' => 'actif',
            ],
            
            // Comptes en USD
            [
                'client_id' => $clients[3]->id,
                'type' => 'epargne',
                'devise' => 'USD',
                'statut' => 'actif',
            ],
            [
                'client_id' => $clients[4]->id,
                'type' => 'cheque',
                'devise' => 'USD',
                'statut' => 'actif',
            ],
        ];

        // Créer tous les comptes
        foreach ($comptesData as $data) {
            Compte::create([
                'numeroCompte' => Compte::generateNumeroCompte(),
                'client_id' => $data['client_id'],
                'type' => $data['type'],
                'devise' => $data['devise'],
                'statut' => $data['statut'],
            ]);
        }

        $this->command->info('✅ Création de 15 comptes de test pour les filtres');
        $this->command->info('   - 5 clients différents');
        $this->command->info('   - 7 comptes épargne (5 FCFA, 1 EUR, 1 USD)');
        $this->command->info('   - 8 comptes chèque (6 FCFA, 1 EUR, 1 USD)');
    }
}
