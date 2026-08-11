<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activite_partenaires', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->integer('nb_ventes')->default(0);
            $table->date('derniere_vente')->nullable();
            $table->integer('nb_permanences')->default(0);
            $table->date('derniere_permanence')->nullable();
            $table->foreignId('partenaire_id')->constrained('partenaires')->cascadeOnDelete();
            $table->foreignId('consultant_id')->nullable()->constrained('consultants')->nullOnDelete();
            $table->timestamps();

            $table->index('partenaire_id');
            $table->index('consultant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activite_partenaires');
    }
};
