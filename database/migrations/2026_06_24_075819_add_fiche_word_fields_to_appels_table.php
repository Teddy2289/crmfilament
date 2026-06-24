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
        Schema::table('appels', function (Blueprint $table) {
            $table->string('fiche_word_path')->nullable()->after('fiche_data');
            $table->timestamp('fiche_word_generated_at')->nullable()->after('fiche_word_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appels', function (Blueprint $table) {
            $table->dropColumn(['fiche_word_path', 'fiche_word_generated_at']);
        });
    }
};
