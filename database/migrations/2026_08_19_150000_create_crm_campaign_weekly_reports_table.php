<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_campaign_weekly_reports', function (Blueprint $table): void {
            $table->id();
            $table->date('report_date');
            $table->string('scope', 40)->default('department');
            $table->string('department', 10)->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedInteger('total_department')->default(0);
            $table->unsignedInteger('total_targeted')->default(0);
            $table->unsignedInteger('total_available')->default(0);
            $table->unsignedInteger('cooling_down')->default(0);
            $table->unsignedInteger('max_attempts_reached')->default(0);
            $table->unsignedInteger('without_phone')->default(0);
            $table->unsignedInteger('treated')->default(0);
            $table->unsignedInteger('remaining')->default(0);
            $table->json('status_breakdown')->nullable();
            $table->json('campaign_breakdown')->nullable();
            $table->json('comparison')->nullable();
            $table->timestamps();
            $table->unique(['report_date', 'scope', 'department', 'campaign_id'], 'crm_weekly_reports_snapshot_unique');
            $table->index(['department', 'report_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_campaign_weekly_reports');
    }
};
