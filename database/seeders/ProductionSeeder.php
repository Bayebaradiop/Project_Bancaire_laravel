<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds for PRODUCTION.
     * N'utilise PAS Faker, seulement les données fixes.
     */
    public function run(): void
    {
        // Seeder uniquement les données essentielles (admin et client test)
        $this->call([
            AdminUserSeeder::class,
            // PAS de CompteSeeder car il utilise Faker
            // PAS de TransactionSeeder car il utilise Faker
        ]);
        
        $this->command->info('✅ Production seeding completed (admin and test client only)');
    }
}
