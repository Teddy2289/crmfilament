<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campagnes_marketing', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('type'); // email, sms, newsletter, social
            $table->text('description')->nullable();
            $table->dateTime('date_debut');
            $table->dateTime('date_fin')->nullable();
            $table->string('statut')->default('brouillon'); // brouillon, active, terminee, annulee
            $table->json('cibles')->nullable(); // Cibles de la campagne
            $table->json('contenu')->nullable(); // Contenu de la campagne
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('type');
            $table->index('statut');
            $table->index('date_debut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campagnes_marketing');
    }
};
