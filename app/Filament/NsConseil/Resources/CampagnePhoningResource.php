<?php

namespace App\Filament\NsConseil\Resources;

use App\Enums\OrganizationType;
use App\Enums\ProspectStatut;
use App\Filament\NsConseil\Resources\CampagnePhoningResource\Pages;
use App\Models\CampagnePhoning;
use App\Models\EntiteCommerciale;
use App\Models\GroupeTelepro;
use App\Models\Partenaire;
use App\Models\User;
use App\Support\UsesResourcePermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CampagnePhoningResource extends Resource
{
    use UsesResourcePermissions;

    protected static ?string $model = CampagnePhoning::class;

    public static function buildAudiencePreview(Get $get): string
    {
        $type = $get('type_entite') ?? 'prospects';
        $payload = [
            'type_entite' => $type,
            'nom' => $get('nom') ?? 'Nouvelle campagne',
            'statut' => $get('statut') ?? 'brouillon',
            'description' => $get('description') ?? null,
            'criteres' => [
                'statuts' => $get('criteres.statuts') ?? [],
                'departement' => $get('criteres.departement') ?? null,
                'secteur_activite' => $get('criteres.secteur_activite') ?? null,
                'nb_salaries_min' => $get('criteres.nb_salaries_min') ?? null,
                'nb_salaries_max' => $get('criteres.nb_salaries_max') ?? null,
                'type_pressenti' => $get('criteres.type_pressenti') ?? null,
                'type' => $get('criteres.type') ?? null,
                'etat' => $get('criteres.etat') ?? null,
                'type_tiers' => $get('criteres.type_tiers') ?? null,
                'rappel_date_debut' => $get('criteres.rappel_date_debut') ?? null,
                'rappel_date_fin' => $get('criteres.rappel_date_fin') ?? null,
                'rdv_date_debut' => $get('criteres.rdv_date_debut') ?? null,
                'rdv_date_fin' => $get('criteres.rdv_date_fin') ?? null,
            ],
            'max_tentatives' => $get('max_tentatives') ?? 4,
            'exclure_sans_telephone' => $get('exclure_sans_telephone') ?? true,
            'exclure_autres_campagnes' => $get('exclure_autres_campagnes') ?? true,
        ];

        $campaign = new CampagnePhoning($payload);
        $count = $campaign->countContacts();
        $label = CampagnePhoning::TYPES_ENTITE[$type] ?? ucfirst($type);

        return sprintf(
            "<div class=\"space-y-1\"><div><strong>%s</strong> · <span class=\"text-gray-600\">%s contacts ciblés</span></div><div class=\"text-sm text-gray-600\">Aucune cible sélectionnée tant que le segment et les filtres ne sont pas renseignés.</div></div>",
            $label,
            number_format($count, 0, ',', ' ')
        );
    }

    public static function buildCampaignPreview(Get $get): string
    {
        $type = $get('type_entite') ?? 'prospects';
        $label = CampagnePhoning::TYPES_ENTITE[$type] ?? ucfirst($type);
        $statuts = collect($get('criteres.statuts') ?? [])->map(fn ($code) => $code)->implode(', ') ?: 'Tous';
        $entite = EntiteCommerciale::find($get('entite_id'))?->nom;
        $groupe = GroupeTelepro::find($get('groupe_telepro_id'))?->nom;
        $agent = User::find($get('user_id')) ? trim("{$get('user_id')}" ) : null;

        $summary = [
            'Nom' => $get('nom') ?: 'Campagne sans nom',
            'Statut' => CampagnePhoning::STATUTS[$get('statut') ?? 'brouillon'] ?? ucfirst($get('statut') ?? 'brouillon'),
            'Cible' => $label,
            'Entité' => $entite ?? 'Toutes',
            'Groupe' => $groupe ?? 'Tous',
            'Agent spécifique' => $agent ?? 'Tous les télépros',
            'Période' => trim(($get('date_debut') ? \Illuminate\Support\Str::of($get('date_debut'))->explode(' ')[0] : '—') . ' → ' . ($get('date_fin') ? \Illuminate\Support\Str::of($get('date_fin'))->explode(' ')[0] : '—')),
            'Statuts ciblés' => $statuts,
            'Tentation max' => (string) ($get('max_tentatives') ?? 4) . ' appel(s)',
        ];

        $lines = collect($summary)->map(fn ($value, $key) => sprintf('<div class="flex items-start justify-between gap-4 py-1"><span class="font-medium text-gray-700">%s</span><span class="text-right text-sm text-gray-900">%s</span></div>', $key, $value))->implode('');

        return sprintf('<div class="space-y-2">%s</div>', $lines);
    }

    protected static string $permissionPrefix = 'campagne_phonings';

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?string $navigationLabel = 'Campagnes d\'appels';

    protected static ?int $navigationSort = 1;

    // ─────────────────────────────────────────────────────────────────
    // FORMULAIRE
    // ─────────────────────────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema(static::applyFormFieldPermissions([
            Forms\Components\Section::make('Identité & Attribution')
                ->description('Nom, dates, entité et agents affectés')
                ->icon('heroicon-o-megaphone')
                ->columns(2)
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('nom')
                        ->label('Nom de la campagne')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('statut')
                        ->label('Statut')
                        ->options(CampagnePhoning::STATUTS)
                        ->default('brouillon')
                        ->required(),

                    Forms\Components\Select::make('entite_id')
                        ->label('Entité commerciale')
                        ->options(fn () => EntiteCommerciale::orderBy('nom')->pluck('nom', 'id'))
                        ->searchable()
                        ->nullable(),

                    Forms\Components\Select::make('groupe_telepro_id')
                        ->label('Groupe assigné')
                        ->options(fn () => GroupeTelepro::actifs()->orderBy('nom')->pluck('nom', 'id'))
                        ->searchable()
                        ->nullable()
                        ->placeholder('Tous les groupes (ouverte à tous)')
                        ->helperText('Tous les télépros de ce groupe voient cette campagne.'),

                    Forms\Components\Select::make('user_id')
                        ->label('Agent spécifique (optionnel)')
                        ->options(
                            fn () => User::where('actif', true)
                                ->orderBy('nom')
                                ->get()
                                ->mapWithKeys(fn ($u) => [$u->id => trim("{$u->prenom} {$u->nom}")])
                        )
                        ->searchable()
                        ->nullable()
                        ->default(fn () => auth()->user()?->hasRoleCache('teleprospecteur') ? auth()->id() : null)
                        ->placeholder('Personne en particulier')
                        ->helperText('Prioritaire sur le groupe si renseigné.'),

                    Forms\Components\DatePicker::make('date_debut')
                        ->label('Date de début')
                        ->nullable()
                        ->displayFormat('d/m/Y'),

                    Forms\Components\DatePicker::make('date_fin')
                        ->label('Date de fin')
                        ->nullable()
                        ->displayFormat('d/m/Y')
                        ->afterOrEqual('date_debut'),
                ]),

            Forms\Components\Section::make('Ciblage du Public')
                ->description('Segment cible et critères de sélection')
                ->icon('heroicon-o-funnel')
                ->columns(2)
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Select::make('type_entite')
                        ->label('Segment Cible')
                        ->options(CampagnePhoning::TYPES_ENTITE)
                        ->default('prospects')
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('criteres', []))
                        ->columnSpanFull(),

                    Forms\Components\Group::make([
                        Forms\Components\CheckboxList::make('criteres.statuts')
                            ->label('Statuts à inclure')
                            ->options(collect(ProspectStatut::cases())->mapWithKeys(
                                fn ($case) => [$case->value => $case->label()]
                            ))
                            ->default([])
                            ->live()
                            ->columns(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('criteres.departement')
                            ->label('Département')
                            ->placeholder('ex: 75')
                            ->maxLength(3),

                        Forms\Components\TextInput::make('criteres.secteur_activite')
                            ->label("Secteur d'activité")
                            ->placeholder('ex: BTP, Industrie…'),

                        Forms\Components\TextInput::make('criteres.nb_salaries_min')
                            ->label('Nb salariés min')
                            ->numeric()
                            ->minValue(0),

                        Forms\Components\TextInput::make('criteres.nb_salaries_max')
                            ->label('Nb salariés max')
                            ->numeric()
                            ->minValue(0),

                        Forms\Components\Select::make('criteres.type_pressenti')
                            ->label('Type pressenti')
                            ->options([
                                'cse' => 'CSE',
                                'artisan' => 'Artisan',
                                'direct' => 'Direct',
                            ])
                            ->nullable()
                            ->placeholder('Tous'),

                        Forms\Components\Section::make('Filtre par Date de Rappel Planifié')
                            ->icon('heroicon-o-calendar')
                            ->columns(2)
                            ->columnSpanFull()
                            ->visible(function (Get $get) {
                                $statuts = $get('criteres.statuts') ?? [];
                                return is_array($statuts) && in_array('RP', $statuts);
                            })
                            ->schema([
                                Forms\Components\DatePicker::make('criteres.rappel_date_debut')
                                    ->label('Rappel planifié — Du')
                                    ->displayFormat('d/m/Y')
                                    ->nullable(),

                                Forms\Components\DatePicker::make('criteres.rappel_date_fin')
                                    ->label('Rappel planifié — Au')
                                    ->displayFormat('d/m/Y')
                                    ->afterOrEqual('criteres.rappel_date_debut')
                                    ->nullable(),
                            ]),

                        Forms\Components\Section::make('Filtre par Date de Rendez-Vous Prévu')
                            ->icon('heroicon-o-calendar-days')
                            ->columns(2)
                            ->columnSpanFull()
                            ->visible(function (Get $get) {
                                $statuts = $get('criteres.statuts') ?? [];
                                $rdvCodes = ['RDV', 'RDV_PRIS', 'RDV_A_PRENDRE'];
                                return is_array($statuts) && count(array_intersect($rdvCodes, $statuts)) > 0;
                            })
                            ->schema([
                                Forms\Components\DatePicker::make('criteres.rdv_date_debut')
                                    ->label('RDV prévu — Du')
                                    ->displayFormat('d/m/Y')
                                    ->nullable(),

                                Forms\Components\DatePicker::make('criteres.rdv_date_fin')
                                    ->label('RDV prévu — Au')
                                    ->displayFormat('d/m/Y')
                                    ->afterOrEqual('criteres.rdv_date_debut')
                                    ->nullable(),
                            ]),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get) => $get('type_entite') === 'prospects')
                    ->columnSpanFull(),

                    Forms\Components\Group::make([
                        Forms\Components\CheckboxList::make('criteres.statuts')
                            ->label('Statuts à inclure')
                            ->default([])
                            ->options(Partenaire::STATUTS)
                            ->columns(2)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('criteres.departement')
                            ->label('Département')
                            ->placeholder('ex: 75')
                            ->maxLength(3),

                        Forms\Components\TextInput::make('criteres.secteur_activite')
                            ->label("Secteur d'activité"),

                        Forms\Components\Select::make('criteres.type')
                            ->label('Type de partenaire')
                            ->options(OrganizationType::class)
                            ->nullable()
                            ->placeholder('Tous'),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get) => $get('type_entite') === 'partenaires')
                    ->columnSpanFull(),

                    Forms\Components\Group::make([
                        Forms\Components\TextInput::make('criteres.departement')
                            ->label('Département')
                            ->placeholder('ex: 75')
                            ->maxLength(3),

                        Forms\Components\Select::make('criteres.etat')
                            ->label('État')
                            ->options([
                                'actif' => 'Actif',
                                'inactif' => 'Inactif',
                                'prospect' => 'Prospect',
                            ])
                            ->nullable()
                            ->placeholder('Tous'),

                        Forms\Components\TextInput::make('criteres.type_tiers')
                            ->label('Type de tiers')
                            ->placeholder('ex: particulier, entreprise…'),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get) => $get('type_entite') === 'clients')
                    ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Exclusions & Limites')
                ->description('Limites de réessai')
                ->icon('heroicon-o-shield-exclamation')
                ->columns(2)
                ->collapsible()
                ->collapsed(false)
                ->schema([
                    Forms\Components\TextInput::make('max_tentatives')
                        ->label('Nombre max de tentatives par contact')
                        ->numeric()
                        ->default(4)
                        ->required()
                        ->minValue(1)
                        ->maxValue(10)
                        ->helperText('Nombre maximal d\'appels non aboutis avant sortie automatique.'),

                    Forms\Components\Toggle::make('exclure_sans_telephone')
                        ->label('Exclure les fiches sans téléphone valide')
                        ->default(true)
                        ->helperText('Exclut automatiquement les prospects n\'ayant ni téléphone fixe ni mobile.'),

                    Forms\Components\Toggle::make('exclure_autres_campagnes')
                        ->label('Exclure les fiches déjà engagées dans une autre campagne active')
                        ->default(true)
                        ->helperText('Évite les doublons d\'appels simultanés sur un même prospect.'),
                ]),

            Forms\Components\Section::make('Script & Consignes')
                ->description('Guide d\'appel et pitch pour les téléprospecteurs')
                ->icon('heroicon-o-document-text')
                ->collapsible()
                ->collapsed(false)
                ->schema([
                    Forms\Components\RichEditor::make('script_appel')
                        ->label('Script d\'appel / Guide d\'entretien')
                        ->placeholder('Rédigez ici les consignes, arguments et objections pour les télépros…')
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'bulletList',
                            'orderedList',
                            'h2',
                            'h3',
                            'link',
                        ])
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Aperçu & Validation')
                ->description('Synthèse avant création de la campagne')
                ->icon('heroicon-o-clipboard-document-check')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Section::make('Synthèse de campagne')
                        ->description('Résumé lisible en une seule lecture avant de lancer la création.')
                        ->icon('heroicon-o-archive-box')
                        ->schema([
                            Forms\Components\Placeholder::make('campaign_preview')
                                ->label('Résumé de la campagne')
                                ->content(fn (Get $get) => static::buildCampaignPreview($get))
                                ->columnSpanFull(),
                        ])
                        ->columnSpanFull(),

                    Forms\Components\Section::make('Population estimée')
                        ->description('Projection de la cible calculée à partir des filtres sélectionnés.')
                        ->icon('heroicon-o-users')
                        ->schema([
                            Forms\Components\Placeholder::make('audience_preview')
                                ->label('Contacts ciblés')
                                ->content(fn (Get $get) => static::buildAudiencePreview($get))
                                ->columnSpanFull(),
                        ])
                        ->columnSpanFull(),
                ]),
        ]));
    }

    // ─────────────────────────────────────────────────────────────────
    // TABLE
    // ─────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Campagne')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('statut')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state) => CampagnePhoning::STATUTS[$state] ?? $state)
                    ->colors([
                        'warning' => 'brouillon',
                        'info' => 'planifiee',
                        'success' => 'active',
                        'danger' => 'en_pause',
                        'gray' => 'terminee',
                    ]),

                Tables\Columns\ToggleColumn::make('active_toggle')
                    ->label('Active')
                    ->getStateUsing(fn ($record) => $record->statut === 'active')
                    ->updateStateUsing(function ($record, $state) {
                        $record->update(['statut' => $state ? 'active' : 'en_pause']);

                        Notification::make()
                            ->title($state ? 'Campagne activée' : 'Campagne mise en pause')
                            ->success()
                            ->send();
                    })
                    ->disabled(fn () => ! static::userCanResourcePermission('update')),

                Tables\Columns\BadgeColumn::make('type_entite')
                    ->label('Cible')
                    ->formatStateUsing(fn ($state) => CampagnePhoning::TYPES_ENTITE[$state] ?? $state)
                    ->colors([
                        'warning' => 'prospects',
                        'primary' => 'partenaires',
                        'success' => 'clients',
                    ]),

                Tables\Columns\TextColumn::make('groupeTelepro.nom')
                    ->label('Groupe')
                    ->placeholder('Tous')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.nom')
                    ->label('Agent spécifique')
                    ->formatStateUsing(
                        fn ($record) => $record->user
                            ? trim("{$record->user->prenom} {$record->user->nom}")
                            : '—'
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('date_debut')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('date_fin')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('contacts_count')
                    ->label('Contacts')
                    ->getStateUsing(fn ($record) => $record->countContacts())
                    ->suffix(' contacts')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('entite.nom')
                    ->label('Entité')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filters([
                Tables\Filters\Filter::make('periode')
                    ->label('Période')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('date_debut')->label('Du'),
                        \Filament\Forms\Components\DatePicker::make('date_fin')->label('Au'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['date_debut'] ?? null, fn ($q, $date) => $q->where(function ($period) use ($date) {
                                $period->whereNull('date_fin')->orWhere('date_fin', '>=', $date);
                            }))
                            ->when($data['date_fin'] ?? null, fn ($q, $date) => $q->where(function ($period) use ($date) {
                                $period->whereNull('date_debut')->orWhere('date_debut', '<=', $date);
                            }));
                    }),
                Tables\Filters\SelectFilter::make('statut')
                    ->label('Statut')
                    ->options(CampagnePhoning::STATUTS),

                Tables\Filters\SelectFilter::make('type_entite')
                    ->label('Cible')
                    ->options(CampagnePhoning::TYPES_ENTITE),

                Tables\Filters\SelectFilter::make('groupe_telepro_id')
                    ->label('Groupe')
                    ->options(fn () => GroupeTelepro::actifs()->orderBy('nom')->pluck('nom', 'id')),

                Tables\Filters\SelectFilter::make("user_id")
                    ->label("Agent spécifique")
                    ->options(
                        fn () => User::where("actif", true)
                            ->orderBy("nom")
                            ->get()
                            ->mapWithKeys(fn ($u) => [$u->id => trim("{$u->prenom} {$u->nom}")])
                    )
                    ->query(function ($query, array $data) {
                        $agentId = $data["value"] ?? null;
                        return filled($agentId)
                            ? $query->whereHas("appels", fn ($appels) => $appels->where("phoning_agent_id", $agentId)->orWhere("user_id", $agentId))
                            : $query;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('lancer_phoning')
                    ->label('Lancer le phoning')
                    ->icon('heroicon-o-phone-arrow-up-right')
                    ->color('primary')
                    ->visible(fn ($record) => static::userCanResourcePermission('view') && $record->statut === 'active')
                    ->url(fn ($record) => route('filament.ns-conseil.pages.phoning-workflow', ['campagne_id' => $record->id])),

                Tables\Actions\ViewAction::make()
                    ->url(function ($record): string {
                        $url = static::getUrl('view', ['record' => $record]);
                        $filters = request()->query('tableFilters', []);
                        return !empty($filters)
                            ? $url . '?' . http_build_query(['tableFilters' => $filters])
                            : $url;
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListCampagnesPhonings::route('/'),
            'create' => Pages\CreateCampagnePhoning::route('/create'),
            'view' => Pages\ViewCampagnePhoning::route('/{record}'),
            'edit' => Pages\EditCampagnePhoning::route('/{record}/edit'),
        ];
    }
}
