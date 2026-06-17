<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autres_interlocuteurs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('partenaire_id')
                ->constrained('partenaires')
                ->cascadeOnDelete();

            // Champ texte libre non normalisable — volontairement non structuré
            $table->text('texte_libre')->nullable();

            $table->timestamps();

            $table->index('partenaire_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autres_interlocuteurs');
    }
};
