<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comptes', function (Blueprint $table) {
            // Clé primaire UUID
            $table->uuid('id')->primary();
            
            // Numéro de compte unique
            $table->string('numeroCompte', 20)->unique();
            
            // Relation avec client
            $table->uuid('client_id');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            
            // Type de compte
            $table->enum('type', ['cheque', 'epargne']);
            
            // Devise
            $table->string('devise', 10)->default('FCFA');
            
            // Statut et motif de blocage
            $table->enum('statut', ['actif', 'bloque', 'ferme'])->default('actif');
            $table->text('motifBlocage')->nullable();
            
            // Version pour contrôle de concurrence optimiste
            $table->integer('version')->default(1);
            
            // Timestamps personnalisés
            $table->timestamp('dateCreation')->useCurrent();
            $table->timestamp('derniereModification')->useCurrent()->useCurrentOnUpdate();
            
            // Soft delete
            $table->softDeletes('deleted_at');
            
            // Index pour optimisation
            $table->index('numeroCompte');
            $table->index('client_id');
            $table->index('statut');
            $table->index('type');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};
