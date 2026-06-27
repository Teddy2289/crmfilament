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

    protected static ?string $navigationLabel = 'Roles & Permissions';

    protected static ?string $navigationGroup = 'Utilisateurs & Acces';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        AccessRightsCatalog::ensurePermissionsExist();

        return $form->schema([
            Forms\Components\Section::make('Role')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nom du role')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('snake_case recommande ex: back_office'),

                    Forms\Components\TextInput::make('guard_name')
                        ->label('Guard')
                        ->default('web')
                        ->required(),
                ]),

            Forms\Components\Section::make('Droits d\'acces')
                ->description('Choisissez un acces total ou un acces selectif par entite, module et champ.')
                ->schema([
                    Forms\Components\Radio::make('access_mode')
                        ->label('Mode d\'acces')
                        ->options([
                            'all' => 'Tout',
                            'selective' => 'Selectif par entite/module',
                        ])
                        ->descriptions([
                            'all' => 'Le role recoit toutes les permissions du catalogue CRM.',
                            'selective' => 'Selection fine par module, champ et action.',
                        ])
                        ->default(fn (?Role $record) => static::accessModeFor($record))
                        ->inline()
                        ->live()
                        ->required(),

                    Forms\Components\Tabs::make('Droits selectifs')
                        ->tabs([
                            Forms\Components\Tabs\Tab::make('Entites et modules')
                                ->schema([
                                    Forms\Components\CheckboxList::make('module_permissions')
                                        ->label('Droits par entite/module')
                                        ->options(AccessRightsCatalog::permissionOptions())
                                        ->descriptions(AccessRightsCatalog::permissionDescriptions())
                                        ->default(fn (?Role $record) => AccessRightsCatalog::roleModulePermissionNames($record))
                                        ->searchable()
                                        ->bulkToggleable()
                                        ->columns(2)
                                        ->gridDirection('row')
                                        ->helperText('Cochez les actions autorisees pour ce role. Les modules non coches seront inaccessibles.'),
                                ]),

                            Forms\Components\Tabs\Tab::make('Champs')
                                ->schema([
                                    Forms\Components\CheckboxList::make('field_permissions')
                                        ->label('Droits par champ')
                                        ->options(AccessRightsCatalog::fieldPermissionOptions())
                                        ->descriptions(AccessRightsCatalog::fieldPermissionDescriptions())
                                        ->default(fn (?Role $record) => AccessRightsCatalog::roleFieldPermissionNames($record))
                                        ->searchable()
                                        ->bulkToggleable()
                                        ->columns(2)
                                        ->gridDirection('row')
                                        ->helperText('Actions par champ : Voir, Creer, Modifier, Flux ou Tout. Si aucun champ n\'est configure pour une entite, le comportement module reste applique.'),
                                ]),
                        ])
                        ->visible(fn (Get $get) => $get('access_mode') === 'selective')
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make('full_access_notice')
                        ->label('Droits appliques')
                        ->content('Mode tout : toutes les entites, tous les modules et tous les champs du catalogue CRM seront autorises pour ce role.')
                        ->visible(fn (Get $get) => $get('access_mode') === 'all'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Role')
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
                    ->state(fn (Role $record) => static::accessModeFor($record) === 'all' ? 'Tout' : 'Selectif')
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
                    ->label('Guard')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cree le')
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
