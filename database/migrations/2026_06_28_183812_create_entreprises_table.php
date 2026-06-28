<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('entreprises', function (Blueprint $table) {
            $table->id();
            $table->string('raison_sociale');
            $table->string('siret')->nullable()->unique();
            $table->string('siren')->nullable();
            $table->string('numero_tva')->nullable();
            $table->string('forme_juridique')->nullable();
            $table->string('capital')->nullable();
            $table->string('adresse')->nullable();
            $table->string('code_postal')->nullable();
            $table->string('ville')->nullable();
            $table->string('pays')->default('France');
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('site_web')->nullable();
            $table->string('secteur_activite')->nullable();
            $table->integer('effectif')->nullable();
            $table->string('code_naf')->nullable();
            $table->date('date_creation')->nullable();
            $table->text('description')->nullable();
            $table->json('extra_data')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('siret');
            $table->index('siren');
            $table->index('raison_sociale');
            $table->index('ville');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entreprises');
    }
};
