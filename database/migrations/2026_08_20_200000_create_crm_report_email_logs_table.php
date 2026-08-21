<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_report_email_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('execution_uuid');
            $table->string('idempotency_key', 190)->unique();
            $table->string('report_key', 100);
            $table->string('report_type', 60)->default('daily');
            $table->string('scope', 60)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_email');
            $table->string('subject')->nullable();
            $table->string('status', 30)->default('pending');
            $table->string('message_id')->nullable();
            $table->string('error_class')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['report_key', 'status']);
            $table->index(['recipient_email', 'created_at']);
            $table->index(['execution_uuid', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_report_email_logs');
    }
};

