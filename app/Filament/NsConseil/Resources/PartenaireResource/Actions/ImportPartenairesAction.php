<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\Actions;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Filament\NsConseil\Resources\PartenaireResource\Import\PartenaireImportResolver;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportPartenairesAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'import_partenaires';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Importer Excel')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('success')
            ->modalHeading('Importer des partenaires depuis Excel')
            ->modalDescription("Format attendu : colonnes Raison sociale, Siret, CP, Ville, Adresse, nbr salariés, telephone 1, Chiffre d'affaires.")
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
                    ->description('Ces valeurs seront utilisées pour chaque partenaire importé.')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label("Type d'organisation")
                            ->options(OrganizationType::class)
                            ->default(OrganizationType::EntrepriseDirecte->value)
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('statut')
                            ->label('Statut initial')
                            ->options(OrganizationStatus::class)
                            ->default(OrganizationStatus::AProspecter->value)
                            ->required()
                            ->native(false),

                        // ✅ ->options() direct, pas ->relationship() qui nécessite un modèle parent
                        Forms\Components\Select::make('commercial_id')
                            ->label('Commercial assigné')
                            ->options(function () {
                                return User::whereIn('role_cache', ['commercial', 'team_leader', 'administrateur'])
                                    ->orderBy('nom')
                                    ->get()
                                    ->mapWithKeys(fn(User $u) => [$u->id => "{$u->prenom} {$u->nom}"])
                                    ->toArray();
                            })
                            ->searchable()
                            ->nullable(),

                        Forms\Components\Select::make('nomenclature_interne')
                            ->label('Nomenclature interne')
                            ->options([
                                'CSE_PME'       => 'CSE PME (< 50 salariés)',
                                'CSE_ETI'       => 'CSE ETI (50–299 salariés)',
                                'CSE_GE'        => 'CSE Grande entreprise (300+)',
                                'SYND_BRANCHE'  => 'Syndicat de branche',
                                'SYND_INTERPRO' => 'Syndicat interprofessionnel',
                                'ENT_DIRECTE'   => 'Entreprise directe',
                                'ASSOC'         => 'Association',
                            ])
                            ->default('ENT_DIRECTE')
                            ->required(),

                        Forms\Components\TextInput::make('secteur_activite')
                            ->label("Secteur d'activité par défaut")
                            ->default('Non renseigné')
                            ->placeholder('ex: Industrie, Commerce…'),
                    ])
                    ->columns(3),
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
                        ->body('Le fichier uploadé est introuvable ou dans un format inattendu.')
                        ->danger()
                        ->send();
                    return;
                }

                if (! $resolvedPath || ! file_exists($resolvedPath)) {
                    Notification::make()
                        ->title('Fichier introuvable')
                        ->body("Chemin résolu : " . ($resolvedPath ?? 'null'))
                        ->danger()
                        ->send();
                    return;
                }

                $defaults = [
                    'type'                 => $data['type'],
                    'statut'               => $data['statut'],
                    'commercial_id'        => $data['commercial_id'] ?? null,
                    'nomenclature_interne' => $data['nomenclature_interne'],
                    'secteur_activite'     => $data['secteur_activite'] ?? 'Non renseigné',
                ];

                try {
                    $results = PartenaireImportResolver::importFile($resolvedPath, $defaults);
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Erreur lors de la lecture du fichier')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                    return;
                }

                $totalCreated = 0;
                $totalUpdated = 0;
                $totalSkipped = 0;
                $allErrors    = [];

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
                    ->success()
                    ->send();

                if (! empty($allErrors)) {
                    $preview   = array_slice($allErrors, 0, 5);
                    $more      = count($allErrors) - 5;
                    $errorBody = implode("\n", $preview);
                    if ($more > 0) $errorBody .= "\n… et {$more} autre(s) erreur(s).";

                    Notification::make()
                        ->title(count($allErrors) . ' ligne(s) en erreur')
                        ->body($errorBody)
                        ->warning()
                        ->persistent()
                        ->send();
                }
            });
    }
}