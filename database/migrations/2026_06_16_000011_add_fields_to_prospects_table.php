<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->integer('numero_ordre')->nullable()->after('id');
            $table->string('raison_sociale')->nullable()->after('nom');
            $table->foreignId('campagne_id')
                ->nullable()
                ->constrained('campagne_phonings')
                ->nullOnDelete()
                ->after('ordre_priorite');
            $table->foreignId('converti_partenaire_id')
                ->nullable()
                ->constrained('partenaires')
                ->nullOnDelete()
                ->after('campagne_id');

            $table->index('campagne_id');
            $table->index('converti_partenaire_id');
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropForeign(['campagne_id']);
            $table->dropForeign(['converti_partenaire_id']);
            $table->dropColumn(['numero_ordre', 'raison_sociale', 'campagne_id', 'converti_partenaire_id']);
        });
    }
};
