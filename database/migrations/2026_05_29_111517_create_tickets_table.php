<?php

use App\Enums\TicketStatut;
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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('contact_particulier_id')->constrained('contact_particuliers');
            $table->foreignId('artisan_id')->nullable()->constrained('artisans')->nullOnDelete();
            $table->foreignId('operateur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('statut')->default(TicketStatut::AppelRecu->value);
            $table->string('niveau_priorite')->nullable();
            $table->string('corps_de_metier')->nullable();
            $table->dateTime('date_creation');
            $table->dateTime('date_cloture')->nullable();
            $table->dateTime('rdv_planifie_at')->nullable();
            $table->dateTime('rappel_promise_at')->nullable();
            $table->string('aircall_call_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('statut');
            $table->index('niveau_priorite');
            $table->index('date_creation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
