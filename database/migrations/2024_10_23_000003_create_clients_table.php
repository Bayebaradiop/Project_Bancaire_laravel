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
        Schema::create('clients', function (Blueprint $table) {
            // Clé primaire UUID
            $table->uuid('id')->primary();
            
            // Relation avec user (optionnelle)
            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            // Informations du client
            $table->string('titulaire', 255);
            $table->string('nci', 13)->unique(); // Numéro Carte d'Identité Sénégalais
            $table->string('email', 255)->unique();
            $table->string('telephone', 20);
            $table->text('adresse');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('nci');
            $table->index('email');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
