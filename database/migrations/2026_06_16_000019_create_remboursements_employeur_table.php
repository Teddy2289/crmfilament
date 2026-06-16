<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Présent uniquement dans l'onglet PARTENAIRES & PROSPECTIONS COMM.
     * Montant fixe 100 € par demande de remboursement employeur.
     */
    public function up(): void
    {
        Schema::create('remboursements_employeur', function (Blueprint $table) {
            $table->id();

            $table->foreignId('partenaire_id')
                  ->constrained('partenaires')
                  ->cascadeOnDelete();

            $table->date('date_demande')->nullable();
            $table->decimal('montant', 8, 2)->default(100.00)->comment('Montant fixe 100 €');
            $table->text('commentaires')->nullable();

            $table->timestamps();

            $table->index('partenaire_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remboursements_employeur');
    }
};
