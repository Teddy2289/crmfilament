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
        Schema::create('document_knowledges', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('type'); // procedure, template, guide, autre
            $table->string('categorie')->nullable(); // CDC, commercial, technique, etc.
            $table->string('fichier_path')->nullable();
            $table->string('fichier_nom')->nullable();
            $table->string('fichier_type')->nullable();
            $table->unsignedBigInteger('taille_octets')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('est_publique')->default(true);
            $table->integer('ordre')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'categorie']);
            $table->index('est_publique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_knowledges');
    }
};
