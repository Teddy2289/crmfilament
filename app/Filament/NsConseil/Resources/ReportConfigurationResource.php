<?php

declare(strict_types=1);

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\ReportConfigurationResource\Pages;
use App\Jobs\SendDailyReportJob;
use App\Models\CrmReportConfiguration;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ReportConfigurationResource extends Resource
{
    protected static ?string $model = CrmReportConfiguration::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Configuration des rapports';

    protected static ?string $modelLabel = 'configuration de rapport';

    protected static ?string $pluralModelLabel = 'configurations des rapports';

    protected static ?int $navigationSort = 40;

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return (bool) $user && static::hasReportAdministrationAccess($user);
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('creator');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identité du rapport')
                ->description('Définissez le rapport et son état de publication.')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nom interne')
                        ->placeholder('Rapport quotidien CRM')
                        ->required()
                        ->maxLength(150)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, Forms\Set $set): void {
                            if (filled($state)) {
                                $set('slug', Str::slug($state));
                            }
                        }),

                    Forms\Components\TextInput::make('slug')
                        ->label('Identifiant technique')
                        ->helperText('Utilisé pour l’idempotence et les logs. Il doit rester stable après création.')
                        ->required()
                        ->maxLength(150)
                        ->alphaDash()
                        ->unique(ignoreRecord: true),

                    Forms\Components\Select::make('report_type')
                        ->label('Type de rapport')
                        ->options([
                            CrmReportConfiguration::TYPE_DAILY => 'Rapport quotidien CRM',
                            CrmReportConfiguration::TYPE_WEEKLY => 'Rapport hebdomadaire CRM',
                            CrmReportConfiguration::TYPE_CAMPAIGNS => 'Ventilation des campagnes',
                        ])
                        ->default(CrmReportConfiguration::TYPE_DAILY)
                        ->required(),

                    Forms\Components\Toggle::make('active')
                        ->label('Actif')
                        ->helperText('Un rapport inactif ne sera pas pris en compte par le scheduler.')
                        ->default(false)
                        ->inline(false),

                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Planification')
                ->description('Le fuseau horaire est utilisé pour calculer la prochaine exécution.')
                ->schema([
                    Forms\Components\Select::make('frequency')
                        ->label('Fréquence')
                        ->options([
                            CrmReportConfiguration::FREQUENCY_DAILY => 'Chaque jour',
                            CrmReportConfiguration::FREQUENCY_WEEKLY => 'Chaque semaine',
                        ])
                        ->default(CrmReportConfiguration::FREQUENCY_DAILY)
                        ->live()
                        ->required(),

                    Forms\Components\TimePicker::make('execution_time')
                        ->label('Heure d’exécution')
                        ->seconds(false)
                        ->native(false)
                        ->default('07:00')
                        ->required(),

                    Forms\Components\Select::make('timezone')
                        ->label('Fuseau horaire')
                        ->options(collect(DateTimeZone::listIdentifiers())
                            ->filter(fn (string $timezone): bool => in_array($timezone, [
                                'Europe/Paris',
                                'UTC',
                                'Indian/Antananarivo',
                                'America/Montreal',
                            ], true))
                            ->mapWithKeys(fn (string $timezone): array => [$timezone => $timezone])
                            ->all())
                        ->default('Europe/Paris')
                        ->searchable()
                        ->required(),

                    Forms\Components\CheckboxList::make('weekdays')
                        ->label('Jours d’exécution')
                        ->options([
                            'monday' => 'Lundi',
                            'tuesday' => 'Mardi',
                            'wednesday' => 'Mercredi',
                            'thursday' => 'Jeudi',
                            'friday' => 'Vendredi',
                            'saturday' => 'Samedi',
                            'sunday' => 'Dimanche',
                        ])
                        ->columns(4)
                        ->default(['monday'])
                        ->required(fn (Get $get): bool => $get('frequency') === CrmReportConfiguration::FREQUENCY_WEEKLY)
                        ->visible(fn (Get $get): bool => $get('frequency') === CrmReportConfiguration::FREQUENCY_WEEKLY)
                        ->columnSpanFull(),
                ])
                ->columns(3),

            Forms\Components\Section::make('Destinataires')
                ->description('Le mode sélectionné détermine la source des destinataires au moment de l’envoi.')
                ->schema([
                    Forms\Components\Select::make('recipient_mode')
                        ->label('Mode de sélection')
                        ->options([
                            CrmReportConfiguration::RECIPIENT_USERS => 'Utilisateurs précis',
                            CrmReportConfiguration::RECIPIENT_ROLES => 'Rôles CRM',
                            CrmReportConfiguration::RECIPIENT_EMAILS => 'Adresses personnalisées',
                        ])
                        ->default(CrmReportConfiguration::RECIPIENT_ROLES)
                        ->live()
                        ->required(),

                    Forms\Components\Select::make('recipient_user_ids')
                        ->label('Utilisateurs')
                        ->options(fn (): array => User::query()
                            ->whereNull('deleted_at')
                            ->whereNotNull('email')
                            ->orderBy('prenom')
                            ->orderBy('nom')
                            ->get()
                            ->mapWithKeys(fn (User $user): array => [$user->id => $user->nom_complet.' — '.$user->email])
                            ->all())
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->required(fn (Get $get): bool => $get('recipient_mode') === CrmReportConfiguration::RECIPIENT_USERS)
                        ->visible(fn (Get $get): bool => $get('recipient_mode') === CrmReportConfiguration::RECIPIENT_USERS)
                        ->columnSpanFull(),

                    Forms\Components\CheckboxList::make('recipient_roles')
                        ->label('Rôles CRM')
                        ->options([
                            User::ROLE_TELEPROSPECTEUR => 'Téléprospecteurs',
                            User::ROLE_COMMERCIAL => 'Commerciaux',
                            User::ROLE_SUPERVISEUR => 'Superviseurs',
                            'team_leader' => 'Team leaders',
                        ])
                        ->columns(2)
                        ->required(fn (Get $get): bool => $get('recipient_mode') === CrmReportConfiguration::RECIPIENT_ROLES)
                        ->visible(fn (Get $get): bool => $get('recipient_mode') === CrmReportConfiguration::RECIPIENT_ROLES)
                        ->columnSpanFull(),

                    Forms\Components\TagsInput::make('recipient_emails')
                        ->label('Adresses personnalisées')
                        ->placeholder('Ajouter une adresse puis appuyer sur Entrée')
                        ->separator(',')
                        ->nestedRecursiveRules(['email'])
                        ->required(fn (Get $get): bool => $get('recipient_mode') === CrmReportConfiguration::RECIPIENT_EMAILS)
                        ->visible(fn (Get $get): bool => $get('recipient_mode') === CrmReportConfiguration::RECIPIENT_EMAILS)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Contenu et période')
                ->schema([
                    Forms\Components\CheckboxList::make('sections')
                        ->label('Blocs inclus dans le rapport')
                        ->options([
                            'kpis' => 'KPI principaux',
                            'pipeline' => 'Pipeline commercial',
                            'statuses' => 'Ventilation des statuts',
                            'agents' => 'Performances par agent',
                            'campaigns' => 'Campagnes',
                            'callbacks' => 'Rappels et rendez-vous',
                            'errors' => 'Erreurs et alertes',
                        ])
                        ->columns(2)
                        ->default(['kpis', 'pipeline', 'statuses', 'agents'])
                        ->required()
                        ->columnSpan(2),

                    Forms\Components\Select::make('period_type')
                        ->label('Période analysée')
                        ->options([
                            CrmReportConfiguration::PERIOD_PREVIOUS_DAY => 'Jour précédent',
                            CrmReportConfiguration::PERIOD_PREVIOUS_WEEK => 'Semaine précédente',
                            CrmReportConfiguration::PERIOD_PREVIOUS_MONTH => 'Mois précédent',
                            CrmReportConfiguration::PERIOD_CURRENT => 'Période courante',
                        ])
                        ->default(CrmReportConfiguration::PERIOD_PREVIOUS_DAY)
                        ->required(),
                ])
                ->columns(3),

            Forms\Components\Section::make('État technique')
                ->description('Ces informations sont mises à jour par les jobs et ne remplacent pas le journal détaillé.')
                ->schema([
                    Forms\Components\Placeholder::make('last_run_at')
                        ->label('Dernière exécution')
                        ->content(fn (?CrmReportConfiguration $record): string => $record?->last_run_at?->format('d/m/Y H:i:s') ?? 'Jamais exécuté'),

                    Forms\Components\Placeholder::make('next_run_at')
                        ->label('Prochaine exécution')
                        ->content(fn (?CrmReportConfiguration $record): string => $record?->next_run_at?->format('d/m/Y H:i:s') ?? 'Non calculée'),

                    Forms\Components\Placeholder::make('last_status')
                        ->label('Dernier statut')
                        ->content(fn (?CrmReportConfiguration $record): string => match ($record?->last_status) {
                            CrmReportConfiguration::STATUS_SENT => 'Envoyé',
                            CrmReportConfiguration::STATUS_FAILED => 'Échec',
                            CrmReportConfiguration::STATUS_PENDING => 'En attente',
                            default => 'Aucun historique',
                        }),
                ])
                ->columns(3)
                ->visible(fn (?CrmReportConfiguration $record): bool => (bool) $record),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Rapport')
                    ->description(fn (CrmReportConfiguration $record): string => $record->report_type)
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('schedule_description')
                    ->label('Planification')
                    ->state(fn (CrmReportConfiguration $record): string => $record->scheduleDescription())
                    ->wrap(),

                Tables\Columns\TextColumn::make('recipient_mode')
                    ->label('Destinataires')
                    ->formatStateUsing(fn (string $state, CrmReportConfiguration $record): string => match ($state) {
                        CrmReportConfiguration::RECIPIENT_USERS => "Utilisateurs ({$record->recipientCount()})",
                        CrmReportConfiguration::RECIPIENT_ROLES => "Rôles ({$record->recipientCount()})",
                        CrmReportConfiguration::RECIPIENT_EMAILS => "Adresses ({$record->recipientCount()})",
                        default => 'Non configurés',
                    })
                    ->badge(),

                Tables\Columns\IconColumn::make('active')
                    ->label('Actif')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_status')
                    ->label('Dernier statut')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        CrmReportConfiguration::STATUS_SENT => 'Envoyé',
                        CrmReportConfiguration::STATUS_FAILED => 'Échec',
                        CrmReportConfiguration::STATUS_PENDING => 'En attente',
                        default => 'Jamais exécuté',
                    })
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        CrmReportConfiguration::STATUS_SENT => 'success',
                        CrmReportConfiguration::STATUS_FAILED => 'danger',
                        CrmReportConfiguration::STATUS_PENDING => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('last_run_at')
                    ->label('Dernière exécution')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Jamais'),

                Tables\Columns\TextColumn::make('creator.nom_complet')
                    ->label('Créé par')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('report_type')
                    ->label('Type')
                    ->options([
                        CrmReportConfiguration::TYPE_DAILY => 'Quotidien',
                        CrmReportConfiguration::TYPE_WEEKLY => 'Hebdomadaire',
                        CrmReportConfiguration::TYPE_CAMPAIGNS => 'Campagnes',
                    ]),

                Tables\Filters\SelectFilter::make('active')
                    ->label('État')
                    ->options([
                        '1' => 'Actifs',
                        '0' => 'Désactivés',
                    ]),

                Tables\Filters\SelectFilter::make('last_status')
                    ->label('Dernier statut')
                    ->options([
                        CrmReportConfiguration::STATUS_SENT => 'Envoyé',
                        CrmReportConfiguration::STATUS_FAILED => 'Échec',
                        CrmReportConfiguration::STATUS_PENDING => 'En attente',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('test')
                    ->label('Envoyer un test')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->form([
                        Forms\Components\TextInput::make('test_email')
                            ->label('Adresse de test')
                            ->email()
                            ->required()
                            ->default(fn (): ?string => Auth::user()?->email)
                            ->helperText('Le rapport est généré pour l’utilisateur connecté puis envoyé uniquement à cette adresse.'),
                    ])
                    ->action(function (CrmReportConfiguration $record, array $data): void {
                        $userId = Auth::id();

                        if (! $userId) {
                            Notification::make()
                                ->title('Session expirée')
                                ->body('Reconnectez-vous avant de lancer un test.')
                                ->danger()
                                ->send();
                            return;
                        }

                        SendDailyReportJob::dispatch(
                            userId: $userId,
                            reportKey: 'test-'.$record->slug.'-'.now()->format('YmdHis'),
                            recipientEmail: $data['test_email'],
                        );

                        Notification::make()
                            ->title('Test placé en file')
                            ->body('Le rapport sera envoyé à '.$data['test_email'].'. Consultez le journal pour le résultat.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (CrmReportConfiguration $record): string => $record->active ? 'Désactiver' : 'Activer')
                    ->icon(fn (CrmReportConfiguration $record): string => $record->active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn (CrmReportConfiguration $record): string => $record->active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (CrmReportConfiguration $record): bool => $record->update(['active' => ! $record->active])),
                Tables\Actions\ReplicateAction::make()
                    ->excludeAttributes(['slug', 'last_run_at', 'next_run_at', 'last_status', 'last_error'])
                    ->mutateRecordDataUsing(function (array $data): array {
                        $data['name'] = ($data['name'] ?? 'Rapport').' — copie';
                        $data['slug'] = null;
                        $data['active'] = false;
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activer la sélection')
                        ->icon('heroicon-o-play')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Désactiver la sélection')
                        ->icon('heroicon-o-pause')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['active' => false])),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistFiltersInSession()
            ->persistSearchInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReportConfigurations::route('/'),
            'create' => Pages\CreateReportConfiguration::route('/create'),
            'view' => Pages\ViewReportConfiguration::route('/{record}'),
            'edit' => Pages\EditReportConfiguration::route('/{record}/edit'),
        ];
    }

    private static function hasReportAdministrationAccess(User $user): bool
    {
        return (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
            || (method_exists($user, 'isAdmin') && $user->isAdmin())
            || (method_exists($user, 'isSuperviseur') && $user->isSuperviseur())
            || in_array($user->role_cache, [
                User::ROLE_ADMIN,
                User::ROLE_SUPERVISEUR,
                'super_admin',
                'superadmin',
            ], true);
    }
}
