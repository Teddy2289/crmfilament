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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('type')->default('custom'); // custom, sales, activity, performance
            $table->json('config'); // Configuration du rapport (colonnes, filtres, etc.)
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->boolean('public')->default(false); // Visible par tous ou seulement par le créateur
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('created_by');
            $table->index('public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
