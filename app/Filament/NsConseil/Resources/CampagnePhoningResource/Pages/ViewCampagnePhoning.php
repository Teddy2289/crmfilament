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
use Filament\Actions;
use Filament\Infolists\Components\Section;
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
                ->columns(2)
                ->schema([
                    TextEntry::make('stats_total_appels')
                        ->label('Total appels passés')
                        ->getStateUsing(fn ($record) => $record->getStats()['total_appels'])
                        ->badge()
                        ->color('info'),
                    TextEntry::make('stats_par_statut')
                        ->label('Répartition par statut')
                        ->getStateUsing(function ($record) {
                            $stats = $record->getStats();
                            if (empty($stats['par_statut'])) {
                                return 'Aucun appel enregistré';
                            }

                            return collect($stats['par_statut'])
                                ->map(fn ($count, $code) => "{$code}: {$count}")
                                ->implode(' | ');
                        })
                        ->columnSpanFull(),
            ]),
        ]));
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

                Tables\Columns\TextColumn::make('queue_assignee')
                    ->label('Assigné à')
                    ->getStateUsing(fn (Model $record) => $this->queueAssignee($record))
                    ->placeholder('Tous'),

                Tables\Columns\TextColumn::make('queue_next_call')
                    ->label('Rappel')
                    ->getStateUsing(fn (Model $record) => $record instanceof Prospect ? $record->rappel_planifie_at : null)
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
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
