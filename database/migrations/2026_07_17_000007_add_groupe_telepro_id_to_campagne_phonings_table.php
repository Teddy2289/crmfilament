<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campagne_phonings', function (Blueprint $table) {
            if (! Schema::hasColumn('campagne_phonings', 'groupe_telepro_id')) {
                $table->foreignId('groupe_telepro_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('groupes_telepro')
                    ->nullOnDelete();

                $table->index('groupe_telepro_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('campagne_phonings', function (Blueprint $table) {
            if (Schema::hasColumn('campagne_phonings', 'groupe_telepro_id')) {
                $table->dropForeign(['groupe_telepro_id']);
                $table->dropColumn('groupe_telepro_id');
            }
        });
    }
};
