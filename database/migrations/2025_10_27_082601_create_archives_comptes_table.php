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
            $table->uuid('id')->primary();
            $table->string('numeroCompte');
            $table->enum('type', ['epargne', 'courant']);
            $table->decimal('solde', 15, 2);
            $table->enum('statut', ['ferme']);
            $table->timestamp('dateFermeture');
            $table->timestamps();
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
