<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Ajouter le numéro de transaction unique
            $table->string('numeroTransaction', 50)->unique()->after('id');
            
            // Ajouter les colonnes pour les transferts
            $table->uuid('compte_source_id')->nullable()->after('compte_id');
            $table->uuid('compte_destinataire_id')->nullable()->after('compte_source_id');
            
            // Modifier le type pour inclure transfert et annulation
            DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check");
            $table->dropColumn('type');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('type', ['depot', 'retrait', 'transfert', 'annulation'])
                ->after('compte_destinataire_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            // Ajouter la devise
            $table->string('devise', 10)->default('FCFA')->after('montant');
            
            // Ajouter les frais (pour les transferts)
            $table->decimal('frais', 15, 2)->default(0)->after('devise');
            
            // Modifier les statuts
            DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_statut_check");
            $table->dropColumn('statut');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('statut', ['en_attente', 'validee', 'annulee', 'echouee'])
                ->default('en_attente')
                ->after('frais');
        });

        Schema::table('transactions', function (Blueprint $table) {
            // Ajouter la description
            $table->text('description')->nullable()->after('statut');
            
            // Ajouter la référence à la transaction parent (pour les annulations)
            $table->uuid('transaction_parent_id')->nullable()->after('description');
            
            // Foreign keys pour les nouveaux champs
            $table->foreign('compte_source_id')
                ->references('id')
                ->on('comptes')
                ->onDelete('cascade');
            
            $table->foreign('compte_destinataire_id')
                ->references('id')
                ->on('comptes')
                ->onDelete('cascade');
            
            $table->foreign('transaction_parent_id')
                ->references('id')
                ->on('transactions')
                ->onDelete('set null');
            
            // Indexes pour optimiser les requêtes
            $table->index('compte_source_id');
            $table->index('compte_destinataire_id');
            $table->index('transaction_parent_id');
            $table->index('numeroTransaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Supprimer les foreign keys
            $table->dropForeign(['compte_source_id']);
            $table->dropForeign(['compte_destinataire_id']);
            $table->dropForeign(['transaction_parent_id']);
            
            // Supprimer les indexes
            $table->dropIndex(['compte_source_id']);
            $table->dropIndex(['compte_destinataire_id']);
            $table->dropIndex(['transaction_parent_id']);
            $table->dropIndex(['numeroTransaction']);
            
            // Supprimer les colonnes
            $table->dropColumn([
                'numeroTransaction',
                'compte_source_id',
                'compte_destinataire_id',
                'devise',
                'frais',
                'description',
                'transaction_parent_id'
            ]);
        });

        // Restaurer l'ancien enum pour type
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('type', ['depot', 'retrait'])->after('compte_id');
        });

        // Restaurer l'ancien enum pour statut
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('statut');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('statut', ['pending', 'complete', 'failed'])
                ->default('complete')
                ->after('montant');
        });
    }
};
