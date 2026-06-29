<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partenaires', function (Blueprint $table) {
            if (! Schema::hasColumn('partenaires', 'commercial_id')) {
                $table->foreignId('commercial_id')
                    ->nullable()
                    ->after('prospect_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('partenaires', function (Blueprint $table) {
            if (Schema::hasColumn('partenaires', 'commercial_id')) {
                $table->dropForeign(['commercial_id']);
                $table->dropColumn('commercial_id');
            }
        });
    }
};
