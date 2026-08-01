<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campagne_phonings', function (Blueprint $table) {
            if (! Schema::hasColumn('campagne_phonings', 'max_tentatives')) {
                $table->integer('max_tentatives')->default(4)->after('criteres');
            }
            if (! Schema::hasColumn('campagne_phonings', 'jours_refroidissement')) {
                $table->integer('jours_refroidissement')->default(15)->after('max_tentatives');
            }
            if (! Schema::hasColumn('campagne_phonings', 'exclure_autres_campagnes')) {
                $table->boolean('exclure_autres_campagnes')->default(true)->after('jours_refroidissement');
            }
            if (! Schema::hasColumn('campagne_phonings', 'exclure_sans_telephone')) {
                $table->boolean('exclure_sans_telephone')->default(true)->after('exclure_autres_campagnes');
            }
            if (! Schema::hasColumn('campagne_phonings', 'script_appel')) {
                $table->text('script_appel')->nullable()->after('exclure_sans_telephone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('campagne_phonings', function (Blueprint $table) {
            $columns = [
                'max_tentatives',
                'jours_refroidissement',
                'exclure_autres_campagnes',
                'exclure_sans_telephone',
                'script_appel',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('campagne_phonings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
