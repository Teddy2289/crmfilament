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
        Schema::table('clients', function (Blueprint $table) {
            // Ajouter un champ JSON pour stocker plusieurs ref_client
            $table->json('ref_clients')->nullable()->after('ref_client');

            // Ajouter des index sur email et téléphone pour la fusion des doublons
            $table->index('email');
            $table->index('telephone');
        });

        // Migrer les ref_client existants vers ref_clients
        DB::statement("UPDATE clients SET ref_clients = JSON_ARRAY(ref_client) WHERE ref_client IS NOT NULL AND ref_client != ''");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['telephone']);
            $table->dropColumn('ref_clients');
        });
    }
};
