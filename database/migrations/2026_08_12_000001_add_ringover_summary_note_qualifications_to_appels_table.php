<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appels', function (Blueprint $table) {
            if (! Schema::hasColumn('appels', 'ringover_summary')) {
                $table->text('ringover_summary')->nullable()->after('ringover_status_tag');
            }

            if (! Schema::hasColumn('appels', 'ringover_note')) {
                $table->text('ringover_note')->nullable()->after('ringover_summary');
            }

            if (! Schema::hasColumn('appels', 'ringover_qualifications')) {
                $table->json('ringover_qualifications')->nullable()->after('ringover_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appels', function (Blueprint $table) {
            foreach (['ringover_summary', 'ringover_note', 'ringover_qualifications'] as $column) {
                if (Schema::hasColumn('appels', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
