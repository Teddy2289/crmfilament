<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class TestRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer les rôles de base pour les tests (doivent correspondre aux constantes User::ROLES)
        $roles = [
            'administrateur',
            'teleprospecteur',
            'commercial',
            'operateur_n1',
            'back_office',
            'responsable_plateau',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }
}
