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
        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->date('date_prevue');
            $table->date('date_reelle')->nullable();
            $table->string('statut')->default('pending'); // pending, in_progress, completed, delayed
            $table->integer('progress')->default(0);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('project_id');
            $table->index('date_prevue');
            $table->index('statut');
            $table->index('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milestones');
    }
};
