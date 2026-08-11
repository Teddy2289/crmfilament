<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campagne_phonings', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('statut', 20)->default('brouillon'); // brouillon | active | terminee
            $table->string('type_entite', 20); // prospects | partenaires | clients
            $table->json('criteres')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('entite_id')->nullable()->constrained('entite_commerciales')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('entite_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campagne_phonings');
    }
};
