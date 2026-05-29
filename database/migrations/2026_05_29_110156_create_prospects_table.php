<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ProspectStatut;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prospects', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('type_pressenti')->nullable();
            $table->string('departement', 3)->nullable();
            $table->string('telephone')->nullable();
            $table->string('telephone_alt')->nullable();
            $table->string('email')->nullable();
            $table->text('adresse')->nullable();
            $table->string('code_postal', 5)->nullable();
            $table->string('ville')->nullable();
            $table->string('siret', 14)->nullable();
            $table->string('secteur_activite')->nullable();
            $table->integer('nb_salaries')->nullable();
            $table->decimal('chiffre_affaires', 15, 2)->nullable();
            $table->string('statut')->default(ProspectStatut::AC->value);
            $table->foreignId('teleprospecteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('commercial_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date_premier_contact')->nullable();
            $table->dateTime('rappel_planifie_at')->nullable();
            $table->string('interlocuteur_nom')->nullable();
            $table->string('interlocuteur_fonction')->nullable();
            $table->string('interlocuteur_telephone')->nullable();
            $table->string('interlocuteur_email')->nullable();
            $table->text('description')->nullable();
            $table->text('motif_ko')->nullable();
            $table->boolean('qf_valide')->default(false);
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('qf_valide_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospects');
    }
};
