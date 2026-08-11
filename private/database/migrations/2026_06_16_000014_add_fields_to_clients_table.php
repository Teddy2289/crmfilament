<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Schéma PERSONNE : prénom séparé (nom_tiers = nom complet existant)
            $table->string('prenom')->nullable()->after('civilite');
            // Type de tiers (client CPF, adhérent CSE, etc.)
            $table->string('type_tiers')->nullable()->after('entreprise');
            // Avis Google (schéma 2)
            $table->string('avis_google')->nullable()->after('type_tiers');
            // Lien partenaire ayant amené la personne
            $table->foreignId('partenaire_id')
                ->nullable()
                ->constrained('partenaires')
                ->nullOnDelete()
                ->after('avis_google');
            // Lien parrain ayant référé la personne
            $table->foreignId('parrain_id')
                ->nullable()
                ->constrained('parrains')
                ->nullOnDelete()
                ->after('partenaire_id');

            $table->index('partenaire_id');
            $table->index('parrain_id');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['partenaire_id']);
            $table->dropForeign(['parrain_id']);
            $table->dropColumn(['prenom', 'type_tiers', 'avis_google', 'partenaire_id', 'parrain_id']);
        });
    }
};
