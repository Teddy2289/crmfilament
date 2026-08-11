<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_partenaires', function (Blueprint $table) {

            // ── Rôle discriminant ─────────────────────────────────────────
            // Permet de retrouver directement Secrétaire / Trésorier / DS
            // sans parser la colonne `fonction` (texte libre)
            if (! Schema::hasColumn('contact_partenaires', 'role')) {
                $table->enum('role', ['SECRETAIRE', 'TRESORIER', 'SYNDICAT_DS', 'AUTRE'])
                    ->default('AUTRE')
                    ->after('fonction')
                    ->comment('Rôle structuré issu des blocs distincts du fichier Excel');
            }

            // ── Syndicat associé au DS ────────────────────────────────────
            if (! Schema::hasColumn('contact_partenaires', 'nom_syndicat')) {
                $table->string('nom_syndicat')
                    ->nullable()
                    ->after('role')
                    ->comment('Nom du syndicat — renseigné uniquement si role = SYNDICAT_DS');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contact_partenaires', function (Blueprint $table) {
            $table->dropColumn(['role', 'nom_syndicat']);
        });
    }
};
