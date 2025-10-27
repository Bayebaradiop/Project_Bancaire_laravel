<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Compte;
use App\Models\Client;
use App\Models\User;
use App\Services\CompteArchiveService;
use Illuminate\Support\Facades\Hash;

class CompteArchiveTestSeeder extends Seeder
{
    /**
     * Seeder pour tester la fonctionnalité d'archivage
     * 
     * Ce seeder crée:
     * - 3 comptes épargne actifs
     * - 2 comptes épargne qui seront archivés
     * 
     * Run this seeder with:
     * php artisan db:seed --class=CompteArchiveTestSeeder
     */
    public function run(): void
    {
        $this->command->info('🔄 Début du seeding pour tester l\'archivage...');
        
        // 1. Récupérer ou créer un admin pour l'archivage
        $admin = User::where('email', 'admin@banque.sn')->first();
        
        if (!$admin) {
            $this->command->warn('⚠️  Admin non trouvé. Création d\'un admin de test...');
            $admin = User::create([
                'nomComplet' => 'Admin Test',
                'email' => 'admin@banque.sn',
                'telephone' => '+221771111111',
                'adresse' => 'Dakar',
                'role' => 'admin',
                'password' => Hash::make('password'),
            ]);
        }
        
        // 2. Créer des clients de test pour les comptes épargne
        $clients = [];
        $timestamp = time(); // Pour garantir l'unicité
        
        for ($i = 1; $i <= 5; $i++) {
            $telephone = "+221779{$timestamp}{$i}";
            $nci = "2{$timestamp}{$i}"; // NCI unique basé sur timestamp
            
            // Vérifier si le client existe déjà
            $existingUser = User::where('telephone', $telephone)->first();
            
            if ($existingUser && $existingUser->client) {
                $clients[] = $existingUser->client;
                $this->command->info("✓ Client existant utilisé: {$existingUser->nomComplet}");
                continue;
            }
            
            // Créer un nouveau user et client
            $user = User::create([
                'nomComplet' => "Client Épargne Test {$i}",
                'nci' => $nci,
                'email' => "epargne.test.{$timestamp}.{$i}@example.com",
                'telephone' => $telephone,
                'adresse' => "Adresse Test {$i}",
                'role' => 'client',
                'password' => Hash::make('password'),
            ]);
            
            $client = Client::create([
                'user_id' => $user->id,
            ]);
            
            $clients[] = $client;
            $this->command->info("✓ Client créé: {$user->nomComplet}");
        }
        
        // 3. Créer 5 comptes épargne
        $comptesEpargne = [];
        $baseNumero = substr($timestamp, -6); // 6 derniers chiffres du timestamp
        
        foreach ($clients as $index => $client) {
            $compte = Compte::create([
                'numeroCompte' => 'CE' . $baseNumero . str_pad($index, 4, '0', STR_PAD_LEFT),
                'client_id' => $client->id,
                'type' => 'epargne',
                'devise' => 'FCFA',
                'statut' => 'actif',
            ]);
            
            $comptesEpargne[] = $compte;
            $this->command->info("✓ Compte épargne créé: {$compte->numeroCompte}");
        }
        
        $this->command->info('');
        $this->command->info('📦 Archivage de 2 comptes épargne...');
        
        // 4. Archiver 2 comptes épargne (les 2 derniers)
        $archiveService = app(CompteArchiveService::class);
        
        $raisons = [
            'Inactif depuis 12 mois',
            'Compte fermé à la demande du client',
        ];
        
        for ($i = 0; $i < 2; $i++) {
            $compteToArchive = $comptesEpargne[3 + $i]; // Les 2 derniers comptes
            
            try {
                $archive = $archiveService->archiveCompte(
                    $compteToArchive,
                    $admin,
                    $raisons[$i]
                );
                
                $this->command->info("✓ Compte archivé: {$compteToArchive->numeroCompte} - Raison: {$raisons[$i]}");
                $this->command->info("  → Stocké dans Neon avec ID: {$archive->id}");
                
            } catch (\Exception $e) {
                $this->command->error("✗ Erreur lors de l'archivage de {$compteToArchive->numeroCompte}: {$e->getMessage()}");
            }
        }
        
        $this->command->info('');
        $this->command->info('✅ Seeding terminé avec succès !');
        $this->command->info('');
        $this->command->table(
            ['Type', 'Nombre'],
            [
                ['Clients créés', count($clients)],
                ['Comptes épargne actifs', 3],
                ['Comptes épargne archivés', 2],
            ]
        );
        
        $this->command->info('');
        $this->command->info('🧪 Pour tester l\'archivage:');
        $this->command->info('1. Liste des comptes actifs:');
        $this->command->info('   GET /api/v1/comptes');
        $this->command->info('');
        $this->command->info('2. Liste des comptes archivés:');
        $this->command->info('   GET /api/v1/comptes/archives');
        $this->command->info('');
        $this->command->info('3. Archiver un compte actif:');
        $this->command->info('   POST /api/v1/comptes/CE09990000000/archive');
        $this->command->info('');
    }
}
