<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiche_p2s', function (Blueprint $table) {
            // Section A : Identification complémentaire
            $table->string('email_client', 150)->nullable()->after('telephone_client');
            $table->string('code_postal_ville', 100)->nullable()->after('adresse_intervention');
            $table->string('canal_contact_preferentiel', 50)->nullable()->after('code_postal_ville');

            // Section C : Bascule P5
            $table->boolean('bascule_p5_requise')->nullable()->default(null)->after('justificatif_priorite');

            // Section D : Compléments logement
            $table->string('garantie_contrat', 20)->nullable()->after('statut_occupant');  // Oui / Non / Inconnu
            $table->string('code_acces_interphone', 50)->nullable()->after('garantie_contrat');
            $table->text('contact_alternatif')->nullable()->after('code_acces_interphone');
            $table->string('etage_ascenseur', 50)->nullable()->after('contact_alternatif');

            // Section F : Champs automatiques de qualification
            $table->foreignId('agent_qualificateur_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->after('reponses_metier');
            $table->dateTime('date_qualification_complete')->nullable()->after('agent_qualificateur_id');
            $table->unsignedInteger('duree_appel_p2')->nullable()->comment('Durée en secondes')->after('date_qualification_complete');
            $table->string('source_appel_ligne', 50)->nullable()->after('duree_appel_p2');
        });
    }

    public function down(): void
    {
        Schema::table('fiche_p2s', function (Blueprint $table) {
            $table->dropForeign(['agent_qualificateur_id']);
            $table->dropColumn([
                'email_client',
                'code_postal_ville',
                'canal_contact_preferentiel',
                'bascule_p5_requise',
                'garantie_contrat',
                'code_acces_interphone',
                'contact_alternatif',
                'etage_ascenseur',
                'agent_qualificateur_id',
                'date_qualification_complete',
                'duree_appel_p2',
                'source_appel_ligne',
            ]);
        });
    }
};
