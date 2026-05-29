<?php

use App\Enums\OrganizationCategory;
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
         Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('nom_fichier');
            $table->string('categorie')->default(OrganizationCategory::Partenaires->value);
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->bigInteger('taille')->nullable();
            $table->morphs('documentable');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
