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
        Schema::create('gantt_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('gantt_tasks')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('prospect_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('partenaire_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('opportunite_id')->nullable()->constrained()->onDelete('set null');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('duration')->nullable(); // En jours
            $table->integer('progress')->default(0); // 0-100
            $table->string('status')->default('pending'); // pending, in_progress, completed, delayed
            $table->integer('order')->default(0);
            $table->boolean('milestone')->default(false);
            $table->string('color')->nullable(); // Couleur pour le diagramme
            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
            $table->index('parent_id');
            $table->index('assigned_to');
            $table->index('start_date');
            $table->index('end_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gantt_tasks');
    }
};
