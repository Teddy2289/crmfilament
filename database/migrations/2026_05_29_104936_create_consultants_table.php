<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultants', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('statut_vdi')->nullable();
            $table->integer('departement')->nullable();
            $table->foreignId('entite_id')->nullable()->constrained('entite_commerciales')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('entite_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultants');
    }
};
