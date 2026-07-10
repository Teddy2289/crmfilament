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

        // ── Forcer Alexandre ────────────────────────────────────────────
        $Alexandre = User::withTrashed()
            ->where('email', 'a.florek@ns-conseil.com')
            ->first();

        if (! $Alexandre) {
            $this->command->warn('Alexandre introuvable → création...');
            $Alexandre = User::create([
                'nom' => 'FLOREK',
                'prenom' => 'Alexandre',
                'email' => 'a.florek@ns-conseil.com',
                'password' => Hash::make('changeme123'),
                'secteur' => 'National',
                'actif' => true,
                'role_cache' => 'administrateur',
            ]);
        }

        // Restaurer si soft-deleted
        if ($Alexandre->trashed()) {
            $Alexandre->restore();
            $this->command->warn('  → Compte restauré (était soft-deleted)');
        }

        // Forcer les valeurs
        $Alexandre->forceFill([
            'password' => Hash::make('changeme123'),
            'actif' => true,
            'role_cache' => 'administrateur',
        ])->save();

        $Alexandre->syncRoles(['administrateur']);

        $this->command->info('✓ Alexandre corrigé');
        $this->command->line('  email    : a.florek@ns-conseil.com');
        $this->command->line('  password : changeme123');
        $this->command->line('  actif    : true');
        $this->command->line('  rôle     : administrateur');
        $this->command->line('  panel    : /ns-conseil');
    }
}
