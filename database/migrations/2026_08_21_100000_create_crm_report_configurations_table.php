<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_report_configurations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('report_type', 40)->default('daily');
            $table->text('description')->nullable();
            $table->boolean('active')->default(false)->index();

            $table->string('frequency', 30)->default('daily');
            $table->time('execution_time')->default('07:00:00');
            $table->string('timezone', 64)->default('Europe/Paris');
            $table->json('weekdays')->nullable();

            $table->string('recipient_mode', 30)->default('roles');
            $table->json('recipient_user_ids')->nullable();
            $table->json('recipient_roles')->nullable();
            $table->json('recipient_emails')->nullable();

            $table->json('sections')->nullable();
            $table->string('period_type', 40)->default('previous_period');
            $table->json('options')->nullable();

            $table->timestamp('last_run_at')->nullable()->index();
            $table->timestamp('next_run_at')->nullable()->index();
            $table->string('last_status', 30)->nullable()->index();
            $table->text('last_error')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['active', 'frequency']);
            $table->index(['report_type', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_report_configurations');
    }
};
