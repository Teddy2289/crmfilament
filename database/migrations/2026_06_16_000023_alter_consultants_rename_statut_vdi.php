<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Renomme statut_vdi → statut et élargit les valeurs possibles.
     *
     * Ancien : statut_vdi (texte libre, implicitement "VDI")
     * Nouveau : statut enum (Mandataire / VDI / Salarié / PRC / PIP)
     *           conformément au MEA CONSULTANT.
     */
    public function up(): void
    {
        Schema::table('consultants', function (Blueprint $table) {

            // Si l'ancienne colonne existe, on la renomme proprement
            if (Schema::hasColumn('consultants', 'statut_vdi')) {
                $table->renameColumn('statut_vdi', 'statut');
            }

            // Si ni statut_vdi ni statut n'existe (table fraîche)
            if (!Schema::hasColumn('consultants', 'statut') && !Schema::hasColumn('consultants', 'statut_vdi')) {
                $table->string('statut')
                      ->nullable()
                      ->after('prenom')
                      ->comment('Mandataire / VDI / Salarié / PRC / PIP');
            }
        });

        // Modifier le type en string explicite avec commentaire
        // (enum natif évité pour faciliter les migrations futures)
        Schema::table('consultants', function (Blueprint $table) {
            $table->string('statut')
                  ->nullable()
                  ->comment('Mandataire / VDI / Salarié / PRC / PIP')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('consultants', function (Blueprint $table) {
            if (Schema::hasColumn('consultants', 'statut')) {
                $table->renameColumn('statut', 'statut_vdi');
            }
        });
    }
};
