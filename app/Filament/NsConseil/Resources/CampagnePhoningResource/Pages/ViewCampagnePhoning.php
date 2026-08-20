<?php

namespace App\Filament\NsConseil\Resources\CampagnePhoningResource\Pages;

use App\Filament\NsConseil\Resources\ClientResource;
use App\Filament\NsConseil\Resources\CampagnePhoningResource;
use App\Filament\NsConseil\Resources\ContactPartenaireResource;
use App\Filament\NsConseil\Resources\ProspectResource;
use App\Enums\EventResult;
use App\Enums\EventType;
use App\Models\CampagnePhoning;
use App\Models\Client;
use App\Models\ContactPartenaire;
use App\Models\EmailConfiguration;
use App\Models\Prospect;
use App\Models\Appel;
use App\Models\StatutPhoning;
use Filament\Actions as PageActions;
use Filament\Tables\Actions as TableActions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;

class ViewCampagnePhoning extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    // Filtre UI state
    // Global (legacy) filter UI state — kept for backward compatibility
    public ?string $filter_start_date = null;
    public ?string $filter_end_date = null;
    public ?int $filter_telepro_id = null;
    public ?int $filter_agent_id = null;
    public ?string $filter_type = null;
    public ?string $ventilationFilter = null;

    public function getActiveVentilation(): string
    {
        return $this->ventilationFilter ?: (string) request()->query("ventilation", "available");
    }

    public function setVentilationFilter(string $filter): void
    {
        $allowed = ["available", "targeted", "multi_appels", "selected"];
        if (str_starts_with($filter, "status:")) {
            $allowed[] = $filter;
        }
        if (! in_array($filter, $allowed, true)) {
            return;
        }
        $this->ventilationFilter = $filter;
        $this->resetTable();
    }
    public ?string $filter_status = null;

    // Per-tab filters: keyed by statut code
    public array $tabFilters = [];

    protected static string $resource = CampagnePhoningResource::class;

    protected static string $view = 'filament.ns-conseil.resources.campagne-phoning-resource.pages.view-campagne-phoning';

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        return [
            PageActions\Action::make('lancer_phoning')
                ->label('Lancer le phoning')
                ->icon('heroicon-o-phone-arrow-up-right')
                ->color('primary')
                ->visible(fn() => CampagnePhoningResource::canView($record) && $record->statut === 'active')
                ->url(fn() => route('filament.ns-conseil.pages.phoning-workflow', ['campagne_id' => $record->id])),

            PageActions\EditAction::make(),
            PageActions\Action::make('export_ventilation_csv')
                ->label('Exporter la ventilation CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => $this->exportDiagnosticVentilation()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema(CampagnePhoningResource::applyShowFieldPermissions([
            Section::make('Informations')
                ->icon('heroicon-o-megaphone')
                ->columns(3)
                ->schema([
                    TextEntry::make('nom')->label('Nom de la campagne')->weight('bold'),
                    TextEntry::make('statut')
                        ->label('Statut')
                        ->badge()
                        ->formatStateUsing(fn($state) => CampagnePhoning::STATUTS[$state] ?? $state)
                        ->color(fn($state) => match ($state) {
                            'active' => 'success',
                            'terminee' => 'gray',
                            default => 'warning',
                        }),
                    TextEntry::make('type_entite')
                        ->label('Cible')
                        ->formatStateUsing(fn($state) => CampagnePhoning::TYPES_ENTITE[$state] ?? $state),
                    TextEntry::make('user.nom')
                        ->label('Assigné à')
                        ->formatStateUsing(fn($record) => $record->user
                            ? trim("{$record->user->prenom} {$record->user->nom}")
                            : 'Tous les agents'),
                    TextEntry::make('groupeTelepro.nom')
                        ->label('Groupe télépro')
                        ->badge()
                        ->color('secondary')
                        ->placeholder('Tous'),
                    TextEntry::make('entite.nom')
                        ->label('Entité commerciale')
                        ->badge()
                        ->color('secondary')
                        ->placeholder('—'),
                    TextEntry::make('date_debut')->label('Début')->date('d/m/Y')->placeholder('—'),
                    TextEntry::make('date_fin')->label('Fin')->date('d/m/Y')->placeholder('—'),
                    TextEntry::make('description')->label('Description')->columnSpanFull()->placeholder('—'),
                    TextEntry::make('email_configuration')
                        ->label('Configuration e-mail')
                        ->getStateUsing(fn($record) => $this->getEmailUsageDescription())
                        ->columnSpanFull()
                        ->helperText('Configuration email active pour cette campagne.')
                        ->placeholder('Aucune configuration trouvée.'),
                ]),

            Section::make('Progression')
                ->icon('heroicon-o-chart-bar')
                ->columns(4)
                ->schema([
                    TextEntry::make('stats_contacts')
                        ->label('Contacts total')
                        ->getStateUsing(fn($record) => $record->getStats()['total_contacts'])
                        ->badge()
                        ->color('info'),
                    TextEntry::make('stats_traites')
                        ->label('Contacts uniques traités')
                        ->getStateUsing(fn($record) => $record->getStats()['contacts_traites'])
                        ->helperText('Contacts ayant au moins un statut qui n’est pas une simple tentative infructueuse.')
                        ->badge()
                        ->color('success'),
                    TextEntry::make('stats_restants')
                        ->label('Contacts uniques restants')
                        ->getStateUsing(fn($record) => $record->getStats()['contacts_restants'])
                        ->helperText('Contacts sans résultat qualifiant ; ils peuvent encore être rappelés.')
                        ->badge()
                        ->color('warning'),
                    TextEntry::make('stats_appels')
                        ->label('Appels enregistrés')
                        ->getStateUsing(fn($record) => $record->getStats()['total_appels'])
                        ->helperText('Nombre total de tentatives, plusieurs appels pouvant concerner le même contact.')
                        ->badge()
                        ->color('info'),
                    TextEntry::make('stats_progression')
                        ->label('Progression')
                        ->getStateUsing(fn($record) => $record->getStats()['progression'] . '%')
                        ->badge()
                        ->color(fn($record) => match (true) {
                            $record->getStats()['progression'] >= 80 => 'success',
                            $record->getStats()['progression'] >= 40 => 'warning',
                            default => 'danger',
                        }),
                ]),

            Section::make('Répartition synthétique')
                ->collapsible()
                ->collapsed()
                ->icon('heroicon-o-list-bullet')
                ->columnSpanFull()
                ->schema([
                    \Filament\Infolists\Components\ViewEntry::make('contacts_traites_par_statut')
                        ->view('filament.infolists.entries.campagne-phoning-contacts-par-statut'),
                ]),

            Section::make("Résultats des appels")
                ->icon("heroicon-o-phone")
                ->columnSpanFull()
                ->schema([
                    \Filament\Infolists\Components\ViewEntry::make("resultats_appels")
                        ->view("filament.infolists.entries.campagne-resultats-appels"),
                ]),
        ]));
    }

    /**
     * Un onglet par statut réellement rencontré, listant chaque appel sous
     * forme de fiche (contact, téléphone, date, agent, commentaire) — plutôt
     * qu'un simple total générique par code.
     */
    protected function buildResultatsParStatutTabs(): Tabs
    {
        $record = $this->getRecord();

        return Tabs::make('resultats_par_statut')
            ->columnSpanFull()
            ->tabs(
                collect($record->statutsUtilises())
                    ->map(function (string $code) use ($record) {
                        $appels = $this->getFilteredAppelsForStatut($record, $code);

                        return Tab::make($record->statutLabel($code))
                            ->badge($appels->count())
                            ->badgeColor($record->statutCouleur($code))
                            ->schema([
                                \Filament\Infolists\Components\ViewEntry::make('appels_table_'.$code)
                                    ->view('filament.infolists.entries.statut-appels-table')
                                    ->state($code),
                            ]);
                    })
                    ->all()
            );
    }

    private function appelContactNom(Appel $appel): string
    {
        if (! $appel->appelable) {
            return 'Contact #' . $appel->appelable_id;
        }

        return $this->queueContactName($appel->appelable);
    }

    private function appelContactTelephone(Appel $appel): ?string
    {
        return $appel->appelable ? $this->queuePhone($appel->appelable) : null;
    }

    public function exportDiagnosticVentilation()
    {
        $record = $this->getRecord();
        $diagnostic = $this->getDiagnosticVentilation();
        $filename = 'campagne-'.$record->id.'-ventilation-'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($record, $diagnostic): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Campagne', 'ID', 'Département', 'Type', 'Indicateur', 'Valeur', 'Détail'], ';');
            foreach ($diagnostic['cards'] ?? [] as $card) {
                fputcsv($handle, [$record->nom, $record->id, $diagnostic['department'] ?? '', $record->type_entite, $card['label'], $card['value'], $card['description']], ';');
            }
            fputcsv($handle, [], ';');
            fputcsv($handle, ['Campagne', 'ID', 'Département', 'Type', 'Statut', 'Nombre', 'Détail'], ';');
            foreach ($diagnostic['statuses'] ?? [] as $status => $count) {
                fputcsv($handle, [$record->nom, $record->id, $diagnostic['department'] ?? '', $record->type_entite, $status, $count, 'Répartition de la population de référence'], ';');
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function getDiagnosticVentilation(): array
    {
        $record = $this->getRecord();
        if ($record->type_entite !== 'prospects') {
            return ['enabled' => false, 'cards' => [], 'statuses' => [], 'selected_statuses' => []];
        }

        $criteria = is_array($record->criteres) ? $record->criteres : [];
        $base = Prospect::query()->whereNull('deleted_at');
        if (! empty($criteria['departement'])) $base->where('departement', $criteria['departement']);
        if (! empty($criteria['secteur_activite'])) $base->where('secteur_activite', 'like', '%'.$criteria['secteur_activite'].'%');
        if (isset($criteria['nb_salaries_min']) && $criteria['nb_salaries_min'] !== '') $base->where('nb_salaries', '>=', (int) $criteria['nb_salaries_min']);
        if (isset($criteria['nb_salaries_max']) && $criteria['nb_salaries_max'] !== '') $base->where('nb_salaries', '<=', (int) $criteria['nb_salaries_max']);
        if (! empty($criteria['type_pressenti'])) $base->where('type_pressenti', $criteria['type_pressenti']);

        $phoneMissing = fn (Builder $q) => $q
            ->where(function (Builder $phone) {
                $phone->whereNull('telephone')->orWhere('telephone', '');
            })
            ->where(function (Builder $alt) {
                $alt->whereNull('telephone_alt')->orWhere('telephone_alt', '');
            });
        $statuses = (clone $base)->select('statut', DB::raw('COUNT(*) as total'))->groupBy('statut')->orderBy('statut')->pluck('total', 'statut')->map(fn ($v) => (int) $v)->all();
        $selected = array_values(array_filter((array) ($criteria['statuts'] ?? []), 'is_string'));
        $selectedCount = $selected === [] ? (clone $base)->count() : (clone $base)->whereIn('statut', $selected)->count();
        $maxAttempts = (clone $base)->when($record->max_tentatives > 0, fn (Builder $q) => $q->whereRaw('(SELECT COUNT(*) FROM appels WHERE appels.appelable_id = prospects.id AND appels.appelable_type = ? AND appels.compte_comme_tentative = 1) >= ?', ['App\\Models\\Prospect', (int) $record->max_tentatives]))->count();
        $multiAppels = (clone $base)->whereHas('appels', fn (Builder $q) => $q, '>=', 2)->count();
        $withoutPhone = (clone $base)->where($phoneMissing)->count();
        $targeted = $record->countContacts();
        $available = $record->countQueueContacts();

        return [
            'enabled' => true,
            'department' => $criteria['departement'] ?? null,
            'cards' => [
                ['key' => 'total', 'label' => 'Population de référence', 'value' => (clone $base)->count(), 'color' => 'gray', 'icon' => 'heroicon-o-users', 'description' => 'Toutes les fiches correspondant au périmètre géographique et métier.'],
                ['key' => 'selected', 'label' => 'Statuts sélectionnés', 'value' => $selectedCount, 'color' => 'info', 'icon' => 'heroicon-o-funnel', 'description' => $selected === [] ? 'Tous les statuts' : implode(', ', $selected)],
                ['key' => 'without_phone', 'label' => 'Sans téléphone', 'value' => $withoutPhone, 'color' => 'danger', 'icon' => 'heroicon-o-phone-x-mark', 'description' => 'Exclus de la file si la règle est activée.'],
                ['key' => 'max_attempts', 'label' => 'Limite de tentatives', 'value' => $maxAttempts, 'color' => 'danger', 'icon' => 'heroicon-o-arrow-path-rounded-square', 'description' => 'Nombre maximal de tentatives atteint.'],
                ['key' => 'multi_appels', 'label' => 'Prospects multi-appelés', 'value' => $multiAppels, 'color' => 'warning', 'icon' => 'heroicon-o-phone-arrow-up-right', 'description' => 'Prospects ayant au moins deux appels enregistrés.'],
                ['key' => 'targeted', 'label' => 'Ciblés par la campagne', 'value' => $targeted, 'color' => 'primary', 'icon' => 'heroicon-o-adjustments-horizontal', 'description' => 'Résultat de la requête de campagne.'],
                ['key' => 'available', 'label' => 'Disponibles dans la file', 'value' => $available, 'color' => 'success', 'icon' => 'heroicon-o-queue-list', 'description' => 'Résultat exact de la file de phoning.'],
                ['key' => 'excluded', 'label' => 'Écart ciblés / disponibles', 'value' => max(0, $targeted - $available), 'color' => 'warning', 'icon' => 'heroicon-o-minus-circle', 'description' => 'Exclusions supplémentaires de la file, dont statuts retirés.'],
            ],
            'statuses' => $statuses,
            'selected_statuses' => $selected,
        ];
    }

    private function applyDiagnosticVentilation(Builder $query, CampagnePhoning $record, string $filter): Builder
    {
        if ($record->type_entite !== 'prospects' || $filter === 'available' || $filter === 'targeted') return $filter === 'available' ? $record->buildQueueQuery() : $query;
        $criteria = is_array($record->criteres) ? $record->criteres : [];
        if ($filter === 'without_phone') $query
            ->where(function (Builder $phone) { $phone->whereNull('telephone')->orWhere('telephone', ''); })
            ->where(function (Builder $alt) { $alt->whereNull('telephone_alt')->orWhere('telephone_alt', ''); });
        if ($filter === 'max_attempts' && $record->max_tentatives > 0) $query->whereRaw('(SELECT COUNT(*) FROM appels WHERE appels.appelable_id = prospects.id AND appels.appelable_type = ? AND appels.compte_comme_tentative = 1) >= ?', ['App\\Models\\Prospect', (int) $record->max_tentatives]);
        if ($filter === 'multi_appels') $query->whereHas('appels', fn (Builder $q) => $q, '>=', 2);
        if ($filter === 'targeted') { /* conserve la population ciblée sans modifier les critères */ }
        if ($filter === 'selected' && ! empty($criteria['statuts'])) $query->whereIn('statut', (array) $criteria['statuts']);
        if (str_starts_with($filter, 'status:')) $query->where('statut', substr($filter, 7));
        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(fn() => 'File d\'attente - ' . $this->getRecord()->countQueueContacts() . ' contact(s)')
            ->searchPlaceholder('Nom, téléphone, email ou ville')
            ->searchDebounce(400)
            ->query(function () {
                $record = $this->getRecord();
                $filter = (string) $this->getActiveVentilation();
                return $this->applyDiagnosticVentilation($record->buildQuery(), $record, $filter);
            })
            ->columns([
                Tables\Columns\TextColumn::make('queue_contact')
                    ->label('Contact')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $this->applyQueueSearch($query, $search))
                    ->getStateUsing(fn(Model $record) => $this->queueContactName($record))
                    ->description(fn(Model $record) => $this->queueContactDescription($record))
                    ->weight('semibold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('queue_type')
                    ->label('Type')
                    ->getStateUsing(fn(Model $record) => $this->queueTypeLabel($record))
                    ->badge()
                    ->color(fn(Model $record) => match (true) {
                        $record instanceof Prospect => 'warning',
                        $record instanceof ContactPartenaire => 'primary',
                        $record instanceof Client => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('queue_phone')
                    ->label('Téléphone')
                    ->badge()
                    ->color('green')
                    ->icon('heroicon-o-phone')
                    ->getStateUsing(fn(Model $record) => $this->queuePhone($record))
                    ->placeholder('—')
                    ->copyable(),

                Tables\Columns\TextColumn::make('queue_email')
                    ->label('Email')
                    ->getStateUsing(fn(Model $record) => $this->queueEmail($record))
                    ->placeholder('—')
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('queue_status')
                    ->label('Statut')
                    ->getStateUsing(fn(Model $record) => $this->queueStatus($record))
                    ->badge()
                    ->color(fn(Model $record) => $this->queueStatusColor($record)),

                Tables\Columns\TextColumn::make('queue_suivi')
                    ->label('Suivi')
                    ->getStateUsing(fn(Model $record) => $this->queueSuivi($record)['label'])
                    ->badge()
                    ->color(fn(Model $record) => $this->queueSuivi($record)['color']),

                Tables\Columns\TextColumn::make('queue_assignee')
                    ->label('Assigné à')
                    ->getStateUsing(fn(Model $record) => $this->queueAssignee($record))
                    ->placeholder('Tous'),

                Tables\Columns\TextColumn::make('queue_next_call')
                    ->label('Rappel prévu')
                    ->getStateUsing(fn(Model $record) => $record instanceof Prospect ? $record->rappel_planifie_at : null)
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Aucun rappel programmé')
                    ->color(fn(Model $record) => $record instanceof Prospect && $record->rappel_est_en_retard ? 'danger' : 'gray'),
            ])
            ->recordUrl(fn(Model $record) => $this->queueRecordUrl($record))
            ->actions([
                TableActions\Action::make('retirer_de_campagne')
                    ->label('Retirer de la campagne')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Retirer ce contact de la campagne ?')
                    ->modalDescription('Le contact sera exclu de la file d\'attente de cette campagne. Cette action laisse la fiche intacte, mais le retire du phoning courant.')
                    ->action(function (Model $record): void {
                        $this->retirerContactDeCampagne($record);
                    }),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Aucun contact en file d\'attente')
            ->emptyStateDescription('La campagne ne contient aucun contact appelable avec les critères actuels.')
            ->emptyStateIcon('heroicon-o-phone-x-mark');
    }

    private function applyQueueSearch(Builder $query, string $search): Builder
    {
        $table = $query->getModel()->getTable();
        $columns = match ($table) {
            'prospects' => ['nom', 'raison_sociale', 'email', 'ville', 'telephone', 'telephone_alt'],
            'clients' => ['prenom', 'nom_tiers', 'email', 'ville', 'telephone'],
            'contacts_partenaires' => ['nom_affichage', 'nom_complet', 'email', 'ville', 'telephone'],
            default => ['nom', 'email', 'ville', 'telephone'],
        };
        $needle = '%' . mb_strtolower(trim($search)) . '%';
        $digits = preg_replace('/\\D+/', '', $search) ?: '';
        return $query->where(function (Builder $q) use ($columns, $needle, $digits): void {
            foreach ($columns as $column) {
                $q->orWhereRaw("LOWER(COALESCE(`{$column}`, '')) LIKE ?", [$needle]);
            }
            if ($digits !== '') {
                foreach (['telephone', 'telephone_alt'] as $column) {
                    $normalized = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(`{$column}`, ''), ' ', ''), '.', ''), '-', ''), '(', ''), ')', '')";
                    $q->orWhereRaw("{$normalized} LIKE ?", ['%' . $digits . '%']);
                }
            }
        });
    }

    private function retirerContactDeCampagne(Model $record): void
    {
        $campagne = $this->getRecord();
        $retireCode = StatutPhoning::query()
            ->where('model_type', $campagne->queueContactType())
            ->where('retire_de_file', true)
            ->value('code');

        if (! $retireCode) {
            $retireCode = 'RETIRE';
        }

        $agentLabel = auth()->user()
            ? trim((string) auth()->user()->prenom . ' ' . (string) auth()->user()->nom)
            : 'un agent';

        $commentaire = 'Retiré manuellement de la campagne par ' . $agentLabel . '.';

        Appel::query()->updateOrCreate([
            'campagne_id' => $campagne->id,
            'appelable_type' => $record::class,
            'appelable_id' => $record->getKey(),
            'phoning_status' => $retireCode,
        ], [
            'user_id' => auth()->id(),
            'type' => EventType::Appel,
            'resultat' => EventResult::NonAbouti,
            'date_heure' => now(),
            'commentaire' => $commentaire,
            'phoning_notes' => $commentaire,
            'phoning_completed_at' => now(),
        ]);
    }

    private function queueContactName(Model $record): string
    {
        return match (true) {
            $record instanceof Prospect => $record->nom ?: ($record->raison_sociale ?: 'Prospect #' . $record->getKey()),
            $record instanceof ContactPartenaire => $record->nom_affichage ?: ($record->nom_complet ?: 'Contact partenaire #' . $record->getKey()),
            $record instanceof Client => trim(($record->prenom ? $record->prenom . ' ' : '') . ($record->nom_tiers ?? '')) ?: 'Client #' . $record->getKey(),
            default => 'Contact #' . $record->getKey(),
        };
    }

    private function queueContactDescription(Model $record): ?string
    {
        return match (true) {
            $record instanceof Prospect => collect([$record->type_pressenti_label, $record->ville, $record->departement])
                ->filter()
                ->implode(' - '),
            $record instanceof ContactPartenaire => collect([$record->partenaire?->nom, $record->fonction_complete])
                ->filter()
                ->implode(' - '),
            $record instanceof Client => collect([$record->entreprise, $record->ville, $record->departement])
                ->filter()
                ->implode(' - '),
            default => null,
        };
    }

    private function queueTypeLabel(Model $record): string
    {
        return match (true) {
            $record instanceof Prospect => 'Prospect',
            $record instanceof ContactPartenaire => 'Partenaire',
            $record instanceof Client => 'Client',
            default => 'Contact',
        };
    }

    private function queuePhone(Model $record): ?string
    {
        return match (true) {
            $record instanceof Prospect => $record->telephone ?: $record->telephone_alt,
            $record instanceof ContactPartenaire => $record->telephone_principal !== 'N/A' ? $record->telephone_principal : null,
            $record instanceof Client => $record->telephone,
            default => null,
        };
    }

    private function queueEmail(Model $record): ?string
    {
        return match (true) {
            $record instanceof Prospect => $record->interlocuteur_email ?: $record->email,
            $record instanceof ContactPartenaire => $record->email_principal !== 'N/A' ? $record->email_principal : null,
            $record instanceof Client => $record->email,
            default => null,
        };
    }

    private function queueStatus(Model $record): string
    {
        return match (true) {
            $record instanceof Prospect => $record->statut_label,
            $record instanceof ContactPartenaire => $record->partenaire?->statut_label ?? $record->role_label,
            $record instanceof Client => $record->etat ?: 'Client',
            default => 'En file',
        };
    }

    private function queueStatusColor(Model $record): string
    {
        return match (true) {
            $record instanceof Prospect => $record->statut_color,
            $record instanceof ContactPartenaire => $record->partenaire?->statut_color ?? 'primary',
            $record instanceof Client => 'success',
            default => 'gray',
        };
    }

    private function queueAssignee(Model $record): ?string
    {
        return match (true) {
            $record instanceof Prospect => $record->teleprospecteur
                ? trim("{$record->teleprospecteur->prenom} {$record->teleprospecteur->nom}")
                : null,
            $record instanceof Client && $record->commercial => trim("{$record->commercial->prenom} {$record->commercial->nom}"),
            default => null,
        };
    }

    /**
     * @var array<string, Appel>|null
     */
    private ?array $dernierAppelParContact = null;

    /**
     * @var array<string, bool>|null
     */
    private ?array $codesSansReponse = null;

    /**
     * État de suivi d'un contact de la file : jamais appelé, appelé sans
     * réponse (simple tentative infructueuse : NRP, sans réponse...), ou
     * déjà traité (un vrai résultat a été obtenu lors du dernier appel).
     *
     * @return array{label: string, color: string}
     */
    private function queueSuivi(Model $record): array
    {
        $appel = $this->dernierAppelPour($record);

        if (! $appel) {
            return ['label' => 'Jamais appelé', 'color' => 'gray'];
        }

        $label = $appel->phoning_status ? strtoupper($appel->phoning_status) : 'Appelé';

        if ($this->estCodeSansReponse($appel->phoning_status)) {
            return ['label' => "Sans réponse ({$label})", 'color' => 'warning'];
        }

        return ['label' => "Traité ({$label})", 'color' => 'success'];
    }

    private function dernierAppelPour(Model $record): ?Appel
    {
        if ($this->dernierAppelParContact === null) {
            $this->dernierAppelParContact = Appel::where('campagne_id', $this->getRecord()->id)
                ->orderByDesc('date_heure')
                ->get()
                ->groupBy(fn(Appel $appel) => $appel->appelable_type . '#' . $appel->appelable_id)
                ->map(fn($appels) => $appels->first())
                ->all();
        }

        return $this->dernierAppelParContact[get_class($record) . '#' . $record->getKey()] ?? null;
    }

    private function estCodeSansReponse(?string $code): bool
    {
        if (! $code) {
            return false;
        }

        if ($this->codesSansReponse === null) {
            $this->codesSansReponse = StatutPhoning::where('model_type', $this->getRecord()->queueContactType())
                ->pluck('compte_comme_tentative', 'code')
                ->all();
        }

        return (bool) ($this->codesSansReponse[$code] ?? false);
    }

    private function queueRecordUrl(Model $record): ?string
    {
        return match (true) {
            $record instanceof Prospect => ProspectResource::getUrl('view', ['record' => $record]),
            $record instanceof ContactPartenaire => ContactPartenaireResource::getUrl('edit', ['record' => $record]),
            $record instanceof Client => ClientResource::getUrl('view', ['record' => $record]),
            default => null,
        };
    }

    private function getEmailUsageDescription(): string
    {
        $config = $this->resolveEmailConfigurationForCampaign($this->getRecord());

        if (! $config) {
            return 'Email utilisé : aucune configuration email active trouvée';
        }

        $sourceLabel = $config->is_global
            ? 'Configuration globale'
            : ('Configuration utilisateur : ' . trim((string) ($config->user?->prenom . ' ' . $config->user?->nom)));

        $syncDate = $config->last_sync_at
            ? $config->last_sync_at->format('d/m/Y H:i')
            : 'Jamais synchronisée';

        return sprintf(
            '%s — %s (%s)',
            $sourceLabel,
            $config->email,
            $syncDate,
        );
    }

    private function resolveEmailConfigurationForCampaign(CampagnePhoning $campagne): ?EmailConfiguration
    {
        if ($campagne->user_id) {
            return EmailConfiguration::forUser($campagne->user_id)
                ->active()
                ->first();
        }

        return EmailConfiguration::query()
            ->active()
            ->where('is_global', true)
            ->first();
    }

    /*******************************
     * Filters & helpers
     *******************************/

    protected function queryTeleprospecteurs()
    {
        $roles = app(\App\Services\Crm\CrmSettingsService::class)->get('roles.teleprospecteur_roles', ['teleprospecteur']);

        return User::where(function ($q) use ($roles) {
            $q->whereHas('roles', fn($r) => $r->whereIn('name', $roles));
            foreach ($roles as $role) {
                $q->orWhere('role_cache', $role);
            }
        })
            ->where('actif', true)
            ->orderBy('nom')
            ->orderBy('prenom');
    }

    public function teleprospecteursOptions(): array
    {
        return $this->queryTeleprospecteurs()->get()->mapWithKeys(fn(User $u) => [$u->id => trim("{$u->prenom} {$u->nom}")])->toArray();
    }

    public function agentOptions(): array
    {
        return User::where('actif', true)->orderBy('nom')->orderBy('prenom')->get()->mapWithKeys(fn(User $u) => [$u->id => trim("{$u->prenom} {$u->nom}")])->toArray();
    }

    public function typeOptions(): array
    {
        return collect(\App\Enums\EventType::cases())->mapWithKeys(fn($t) => [$t->value => $t->label()])->toArray();
    }

    public function statusOptions(): array
    {
        $codes = $this->getRecord()->statutsUtilises();
        return collect($codes)->mapWithKeys(fn($c) => [$c => $this->getRecord()->statutLabel($c)])->toArray();
    }

    public function resetFilters(): void
    {
        $this->filter_start_date = null;
        $this->filter_end_date = null;
        $this->filter_telepro_id = null;
        $this->filter_agent_id = null;
        $this->filter_type = null;
        $this->filter_status = null;
    }

    public function resetTabFilters(string $statut): void
    {
        $this->tabFilters[$statut] = [];
    }

    public function getFilteredAppelsForStatut(CampagnePhoning $campagne, string $code)
    {
        $query = Appel::query()
            ->where('campagne_id', $campagne->id)
            ->where('phoning_status', $code);

        $filters = $this->tabFilters[$code] ?? [];

        // fallback to global filters for backward compatibility
        $startDate = $filters['start_date'] ?? $this->filter_start_date ?? null;
        $endDate = $filters['end_date'] ?? $this->filter_end_date ?? null;
        $teleproId = $filters['telepro_id'] ?? $this->filter_telepro_id ?? null;
        $agentId = $filters['agent_id'] ?? $this->filter_agent_id ?? null;
        $type = $filters['type'] ?? $this->filter_type ?? null;
        $status = $filters['status'] ?? $this->filter_status ?? null;

        if ($type) {
            $query->where('type', $type);
        }

        if ($agentId) {
            $query->where(function($q) use ($agentId) {
                $q->where('phoning_agent_id', $agentId)->orWhere('user_id', $agentId);
            });
        }

        if ($status) {
            $query->where('phoning_status', $status);
        }

        if ($startDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $query->where('date_heure', '>=', $start);
        }

        if ($endDate) {
            $end = Carbon::parse($endDate)->endOfDay();
            $query->where('date_heure', '<=', $end);
        }

        if ($teleproId) {
            $prospectIds = Prospect::where('teleprospecteur_id', $teleproId)->pluck('id');
            if ($prospectIds->isNotEmpty()) {
                $query->where('appelable_type', Prospect::class)->whereIn('appelable_id', $prospectIds->toArray());
            } else {
                return collect([]);
            }
        }

        return $query->with(['appelable', 'user'])->orderByDesc('date_heure')->get();
    }
}
