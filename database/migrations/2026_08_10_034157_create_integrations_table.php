<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // slack, teams, outlook, etc.
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('config'); // API keys, webhooks, etc.
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('actif')->default(true);
            $table->boolean('verified')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('user_id');
            $table->index('actif');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
