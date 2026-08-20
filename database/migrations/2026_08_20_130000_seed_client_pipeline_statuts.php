<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    public function up(): void
    {
        $statuses = [
            ['code' => 'prospect', 'label' => 'Prospect', 'couleur' => 'gray', 'ordre' => 10, 'description' => 'Client à traiter ou à qualifier.'],
            ['code' => 'en_cours', 'label' => 'En cours', 'couleur' => 'blue', 'ordre' => 20, 'description' => 'Dossier ou formation en cours.'],
            ['code' => 'termine', 'label' => 'Terminé', 'couleur' => 'green', 'ordre' => 30, 'description' => 'Dossier terminé.'],
            ['code' => 'certifie', 'label' => 'Certifié', 'couleur' => 'purple', 'ordre' => 40, 'description' => 'Dossier terminé et certifié.'],
            ['code' => 'abandonne', 'label' => 'Abandonné', 'couleur' => 'red', 'ordre' => 50, 'description' => 'Dossier abandonné.'],
        ];
        foreach ($statuses as $status) {
            DB::table('pipeline_statuts')->updateOrInsert(
                ['model_type' => 'client', 'code' => $status['code']],
                array_merge($status, ['actif' => true, 'updated_at' => now(), 'created_at' => now()])
            );
        }
    }
    public function down(): void
    {
        DB::table('pipeline_statuts')->where('model_type', 'client')->whereIn('code', ['prospect','en_cours','termine','certifie','abandonne'])->delete();
    }
};
