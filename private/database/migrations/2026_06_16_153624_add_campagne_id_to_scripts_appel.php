<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scripts_appel', function (Blueprint $table) {
            // null = script universel/générique ; non-null = lié à une campagne spécifique
            $table->foreignId('campagne_id')
                ->nullable()
                ->after('type_contact')
                ->constrained('campagne_phonings')
                ->nullOnDelete();

            $table->index('campagne_id');
        });
    }

    public function down(): void
    {
        Schema::table('scripts_appel', function (Blueprint $table) {
            $table->dropForeign(['campagne_id']);
            $table->dropIndex(['scripts_appel_campagne_id_index']);
            $table->dropColumn('campagne_id');
        });
    }
};
