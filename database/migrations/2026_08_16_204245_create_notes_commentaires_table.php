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
        Schema::create('notes_commentaires', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relation
            $table->morphs('notable');
            
            // User who created the note/comment
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            // Type of note/comment
            $table->string('type_note')->default('note'); // 'note', 'commentaire', 'suivi', 'rapport', 'phoning', 'fiche', etc.
            
            // Content
            $table->text('contenu');
            
            // Privacy flag
            $table->boolean('is_prive')->default(false);
            
            // Context metadata (JSON)
            $table->json('contexte')->nullable();
            
            // Soft deletes
            $table->softDeletes();
            
            $table->timestamps();
            
            // Indexes
            $table->index('type_note');
            $table->index('is_prive');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes_commentaires');
    }
};
