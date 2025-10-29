<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NeonSeeder extends Seeder
{
    /**
     * Seeder pour la base NEON (Archive Cloud)
     * Crée UNIQUEMENT 2 comptes archivés (1 bloqué + 1 fermé)
     */
    public function run(): void
    {
        try {
            $this->command->info('☁️  Seeding NEON (Archive Cloud) - Base d\'archivage');
            
            // Vérifier la connexion Neon
            DB::connection('neon')->getPdo();
            $this->command->info('  ✅ Connexion à Neon établie');
            
            // Créer la table si elle n'existe pas
            $this->createTableIfNotExists();
            
            // Créer 2 comptes archivés
            $this->seedArchivedComptes();
            
            $totalNeon = DB::connection('neon')->table('comptes_archives')->count();
            $this->command->info('✅ NEON seeding completed!');
            $this->command->info('📊 Total comptes archivés dans Neon: ' . $totalNeon);
            
        } catch (\Exception $e) {
            $this->command->error('❌ Erreur Neon: ' . $e->getMessage());
            $this->command->warn('⚠️  Continuez sans Neon si la connexion échoue');
        }
    }

    private function createTableIfNotExists(): void
    {
        // Vérifier si la table existe
        $tableExists = DB::connection('neon')->select(
            "SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = 'comptes_archives'
            )"
        );

        if (!$tableExists[0]->exists) {
            // Créer la table comptes_archives dans Neon
            DB::connection('neon')->statement("
                CREATE TABLE comptes_archives (
                    id UUID PRIMARY KEY,
                    numeroCompte VARCHAR(255) UNIQUE NOT NULL,
                    client_id UUID NOT NULL,
                    type VARCHAR(50) NOT NULL,
                    solde DECIMAL(15, 2) DEFAULT 0,
                    devise VARCHAR(10) DEFAULT 'FCFA',
                    statut VARCHAR(50) NOT NULL,
                    motifBlocage TEXT,
                    dateCreation TIMESTAMP,
                    derniereModification TIMESTAMP,
                    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP
                )
            ");
            $this->command->info('  ✅ Table comptes_archives créée dans Neon');
        } else {
            $this->command->info('  ✅ Table comptes_archives existe déjà dans Neon');
        }
    }

    private function seedArchivedComptes(): void
    {
        // Générer des IDs pour cohérence
        $clientId = Str::uuid()->toString();
        
        // Compte 1 : BLOQUÉ
        $compte1Id = Str::uuid()->toString();
        $numeroCompte1 = 'CP' . str_pad(rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
        
        DB::connection('neon')->table('comptes_archives')->insert([
            'id' => $compte1Id,
            'numerocompte' => $numeroCompte1,  // Minuscules pour Neon
            'client_id' => $clientId,
            'type' => 'epargne',
            'solde' => 250000.00,
            'devise' => 'FCFA',
            'statut' => 'bloque',
            'motifblocage' => 'Compte bloqué pour vérification - Archivé dans Neon',  // Minuscules
            'archived_at' => now()->subDays(30),
            'created_at' => now()->subMonths(6),
            'updated_at' => now()->subDays(30),
        ]);
        
        $this->command->info("  ✅ Compte BLOQUÉ archivé: $numeroCompte1 (Solde: 250,000 FCFA)");

        // Compte 2 : FERMÉ
        $compte2Id = Str::uuid()->toString();
        $numeroCompte2 = 'CP' . str_pad(rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
        
        DB::connection('neon')->table('comptes_archives')->insert([
            'id' => $compte2Id,
            'numerocompte' => $numeroCompte2,  // Minuscules pour Neon
            'client_id' => $clientId,
            'type' => 'cheque',
            'solde' => 0.00,
            'devise' => 'FCFA',
            'statut' => 'ferme',
            'motifblocage' => 'Compte fermé à la demande du client - Archivé dans Neon',  // Minuscules
            'archived_at' => now()->subDays(60),
            'created_at' => now()->subYear(),
            'updated_at' => now()->subDays(60),
        ]);
        
        $this->command->info("  ✅ Compte FERMÉ archivé: $numeroCompte2 (Solde: 0 FCFA)");
        
        $this->command->info('  📊 2 comptes archivés créés dans Neon (1 bloqué + 1 fermé)');
    }
}
