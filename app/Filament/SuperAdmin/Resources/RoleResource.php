<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\RoleResource\Pages\CreateRole;
use App\Filament\SuperAdmin\Resources\RoleResource\Pages\EditRole;
use App\Filament\SuperAdmin\Resources\RoleResource\Pages\ListRoles;
use App\Support\AccessRightsCatalog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Rôles et permissions';

    protected static ?string $navigationGroup = 'Utilisateurs & Accès';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        AccessRightsCatalog::ensurePermissionsExist();

        return $form->schema([
            Forms\Components\Section::make('Informations du rôle')
                ->icon('heroicon-o-shield-check')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Nom du rôle')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->helperText('snake_case recommandé, ex. : back_office'),

                            Forms\Components\TextInput::make('guard_name')
                                ->label('Garde d\'authentification')
                                ->default('web')
                                ->required(),
                        ]),
                ]),

            Forms\Components\Section::make('Droits d\'accès')
                ->icon('heroicon-o-key')
                ->description('Choisissez un accès total ou un accès sélectif par entité, module et champ.')
                ->schema([
                    Forms\Components\Radio::make('access_mode')
                        ->label('Mode d\'accès')
                        ->options([
                            'all' => 'Tout',
                            'selective' => 'Sélectif par entité/module',
                        ])
                        ->descriptions([
                            'all' => 'Le rôle reçoit toutes les permissions du catalogue CRM.',
                            'selective' => 'Sélection fine par module, champ et action.',
                        ])
                        ->default(fn (?Role $record) => static::accessModeFor($record))
                        ->inline()
                        ->live()
                        ->required()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state === 'all') {
                                $set('module_permissions', []);
                                $set('field_permissions', []);
                            }
                        }),

                    Forms\Components\Tabs::make('Droits sélectifs')
                        ->tabs([
                            Forms\Components\Tabs\Tab::make('Entités et modules')
                                ->icon('heroicon-o-squares-plus')
                                ->badge(fn (?Role $record) => count(AccessRightsCatalog::roleModulePermissionNames($record)))
                                ->schema([
                                    Forms\Components\Section::make('AOPIA - Modules')
                                        ->description('Permissions pour le panel NsConseil')
                                        ->icon('heroicon-o-building-office-2')
                                        ->collapsible()
                                        ->schema([
                                            Forms\Components\CheckboxList::make('module_permissions_aopia')
                                                ->label('Modules AOPIA')
                                                ->options(fn () => collect(AccessRightsCatalog::permissionOptions())
                                                    ->filter(fn ($label, $key) => in_array(
                                                        explode('.', $key)[0] ?? '',
                                                        ['prospects', 'partenaires', 'clients', 'opportunites', 'rendez_vous', 'entreprises', 'campagne_phonings', 'dossier_formations', 'activites', 'rapports', 'document_knowledges', 'script_appels', 'statut_phonings']
                                                    ))
                                                    ->toArray())
                                                ->descriptions(fn () => collect(AccessRightsCatalog::permissionDescriptions())
                                                    ->filter(fn ($label, $key) => in_array(
                                                        explode('.', $key)[0] ?? '',
                                                        ['prospects', 'partenaires', 'clients', 'opportunites', 'rendez_vous', 'entreprises', 'campagne_phonings', 'dossier_formations', 'activites', 'rapports', 'document_knowledges', 'script_appels', 'statut_phonings']
                                                    ))
                                                    ->toArray())
                                                ->default(fn (?Role $record) => collect(AccessRightsCatalog::roleModulePermissionNames($record))
                                                    ->filter(fn ($perm) => in_array(
                                                        explode('.', $perm)[0] ?? '',
                                                        ['prospects', 'partenaires', 'clients', 'opportunites', 'rendez_vous', 'entreprises', 'campagne_phonings', 'dossier_formations', 'activites', 'rapports', 'document_knowledges', 'script_appels', 'statut_phonings']
                                                    ))
                                                    ->values()
                                                    ->toArray())
                                                ->searchable()
                                                ->bulkToggleable()
                                                ->columns(1)
                                                ->gridDirection('row')
                                                ->helperText('Cochez les modules AOPIA autorisés pour ce rôle.')
                                                ->live()
                                                ->afterStateUpdated(function ($state, Forms\Set $set, Get $get) {
                                                    $current = $get('module_permissions') ?? [];
                                                    $others = collect($current)->filter(fn ($p) => ! in_array(
                                                        explode('.', $p)[0] ?? '',
                                                        ['prospects', 'partenaires', 'clients', 'opportunites', 'rendez_vous', 'entreprises', 'campagne_phonings', 'dossier_formations', 'activites', 'rapports', 'document_knowledges', 'script_appels', 'statut_phonings']
                                                    ))->toArray();
                                                    $new = array_merge($others, $state ?? []);
                                                    $set('module_permissions', $new);
                                                    $set('module_permissions_count', count($new));
                                                }),
                                        ]),

                                    Forms\Components\Section::make('AlloPro - Modules')
                                        ->description('Permissions pour le panel AlloPro')
                                        ->icon('heroicon-o-phone')
                                        ->collapsible()
                                        ->schema([
                                            Forms\Components\CheckboxList::make('module_permissions_allopro')
                                                ->label('Modules AlloPro')
                                                ->options(fn () => collect(AccessRightsCatalog::permissionOptions())
                                                    ->filter(fn ($label, $key) => in_array(
                                                        explode('.', $key)[0] ?? '',
                                                        ['tickets', 'fiche_p2', 'artisans', 'reclamations', 'rapports_satisfaction', 'prospection_artisans', 'dashboard']
                                                    ))
                                                    ->toArray())
                                                ->descriptions(fn () => collect(AccessRightsCatalog::permissionDescriptions())
                                                    ->filter(fn ($label, $key) => in_array(
                                                        explode('.', $key)[0] ?? '',
                                                        ['tickets', 'fiche_p2', 'artisans', 'reclamations', 'rapports_satisfaction', 'prospection_artisans', 'dashboard']
                                                    ))
                                                    ->toArray())
                                                ->default(fn (?Role $record) => collect(AccessRightsCatalog::roleModulePermissionNames($record))
                                                    ->filter(fn ($perm) => in_array(
                                                        explode('.', $perm)[0] ?? '',
                                                        ['tickets', 'fiche_p2', 'artisans', 'reclamations', 'rapports_satisfaction', 'prospection_artisans', 'dashboard']
                                                    ))
                                                    ->values()
                                                    ->toArray())
                                                ->searchable()
                                                ->bulkToggleable()
                                                ->columns(1)
                                                ->gridDirection('row')
                                                ->helperText('Cochez les modules AlloPro autorisés pour ce rôle.')
                                                ->live()
                                                ->afterStateUpdated(function ($state, Forms\Set $set, Get $get) {
                                                    $current = $get('module_permissions') ?? [];
                                                    $others = collect($current)->filter(fn ($p) => ! in_array(
                                                        explode('.', $p)[0] ?? '',
                                                        ['tickets', 'fiche_p2', 'artisans', 'reclamations', 'rapports_satisfaction', 'prospection_artisans', 'dashboard']
                                                    ))->toArray();
                                                    $new = array_merge($others, $state ?? []);
                                                    $set('module_permissions', $new);
                                                    $set('module_permissions_count', count($new));
                                                }),
                                        ]),

                                    Forms\Components\Placeholder::make('module_permissions_count')
                                        ->label('Permissions sélectionnées')
                                        ->content(fn (Get $get) => is_array($get('module_permissions')) ? count($get('module_permissions')) : 0)
                                        ->inlineLabel(),
                                ]),

                            Forms\Components\Tabs\Tab::make('Champs')
                                ->icon('heroicon-o-table-cells')
                                ->badge(fn (?Role $record) => count(AccessRightsCatalog::roleFieldPermissionNames($record)))
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\CheckboxList::make('field_permissions')
                                                ->label('Droits par champ')
                                                ->options(AccessRightsCatalog::fieldPermissionOptions())
                                                ->descriptions(AccessRightsCatalog::fieldPermissionDescriptions())
                                                ->default(fn (?Role $record) => AccessRightsCatalog::roleFieldPermissionNames($record))
                                                ->searchable()
                                                ->bulkToggleable()
                                                ->columns(1)
                                                ->gridDirection('row')
                                                ->helperText('Actions par champ : Voir, Créer, Modifier, Flux ou Tout. Si aucun champ n\'est configuré pour une entité, le comportement du module reste appliqué.')
                                                ->live()
                                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                    $count = is_array($state) ? count($state) : 0;
                                                    $set('field_permissions_count', $count);
                                                }),

                                            Forms\Components\Placeholder::make('field_permissions_count')
                                                ->label('Permissions de champ sélectionnées')
                                                ->content(fn (Get $get) => is_array($get('field_permissions')) ? count($get('field_permissions')) : 0)
                                                ->inlineLabel(),
                                        ]),
                                ]),
                        ])
                        ->visible(fn (Get $get) => $get('access_mode') === 'selective')
                        ->columnSpanFull()
                        ->persistTabInQueryString(),

                    Forms\Components\Placeholder::make('full_access_notice')
                        ->label('Droits appliqués')
                        ->content('Mode tout : toutes les entités, tous les modules et tous les champs du catalogue CRM seront autorisés pour ce rôle.')
                        ->visible(fn (Get $get) => $get('access_mode') === 'all')
                        ->extraAttributes(['class' => 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4']),
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
                    ->color(fn (string $state) => match ($state) {
                        'super_admin' => 'danger',
                        'administrateur' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('access_mode')
                    ->label('Mode')
                    ->state(fn (Role $record) => static::accessModeFor($record) === 'all' ? 'Tout' : 'Sélectif')
                    ->badge()
                    ->color(fn (Role $record) => static::accessModeFor($record) === 'all' ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Utilisateurs')
                    ->counts('users')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('guard_name')
                    ->label('Garde')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Role $record) => ! in_array($record->name, ['super_admin', 'administrateur'])),
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
}
