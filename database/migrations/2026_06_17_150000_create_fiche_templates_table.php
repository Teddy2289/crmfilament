<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiche_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();              // bleue, jaune, verte
            $table->string('nom');                              // Libellé affiché
            $table->text('description')->nullable();
            $table->string('template_path');                    // Chemin vers le .docx dans storage
            $table->json('placeholders')->nullable();           // {"${RAISON_SOCIALE}": "raison_sociale|nom", ...}
            $table->json('statut_phoning_codes')->nullable();   // ["RDV","CSE-NI","BLOC2"] — codes déclencheurs
            $table->boolean('auto_generation')->default(false); // Générer auto quand statut atteint
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiche_templates');
    }
};
