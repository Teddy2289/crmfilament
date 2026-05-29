<?php

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
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
        Schema::create('partenaires', function (Blueprint $table) {
            $table->id();

            // Identification
            $table->string('nom');
            $table->string('siret', 14)->unique()->nullable();
            $table->string('type')->default(OrganizationType::CSE->value);
            $table->string('nomenclature_interne')->nullable(); // définie par Nina
            $table->foreignId('entreprise_mere_id')->nullable()->constrained('partenaires')->nullOnDelete();

            // Coordonnées
            $table->text('adresse')->nullable();
            $table->string('code_postal', 5)->nullable();
            $table->string('ville')->nullable();
            $table->string('departement', 3)->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('secteur_activite')->nullable();
            $table->integer('nb_salaries')->nullable();
            $table->decimal('chiffre_affaires', 15, 2)->nullable();

            // Pipeline — 6 statuts CDC (corrigé : ajout rdv_en_cours)
            $table->string('statut')->default(OrganizationStatus::AProspecter->value);
            $table->timestamp('date_modification_statut')->nullable(); // workflow 90 jours
            $table->date('date_convention')->nullable();

            // Commercial assigné
            $table->foreignId('commercial_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('origine_contact')->nullable();
            $table->text('parrain_marraine')->nullable();
            $table->integer('nombre_ventes_liees')->default(0);

            // Syndicat
            $table->string('syndicat_appartenance')->nullable();
            $table->string('syndicat_nom_organisation')->nullable();
            $table->string('syndicat_responsable_nom')->nullable();
            $table->string('syndicat_responsable_prenom')->nullable();
            $table->string('syndicat_responsable_fonction')->nullable();
            $table->string('syndicat_tel_direct')->nullable();
            $table->string('syndicat_tel_perso')->nullable();
            $table->string('syndicat_email_pro')->nullable();
            $table->string('syndicat_email_perso')->nullable();
            $table->text('syndicat_perimetre')->nullable();
            $table->text('syndicat_notes')->nullable();

            // Dirigeant
            $table->string('dirigeant_nom')->nullable();
            $table->string('dirigeant_prenom')->nullable();
            $table->string('dirigeant_fonction')->nullable();
            $table->string('dirigeant_telephone')->nullable();
            $table->string('dirigeant_email')->nullable();

            // CSE
            $table->string('cse_secretaire_nom')->nullable();
            $table->string('cse_secretaire_prenom')->nullable();
            $table->string('cse_secretaire_tel_direct')->nullable();
            $table->string('cse_secretaire_tel_perso')->nullable();
            $table->string('cse_secretaire_email_pro')->nullable();
            $table->string('cse_secretaire_email_perso')->nullable();
            $table->string('cse_tresorier_nom')->nullable();
            $table->string('cse_tresorier_prenom')->nullable();
            $table->string('cse_tresorier_tel_direct')->nullable();
            $table->string('cse_tresorier_tel_perso')->nullable();
            $table->string('cse_tresorier_email_pro')->nullable();
            $table->string('cse_tresorier_email_perso')->nullable();
            $table->integer('cse_nb_elus')->nullable();
            $table->date('cse_date_fin_mandat')->nullable();
            $table->boolean('cse_existence_juridique')->default(false);
            $table->text('cse_notes')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('statut');
            $table->index('commercial_id');
            $table->index('departement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partenaires');
    }
};
