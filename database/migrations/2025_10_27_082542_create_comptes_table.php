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
            $table->uuid('id')->primary();
            $table->string('numeroCompte')->unique();
            $table->enum('type', ['epargne', 'courant']);
            $table->uuid('client_id');
            $table->enum('statut', ['actif', 'ferme'])->default('actif');
            $table->string('devise', 3)->default('FCFA');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('users');
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
