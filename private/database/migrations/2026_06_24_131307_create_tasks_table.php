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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('type')->default('tache'); // tache, rappel, appel, rdv
            $table->string('statut')->default('a_faire'); // a_faire, en_cours, terminee, annulee
            $table->dateTime('date_echeance')->nullable();
            $table->dateTime('date_realisation')->nullable();
            $table->foreignId('assigne_a')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('prospect_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('partenaire_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['statut', 'date_echeance']);
            $table->index('assigne_a');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
