<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Données de PERMANENCES uniquement.
     * Champs spécifiques : prc_2026, rdv_physique, rdv_telephonique
     * issus des onglets PERMANENCES et DRAFT du fichier Excel source.
     */
    public function up(): void
    {
        Schema::create('activite_permanences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('partenaire_id')
                ->constrained('partenaires')
                ->cascadeOnDelete();

            $table->foreignId('consultant_id')
                ->nullable()
                ->constrained('consultants')
                ->nullOnDelete();

            $table->date('derniere_permanence')->nullable();
            $table->integer('nbre_2025')->default(0);
            $table->integer('nbre_2026')->default(0);

            // Onglet PERMANENCES
            $table->integer('prc_2026')->default(0)->comment('Nombre de PRC 2026');

            // Onglet DRAFT
            $table->integer('rdv_physique')->nullable()->comment('Nb RDV physiques');
            $table->integer('rdv_telephonique')->nullable()->comment('Nb RDV téléphoniques');

            $table->timestamps();

            $table->index('partenaire_id');
            $table->index('consultant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activite_permanences');
    }
};
