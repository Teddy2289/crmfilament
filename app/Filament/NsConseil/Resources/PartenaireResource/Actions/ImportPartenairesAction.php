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
            ->modalHeading('Importer depuis Excel — feuille « MAJ »')
            ->modalDescription(
                'Seule la feuille "MAJ" est importée. '
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
                    ->columnSpanFull(),

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

                        Forms\Components\Select::make('nomenclature_interne')
                            ->label('Nomenclature interne')
                            ->options([
                                'CSE_PME' => 'CSE PME (< 50 salariés)',
                                'CSE_ETI' => 'CSE ETI (50–299 salariés)',
                                'CSE_GE' => 'CSE Grande entreprise (300+)',
                                'SYND_BRANCHE' => 'Syndicat de branche',
                                'SYND_INTERPRO' => 'Syndicat interprofessionnel',
                                'ENT_DIRECTE' => 'Entreprise directe',
                                'ASSOC' => 'Association',
                            ])
                            ->nullable(),
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
                    'nomenclature_interne' => $data['nomenclature_interne'] ?? null,
                ], fn ($v) => $v !== null);

                $strategy = $data['strategy'] ?? 'merge';

                try {
                    $result = PartenaireImportResolver::importFile($resolvedPath, $defaults, $strategy);
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Erreur lors de la lecture du fichier')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Import terminé — feuille MAJ')
                    ->body(
                        "Créés : {$result['created']} | "
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
