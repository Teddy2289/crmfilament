<?php

namespace App\Filament\NsConseil\Actions;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Models\Partenaire;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportPartenairesAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Importer des partenaires')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('primary')
            ->form([
                FileUpload::make('file')
                    ->label('Fichier Excel')
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                    ->helperText('Format Excel (.xlsx, .xls)')
                    ->required()
                    ->disk('local')
                    ->directory('imports')
                    ->maxSize(10240),
            ])
            ->action(function (array $data) {
                $filePath = storage_path('app/' . $data['file']);
                
                if (!file_exists($filePath)) {
                    Notification::make()
                        ->danger()
                        ->title('Erreur')
                        ->body('Fichier introuvable')
                        ->send();
                    return;
                }

                try {
                    $spreadsheet = IOFactory::load($filePath);
                    $worksheet = $spreadsheet->getActiveSheet();
                    $rows = $worksheet->toArray();
                    
                    // Skip header row
                    $header = array_shift($rows);
                    
                    $imported = 0;
                    $errors = 0;
                    
                    foreach ($rows as $row) {
                        if (empty($row[0])) continue; // Skip empty rows
                        
                        try {
                            $data = array_combine($header, $row);
                            
                            Partenaire::updateOrCreate(
                                ['siret' => $data['siret'] ?? null],
                                [
                                    'nom' => $data['nom'] ?? '',
                                    'entreprise' => $data['entreprise'] ?? null,
                                    'type' => $this->mapType($data['type'] ?? null),
                                    'statut' => $this->mapStatut($data['statut'] ?? null),
                                    'adresse' => $data['adresse'] ?? null,
                                    'code_postal' => $data['code_postal'] ?? null,
                                    'ville' => $data['ville'] ?? null,
                                    'departement' => $data['departement'] ?? null,
                                    'telephone' => $data['telephone'] ?? null,
                                    'email' => $data['email'] ?? null,
                                    'secteur_activite' => $data['secteur_activite'] ?? null,
                                    'nb_salaries' => $data['nb_salaries'] ?? null,
                                    'chiffre_affaires' => $data['chiffre_affaires'] ?? null,
                                    'origine_contact' => $data['origine_contact'] ?? null,
                                ]
                            );
                            
                            $imported++;
                        } catch (\Exception $e) {
                            $errors++;
                        }
                    }
                    
                    // Delete uploaded file
                    Storage::disk('local')->delete($data['file']);
                    
                    Notification::make()
                        ->success()
                        ->title('Import terminé')
                        ->body("{$imported} partenaires importés avec succès" . ($errors > 0 ? " ({$errors} erreurs)" : ''))
                        ->send();
                        
                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Erreur d\'import')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }
    
    protected function mapType(?string $value): ?string
    {
        if (!$value) return null;
        
        $mapping = [
            'CSE' => OrganizationType::CSE->value,
            'Syndicat' => OrganizationType::Syndicat->value,
            'Entreprise directe' => OrganizationType::EntrepriseDirecte->value,
            'Association' => OrganizationType::Association->value,
            'Partenariat annulé' => OrganizationType::PartenariatAnnule->value,
        ];
        
        return $mapping[$value] ?? $value;
    }
    
    protected function mapStatut(?string $value): ?string
    {
        if (!$value) return OrganizationStatus::AProspecter->value;
        
        $mapping = [
            'À prospecter' => OrganizationStatus::AProspecter->value,
            'En cours de prospection' => OrganizationStatus::EnCoursProspection->value,
            'RDV en cours' => OrganizationStatus::RdvEnCours->value,
            'Signé accord cadre' => OrganizationStatus::SigneAccordCadre->value,
            'Convention d\'engagement' => OrganizationStatus::ConventionEngagement->value,
            'Refus' => OrganizationStatus::Refus->value,
        ];
        
        return $mapping[$value] ?? $value;
    }
}
