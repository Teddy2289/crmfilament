<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarifications', function (Blueprint $table) {

            // ── Tarif net salarié ─────────────────────────────────────────
            if (! Schema::hasColumn('tarifications', 'tarifs')) {
                $table->decimal('tarifs', 8, 2)
                    ->nullable()
                    ->after('part_aopia')
                    ->comment('Tarifs = prix net salarié (colonne Excel)');
            }

            // ── Tarif affiché sur la communication ───────────────────────
            if (! Schema::hasColumn('tarifications', 'tarifs_affichage_comm')) {
                $table->decimal('tarifs_affichage_comm', 8, 2)
                    ->nullable()
                    ->after('tarifs')
                    ->comment('Tarifs à afficher sur la comm partenaire');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tarifications', function (Blueprint $table) {
            $table->dropColumn(['tarifs', 'tarifs_affichage_comm']);
        });
    }
};
