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
         Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('source_sheet')->nullable();
            $table->string('ref_client')->nullable()->index();
            $table->string('civilite')->nullable();
            $table->string('nom_tiers')->nullable();
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->string('adresse')->nullable();
            $table->string('code_postal')->nullable();
            $table->string('ville')->nullable();
            $table->string('region')->nullable();
            $table->string('departement')->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('entreprise')->nullable();
            $table->string('etat')->nullable();
            $table->decimal('montant_cpf', 10, 2)->nullable();
            $table->boolean('ne_plus_contacter')->default(false);
            $table->json('extra_data')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('Propositions', function (Blueprint $table) {
            $table->id();
            $table->string('source_sheet')->nullable();
            $table->string('ref_client')->nullable()->index();
            $table->string('tiers')->nullable();
            $table->string('etat')->nullable();
            $table->date('date_lancement')->nullable();
            $table->date('date_vente')->nullable();
            $table->integer('nb_heures_formation')->nullable();
            $table->integer('heures_realisees')->nullable();
            $table->integer('heures_restantes')->nullable();
            $table->date('date_debut_formation')->nullable();
            $table->date('date_fin_formation')->nullable();
            $table->string('consultant_formateur')->nullable();
            $table->date('date_certification')->nullable();
            $table->json('extra_data')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('Import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('sheet_name');
            $table->enum('model_type', ['client', 'proposition']);
            $table->integer('rows_imported')->default(0);
            $table->integer('rows_skipped')->default(0);
            $table->integer('rows_failed')->default(0);
            $table->json('errors')->nullable();
            $table->json('column_mapping')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
         Schema::dropIfExists('Import_logs');
        Schema::dropIfExists('Propositions');
    }
};
