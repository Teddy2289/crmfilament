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
        Schema::table('prospects', function (Blueprint $table) {
            $table->string('interlocuteur_add_nom')->nullable()->after('interlocuteur_email');
            $table->string('interlocuteur_add_fonction')->nullable()->after('interlocuteur_add_nom');
            $table->string('interlocuteur_add_telephone')->nullable()->after('interlocuteur_add_fonction');
            $table->string('interlocuteur_add_email')->nullable()->after('interlocuteur_add_telephone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn(['interlocuteur_add_nom', 'interlocuteur_add_fonction', 'interlocuteur_add_telephone', 'interlocuteur_add_email']);
        });
    }
};
