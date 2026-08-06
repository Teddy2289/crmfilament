<?php

namespace Database\Seeders;

use App\Support\AccessRightsCatalog;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;

class RdvAssociationsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure permissions exist
        AccessRightsCatalog::ensurePermissionsExist();

        $permissions = [
            'rdv_association.view_any',
            'rdv_association.view',
        ];

        // Assign to roles if they exist
        $roles = Role::whereIn('name', [
            'administrateur',
            'team_leader',
            'teleprospecteur', // "telepro"
            'back_office',     // "agent back office"
            'responsable_plateau', // superviseur/manager
        ])->get();

        foreach ($roles as $role) {
            AccessRightsCatalog::syncSelectiveAccess($role, $permissions);
        }
    }
}
