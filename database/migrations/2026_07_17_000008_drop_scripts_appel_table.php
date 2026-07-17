<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('scripts_appel');
    }

    public function down(): void
    {
        Schema::create('scripts_appel', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->string('slug')->unique();
            $table->string('type_contact')->nullable();
            $table->foreignId('campagne_id')->nullable()->constrained('campagne_phonings')->nullOnDelete();
            $table->enum('onglet', [
                'accroche',
                'decouverte',
                'argumentaire',
                'objections',
                'closing',
            ])->default('accroche');
            $table->text('contenu')->nullable();
            $table->text('conseil')->nullable();
            $table->json('variables_disponibles')->nullable();
            $table->json('objections')->nullable();
            $table->json('kpis')->nullable();
            $table->boolean('actif')->default(true);
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['onglet', 'type_contact', 'actif']);
        });
    }
};
