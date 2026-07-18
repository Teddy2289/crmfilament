<?php

namespace App\Filament\NsConseil\Resources\CampagnePhoningResource\Pages;

use App\Filament\NsConseil\Resources\ClientResource;
use App\Filament\NsConseil\Resources\CampagnePhoningResource;
use App\Filament\NsConseil\Resources\ContactPartenaireResource;
use App\Filament\NsConseil\Resources\ProspectResource;
use App\Models\CampagnePhoning;
use App\Models\Client;
use App\Models\ContactPartenaire;
use App\Models\Prospect;
use App\Models\Appel;
use App\Models\StatutPhoning;
use Filament\Actions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ViewCampagnePhoning extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CampagnePhoningResource::class;

    protected static string $view = 'filament.ns-conseil.resources.campagne-phoning-resource.pages.view-campagne-phoning';

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        return [
            Actions\Action::make('lancer_phoning')
                ->label('Lancer le phoning')
                ->icon('heroicon-o-phone-arrow-up-right')
                ->color('primary')
                ->visible(fn () => CampagnePhoningResource::canView($record) && $record->statut === 'active')
                ->url(fn () => route('filament.ns-conseil.pages.phoning-workflow', ['campagne_id' => $record->id])),

            Actions\EditAction::make(),
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
                        ->formatStateUsing(fn ($state) => CampagnePhoning::STATUTS[$state] ?? $state)
                        ->color(fn ($state) => match ($state) {
                            'active' => 'success',
                            'terminee' => 'gray',
                            default => 'warning',
                        }),
                    TextEntry::make('type_entite')
                        ->label('Cible')
                        ->formatStateUsing(fn ($state) => CampagnePhoning::TYPES_ENTITE[$state] ?? $state),
                    TextEntry::make('user.nom')
                        ->label('Assigné à')
                        ->formatStateUsing(fn ($record) => $record->user
                            ? trim("{$record->user->prenom} {$record->user->nom}")
                            : 'Tous les agents'),
                    TextEntry::make('date_debut')->label('Début')->date('d/m/Y')->placeholder('—'),
                    TextEntry::make('date_fin')->label('Fin')->date('d/m/Y')->placeholder('—'),
                    TextEntry::make('description')->label('Description')->columnSpanFull()->placeholder('—'),
                ]),

            Section::make('Progression')
                ->icon('heroicon-o-chart-bar')
                ->columns(4)
                ->schema([
                    TextEntry::make('stats_contacts')
                        ->label('Contacts total')
                        ->getStateUsing(fn ($record) => $record->getStats()['total_contacts'])
                        ->badge()
                        ->color('info'),
                    TextEntry::make('stats_traites')
                        ->label('Contacts traités')
                        ->getStateUsing(fn ($record) => $record->getStats()['contacts_traites'])
                        ->badge()
                        ->color('success'),
                    TextEntry::make('stats_restants')
                        ->label('Contacts restants')
                        ->getStateUsing(fn ($record) => $record->getStats()['contacts_restants'])
                        ->badge()
                        ->color('warning'),
                    TextEntry::make('stats_progression')
                        ->label('Progression')
                        ->getStateUsing(fn ($record) => $record->getStats()['progression'].'%')
                        ->badge()
                        ->color(fn ($record) => match (true) {
                            $record->getStats()['progression'] >= 80 => 'success',
                            $record->getStats()['progression'] >= 40 => 'warning',
                            default => 'danger',
                        }),
                ]),

            Section::make('Résultats des appels')
                ->icon('heroicon-o-phone')
                ->schema([
                    TextEntry::make('stats_total_appels')
                        ->label('Total appels passés')
                        ->getStateUsing(fn ($record) => $record->getStats()['total_appels'])
                        ->badge()
                        ->color('info'),

                    ...($this->getRecord()->statutsUtilises() === []
                        ? [
                            TextEntry::make('aucun_appel')
                                ->hiddenLabel()
                                ->getStateUsing(fn () => 'Aucun appel enregistré pour le moment.'),
                        ]
                        : [$this->buildResultatsParStatutTabs()]),
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
                        $appels = $record->appelsParStatut($code);

                        return Tab::make($record->statutLabel($code))
                            ->badge($appels->count())
                            ->badgeColor($record->statutCouleur($code))
                            ->schema([
                                RepeatableEntry::make('appels_'.$code)
                                    ->hiddenLabel()
                                    // Les entrées imbriquées ci-dessous lisent $record (l'Appel
                                    // de la ligne courante) via getStateUsing plutôt que des
                                    // noms en pointillés ("appelable.nom"), car la résolution de
                                    // chemin absolu d'un RepeatableEntry lié à un état brut (pas
                                    // une vraie relation Eloquent) ne traverse pas les relations.
                                    ->state(fn () => $appels)
                                    ->schema([
                                        TextEntry::make('contact')
                                            ->label('Contact')
                                            ->getStateUsing(fn (Appel $record) => $this->appelContactNom($record))
                                            ->weight('semibold'),
                                        TextEntry::make('telephone')
                                            ->label('Téléphone')
                                            ->getStateUsing(fn (Appel $record) => $this->appelContactTelephone($record))
                                            ->placeholder('—'),
                                        TextEntry::make('date_heure')
                                            ->label("Date de l'appel")
                                            ->getStateUsing(fn (Appel $record) => $record->date_heure)
                                            ->dateTime('d/m/Y H:i')
                                            ->placeholder('—'),
                                        TextEntry::make('agent')
                                            ->label('Agent')
                                            ->getStateUsing(fn (Appel $record) => $record->user
                                                ? trim("{$record->user->prenom} {$record->user->nom}")
                                                : '—'),
                                        TextEntry::make('commentaire')
                                            ->label('Commentaire')
                                            ->getStateUsing(fn (Appel $record) => $record->commentaire ?: $record->phoning_notes)
                                            ->placeholder('—')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(4),
                            ]);
                    })
                    ->all()
            );
    }

    private function appelContactNom(Appel $appel): string
    {
        if (! $appel->appelable) {
            return 'Contact #'.$appel->appelable_id;
        }

        return $this->queueContactName($appel->appelable);
    }

    private function appelContactTelephone(Appel $appel): ?string
    {
        return $appel->appelable ? $this->queuePhone($appel->appelable) : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(fn () => 'File d\'attente - '.$this->getRecord()->countQueueContacts().' contact(s)')
            ->query(fn () => $this->getRecord()->buildQueueQuery())
            ->columns([
                Tables\Columns\TextColumn::make('queue_contact')
                    ->label('Contact')
                    ->getStateUsing(fn (Model $record) => $this->queueContactName($record))
                    ->description(fn (Model $record) => $this->queueContactDescription($record))
                    ->weight('semibold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('queue_type')
                    ->label('Type')
                    ->getStateUsing(fn (Model $record) => $this->queueTypeLabel($record))
                    ->badge()
                    ->color(fn (Model $record) => match (true) {
                        $record instanceof Prospect => 'warning',
                        $record instanceof ContactPartenaire => 'primary',
                        $record instanceof Client => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('queue_phone')
                    ->label('Téléphone')
                    ->getStateUsing(fn (Model $record) => $this->queuePhone($record))
                    ->placeholder('—')
                    ->copyable(),

                Tables\Columns\TextColumn::make('queue_email')
                    ->label('Email')
                    ->getStateUsing(fn (Model $record) => $this->queueEmail($record))
                    ->placeholder('—')
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('queue_status')
                    ->label('Statut')
                    ->getStateUsing(fn (Model $record) => $this->queueStatus($record))
                    ->badge()
                    ->color(fn (Model $record) => $this->queueStatusColor($record)),

                Tables\Columns\TextColumn::make('queue_suivi')
                    ->label('Suivi')
                    ->getStateUsing(fn (Model $record) => $this->queueSuivi($record)['label'])
                    ->badge()
                    ->color(fn (Model $record) => $this->queueSuivi($record)['color']),

                Tables\Columns\TextColumn::make('queue_assignee')
                    ->label('Assigné à')
                    ->getStateUsing(fn (Model $record) => $this->queueAssignee($record))
                    ->placeholder('Tous'),

                Tables\Columns\TextColumn::make('queue_next_call')
                    ->label('Rappel prévu')
                    ->getStateUsing(fn (Model $record) => $record instanceof Prospect ? $record->rappel_planifie_at : null)
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Aucun rappel programmé')
                    ->color(fn (Model $record) => $record instanceof Prospect && $record->rappel_est_en_retard ? 'danger' : 'gray'),
            ])
            ->recordUrl(fn (Model $record) => $this->queueRecordUrl($record))
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Aucun contact en file d\'attente')
            ->emptyStateDescription('La campagne ne contient aucun contact appelable avec les critères actuels.')
            ->emptyStateIcon('heroicon-o-phone-x-mark');
    }

    private function queueContactName(Model $record): string
    {
        return match (true) {
            $record instanceof Prospect => $record->nom ?: ($record->raison_sociale ?: 'Prospect #'.$record->getKey()),
            $record instanceof ContactPartenaire => $record->nom_affichage ?: ($record->nom_complet ?: 'Contact partenaire #'.$record->getKey()),
            $record instanceof Client => trim(($record->prenom ? $record->prenom.' ' : '').($record->nom_tiers ?? '')) ?: 'Client #'.$record->getKey(),
            default => 'Contact #'.$record->getKey(),
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
                ->groupBy(fn (Appel $appel) => $appel->appelable_type.'#'.$appel->appelable_id)
                ->map(fn ($appels) => $appels->first())
                ->all();
        }

        return $this->dernierAppelParContact[get_class($record).'#'.$record->getKey()] ?? null;
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
}
