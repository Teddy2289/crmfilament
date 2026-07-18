<?php

namespace App\Filament\NsConseil\Actions;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Models\Client;
use App\Models\Partenaire;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportMultiSheetAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'import_multi_sheet';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Import multi-onglets (Partenaires + Clients)')
            ->icon('heroicon-o-table-cells')
            ->color('primary')
            ->form([
                FileUpload::make('file')
                    ->label('Fichier Excel multi-onglets')
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->helperText('Fichier Excel avec onglets "Partenaires" et "Clients"')
                    ->required()
                    ->disk('local')
                    ->directory('imports')
                    ->maxSize(10240),
            ])
            ->action(function (array $data) {
                $filePath = Storage::disk('local')->path($data['file']);
                
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
                    
                    $results = [
                        'partenaires' => ['imported' => 0, 'errors' => 0],
                        'clients' => ['imported' => 0, 'errors' => 0],
                    ];
                    
                    // Import Partenaires
                    if ($spreadsheet->sheetNameExists('Partenaires')) {
                        $worksheet = $spreadsheet->getSheetByName('Partenaires');
                        $rows = $worksheet->toArray();
                        $header = array_shift($rows);
                        
                        foreach ($rows as $row) {
                            if (empty($row[0])) continue;
                            
                            try {
                                $data = array_combine($header, $row);
                                $siret = trim((string) ($data['siret'] ?? '')) ?: null;

                                $attributes = [
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
                                ];

                                // Le SIRET identifie une entreprise de façon fiable ; sans lui,
                                // on évite de faire correspondre plusieurs lignes distinctes
                                // sur un même partenaire "siret = null" (updateOrCreate collision)
                                // et on retombe sur nom + ville.
                                if ($siret) {
                                    Partenaire::updateOrCreate(['siret' => $siret], $attributes);
                                } else {
                                    $existing = Partenaire::where('nom', $attributes['nom'])
                                        ->where('ville', $attributes['ville'])
                                        ->first();

                                    if ($existing) {
                                        $existing->update($attributes);
                                    } else {
                                        Partenaire::create($attributes + ['siret' => null]);
                                    }
                                }

                                $results['partenaires']['imported']++;
                            } catch (\Exception $e) {
                                $results['partenaires']['errors']++;
                            }
                        }
                    }
                    
                    // Import Clients
                    if ($spreadsheet->sheetNameExists('Clients')) {
                        $worksheet = $spreadsheet->getSheetByName('Clients');
                        $rows = $worksheet->toArray();
                        $header = array_shift($rows);
                        
                        foreach ($rows as $row) {
                            if (empty($row[0])) continue;
                            
                            try {
                                $data = array_combine($header, $row);
                                
                                Client::updateOrCreate(
                                    ['email' => $data['email'] ?? null],
                                    [
                                        'civilite' => $data['civilite'] ?? null,
                                        'prenom' => $data['prenom'] ?? null,
                                        'nom_tiers' => $data['nom_tiers'] ?? '',
                                        'telephone' => $data['telephone'] ?? null,
                                        'adresse' => $data['adresse'] ?? null,
                                        'code_postal' => $data['code_postal'] ?? null,
                                        'ville' => $data['ville'] ?? null,
                                        'departement' => $data['departement'] ?? null,
                                        'region' => $data['region'] ?? null,
                                        'date_naissance' => $this->parseDate($data['date_naissance'] ?? null),
                                        'entreprise' => $data['entreprise'] ?? null,
                                        'type_tiers' => $data['type_tiers'] ?? null,
                                        'etat' => $data['etat'] ?? null,
                                        'montant_cpf' => $data['montant_cpf'] ?? null,
                                        'ne_plus_contacter' => $data['ne_plus_contacter'] ?? false,
                                    ]
                                );
                                
                                $results['clients']['imported']++;
                            } catch (\Exception $e) {
                                $results['clients']['errors']++;
                            }
                        }
                    }
                    
                    // Delete uploaded file
                    Storage::disk('local')->delete($data['file']);
                    
                    $message = "Import terminé :\n";
                    $message .= "- Partenaires : {$results['partenaires']['imported']} importés";
                    if ($results['partenaires']['errors'] > 0) {
                        $message .= " ({$results['partenaires']['errors']} erreurs)";
                    }
                    $message .= "\n- Clients : {$results['clients']['imported']} importés";
                    if ($results['clients']['errors'] > 0) {
                        $message .= " ({$results['clients']['errors']} erreurs)";
                    }
                    
                    Notification::make()
                        ->success()
                        ->title('Import multi-onglets terminé')
                        ->body($message)
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
    
    protected function parseDate(?string $value): ?string
    {
        if (!$value) return null;
        
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
