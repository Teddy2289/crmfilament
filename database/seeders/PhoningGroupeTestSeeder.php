<?php

// ╔══════════════════════════════════════════════════════════════════╗
// ║  database/seeders/PhoningGroupeTestSeeder.php                    ║
// ║  Données de test pour la fonctionnalité "campagnes assignées à   ║
// ║  un groupe de télépros" : 2 groupes, 2 télépros par groupe, 4    ║
// ║  campagnes actives (2 départements par groupe) + prospects       ║
// ║  appelables associés.                                            ║
// ║                                                                   ║
// ║  Lancer isolément :                                               ║
// ║  php artisan db:seed --class=PhoningGroupeTestSeeder             ║
// ╚══════════════════════════════════════════════════════════════════╝

namespace Database\Seeders;

use App\Models\CampagnePhoning;
use App\Models\GroupeTelepro;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PhoningGroupeTestSeeder extends Seeder
{
    public function run(): void
    {
        $groupes = [
            'Groupe 44-45' => [
                'departements' => ['44', '45'],
                'telepros' => [
                    ['prenom' => 'Amel', 'nom' => 'BENALI', 'email' => 'a.benali@ns-conseil.com'],
                    ['prenom' => 'Karim', 'nom' => 'SAIDI', 'email' => 'k.saidi@ns-conseil.com'],
                ],
            ],
            'Groupe 60-80' => [
                'departements' => ['60', '80'],
                'telepros' => [
                    ['prenom' => 'Lucie', 'nom' => 'PETIT', 'email' => 'l.petit@ns-conseil.com'],
                    ['prenom' => 'Hugo', 'nom' => 'ROUX', 'email' => 'h.roux@ns-conseil.com'],
                ],
            ],
        ];

        foreach ($groupes as $nomGroupe => $config) {
            $groupe = GroupeTelepro::updateOrCreate(
                ['nom' => $nomGroupe],
                ['actif' => true]
            );

            $this->command->info("Groupe « {$nomGroupe} »");

            foreach ($config['telepros'] as $t) {
                $user = User::firstOrCreate(
                    ['email' => $t['email']],
                    [
                        'nom' => $t['nom'],
                        'prenom' => $t['prenom'],
                        'password' => Hash::make('changeme123'),
                        'actif' => true,
                        'role_cache' => 'teleprospecteur',
                    ]
                );

                $user->update(['groupe_telepro_id' => $groupe->id]);
                $user->syncRoles(['teleprospecteur']);

                $this->command->line("  ✓ Télépro {$t['prenom']} {$t['nom']} ({$t['email']} / changeme123)");
            }

            foreach ($config['departements'] as $departement) {
                CampagnePhoning::updateOrCreate(
                    ['nom' => "Prospection département {$departement}"],
                    [
                        'description' => "Campagne de test — département {$departement} ({$nomGroupe})",
                        'statut' => 'active',
                        'type_entite' => 'prospects',
                        'criteres' => [
                            'statuts' => ['AC'],
                            'departement' => $departement,
                        ],
                        'date_debut' => now()->subDay(),
                        'date_fin' => now()->addDays(30),
                        'user_id' => null,
                        'groupe_telepro_id' => $groupe->id,
                    ]
                );

                $this->command->line("  ✓ Campagne « Prospection département {$departement} » → {$nomGroupe}");

                if (Prospect::where('departement', $departement)->where('statut', 'AC')->count() < 3) {
                    Prospect::factory()->count(3)->create([
                        'departement' => $departement,
                        'statut' => 'AC',
                        'teleprospecteur_id' => null,
                        'commercial_id' => null,
                    ]);
                }
            }
        }

        $this->command->info('Groupes, télépros et campagnes de test créés (mot de passe des télépros : changeme123).');
    }
}
