<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FixAlexSeeder extends Seeder
{
    public function run(): void
    {
        // ── Afficher tous les users existants ──────────────────────
        $this->command->info('=== Users en base ===');
        User::withTrashed()->get()->each(function ($u) {
            $roles = $u->roles->pluck('name')->implode(', ') ?: 'AUCUN';
            $actif = $u->actif ? 'actif' : 'INACTIF';
            $deleted = $u->deleted_at ? ' [SOFT DELETED]' : '';
            $this->command->line("  id:{$u->id} | {$u->email} | actif:{$actif} | rôles:{$roles}{$deleted}");
        });

        $this->command->info('');

        // ── Forcer Alex ────────────────────────────────────────────
        $alex = User::withTrashed()
            ->where('email', 'a.florek@ns-conseil.com')
            ->first();

        if (! $alex) {
            $this->command->warn('Alex introuvable → création...');
            $alex = User::create([
                'nom' => 'FLOREK',
                'prenom' => 'Alex',
                'email' => 'a.florek@ns-conseil.com',
                'password' => Hash::make('changeme123'),
                'secteur' => 'National',
                'actif' => true,
                'role_cache' => 'administrateur',
            ]);
        }

        // Restaurer si soft-deleted
        if ($alex->trashed()) {
            $alex->restore();
            $this->command->warn('  → Compte restauré (était soft-deleted)');
        }

        // Forcer les valeurs
        $alex->forceFill([
            'password' => Hash::make('changeme123'),
            'actif' => true,
            'role_cache' => 'administrateur',
        ])->save();

        $alex->syncRoles(['administrateur']);

        $this->command->info('✓ Alex corrigé');
        $this->command->line('  email    : a.florek@ns-conseil.com');
        $this->command->line('  password : changeme123');
        $this->command->line('  actif    : true');
        $this->command->line('  rôle     : administrateur');
        $this->command->line('  panel    : /ns-conseil');
    }
}
