<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appels', function (Blueprint $table) {
            $table->foreignId('campagne_id')
                ->nullable()
                ->after('phoning_agent_id')
                ->constrained('campagne_phonings')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appels', function (Blueprint $table) {
            $table->dropForeign(['campagne_id']);
            $table->dropColumn('campagne_id');
        });
    }
};
