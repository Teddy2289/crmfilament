<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix the ENUM values for resultat column to use proper UTF-8 encoding
        DB::statement("ALTER TABLE appels MODIFY COLUMN resultat ENUM('Réalisé','Annulé','Décalé','Non abouti','Rappel') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'Réalisé'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to the original (potentially broken) values
        DB::statement("ALTER TABLE appels MODIFY COLUMN resultat ENUM('R??lis??','Annul??','D??cal??','Non abouti','Rappel') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'R??lis??'");
    }
};
