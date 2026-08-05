<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('email_templates', 'corps_plain')) {
            Schema::table('email_templates', function (Blueprint $table) {
                $table->text('corps_plain')->nullable()->after('corps');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('email_templates', 'corps_plain')) {
            Schema::table('email_templates', function (Blueprint $table) {
                $table->dropColumn('corps_plain');
            });
        }
    }
};
