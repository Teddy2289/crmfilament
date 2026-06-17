<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Complète la table partenaires avec les colonnes MEA et corrige les types.
 * Exécutée après create_prospects_table → prospect_id peut être ajouté ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partenaires', function (Blueprint $table) {
            // prospect_id — ajouté ici car prospects n'existait pas encore lors du create
            if (! Schema::hasColumn('partenaires', 'prospect_id')) {
                $table->foreignId('prospect_id')
                    ->nullable()
                    ->constrained('prospects')
                    ->nullOnDelete();
                $table->index('prospect_id');
            }

            // possibilite_permanence et replicable (TEXT, pas VARCHAR)
            if (! Schema::hasColumn('partenaires', 'possibilite_permanence')) {
                $table->string('possibilite_permanence')->nullable();
            }
            if (! Schema::hasColumn('partenaires', 'replicable')) {
                $table->text('replicable')->nullable();
            }

            // commentaire_import
            if (! Schema::hasColumn('partenaires', 'commentaire_import')) {
                $table->text('commentaire_import')->nullable();
            }
        });

        // Corriger replicable VARCHAR→TEXT si déjà créée en string
        if (Schema::hasColumn('partenaires', 'replicable')) {
            DB::statement('ALTER TABLE partenaires MODIFY COLUMN replicable TEXT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('partenaires', function (Blueprint $table) {
            if (Schema::hasColumn('partenaires', 'prospect_id')) {
                $table->dropForeign(['prospect_id']);
                $table->dropColumn('prospect_id');
            }
            foreach (['possibilite_permanence', 'replicable', 'commentaire_import'] as $col) {
                if (Schema::hasColumn('partenaires', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
