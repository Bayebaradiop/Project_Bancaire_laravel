<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un admin
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

        // Créer un client de test
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

        $this->command->info('✅ Utilisateurs créés avec succès:');
        $this->command->info('Admin: admin@banque.sn / Admin@2025');
        $this->command->info('Client: client@banque.sn / Client@2025');
    }
}
