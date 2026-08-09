<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->enum('priorite', ['basse', 'moyenne', 'haute', 'critique'])->default('moyenne');
            $table->enum('statut', ['a_faire', 'en_cours', 'en_attente', 'terminee', 'annulee'])->default('a_faire');
            $table->dateTime('date_echeance')->nullable();
            $table->dateTime('date_rappel')->nullable();
            $table->dateTime('date_terminee')->nullable();
            
            // Relations polymorphiques
            $table->string('taskable_type')->nullable();
            $table->unsignedBigInteger('taskable_id')->nullable();
            
            // Assignation
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index(['taskable_type', 'taskable_id']);
            $table->index('assigned_to');
            $table->index('statut');
            $table->index('priorite');
            $table->index('date_echeance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
