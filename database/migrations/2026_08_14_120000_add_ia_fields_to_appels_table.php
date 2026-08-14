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
        Schema::table('appels', function (Blueprint $table) {
            // Résumé IA de l'appel
            $table->text('resume_ia')->nullable()->after('ringover_payload');

            // Note/Score IA de l'appel (0-100)
            $table->integer('note_ia')->nullable()->after('resume_ia');

            // Sentiment IA (positive, neutral, negative)
            $table->enum('sentiment_ia', ['positive', 'neutral', 'negative'])->nullable()->after('note_ia');

            // Timestamp de génération IA
            $table->timestamp('ia_generated_at')->nullable()->after('sentiment_ia');

            // Index pour optimiser les requêtes
            $table->index('note_ia');
            $table->index('sentiment_ia');
            $table->index('ia_generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appels', function (Blueprint $table) {
            $table->dropIndex(['note_ia']);
            $table->dropIndex(['sentiment_ia']);
            $table->dropIndex(['ia_generated_at']);
            $table->dropColumn(['resume_ia', 'note_ia', 'sentiment_ia', 'ia_generated_at']);
        });
    }
};
