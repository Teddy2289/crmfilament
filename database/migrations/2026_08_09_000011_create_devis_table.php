<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devis', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->dateTime('date_emission');
            $table->dateTime('date_validite')->nullable();
            $table->decimal('montant_ht', 10, 2);
            $table->decimal('montant_tva', 10, 2)->default(0);
            $table->decimal('montant_ttc', 10, 2);
            $table->string('statut')->default('brouillon'); // brouillon, envoye, accepte, refuse, expire
            $table->text('conditions')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('client_id');
            $table->index('statut');
            $table->index('date_emission');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devis');
    }
};
