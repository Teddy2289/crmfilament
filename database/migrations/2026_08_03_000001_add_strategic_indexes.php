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
        // Indexes pour prospects
        Schema::table('prospects', function (Blueprint $table) {
            // Index composé statut + commercial_id pour les filtres fréquents
            if (! Schema::hasIndex('prospects', 'prospects_statut_commercial_id')) {
                $table->index(['statut', 'commercial_id'], 'prospects_statut_commercial_id');
            }

            // Index composé statut + teleprospecteur_id pour le phoning
            if (! Schema::hasIndex('prospects', 'prospects_statut_teleprospecteur_id')) {
                $table->index(['statut', 'teleprospecteur_id'], 'prospects_statut_teleprospecteur_id');
            }

            // Index pour rappel_planifie_at (très utilisé pour les rappels)
            if (! Schema::hasIndex('prospects', 'prospects_rappel_planifie_at')) {
                $table->index('rappel_planifie_at', 'prospects_rappel_planifie_at');
            }
        });

        // Indexes pour partenaires
        Schema::table('partenaires', function (Blueprint $table) {
            // Index composé statut + commercial_id
            if (! Schema::hasIndex('partenaires', 'partenaires_statut_commercial_id')) {
                $table->index(['statut', 'commercial_id'], 'partenaires_statut_commercial_id');
            }

            // Index composé statut + entite_id
            if (! Schema::hasIndex('partenaires', 'partenaires_statut_entite_id')) {
                $table->index(['statut', 'entite_id'], 'partenaires_statut_entite_id');
            }

            // Index pour date_modification_statut
            if (! Schema::hasIndex('partenaires', 'partenaires_date_modification_statut')) {
                $table->index('date_modification_statut', 'partenaires_date_modification_statut');
            }
        });

        // Indexes pour clients
        Schema::table('clients', function (Blueprint $table) {
            // Index composé etat + commercial_id
            if (! Schema::hasIndex('clients', 'clients_etat_commercial_id')) {
                $table->index(['etat', 'commercial_id'], 'clients_etat_commercial_id');
            }

            // Index composé etat + partenaire_id
            if (! Schema::hasIndex('clients', 'clients_etat_partenaire_id')) {
                $table->index(['etat', 'partenaire_id'], 'clients_etat_partenaire_id');
            }

            // Index pour ne_plus_contacter
            if (! Schema::hasIndex('clients', 'clients_ne_plus_contacter')) {
                $table->index('ne_plus_contacter', 'clients_ne_plus_contacter');
            }
        });

        // Indexes pour rendez_vous
        Schema::table('rendez_vous', function (Blueprint $table) {
            // Index composé statut + commercial_id
            if (! Schema::hasIndex('rendez_vous', 'rendez_vous_statut_commercial_id')) {
                $table->index(['statut', 'commercial_id'], 'rendez_vous_statut_commercial_id');
            }

            // Index composé statut + teleprospecteur_id
            if (! Schema::hasIndex('rendez_vous', 'rendez_vous_statut_teleprospecteur_id')) {
                $table->index(['statut', 'teleprospecteur_id'], 'rendez_vous_statut_teleprospecteur_id');
            }

            // Index composé date_heure + statut
            if (! Schema::hasIndex('rendez_vous', 'rendez_vous_date_heure_statut')) {
                $table->index(['date_heure', 'statut'], 'rendez_vous_date_heure_statut');
            }
        });

        // Indexes pour appels
        Schema::table('appels', function (Blueprint $table) {
            // Index composé appelable_type + appelable_id
            if (! Schema::hasIndex('appels', 'appels_appelable_type_id')) {
                $table->index(['appelable_type', 'appelable_id'], 'appels_appelable_type_id');
            }

            // Index composé date_heure + user_id
            if (! Schema::hasIndex('appels', 'appels_date_heure_user_id')) {
                $table->index(['date_heure', 'user_id'], 'appels_date_heure_user_id');
            }

            // Index pour phoning_status
            if (! Schema::hasIndex('appels', 'appels_phoning_status')) {
                $table->index('phoning_status', 'appels_phoning_status');
            }
        });

        // Indexes pour propositions (si la table existe)
        if (Schema::hasTable('propositions')) {
            Schema::table('propositions', function (Blueprint $table) {
                // Index composé etat + date_lancement
                if (! Schema::hasIndex('propositions', 'propositions_etat_date_lancement')) {
                    $table->index(['etat', 'date_lancement'], 'propositions_etat_date_lancement');
                }

                // Index composé etat + date_vente
                if (! Schema::hasIndex('propositions', 'propositions_etat_date_vente')) {
                    $table->index(['etat', 'date_vente'], 'propositions_etat_date_vente');
                }

                // Index pour date_debut_formation
                if (! Schema::hasIndex('propositions', 'propositions_date_debut_formation')) {
                    $table->index('date_debut_formation', 'propositions_date_debut_formation');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropIndex('prospects_statut_commercial_id');
            $table->dropIndex('prospects_statut_teleprospecteur_id');
            $table->dropIndex('prospects_rappel_planifie_at');
        });

        Schema::table('partenaires', function (Blueprint $table) {
            $table->dropIndex('partenaires_statut_commercial_id');
            $table->dropIndex('partenaires_statut_entite_id');
            $table->dropIndex('partenaires_date_modification_statut');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_etat_commercial_id');
            $table->dropIndex('clients_etat_partenaire_id');
            $table->dropIndex('clients_ne_plus_contacter');
        });

        Schema::table('rendez_vous', function (Blueprint $table) {
            $table->dropIndex('rendez_vous_statut_commercial_id');
            $table->dropIndex('rendez_vous_statut_teleprospecteur_id');
            $table->dropIndex('rendez_vous_date_heure_statut');
        });

        Schema::table('appels', function (Blueprint $table) {
            $table->dropIndex('appels_appelable_type_id');
            $table->dropIndex('appels_date_heure_user_id');
            $table->dropIndex('appels_phoning_status');
        });

        Schema::table('propositions', function (Blueprint $table) {
            $table->dropIndex('propositions_etat_date_lancement');
            $table->dropIndex('propositions_etat_date_vente');
            $table->dropIndex('propositions_date_debut_formation');
        });
    }
};
