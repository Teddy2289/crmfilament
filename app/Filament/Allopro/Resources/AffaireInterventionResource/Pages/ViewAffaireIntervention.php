<?php

namespace App\Filament\Allopro\Resources\AffaireInterventionResource\Pages;

use App\Enums\StatutAffaireIntervention;
use App\Filament\Allopro\Resources\AffaireInterventionResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewAffaireIntervention extends ViewRecord
{
    protected static string $resource = AffaireInterventionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // P4 — Artisan confirme sa venue
            Actions\Action::make('confirmer')
                ->label('Confirmer artisan')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->visible(fn () => $this->record->statut === StatutAffaireIntervention::EnAttente)
                ->requiresConfirmation()
                ->modalHeading("L'artisan confirme sa venue ?")
                ->modalDescription("Cette action mettra le ticket associé en statut 'Artisan confirmé'.")
                ->action(function () {
                    $this->record->confirmerParArtisan();
                    $this->refreshFormData(['statut', 'date_confirmation_artisan', 'delai_confirmation_minutes']);
                    Notification::make()->title('Artisan confirmé')->success()->send();
                }),

            // Démarrage sur place
            Actions\Action::make('demarrer')
                ->label('Démarrer intervention')
                ->icon('heroicon-o-play')
                ->color('info')
                ->visible(fn () => $this->record->statut === StatutAffaireIntervention::Confirmee)
                ->requiresConfirmation()
                ->modalHeading("Démarrer l'intervention ?")
                ->action(function () {
                    $this->record->demarrer();
                    $this->refreshFormData(['statut', 'date_debut_reelle']);
                    Notification::make()->title('Intervention démarrée')->success()->send();
                }),

            // Clôture artisan avec compte-rendu
            Actions\Action::make('finaliser')
                ->label('Finaliser')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('teal')
                ->visible(fn () => $this->record->statut === StatutAffaireIntervention::EnCours)
                ->form([
                    Forms\Components\Textarea::make('compte_rendu_artisan')
                        ->label("Compte-rendu de l'artisan")
                        ->rows(4)
                        ->required(),
                    Forms\Components\Textarea::make('description_travaux_realises')
                        ->label('Description des travaux réalisés')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->finaliserParArtisan(
                        $data['compte_rendu_artisan'],
                        $data['description_travaux_realises'] ?? null
                    );
                    $this->refreshFormData(['statut', 'date_fin_reelle', 'duree_reelle_minutes', 'compte_rendu_artisan']);
                    Notification::make()->title('Intervention finalisée — ticket mis à jour')->success()->send();
                }),

            // Validation client + satisfaction à chaud
            Actions\Action::make('valider_client')
                ->label('Valider par client')
                ->icon('heroicon-o-hand-thumb-up')
                ->color('success')
                ->visible(fn () => $this->record->statut === StatutAffaireIntervention::Realisee)
                ->form([
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
                ])
                ->action(function (array $data) {
                    $this->record->validerParClient($data['satisfaction_immediate'] ?? null);
                    $this->refreshFormData(['statut', 'signature_client', 'date_signature_client', 'satisfaction_immediate']);
                    Notification::make()->title('Bon d\'intervention validé par le client')->success()->send();
                }),

            // Annulation
            Actions\Action::make('annuler')
                ->label('Annuler')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->statut?->estActive())
                ->form([
                    Forms\Components\Textarea::make('motif')
                        ->label("Motif d'annulation")
                        ->required()
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    $this->record->annuler($data['motif']);
                    $this->refreshFormData(['statut', 'motif_annulation']);
                    Notification::make()->title('Affaire annulée')->warning()->send();
                }),

            // Échec
            Actions\Action::make('echec')
                ->label('Déclarer échec')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->visible(fn () => $this->record->statut === StatutAffaireIntervention::EnCours)
                ->form([
                    Forms\Components\Textarea::make('motif')
                        ->label("Motif de l'échec")
                        ->required()
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    $this->record->declarerEchec($data['motif']);
                    $this->refreshFormData(['statut', 'motif_annulation']);
                    Notification::make()->title('Échec déclaré')->danger()->send();
                }),

            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            Section::make('Statut & SLA')
                ->icon('heroicon-o-chart-bar')
                ->columns(5)
                ->schema([
                    TextEntry::make('reference')
                        ->label('Référence')
                        ->weight('bold')
                        ->copyable(),

                    TextEntry::make('statut')
                        ->label('Statut')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state instanceof StatutAffaireIntervention ? $state->label() : $state)
                        ->color(fn ($state) => $state instanceof StatutAffaireIntervention ? $state->color() : 'gray')
                        ->icon(fn ($state) => $state instanceof StatutAffaireIntervention ? $state->icon() : null),

                    TextEntry::make('numero_tentative')
                        ->label('Tentative n°')
                        ->badge()
                        ->color(fn ($state) => $state > 1 ? 'warning' : 'gray'),

                    IconEntry::make('sla_respectee')
                        ->label('SLA P4')
                        ->boolean()
                        ->trueIcon('heroicon-o-check-circle')
                        ->falseIcon('heroicon-o-exclamation-circle')
                        ->trueColor('success')
                        ->falseColor('danger'),

                    TextEntry::make('delai_confirmation_formate')
                        ->label('Délai confirmation')
                        ->badge()
                        ->color(fn ($state, $record) => match (true) {
                            ! $record->delai_confirmation_minutes => 'gray',
                            $record->delai_confirmation_minutes <= 5 => 'success',
                            $record->delai_confirmation_minutes <= 30 => 'warning',
                            default => 'danger',
                        }),
                ]),

            Section::make('Ticket associé')
                ->icon('heroicon-o-ticket')
                ->columns(3)
                ->schema([
                    TextEntry::make('ticket.reference')
                        ->label('Référence ticket')
                        ->badge()
                        ->color('gray')
                        ->copyable(),

                    TextEntry::make('ticket.statut')
                        ->label('Statut ticket')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state?->label() ?? '—')
                        ->color(fn ($state) => $state?->color() ?? 'gray'),

                    TextEntry::make('ticket.niveau_priorite')
                        ->label('Priorité ticket')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state?->label() ?? '—')
                        ->color(fn ($state) => $state?->color() ?? 'gray'),

                    TextEntry::make('ticket.contactParticulier.nom')
                        ->label('Client')
                        ->formatStateUsing(fn ($state, $record) => trim(($record->ticket?->contactParticulier?->prenom ?? '').' '.($record->ticket?->contactParticulier?->nom ?? '')) ?: '—'
                        ),

                    TextEntry::make('ticket.contactParticulier.telephone')
                        ->label('Téléphone client')
                        ->icon('heroicon-o-phone')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('ticket.contactParticulier.adresse_complete')
                        ->label('Adresse intervention')
                        ->icon('heroicon-o-map-pin')
                        ->placeholder('—'),
                ]),

            Section::make('Artisan')
                ->icon('heroicon-o-user-circle')
                ->columns(4)
                ->schema([
                    TextEntry::make('artisan.nom')
                        ->label('Artisan')
                        ->formatStateUsing(fn ($state, $record) => $record->artisan?->nom_complet ?? '—')
                        ->weight('semibold'),

                    TextEntry::make('artisan.corps_de_metier')
                        ->label('Métier')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state?->label() ?? '—')
                        ->color(fn ($state) => $state?->color() ?? 'gray'),

                    TextEntry::make('artisan.telephone_principal')
                        ->label('Téléphone')
                        ->icon('heroicon-o-phone')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('artisan.numero_cti_transfert')
                        ->label('N° CTI transfert')
                        ->icon('heroicon-o-phone-arrow-up-right')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('canal_notification')
                        ->label('Canal notification')
                        ->badge()
                        ->color('info'),

                    TextEntry::make('operateurDispatch.prenom')
                        ->label('Dispatcher')
                        ->formatStateUsing(fn ($state, $record) => trim(($record->operateurDispatch?->prenom ?? '').' '.($record->operateurDispatch?->nom ?? '')) ?: '—'
                        ),
                ]),

            Section::make('Planning')
                ->icon('heroicon-o-calendar')
                ->columns(3)
                ->schema([
                    TextEntry::make('date_rdv_prevue')
                        ->label('RDV prévu')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('—'),

                    TextEntry::make('creneau_debut')
                        ->label('Créneau')
                        ->formatStateUsing(fn ($state, $record) => ($record->creneau_debut && $record->creneau_fin)
                                ? $record->creneau_debut.' – '.$record->creneau_fin
                                : '—'
                        ),

                    TextEntry::make('date_notification_artisan')
                        ->label('Notifié le')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('—'),

                    TextEntry::make('date_confirmation_artisan')
                        ->label('Confirmé le')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('En attente'),
                ]),

            Section::make('Réalisation')
                ->icon('heroicon-o-wrench-screwdriver')
                ->columns(3)
                ->collapsible()
                ->schema([
                    TextEntry::make('date_debut_reelle')
                        ->label('Début réel')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('—'),

                    TextEntry::make('date_fin_reelle')
                        ->label('Fin réelle')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('—'),

                    TextEntry::make('duree_reelle_formatee')
                        ->label('Durée réelle')
                        ->placeholder('—'),

                    TextEntry::make('description_travaux_realises')
                        ->label('Travaux réalisés')
                        ->prose()
                        ->placeholder('Non renseigné')
                        ->columnSpanFull(),

                    TextEntry::make('compte_rendu_artisan')
                        ->label('Compte-rendu artisan')
                        ->prose()
                        ->placeholder('Non renseigné')
                        ->columnSpanFull(),
                ]),

            Section::make('Validation client')
                ->icon('heroicon-o-hand-thumb-up')
                ->columns(3)
                ->collapsible()
                ->schema([
                    IconEntry::make('signature_client')
                        ->label('Bon signé')
                        ->boolean()
                        ->trueColor('success')
                        ->falseColor('gray'),

                    TextEntry::make('date_signature_client')
                        ->label('Signé le')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('—'),

                    TextEntry::make('satisfaction_immediate')
                        ->label('Satisfaction immédiate')
                        ->formatStateUsing(fn ($state) => $state ? $state.' / 5' : '—')
                        ->badge()
                        ->color(fn ($state) => match (true) {
                            $state === null => 'gray',
                            $state >= 4 => 'success',
                            $state === 3 => 'warning',
                            default => 'danger',
                        }),
                ]),

            Section::make('Notes & Motifs')
                ->icon('heroicon-o-document-text')
                ->columns(2)
                ->collapsible()
                ->schema([
                    TextEntry::make('notes_dispatch')
                        ->label('Notes dispatch P3')
                        ->prose()
                        ->placeholder('Aucune note'),

                    TextEntry::make('notes_intervention')
                        ->label('Notes intervention')
                        ->prose()
                        ->placeholder('Aucune note'),

                    TextEntry::make('motif_annulation')
                        ->label("Motif d'annulation / d'échec")
                        ->prose()
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
