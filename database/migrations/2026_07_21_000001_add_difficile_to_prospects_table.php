<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->boolean('difficile')->default(false)->after('rappel_planifie_at');
            $table->timestamp('difficile_at')->nullable()->after('difficile');
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn(['difficile', 'difficile_at']);
        });
    }
};
