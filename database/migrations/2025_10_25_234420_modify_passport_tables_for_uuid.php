<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Supprimer les données existantes (elles seront régénérées)
        DB::statement('TRUNCATE TABLE oauth_access_tokens CASCADE');
        DB::statement('TRUNCATE TABLE oauth_auth_codes CASCADE');
        DB::statement('TRUNCATE TABLE oauth_clients CASCADE');
        DB::statement('TRUNCATE TABLE oauth_personal_access_clients CASCADE');
        DB::statement('TRUNCATE TABLE oauth_refresh_tokens CASCADE');

        // Modifier la colonne user_id pour utiliser uuid au lieu de bigint avec USING
        DB::statement('ALTER TABLE oauth_access_tokens ALTER COLUMN user_id TYPE uuid USING user_id::text::uuid');
        DB::statement('ALTER TABLE oauth_auth_codes ALTER COLUMN user_id TYPE uuid USING user_id::text::uuid');
        DB::statement('ALTER TABLE oauth_clients ALTER COLUMN user_id TYPE uuid USING user_id::text::uuid');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('TRUNCATE TABLE oauth_access_tokens CASCADE');
        DB::statement('TRUNCATE TABLE oauth_auth_codes CASCADE');
        DB::statement('TRUNCATE TABLE oauth_clients CASCADE');
        DB::statement('TRUNCATE TABLE oauth_personal_access_clients CASCADE');
        DB::statement('TRUNCATE TABLE oauth_refresh_tokens CASCADE');

        DB::statement('ALTER TABLE oauth_access_tokens ALTER COLUMN user_id TYPE bigint USING user_id::text::bigint');
        DB::statement('ALTER TABLE oauth_auth_codes ALTER COLUMN user_id TYPE bigint USING user_id::text::bigint');
        DB::statement('ALTER TABLE oauth_clients ALTER COLUMN user_id TYPE bigint USING user_id::text::bigint');
    }
};
