<?php

namespace App\Filament\Allopro\Resources\TicketResource\Pages;

use App\Enums\TicketStatut;
use App\Filament\Allopro\Resources\TicketResource;
use App\Models\Artisan;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('changer_statut')
                ->label('Changer statut')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('primary')
                ->visible(
                    fn() => $this->record->estActif() &&
                        count($this->record->statut->statutsSuivants()) > 0
                )
                ->form(fn() => [
                    Forms\Components\Select::make('nouveau_statut')
                        ->label('Nouveau statut')
                        ->options(
                            collect($this->record->statut->statutsSuivants())
                                ->mapWithKeys(fn($s) => [$s->value => $s->label()])
                                ->toArray()
                        )
                        ->required()
                        ->native(false),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    $nouveau = TicketStatut::from($data['nouveau_statut']);
                    $this->record->changerStatut($nouveau, $data['notes'] ?? null);
                    $this->refreshFormData(['statut', 'notes']);
                    Notification::make()
                        ->title('→ ' . $nouveau->label())
                        ->success()
                        ->send();
                }),

            Actions\Action::make('assigner_artisan')
                ->label('Assigner artisan')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('warning')
                ->visible(fn() => is_null($this->record->artisan_id) && $this->record->estActif())
                ->form([
                    Forms\Components\Select::make('artisan_id')
                        ->label('Artisan disponible')
                        ->options(
                            Artisan::disponibles()->get()
                                ->mapWithKeys(fn($a) => [
                                    $a->id => $a->nom_complet . ' — ' . $a->corps_de_metier->label(),
                                ])
                        )
                        ->required()
                        ->searchable()
                        ->native(false),
                ])
                ->action(function (array $data) {
                    $artisan = Artisan::findOrFail($data['artisan_id']);
                    $this->record->assignerArtisan($artisan);
                    $this->refreshFormData(['artisan_id']);
                    Notification::make()
                        ->title('Artisan assigné : ' . $artisan->nom_complet)
                        ->success()
                        ->send();
                }),

            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema(TicketResource::applyShowFieldPermissions([

            Section::make('Avancement du dossier')
                ->icon('heroicon-o-arrows-right-left')
                ->schema([
                    TextEntry::make('statut')
                        ->label('État actuel')
                        ->badge()
                        ->formatStateUsing(fn($state) => $state instanceof TicketStatut ? $state->label() : $state)
                        ->color(fn($state) => $state instanceof TicketStatut ? $state->color() : 'gray')
                        ->icon(fn($state) => $state instanceof TicketStatut ? $state->icon() : null),

                    TextEntry::make('niveau_priorite')
                        ->label('Niveau de priorité')
                        ->badge()
                        ->formatStateUsing(fn($state) => $state?->label() ?? 'Non défini')
                        ->color(fn($state) => $state?->color() ?? 'gray'),

                    TextEntry::make('progression_pourcentage')
                        ->label('Progression du dossier')
                        ->formatStateUsing(fn($state) => $state . '%'),

                    TextEntry::make('duree_traitement_formatee')
                        ->label('Temps passé sur le dossier'),

                    IconEntry::make('sla_respecte')
                        ->label('Délai respecté ?')
                        ->boolean()
                        ->trueColor('success')
                        ->falseColor('danger'),
                ])
                ->columns(5),

            Section::make('Informations client')
                ->icon('heroicon-o-user')
                ->columns(3)
                ->schema([
                    TextEntry::make('contactParticulier.nom')
                        ->label('Nom du client')
                        ->formatStateUsing(
                            fn($state, $record) => trim(($record->contactParticulier?->prenom ?? '') . ' ' . ($record->contactParticulier?->nom ?? '')) ?: '—'
                        ),

                    TextEntry::make('contactParticulier.telephone')
                        ->label('Téléphone')
                        ->icon('heroicon-o-phone')
                        ->copyable(),

                    TextEntry::make('contactParticulier.adresse_complete')
                        ->label('Adresse')
                        ->icon('heroicon-o-map-pin'),

                    TextEntry::make('contactParticulier.type_logement')
                        ->label('Type de logement')
                        ->formatStateUsing(fn($state) => $state?->label() ?? '—')
                        ->badge(),

                    TextEntry::make('contactParticulier.statut_occupant')
                        ->label('Statut d\'occupation')
                        ->formatStateUsing(fn($state) => $state?->label() ?? '—')
                        ->badge(),
                ]),

            Section::make('Informations artisan')
                ->icon('heroicon-o-wrench-screwdriver')
                ->columns(3)
                ->schema([
                    TextEntry::make('artisan.nom')
                        ->label('Nom de l\'artisan')
                        ->formatStateUsing(
                            fn($state, $record) => $record->artisan?->nom_complet ?? '—'
                        )
                        ->placeholder('Non assigné'),

                    TextEntry::make('artisan.telephone_principal')
                        ->label('Téléphone')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('artisan.corps_de_metier')
                        ->label('Métier / Spécialité')
                        ->formatStateUsing(fn($state) => $state?->label() ?? '—')
                        ->badge(),

                    TextEntry::make('rdv_planifie_at')
                        ->label('Rendez-vous prévu')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('Non planifié'),

                    TextEntry::make('rappel_promise_at')
                        ->label('Rappel promis au client')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('—'),
                ]),

            Section::make('Informations générales')
                ->icon('heroicon-o-information-circle')
                ->columns(3)
                ->collapsible()
                ->schema([
                    TextEntry::make('reference')
                        ->label('Numéro de dossier')
                        ->copyable()
                        ->weight('bold'),

                    TextEntry::make('date_creation')
                        ->label('Date de création')
                        ->dateTime('d/m/Y H:i'),

                    TextEntry::make('date_cloture')
                        ->label('Date de clôture')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('En cours'),

                    TextEntry::make('operateur.prenom')
                        ->label('Opérateur responsable')
                        ->formatStateUsing(
                            fn($state, $record) => trim(($record->operateur?->prenom ?? '') . ' ' . ($record->operateur?->nom ?? '')) ?: '—'
                        ),

                    TextEntry::make('ringover_call_id')
                        ->label('ID appel Ringover')
                        ->copyable()
                        ->placeholder('—'),
                ]),

            Section::make('Notes et commentaires')
                ->icon('heroicon-o-document-text')
                ->collapsible()
                ->schema([
                    TextEntry::make('notes')
                        ->label('')
                        ->prose()
                        ->placeholder('Aucune note'),
                ]),
        ], [
            'progression_pourcentage' => 'statut',
            'duree_traitement_formatee' => 'statut',
            'sla_respecte' => 'niveau_priorite',
            'contactParticulier.nom' => 'contact_particulier_id',
            'contactParticulier.telephone' => 'contact_particulier_id',
            'contactParticulier.adresse_complete' => 'contact_particulier_id',
            'contactParticulier.type_logement' => 'contact_particulier_id',
            'contactParticulier.statut_occupant' => 'contact_particulier_id',
            'artisan.nom' => 'artisan_id',
            'artisan.telephone_principal' => 'artisan_id',
            'artisan.corps_de_metier' => 'corps_de_metier',
            'operateur.prenom' => 'operateur_id',
            'date_cloture' => 'statut',
        ]));
    }
}
