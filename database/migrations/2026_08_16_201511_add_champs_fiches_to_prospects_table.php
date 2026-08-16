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
        Schema::table('prospects', function (Blueprint $table) {
            // Champs pour fiche bleue (RDV confirmé)
            $table->text('besoins_exprimes')->nullable()->after('description');
            $table->text('objections_soulevees')->nullable()->after('besoins_exprimes');
            $table->text('points_attention_rdv')->nullable()->after('objections_soulevees');
            $table->boolean('invitation_agenda_envoyee')->default(false)->after('points_attention_rdv');
            
            // Champs pour fiche verte (RDV à conclure)
            $table->string('presence_cse')->nullable()->after('invitation_agenda_envoyee');
            $table->string('jour_dispo_appel')->nullable()->after('presence_cse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn([
                'besoins_exprimes',
                'objections_soulevees',
                'points_attention_rdv',
                'invitation_agenda_envoyee',
                'presence_cse',
                'jour_dispo_appel',
            ]);
        });
    }
};
