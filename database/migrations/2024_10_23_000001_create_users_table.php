<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Informations personnelles
            $table->string('nomComplet', 150);
            $table->string('nci', 20)->unique();
            $table->string('email', 100)->unique();
            $table->string('telephone', 20)->unique();
            $table->string('adresse', 255)->nullable();

            // Authentification
            $table->string('password', 255);
            $table->string('code', 10)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();

            // Statut
            $table->enum('statut', ['actif', 'inactif'])->default('actif');

            // Timestamps
            $table->timestamps();

            // Index
            $table->index('nci');
            $table->index('email');
            $table->index('telephone');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
