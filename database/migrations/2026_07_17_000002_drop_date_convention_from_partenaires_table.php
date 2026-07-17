<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partenaires', function (Blueprint $table) {
            if (Schema::hasColumn('partenaires', 'date_convention')) {
                $table->dropColumn('date_convention');
            }
        });
    }

    public function down(): void
    {
        Schema::table('partenaires', function (Blueprint $table) {
            $table->date('date_convention')->nullable();
        });
    }
};
