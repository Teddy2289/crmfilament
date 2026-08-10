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
        Schema::create('analytic_dashboards', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->json('widgets')->nullable(); // Configuration des widgets
            $table->json('filters')->nullable(); // Filtres par défaut
            $table->boolean('public')->default(false);
            $table->boolean('default')->default(false); // Dashboard par défaut pour l'utilisateur
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('created_by');
            $table->index('public');
            $table->index('default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytic_dashboards');
    }
};
