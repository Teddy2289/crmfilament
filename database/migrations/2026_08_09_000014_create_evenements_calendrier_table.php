<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evenements_calendrier', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->dateTime('debut');
            $table->dateTime('fin');
            $table->boolean('journee_entiere')->default(false);
            $table->string('type')->default('rdv'); // rdv, tache, rappel, evenement
            $table->string('statut')->default('planifie'); // planifie, en_cours, termine, annule
            $table->string('lieu')->nullable();
            $table->json('participants')->nullable(); // IDs des participants
            $table->string('couleur')->default('blue'); // Couleur d'affichage
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('rendez_vous_id')->nullable()->constrained('rendez_vous')->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('user_id');
            $table->index('debut');
            $table->index('fin');
            $table->index('type');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenements_calendrier');
    }
};
