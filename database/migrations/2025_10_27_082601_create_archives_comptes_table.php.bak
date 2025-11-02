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
        Schema::connection('neon')->create('archives_comptes', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Clé primaire définie directement
            $table->string('numerocompte'); // minuscule pour cohérence avec INSERT
            $table->uuid('client_id'); // ID du client propriétaire
            $table->enum('type', ['epargne', 'courant']);
            $table->decimal('solde', 15, 2);
            $table->string('devise', 10)->default('FCFA'); // Devise du compte
            $table->enum('statut', ['ferme']);
            $table->timestamp('archived_at'); // Date d'archivage
            $table->text('archive_reason')->nullable(); // Raison de l'archivage
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archives_comptes');
    }
};
