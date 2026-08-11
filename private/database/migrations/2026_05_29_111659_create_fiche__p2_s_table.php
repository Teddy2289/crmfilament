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
        Schema::create('fiche_p2s', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->unique()->constrained('tickets')->cascadeOnDelete();

            // Section A : Identification métier
            $table->string('corps_de_metier');
            $table->string('nature_probleme', 255);
            $table->text('description_detaillee');

            // Section B : Localisation
            $table->text('localisation_precise');
            $table->string('anciennete_probleme');

            // Section C : Priorité
            $table->string('niveau_priorite');
            $table->text('justificatif_priorite');

            // Section D : Présence et logement
            $table->boolean('presence_client')->default(true);
            $table->string('type_logement');
            $table->string('statut_occupant');

            // Section E : Coordonnées confirmées
            $table->string('nom_client', 100);
            $table->string('telephone_client', 20);
            $table->text('adresse_intervention');

            // Section F : Questions spécifiques métier (JSON flexible)
            $table->json('reponses_metier')->nullable();

            $table->boolean('fiche_complete')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiche_p2s');
    }
};
