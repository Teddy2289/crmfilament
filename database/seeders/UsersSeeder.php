<?php

// ╔══════════════════════════════════════════════════════════════════╗
// ║  database/seeders/UsersSeeder.php                               ║
// ╚══════════════════════════════════════════════════════════════════╝

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // ── NS CONSEIL ─────────────────────────────────────────
            [
                'nom'     => 'FLOREK',
                'prenom'  => 'Alex',
                'email'   => 'a.florek@ns-conseil.com',
                'secteur' => 'National',
                'role'    => 'administrateur',
            ],
            [
                'nom'     => 'DURAND',
                'prenom'  => 'Bruno',
                'email'   => 'b.durand@ns-conseil.com',
                'secteur' => 'Île-de-France',
                'role'    => 'team_leader',
            ],
            [
                'nom'     => 'MARTIN',
                'prenom'  => 'Nina',
                'email'   => 'n.martin@ns-conseil.com',
                'secteur' => 'Nord-Est',
                'role'    => 'commercial',
            ],
            [
                'nom'     => 'RAKOTO',
                'prenom'  => 'Ranto',
                'email'   => 'r.rakoto@ns-conseil.com',
                'secteur' => 'Sud-Ouest',
                'role'    => 'commercial',
            ],

            // ── AlloPro 24/24 ──────────────────────────────────────
            [
                'nom'     => 'DUPONT',
                'prenom'  => 'Sophie',
                'email'   => 's.dupont@allopro.fr',
                'secteur' => null,
                'role'    => 'responsable_plateau',
            ],
            [
                'nom'     => 'LEFEBVRE',
                'prenom'  => 'Marc',
                'email'   => 'm.lefebvre@allopro.fr',
                'secteur' => null,
                'role'    => 'operateur_n1',
            ],
            [
                'nom'     => 'BERNARD',
                'prenom'  => 'Claire',
                'email'   => 'c.bernard@allopro.fr',
                'secteur' => null,
                'role'    => 'back_office',
            ],
            [
                'nom'     => 'NGUYEN',
                'prenom'  => 'Linh',
                'email'   => 'l.nguyen@allopro.fr',
                'secteur' => null,
                'role'    => 'operateur_n1',
            ],
            [
                'nom'     => 'MOREAU',
                'prenom'  => 'Julien',
                'email'   => 'j.moreau@ns-conseil.com',
                'secteur' => 'Ouest',
                'role'    => 'teleprospecteur',
            ],
        ];

        foreach ($users as $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'nom'        => $u['nom'],
                    'prenom'     => $u['prenom'],
                    'secteur'    => $u['secteur'],
                    'password'   => Hash::make('changeme123'),
                    'actif'      => true,
                    'role_cache' => $u['role'],
                ]
            );

            // Spatie : assigner le rôle
            $user->syncRoles([$u['role']]);

            $this->command->line("  ✓ {$u['prenom']} {$u['nom']} ({$u['role']})");
        }

        $this->command->info('Utilisateurs créés avec succès.');
    }
}
