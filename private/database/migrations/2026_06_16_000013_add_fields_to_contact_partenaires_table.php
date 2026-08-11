<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_partenaires', function (Blueprint $table) {
            // Champ 'mail' du schéma (alias propre de email pour conformité schéma)
            $table->string('mail')->nullable()->after('email');
            // Champ 'telephone' consolidé du schéma
            $table->string('telephone', 20)->nullable()->after('mail');
            // Préférence de contact (schéma 1 uniquement, ignoré par schéma 2)
            $table->string('preference_contact')->nullable()->after('telephone');
        });
    }

    public function down(): void
    {
        Schema::table('contact_partenaires', function (Blueprint $table) {
            $table->dropColumn(['mail', 'telephone', 'preference_contact']);
        });
    }
};
