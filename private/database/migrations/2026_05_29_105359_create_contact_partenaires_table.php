<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_partenaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partenaire_id')->constrained('partenaires')->cascadeOnDelete();

            // Identité
            $table->string('civilite')->nullable(); // M., Mme, Mlle
            $table->string('nom', 100);
            $table->string('prenom', 100)->nullable();
            $table->string('fonction')->nullable();
            $table->string('service')->nullable();

            // Coordonnées pro
            $table->string('email')->nullable();
            $table->string('telephone_direct', 20)->nullable();
            $table->string('telephone_mobile', 20)->nullable();

            // Coordonnées perso (pour syndicats/CSE)
            $table->string('telephone_perso', 20)->nullable();
            $table->string('email_perso')->nullable();

            // Informations complémentaires
            $table->date('date_naissance')->nullable();
            $table->text('notes')->nullable();

            // Qualification
            $table->boolean('est_principal')->default(false);
            $table->boolean('est_decisionnaire')->default(false);
            $table->integer('niveau_influence')->nullable(); // 1-5
            $table->string('canal_prefere')->nullable(); // email, telephone, sms

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index('partenaire_id');
            $table->index('est_principal');
            $table->index('fonction');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_partenaires');
    }
};
