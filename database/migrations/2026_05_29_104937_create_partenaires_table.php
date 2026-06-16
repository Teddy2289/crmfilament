<?php

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partenaires', function (Blueprint $table) {
            $table->id();

            // ── Identité ──────────────────────────────────────────────
            $table->string('nom');
            $table->string('entreprise')->nullable();
            $table->string('nom_retenu')->nullable();
            $table->string('siret', 14)->unique()->nullable();
            $table->string('type')->default(OrganizationType::CSE->value);
            $table->string('nomenclature_interne')->nullable();
            $table->foreignId('entreprise_mere_id')->nullable()->constrained('partenaires')->nullOnDelete();
            $table->foreignId('entite_id')->nullable()->constrained('entite_commerciales')->nullOnDelete();

            // ── Localisation ──────────────────────────────────────────
            $table->text('adresse')->nullable();
            $table->string('code_postal', 5)->nullable();
            $table->string('ville')->nullable();
            $table->string('departement')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('secteur_activite')->nullable();
            $table->integer('nb_salaries')->nullable();
            $table->decimal('chiffre_affaires', 15, 2)->nullable();

            // ── Pipeline ──────────────────────────────────────────────
            $table->string('statut')->default(OrganizationStatus::AProspecter->value);
            $table->timestamp('date_modification_statut')->nullable();
            $table->date('date_signature')->nullable();       // ✅ MEA
            $table->integer('annee_signature')->nullable();   // ✅ MEA
            $table->date('date_convention')->nullable();
            $table->date('date_evaluation')->nullable();

            // ── Assignation ───────────────────────────────────────────
            // conseiller_id → Consultant (remplace l'ancien commercial_id → User)
            $table->foreignId('conseiller_id')->nullable()->constrained('consultants')->nullOnDelete();
            $table->foreignId('parrain_partenaire_id')->nullable()->constrained('partenaires')->nullOnDelete();

            // ── Origine / Parrainage ──────────────────────────────────
            $table->string('origine_contact')->nullable();
            $table->string('parrain_marraine')->nullable();
            $table->string('parrain_marraine_texte')->nullable(); // ✅ MEA
            $table->boolean('parrainage_entreprise')->nullable(); // ✅ MEA OUI/NON
            $table->integer('nombre_ventes_liees')->default(0);

            // ── Fonctionnement partenariat ────────────────────────────
            $table->string('possibilite_permanence')->nullable();
            $table->text('replicable')->nullable();           // ✅ TEXT (pas VARCHAR)
            $table->string('syndicat_majoritaire')->nullable(); // ✅ MEA

            // ── CSE ───────────────────────────────────────────────────
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

            // ── Syndicat ──────────────────────────────────────────────
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

            // ── Dirigeant ─────────────────────────────────────────────
            $table->string('dirigeant_nom')->nullable();
            $table->string('dirigeant_prenom')->nullable();
            $table->string('dirigeant_fonction')->nullable();
            $table->string('dirigeant_telephone')->nullable();
            $table->string('dirigeant_email')->nullable();

            // ── Misc ──────────────────────────────────────────────────
            $table->text('commentaires')->nullable();         // ✅ MEA
            $table->text('commentaire_import')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('statut');
            $table->index('conseiller_id');
            $table->index('entite_id');
            $table->index('departement');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partenaires');
    }
};
