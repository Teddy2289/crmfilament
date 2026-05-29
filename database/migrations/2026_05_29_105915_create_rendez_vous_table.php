<?php

use App\Enums\RendezVousStatut;
use App\Enums\RendezVousType;
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
        Schema::create('rendez_vous', function (Blueprint $table) {
            $table->id();
            $table->morphs('rdvable');
            $table->foreignId('commercial_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('teleprospecteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default(RendezVousType::Appel->value);
            $table->string('statut')->default(RendezVousStatut::Planifie->value);
            $table->dateTime('date_heure');
            $table->string('lieu')->nullable();
            $table->text('adresse_lieu')->nullable();
            $table->string('interlocuteur_nom')->nullable();
            $table->string('interlocuteur_tel')->nullable();
            $table->string('interlocuteur_email')->nullable();
            $table->text('notes')->nullable();
            $table->string('pdf_recap')->nullable();
            $table->string('enregistrement_audio')->nullable();
            $table->boolean('email_confirmation_envoye')->default(false);
            $table->boolean('email_invitation_envoye')->default(false);
            $table->string('outlook_event_id')->nullable();
            $table->string('google_event_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rendez_vous');
    }
};
