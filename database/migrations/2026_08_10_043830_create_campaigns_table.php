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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('type')->default('email'); // email, sms, social, print, etc.
            $table->string('statut')->default('draft'); // draft, active, paused, completed, cancelled
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->decimal('budget', 10, 2)->nullable();
            $table->decimal('budget_depense', 10, 2)->default(0);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->json('cibles')->nullable(); // Target audience configuration
            $table->json('contenu')->nullable(); // Content configuration
            $table->integer('envois_total')->default(0);
            $table->integer('ouvertures')->default(0);
            $table->integer('clics')->default(0);
            $table->integer('conversions')->default(0);
            $table->timestamps();

            $table->index('type');
            $table->index('statut');
            $table->index('date_debut');
            $table->index('created_by');
            $table->index('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
