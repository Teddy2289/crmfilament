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
        Schema::table('ringover_api_keys', function (Blueprint $table) {
            $table->string('click_to_call_url')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ringover_api_keys', function (Blueprint $table) {
            $table->dropColumn('click_to_call_url');
        });
    }
};
