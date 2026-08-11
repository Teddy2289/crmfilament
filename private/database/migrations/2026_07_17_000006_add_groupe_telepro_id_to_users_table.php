<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'groupe_telepro_id')) {
                $table->foreignId('groupe_telepro_id')
                    ->nullable()
                    ->after('role_cache')
                    ->constrained('groupes_telepro')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'groupe_telepro_id')) {
                $table->dropForeign(['groupe_telepro_id']);
                $table->dropColumn('groupe_telepro_id');
            }
        });
    }
};
