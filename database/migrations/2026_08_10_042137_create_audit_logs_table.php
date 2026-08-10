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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action'); // created, updated, deleted, restored, etc.
            $table->string('model_type'); // Prospect, Client, etc.
            $table->unsignedBigInteger('model_id');
            $table->string('model_name')->nullable(); // Nom lisible du modèle
            $table->json('old_values')->nullable(); // Valeurs avant modification
            $table->json('new_values')->nullable(); // Valeurs après modification
            $table->text('description')->nullable(); // Description de l'action
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->foreignId('related_model_id')->nullable();
            $table->string('related_model_type')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
