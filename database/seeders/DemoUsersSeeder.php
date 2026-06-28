<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Rôles définis dans User::ROLES
        $roles = [
            'super_admin',
            'administrateur',
            'commercial',
            'teleprospecteur',
            'operateur_n1',
            'back_office',
            'responsable_plateau',
        ];

        // Prénoms et noms pour générer des utilisateurs
        $prenoms = ['Jean', 'Marie', 'Pierre', 'Sophie', 'Luc', 'Claire', 'Thomas', 'Emma', 'Nicolas', 'Julie', 'Antoine', 'Camille', 'Maxime', 'Sarah', 'Hugo', 'Léa', 'Louis', 'Manon', 'Alexandre', 'Chloé', 'Mathieu', 'Pauline'];
        $noms = ['Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand', 'Leroy', 'Moreau', 'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David', 'Bertrand', 'Roux', 'Fournier', 'Guérin'];

        $userCount = 0;

        foreach ($roles as $role) {
            $this->command->info("Création des utilisateurs pour le rôle: {$role}");

            for ($i = 1; $i <= 3; $i++) {
                $prenom = $prenoms[$userCount % count($prenoms)];
                $nom = $noms[$userCount % count($noms)];
                $userCount++;

                $email = strtolower("{$prenom}.{$nom}.{$i}@demo-crm.local");
                $password = 'demo123';

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'nom' => strtoupper($nom),
                        'prenom' => ucfirst($prenom),
                        'secteur' => $this->getSecteurForRole($role),
                        'password' => Hash::make($password),
                        'actif' => true,
                        'role_cache' => $role,
                    ]
                );

                // Spatie : assigner le rôle
                $user->syncRoles([$role]);

                $this->command->line("  ✓ {$email} | {$password}");
            }
        }

        $this->command->info('Utilisateurs de démo créés avec succès.');
    }

    private function getSecteurForRole(string $role): ?string
    {
        return match ($role) {
            'commercial', 'teleprospecteur' => ['nord', 'sud', 'est', 'ouest', 'idf', 'national'][array_rand(['nord', 'sud', 'est', 'ouest', 'idf', 'national'])],
            default => null,
        };
    }
}
