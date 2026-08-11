<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planning_formations', function (Blueprint $table) {
            // ✅ Clé primaire auto-incrémentée
            $table->id();

            // ✅ Clé étrangère vers dossier_formations
            $table->foreignId('dossier_id')
                ->constrained('dossier_formations')
                ->cascadeOnDelete();

            $table->date('date_lancement')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin_theorique')->nullable();
            $table->date('date_certification')->nullable();
            $table->date('date_questionnaire_chaud')->nullable();
            $table->timestamps();

            $table->index('dossier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_formations');
    }
};
