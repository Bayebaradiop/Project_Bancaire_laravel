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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('compte_id');
            $table->enum('type', ['depot', 'retrait']);
            $table->decimal('montant', 15, 2);
            $table->enum('statut', ['pending', 'complete', 'failed'])->default('complete');
            $table->timestamp('date')->useCurrent();
            $table->timestamps();

            // Foreign keys
            $table->foreign('compte_id')->references('id')->on('comptes')->onDelete('cascade');

            // Indexes
            $table->index('compte_id');
            $table->index('type');
            $table->index('statut');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
