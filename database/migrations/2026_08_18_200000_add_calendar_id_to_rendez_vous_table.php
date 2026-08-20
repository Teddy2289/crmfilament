<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('rendez_vous', 'calendar_id')) {
            Schema::table('rendez_vous', function (Blueprint $table) {
                $table->string('calendar_id')->nullable()->after('google_event_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('rendez_vous', 'calendar_id')) {
            Schema::table('rendez_vous', fn (Blueprint $table) => $table->dropColumn('calendar_id'));
        }
    }
};
