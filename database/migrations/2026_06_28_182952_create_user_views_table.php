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
        Schema::create('user_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('resource'); // e.g., 'prospects', 'clients', 'partenaires'
            $table->string('name'); // e.g., 'Ma vue', 'Vue par statut'
            $table->string('type'); // 'list', 'kanban', 'grid'
            $table->json('config'); // Store view configuration (columns, filters, groupings)
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'resource', 'name']);
            $table->index(['user_id', 'resource']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_views');
    }
};
