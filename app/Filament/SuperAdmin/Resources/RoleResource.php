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
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\CheckboxList::make('module_permissions')
                                                ->label('Droits par entité/module')
                                                ->options(AccessRightsCatalog::permissionOptions())
                                                ->descriptions(AccessRightsCatalog::permissionDescriptions())
                                                ->default(fn (?Role $record) => AccessRightsCatalog::roleModulePermissionNames($record))
                                                ->searchable()
                                                ->bulkToggleable()
                                                ->columns(1)
                                                ->gridDirection('row')
                                                ->helperText('Cochez les actions autorisées pour ce rôle. Les modules non cochés seront inaccessibles.')
                                                ->live()
                                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                    $count = is_array($state) ? count($state) : 0;
                                                    $set('module_permissions_count', $count);
                                                }),

                                            Forms\Components\Placeholder::make('module_permissions_count')
                                                ->label('Permissions sélectionnées')
                                                ->content(fn (Get $get) => is_array($get('module_permissions')) ? count($get('module_permissions')) : 0)
                                                ->inlineLabel(),
                                        ]),
                                ]),

                            Forms\Components\Tabs\Tab::make('Champs')
                                ->icon('heroicon-o-table-cells')
                                ->badge(fn (?Role $record) => count(AccessRightsCatalog::roleFieldPermissionNames($record)))
                                ->schema([
                                    Forms\Components\Accordion::make('field_permissions_accordion')
                                        ->schema([
                                            Forms\Components\Accordion\Item::make('prospects')
                                                ->label('Prospects')
                                                ->icon('heroicon-o-user')
                                                ->schema([
                                                    Forms\Components\CheckboxList::make('field_permissions_prospects')
                                                        ->label('Champs prospects')
                                                        ->options(fn () => collect(AccessRightsCatalog::fieldPermissionOptions())
                                                            ->filter(fn ($label, $key) => str_starts_with($key, 'prospects.'))
                                                            ->toArray())
                                                        ->descriptions(fn () => collect(AccessRightsCatalog::fieldPermissionDescriptions())
                                                            ->filter(fn ($label, $key) => str_starts_with($key, 'prospects.'))
                                                            ->toArray())
                                                        ->default(fn (?Role $record) => collect(AccessRightsCatalog::roleFieldPermissionNames($record))
                                                            ->filter(fn ($perm) => str_starts_with($perm, 'prospects.'))
                                                            ->values()
                                                            ->toArray())
                                                        ->searchable()
                                                        ->bulkToggleable()
                                                        ->columns(1)
                                                        ->gridDirection('row')
                                                        ->live()
                                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                            $current = $set('field_permissions') ?? [];
                                                            $others = collect($current)->filter(fn ($p) => ! str_starts_with($p, 'prospects.'))->toArray();
                                                            $new = array_merge($others, $state ?? []);
                                                            $set('field_permissions', $new);
                                                            $set('field_permissions_count', count($new));
                                                        }),
                                                ]),

                                            Forms\Components\Accordion\Item::make('clients')
                                                ->label('Clients')
                                                ->icon('heroicon-o-users')
                                                ->schema([
                                                    Forms\Components\CheckboxList::make('field_permissions_clients')
                                                        ->label('Champs clients')
                                                        ->options(fn () => collect(AccessRightsCatalog::fieldPermissionOptions())
                                                            ->filter(fn ($label, $key) => str_starts_with($key, 'clients.'))
                                                            ->toArray())
                                                        ->descriptions(fn () => collect(AccessRightsCatalog::fieldPermissionDescriptions())
                                                            ->filter(fn ($label, $key) => str_starts_with($key, 'clients.'))
                                                            ->toArray())
                                                        ->default(fn (?Role $record) => collect(AccessRightsCatalog::roleFieldPermissionNames($record))
                                                            ->filter(fn ($perm) => str_starts_with($perm, 'clients.'))
                                                            ->values()
                                                            ->toArray())
                                                        ->searchable()
                                                        ->bulkToggleable()
                                                        ->columns(1)
                                                        ->gridDirection('row')
                                                        ->live()
                                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                            $current = $set('field_permissions') ?? [];
                                                            $others = collect($current)->filter(fn ($p) => ! str_starts_with($p, 'clients.'))->toArray();
                                                            $new = array_merge($others, $state ?? []);
                                                            $set('field_permissions', $new);
                                                            $set('field_permissions_count', count($new));
                                                        }),
                                                ]),

                                            Forms\Components\Accordion\Item::make('partenaires')
                                                ->label('Partenaires')
                                                ->icon('heroicon-o-building-office')
                                                ->schema([
                                                    Forms\Components\CheckboxList::make('field_permissions_partenaires')
                                                        ->label('Champs partenaires')
                                                        ->options(fn () => collect(AccessRightsCatalog::fieldPermissionOptions())
                                                            ->filter(fn ($label, $key) => str_starts_with($key, 'partenaires.'))
                                                            ->toArray())
                                                        ->descriptions(fn () => collect(AccessRightsCatalog::fieldPermissionDescriptions())
                                                            ->filter(fn ($label, $key) => str_starts_with($key, 'partenaires.'))
                                                            ->toArray())
                                                        ->default(fn (?Role $record) => collect(AccessRightsCatalog::roleFieldPermissionNames($record))
                                                            ->filter(fn ($perm) => str_starts_with($perm, 'partenaires.'))
                                                            ->values()
                                                            ->toArray())
                                                        ->searchable()
                                                        ->bulkToggleable()
                                                        ->columns(1)
                                                        ->gridDirection('row')
                                                        ->live()
                                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                            $current = $set('field_permissions') ?? [];
                                                            $others = collect($current)->filter(fn ($p) => ! str_starts_with($p, 'partenaires.'))->toArray();
                                                            $new = array_merge($others, $state ?? []);
                                                            $set('field_permissions', $new);
                                                            $set('field_permissions_count', count($new));
                                                        }),
                                                ]),
                                        ])
                                        ->collapsible()
                                        ->defaultOpen(1)
                                        ->columnSpanFull(),

                                    Forms\Components\Placeholder::make('field_permissions_count')
                                        ->label('Permissions de champ sélectionnées')
                                        ->content(fn (Get $get) => is_array($get('field_permissions')) ? count($get('field_permissions')) : 0)
                                        ->inlineLabel(),
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
