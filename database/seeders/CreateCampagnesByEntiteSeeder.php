<?php

namespace Database\Seeders;

use App\Models\CampagnePhoning;
use App\Models\EntiteCommerciale;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreateCampagnesByEntiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $entites = EntiteCommerciale::all();

        foreach ($entites as $entite) {
            // Campagne 1: Prospects
            CampagnePhoning::create([
                'nom' => "Campagne Prospects - {$entite->nom}",
                'description' => "Campagne de phoning pour les prospects de l'entité {$entite->nom}",
                'statut' => 'brouillon',
                'type_entite' => 'prospects',
                'criteres' => [
                    'statuts' => ['a_prosperer', 'en_cours'],
                    'nb_salaries_min' => 10,
                ],
                'date_debut' => now()->addDays(7),
                'date_fin' => now()->addDays(30),
                'entite_id' => $entite->id,
            ]);

            // Campagne 2: Partenaires
            CampagnePhoning::create([
                'nom' => "Campagne Partenaires - {$entite->nom}",
                'description' => "Campagne de phoning pour les partenaires de l'entité {$entite->nom}",
                'statut' => 'brouillon',
                'type_entite' => 'partenaires',
                'criteres' => [
                    'statuts' => ['a_prosperer', 'en_cours'],
                    'type' => 'CSE',
                ],
                'date_debut' => now()->addDays(7),
                'date_fin' => now()->addDays(30),
                'entite_id' => $entite->id,
            ]);
        }

        $this->command->info('Campagnes créées avec succès pour chaque entité.');
    }
}
