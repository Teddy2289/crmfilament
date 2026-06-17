<?php

namespace App\Services\Aopia;

use App\Enums\OrganizationCategory;
use App\Models\Document;
use App\Models\FicheTemplate;
use App\Models\Prospect;
use App\Models\RendezVous;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;

class FicheGenerationService
{
    /**
     * Génère un document Word à partir d'un template et d'un prospect.
     *
     * @return Document Le document créé et lié au prospect
     */
    public function generer(FicheTemplate $template, Prospect $prospect, ?RendezVous $rdv = null): Document
    {
        $templatePath = Storage::path($template->template_path);

        if (! file_exists($templatePath)) {
            throw new RuntimeException("Le fichier template n'existe pas : {$template->template_path}");
        }

        $processor = new TemplateProcessor($templatePath);
        $values = $this->resoudreValeurs($template, $prospect, $rdv);

        foreach ($values as $placeholder => $value) {
            $key = trim($placeholder, '${}');
            $processor->setValue($key, $value ?? '');
        }

        $outputDir = 'fiches-generees/'.date('Y/m');
        Storage::makeDirectory($outputDir);

        $filename = $this->genererNomFichier($template, $prospect);
        $outputPath = $outputDir.'/'.$filename;
        $absoluteOutput = Storage::path($outputPath);

        $processor->saveAs($absoluteOutput);

        return Document::create([
            'nom_fichier' => $filename,
            'categorie' => OrganizationCategory::FichesProspection,
            'path' => $outputPath,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'taille' => filesize($absoluteOutput),
            'documentable_type' => Prospect::class,
            'documentable_id' => $prospect->id,
            'uploaded_by' => auth()->id(),
        ]);
    }

    /**
     * Génère toutes les fiches auto pour un code statut phoning donné.
     *
     * @return list<Document>
     */
    public function genererAutoParStatut(string $statutPhoningCode, Prospect $prospect, ?RendezVous $rdv = null): array
    {
        $templates = FicheTemplate::autoGenerationPourStatut($statutPhoningCode);
        $documents = [];

        foreach ($templates as $template) {
            $documents[] = $this->generer($template, $prospect, $rdv);
        }

        return $documents;
    }

    /**
     * Résout les valeurs des placeholders à partir du prospect et du RDV.
     */
    private function resoudreValeurs(FicheTemplate $template, Prospect $prospect, ?RendezVous $rdv = null): array
    {
        $mapping = $template->placeholders ?? $this->mappingParDefaut();
        $values = [];

        foreach ($mapping as $placeholder => $fieldPath) {
            $values[$placeholder] = $this->resoudreChamp($fieldPath, $prospect, $rdv);
        }

        return $values;
    }

    /**
     * Résout un champ à partir d'un chemin (ex: "raison_sociale|nom" = fallback).
     */
    private function resoudreChamp(string $fieldPath, Prospect $prospect, ?RendezVous $rdv = null): string
    {
        // Support des fallbacks avec |
        $alternatives = explode('|', $fieldPath);

        foreach ($alternatives as $field) {
            $field = trim($field);
            $value = $this->extraireValeur($field, $prospect, $rdv);

            if (! blank($value)) {
                return (string) $value;
            }
        }

        return '';
    }

    private function extraireValeur(string $field, Prospect $prospect, ?RendezVous $rdv = null): mixed
    {
        // Champs spéciaux calculés
        return match ($field) {
            'adresse_complete' => $prospect->adresse_complete,
            'teleprospecteur_nom' => $prospect->teleprospecteur?->nom_complet
                ?? ($prospect->teleprospecteur ? "{$prospect->teleprospecteur->prenom} {$prospect->teleprospecteur->nom}" : ''),
            'commercial_nom' => $prospect->commercial?->nom_complet
                ?? ($prospect->commercial ? "{$prospect->commercial->prenom} {$prospect->commercial->nom}" : ''),
            'date_generation' => now()->format('d/m/Y'),
            'date_premier_contact_format' => $prospect->date_premier_contact?->format('d/m/Y') ?? '',
            'rdv_date_heure' => $rdv?->date_heure?->format('d/m/Y — H:i') ?? '',
            'rdv_lieu' => $rdv ? ($rdv->lieu ?: $rdv->adresse_lieu) : '',
            'cse_secretaire_complet' => trim(($prospect->cse_secretaire_prenom ?? '').' '.($prospect->cse_secretaire_nom ?? '')),
            'cse_tresorier_complet' => trim(($prospect->cse_tresorier_prenom ?? '').' '.($prospect->cse_tresorier_nom ?? '')),
            default => $prospect->{$field} ?? '',
        };
    }

    private function genererNomFichier(FicheTemplate $template, Prospect $prospect): string
    {
        $raison = preg_replace('/[^a-zA-Z0-9_-]/', '_', $prospect->raison_sociale ?: $prospect->nom ?: 'prospect');
        $raison = substr($raison, 0, 50);
        $date = now()->format('Ymd_His');

        return "Fiche_{$template->type}_{$raison}_{$date}.docx";
    }

    /**
     * Mapping par défaut placeholder → champ prospect (utilisé si aucun mapping custom).
     */
    public static function mappingParDefaut(): array
    {
        return [
            '${RAISON_SOCIALE}' => 'raison_sociale|nom',
            '${SECTEUR_ACTIVITE}' => 'secteur_activite',
            '${NB_SALARIES}' => 'nb_salaries',
            '${ADRESSE_COMPLETE}' => 'adresse_complete',
            '${DEPARTEMENT}' => 'departement',
            '${VILLE}' => 'ville',
            '${CODE_POSTAL}' => 'code_postal',
            '${TELEPHONE}' => 'telephone',
            '${INTERLOCUTEUR_NOM}' => 'interlocuteur_nom',
            '${INTERLOCUTEUR_FONCTION}' => 'interlocuteur_fonction',
            '${INTERLOCUTEUR_TELEPHONE}' => 'interlocuteur_telephone',
            '${INTERLOCUTEUR_EMAIL}' => 'interlocuteur_email',
            '${TELEPROSPECTEUR}' => 'teleprospecteur_nom',
            '${COMMERCIAL}' => 'commercial_nom',
            '${DATE_APPEL}' => 'date_premier_contact_format',
            '${DATE_GENERATION}' => 'date_generation',
            '${NOTES}' => 'description',
            '${RDV_DATE_HEURE}' => 'rdv_date_heure',
            '${RDV_LIEU}' => 'rdv_lieu',
            '${CSE_SECRETAIRE}' => 'cse_secretaire_complet',
            '${CSE_TRESORIER}' => 'cse_tresorier_complet',
            '${CSE_NB_ELUS}' => 'cse_nb_elus',
        ];
    }
}
