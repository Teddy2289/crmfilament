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
        // Indexes pour la table documents (documentable)
        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasIndex('documents', 'documents_documentable_type_id')) {
                $table->index(['documentable_type', 'documentable_id'], 'documents_documentable_type_id');
            }
        });

        // Indexes pour la table rendez_vous (rdvable)
        Schema::table('rendez_vous', function (Blueprint $table) {
            if (! Schema::hasIndex('rendez_vous', 'rendez_vous_rdvable_type_id')) {
                $table->index(['rdvable_type', 'rdvable_id'], 'rendez_vous_rdvable_type_id');
            }
        });

        // Indexes pour la table appels (appelable)
        Schema::table('appels', function (Blueprint $table) {
            if (! Schema::hasIndex('appels', 'appels_appelable_type_id')) {
                $table->index(['appelable_type', 'appelable_id'], 'appels_appelable_type_id');
            }
        });

        // Indexes pour la table sent_emails (emailable)
        Schema::table('sent_emails', function (Blueprint $table) {
            if (! Schema::hasIndex('sent_emails', 'sent_emails_emailable_type_id')) {
                $table->index(['emailable_type', 'emailable_id'], 'sent_emails_emailable_type_id');
            }
        });

        // Indexes pour la table historique_interactions_users (interactable)
        Schema::table('historique_interactions_users', function (Blueprint $table) {
            if (! Schema::hasIndex('historique_interactions_users', 'historique_interactions_interactable_type_id')) {
                $table->index(['interactable_type', 'interactable_id'], 'historique_interactions_interactable_type_id');
            }
        });

        // Aucune action supplémentaire pour historique_modifications : la colonne morphs('model')
        // crée déjà l'index par défaut sur (model_type, model_id).
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('documents_documentable_type_id');
        });

        Schema::table('rendez_vous', function (Blueprint $table) {
            $table->dropIndex('rendez_vous_rdvable_type_id');
        });

        Schema::table('appels', function (Blueprint $table) {
            $table->dropIndex('appels_appelable_type_id');
        });

        Schema::table('sent_emails', function (Blueprint $table) {
            $table->dropIndex('sent_emails_emailable_type_id');
        });

        Schema::table('historique_interactions_users', function (Blueprint $table) {
            $table->dropIndex('historique_interactions_interactable_type_id');
        });

    }
};
