<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\UserResource\Pages\CreateUser;
use App\Filament\SuperAdmin\Resources\UserResource\Pages\EditUser;
use App\Filament\SuperAdmin\Resources\UserResource\Pages\ListUsers;
use App\Filament\SuperAdmin\Resources\UserResource\Pages\ViewUser;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Utilisateurs';

    protected static ?string $navigationGroup = 'Utilisateurs & Accès';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) User::count();
    }

    // ── Formulaire ───────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Identité')
                ->icon('heroicon-o-user')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('prenom')
                        ->label('Prénom')->required()->maxLength(100),

                    Forms\Components\TextInput::make('nom')
                        ->label('Nom')->required()->maxLength(100),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')->email()->required()->unique(ignoreRecord: true)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('secteur')
                        ->label('Secteur')->nullable(),

                    Forms\Components\Toggle::make('actif')
                        ->label('Compte actif')->default(true)->inline(false),
                ]),

            Forms\Components\Section::make('Rôles')
                ->icon('heroicon-o-shield-check')
                ->schema([
                    Forms\Components\CheckboxList::make('roles')
                        ->label('')
                        ->relationship('roles', 'name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => match ($record->name) {
                            'super_admin' => '⚡ Super Admin',
                            'administrateur' => '🔑 Administrateur',
                            'responsable_plateau' => '📋 Responsable Plateau',
                            'back_office' => '🖥️ Back Office',
                            'operateur_n1' => '📞 Opérateur N1',
                            'superviseur' => '👁️ Superviseur',
                            'team_leader' => '🏆 Team Leader',
                            'commercial' => '💼 Commercial',
                            'teleprospecteur' => '📣 Téléprospecteur',
                            default => $record->name,
                        })
                        ->columns(3)
                        ->gridDirection('row'),
                ]),

            Forms\Components\Section::make('Mot de passe')
                ->icon('heroicon-o-lock-closed')
                ->collapsible()
                ->collapsed(fn ($record) => $record !== null)
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->label('Nouveau mot de passe')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->dehydrated(fn ($state) => filled($state))
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->required(fn ($record) => $record === null)
                        ->helperText('Laisser vide pour ne pas modifier'),

                    Forms\Components\TextInput::make('password_confirmation')
                        ->label('Confirmer le mot de passe')
                        ->password()->revealable()
                        ->same('password')
                        ->required(fn ($record) => $record === null)
                        ->dehydrated(false),
                ]),
        ]);
    }

    // ── Table ────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Nom')
                    ->formatStateUsing(fn ($state, $record) => trim($record->prenom.' '.$record->nom))
                    ->searchable(['nom', 'prenom'])
                    ->weight('semibold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()->copyable()->icon('heroicon-o-envelope'),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rôles')
                    ->badge()
                    ->separator(',')
                    ->color(fn ($state) => match ($state) {
                        'super_admin' => 'danger',
                        'administrateur' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('secteur')
                    ->label('Secteur')->placeholder('—'),

                Tables\Columns\IconColumn::make('actif')
                    ->label('Actif')->boolean()
                    ->trueColor('success')->falseColor('danger'),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Dernière connexion')
                    ->since()->placeholder('Jamais')
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')->date('d/m/Y')->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Rôle')
                    ->relationship('roles', 'name')
                    ->multiple()->native(false),

                Tables\Filters\TernaryFilter::make('actif')
                    ->label('Statut compte')
                    ->trueLabel('Actifs')->falseLabel('Désactivés')->native(false),
            ])

            ->actions([
                // ── Désactiver/Activer ──
                Tables\Actions\Action::make('toggle_actif')
                    ->label(fn (User $record) => $record->actif ? 'Désactiver' : 'Activer')
                    ->icon(fn (User $record) => $record->actif ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (User $record) => $record->actif ? 'danger' : 'success')
                    ->visible(fn (User $record) => $record->id !== auth()->id())
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->update(['actif' => ! $record->actif]);
                        Notification::make()
                            ->title($record->actif ? 'Compte activé' : 'Compte désactivé')
                            ->success()->send();
                    }),

                // ── Reset mot de passe ──
                Tables\Actions\Action::make('reset_password')
                    ->label('Reset MDP')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->label('Nouveau mot de passe')
                            ->password()->revealable()->minLength(8)->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->update(['password' => Hash::make($data['password'])]);
                        Notification::make()->title('Mot de passe réinitialisé')->success()->send();
                    }),

                // ── Impersonation ──
                Tables\Actions\Action::make('impersonate')
                    ->label('Se connecter en tant que')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('info')
                    ->visible(fn (User $record) => $record->id !== auth()->id() && ! $record->roles->pluck('name')->contains('super_admin'))

                    ->requiresConfirmation()
                    ->modalHeading('Connexion en tant que cet utilisateur ?')
                    ->modalDescription('Vous serez connecté avec les droits de cet utilisateur. Déconnectez-vous pour revenir.')
                    ->action(function (User $record) {
                        session(['impersonating' => auth()->id()]);
                        auth()->login($record);
                        Notification::make()
                            ->title('Connecté en tant que '.$record->nom_complet)
                            ->warning()->send();

                        return redirect('/');
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $record) => $record->id !== auth()->id()),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // ── Activer en masse ──
                    Tables\Actions\BulkAction::make('activer')
                        ->label('Activer la sélection')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['actif' => true])),

                    // ── Désactiver en masse ──
                    Tables\Actions\BulkAction::make('desactiver')
                        ->label('Désactiver la sélection')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['actif' => false])),

                    // ── Assigner rôle en masse ──
                    Tables\Actions\BulkAction::make('assigner_role')
                        ->label('Assigner un rôle')
                        ->icon('heroicon-o-shield-check')
                        ->form([
                            Forms\Components\Select::make('role')
                                ->label('Rôle')
                                ->options(Role::pluck('name', 'name'))
                                ->required()->native(false),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each->assignRole($data['role']);
                            Notification::make()->title('Rôle assigné')->success()->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Identité')
                ->columns(3)
                ->schema([
                    TextEntry::make('nom')->label('Nom complet')
                        ->formatStateUsing(fn ($state, $record) => trim($record->prenom.' '.$record->nom))
                        ->weight('bold'),
                    TextEntry::make('email')->label('Email')->copyable()->icon('heroicon-o-envelope'),
                    TextEntry::make('secteur')->label('Secteur')->placeholder('—'),
                    IconEntry::make('actif')->label('Actif')->boolean(),
                    TextEntry::make('created_at')->label('Créé le')->date('d/m/Y H:i'),
                    TextEntry::make('last_login_at')->label('Dernière connexion')->since()->placeholder('Jamais'),
                ]),
            Section::make('Rôles & Permissions')
                ->schema([
                    TextEntry::make('roles.name')->label('Rôles')->badge()
                        ->color(fn (string $state) => match ($state) {
                            'super_admin' => 'danger',
                            'administrateur' => 'warning',
                            default => 'gray',
                        }),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
