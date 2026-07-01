<?php

namespace App\Filament\NsConseil\Resources\ProspectResource\Pages;

use App\Enums\ProspectStatut;
use App\Filament\NsConseil\Resources\ProspectResource;
use App\Models\FicheTemplate;
use App\Services\Aopia\FicheGenerationService;
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

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Modifier')
                ->icon('heroicon-o-pencil-square'),

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
                        ->minDate(now()),
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

            Action::make('generer_fiche')
                ->label('Générer fiche Word')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->visible(fn () => FicheTemplate::where('actif', true)->exists())
                ->form([
                    Select::make('fiche_template_id')
                        ->label('Modèle de fiche')
                        ->options(fn () => FicheTemplate::where('actif', true)
                            ->get()
                            ->mapWithKeys(fn (FicheTemplate $t) => [
                                $t->id => "{$t->type_label} — {$t->nom}",
                            ]))
                        ->required()
                        ->native(false)
                        ->helperText('Sélectionnez le modèle de fiche à générer pour ce prospect.'),
                ])
                ->modalHeading('Générer une fiche Word')
                ->modalDescription('Le document sera généré à partir des données du prospect et enregistré dans ses documents.')
                ->action(function (array $data) {
                    try {
                        $template = FicheTemplate::findOrFail($data['fiche_template_id']);
                        $service = app(FicheGenerationService::class);
                        $rdv = $this->record->rendezVous()->latest('date_heure')->first();
                        $document = $service->generer($template, $this->record, $rdv);

                        Notification::make()
                            ->title('Fiche générée')
                            ->body("Document « {$document->nom_fichier} » créé et lié au prospect.")
                            ->success()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('telecharger')
                                    ->label('Télécharger')
                                    ->url(Storage::url($document->path))
                                    ->openUrlInNewTab(),
                            ])
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur de génération')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
