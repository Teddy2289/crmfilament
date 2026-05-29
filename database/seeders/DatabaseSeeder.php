<?php

// ╔══════════════════════════════════════════════════════════════════╗
// ║  database/seeders/DatabaseSeeder.php                            ║
// ╚══════════════════════════════════════════════════════════════════╝

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Rôles et permissions Spatie — TOUJOURS EN PREMIER
            RolesAndPermissionsSeeder::class,

            // 2. Utilisateurs — après les rôles (dépendance)
            UsersSeeder::class,

            // 3. Diagnostic et correction d'Alex FLOREK
            DiagnosticSeeder::class,
            FixAlexSeeder::class,
        ]);
    }
}
