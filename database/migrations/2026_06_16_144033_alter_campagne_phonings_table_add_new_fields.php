<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campagne_phonings', function (Blueprint $table) {
            // Supprimer les colonnes obsolètes (pas de FK sur consultant_id en base)
            $table->dropColumn(['departement', 'annee', 'consultant_id']);

            // Nouvelles colonnes
            $table->text('description')->nullable()->after('nom');
            $table->string('statut', 20)->default('brouillon')->after('description');
            $table->string('type_entite', 20)->default('prospects')->after('statut');
            $table->json('criteres')->nullable()->after('type_entite');
            $table->date('date_debut')->nullable()->after('criteres');
            $table->date('date_fin')->nullable()->after('date_debut');
            $table->foreignId('user_id')->nullable()->after('date_fin')
                ->constrained('users')->nullOnDelete();

            // Index
            $table->index('user_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::table('campagne_phonings', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['campagne_phonings_user_id_index']);
            $table->dropIndex(['campagne_phonings_statut_index']);
            $table->dropColumn(['description', 'statut', 'type_entite', 'criteres', 'date_debut', 'date_fin', 'user_id']);

            $table->integer('departement')->nullable();
            $table->string('annee', 4)->nullable();
            $table->unsignedBigInteger('consultant_id')->nullable();
            $table->index('consultant_id');
        });
    }
};
