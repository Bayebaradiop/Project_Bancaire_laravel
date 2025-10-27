<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Cette migration sera exécutée sur la base de données Neon (cloud)
     * pour créer la table des comptes archivés
     * 
     * Commande: php artisan migrate --database=neon
     */
    public function up(): void
    {
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
            
            CREATE INDEX IF NOT EXISTS idx_client_id ON comptes_archives(client_id);
            CREATE INDEX IF NOT EXISTS idx_type ON comptes_archives(type);
            CREATE INDEX IF NOT EXISTS idx_archived_at ON comptes_archives(archived_at);
            CREATE UNIQUE INDEX IF NOT EXISTS idx_numeroCompte ON comptes_archives(numeroCompte);
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('neon')->dropIfExists('comptes_archives');
    }
};
