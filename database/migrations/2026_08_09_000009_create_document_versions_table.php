<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->string('fichier');
            $table->string('chemin');
            $table->integer('version')->default(1);
            $table->text('commentaire')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index('document_id');
            $table->index('version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
