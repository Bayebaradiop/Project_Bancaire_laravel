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
            $table->timestamp('archived_at')->nullable()->after('deleted_at')
                ->comment('Date d\'archivage du compte pour stockage cloud');
            $table->string('cloud_storage_path')->nullable()->after('archived_at')
                ->comment('Chemin du fichier archivé dans le cloud (S3, Google Cloud, etc.)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropColumn(['archived_at', 'cloud_storage_path']);
        });
    }
};
