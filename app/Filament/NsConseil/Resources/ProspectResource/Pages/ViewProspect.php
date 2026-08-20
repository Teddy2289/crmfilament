<?php

namespace App\Filament\NsConseil\Resources\ProspectResource\Pages;

use App\Enums\ProspectStatut;
use App\Filament\NsConseil\Resources\ProspectResource;
use App\Filament\Widgets\HistoriqueModificationsWidget;
use App\Services\Phoning\FichePdfGenerationService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewProspect extends ViewRecord
{
    protected static string $resource = ProspectResource::class;

    protected function getFooterWidgets(): array
    {
        return [
            HistoriqueModificationsWidget::make([
                'modelType' => Prospect::class,
                'modelId' => $this->record->id,
            ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Modifier')
                ->icon('heroicon-o-pencil-square'),

            Action::make('lancer_appel_phoning')
                ->label('Lancer l\'appel')
                ->icon('heroicon-o-phone-arrow-up-right')
                ->color('primary')
                ->url(fn () => route('filament.ns-conseil.pages.phoning-workflow', [
                    'contact_id' => $this->record->id,
                    'contact_type' => 'prospect',
                ]))
                ->openUrlInNewTab(),

            Action::make('qualifier_qf')
                ->label('Qualifier QF')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => ! in_array($this->record->statut, [
                    ProspectStatut::KO,
                    ProspectStatut::QF,
                ]))
                ->requiresConfirmation()
                ->modalHeading('Qualifier ce prospect ?')
                ->modalDescription('Le statut passera à QF (Qualifié). Cette action notifiera le commercial assigné.')
                ->action(function () {
                    $this->record->qualifier();
                    Notification::make()
                        ->title('Prospect qualifié QF ✓')
                        ->success()
                        ->send();
                    $this->refreshFormData(['statut', 'qf_valide']);
                }),

            Action::make('valider_qf')
                ->label('Valider QF')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->visible(fn () => $this->record->statut === ProspectStatut::QF
                    && ! $this->record->qf_valide
                    && ! $this->record->converti_partenaire_id
                    && auth()->user()
                    && (auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || auth()->user()->isSuperviseur()))
                ->requiresConfirmation()
                ->modalHeading('Valider la qualification QF ?')
                ->modalDescription('Cette validation débloque la conversion de ce prospect en partenaire. Réservée aux responsables d\'équipe (CDC §6).')
                ->action(function () {
                    $this->record->validerQF(auth()->id());
                    Notification::make()
                        ->title('QF validé ✓')
                        ->body('Le prospect peut maintenant être converti en partenaire.')
                        ->success()
                        ->send();
                    $this->refreshFormData(['qf_valide', 'valide_par', 'qf_valide_at']);
                }),

            Action::make('marquer_ko')
                ->label('Marquer KO')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => ! in_array($this->record->statut, [
                    ProspectStatut::KO,
                    ProspectStatut::QF,
                ]))
                ->form([
                    Textarea::make('motif')
                        ->label('Motif KO')
                        ->required()
                        ->rows(3)
                        ->placeholder('Raison du refus, contexte...'),
                ])
                ->requiresConfirmation()
                ->modalHeading('Marquer comme KO ?')
                ->action(function (array $data) {
                    $this->record->marquerKO($data['motif']);
                    Notification::make()
                        ->title('Prospect marqué KO')
                        ->warning()
                        ->send();
                    $this->refreshFormData(['statut', 'motif_ko']);
                }),

            Action::make('programmer_rappel')
                ->label('Planifier rappel')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->visible(fn () => ! in_array($this->record->statut, [ProspectStatut::KO]))
                ->form([
                    DateTimePicker::make('rappel_at')
                        ->label('Date et heure du rappel')
                        ->required()
                        ->seconds(false)
                        ->minDate(now()->startOfMinute()),
                ])
                ->action(function (array $data) {
                    $this->record->programmerRappel(new \DateTime($data['rappel_at']));
                    Notification::make()
                        ->title('Rappel planifié ✓')
                        ->success()
                        ->send();
                    $this->refreshFormData(['rappel_planifie_at']);
                }),

            Action::make('ajouter_note')
                ->label('Ajouter une note')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('gray')
                ->form([
                    Textarea::make('note')
                        ->label('Note')
                        ->required()
                        ->rows(4)
                        ->placeholder('Compte rendu, information importante...'),
                ])
                ->action(function (array $data) {
                    $this->record->ajouterNote($data['note']);
                    Notification::make()
                        ->title('Note ajoutée ✓')
                        ->success()
                        ->send();
                    $this->refreshFormData(['description']);
                }),

            Action::make('convertir_partenaire')
                ->label('→ Convertir en Partenaire')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success')
                ->visible(fn () => $this->record->est_convertible_en_partenaire)
                ->requiresConfirmation()
                ->modalHeading('Convertir en Partenaire ?')
                ->modalDescription('Un nouveau partenaire sera créé à partir des données de ce prospect. Le prospect sera archivé et restera traçable depuis le partenaire.')
                ->action(function () {
                    try {
                        $partenaire = $this->record->convertirEnPartenaire();
                        Notification::make()
                            ->title('Converti en partenaire ✓')
                            ->body("Partenaire #{$partenaire->id} cree. Prospect archive.")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur de conversion')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('fiches_pdf')
                ->label('Fiches PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->form([
                    Select::make('fiche_type')
                        ->label('Type de fiche')
                        ->options([
                            'bleue' => 'Fiche bleue',
                            'jaune' => 'Fiche jaune',
                            'verte' => 'Fiche verte',
                        ])
                        ->default('bleue')
                        ->required()
                        ->native(false)
                        ->helperText('Choisissez la couleur de la fiche PDF à générer.'),
                ])
                ->modalHeading('Générer une fiche PDF')
                ->modalDescription('La fiche sera générée à partir des données du prospect et téléchargée au format PDF.')
                ->action(function (array $data) {
                    try {
                        $type = (string) ($data['fiche_type'] ?? 'bleue');
                        $service = app(FichePdfGenerationService::class);
                        $rdv = $this->record->rendezVous()->latest('date_heure')->first();
                        $ficheData = match ($type) {
                            'bleue' => $service->preparerDonneesFicheBleue($this->record, $rdv),
                            'jaune' => $service->preparerDonneesFicheJaune($this->record),
                            'verte' => $service->preparerDonneesFicheVerte($this->record),
                            default => throw new \InvalidArgumentException('Type de fiche invalide.'),
                        };
                        $filename = $service->genererNomFichier($type, $this->record);
                        $url = $service->generer($type, $ficheData, $filename);
                        Notification::make()
                            ->title('Fiche PDF générée')
                            ->body("La fiche {$type} a été générée pour ce prospect.")
                            ->success()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('telecharger')
                                    ->label('Télécharger le PDF')
                                    ->url($url)
                                    ->openUrlInNewTab(),
                            ])
                            ->send();
                    } catch (\Throwable $e) {
                        report($e);
                        Notification::make()
                            ->title('Erreur de génération PDF')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
