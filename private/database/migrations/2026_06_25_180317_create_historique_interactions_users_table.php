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
        Schema::create('historique_interactions_users', function (Blueprint $table) {
            $table->id();
            $table->string('interactable_type');
            $table->unsignedBigInteger('interactable_id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type_interaction')->default('consultation');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('date_interaction')->useCurrent();
            $table->timestamps();

            $table->index(['interactable_type', 'interactable_id'], 'interactable_idx');
            $table->index('user_id');
            $table->index('date_interaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historique_interactions_users');
    }
};
