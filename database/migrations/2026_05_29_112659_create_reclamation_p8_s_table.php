<?php

use App\Enums\StatutReclamation;
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

        Schema::create('reclamation_p8s', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets');
            $table->foreignId('rapport_satisfaction_id')->nullable()
                ->constrained('rapport_satisfaction_p6s')->nullOnDelete();
            $table->dateTime('date_ouverture');
            $table->text('description_reclamation');
            $table->string('statut')->default(StatutReclamation::Ouverte->value);
            $table->date('date_resolution_cible');
            $table->date('date_resolution_effective')->nullable();
            $table->boolean('validation_superviseur')->default(false);
            $table->foreignId('superviseur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes_resolution')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reclamation_p8_s');
    }
};
