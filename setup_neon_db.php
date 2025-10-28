<?php

/**
 * Script pour initialiser uniquement la table comptes_archives sur NEON
 * Usage: php setup_neon_db.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "🚀 Connexion à la base de données NEON...\n";
    
    // Test de connexion
    DB::connection('neon')->getPdo();
    echo "✅ Connexion réussie!\n\n";
    
    echo "📋 Création de la table comptes_archives sur NEON...\n";
    
    // Créer la table comptes_archives sur NEON
    DB::connection('neon')->unprepared('
        CREATE TABLE IF NOT EXISTS comptes_archives (
            id VARCHAR(36) PRIMARY KEY,
            numeroCompte VARCHAR(20) NOT NULL,
            client_id VARCHAR(36) NOT NULL,
            type VARCHAR(50) NOT NULL,
            solde DECIMAL(15, 2) DEFAULT 0,
            devise VARCHAR(10) DEFAULT \'FCFA\',
            statut VARCHAR(50) DEFAULT \'actif\',
            motifBlocage TEXT,
            metadata JSONB,
            archived_at TIMESTAMP NOT NULL,
            archived_by VARCHAR(36),
            archive_reason VARCHAR(255),
            client_nom VARCHAR(255),
            client_email VARCHAR(255),
            client_telephone VARCHAR(255),
            created_at TIMESTAMP,
            updated_at TIMESTAMP
        );
    ');
    
    echo "✅ Table comptes_archives créée!\n\n";
    
    echo "🔧 Création des index...\n";
    
    // Créer les index
    DB::connection('neon')->unprepared('
        CREATE INDEX IF NOT EXISTS idx_client_id ON comptes_archives(client_id);
        CREATE INDEX IF NOT EXISTS idx_type ON comptes_archives(type);
        CREATE INDEX IF NOT EXISTS idx_archived_at ON comptes_archives(archived_at);
        CREATE UNIQUE INDEX IF NOT EXISTS idx_numeroCompte ON comptes_archives(numeroCompte);
    ');
    
    echo "✅ Index créés!\n\n";
    
    // Créer la table migrations pour NEON
    echo "📋 Création de la table migrations...\n";
    DB::connection('neon')->unprepared('
        CREATE TABLE IF NOT EXISTS migrations (
            id SERIAL PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INTEGER NOT NULL
        );
    ');
    
    echo "✅ Table migrations créée!\n\n";
    
    // Enregistrer la migration
    DB::connection('neon')->table('migrations')->insertOrIgnore([
        'migration' => '2025_10_26_190000_create_comptes_archives_table_neon',
        'batch' => 1
    ]);
    
    echo "✅ Migration enregistrée!\n\n";
    
    // Vérifier les tables
    echo "📊 Vérification des tables dans NEON:\n";
    $tables = DB::connection('neon')->select("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public'
        ORDER BY table_name
    ");
    
    foreach ($tables as $table) {
        echo "  - {$table->table_name}\n";
    }
    
    echo "\n✨ Configuration NEON terminée avec succès!\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
