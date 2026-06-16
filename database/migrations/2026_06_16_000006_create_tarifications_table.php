<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partenaire_id')->constrained('partenaires')->cascadeOnDelete();
            $table->decimal('prix_pc', 10, 2)->nullable();
            $table->decimal('part_aopia', 10, 2)->nullable();
            $table->decimal('part_cse', 10, 2)->nullable();
            $table->decimal('part_salarie', 10, 2)->nullable();
            $table->text('adresse_facturation')->nullable();
            $table->timestamps();

            $table->index('partenaire_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifications');
    }
};
