<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DiagnosticSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Vérifier que le rôle team_leader existe ──────────────
        // Il manquait dans RolesAndPermissionsSeeder !
        $rolesManquants = ['team_leader'];
        foreach ($rolesManquants as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $this->command->line("  ✓ Rôle '{$role}' créé/vérifié");
        }

        // ── 2. Tous les rôles présents ──────────────────────────────
        $tousLesRoles = Role::pluck('name')->toArray();
        $this->command->info('Rôles en base : ' . implode(', ', $tousLesRoles));

        // ── 3. Vérifier Alex FLOREK ─────────────────────────────────
        $alex = User::where('email', 'a.florek@ns-conseil.com')->first();

        if (!$alex) {
            $this->command->error('❌ Alex FLOREK introuvable — recréation...');
            $alex = User::create([
                'nom'        => 'FLOREK',
                'prenom'     => 'Alex',
                'email'      => 'a.florek@ns-conseil.com',
                'password'   => Hash::make('changeme123'),
                'secteur'    => 'National',
                'actif'      => true,
                'role_cache' => 'administrateur',
            ]);
        } else {
            $this->command->line("  ✓ Alex trouvé — id: {$alex->id}");
        }

        // ── 4. Forcer le mot de passe ───────────────────────────────
        $alex->update([
            'password'   => Hash::make('changeme123'),
            'actif'      => true,
            'role_cache' => 'administrateur',
        ]);
        $alex->syncRoles(['administrateur']);
        $this->command->line("  ✓ Mot de passe réinitialisé → 'changeme123'");
        $this->command->line("  ✓ Rôle administrateur assigné");

        // ── 5. Résumé de tous les users ─────────────────────────────
        $this->command->info('');
        $this->command->info('=== Utilisateurs en base ===');
        User::all()->each(function ($u) {
            $roles = $u->roles->pluck('name')->implode(', ') ?: 'AUCUN RÔLE';
            $actif = $u->actif ? '✓' : '✗';
            $this->command->line("  [{$actif}] {$u->prenom} {$u->nom} | {$u->email} | {$roles}");
        });
    }
}
