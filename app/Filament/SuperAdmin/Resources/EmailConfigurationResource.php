<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\EmailConfigurationResource\Pages;
use App\Models\EmailConfiguration;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class EmailConfigurationResource extends Resource
{
    protected static ?string $model = EmailConfiguration::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Configurations Email';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->isSuperAdmin() ?? false;
    }

    // ─────────────────────────────────────────────────────────────────
    // FORMULAIRE
    // ─────────────────────────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informations générales')
                ->schema([
                    Forms\Components\Toggle::make('is_global')
                        ->label('Configuration globale')
                        ->helperText('Si activé, cette configuration s\'applique à tous les utilisateurs')
                        ->default(false)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $set('user_id', null);
                            }
                        }),

                    Forms\Components\Select::make('user_id')
                        ->label('Utilisateur')
                        ->options(fn () => User::query()
                            ->select(['id', 'prenom', 'nom'])
                            ->whereNull('deleted_at')
                            ->orderBy('prenom')
                            ->orderBy('nom')
                            ->get()
                            ->mapWithKeys(fn (User $record) => [
                                $record->id => $record->nom_complet,
                            ])
                            ->toArray(),
                        )
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->visible(fn (callable $get) => ! $get('is_global'))
                        ->helperText('Laisser vide pour une configuration globale'),

                    Forms\Components\Toggle::make('active')
                        ->label('Active')
                        ->default(true),

                    Forms\Components\Toggle::make('sync_enabled')
                        ->label('Synchronisation activée')
                        ->default(true)
                        ->helperText('Active la synchronisation automatique des emails'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Configuration IMAP')
                ->schema([
                    Forms\Components\TextInput::make('imap_host')
                        ->label('Hôte IMAP')
                        ->default('imap.gmail.com')
                        ->required()
                        ->helperText('Ex: imap.gmail.com, outlook.office365.com'),

                    Forms\Components\TextInput::make('imap_port')
                        ->label('Port IMAP')
                        ->numeric()
                        ->default(993)
                        ->required()
                        ->helperText('Généralement 993 pour SSL, 143 pour STARTTLS'),

                    Forms\Components\Select::make('imap_encryption')
                        ->label('Chiffrement IMAP')
                        ->options([
                            'ssl' => 'SSL',
                            'tls' => 'TLS',
                            'starttls' => 'STARTTLS',
                            'none' => 'Aucun',
                        ])
                        ->default('ssl')
                        ->required(),

                    Forms\Components\Select::make('imap_protocol')
                        ->label('Protocole')
                        ->options([
                            'imap' => 'IMAP',
                            'pop3' => 'POP3',
                        ])
                        ->default('imap')
                        ->required(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Configuration SMTP')
                ->schema([
                    Forms\Components\TextInput::make('smtp_host')
                        ->label('Hôte SMTP')
                        ->default('smtp.gmail.com')
                        ->required()
                        ->helperText('Ex: smtp.gmail.com, smtp.office365.com'),

                    Forms\Components\TextInput::make('smtp_port')
                        ->label('Port SMTP')
                        ->numeric()
                        ->default(587)
                        ->required()
                        ->helperText('Généralement 587 pour TLS, 465 pour SSL'),

                    Forms\Components\Select::make('smtp_encryption')
                        ->label('Chiffrement SMTP')
                        ->options([
                            'ssl' => 'SSL',
                            'tls' => 'TLS',
                            'starttls' => 'STARTTLS',
                            'none' => 'Aucun',
                        ])
                        ->default('tls')
                        ->required(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Identifiants')
                ->schema([
                    Forms\Components\TextInput::make('email')
                        ->label('Adresse email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true),

                    Forms\Components\TextInput::make('password')
                        ->label('Mot de passe')
                        ->password()
                        ->required(fn (?EmailConfiguration $record) => ! $record)
                        ->dehydrated(fn ($state) => filled($state))
                        ->helperText('Mot de passe de l\'email ou mot de passe d\'application (laisser vide pour conserver le mot de passe actuel en édition)')
                        ->revealable(),

                    Forms\Components\TextInput::make('from_name')
                        ->label('Nom d\'expéditeur')
                        ->helperText('Nom qui apparaîtra comme expéditeur des emails'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Paramètres de synchronisation')
                ->schema([
                    Forms\Components\TextInput::make('sync_interval')
                        ->label('Intervalle de synchronisation (minutes)')
                        ->numeric()
                        ->default(5)
                        ->rules(['min:1'])
                        ->required()
                        ->helperText('Fréquence de synchronisation automatique'),

                    Forms\Components\TextInput::make('sync_limit')
                        ->label('Limite de synchronisation')
                        ->numeric()
                        ->default(50)
                        ->rules(['min:1'])
                        ->required()
                        ->helperText('Nombre maximum d\'emails à synchroniser à chaque fois'),
                ])
                ->columns(2),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // TABLE
    // ─────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_global')
                    ->label('Globale')
                    ->boolean()
                    ->trueIcon('heroicon-o-globe-alt')
                    ->falseIcon('heroicon-o-user'),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Utilisateur')
                    ->formatStateUsing(fn ($state, $record) => $record->user?->nom_complet ?? $state)
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('imap_host')
                    ->label('Hôte IMAP')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('smtp_host')
                    ->label('Hôte SMTP')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\IconColumn::make('sync_enabled')
                    ->label('Sync')
                    ->boolean()
                    ->trueIcon('heroicon-o-refresh')
                    ->falseIcon('heroicon-o-x'),

                Tables\Columns\TextColumn::make('last_sync_at')
                    ->label('Dernière sync')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('sync_interval')
                    ->label('Intervalle')
                    ->formatStateUsing(fn ($state) => $state.' min')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('active')
                    ->label('Statut')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),

                Tables\Filters\SelectFilter::make('is_global')
                    ->label('Type')
                    ->options([
                        '1' => 'Globale',
                        '0' => 'Utilisateur',
                    ]),

                Tables\Filters\SelectFilter::make('sync_enabled')
                    ->label('Synchronisation')
                    ->options([
                        '1' => 'Activée',
                        '0' => 'Désactivée',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('test_connection')
                    ->label('Tester connexion')
                    ->icon('heroicon-o-signal')
                    ->color('success')
                    ->action(function ($record) {
                        $result = $record->testConnection();
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title($result['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title($result['message'])
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('toggle_sync')
                    ->label('Toggle Sync')
                    ->icon('heroicon-o-refresh')
                    ->color('warning')
                    ->action(function ($record) {
                        if ($record->sync_enabled) {
                            $record->disableSync();
                            Notification::make()
                                ->title('Synchronisation désactivée')
                                ->success()
                                ->send();
                        } else {
                            $record->enableSync();
                            Notification::make()
                                ->title('Synchronisation activée')
                                ->success()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('toggle_active')
                    ->label('Toggle Active')
                    ->icon('heroicon-o-power')
                    ->color('gray')
                    ->action(function ($record) {
                        if ($record->active) {
                            $record->deactivate();
                            Notification::make()
                                ->title('Configuration désactivée')
                                ->success()
                                ->send();
                        } else {
                            $record->activate();
                            Notification::make()
                                ->title('Configuration activée')
                                ->success()
                                ->send();
                        }
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activer')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each->activate()),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Désactiver')
                        ->icon('heroicon-o-x')
                        ->action(fn ($records) => $records->each->deactivate()),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // ─────────────────────────────────────────────────────────────────
    // PAGES
    // ─────────────────────────────────────────────────────────────────
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailConfigurations::route('/'),
            'create' => Pages\CreateEmailConfiguration::route('/create'),
            'edit' => Pages\EditEmailConfiguration::route('/{record}/edit'),
        ];
    }
}
