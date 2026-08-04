<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\EmailConfigurationResource\Pages;
use App\Models\EmailConfiguration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class EmailConfigurationResource extends Resource
{
    protected static ?string $model = EmailConfiguration::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Paramètres';

    protected static ?string $navigationLabel = 'Profil mail';

    protected static ?int $navigationSort = 60;

    public static function canViewAny(): bool
    {
        return Auth::check();
    }

    public static function canCreate(): bool
    {
        return Auth::check();
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $record->user_id === $user->id || ($record->is_global && $user->isSuperAdmin());
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::canEdit($record);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('is_global', true);
        });
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;

        return $form->schema([
            Forms\Components\Section::make('Informations générales')
                ->schema([
                    Forms\Components\Toggle::make('is_global')
                        ->label('Configuration globale')
                        ->helperText('Visible uniquement par les profils administrateurs')
                        ->default(false)
                        ->disabled(! $isSuperAdmin)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $set('user_id', null);
                            }
                        }),

                    Forms\Components\Select::make('user_id')
                        ->label('Utilisateur')
                        ->relationship('user', 'nom_complet')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->visible(fn () => $isSuperAdmin)
                        ->default($user?->id)
                        ->helperText('Laisser vide pour une configuration globale'),

                    Forms\Components\Toggle::make('active')
                        ->label('Actif')
                        ->default(true),

                    Forms\Components\Toggle::make('sync_enabled')
                        ->label('Synchronisation activée')
                        ->default(true),
                ])
                ->columns(2),

            Forms\Components\Section::make('Configuration IMAP')
                ->schema([
                    Forms\Components\TextInput::make('imap_host')
                        ->label('Hôte IMAP')
                        ->default('imap.gmail.com')
                        ->required(),

                    Forms\Components\TextInput::make('imap_port')
                        ->label('Port IMAP')
                        ->numeric()
                        ->default(993)
                        ->required(),

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
                        ->required(),

                    Forms\Components\TextInput::make('smtp_port')
                        ->label('Port SMTP')
                        ->numeric()
                        ->default(587)
                        ->required(),

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
                        ->revealable()
                        ->required(fn (?EmailConfiguration $record) => ! $record)
                        ->helperText('Mot de passe d’application ou du compte email'),

                    Forms\Components\TextInput::make('from_name')
                        ->label('Nom d’expéditeur')
                        ->helperText('Nom qui apparaîtra comme expéditeur'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Paramètres de synchronisation')
                ->schema([
                    Forms\Components\TextInput::make('sync_interval')
                        ->label('Intervalle de synchronisation (minutes)')
                        ->numeric()
                        ->default(5)
                        ->rules(['min:1'])
                        ->required(),

                    Forms\Components\TextInput::make('sync_limit')
                        ->label('Limite de synchronisation')
                        ->numeric()
                        ->default(50)
                        ->rules(['min:1'])
                        ->required(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_global')
                    ->label('Globale')
                    ->boolean(),

                Tables\Columns\TextColumn::make('user.nom_complet')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('smtp_host')
                    ->label('SMTP')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\IconColumn::make('sync_enabled')
                    ->label('Sync')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('active')
                    ->label('Statut')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailConfigurations::route('/'),
            'create' => Pages\CreateEmailConfiguration::route('/create'),
            'edit' => Pages\EditEmailConfiguration::route('/{record}/edit'),
        ];
    }
}
