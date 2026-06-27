<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Partenaire;
use App\Models\Prospect;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class AccessRightsCatalog
{
    /**
     * @var array<int|string, array<int, string>>
     */
    private static array $userPermissionCache = [];

    /**
     * Source of truth for module/entity permissions exposed in Super Admin.
     *
     * @return array<string, array{label: string, panel: string, permissions: array<string, string>}>
     */
    public static function modules(): array
    {
        return [
            'prospects' => [
                'label' => 'AOPIA - Prospects',
                'panel' => 'ns-conseil',
                'permissions' => [
                    'prospects.view_any' => 'Lister',
                    'prospects.view' => 'Voir',
                    'prospects.create' => 'Creer',
                    'prospects.update' => 'Modifier',
                    'prospects.valider_qf' => 'Valider QF',
                ],
            ],
            'partenaires' => [
                'label' => 'AOPIA - Partenaires',
                'panel' => 'ns-conseil',
                'permissions' => [
                    'partenaires.view_any' => 'Lister',
                    'partenaires.view' => 'Voir',
                    'partenaires.create' => 'Creer',
                    'partenaires.update' => 'Modifier',
                    'partenaires.delete' => 'Supprimer',
                ],
            ],
            'clients' => [
                'label' => 'AOPIA - Clients',
                'panel' => 'ns-conseil',
                'permissions' => [
                    'clients.view_any' => 'Lister',
                    'clients.view' => 'Voir',
                    'clients.create' => 'Creer',
                    'clients.update' => 'Modifier',
                ],
            ],
            'activites' => [
                'label' => 'AOPIA - Activites',
                'panel' => 'ns-conseil',
                'permissions' => [
                    'activites.create' => 'Creer',
                    'activites.update' => 'Modifier',
                ],
            ],
            'rapports' => [
                'label' => 'AOPIA - Rapports',
                'panel' => 'ns-conseil',
                'permissions' => [
                    'rapports.view' => 'Voir',
                    'rapports.export' => 'Exporter',
                ],
            ],
            'tickets' => [
                'label' => 'AlloPro - Tickets',
                'panel' => 'allopro',
                'permissions' => [
                    'tickets.create' => 'Creer',
                    'tickets.view' => 'Voir',
                    'tickets.update_statut' => 'Modifier le statut',
                ],
            ],
            'fiche_p2' => [
                'label' => 'AlloPro - Fiche P2',
                'panel' => 'allopro',
                'permissions' => [
                    'fiche_p2.create' => 'Creer',
                    'fiche_p2.view' => 'Voir',
                    'fiche_p2.update' => 'Modifier',
                ],
            ],
            'artisans' => [
                'label' => 'AlloPro - Artisans',
                'panel' => 'allopro',
                'permissions' => [
                    'artisans.view' => 'Voir',
                    'artisans.update' => 'Modifier',
                ],
            ],
            'reclamations' => [
                'label' => 'AlloPro - Reclamations',
                'panel' => 'allopro',
                'permissions' => [
                    'reclamations.view' => 'Voir',
                    'reclamations.create' => 'Creer',
                    'reclamations.valider' => 'Valider',
                ],
            ],
            'rapports_satisfaction' => [
                'label' => 'AlloPro - Satisfaction',
                'panel' => 'allopro',
                'permissions' => [
                    'rapports_satisfaction.create' => 'Creer',
                ],
            ],
            'prospection_artisans' => [
                'label' => 'AlloPro - Prospection artisans',
                'panel' => 'allopro',
                'permissions' => [
                    'prospection_artisans.create' => 'Creer',
                    'prospection_artisans.update' => 'Modifier',
                ],
            ],
            'dashboard' => [
                'label' => 'AlloPro - Temps reel',
                'panel' => 'allopro',
                'permissions' => [
                    'dashboard.temps_reel' => 'Voir dashboard temps reel',
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function fieldActions(): array
    {
        return [
            'show' => 'Voir',
            'create' => 'Creer',
            'edit' => 'Modifier',
            'flux' => 'Flux',
            'all' => 'Tout',
        ];
    }

    /**
     * Source of truth for field-level permissions exposed in Super Admin.
     *
     * @return array<string, array{label: string, panel: string, fields: array<string, string>}>
     */
    public static function fieldModules(): array
    {
        return [
            'prospects' => [
                'label' => 'AOPIA - Prospects',
                'panel' => 'ns-conseil',
                'fields' => static::fieldLabelsForModel(Prospect::class, [
                    'nom' => 'Nom',
                    'type_pressenti' => 'Type pressenti',
                    'telephone' => 'Telephone',
                    'telephone_alt' => 'Telephone alternatif',
                    'email' => 'Email',
                    'adresse' => 'Adresse',
                    'code_postal' => 'Code postal',
                    'ville' => 'Ville',
                    'siret' => 'SIRET',
                    'statut' => 'Statut',
                    'teleprospecteur_id' => 'Teleprospecteur',
                    'commercial_id' => 'Commercial',
                    'interlocuteur_nom' => 'Interlocuteur',
                    'interlocuteur_email' => 'Email interlocuteur',
                    'description' => 'Description',
                ]),
            ],
            'partenaires' => [
                'label' => 'AOPIA - Partenaires',
                'panel' => 'ns-conseil',
                'fields' => static::fieldLabelsForModel(Partenaire::class, [
                    'nom' => 'Nom',
                    'entreprise' => 'Entreprise',
                    'nom_retenu' => 'Nom retenu',
                    'siret' => 'SIRET',
                    'type' => 'Type',
                    'telephone' => 'Telephone',
                    'email' => 'Email',
                    'adresse' => 'Adresse',
                    'code_postal' => 'Code postal',
                    'ville' => 'Ville',
                    'statut' => 'Statut',
                    'commercial_id' => 'Commercial',
                    'conseiller_id' => 'Conseiller',
                    'date_signature' => 'Date signature',
                    'date_convention' => 'Date convention',
                    'notes' => 'Notes internes',
                    'commentaires' => 'Commentaires',
                ]),
            ],
            'clients' => [
                'label' => 'AOPIA - Clients',
                'panel' => 'ns-conseil',
                'fields' => static::fieldLabelsForModel(Client::class, [
                    'ref_client' => 'Reference client',
                    'civilite' => 'Civilite',
                    'nom_tiers' => 'Nom',
                    'email' => 'Email',
                    'telephone' => 'Telephone',
                    'adresse' => 'Adresse',
                    'code_postal' => 'Code postal',
                    'ville' => 'Ville',
                    'date_naissance' => 'Date de naissance',
                    'entreprise' => 'Entreprise',
                    'etat' => 'Etat',
                    'montant_cpf' => 'Montant CPF',
                    'ne_plus_contacter' => 'Ne plus contacter',
                    'partenaire_id' => 'Partenaire',
                    'notes_commerciales' => 'Notes commerciales',
                ]),
            ],
            'tickets' => [
                'label' => 'AlloPro - Tickets',
                'panel' => 'allopro',
                'fields' => static::fieldLabelsForModel(Ticket::class, [
                    'reference' => 'Reference',
                    'contact_particulier_id' => 'Contact particulier',
                    'artisan_id' => 'Artisan',
                    'operateur_id' => 'Operateur',
                    'statut' => 'Statut',
                    'niveau_priorite' => 'Niveau priorite',
                    'corps_de_metier' => 'Corps de metier',
                    'rdv_planifie_at' => 'RDV planifie',
                    'rappel_promise_at' => 'Rappel promis',
                    'aircall_call_id' => 'Aircall call ID',
                    'source_appel' => 'Source appel',
                    'notes' => 'Notes',
                ]),
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function groupedPermissionOptions(): array
    {
        return collect(static::modules())
            ->mapWithKeys(fn (array $module) => [$module['label'] => $module['permissions']])
            ->toArray();
    }

    /**
     * @return array<string, string>
     */
    public static function permissionOptions(): array
    {
        $options = [];

        foreach (static::modules() as $module) {
            foreach ($module['permissions'] as $permission => $label) {
                $options[$permission] = "{$module['label']} - {$label}";
            }
        }

        return $options;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function groupedFieldPermissionOptions(): array
    {
        return collect(static::fieldModules())
            ->mapWithKeys(function (array $module, string $entity) {
                $options = [];

                foreach ($module['fields'] as $field => $fieldLabel) {
                    foreach (static::fieldActions() as $action => $actionLabel) {
                        $options[static::fieldPermissionName($entity, $field, $action)] = "{$fieldLabel} - {$actionLabel}";
                    }
                }

                return [$module['label'] => $options];
            })
            ->toArray();
    }

    /**
     * @return array<string, string>
     */
    public static function fieldPermissionOptions(): array
    {
        $options = [];

        foreach (static::fieldModules() as $entity => $module) {
            foreach ($module['fields'] as $field => $fieldLabel) {
                foreach (static::fieldActions() as $action => $actionLabel) {
                    $permission = static::fieldPermissionName($entity, $field, $action);
                    $options[$permission] = "{$module['label']} - {$fieldLabel} - {$actionLabel}";
                }
            }
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public static function permissionDescriptions(): array
    {
        $descriptions = [];

        foreach (static::modules() as $module) {
            foreach (array_keys($module['permissions']) as $permission) {
                $descriptions[$permission] = $module['label'];
            }
        }

        return $descriptions;
    }

    /**
     * @return array<string, string>
     */
    public static function fieldPermissionDescriptions(): array
    {
        $descriptions = [];

        foreach (static::fieldModules() as $entity => $module) {
            foreach ($module['fields'] as $field => $fieldLabel) {
                foreach (array_keys(static::fieldActions()) as $action) {
                    $descriptions[static::fieldPermissionName($entity, $field, $action)] = "{$module['label']} / {$fieldLabel}";
                }
            }
        }

        return $descriptions;
    }

    /**
     * @return array<int, string>
     */
    public static function modulePermissionNames(): array
    {
        return collect(static::modules())
            ->flatMap(fn (array $module) => array_keys($module['permissions']))
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * @return array<int, string>
     */
    public static function allFieldPermissionNames(): array
    {
        return collect(static::fieldPermissionOptions())
            ->keys()
            ->values()
            ->toArray();
    }

    /**
     * @return array<int, string>
     */
    public static function allPermissionNames(): array
    {
        return collect(static::modulePermissionNames())
            ->merge(static::allFieldPermissionNames())
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * @return array<int, string>
     */
    public static function permissionNamesForPanel(string $panel): array
    {
        $modulePermissions = collect(static::modules())
            ->filter(fn (array $module) => $module['panel'] === $panel)
            ->flatMap(fn (array $module) => array_keys($module['permissions']))
            ->values();

        $fieldPermissions = collect(static::fieldModules())
            ->filter(fn (array $module) => $module['panel'] === $panel)
            ->flatMap(function (array $module, string $entity) {
                $permissions = [];

                foreach (array_keys($module['fields']) as $field) {
                    foreach (array_keys(static::fieldActions()) as $action) {
                        $permissions[] = static::fieldPermissionName($entity, $field, $action);
                    }
                }

                return $permissions;
            })
            ->values();

        return $modulePermissions
            ->merge($fieldPermissions)
            ->unique()
            ->values()
            ->toArray();
    }

    public static function hasPermission(string $permission): bool
    {
        return in_array($permission, static::allPermissionNames(), true);
    }

    public static function ensurePermissionsExist(): void
    {
        foreach (static::allPermissionNames() as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    public static function syncFullAccess(Role $role): void
    {
        static::ensurePermissionsExist();
        $role->syncPermissions(static::allPermissionNames());
        static::$userPermissionCache = [];
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public static function syncSelectiveAccess(Role $role, array $permissions): void
    {
        static::ensurePermissionsExist();

        $role->syncPermissions(
            collect($permissions)
                ->intersect(static::allPermissionNames())
                ->values()
                ->all()
        );

        static::$userPermissionCache = [];
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function roleHasFullAccess(?Role $role): bool
    {
        if (! $role) {
            return false;
        }

        $catalogPermissions = static::allPermissionNames();

        if ($catalogPermissions === []) {
            return false;
        }

        $rolePermissions = $role->permissions->pluck('name')->all();

        return collect($catalogPermissions)->diff($rolePermissions)->isEmpty();
    }

    /**
     * @return array<int, string>
     */
    public static function roleModulePermissionNames(?Role $role): array
    {
        return static::rolePermissionNames($role, static::modulePermissionNames());
    }

    /**
     * @return array<int, string>
     */
    public static function roleFieldPermissionNames(?Role $role): array
    {
        return static::rolePermissionNames($role, static::allFieldPermissionNames());
    }

    public static function userCan(?Authenticatable $user, string $permission): bool
    {
        if (! $user instanceof User || ! $user->actif) {
            return false;
        }

        try {
            return $user->can($permission);
        } catch (Throwable) {
            return false;
        }
    }

    public static function fieldPermissionName(string $entity, string $field, string $action): string
    {
        return 'fields.'.Str::snake($entity).'.'.Str::snake($field).'.'.static::normalizeFieldAction($action);
    }

    public static function userCanField(?Authenticatable $user, string $entity, string $field, string $action): bool
    {
        $action = static::normalizeFieldAction($action);

        if (! static::hasFieldDefinition($entity, $field)) {
            return true;
        }

        if (! static::userHasAnyFieldPermission($user, $entity)) {
            return true;
        }

        $permissions = static::userPermissionNames($user);

        return in_array(static::fieldPermissionName($entity, $field, 'all'), $permissions, true)
            || in_array(static::fieldPermissionName($entity, $field, $action), $permissions, true);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function filterFieldDataForUser(?Authenticatable $user, string $entity, array $data, string $action): array
    {
        if (! static::userHasAnyFieldPermission($user, $entity)) {
            return $data;
        }

        foreach (array_keys($data) as $field) {
            if (static::hasFieldDefinition($entity, $field) && ! static::userCanField($user, $entity, $field, $action)) {
                unset($data[$field]);
            }
        }

        return $data;
    }

    private static function userHasAnyFieldPermission(?Authenticatable $user, string $entity): bool
    {
        $prefix = 'fields.'.Str::snake($entity).'.';

        foreach (static::userPermissionNames($user) as $permission) {
            if (str_starts_with($permission, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $allowedPermissions
     * @return array<int, string>
     */
    private static function rolePermissionNames(?Role $role, array $allowedPermissions): array
    {
        if (! $role) {
            return [];
        }

        return $role->permissions
            ->pluck('name')
            ->intersect($allowedPermissions)
            ->values()
            ->all();
    }

    private static function hasFieldDefinition(string $entity, string $field): bool
    {
        $entity = Str::snake($entity);
        $field = Str::snake($field);

        return isset(static::fieldModules()[$entity]['fields'][$field]);
    }

    private static function normalizeFieldAction(string $action): string
    {
        return match ($action) {
            'view' => 'show',
            'update' => 'edit',
            default => $action,
        };
    }

    /**
     * @return array<int, string>
     */
    private static function userPermissionNames(?Authenticatable $user): array
    {
        if (! $user instanceof User || ! $user->actif || ! method_exists($user, 'getAllPermissions')) {
            return [];
        }

        $cacheKey = spl_object_id($user);

        if (! array_key_exists($cacheKey, static::$userPermissionCache)) {
            try {
                static::$userPermissionCache[$cacheKey] = $user->getAllPermissions()->pluck('name')->all();
            } catch (Throwable) {
                static::$userPermissionCache[$cacheKey] = [];
            }
        }

        return static::$userPermissionCache[$cacheKey];
    }

    /**
     * @param  class-string  $modelClass
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private static function fieldLabelsForModel(string $modelClass, array $overrides = []): array
    {
        $model = new $modelClass();
        $labels = [];

        foreach ($model->getFillable() as $field) {
            $labels[$field] = $overrides[$field] ?? static::defaultFieldLabel($field);
        }

        return $labels;
    }

    private static function defaultFieldLabel(string $field): string
    {
        return Str::headline(str_replace('_', ' ', $field));
    }
}
