<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('crm_campaign_weekly_reports', function (Blueprint $table): void {
            $table->json('status_trends')->nullable()->after('status_breakdown');
        });
    }

    public function down(): void
    {
        Schema::table('crm_campaign_weekly_reports', function (Blueprint $table): void {
            $table->dropColumn('status_trends');
        });
    }
};
