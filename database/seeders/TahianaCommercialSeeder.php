<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TahianaCommercialSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'tahiana.andriamifidy@ns-conseil.com';

        $user = User::withTrashed()->where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'nom' => 'ANDRIAMIFIDY',
                'prenom' => 'Tahiana',
                'email' => $email,
                'password' => Hash::make('changeme123'),
                'secteur' => null,
                'actif' => true,
                'role_cache' => 'commercial',
            ]);
            $this->command->info("✓ Utilisateur {$email} créé");
        } else {
            if ($user->trashed()) {
                $user->restore();
                $this->command->warn('  → Compte restauré (était soft-deleted)');
            }

            $user->forceFill([
                'nom' => 'ANDRIAMIFIDY',
                'prenom' => 'Tahiana',
                'password' => Hash::make('changeme123'),
                'actif' => true,
                'role_cache' => 'commercial',
            ])->save();

            $this->command->info("✓ Utilisateur {$email} mis à jour");
        }

        $user->syncRoles(['commercial']);

        $this->command->line("  email    : {$email}");
        $this->command->line('  password : changeme123');
        $this->command->line('  rôle     : commercial');
        $this->command->line('  panel    : /ns-conseil');
    }
}
