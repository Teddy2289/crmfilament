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
        Schema::create('kpis', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('type')->default('count'); // count, sum, average, percentage
            $table->string('model'); // prospect, client, partenaire, etc.
            $table->string('field'); // Le champ à calculer
            $table->json('filters')->nullable(); // Filtres pour le calcul
            $table->string('aggregation_period')->default('daily'); // daily, weekly, monthly, yearly
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('public')->default(false);
            $table->boolean('actif')->default(true);
            $table->decimal('target_value', 10, 2)->nullable();
            $table->string('target_operator')->default('>='); // >=, <=, =, >, <
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('model');
            $table->index('user_id');
            $table->index('public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpis');
    }
};
