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
        // Créer un admin (uniquement s'il n'existe pas déjà)
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
            $this->command->info('✅ Admin créé: admin@banque.sn / Admin@2025');
        } else {
            $this->command->info('⚠️  Admin existe déjà: admin@banque.sn');
        }

        // Créer un client de test (uniquement s'il n'existe pas déjà)
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
            $this->command->info('✅ Client créé: client@banque.sn / Client@2025');
        } else {
            $this->command->info('⚠️  Client existe déjà: client@banque.sn');
        }
    }
}
