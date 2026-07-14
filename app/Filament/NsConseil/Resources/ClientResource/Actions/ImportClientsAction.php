<?php
namespace App\Filament\NsConseil\Resources\ClientResource\Actions;

use App\Filament\NsConseil\Resources\ClientResource\Import\ImportResolver;
use App\Jobs\ImportClientsJob;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
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
                // qui wrape un fichier temp PHP, supprimé à la fin de la requête HTTP.
                // Comme l'import tourne maintenant en job de queue (donc dans une
                // requête/processus différent, potentiellement bien plus tard), on
                // DOIT recopier le fichier vers un stockage persistant avant que la
                // requête courante ne se termine.
                $upload = $data['file'];

                if (! $upload instanceof TemporaryUploadedFile) {
                    Notification::make()
                        ->title('Fichier invalide')
                        ->body('Le fichier uploadé est introuvable ou dans un format inattendu.')
                        ->danger()
                        ->send();

                    return;
                }

                $extension = $upload->getClientOriginalExtension() ?: 'xlsx';
                $storedPath = $upload->storeAs(
                    'imports/clients',
                    uniqid('import_', true).'.'.$extension,
                    'local'
                );

                if (! $storedPath || ! Storage::disk('local')->exists($storedPath)) {
                    Notification::make()
                        ->title('Fichier introuvable')
                        ->body('Le fichier n\'a pas pu être stocké avant traitement.')
                        ->danger()
                        ->send();

                    return;
                }

                ImportClientsJob::dispatch(
                    $storedPath,
                    $data['force_model'] ?? null,
                    $data['strategy'] ?? 'merge',
                    auth()->id(),
                );

                Notification::make()
                    ->title('Import lancé')
                    ->body('Le fichier est en cours de traitement en arrière-plan. Vous recevrez une notification (icône cloche) une fois l\'import terminé — inutile de garder cette page ouverte.')
                    ->success()
                    ->send();
            });
    }
}
