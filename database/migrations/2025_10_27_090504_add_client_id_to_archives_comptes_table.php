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
        Schema::connection('neon')->table('archives_comptes', function (Blueprint $table) {
            $table->uuid('client_id')->nullable()->after('numeroCompte');
            $table->string('devise', 10)->default('FCFA')->after('solde');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('neon')->table('archives_comptes', function (Blueprint $table) {
            $table->dropColumn(['client_id', 'devise']);
        });
    }
};
