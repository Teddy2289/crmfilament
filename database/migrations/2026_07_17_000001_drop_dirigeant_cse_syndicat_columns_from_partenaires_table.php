<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $columns = [
        'dirigeant_nom',
        'dirigeant_prenom',
        'dirigeant_fonction',
        'dirigeant_telephone',
        'dirigeant_email',
        'cse_secretaire_nom',
        'cse_secretaire_prenom',
        'cse_secretaire_tel_direct',
        'cse_secretaire_tel_perso',
        'cse_secretaire_email_pro',
        'cse_secretaire_email_perso',
        'cse_tresorier_nom',
        'cse_tresorier_prenom',
        'cse_tresorier_tel_direct',
        'cse_tresorier_tel_perso',
        'cse_tresorier_email_pro',
        'cse_tresorier_email_perso',
        'cse_nb_elus',
        'cse_date_fin_mandat',
        'cse_existence_juridique',
        'cse_notes',
        'syndicat_appartenance',
        'syndicat_nom_organisation',
        'syndicat_responsable_nom',
        'syndicat_responsable_prenom',
        'syndicat_responsable_fonction',
        'syndicat_tel_direct',
        'syndicat_tel_perso',
        'syndicat_email_pro',
        'syndicat_email_perso',
        'syndicat_perimetre',
        'syndicat_notes',
    ];

    public function up(): void
    {
        Schema::table('partenaires', function (Blueprint $table) {
            $existing = array_filter($this->columns, fn (string $c) => Schema::hasColumn('partenaires', $c));
            if (! empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }

    public function down(): void
    {
        Schema::table('partenaires', function (Blueprint $table) {
            $table->string('dirigeant_nom')->nullable();
            $table->string('dirigeant_prenom')->nullable();
            $table->string('dirigeant_fonction')->nullable();
            $table->string('dirigeant_telephone')->nullable();
            $table->string('dirigeant_email')->nullable();
            $table->string('cse_secretaire_nom')->nullable();
            $table->string('cse_secretaire_prenom')->nullable();
            $table->string('cse_secretaire_tel_direct')->nullable();
            $table->string('cse_secretaire_tel_perso')->nullable();
            $table->string('cse_secretaire_email_pro')->nullable();
            $table->string('cse_secretaire_email_perso')->nullable();
            $table->string('cse_tresorier_nom')->nullable();
            $table->string('cse_tresorier_prenom')->nullable();
            $table->string('cse_tresorier_tel_direct')->nullable();
            $table->string('cse_tresorier_tel_perso')->nullable();
            $table->string('cse_tresorier_email_pro')->nullable();
            $table->string('cse_tresorier_email_perso')->nullable();
            $table->integer('cse_nb_elus')->nullable();
            $table->date('cse_date_fin_mandat')->nullable();
            $table->boolean('cse_existence_juridique')->default(false);
            $table->text('cse_notes')->nullable();
            $table->string('syndicat_appartenance')->nullable();
            $table->string('syndicat_nom_organisation')->nullable();
            $table->string('syndicat_responsable_nom')->nullable();
            $table->string('syndicat_responsable_prenom')->nullable();
            $table->string('syndicat_responsable_fonction')->nullable();
            $table->string('syndicat_tel_direct')->nullable();
            $table->string('syndicat_tel_perso')->nullable();
            $table->string('syndicat_email_pro')->nullable();
            $table->string('syndicat_email_perso')->nullable();
            $table->text('syndicat_perimetre')->nullable();
            $table->text('syndicat_notes')->nullable();
        });
    }
};
