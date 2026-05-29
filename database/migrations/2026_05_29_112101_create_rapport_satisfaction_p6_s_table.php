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
        Schema::create('rapport_satisfaction_p6s', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('artisan_id')->constrained('artisans');
            $table->foreignId('operateur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date_appel_j1');
            $table->integer('note_nps');
            $table->text('verbatim_client')->nullable();
            $table->boolean('feedback_artisan')->default(false);
            $table->string('statut_cloture');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rapport_satisfaction_p6s');
    }
};
