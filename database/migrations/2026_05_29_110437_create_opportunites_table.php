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
        Schema::create('opportunites', function (Blueprint $table) {
            $table->id();
            $table->string('nom_entite');
            $table->string('type_pressenti')->nullable();
            $table->string('departement', 3)->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->text('adresse')->nullable();
            $table->string('siret', 14)->nullable();
            $table->string('secteur_activite')->nullable();
            $table->integer('nb_salaries')->nullable();
            $table->decimal('chiffre_affaires', 15, 2)->nullable();
            $table->string('source_detection')->nullable();
            $table->text('details_source')->nullable();
            $table->string('potentiel')->nullable();
            $table->string('statut')->default('nouveau');
            $table->string('interlocuteur_nom')->nullable();
            $table->string('interlocuteur_fonction')->nullable();
            $table->string('interlocuteur_telephone')->nullable();
            $table->string('interlocuteur_email')->nullable();
            $table->foreignId('assigne_a')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date_detection')->nullable();
            $table->date('date_premier_contact')->nullable();
            $table->text('notes')->nullable();
            $table->text('raison_perte')->nullable();
            $table->foreignId('converti_en_prospect_id')->nullable()->constrained('prospects')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunites');
    }
};
