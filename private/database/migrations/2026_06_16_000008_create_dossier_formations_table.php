<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dossier_formations', function (Blueprint $table) {
            $table->id();
            $table->string('ref_client')->nullable()->index();
            $table->string('intitule_programme')->nullable();
            $table->foreignId('entite_id')->nullable()->constrained('entite_commerciales')->nullOnDelete();
            $table->foreignId('personne_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->decimal('montant_ht', 10, 2)->nullable();
            $table->decimal('montant_cpf', 10, 2)->nullable();
            $table->date('date_vente')->nullable();
            $table->string('statut_formation')->nullable();
            $table->string('no_dossier_edof')->nullable();
            $table->string('etat')->nullable();
            $table->foreignId('consultant_accueil_id')->nullable()->constrained('consultants')->nullOnDelete();
            $table->foreignId('consultant_formateur_id')->nullable()->constrained('consultants')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('entite_id');
            $table->index('personne_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossier_formations');
    }
};
