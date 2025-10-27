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
        Schema::table('comptes', function (Blueprint $table) {
            $table->timestamp('dateBlocage')->nullable()->after('motifBlocage');
            $table->timestamp('dateDeblocagePrevue')->nullable()->after('dateBlocage');
            $table->timestamp('dateDeblocage')->nullable()->after('dateDeblocagePrevue');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropColumn(['dateBlocage', 'dateDeblocagePrevue', 'dateDeblocage']);
        });
    }
};
