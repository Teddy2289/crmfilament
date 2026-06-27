<?php

namespace Database\Seeders;

use App\Support\AccessRightsCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (AccessRightsCatalog::allPermissionNames() as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->call([
            CrmProfileSeeder::class,
        ]);

        $this->command->info('Rôles, profils et permissions synchronisés depuis database/seeders/data/crm_profiles.php');
    }
}
