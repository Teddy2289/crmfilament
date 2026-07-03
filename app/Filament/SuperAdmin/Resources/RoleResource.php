<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\RoleResource\Pages\CreateRole;
use App\Filament\SuperAdmin\Resources\RoleResource\Pages\EditRole;
use App\Filament\SuperAdmin\Resources\RoleResource\Pages\ListRoles;
use App\Support\AccessRightsCatalog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Rôles et permissions';

    protected static ?string $navigationGroup = 'Utilisateurs & Accès';

    protected static ?int $navigationSort = 2;

    private const AOPIA_MODULES = [
        'prospects',
        'partenaires',
        'clients',
        'opportunites',
        'rendez_vous',
        'entreprises',
        'campagne_phonings',
        'dossier_formations',
        'activites',
        'rapports',
        'document_knowledges',
        'script_appels',
        'statut_phonings',
    ];

    private const ALLOPRO_MODULES = [
        'tickets',
        'fiche_p2',
        'artisans',
        'reclamations',
        'rapports_satisfaction',
        'prospection_artisans',
        'dashboard',
    ];

    private const FIELD_GROUPS = [
        'prospects' => ['label' => 'Prospects', 'icon' => 'heroicon-o-user'],
        'partenaires' => ['label' => 'Partenaires', 'icon' => 'heroicon-o-building-office'],
        'clients' => ['label' => 'Clients', 'icon' => 'heroicon-o-users'],
        'opportunites' => ['label' => 'Opportunités', 'icon' => 'heroicon-o-sparkles'],
        'rendez_vous' => ['label' => 'Rendez-vous', 'icon' => 'heroicon-o-calendar-days'],
        'autres' => ['label' => 'Autres champs', 'icon' => 'heroicon-o-cube'],
    ];

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('permissions')
            ->withCount(['permissions', 'users']);
    }

    public static function form(Form $form): Form
    {
        AccessRightsCatalog::ensurePermissionsExist();

        return $form->schema([
            Forms\Components\Hidden::make('module_permissions')
                ->default(fn (?Role $record): array => AccessRightsCatalog::roleModulePermissionNames($record))
                ->dehydrated(),

            Forms\Components\Hidden::make('field_permissions')
                ->default(fn (?Role $record): array => AccessRightsCatalog::roleFieldPermissionNames($record))
                ->dehydrated(),

            Forms\Components\Tabs::make('Configuration du rôle')
                ->persistTabInQueryString()
                ->columnSpanFull()
                ->tabs([
                    Forms\Components\Tabs\Tab::make('Identité')
                        ->icon('heroicon-o-identification')
                        ->schema([
                            Forms\Components\Section::make('Informations du rôle')
                                ->icon('heroicon-o-shield-check')
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Nom du rôle')
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->helperText('Format conseillé : snake_case, par exemple back_office.'),

                                    Forms\Components\TextInput::make('guard_name')
                                        ->label('Garde d’authentification')
                                        ->default('web')
                                        ->required(),
                                ])
                                ->columns(2),

                            Forms\Components\Section::make('Mode d’accès')
                                ->icon('heroicon-o-key')
                                ->description('Choisissez entre un accès complet ou une sélection fine par module et par champ.')
                                ->schema([
                                    Forms\Components\Radio::make('access_mode')
                                        ->label('Accès')
                                        ->options([
                                            'all' => 'Tout autoriser',
                                            'selective' => 'Sélectif',
                                        ])
                                        ->descriptions([
                                            'all' => 'Toutes les permissions du catalogue CRM sont appliquées.',
                                            'selective' => 'Les modules et champs sont choisis dans les onglets dédiés.',
                                        ])
                                        ->default(fn (?Role $record): string => static::accessModeFor($record))
                                        ->inline()
                                        ->live()
                                        ->required()
                                        ->afterStateUpdated(function ($state, Set $set): void {
                                            if ($state === 'all') {
                                                $set('module_permissions', []);
                                                $set('field_permissions', []);
                                            }
                                        }),

                                    Forms\Components\Placeholder::make('selection_resume')
                                        ->label('Résumé')
                                        ->content(fn (Get $get): HtmlString => static::selectionSummary($get))
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ]),

                    Forms\Components\Tabs\Tab::make('Modules')
                        ->icon('heroicon-o-squares-plus')
                        ->badge(fn (Get $get): int => count($get('module_permissions') ?? []))
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    static::modulePermissionSection(
                                        key: 'aopia',
                                        title: 'NS Conseil / AOPIA',
                                        icon: 'heroicon-o-building-office-2',
                                        moduleKeys: self::AOPIA_MODULES,
                                    ),
                                    static::modulePermissionSection(
                                        key: 'allopro',
                                        title: 'AlloPro',
                                        icon: 'heroicon-o-phone',
                                        moduleKeys: self::ALLOPRO_MODULES,
                                    ),
                                ]),
                        ]),

                    Forms\Components\Tabs\Tab::make('Champs')
                        ->icon('heroicon-o-table-cells')
                        ->badge(fn (Get $get): int => count($get('field_permissions') ?? []))
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema(static::fieldPermissionSections()),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Rôle')
                    ->searchable()
                    ->weight('semibold')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'administrateur' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('access_mode')
                    ->label('Accès')
                    ->state(fn (Role $record): string => static::accessModeFor($record) === 'all' ? 'Tout' : 'Sélectif')
                    ->badge()
                    ->color(fn (Role $record): string => static::accessModeFor($record) === 'all' ? 'danger' : 'info'),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Utilisateurs')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('guard_name')
                    ->label('Garde')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('guard_name')
                    ->label('Garde')
                    ->options(['web' => 'web']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Role $record): bool => ! in_array($record->name, ['super_admin', 'administrateur'], true)),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    public static function accessModeFor(?Role $role): string
    {
        return AccessRightsCatalog::roleHasFullAccess($role) ? 'all' : 'selective';
    }

    /**
     * @param  array<int, string>  $moduleKeys
     */
    private static function modulePermissionSection(string $key, string $title, string $icon, array $moduleKeys): Forms\Components\Section
    {
        $candidatePermissions = static::moduleCandidatePermissions($moduleKeys);

        return Forms\Components\Section::make($title)
            ->icon($icon)
            ->description(fn (Get $get): string => static::permissionCountSummary($get, 'module_permissions', $candidatePermissions))
            ->collapsible()
            ->collapsed(fn (Get $get): bool => ! static::hasSelectedPermissions($get, 'module_permissions', $candidatePermissions))
            ->schema([
                Forms\Components\Grid::make(1)
                    ->schema(static::modulePermissionAccordions($key, $moduleKeys)),
            ]);
    }

    /**
     * @param  array<int, string>  $moduleKeys
     * @return array<int, Forms\Components\Section>
     */
    private static function modulePermissionAccordions(string $groupKey, array $moduleKeys): array
    {
        $modules = AccessRightsCatalog::modules();
        $sections = [];

        foreach ($moduleKeys as $moduleKey) {
            if (! isset($modules[$moduleKey])) {
                continue;
            }

            $module = $modules[$moduleKey];
            $candidatePermissions = array_keys($module['permissions']);

            $sections[] = Forms\Components\Section::make($module['label'])
                ->description(fn (Get $get): string => static::permissionCountSummary($get, 'module_permissions', $candidatePermissions))
                ->collapsible()
                ->collapsed(fn (Get $get): bool => ! static::hasSelectedPermissions($get, 'module_permissions', $candidatePermissions))
                ->schema([
                    Forms\Components\CheckboxList::make(static::permissionStateName('module_permissions', $groupKey, $moduleKey))
                        ->label('Droits du module')
                        ->options(fn (): array => $module['permissions'])
                        ->default(fn (?Role $record): array => collect(AccessRightsCatalog::roleModulePermissionNames($record))
                            ->intersect($candidatePermissions)
                            ->values()
                            ->all())
                        ->bulkToggleable()
                        ->columns(3)
                        ->gridDirection('row')
                        ->live()
                        ->dehydrated(false)
                        ->disabled(fn (Get $get): bool => $get('access_mode') === 'all')
                        ->afterStateUpdated(fn ($state, Set $set, Get $get) => static::replacePermissionSlice(
                            get: $get,
                            set: $set,
                            targetState: 'module_permissions',
                            candidates: $candidatePermissions,
                            selected: $state ?? [],
                        )),
                ]);
        }

        return $sections;
    }

    /**
     * @return array<int, Forms\Components\Section>
     */
    private static function fieldPermissionSections(): array
    {
        $sections = [];

        foreach (self::FIELD_GROUPS as $entity => $config) {
            $entities = $entity === 'autres'
                ? static::otherFieldEntities()
                : [$entity];

            if ($entities === []) {
                continue;
            }

            $candidatePermissions = static::fieldCandidatePermissions($entities);

            $sections[] = Forms\Components\Section::make($config['label'])
                ->icon($config['icon'])
                ->description(fn (Get $get): string => static::permissionCountSummary($get, 'field_permissions', $candidatePermissions))
                ->collapsible()
                ->collapsed(fn (Get $get): bool => ! static::hasSelectedPermissions($get, 'field_permissions', $candidatePermissions))
                ->schema([
                    Forms\Components\Grid::make(1)
                        ->schema(static::fieldPermissionAccordions($entity, $entities)),
                ]);
        }

        return $sections;
    }

    /**
     * @param  array<int, string>  $entities
     * @return array<int, Forms\Components\Section>
     */
    private static function fieldPermissionAccordions(string $groupKey, array $entities): array
    {
        $modules = AccessRightsCatalog::fieldModules();
        $actions = AccessRightsCatalog::fieldActions();
        $sections = [];
        $showEntityLabel = count($entities) > 1;

        foreach ($entities as $entity) {
            if (! isset($modules[$entity])) {
                continue;
            }

            $module = $modules[$entity];

            foreach ($module['fields'] as $field => $fieldLabel) {
                $candidatePermissions = static::fieldCandidatePermissionsFor($entity, $field);
                $title = $showEntityLabel ? "{$module['label']} - {$fieldLabel}" : $fieldLabel;

                $sections[] = Forms\Components\Section::make($title)
                    ->description(fn (Get $get): string => static::permissionCountSummary($get, 'field_permissions', $candidatePermissions))
                    ->collapsible()
                    ->collapsed(fn (Get $get): bool => ! static::hasSelectedPermissions($get, 'field_permissions', $candidatePermissions))
                    ->schema([
                        Forms\Components\CheckboxList::make(static::permissionStateName('field_permissions', $groupKey, $entity, $field))
                            ->label('Droits du champ')
                            ->options(fn (): array => collect($actions)
                                ->mapWithKeys(fn (string $label, string $action): array => [
                                    AccessRightsCatalog::fieldPermissionName($entity, $field, $action) => $label,
                                ])
                                ->all())
                            ->default(fn (?Role $record): array => collect(AccessRightsCatalog::roleFieldPermissionNames($record))
                                ->intersect($candidatePermissions)
                                ->values()
                                ->all())
                            ->bulkToggleable()
                            ->columns(5)
                            ->gridDirection('row')
                            ->live()
                            ->dehydrated(false)
                            ->disabled(fn (Get $get): bool => $get('access_mode') === 'all')
                            ->afterStateUpdated(fn ($state, Set $set, Get $get) => static::replacePermissionSlice(
                                get: $get,
                                set: $set,
                                targetState: 'field_permissions',
                                candidates: $candidatePermissions,
                                selected: $state ?? [],
                            )),
                    ]);
            }
        }

        return $sections;
    }

    /**
     * @param  array<int, string>  $moduleKeys
     * @return array<int, string>
     */
    private static function moduleCandidatePermissions(array $moduleKeys): array
    {
        return collect(AccessRightsCatalog::modules())
            ->only($moduleKeys)
            ->flatMap(fn (array $module): array => array_keys($module['permissions']))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $entities
     * @return array<int, string>
     */
    private static function fieldCandidatePermissions(array $entities): array
    {
        return collect($entities)
            ->flatMap(function (string $entity): array {
                $module = AccessRightsCatalog::fieldModules()[$entity] ?? null;

                if ($module === null) {
                    return [];
                }

                return collect(array_keys($module['fields']))
                    ->flatMap(fn (string $field): array => static::fieldCandidatePermissionsFor($entity, $field))
                    ->values()
                    ->all();
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function fieldCandidatePermissionsFor(string $entity, string $field): array
    {
        return collect(array_keys(AccessRightsCatalog::fieldActions()))
            ->map(fn (string $action): string => AccessRightsCatalog::fieldPermissionName($entity, $field, $action))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function otherFieldEntities(): array
    {
        return collect(array_keys(AccessRightsCatalog::fieldModules()))
            ->diff(array_keys(array_filter(
                self::FIELD_GROUPS,
                fn (string $entity): bool => $entity !== 'autres',
                ARRAY_FILTER_USE_KEY
            )))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $candidates
     * @param  array<int, string>  $selected
     */
    private static function replacePermissionSlice(Get $get, Set $set, string $targetState, array $candidates, array $selected): void
    {
        $current = $get($targetState) ?? [];

        $next = collect($current)
            ->reject(fn (string $permission): bool => in_array($permission, $candidates, true))
            ->merge($selected)
            ->unique()
            ->values()
            ->all();

        $set($targetState, $next);
    }

    /**
     * @param  array<int, string>  $candidates
     */
    private static function permissionCountSummary(Get $get, string $targetState, array $candidates): string
    {
        $selectedCount = static::selectedPermissionCount($get, $targetState, $candidates);
        $totalCount = count($candidates);

        if ($selectedCount === 0) {
            return "Aucun droit sélectionné sur {$totalCount}.";
        }

        if ($selectedCount === $totalCount) {
            return "Tous les droits sont sélectionnés ({$totalCount}).";
        }

        return "{$selectedCount}/{$totalCount} droits sélectionnés.";
    }

    /**
     * @param  array<int, string>  $candidates
     */
    private static function hasSelectedPermissions(Get $get, string $targetState, array $candidates): bool
    {
        return static::selectedPermissionCount($get, $targetState, $candidates) > 0;
    }

    /**
     * @param  array<int, string>  $candidates
     */
    private static function selectedPermissionCount(Get $get, string $targetState, array $candidates): int
    {
        return collect($get($targetState) ?? [])
            ->intersect($candidates)
            ->count();
    }

    private static function permissionStateName(string $prefix, string ...$parts): string
    {
        return collect([$prefix, ...$parts])
            ->map(fn (string $part): string => preg_replace('/[^A-Za-z0-9_]/', '_', $part) ?: $part)
            ->implode('_');
    }

    private static function selectionSummary(Get $get): HtmlString
    {
        if ($get('access_mode') === 'all') {
            return new HtmlString('<div class="text-sm text-emerald-700 dark:text-emerald-300">Toutes les entités, tous les modules et tous les champs du catalogue CRM seront autorisés.</div>');
        }

        $moduleCount = count($get('module_permissions') ?? []);
        $fieldCount = count($get('field_permissions') ?? []);

        return new HtmlString(
            '<div class="grid gap-3 sm:grid-cols-2">'
            .'<div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700"><div class="text-xs text-gray-500">Modules</div><div class="text-xl font-semibold">'.$moduleCount.'</div></div>'
            .'<div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700"><div class="text-xs text-gray-500">Droits par champ</div><div class="text-xl font-semibold">'.$fieldCount.'</div></div>'
            .'</div>'
        );
    }
}
