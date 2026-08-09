<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('model_type'); // prospect, partenaire, client, etc.
            $table->string('nom');
            $table->json('mapping'); // Mapping colonnes CSV -> champs modèle
            $table->json('options')->nullable(); // Options d'import
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('model_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_mappings');
    }
};
