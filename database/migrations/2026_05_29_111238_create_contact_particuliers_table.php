<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\TypeLogement;
use App\Enums\StatutOccupant;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contact_particuliers', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('prenom', 100)->nullable();
            $table->string('telephone', 20);
            $table->string('email')->nullable();
            $table->text('adresse_complete')->nullable();
            $table->string('type_logement')->nullable();
            $table->string('statut_occupant')->nullable();
            $table->timestamps();

            $table->index('telephone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_particuliers');
    }
};
