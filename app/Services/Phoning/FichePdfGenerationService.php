<?php

namespace App\Services\Phoning;

use App\Models\Prospect;
use App\Models\RendezVous;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FichePdfGenerationService
{
    /**
     * Génère un PDF pour un type de fiche donné.
     *
     * @param string $type Type de fiche (bleue, jaune, verte)
     * @param array $data Données pour le template
     * @param string $filename Nom du fichier de sortie
     * @return string Chemin du fichier généré
     */
    public function generer(string $type, array $data, string $filename): string
    {
        $view = $this->getViewForType($type);
        
        if (!view()->exists($view)) {
            throw new \Exception("Template PDF introuvable : {$view}");
        }

        $pdf = Pdf::loadView($view, ['data' => $data])
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        $outputDir = 'fiches-pdf/' . date('Y/m');
        Storage::disk('public')->makeDirectory($outputDir);

        $path = $outputDir . '/' . $filename;
        
        // Générer le PDF et le stocker
        Storage::disk('public')->put($path, $pdf->output());

        return Storage::disk('public')->url($path);
    }

    /**
     * Génère les données pour une fiche bleue (RDV confirmé).
     */
    public function preparerDonneesFicheBleue(Prospect $prospect, ?RendezVous $rdv = null, array $formData = []): array
    {
        return [
            'raison_sociale' => $prospect->raison_sociale ?? $prospect->nom,
            'secteur_activite' => $prospect->secteur_activite,
            'nb_salaries' => $prospect->nb_salaries,
            'siret' => $prospect->siret,
            'adresse_complete' => $prospect->adresse_complete,
            'interlocuteur_nom' => $formData['interlocuteur_nom'] ?? $prospect->interlocuteur_complet,
            'interlocuteur_fonction' => $formData['interlocuteur_fonction'] ?? $prospect->interlocuteur_fonction,
            'interlocuteur_telephone' => $formData['interlocuteur_telephone'] ?? $prospect->interlocuteur_telephone,
            'interlocuteur_email' => $formData['interlocuteur_email'] ?? $prospect->interlocuteur_email,
            'rdv_date_heure' => $rdv?->date_heure?->format('d/m/Y à H:i'),
            'rdv_lieu' => $formData['lieu_rdv'] ?? ($rdv?->lieu ?: $rdv?->adresse_lieu),
            'enregistrement_appel' => filled($rdv?->enregistrement_audio) ? 'Oui' : 'Non',
            'fiche_validee_tl' => 'En attente',
            'invitation_agenda_envoyee' => ($formData['invitation_agenda_envoyee'] ?? false) ? 'Oui' : 'Non',
            'cse_secretaire' => trim(($prospect->cse_secretaire_prenom ?? '') . ' ' . ($prospect->cse_secretaire_nom ?? '')),
            'cse_tresorier' => trim(($prospect->cse_tresorier_prenom ?? '') . ' ' . ($prospect->cse_tresorier_nom ?? '')),
            'cse_nb_elus' => $prospect->cse_nb_elus,
            'besoins_exprimes' => $formData['besoins_exprimes'] ?? $prospect->besoins_exprimes,
            'objections_soulevees' => $formData['objections_soulevees'] ?? $prospect->objections_soulevees,
            'points_attention_rdv' => $formData['points_attention_rdv'] ?? $prospect->points_attention_rdv,
            'teleprospecteur_nom' => $prospect->teleprospecteur?->nom_complet,
            'commercial_nom' => $prospect->commercial?->nom_complet,
            'date_appel' => $prospect->date_premier_contact?->format('d/m/Y'),
            'date_heure_dernier_appel' => $this->dateHeureDernierAppel($prospect),
            'date_generation' => now()->format('d/m/Y H:i'),
            'notes' => $formData['commentaires'] ?? $prospect->description,
        ];
    }

    /**
     * Génère les données pour une fiche jaune (CSE pas intéressé).
     */
    public function preparerDonneesFicheJaune(Prospect $prospect, array $formData = []): array
    {
        $dateRappelJ7 = $prospect->date_premier_contact 
            ? $prospect->date_premier_contact->addDays(7)->format('d/m/Y') 
            : now()->addDays(7)->format('d/m/Y');

        return [
            'raison_sociale' => $prospect->raison_sociale ?? $prospect->nom,
            'secteur_activite' => $prospect->secteur_activite,
            'nb_salaries' => $prospect->nb_salaries,
            'siret' => $prospect->siret,
            'adresse_complete' => $prospect->adresse_complete,
            'interlocuteur_nom' => $formData['interlocuteur_nom'] ?? $prospect->interlocuteur_complet,
            'interlocuteur_fonction' => $formData['interlocuteur_fonction'] ?? $prospect->interlocuteur_fonction,
            'interlocuteur_telephone' => $formData['interlocuteur_telephone'] ?? $prospect->interlocuteur_telephone,
            'interlocuteur_email' => $formData['interlocuteur_email'] ?? $prospect->interlocuteur_email,
            'cse_secretaire' => trim(($prospect->cse_secretaire_prenom ?? '') . ' ' . ($prospect->cse_secretaire_nom ?? '')),
            'cse_tresorier' => trim(($prospect->cse_tresorier_prenom ?? '') . ' ' . ($prospect->cse_tresorier_nom ?? '')),
            'cse_nb_elus' => $prospect->cse_nb_elus,
            'motif_refus' => $formData['commentaires'] ?? $prospect->motif_ko,
            'commentaires' => $formData['commentaires'] ?? $prospect->motif_ko,
            'date_email_assistante' => $prospect->date_premier_contact?->format('d/m/Y'),
            'date_appel' => $prospect->date_premier_contact?->format('d/m/Y'),
            'date_heure_dernier_appel' => $this->dateHeureDernierAppel($prospect),
            'date_rappel_j7' => $dateRappelJ7,
            'teleprospecteur_nom' => $prospect->teleprospecteur?->nom_complet,
            'commercial_nom' => $prospect->commercial?->nom_complet,
            'date_generation' => now()->format('d/m/Y H:i'),
            'notes' => $prospect->description,
        ];
    }

    /**
     * Génère les données pour une fiche verte (RDV à conclure).
     */
    public function preparerDonneesFicheVerte(Prospect $prospect, array $formData = []): array
    {
        return [
            'raison_sociale' => $prospect->raison_sociale ?? $prospect->nom,
            'secteur_activite' => $prospect->secteur_activite,
            'nb_salaries' => $prospect->nb_salaries,
            'siret' => $prospect->siret,
            'adresse_complete' => $prospect->adresse_complete,
            'interlocuteur_nom' => $formData['interlocuteur_nom'] ?? $prospect->interlocuteur_complet,
            'interlocuteur_fonction' => $formData['interlocuteur_fonction'] ?? $prospect->interlocuteur_fonction,
            'interlocuteur_telephone' => $formData['interlocuteur_telephone'] ?? $prospect->interlocuteur_telephone,
            'interlocuteur_email' => $formData['interlocuteur_email'] ?? $prospect->interlocuteur_email,
            'presence_cse' => $formData['presence_cse'] ?? $prospect->presence_cse,
            'jour_dispo_appel' => $formData['jour_dispo_appel'] ?? $prospect->jour_dispo_appel,
            'cse_secretaire' => trim(($prospect->cse_secretaire_prenom ?? '') . ' ' . ($prospect->cse_secretaire_nom ?? '')),
            'cse_tresorier' => trim(($prospect->cse_tresorier_prenom ?? '') . ' ' . ($prospect->cse_tresorier_nom ?? '')),
            'cse_nb_elus' => $prospect->cse_nb_elus,
            'date_rdv_a_prendre' => $formData['rappel_date'] ?? null,
            'commentaires' => $formData['commentaires'] ?? $prospect->description,
            'heure_rdv_a_prendre' => $formData['rappel_heure'] ?? null,
            'teleprospecteur_nom' => $prospect->teleprospecteur?->nom_complet,
            'commercial_nom' => $prospect->commercial?->nom_complet,
            'date_appel' => $prospect->date_premier_contact?->format('d/m/Y'),
            'date_heure_dernier_appel' => $this->dateHeureDernierAppel($prospect),
            'date_generation' => now()->format('d/m/Y H:i'),
            'notes' => $formData['commentaires'] ?? $prospect->description,
        ];
    }

    /**
     * Retourne la date et l'heure exactes du dernier appel enregistré pour le prospect.
     */
    private function dateHeureDernierAppel(Prospect $prospect): ?string
    {
        return $prospect->appels()
            ->latest('date_heure')
            ->first()?->date_heure?->format('d/m/Y H:i');
    }

    /**
     * Génère le nom de fichier pour une fiche.
     */
    public function genererNomFichier(string $type, Prospect $prospect): string
    {
        $raison = preg_replace('/[^a-zA-Z0-9_-]/', '_', $prospect->raison_sociale ?: $prospect->nom ?: 'prospect');
        $raison = substr($raison, 0, 50);
        $date = now()->format('Ymd_His');

        return "Fiche_{$type}_{$raison}_{$date}.pdf";
    }

    /**
     * Retourne la vue Blade pour un type de fiche.
     */
    private function getViewForType(string $type): string
    {
        return match ($type) {
            'bleue' => 'pdf.fiches.fiche-bleue',
            'jaune' => 'pdf.fiches.fiche-jaune',
            'verte' => 'pdf.fiches.fiche-verte',
            default => throw new \Exception("Type de fiche inconnu : {$type}"),
        };
    }
}