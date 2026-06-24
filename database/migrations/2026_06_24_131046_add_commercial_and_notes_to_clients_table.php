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
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('commercial_id')->nullable()->after('partenaire_id')->constrained('users')->nullOnDelete();
            $table->text('notes_commerciales')->nullable()->after('extra_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['commercial_id']);
            $table->dropColumn(['commercial_id', 'notes_commerciales']);
        });
    }
};
