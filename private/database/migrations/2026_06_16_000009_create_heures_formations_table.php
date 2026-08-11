<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heures_formations', function (Blueprint $table) {
            // ✅ Clé primaire auto-incrémentée
            $table->id();

            // ✅ Clé étrangère vers dossier_formations
            $table->foreignId('dossier_id')
                ->constrained('dossier_formations')
                ->cascadeOnDelete();

            $table->decimal('heures_obligatoires', 8, 2)->default(0);
            $table->decimal('heures_complementaires', 8, 2)->default(0);
            $table->decimal('heures_elearning', 8, 2)->default(0);
            $table->decimal('total_heures', 8, 2)->default(0);
            $table->decimal('heures_realisees', 8, 2)->default(0);
            $table->decimal('heures_restantes', 8, 2)->default(0);
            $table->timestamps();

            // ✅ Index pour les recherches par dossier
            $table->index('dossier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heures_formations');
    }
};
