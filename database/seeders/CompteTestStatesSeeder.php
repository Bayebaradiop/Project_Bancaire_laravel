<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Compte;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder pour tester les différents états des comptes
 * 
 * Ce seeder crée des comptes dans différents états pour tester
 * les règles de filtrage de l'US 2.0:
 * - Comptes épargne actifs ✅ (visibles)
 * - Comptes épargne bloqués ❌ (invisibles)
 * - Comptes chèque actifs ✅ (visibles)
 * - Comptes chèque bloqués ✅ (visibles)
 * - Comptes soft deleted ❌ (invisibles)
 * - Comptes archivés ❌ (invisibles - dans Neon)
 * 
 * Run: php artisan db:seed --class=CompteTestStatesSeeder
 */
class CompteTestStatesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔄 Création des comptes de test pour différents états...');
        
        // Créer ou récupérer un client de test
        $user = User::firstOrCreate(
            ['email' => 'test.states@example.com'],
            [
                'nomComplet' => 'Client Test États',
                'nci' => '2999999999999',
                'telephone' => '+221779999999',
                'adresse' => 'Test Address',
                'role' => 'client',
                'password' => Hash::make('password'),
            ]
        );
        
        $client = Client::firstOrCreate(['user_id' => $user->id]);
        
        $this->command->info("✓ Client créé: {$user->nomComplet}");
        
        // 1. Compte ÉPARGNE ACTIF ✅ (doit être visible)
        $epargneActif = Compte::create([
            'numeroCompte' => 'TEST_EP_ACTIF',
            'client_id' => $client->id,
            'type' => 'epargne',
            'statut' => 'actif',
            'devise' => 'FCFA',
        ]);
        $this->command->info("✅ Compte épargne ACTIF créé: {$epargneActif->numeroCompte}");
        
        // 2. Compte ÉPARGNE BLOQUÉ ❌ (ne doit PAS être visible)
        $epargneBloque = Compte::create([
            'numeroCompte' => 'TEST_EP_BLOQUE',
            'client_id' => $client->id,
            'type' => 'epargne',
            'statut' => 'bloque',
            'motifBlocage' => 'Test blocage',
            'devise' => 'FCFA',
        ]);
        $this->command->info("❌ Compte épargne BLOQUÉ créé: {$epargneBloque->numeroCompte}");
        
        // 3. Compte ÉPARGNE FERMÉ ❌ (ne doit PAS être visible)
        $epagneFerme = Compte::create([
            'numeroCompte' => 'TEST_EP_FERME',
            'client_id' => $client->id,
            'type' => 'epargne',
            'statut' => 'ferme',
            'motifBlocage' => 'Compte fermé',
            'devise' => 'FCFA',
        ]);
        $this->command->info("❌ Compte épargne FERMÉ créé: {$epagneFerme->numeroCompte}");
        
        // 4. Compte CHÈQUE ACTIF ✅ (doit être visible)
        $chequeActif = Compte::create([
            'numeroCompte' => 'TEST_CQ_ACTIF',
            'client_id' => $client->id,
            'type' => 'cheque',
            'statut' => 'actif',
            'devise' => 'FCFA',
        ]);
        $this->command->info("✅ Compte chèque ACTIF créé: {$chequeActif->numeroCompte}");
        
        // 5. Compte CHÈQUE BLOQUÉ ✅ (doit être visible quand même)
        $chequeBloque = Compte::create([
            'numeroCompte' => 'TEST_CQ_BLOQUE',
            'client_id' => $client->id,
            'type' => 'cheque',
            'statut' => 'bloque',
            'motifBlocage' => 'Test blocage chèque',
            'devise' => 'FCFA',
        ]);
        $this->command->info("✅ Compte chèque BLOQUÉ créé: {$chequeBloque->numeroCompte} (visible car type=cheque)");
        
        // 6. Compte CHÈQUE FERMÉ ✅ (doit être visible quand même)
        $chequeFerme = Compte::create([
            'numeroCompte' => 'TEST_CQ_FERME',
            'client_id' => $client->id,
            'type' => 'cheque',
            'statut' => 'ferme',
            'motifBlocage' => 'Compte fermé',
            'devise' => 'FCFA',
        ]);
        $this->command->info("✅ Compte chèque FERMÉ créé: {$chequeFerme->numeroCompte} (visible car type=cheque)");
        
        // 7. Compte SOFT DELETED ❌ (ne doit PAS être visible)
        $compteSoftDeleted = Compte::create([
            'numeroCompte' => 'TEST_SOFT_DELETE',
            'client_id' => $client->id,
            'type' => 'epargne',
            'statut' => 'actif',
            'devise' => 'FCFA',
        ]);
        $compteSoftDeleted->delete(); // Soft delete
        $this->command->info("❌ Compte SOFT DELETED créé: {$compteSoftDeleted->numeroCompte}");
        
        // 8. Compte ARCHIVÉ ❌ (ne doit PAS être visible - sera dans Neon)
        $compteArchive = Compte::create([
            'numeroCompte' => 'TEST_ARCHIVED',
            'client_id' => $client->id,
            'type' => 'epargne',
            'statut' => 'actif',
            'devise' => 'FCFA',
            'archived_at' => now(),
            'cloud_storage_path' => 'neon://test',
        ]);
        $this->command->info("❌ Compte ARCHIVÉ créé: {$compteArchive->numeroCompte}");
        
        $this->command->info('');
        $this->command->info('✅ Seeding terminé !');
        $this->command->info('');
        
        // Résumé des attentes
        $this->command->table(
            ['Type', 'Statut', 'État', 'Visible?'],
            [
                ['ÉPARGNE', 'actif', 'Normal', '✅ OUI'],
                ['ÉPARGNE', 'bloqué', 'Normal', '❌ NON'],
                ['ÉPARGNE', 'fermé', 'Normal', '❌ NON'],
                ['CHÈQUE', 'actif', 'Normal', '✅ OUI'],
                ['CHÈQUE', 'bloqué', 'Normal', '✅ OUI'],
                ['CHÈQUE', 'fermé', 'Normal', '✅ OUI'],
                ['ÉPARGNE', 'actif', 'Soft Deleted', '❌ NON'],
                ['ÉPARGNE', 'actif', 'Archivé', '❌ NON (dans Neon)'],
            ]
        );
        
        $this->command->info('');
        $this->command->info('🧪 Pour tester:');
        $this->command->info('GET /api/v1/comptes');
        $this->command->info('');
        $this->command->info('Résultat attendu: 4 comptes visibles');
        $this->command->info('  - TEST_EP_ACTIF (épargne actif)');
        $this->command->info('  - TEST_CQ_ACTIF (chèque actif)');
        $this->command->info('  - TEST_CQ_BLOQUE (chèque bloqué)');
        $this->command->info('  - TEST_CQ_FERME (chèque fermé)');
    }
}
