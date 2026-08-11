<?php

namespace Database\Seeders;

use App\Models\CrmProfile;
use App\Support\AccessRightsCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CrmProfileSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = require database_path('seeders/data/crm_profiles.php');

        foreach ($profiles as $profile) {
            CrmProfile::updateOrCreate(
                ['role_name' => $profile['role_name']],
                collect($profile)->except(['permissions', 'groupe'])->merge([
                    'actif' => $profile['actif'] ?? true,
                ])->toArray()
            );

            $this->syncRolePermissions($profile);
        }
    }

    private function syncRolePermissions(array $profile): void
    {
        AccessRightsCatalog::ensurePermissionsExist();

        $role = Role::firstOrCreate([
            'name' => $profile['role_name'],
            'guard_name' => 'web',
        ]);

        $perms = $profile['permissions'] ?? [];

        if ($perms === '*') {
            $role->syncPermissions(AccessRightsCatalog::allPermissionNames());

            return;
        }

        if ($perms === 'allopro') {
            $role->syncPermissions(AccessRightsCatalog::permissionNamesForPanel('allopro'));

            return;
        }

        if (is_array($perms)) {
            foreach ($perms as $perm) {
                Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            }
            $role->syncPermissions($perms);
        }
    }
}
