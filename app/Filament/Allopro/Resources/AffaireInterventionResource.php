<?php

namespace App\Filament\Allopro\Resources;

use App\Enums\CanalContactPreferentiel;
use App\Enums\StatutAffaireIntervention;
use App\Filament\Allopro\Resources\AffaireInterventionResource\Pages\CreateAffaireIntervention;
use App\Filament\Allopro\Resources\AffaireInterventionResource\Pages\EditAffaireIntervention;
use App\Filament\Allopro\Resources\AffaireInterventionResource\Pages\ListAffaireInterventions;
use App\Filament\Allopro\Resources\AffaireInterventionResource\Pages\ViewAffaireIntervention;
use App\Models\AffaireIntervention;
use App\Support\UsesResourcePermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AffaireInterventionResource extends Resource
{
    use UsesResourcePermissions;

    protected static ?string $model = AffaireIntervention::class;

    protected static string $permissionPrefix = 'affaire_interventions';

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Affaires / Interventions';

    protected static ?string $navigationGroup = 'Tickets';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'reference';

    // ── Badge navigation ─────────────────────────────────────────

    public static function getNavigationBadge(): ?string
    {
        $count = AffaireIntervention::actives()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        $enAttente = AffaireIntervention::enAttente()->count();

        return $enAttente > 0 ? 'warning' : 'primary';
    }

    // ── Formulaire ───────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Identification')
                ->icon('heroicon-o-identification')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('reference')
                        ->label('Référence')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Généré automatiquement'),

                    Forms\Components\Select::make('statut')
                        ->label('Statut')
                        ->options(
                            collect(StatutAffaireIntervention::cases())
                                ->mapWithKeys(fn ($e) => [$e->value => $e->label()])
                                ->toArray()
                        )
                        ->native(false)
                        ->required()
                        ->default(StatutAffaireIntervention::EnAttente->value),

                    Forms\Components\TextInput::make('numero_tentative')
                        ->label('Tentative n°')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->default(1),
                ]),

            Forms\Components\Section::make('Ticket & Dispatch')
                ->icon('heroicon-o-ticket')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('ticket_id')
                        ->label('Ticket')
                        ->relationship('ticket', 'reference')
                        ->getOptionLabelFromRecordUsing(
                            fn ($record) => $record
                                ? $record->reference.' — '.($record->contactParticulier?->nom ?? '—')
                                : '—'
                        )
                        ->searchable(['reference'])
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('artisan_id')
                        ->label('Artisan')
                        ->relationship(
                            name: 'artisan',
                            titleAttribute: 'nom',
                            modifyQueryUsing: fn (Builder $query) => $query->where('statut_compte', 'actif')

                        )
                        ->getOptionLabelFromRecordUsing(function ($record) {
                            if (! $record) {
                                return '—';
                            }

                            return $record->nom_complet.' — '.($record->corps_de_metier?->label() ?? '');
                        })
                        ->searchable(['nom', 'prenom'])
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('operateur_dispatch_id')
                        ->label('Opérateur dispatch')
                        ->relationship('operateurDispatch', 'name') // ← 'name' et non 'nom'
                        ->getOptionLabelFromRecordUsing(
                            fn ($record) => $record
                                ? trim(($record->prenom ?? $record->name ?? '').' '.($record->nom ?? ''))
                                : '—'
                        )
                        ->searchable()
                        ->default(fn () => auth()->id()),

                    Forms\Components\Select::make('canal_notification')
                        ->label('Canal de notification artisan')
                        ->options(
                            collect(CanalContactPreferentiel::cases())
                                ->mapWithKeys(fn ($e) => [$e->value => $e->label()])
                                ->toArray()
                        )
                        ->native(false)
                        ->default(CanalContactPreferentiel::Appel->value),
                ]),

            Forms\Components\Section::make('Planning')
                ->icon('heroicon-o-calendar')
                ->columns(3)
                ->schema([
                    Forms\Components\DateTimePicker::make('date_rdv_prevue')
                        ->label('Date RDV prévue')
                        ->native(false)
                        ->nullable(),

                    Forms\Components\TextInput::make('creneau_debut')
                        ->label('Créneau début')
                        ->type('time')
                        ->nullable(),

                    Forms\Components\TextInput::make('creneau_fin')
                        ->label('Créneau fin')
                        ->type('time')
                        ->nullable(),

                    Forms\Components\DateTimePicker::make('date_notification_artisan')
                        ->label('Notifié le')
                        ->native(false)
                        ->nullable(),

                    Forms\Components\DateTimePicker::make('date_confirmation_artisan')
                        ->label('Confirmé le')
                        ->native(false)
                        ->nullable(),

                    Forms\Components\TextInput::make('delai_confirmation_minutes')
                        ->label('Délai confirmation (min)')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->suffix('min'),
                ]),

            Forms\Components\Section::make('Réalisation')
                ->icon('heroicon-o-wrench-screwdriver')
                ->columns(2)
                ->collapsible()
                ->schema([
                    Forms\Components\DateTimePicker::make('date_debut_reelle')
                        ->label('Début réel')
                        ->native(false)
                        ->nullable(),

                    Forms\Components\DateTimePicker::make('date_fin_reelle')
                        ->label('Fin réelle')
                        ->native(false)
                        ->nullable(),

                    Forms\Components\TextInput::make('duree_reelle_minutes')
                        ->label('Durée réelle')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->suffix('min'),

                    Forms\Components\Textarea::make('description_travaux_realises')
                        ->label('Description des travaux réalisés')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('compte_rendu_artisan')
                        ->label("Compte-rendu de l'artisan")
                        ->rows(4)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Validation client')
                ->icon('heroicon-o-hand-thumb-up')
                ->columns(3)
                ->collapsible()
                ->schema([
                    Forms\Components\Toggle::make('signature_client')
                        ->label('Bon d\'intervention signé')
                        ->helperText('Le client a signé le bon d\'intervention'),

                    Forms\Components\DateTimePicker::make('date_signature_client')
                        ->label('Signé le')
                        ->native(false)
                        ->nullable(),

                    Forms\Components\Select::make('satisfaction_immediate')
                        ->label('Satisfaction immédiate (1–5)')
                        ->options([
                            1 => '1 — Très insatisfait',
                            2 => '2 — Insatisfait',
                            3 => '3 — Neutre',
                            4 => '4 — Satisfait',
                            5 => '5 — Très satisfait',
                        ])
                        ->native(false)
                        ->nullable(),
                ]),

            Forms\Components\Section::make('Notes & Motifs')
                ->icon('heroicon-o-document-text')
                ->columns(2)
                ->collapsible()
                ->schema([
                    Forms\Components\Textarea::make('notes_dispatch')
                        ->label('Notes dispatch P3')
                        ->rows(3),

                    Forms\Components\Textarea::make('notes_intervention')
                        ->label('Notes intervention')
                        ->rows(3),

                    Forms\Components\Textarea::make('motif_annulation')
                        ->label("Motif d'annulation / d'échec")
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // ── Table ────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Réf.')
                    ->searchable()
                    ->weight('semibold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof StatutAffaireIntervention ? $state->label() : $state)
                    ->color(fn ($state) => $state instanceof StatutAffaireIntervention ? $state->color() : 'gray')
                    ->icon(fn ($state) => $state instanceof StatutAffaireIntervention ? $state->icon() : null),

                Tables\Columns\TextColumn::make('ticket.reference')
                    ->label('Ticket')
                    ->searchable()
                    ->badge()
                    ->color('gray')
                    ->url(
                        fn ($record) => $record->ticket_id
                            ? TicketResource::getUrl('view', ['record' => $record->ticket_id])
                            : null
                    ),

                Tables\Columns\TextColumn::make('artisan.nom')
                    ->label('Artisan')
                    ->formatStateUsing(fn ($state, $record) => $record->artisan?->nom_complet ?? '—')
                    ->description(fn ($record) => $record->artisan?->corps_de_metier?->label())
                    ->searchable(['artisan.nom', 'artisan.prenom'])
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('date_rdv_prevue')
                    ->label('RDV prévu')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(
                        fn ($record) => $record->creneau_debut && $record->creneau_fin
                            ? $record->creneau_debut.' – '.$record->creneau_fin
                            : null
                    )
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('sla_respectee')
                    ->label('SLA P4')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-exclamation-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn ($record) => $record->delai_confirmation_formate),

                Tables\Columns\TextColumn::make('delai_confirmation_minutes')
                    ->label('Délai confirm.')
                    ->formatStateUsing(fn ($state) => $state ? $state.' min' : '—')
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state <= 5 => 'success',
                        $state <= 30 => 'warning',
                        default => 'danger',
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('numero_tentative')
                    ->label('Tentative')
                    ->badge()
                    ->color(fn ($state) => $state > 1 ? 'warning' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('satisfaction_immediate')
                    ->label('Satisfaction')
                    ->formatStateUsing(fn ($state) => $state ? $state.'/5' : '—')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state >= 4 => 'success',
                        $state === 3 => 'warning',
                        default => 'danger',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('operateurDispatch.prenom')
                    ->label('Dispatcher')
                    ->formatStateUsing(
                        fn ($state, $record) => trim(($record->operateurDispatch?->prenom ?? '').' '.($record->operateurDispatch?->nom ?? '')) ?: '—'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('statut')
                    ->label('Statut')
                    ->options(
                        collect(StatutAffaireIntervention::cases())
                            ->mapWithKeys(fn ($e) => [$e->value => $e->label()])
                            ->toArray()
                    )
                    ->native(false)
                    ->multiple(),

                Tables\Filters\Filter::make('en_retard_confirmation')
                    ->label('En retard confirmation (>30 min)')
                    ->query(
                        fn (Builder $q) => $q
                            ->where('statut', StatutAffaireIntervention::EnAttente->value)
                            ->where('date_notification_artisan', '<', now()->subMinutes(30))
                    ),

                Tables\Filters\Filter::make('du_jour')
                    ->label("Aujourd'hui")
                    ->query(fn (Builder $q) => $q->whereDate('date_rdv_prevue', today())),

                Tables\Filters\Filter::make('echec_ou_annulee')
                    ->label('Échecs & Annulations')
                    ->query(fn (Builder $q) => $q->whereIn('statut', [
                        StatutAffaireIntervention::Annulee->value,
                        StatutAffaireIntervention::Echec->value,
                    ])),
            ])

            ->actions([
                Tables\Actions\Action::make('confirmer')
                    ->label('Confirmer')
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->visible(fn (AffaireIntervention $r) => $r->statut === StatutAffaireIntervention::EnAttente)
                    ->requiresConfirmation()
                    ->modalHeading("Confirmer la venue de l'artisan ?")
                    ->action(function (AffaireIntervention $record) {
                        $record->confirmerParArtisan();
                        Notification::make()->title('Artisan confirmé')->success()->send();
                    }),

                Tables\Actions\Action::make('demarrer')
                    ->label('Démarrer')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->visible(fn (AffaireIntervention $r) => $r->statut === StatutAffaireIntervention::Confirmee)
                    ->requiresConfirmation()
                    ->modalHeading("Démarrer l'intervention ?")
                    ->action(function (AffaireIntervention $record) {
                        $record->demarrer();
                        Notification::make()->title('Intervention démarrée')->success()->send();
                    }),

                Tables\Actions\Action::make('finaliser')
                    ->label('Finaliser')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('teal')
                    ->visible(fn (AffaireIntervention $r) => $r->statut === StatutAffaireIntervention::EnCours)
                    ->form([
                        Forms\Components\Textarea::make('compte_rendu_artisan')
                            ->label("Compte-rendu de l'artisan")
                            ->rows(4)
                            ->required(),
                        Forms\Components\Textarea::make('description_travaux_realises')
                            ->label('Description des travaux réalisés')
                            ->rows(3),
                    ])
                    ->action(function (AffaireIntervention $record, array $data) {
                        $record->finaliserParArtisan(
                            $data['compte_rendu_artisan'],
                            $data['description_travaux_realises'] ?? null
                        );
                        Notification::make()->title('Intervention finalisée')->success()->send();
                    }),

                Tables\Actions\Action::make('annuler')
                    ->label('Annuler')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (AffaireIntervention $r) => $r->statut?->estActive())
                    ->form([
                        Forms\Components\Textarea::make('motif')
                            ->label("Motif d'annulation")
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function (AffaireIntervention $record, array $data) {
                        $record->annuler($data['motif']);
                        Notification::make()->title('Affaire annulée')->warning()->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasRole('responsable_plateau')),
                ]),
            ])

            ->emptyStateIcon('heroicon-o-wrench-screwdriver')
            ->emptyStateHeading('Aucune affaire d\'intervention')
            ->emptyStateDescription('Les affaires sont créées lors du dispatch artisan (P3).')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAffaireInterventions::route('/'),
            'create' => CreateAffaireIntervention::route('/create'),
            'view' => ViewAffaireIntervention::route('/{record}'),
            'edit' => EditAffaireIntervention::route('/{record}/edit'),
        ];
    }

}
