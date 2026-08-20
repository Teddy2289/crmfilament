<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('crm_campaign_weekly_reports', function (Blueprint $table): void {
            $table->unsignedInteger('total_unique_called')->default(0)->after('total_available');
            $table->unsignedInteger('total_calls')->default(0)->after('total_unique_called');
        });
    }
    public function down(): void
    {
        Schema::table('crm_campaign_weekly_reports', function (Blueprint $table): void {
            $table->dropColumn(['total_unique_called', 'total_calls']);
        });
    }
};
