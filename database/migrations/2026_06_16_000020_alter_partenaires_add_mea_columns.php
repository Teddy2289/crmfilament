<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes MEA déjà présentes dans create_partenaires_table depuis la refonte.
 * Cette migration est conservée vide pour ne pas casser le batch migrations existant.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Toutes les colonnes MEA sont désormais dans create_partenaires_table.
        // Migration conservée pour compatibilité avec l'historique migrate.
    }

    public function down(): void
    {
        //
    }
};
