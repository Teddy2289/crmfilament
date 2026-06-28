<?php

namespace Database\Seeders;

use App\Models\CampagnePhoning;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CampagnePhoningSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        CampagnePhoning::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Création des campagnes de phoning...');

        // ── CAMPAGNES PROSPECTS ─────────────────────────────────────────────
        $this->command->info('  → Campagnes Prospects');
        
        CampagnePhoning::create([
            'nom' => 'Prospection CSE Île-de-France',
            'description' => 'Campagne de prospection CSE en Île-de-France (75, 77, 78, 91, 92, 93, 94, 95)',
            'statut' => 'active',
            'type_entite' => 'prospects',
            'criteres' => [
                'statuts' => ['AC', 'STD_NR', 'CSE_NR'],
                'departement' => null,
                'secteur_activite' => null,
                'nb_salaries_min' => null,
                'nb_salaries_max' => null,
                'type_pressenti' => 'CSE',
            ],
            'date_debut' => now()->subDays(7),
            'date_fin' => now()->addDays(30),
            'user_id' => null, // Ouverte à tous les téléprospecteurs
        ]);

        CampagnePhoning::create([
            'nom' => 'Relance Rappels en retard',
            'description' => 'Campagne de relance des prospects avec rappels en retard',
            'statut' => 'active',
            'type_entite' => 'prospects',
            'criteres' => [
                'statuts' => ['AC', 'STD_NR', 'CSE_NR'],
                'departement' => null,
                'secteur_activite' => null,
                'nb_salaries_min' => null,
                'nb_salaries_max' => null,
                'type_pressenti' => null,
            ],
            'date_debut' => now()->subDays(1),
            'date_fin' => now()->addDays(7),
            'user_id' => null,
        ]);

        CampagnePhoning::create([
            'nom' => 'Prospection PME < 50 salariés',
            'description' => 'Campagne ciblée sur les PME de moins de 50 salariés',
            'statut' => 'active',
            'type_entite' => 'prospects',
            'criteres' => [
                'statuts' => ['AC'],
                'departement' => null,
                'secteur_activite' => null,
                'nb_salaries_min' => 1,
                'nb_salaries_max' => 49,
                'type_pressenti' => null,
            ],
            'date_debut' => now()->subDays(3),
            'date_fin' => now()->addDays(21),
            'user_id' => null,
        ]);

        CampagnePhoning::create([
            'nom' => 'Prospection Syndicats',
            'description' => 'Campagne spécifique aux syndicats',
            'statut' => 'active',
            'type_entite' => 'prospects',
            'criteres' => [
                'statuts' => ['AC', 'STD_NR'],
                'departement' => null,
                'secteur_activite' => null,
                'nb_salaries_min' => null,
                'nb_salaries_max' => null,
                'type_pressenti' => 'Syndicat',
            ],
            'date_debut' => now()->subDays(5),
            'date_fin' => now()->addDays(25),
            'user_id' => null,
        ]);

        // ── CAMPAGNES PARTENAIRES ───────────────────────────────────────────
        $this->command->info('  → Campagnes Partenaires');

        CampagnePhoning::create([
            'nom' => 'Suivi Partenaires Actifs',
            'description' => 'Campagne de suivi des partenaires avec statut en cours de prospection',
            'statut' => 'active',
            'type_entite' => 'partenaires',
            'criteres' => [
                'statuts' => ['EnCoursProspection', 'RdvEnCours'],
                'departement' => null,
                'type' => null,
                'secteur_activite' => null,
            ],
            'date_debut' => now()->subDays(10),
            'date_fin' => now()->addDays(20),
            'user_id' => null,
        ]);

        CampagnePhoning::create([
            'nom' => 'Relance Partenaires Île-de-France',
            'description' => 'Campagne de relance partenaires en Île-de-France',
            'statut' => 'active',
            'type_entite' => 'partenaires',
            'criteres' => [
                'statuts' => ['AProspecter', 'EnCoursProspection'],
                'departement' => '75',
                'type' => null,
                'secteur_activite' => null,
            ],
            'date_debut' => now()->subDays(2),
            'date_fin' => now()->addDays(14),
            'user_id' => null,
        ]);

        CampagnePhoning::create([
            'nom' => 'Suivi CSE Signés',
            'description' => 'Campagne de suivi des CSE avec accord cadre signé',
            'statut' => 'active',
            'type_entite' => 'partenaires',
            'criteres' => [
                'statuts' => ['SigneAccordCadre', 'ConventionEngagement'],
                'departement' => null,
                'type' => 'CSE',
                'secteur_activite' => null,
            ],
            'date_debut' => now()->subDays(15),
            'date_fin' => now()->addDays(45),
            'user_id' => null,
        ]);

        CampagnePhoning::create([
            'nom' => 'Prospection Nouveaux Partenaires',
            'description' => 'Campagne de prospection pour nouveaux partenaires',
            'statut' => 'active',
            'type_entite' => 'partenaires',
            'criteres' => [
                'statuts' => ['AProspecter'],
                'departement' => null,
                'type' => null,
                'secteur_activite' => null,
            ],
            'date_debut' => now()->subDays(1),
            'date_fin' => now()->addDays(30),
            'user_id' => null,
        ]);

        // ── CAMPAGNES CLIENTS ─────────────────────────────────────────────────
        $this->command->info('  → Campagnes Clients');

        CampagnePhoning::create([
            'nom' => 'Suivi Clients en cours',
            'description' => 'Campagne de suivi des clients avec formation en cours',
            'statut' => 'active',
            'type_entite' => 'clients',
            'criteres' => [
                'etat' => 'en_cours',
                'departement' => null,
                'type_tiers' => null,
            ],
            'date_debut' => now()->subDays(5),
            'date_fin' => now()->addDays(60),
            'user_id' => null,
        ]);

        CampagnePhoning::create([
            'nom' => 'Relance Clients Prospects',
            'description' => 'Campagne de relance des clients en statut prospect',
            'statut' => 'active',
            'type_entite' => 'clients',
            'criteres' => [
                'etat' => 'prospect',
                'departement' => null,
                'type_tiers' => null,
            ],
            'date_debut' => now()->subDays(3),
            'date_fin' => now()->addDays(15),
            'user_id' => null,
        ]);

        CampagnePhoning::create([
            'nom' => 'Suivi Clients Île-de-France',
            'description' => 'Campagne de suivi clients en Île-de-France',
            'statut' => 'active',
            'type_entite' => 'clients',
            'criteres' => [
                'etat' => null,
                'departement' => '75',
                'type_tiers' => null,
            ],
            'date_debut' => now()->subDays(7),
            'date_fin' => now()->addDays(30),
            'user_id' => null,
        ]);

        CampagnePhoning::create([
            'nom' => 'Relance Clients Terminés',
            'description' => 'Campagne de relance des clients avec formation terminée',
            'statut' => 'active',
            'type_entite' => 'clients',
            'criteres' => [
                'etat' => 'termine',
                'departement' => null,
                'type_tiers' => null,
            ],
            'date_debut' => now()->subDays(10),
            'date_fin' => now()->addDays(20),
            'user_id' => null,
        ]);

        $this->command->info('Campagnes créées avec succès : 12 campagnes (4 prospects, 4 partenaires, 4 clients)');
    }
}
