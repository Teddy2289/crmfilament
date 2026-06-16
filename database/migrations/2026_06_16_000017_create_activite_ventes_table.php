<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remplace la colonne `type` de l'ancienne table activite_partenaires
     * pour les données de VENTES uniquement.
     * Séparée de activite_permanences pour avoir des champs distincts par année.
     */
    public function up(): void
    {
        Schema::create('activite_ventes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('partenaire_id')
                  ->constrained('partenaires')
                  ->cascadeOnDelete();

            $table->foreignId('consultant_id')
                  ->nullable()
                  ->constrained('consultants')
                  ->nullOnDelete();

            $table->integer('nombre_ventes_total')->default(0);
            $table->date('derniere_vente')->nullable();
            $table->integer('ventes_2025')->default(0);
            $table->integer('ventes_2026')->default(0);

            $table->timestamps();

            $table->index('partenaire_id');
            $table->index('consultant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activite_ventes');
    }
};
