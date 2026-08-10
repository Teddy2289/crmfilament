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
        // Cette colonne est déjà définie dans 2026_06_28_070806_create_workflow_steps_table.php
        // Migration conservée pour la compatibilité de l'historique, mais sans opération
        if (!Schema::hasColumn('workflow_steps', 'est_final')) {
            Schema::table('workflow_steps', function (Blueprint $table) {
                $table->boolean('est_final')->default(false)->after('actif');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_steps', function (Blueprint $table) {
            $table->dropColumn('est_final');
        });
    }
};
