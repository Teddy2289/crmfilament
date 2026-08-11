<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            if (! Schema::hasColumn('prospects', 'interlocuteur_prenom')) {
                $table->string('interlocuteur_prenom')->nullable()->after('interlocuteur_nom');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn('interlocuteur_prenom');
        });
    }
};
