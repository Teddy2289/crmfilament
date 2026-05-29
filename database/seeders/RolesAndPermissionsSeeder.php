<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Permissions NS CONSEIL ─────────────────────────────────
        $permissionsNS = [
            'partenaires.view_any',
            'partenaires.view',
            'partenaires.create',
            'partenaires.update',
            'partenaires.delete',
            'prospects.view_any',
            'prospects.view',
            'prospects.create',
            'prospects.update',
            'prospects.valider_qf',
            'clients.view_any',
            'clients.view',
            'clients.create',
            'clients.update',
            'activites.create',
            'activites.update',
            'rapports.view',
            'rapports.export',
        ];

        // ── Permissions AlloPro ────────────────────────────────────
        $permissionsAP = [
            'tickets.create',
            'tickets.view',
            'tickets.update_statut',
            'fiche_p2.create',
            'fiche_p2.view',
            'fiche_p2.update',
            'artisans.view',
            'artisans.update',
            'reclamations.view',
            'reclamations.create',
            'reclamations.valider',
            'rapports_satisfaction.create',
            'prospection_artisans.create',
            'prospection_artisans.update',
            'dashboard.temps_reel',
        ];

        foreach (array_merge($permissionsNS, $permissionsAP) as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ── Rôles NS CONSEIL ───────────────────────────────────────
        $admin = Role::firstOrCreate(['name' => 'administrateur', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $teamLeader = Role::firstOrCreate(['name' => 'team_leader', 'guard_name' => 'web']); // ← AJOUTÉ
        $teamLeader->syncPermissions([
            'partenaires.view_any',
            'partenaires.view',
            'partenaires.create',
            'partenaires.update',
            'prospects.view_any',
            'prospects.view',
            'prospects.create',
            'prospects.update',
            'prospects.valider_qf',
            'clients.view_any',
            'clients.view',
            'activites.create',
            'activites.update',
            'rapports.view',
            'rapports.export',
        ]);

        $teleprospecteur = Role::firstOrCreate(['name' => 'teleprospecteur', 'guard_name' => 'web']);
        $teleprospecteur->syncPermissions([
            'partenaires.view_any',
            'prospects.view_any',
            'prospects.view',
            'prospects.create',
            'prospects.update',
            'activites.create',
            'prospection_artisans.create',
            'prospection_artisans.update',
        ]);

        $commercial = Role::firstOrCreate(['name' => 'commercial', 'guard_name' => 'web']);
        $commercial->syncPermissions([
            'partenaires.view_any',
            'partenaires.view',
            'partenaires.update',
            'prospects.view',
            'clients.view',
            'clients.update',
            'activites.create',
            'activites.update',
        ]);

        // ── Rôles AlloPro ──────────────────────────────────────────
        $operateurN1 = Role::firstOrCreate(['name' => 'operateur_n1', 'guard_name' => 'web']);
        $operateurN1->syncPermissions([
            'tickets.create',
            'tickets.view',
            'tickets.update_statut',
            'fiche_p2.create',
            'fiche_p2.view',
            'fiche_p2.update',
            'artisans.view',
        ]);

        $backOffice = Role::firstOrCreate(['name' => 'back_office', 'guard_name' => 'web']);
        $backOffice->syncPermissions([
            'tickets.view',
            'tickets.update_statut',
            'fiche_p2.view',
            'artisans.view',
            'artisans.update',
            'reclamations.view',
            'reclamations.create',
            'rapports_satisfaction.create',
        ]);

        $responsablePlateau = Role::firstOrCreate(['name' => 'responsable_plateau', 'guard_name' => 'web']);
        $responsablePlateau->syncPermissions(
            Permission::whereIn('name', $permissionsAP)->get()
        );

        $this->command->info('Rôles et permissions créés avec succès.');
        $this->command->table(
            ['Rôle', 'Nb permissions'],
            Role::with('permissions')->get()->map(fn($r) => [
                $r->name,
                $r->permissions->count(),
            ])->toArray()
        );
    }
}
