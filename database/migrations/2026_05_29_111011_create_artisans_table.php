<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\CanalAlerte;
use App\Enums\StatutCompteArtisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('artisans', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('raison_sociale')->nullable();
            $table->string('siret', 14)->unique();
            $table->string('corps_de_metier');
            $table->text('zone_intervention');
            $table->string('telephone_principal', 20);
            $table->string('telephone_secondaire', 20)->nullable();
            $table->string('email');
            $table->string('canal_alerte')->default(CanalAlerte::LesDeux->value);
            $table->string('statut_compte')->default(StatutCompteArtisan::EnAttenteActivation->value);
            $table->date('date_souscription');
            $table->date('date_activation')->nullable();
            $table->boolean('agenda_disponibilites')->default(false);
            $table->decimal('note_moyenne', 3, 2)->nullable();
            $table->integer('nb_interventions')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('statut_compte');
            $table->index('corps_de_metier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artisans');
    }
};
