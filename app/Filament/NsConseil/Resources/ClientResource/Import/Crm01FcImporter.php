<?php

namespace App\Filament\NsConseil\Resources\ClientResource\Import;

/**
 * Importer pour l'onglet "CRM 01FC" (30 colonnes, ~3 800 dossiers).
 *
 * Structure de la ref_client :
 *   PROGRAMME [NOM_TIERS]?
 *   ex: "TOSA BUR EXCEL EXPERT"  ou  "TOEIC NEVEU Aranud"
 *   Le programme est toujours en tête ; le nom du tiers peut apparaître en fin.
 *
 * Partenaire : colonne "Provenance" (nom brut).
 * Deux consultants distincts : "Consultant 1er Accueil" + "Consultant Formateur".
 * Colonne "Auteur" (back-office) : conservée en extra.
 * Pas de bloc Parrain dans cet onglet.
 */
class Crm01FcImporter extends BaseClientImporter
{
    public static function getName(): string
    {
        return 'CRM 01FC';
    }

    public static function getRequiredColumns(): array
    {
        return ['Réf. client', 'Tiers', 'Département', 'Type du tiers', 'Provenance'];
    }

    protected string $entiteCode = '01FC';

    // ── Mapping principal ───────────────────────────────────────────────────

    protected function mapRow(array $row): array
    {
        $ref = trim((string) ($row['Réf. client'] ?? ''));
        $tiers = trim((string) ($row['Tiers'] ?? ''));
        $provenance = trim((string) ($row['Provenance'] ?? ''));
        $auteur = trim((string) ($row['Auteur'] ?? ''));

        return [
            // ── Client ───────────────────────────────────────────────────
            'client' => array_filter([
                'nom_tiers' => $tiers ?: null,
                'telephone' => $this->formatTelephone($row['Téléphone'] ?? null),
                'email' => $this->normalizeEmail($row['Email'] ?? null),
                'ville' => trim((string) ($row['Ville'] ?? '')) ?: null,
                'code_postal' => $this->formatCodePostal($row['Code postal'] ?? null),
                'departement' => trim((string) ($row['Département'] ?? '')) ?: null,
                'entreprise' => trim((string) ($row['Entreprise'] ?? '')) ?: null,
                'date_naissance' => $this->parseDate($row['Date de naissance'] ?? null),
                'type_tiers' => trim((string) ($row['Type du tiers'] ?? '')) ?: null,
                '_partenaire_nomenclature' => $provenance ?: null,
                'extra_data' => array_filter([
                    'provenance' => $provenance ?: null,
                    'auteur' => $auteur ?: null,
                ], fn ($v) => $v !== null && $v !== ''),
            ], fn ($v) => $v !== null && $v !== ''),

            // ── DossierFormation ─────────────────────────────────────────
            'dossier' => array_filter([
                'ref_client' => $ref ?: null,
                'intitule_programme' => $ref
                    ? $this->extractProgramme01Fc($ref, $tiers)
                    : null,
                'etat' => $this->mapEtat($row['État'] ?? ''),
                'statut_formation' => $this->mapStatutFormation($row['Statut formation'] ?? ''),
                'montant_ht' => $this->parseFloat($row['Montant HT'] ?? null),
                // Typo source conservée intentionnellement ("Dare de vente" dans le fichier)
                'date_vente' => $this->parseDate(
                    $row['Dare de vente'] ?? $row['Date de vente'] ?? null
                ),
                'no_dossier_edof' => trim((string) ($row['(C) N° dossier EDOF'] ?? '')) ?: null,
                '_consultant_accueil_nom' => trim((string) ($row['Consultant 1er Accueil'] ?? '')) ?: null,
                '_consultant_formateur_nom' => trim((string) ($row['Consultant Formateur'] ?? '')) ?: null,
                '_entite_code' => $this->entiteCode,
                // Provenance (partenaire apporteur) et auteur conservés pour liaison manuelle
                'extra_provenance' => trim((string) ($row['Provenance'] ?? '')) ?: null,
                'extra_auteur' => trim((string) ($row['Auteur'] ?? '')) ?: null,
            ], fn ($v) => $v !== null && $v !== ''),

            // ── HeuresFormation ──────────────────────────────────────────
            'heures' => array_filter([
                // 01FC n'a pas les colonnes obligatoires/complémentaires/elearning
                'total_heures' => $this->parseFloat($row["Nombre d'heures de formation"] ?? null),
                'heures_realisees' => $this->parseFloat($row['Heures réalisées'] ?? null),
                'heures_restantes' => $this->parseFloat($row['Heures restantes'] ?? null),
            ], fn ($v) => $v !== null),

            // ── PlanningFormation ────────────────────────────────────────
            'planning' => array_filter([
                'date_lancement' => $this->parseDate($row['(F) Date de lancement'] ?? null),
                'date_debut' => $this->parseDate($row['(C) Date début de formation'] ?? null),
                'date_fin_theorique' => $this->parseDate($row['(C) Date de fin formation théorique'] ?? null),
                'date_certification' => $this->parseDate($row['Date certification'] ?? null),
                'date_questionnaire_chaud' => $this->parseDate($row['Date envoi questionnaire à chaud'] ?? null),
            ], fn ($v) => $v !== null),

            // ── Parrain ──────────────────────────────────────────────────
            // Pas de bloc parrain dans 01FC
            'parrain' => [],
        ];
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function formatTelephone(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', (string) $value);
        if (strlen($digits) === 10) {
            return implode(' ', str_split($digits, 2));
        }

        return (string) $value ?: null;
    }

    private function normalizeEmail(mixed $value): ?string
    {
        $email = strtolower(trim((string) ($value ?? '')));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function formatCodePostal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $cp = (string) (int) $value;

        return strlen($cp) <= 5 ? str_pad($cp, 5, '0', STR_PAD_LEFT) : $cp;
    }
}
