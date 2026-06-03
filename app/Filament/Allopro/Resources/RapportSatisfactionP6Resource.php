<?php

namespace App\Filament\Allopro\Resources;

use App\Enums\StatutClotureP6;
use App\Enums\TicketStatut;
use App\Filament\Allopro\Resources\RapportSatisfactionP6Resource\Pages\CreateRapportSatisfactionP6;
use App\Filament\Allopro\Resources\RapportSatisfactionP6Resource\Pages\ListRapportSatisfactionP6s;
use App\Filament\Allopro\Resources\RapportSatisfactionP6Resource\Pages\ViewRapportSatisfactionP6;
use App\Filament\Allopro\Resources\RapportSatisfactionP6Resource\RelationManagers\ReclamationRelationManager;
use App\Models\RapportSatisfactionP6;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RapportSatisfactionP6Resource extends Resource
{
    protected static ?string $model               = RapportSatisfactionP6::class;
    protected static ?string $navigationIcon      = 'heroicon-o-star';
    protected static ?string $navigationLabel     = 'Satisfaction P6';
    protected static ?string $navigationGroup     = 'Qualité & Suivi';
    protected static ?int    $navigationSort      = 1;
    protected static ?string $recordTitleAttribute = 'id';

    // ── Badge : appels J+1 à planifier ───────────────────────────
    public static function getNavigationBadge(): ?string
    {
        $count = \App\Models\Ticket::query()->where('statut', TicketStatut::InterventionRealisee->value)
            ->doesntHave('rapportSatisfaction')
            ->where('updated_at', '<', now()->subDay())
            ->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    // ── Formulaire ───────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Ticket & Contexte')
                ->icon('heroicon-o-ticket')
                ->columns(2)
                ->schema([
                    // ✅ Utiliser relationship() comme dans DevisResource
                    Forms\Components\Select::make('ticket_id')
                        ->label('Ticket')
                        ->options(
                            \App\Models\Ticket::query()
                                ->where('statut', TicketStatut::InterventionRealisee->value)
                                ->with('contactParticulier')
                                ->get()
                                ->mapWithKeys(fn($ticket) => [
                                    $ticket->id => $ticket->reference . ' - ' .
                                        ($ticket->contactParticulier
                                            ? trim($ticket->contactParticulier->prenom . ' ' . $ticket->contactParticulier->nom)
                                            : 'Sans client')
                                ])
                                ->toArray()
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if (!$state) return;
                            $ticket = \App\Models\Ticket::with(['artisan', 'contactParticulier'])->find($state);
                            if ($ticket) {
                                $set('artisan_id', $ticket->artisan_id);
                                $set('operateur_id', auth()->id());
                            }
                        })
                        ->helperText('Seuls les tickets "Intervention réalisée" sont listés'),

                    // ✅ Garder relationship() aussi pour artisan_id
                    Forms\Components\Select::make('artisan_id')
                        ->label('Artisan concerné')
                        ->relationship('artisan', 'nom')
                        ->getOptionLabelFromRecordUsing(
                            fn($record) =>
                            $record->nom_complet . ($record->siret ? ' — ' . $record->siret : '')
                        )
                        ->searchable(['nom', 'prenom', 'siret'])
                        ->required(),
                ]),

            Forms\Components\Section::make('Appel J+1')
                ->icon('heroicon-o-phone')
                ->columns(2)
                ->schema([
                    Forms\Components\DatePicker::make('date_appel_j1')
                        ->label('Date de l\'appel J+1')
                        ->required()
                        ->native(false)
                        ->default(now()->addDay())
                        ->maxDate(today()->addDays(3))
                        ->helperText('Dans les 72h suivant l\'intervention — SLA CDC'),

                    // ✅ Garder relationship() pour operateur_id
                    Forms\Components\Select::make('operateur_id')
                        ->label('Agent Back-Office')
                        ->relationship('operateur', 'nom')
                        ->getOptionLabelFromRecordUsing(
                            fn($record) =>
                            trim($record->prenom . ' ' . $record->nom)
                        )
                        ->searchable(['nom', 'prenom'])
                        ->default(auth()->id())
                        ->required(),
                ]),

            Forms\Components\Section::make('Score NPS')
                ->icon('heroicon-o-chart-bar')
                ->columns(1)
                ->schema([
                    Forms\Components\Select::make('note_nps')
                        ->label('Note NPS (0 → 10)')
                        ->options(array_combine(range(0, 10), range(0, 10)))
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state === null) return;
                            $set('statut_cloture', match (true) {
                                $state >= 8 => 'satisfait',
                                $state >= 6 => 'suivi_qualite_requis',
                                default     => 'reclamation_ouverte',
                            });
                        })
                        ->helperText(fn(Get $get) => match (true) {
                            ($get('note_nps') ?? -1) >= 9 => '⭐ Promoteur — Client très satisfait',
                            ($get('note_nps') ?? -1) >= 7 => '😐 Passif — Satisfaction neutre',
                            ($get('note_nps') ?? -1) >= 6 => '⚠️ À surveiller — Suivi qualité P7',
                            ($get('note_nps') ?? -1) >= 0 => '🚨 Détracteur — Réclamation P8 déclenchée automatiquement',
                            default => 'Saisir une note de 0 à 10',
                        }),

                    Forms\Components\Select::make('statut_cloture')
                        ->label('Statut de clôture (auto-calculé)')
                        ->options([
                            'satisfait'            => '✅ Satisfait — Clôture définitive',
                            'suivi_qualite_requis' => '⚠️ Suivi qualité requis — Alerte P7',
                            'reclamation_ouverte'  => '🚨 Réclamation ouverte — P8 déclenché',
                        ])
                        ->required()
                        ->native(false)
                        ->helperText('Calculé automatiquement depuis le NPS — modifiable si besoin'),

                    Forms\Components\Textarea::make('verbatim_client')
                        ->label('Verbatim client (mot à mot)')
                        ->rows(4)
                        ->placeholder('Retranscription exacte des mots du client…'),

                    Forms\Components\Toggle::make('feedback_artisan')
                        ->label('Feedback transmis à l\'artisan')
                        ->default(false)
                        ->inline(false),
                ]),
        ]);
    }

    // ── Table ────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_appel_j1', 'desc')
            ->poll('60s')
            ->columns([
                Tables\Columns\TextColumn::make('ticket.reference')
                    ->label('Ticket')
                    ->searchable()
                    ->weight('semibold')
                    ->url(fn($record) => $record->ticket_id
                        ? TicketResource::getUrl('view', ['record' => $record->ticket_id])
                        : null)
                    ->color('primary'),

                Tables\Columns\TextColumn::make('note_nps')
                    ->label('NPS')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state . ' / 10')
                    ->color(fn($state) => match (true) {
                        $state >= 9 => 'success',
                        $state >= 7 => 'warning',
                        $state >= 6 => 'orange',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('classification_nps')
                    ->label('Classification')
                    ->state(fn($record) => $record->getClassificationNPS())
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Promoteur'   => 'success',
                        'Passif'      => 'warning',
                        'Détracteur'  => 'danger',
                        default       => 'gray',
                    }),

                Tables\Columns\TextColumn::make('statut_cloture')
                    ->label('Clôture')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'satisfait'            => 'Satisfait',
                        'suivi_qualite_requis' => 'Suivi qualité',
                        'reclamation_ouverte'  => 'Réclamation P8',
                        default                => $state,
                    })
                    ->color(fn($state) => match ($state) {
                        'satisfait'            => 'success',
                        'suivi_qualite_requis' => 'warning',
                        'reclamation_ouverte'  => 'danger',
                        default                => 'gray',
                    }),

                Tables\Columns\TextColumn::make('artisan_nom')
                    ->label('Artisan')
                    ->getStateUsing(fn($record) => $record->artisan?->nom_complet ?? '—')
                    ->description(fn($record) => $record->artisan?->corps_de_metier?->label()),

                Tables\Columns\TextColumn::make('verbatim_client')
                    ->label('Verbatim')
                    ->limit(50)
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('feedback_artisan')
                    ->label('Feedback')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn($record) => $record->feedback_artisan ? 'Transmis' : '⚠️ Non transmis'),

                Tables\Columns\TextColumn::make('reclamation_statut')
                    ->label('P8')
                    ->getStateUsing(fn($record) => $record->reclamation?->statut)
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? $state->label() : '—')
                    ->color(fn($state) => $state ? $state->color() : 'gray')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('date_appel_j1')
                    ->label('Appel J+1')
                    ->date('d/m/Y')
                    ->sortable(),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('statut_cloture')
                    ->label('Statut')
                    ->options([
                        'satisfait'            => 'Satisfait',
                        'suivi_qualite_requis' => 'Suivi qualité requis',
                        'reclamation_ouverte'  => 'Réclamation ouverte',
                    ])
                    ->native(false),

                Tables\Filters\Filter::make('detracteurs')
                    ->label('Détracteurs (NPS ≤ 6)')
                    ->query(fn(Builder $q) => $q->detracteurs()),

                Tables\Filters\Filter::make('sans_feedback')
                    ->label('Feedback non transmis')
                    ->query(fn(Builder $q) => $q->where('feedback_artisan', false)),

                Tables\Filters\Filter::make('du_mois')
                    ->label('Ce mois')
                    ->query(fn(Builder $q) => $q->duMois()),
            ])

            ->actions([
                Tables\Actions\Action::make('transmettre_feedback')
                    ->label('Feedback transmis')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('info')
                    ->visible(fn(RapportSatisfactionP6 $record) => !$record->feedback_artisan)
                    ->requiresConfirmation()
                    ->modalHeading('Confirmer la transmission du feedback à l\'artisan ?')
                    ->action(function (RapportSatisfactionP6 $record) {
                        $record->update(['feedback_artisan' => true]);
                        Notification::make()
                            ->title('Feedback marqué comme transmis')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('ouvrir_p8')
                    ->label('Ouvrir P8')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(
                        fn(RapportSatisfactionP6 $record) =>
                        $record->declencheP8() && !$record->reclamation()->exists()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Ouvrir une réclamation P8 ?')
                    ->modalDescription('Une réclamation sera créée avec délai de résolution à J+5 ouvrés.')
                    ->action(function (RapportSatisfactionP6 $record) {
                        $record->ouvrirReclamationP8();
                        Notification::make()
                            ->title('Réclamation P8 ouverte — délai J+5')
                            ->danger()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
            ])

            ->emptyStateIcon('heroicon-o-star')
            ->emptyStateHeading('Aucun rapport P6')
            ->emptyStateDescription('Les rapports sont créés après chaque intervention réalisée (appel J+1).')
            ->striped();
    }

    // ── Infolist ─────────────────────────────────────────────────
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            Section::make('Score NPS')
                ->icon('heroicon-o-chart-bar')
                ->columns(4)
                ->schema([
                    TextEntry::make('note_nps')
                        ->label('Note NPS')
                        ->formatStateUsing(fn($state) => $state . ' / 10')
                        ->badge()
                        ->color(fn($state) => match (true) {
                            $state >= 9 => 'success',
                            $state >= 7 => 'warning',
                            default => 'danger',
                        }),

                    TextEntry::make('classification_nps')
                        ->label('Classification')
                        ->getStateUsing(fn($record) => $record->getClassificationNPS())
                        ->badge()
                        ->color(fn($state) => match ($state) {
                            'Promoteur'  => 'success',
                            'Passif'     => 'warning',
                            'Détracteur' => 'danger',
                            default      => 'gray',
                        }),

                    TextEntry::make('statut_cloture')
                        ->label('Statut clôture')
                        ->formatStateUsing(fn($state) => match ($state) {
                            'satisfait'            => '✅ Satisfait',
                            'suivi_qualite_requis' => '⚠️ Suivi qualité',
                            'reclamation_ouverte'  => '🚨 Réclamation P8',
                            default                => $state,
                        }),

                    IconEntry::make('feedback_artisan')
                        ->label('Feedback transmis')
                        ->boolean()
                        ->trueColor('success')
                        ->falseColor('danger'),
                ]),

            Section::make('Contexte')
                ->columns(3)
                ->schema([
                    TextEntry::make('ticket.reference')
                        ->label('Ticket')
                        ->url(fn($record) => $record->ticket_id
                            ? TicketResource::getUrl('view', ['record' => $record->ticket_id])
                            : null),

                    TextEntry::make('artisan.nom')
                        ->label('Artisan')
                        ->formatStateUsing(fn($record) => $record->artisan?->nom_complet ?? '—'),

                    TextEntry::make('operateur')
                        ->label('Agent Back-Office')
                        ->formatStateUsing(fn($record) => $record->operateur
                            ? trim($record->operateur->prenom . ' ' . $record->operateur->nom)
                            : '—'),

                    TextEntry::make('date_appel_j1')
                        ->label('Date appel J+1')
                        ->date('d/m/Y'),
                ]),

            Section::make('Verbatim client')
                ->collapsible()
                ->schema([
                    TextEntry::make('verbatim_client')
                        ->label('')
                        ->prose()
                        ->placeholder('Aucun verbatim'),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            ReclamationRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListRapportSatisfactionP6s::route('/'),
            'create' => CreateRapportSatisfactionP6::route('/create'),
            'view'   => ViewRapportSatisfactionP6::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['back_office', 'responsable_plateau']) ?? false;
    }
}
