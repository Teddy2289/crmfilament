<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adresse_cses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partenaire_id')->constrained('partenaires')->cascadeOnDelete();
            $table->text('adresse')->nullable();
            $table->string('code_postal', 10)->nullable();
            $table->string('commune')->nullable();
            $table->timestamps();

            $table->index('partenaire_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adresse_cses');
    }
};
