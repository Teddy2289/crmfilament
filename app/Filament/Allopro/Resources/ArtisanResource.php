<?php

namespace App\Filament\Allopro\Resources;

use App\Enums\CanalAlerte;
use App\Enums\CorpsDeMetier;
use App\Enums\StatutCompteArtisan;
use App\Filament\Allopro\Resources\ArtisanResource\Pages;
use App\Filament\Allopro\Resources\ArtisanResource\Pages\CreateArtisan;
use App\Filament\Allopro\Resources\ArtisanResource\Pages\EditArtisan;
use App\Filament\Allopro\Resources\ArtisanResource\Pages\ListArtisans;
use App\Filament\Allopro\Resources\ArtisanResource\Pages\ViewArtisan;
use App\Filament\Allopro\Resources\ArtisanResource\RelationManagers\ProspectionRelationManager;
use App\Filament\Allopro\Resources\ArtisanResource\RelationManagers\RapportsSatisfactionRelationManager;
use App\Filament\Allopro\Resources\ArtisanResource\RelationManagers\TicketsRelationManager;
use App\Filament\Shared\Actions\SendEmailAction;
use App\Filament\Shared\RelationManagers\SentEmailsRelationManager;
use App\Mail\BienvenuArtisanMail;
use App\Models\Artisan;
use Illuminate\Support\Facades\Mail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ArtisanResource extends Resource
{
    protected static ?string $model = Artisan::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Artisans';

    protected static ?string $navigationGroup = 'Artisans';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nom_complet';

    // ── Badges de navigation ─────────────────────────────────────
    public static function getNavigationBadge(): ?string
    {
        return (string) Artisan::enAttente()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
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
                        ->label('Prénom')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\TextInput::make('nom')
                        ->label('Nom')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\TextInput::make('raison_sociale')
                        ->label('Raison sociale')
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('siret')
                        ->label('SIRET')
                        ->length(14)
                        ->numeric()
                        ->unique(ignoreRecord: true)
                        ->helperText('14 chiffres obligatoires')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Activité')
                ->icon('heroicon-o-briefcase')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('corps_de_metier')
                        ->label('Corps de métier')
                        ->required()
                        ->options(CorpsDeMetier::class)
                        ->searchable()
                        ->native(false),

                    Forms\Components\Textarea::make('zone_intervention')
                        ->label('Zone d\'intervention')
                        ->required()
                        ->rows(2)
                        ->helperText('Codes postaux ou départements couverts'),
                ]),

            Forms\Components\Section::make('Contact')
                ->icon('heroicon-o-phone')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('telephone_principal')
                        ->label('Téléphone principal')
                        ->required()
                        ->tel()
                        ->maxLength(20),

                    Forms\Components\TextInput::make('telephone_secondaire')
                        ->label('Téléphone secondaire')
                        ->tel()
                        ->maxLength(20),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->required()
                        ->email()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('canal_alerte')
                        ->label('Canal d\'alerte')
                        ->required()
                        ->options(CanalAlerte::class)
                        ->native(false)
                        ->default(CanalAlerte::LesDeux),
                ]),

            Forms\Components\Section::make('Compte & Statut')
                ->icon('heroicon-o-cog-6-tooth')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('statut_compte')
                        ->label('Statut')
                        ->required()
                        ->options(StatutCompteArtisan::class)
                        ->native(false)
                        ->default(StatutCompteArtisan::EnAttenteActivation),

                    Forms\Components\Toggle::make('agenda_disponibilites')
                        ->label('Agenda configuré')
                        ->helperText('L\'artisan a fourni ses disponibilités'),

                    Forms\Components\DatePicker::make('date_souscription')
                        ->label('Date de souscription')
                        ->default(now())
                        ->native(false),

                    Forms\Components\DatePicker::make('date_activation')
                        ->label('Date d\'activation')
                        ->native(false),
                ]),

            Forms\Components\Section::make('Notes internes')
                ->icon('heroicon-o-document-text')
                ->collapsible()
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(4)
                        ->columnSpanFull(),
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
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn(Artisan $r) => $r->raison_sociale),

                Tables\Columns\TextColumn::make('prenom')
                    ->label('Prénom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('corps_de_metier')
                    ->label('Métier')
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state->label())
                    ->color(fn($state) => $state->color()),

                Tables\Columns\TextColumn::make('statut_compte')
                    ->label('Statut')
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state->label())
                    ->color(fn($state) => $state->color()),

                Tables\Columns\TextColumn::make('telephone_principal')
                    ->label('Téléphone')
                    ->copyable()
                    ->badge()
                    ->color('green')
                    ->icon('heroicon-o-phone'),

                Tables\Columns\IconColumn::make('agenda_disponibilites')
                    ->label('Agenda')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('nb_interventions')
                    ->label('Interventions')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('note_moyenne')
                    ->label('Note moy.')
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state ? number_format($state, 1) . ' / 10' : '—')
                    ->color(fn($state) => match (true) {
                        $state >= 8 => 'success',
                        $state >= 6 => 'warning',
                        $state !== null => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('date_souscription')
                    ->label('Souscription')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('zone_intervention')
                    ->label('Zone')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('statut_compte')
                    ->label('Statut')
                    ->options(StatutCompteArtisan::class)
                    ->native(false),

                Tables\Filters\SelectFilter::make('corps_de_metier')
                    ->label('Métier')
                    ->options(CorpsDeMetier::class)
                    ->native(false)
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('agenda_disponibilites')
                    ->label('Agenda configuré'),

                Tables\Filters\Filter::make('prioritaires')
                    ->label('Métiers prioritaires')
                    ->query(fn(Builder $q) => $q->prioritaires()),

                Tables\Filters\Filter::make('bien_notes')
                    ->label('Bien notés (≥ 8)')
                    ->query(fn(Builder $q) => $q->bienNotes(8)),
            ])

            ->actions([
                Tables\Actions\Action::make('activer')
                    ->label('Activer')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Artisan $r) => $r->estEnAttente())
                    ->requiresConfirmation()
                    ->action(function (Artisan $record) {
                        $record->activer();
                        Notification::make()
                            ->title('Artisan activé')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('suspendre')
                    ->label('Suspendre')
                    ->icon('heroicon-o-pause-circle')
                    ->color('danger')
                    ->visible(fn(Artisan $r) => $r->estActif())
                    ->form([
                        Forms\Components\Textarea::make('motif')
                            ->label('Motif de suspension')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Artisan $record, array $data) {
                        $record->suspendre($data['motif']);
                        Notification::make()
                            ->title('Artisan suspendu')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),

                // ── Actions email contextuelles ──────────────────────
                Tables\Actions\Action::make('envoyer_bienvenue')
                    ->label('Envoyer bienvenue')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn(Artisan $r) => !empty($r->email))
                    ->requiresConfirmation()
                    ->modalHeading('Envoyer l\'email de bienvenue ?')
                    ->modalDescription(fn(Artisan $r) => 'Destinataire : ' . $r->email)
                    ->action(function (Artisan $record) {
                        $mailable = new BienvenuArtisanMail($record);
                        Mail::to($record->email)->send($mailable);
                        $mailable->logEnvoi($record, $record->email);
                        Notification::make()->title('Email de bienvenue envoyé')->success()->send();
                    }),

                SendEmailAction::make(fn(Artisan $r) => $r->email),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activer_selection')
                        ->label('Activer la sélection')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->activer();
                            Notification::make()
                                ->title(count($records) . ' artisan(s) activé(s)')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])

            ->emptyStateIcon('heroicon-o-wrench-screwdriver')
            ->emptyStateHeading('Aucun artisan')
            ->emptyStateDescription('Commencez par ajouter un artisan ou importez une liste.')
            ->emptyStateActions([
                Tables\Actions\Action::make('create')
                    ->label('Ajouter un artisan')
                    ->icon('heroicon-o-plus')
                    ->url(static::getUrl('create')),
            ]);
    }

    // ── Pages ────────────────────────────────────────────────────
    public static function getPages(): array
    {
        return [
            'index' => ListArtisans::route('/'),
            'create' => CreateArtisan::route('/create'),
            'view' => ViewArtisan::route('/{record}'),
            'edit' => EditArtisan::route('/{record}/edit'),
        ];
    }

    // ── Relations ────────────────────────────────────────────────
    public static function getRelations(): array
    {
        return [
            TicketsRelationManager::class,
            ProspectionRelationManager::class,
            RapportsSatisfactionRelationManager::class,
            SentEmailsRelationManager::class,
        ];
    }

    // ── Permissions par rôle ─────────────────────────────────────
    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['responsable_plateau', 'back_office']) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['responsable_plateau', 'back_office']) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasRole('responsable_plateau') ?? false;
    }
}
