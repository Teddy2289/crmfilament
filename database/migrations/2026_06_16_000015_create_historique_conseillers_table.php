<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historique_conseillers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('partenaire_id')
                  ->constrained('partenaires')
                  ->cascadeOnDelete();

            $table->foreignId('ancien_conseiller_id')
                  ->nullable()
                  ->constrained('consultants')
                  ->nullOnDelete();

            $table->foreignId('nouveau_conseiller_id')
                  ->nullable()
                  ->constrained('consultants')
                  ->nullOnDelete();

            $table->date('date_changement');
            $table->string('motif')->nullable();

            $table->timestamps();

            // Index utiles pour les requêtes de traçabilité
            $table->index('partenaire_id');
            $table->index('ancien_conseiller_id');
            $table->index('date_changement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_conseillers');
    }
};
