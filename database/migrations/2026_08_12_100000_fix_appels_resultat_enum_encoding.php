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
        // First, convert the column to VARCHAR to preserve existing data
        DB::statement("ALTER TABLE appels MODIFY COLUMN resultat VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL");

        // Fix corrupted encoding - set any invalid values to 'Réalisé'
        DB::statement("UPDATE appels SET resultat = 'Réalisé' WHERE resultat NOT IN ('Réalisé', 'Annulé', 'Décalé', 'Non abouti', 'Rappel')");

        // Set any NULL or empty values to default
        DB::statement("UPDATE appels SET resultat = 'Réalisé' WHERE resultat IS NULL OR resultat = ''");

        // Convert back to ENUM with proper UTF-8 encoding
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
