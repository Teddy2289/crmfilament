<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affaire_interventions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique()->comment('AFF-YYYY-NNNNN');

            // Liens
            $table->foreignId('ticket_id')
                ->constrained('tickets')
                ->cascadeOnDelete();
            $table->foreignId('artisan_id')
                ->constrained('artisans')
                ->cascadeOnDelete();
            $table->foreignId('operateur_dispatch_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Agent qui a effectué le dispatch P3');

            // Statut
            $table->string('statut')->default('en_attente');
            $table->unsignedTinyInteger('numero_tentative')->default(1)
                ->comment('Nième tentative de dispatch pour ce ticket');
            $table->text('motif_annulation')->nullable();

            // Planning P3/P4
            $table->dateTime('date_rdv_prevue')->nullable();
            $table->time('creneau_debut')->nullable();
            $table->time('creneau_fin')->nullable();
            $table->dateTime('date_notification_artisan')->nullable()
                ->comment('Heure d\'envoi du dispatch');
            $table->dateTime('date_confirmation_artisan')->nullable();
            $table->unsignedSmallInteger('delai_confirmation_minutes')->nullable()
                ->comment('SLA P4 : délai entre notification et confirmation');
            $table->string('canal_notification', 20)->nullable()
                ->comment('Canal utilisé : appel, sms, email');

            // Réalisation
            $table->dateTime('date_debut_reelle')->nullable();
            $table->dateTime('date_fin_reelle')->nullable();
            $table->unsignedSmallInteger('duree_reelle_minutes')->nullable();
            $table->text('description_travaux_realises')->nullable();
            $table->text('compte_rendu_artisan')->nullable();

            // Validation client (bon d'intervention signé)
            $table->boolean('signature_client')->default(false);
            $table->dateTime('date_signature_client')->nullable();
            $table->unsignedTinyInteger('satisfaction_immediate')->nullable()
                ->comment('Note immédiate client 1–5 à chaud');

            // Notes
            $table->text('notes_dispatch')->nullable();
            $table->text('notes_intervention')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('statut');
            $table->index('ticket_id');
            $table->index('artisan_id');
            $table->index('date_rdv_prevue');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affaire_interventions');
    }
};
