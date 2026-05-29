<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\StatutCampagneProspection;
use App\Enums\PrioriteSegment;
use App\Enums\CorpsDeMetier;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('artisan_prospections', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('corps_de_metier');
            $table->string('telephone', 20);
            $table->text('zone_geo');
            $table->string('statut_campagne')->default(StatutCampagneProspection::AC->value);
            $table->dateTime('date_dernier_contact')->nullable();
            $table->foreignId('teleprospecteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('priorite_segment')->default(PrioriteSegment::Standard->value);
            $table->boolean('accord_verbal')->default(false);
            $table->dateTime('date_envoi_document')->nullable();
            $table->foreignId('artisan_id')->nullable()
                ->constrained('artisans')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('statut_campagne');
            $table->index('priorite_segment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artisan_prospections');
    }
};
