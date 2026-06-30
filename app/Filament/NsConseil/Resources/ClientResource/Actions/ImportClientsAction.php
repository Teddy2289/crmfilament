<?php

namespace App\Filament\NsConseil\Resources\ClientResource\Actions;

use App\Filament\NsConseil\Resources\ClientResource\Import\ImportResolver;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportClientsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'import_clients';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Importer Excel')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('success')
            ->modalHeading('Importer des clients depuis Excel')
            ->modalDescription('Formats acceptés : .xlsx — CRM LIKE, CRM AOPIA-ABO, CRM 01FC.')
            ->modalWidth('lg')
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
                    ->helperText('Le modèle est détecté automatiquement. Vous pouvez le forcer ci-dessous.'),

                Forms\Components\Select::make('force_model')
                    ->label('Forcer un modèle (optionnel)')
                    ->placeholder('Détection automatique')
                    ->options(ImportResolver::getOptions())
                    ->searchable()
                    ->helperText('Laissez vide pour laisser le système détecter le modèle automatiquement.'),

                Forms\Components\Section::make('Stratégie pour clients existants')
                        ->icon('heroicon-o-arrow-path-rounded-square')

                    ->description('Comportement si un client existe déjà (même ref_client ou email).')
                    ->schema([
                        Forms\Components\Select::make('strategy')
                            ->label('Stratégie de mise à jour')
                            ->options([
                                'merge' => 'Fusion intelligente (recommandé)',
                                'overwrite' => 'Écraser toutes les données',
                                'skip' => 'Ignorer les existants',
                            ])
                            ->default('merge')
                            ->required()
                            ->native(false)
                            ->helperText('Fusion intelligente : préserve état (si ≠ prospect), statut_formation (si ≠ a_venir), parrain, consultants et notes'),
                    ])
                    ->columns(1),
            ])
            ->action(function (array $data): void {
                // Avec storeFiles(false), $data['file'] est un TemporaryUploadedFile (Livewire)
                // qui wrape le fichier temp PHP. On récupère le chemin réel via getRealPath().
                $upload = $data['file'];

                if ($upload instanceof TemporaryUploadedFile) {
                    $resolvedPath = $upload->getRealPath();
                } elseif (is_string($upload) && file_exists($upload)) {
                    // Fallback : chemin brut passé directement
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
                        ->body('Chemin résolu : '.($resolvedPath ?? 'null'))
                        ->danger()
                        ->send();

                    return;
                }

                try {
                    $results = ImportResolver::importFile(
                        $resolvedPath,
                        $data['force_model'] ?? null,
                        $data['strategy'] ?? 'merge'
                    );
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Erreur lors de la lecture du fichier')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    return;
                }
                // Pas d'unlink : c'est un fichier temp PHP, le GC s'en charge

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

                $modelNames = implode(', ', array_unique(
                    array_filter(array_column($results, 'model'))
                ));

                $body = "Créés : {$totalCreated} | Mis à jour : {$totalUpdated} | Ignorés : {$totalSkipped}";
                if ($modelNames) {
                    $body .= "\nModèle(s) : {$modelNames}";
                }

                Notification::make()
                    ->title('Import terminé')
                    ->body($body)
                    ->success()
                    ->send();

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
                        ->warning()
                        ->persistent()
                        ->send();
                }
            });
    }
}
