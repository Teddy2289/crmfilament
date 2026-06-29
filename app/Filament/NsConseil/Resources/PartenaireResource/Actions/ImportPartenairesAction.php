<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\Actions;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Filament\NsConseil\Resources\PartenaireResource\Import\PartenaireImportResolver;
use App\Models\Consultant;
use App\Models\EntiteCommerciale;
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
            ->modalHeading('Importer depuis Excel')
            ->modalDescription(
                'Sélectionnez les onglets à importer. '
                .'Les valeurs ci-dessous sont utilisées en fallback si une colonne est absente ou vide.'
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
                    ->columnSpanFull()
                    ->live()
                    ->afterStateUpdated(function (Forms\Components\FileUpload $component, $state) {
                        // Charger les onglets disponibles après sélection du fichier
                        if ($state instanceof TemporaryUploadedFile) {
                            try {
                                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($state->getRealPath());
                                $reader->setReadDataOnly(true);
                                $spreadsheet = $reader->load($state->getRealPath());
                                
                                $sheets = [];
                                foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                                    $sheets[$sheet->getTitle()] = $sheet->getTitle();
                                }
                                
                                $component->getContainer()->getComponent('target_sheets')->options($sheets);
                            } catch (\Throwable $e) {
                                // Ignorer les erreurs lors de la lecture
                            }
                        }
                    }),

                Forms\Components\Section::make('Onglets à importer')
                    ->icon('heroicon-o-table-cells')
                    ->schema([
                        Forms\Components\Select::make('target_sheets')
                            ->label('Onglets')
                            ->options([])
                            ->multiple()
                            ->searchable()
                            ->allowHtml()
                            ->helperText('Laissez vide pour importer tous les onglets, ou sélectionnez les onglets spécifiques.')
                            ->live()
                            ->afterStateUpdated(function (Forms\Components\Select $component, $state) {
                                // Les options seront remplies dynamiquement après le chargement du fichier
                            }),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Valeurs par défaut (fallback)')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Forms\Components\Select::make('entite_id')
                            ->label('Entité commerciale')
                            ->options(fn () => EntiteCommerciale::orderBy('nom')
                                ->pluck('nom', 'id')
                                ->toArray()
                            )
                            ->searchable()
                            ->nullable()
                            ->helperText('Utilisé si la colonne "Entité" est vide.'),

                        Forms\Components\Select::make('type')
                            ->label("Type d'organisation")
                            ->options(OrganizationType::class)
                            ->default(OrganizationType::CSE->value)
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('statut')
                            ->label('Statut initial')
                            ->options(OrganizationStatus::class)
                            ->default(OrganizationStatus::AProspecter->value)
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('conseiller_id')
                            ->label('Conseiller (fallback)')
                            ->options(fn () => Consultant::orderBy('nom')
                                ->get()
                                ->mapWithKeys(fn (Consultant $c) => [
                                    $c->id => trim("{$c->prenom} {$c->nom}"),
                                ])
                                ->toArray()
                            )
                            ->searchable()
                            ->nullable()
                            ->helperText('Utilisé si le conseiller n\'est pas trouvé en base.'),
                        Forms\Components\Placeholder::make('nomenclature_interne_info')
                            ->label('Nomenclature interne')
                            ->content('Generee automatiquement par ligne au format [Type] [Entreprise] [Ville].'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Stratégie pour partenaires existants')
                    ->icon('heroicon-o-arrows-merge')
                    ->description('Comportement si un partenaire existe déjà (même nom + ville).')
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
                            ->helperText('Fusion intelligente : préserve statut, commentaires, date signature et assignations'),
                    ])
                    ->columns(1),
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
                        ->body('Chemin résolu : '.($resolvedPath ?? 'null'))
                        ->danger()
                        ->send();

                    return;
                }

                $defaults = array_filter([
                    'entite_id' => $data['entite_id'] ?? null,
                    'type' => $data['type'],
                    'statut' => $data['statut'],
                    'conseiller_id' => $data['conseiller_id'] ?? null,
                ], fn ($v) => $v !== null);

                $strategy = $data['strategy'] ?? 'merge';
                $targetSheets = $data['target_sheets'] ?? null;

                try {
                    $result = PartenaireImportResolver::importFile($resolvedPath, $defaults, $strategy, $targetSheets);
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Erreur lors de la lecture du fichier')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                $sheetsProcessed = implode(', ', $result['sheets_processed'] ?? []);
                $sheetText = count($result['sheets_processed'] ?? []) > 1 
                    ? "Onglets importés : {$sheetsProcessed}" 
                    : "Onglet importé : {$sheetsProcessed}";

                Notification::make()
                    ->title('Import terminé')
                    ->body(
                        "{$sheetText}\n"
                        ."Créés : {$result['created']} | "
                        ."Mis à jour : {$result['updated']} | "
                        ."Ignorés : {$result['skipped']}"
                    )
                    ->success()
                    ->send();

                if (! empty($result['errors'])) {
                    $preview = array_slice($result['errors'], 0, 5);
                    $more = count($result['errors']) - 5;
                    $errorBody = implode("\n", $preview);
                    if ($more > 0) {
                        $errorBody .= "\n… et {$more} autre(s) erreur(s).";
                    }

                    Notification::make()
                        ->title(count($result['errors']).' ligne(s) en erreur')
                        ->body($errorBody)
                        ->warning()
                        ->persistent()
                        ->send();
                }
            });
    }
}
