<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artisans', function (Blueprint $table) {
            // Numéro de transfert CTI (Screen Pop — Section 11.3 du pipeline)
            $table->string('numero_cti_transfert', 30)->nullable()->after('telephone_secondaire')
                ->comment('Numéro affiché lors du transfert CTI vers l\'artisan (Screen Pop)');
        });
    }

    public function down(): void
    {
        Schema::table('artisans', function (Blueprint $table) {
            $table->dropColumn('numero_cti_transfert');
        });
    }
};
