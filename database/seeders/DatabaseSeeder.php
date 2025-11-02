<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * 
     * RenderSeeder : Crée les comptes ACTIFS dans PostgreSQL (Render)
     * NeonSeeder   : Crée 2 comptes ARCHIVÉS dans Neon (1 bloqué + 1 fermé)
     */
    public function run(): void
    {
        $this->command->info('========================================');
        $this->command->info('🌱 SEEDING COMPLET - Render + Neon');
        $this->command->info('========================================');
        $this->command->info('');
        
        // 1. Seeder Render (PostgreSQL) - Comptes ACTIFS
        $this->call([
            RenderSeeder::class,
        ]);
        
        $this->command->info('');
        
        // 2. Seeder Transactions - Transactions de test
        $this->call([
            TransactionSeeder::class,
        ]);
        
        $this->command->info('');
        
        // 3. Seeder Neon (Archive) - 2 comptes ARCHIVÉS
        $this->call([
            NeonSeeder::class,
        ]);
        
        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('✅ SEEDING TERMINÉ !');
        $this->command->info('📊 Render : Comptes ACTIFS (chèque + épargne)');
        $this->command->info('💰 Transactions : Dépôts, Retraits, Transferts');
        $this->command->info('☁️  Neon   : 2 comptes ARCHIVÉS (bloqué + fermé)');
        $this->command->info('========================================');
    }
}
