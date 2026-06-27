<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appels', function (Blueprint $table) {
            if (! Schema::hasColumn('appels', 'ringover_call_id')) {
                $table->string('ringover_call_id')->nullable()->unique()->after('commentaire');
            }

            if (! Schema::hasColumn('appels', 'enregistrement_audio')) {
                $table->string('enregistrement_audio')->nullable()->after('ringover_call_id');
            }

            if (! Schema::hasColumn('appels', 'ringover_number_id')) {
                $table->string('ringover_number_id')->nullable()->after('ringover_call_id');
            }

            if (! Schema::hasColumn('appels', 'ringover_user_id')) {
                $table->string('ringover_user_id')->nullable()->after('ringover_number_id');
            }

            if (! Schema::hasColumn('appels', 'direction')) {
                $table->string('direction')->nullable()->after('ringover_user_id');
            }

            if (! Schema::hasColumn('appels', 'numero_appelant')) {
                $table->string('numero_appelant')->nullable()->after('direction');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appels', function (Blueprint $table) {
            $table->dropColumn([
                'ringover_number_id',
                'ringover_user_id',
                'direction',
                'numero_appelant',
            ]);
        });
    }
};
