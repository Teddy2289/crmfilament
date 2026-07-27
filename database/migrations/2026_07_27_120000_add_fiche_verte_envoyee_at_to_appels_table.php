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
        Schema::table('appels', function (Blueprint $table) {
            $table->timestamp('fiche_verte_envoyee_at')->nullable()
                ->after('fiche_jaune_j7_envoye_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appels', function (Blueprint $table) {
            $table->dropColumn('fiche_verte_envoyee_at');
        });
    }
};
