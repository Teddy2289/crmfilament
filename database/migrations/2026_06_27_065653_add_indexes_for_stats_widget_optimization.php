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
        // Index pour la table prospects (vérifier existence)
        Schema::table('prospects', function (Blueprint $table) {
            if (!Schema::hasIndex('prospects', 'idx_prospects_teleprospecteur_statut')) {
                $table->index(['teleprospecteur_id', 'statut'], 'idx_prospects_teleprospecteur_statut');
            }
            if (!Schema::hasIndex('prospects', 'idx_prospects_statut_updated_at')) {
                $table->index(['statut', 'updated_at'], 'idx_prospects_statut_updated_at');
            }
            if (!Schema::hasIndex('prospects', 'idx_prospects_conversion')) {
                $table->index(['teleprospecteur_id', 'converti_partenaire_id', 'updated_at'], 'idx_prospects_conversion');
            }
        });

        // Index pour la table appels
        Schema::table('appels', function (Blueprint $table) {
            if (!Schema::hasIndex('appels', 'idx_appels_user_date')) {
                $table->index(['user_id', 'date_heure'], 'idx_appels_user_date');
            }
        });

        // Index pour la table rendez_vous
        Schema::table('rendez_vous', function (Blueprint $table) {
            if (!Schema::hasIndex('rendez_vous', 'idx_rendez_vous_commercial_statut_date')) {
                $table->index(['commercial_id', 'statut', 'date_heure'], 'idx_rendez_vous_commercial_statut_date');
            }
        });

        // Index pour la table partenaires
        Schema::table('partenaires', function (Blueprint $table) {
            if (!Schema::hasIndex('partenaires', 'idx_partenaires_conseiller_statut')) {
                $table->index(['conseiller_id', 'statut'], 'idx_partenaires_conseiller_statut');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Index pour la table prospects
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropIndex('idx_prospects_teleprospecteur_statut');
            $table->dropIndex('idx_prospects_statut_updated_at');
            $table->dropIndex('idx_prospects_conversion');
        });

        // Index pour la table appels
        Schema::table('appels', function (Blueprint $table) {
            $table->dropIndex('idx_appels_user_date');
        });

        // Index pour la table rendez_vous
        Schema::table('rendez_vous', function (Blueprint $table) {
            $table->dropIndex('idx_rendez_vous_commercial_statut_date');
        });

        // Index pour la table partenaires
        Schema::table('partenaires', function (Blueprint $table) {
            $table->dropIndex('idx_partenaires_conseiller_statut');
        });
    }
};
