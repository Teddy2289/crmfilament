<?php

namespace App\Filament\NsConseil\Resources\ProspectResource\Actions;

use App\Enums\OrganizationType;
use App\Enums\ProspectStatut;
use App\Filament\NsConseil\Resources\ProspectResource\Import\ProspectImportResolver;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportProspectsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'import_prospects';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Importer Excel')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('success')
            ->modalHeading('Importer des prospects depuis Excel')
            ->modalDescription(
                'Colonnes reconnues : Nom / Raison sociale, Téléphone, Département, CP, Ville, '.
                "Secteur d'activité, Nb salariés, CA, Email, Statut, Conseiller / Téléprospecteur, ".
                'Date rappel, Commentaires.'
            )
            ->modalWidth('xl')
            ->form([
                Forms\Components\FileUpload::make('file')
                    ->label('Fichier Excel (.xlsx)')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                    ])
                    ->maxSize(20480)
                    ->required()
                    ->storeFiles(false)
                    ->columnSpanFull(),

                Forms\Components\Section::make('Valeurs par défaut appliquées à toutes les lignes')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->description('Utilisées si la colonne correspondante est absente du fichier.')
                    ->schema([
                        Forms\Components\Select::make('statut')
                            ->label('Statut initial')
                            ->options(collect(ProspectStatut::cases())
                                ->mapWithKeys(fn ($e) => [$e->value => $e->label()])
                                ->toArray()
                            )
                            ->default(ProspectStatut::AC->value)
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('type_pressenti')
                            ->label('Type pressenti par défaut')
                            ->options(OrganizationType::class)
                            ->nullable()
                            ->native(false),

                        Forms\Components\Select::make('teleprospecteur_id')
                            ->label('Téléprospecteur assigné')
                            ->options(function () {
                                return User::whereIn('role_cache', [
                                    'teleprospecteur', 'commercial', 'team_leader', 'administrateur',
                                ])
                                    ->orderBy('nom')
                                    ->get()
                                    ->mapWithKeys(fn (User $u) => [$u->id => "{$u->prenom} {$u->nom}"])
                                    ->toArray();
                            })
                            ->searchable()
                            ->nullable(),

                        Forms\Components\TextInput::make('secteur_activite')
                            ->label("Secteur d'activité par défaut")
                            ->nullable()
                            ->placeholder('ex: Industrie, Commerce…'),
                    ])
                    ->columns(2),
            ])
            ->action(function (array $data): void {
                $upload = $data['file'];

                if ($upload instanceof TemporaryUploadedFile) {
                    $resolvedPath = $upload->getRealPath();
                } elseif (is_string($upload) && file_exists($upload)) {
                    $resolvedPath = $upload;
                } else {
                    Notification::make()
                        ->title('Fichier invalide')
                        ->body('Le fichier est introuvable ou dans un format inattendu.')
                        ->danger()->send();

                    return;
                }

                if (! $resolvedPath || ! file_exists($resolvedPath)) {
                    Notification::make()
                        ->title('Fichier introuvable')
                        ->body('Chemin résolu : '.($resolvedPath ?? 'null'))
                        ->danger()->send();

                    return;
                }

                $defaults = [
                    'statut' => $data['statut'],
                    'type_pressenti' => $data['type_pressenti'] ?? null,
                    'teleprospecteur_id' => $data['teleprospecteur_id'] ?? null,
                    'secteur_activite' => $data['secteur_activite'] ?? null,
                ];

                try {
                    $results = ProspectImportResolver::importFile($resolvedPath, $defaults);
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Erreur lors de la lecture du fichier')
                        ->body($e->getMessage())
                        ->danger()->send();

                    return;
                }

                $totalCreated = 0;
                $totalUpdated = 0;
                $totalSkipped = 0;
                $allErrors = [];

                foreach ($results as $sheetName => $result) {
                    $totalCreated += $result['created'];
                    $totalUpdated += $result['updated'];
                    $totalSkipped += $result['skipped'];
                    foreach ($result['errors'] as $err) {
                        $allErrors[] = "[{$sheetName}] {$err}";
                    }
                }

                Notification::make()
                    ->title('Import terminé')
                    ->body("Créés : {$totalCreated} | Mis à jour : {$totalUpdated} | Ignorés : {$totalSkipped}")
                    ->success()->send();

                if (! empty($allErrors)) {
                    $preview = array_slice($allErrors, 0, 5);
                    $more = count($allErrors) - 5;
                    $errorBody = implode("\n", $preview);
                    if ($more > 0) {
                        $errorBody .= "\n… et {$more} autre(s) erreur(s).";
                    }

                    Notification::make()
                        ->title(count($allErrors).' ligne(s) en erreur')
                        ->body($errorBody)
                        ->warning()->persistent()->send();
                }
            });
    }
}
