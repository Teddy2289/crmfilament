<?php

namespace App\Filament\NsConseil\Resources\ClientResource\Import;

/**
 * Importer pour l'onglet "CRM AOPIA-ABO" (41 colonnes, ~4 300 dossiers).
 *
 * Structure de la ref_client :
 *   PROGRAMME NOM_TIERS AOPIA2
 *   ex: "TOSA PHOTOSHOP BODET Jean-Philippe AOPIA2"
 *
 * Partenaire : colonne "Interlocuteur" (nom brut du partenaire apporteur).
 * Bloc Parrain : identique à LIKE (NOM PRENOM / Tél / email / Adresse postale /
 *               Code postal.1 / Ville.1 / Commentaires / Super parrain /
 *               Date de création du parrain).
 */
class CrmAopiaAboImporter extends BaseClientImporter
{
    public static function getName(): string
    {
        return 'CRM AOPIA-ABO';
    }

    public static function getRequiredColumns(): array
    {
        return ['Réf. client', 'Tiers', 'Interlocuteur', 'Montant cpf'];
    }

    protected string $entiteCode = 'AOPIA-ABO';

    // ── Mapping principal ───────────────────────────────────────────────────

    protected function mapRow(array $row): array
    {
        $ref = trim((string) ($row['Réf. client'] ?? ''));
        $tiers = trim((string) ($row['Tiers'] ?? ''));
        $interlocuteur = trim((string) ($row['Interlocuteur'] ?? ''));
        $suiviClient = trim((string) ($row['Suivi actuel du Client'] ?? ''));

        return [
            // ── Client ───────────────────────────────────────────────────
            'client' => array_filter([
                'nom_tiers' => $tiers ?: null,
                'telephone' => $this->formatTelephone($row['Téléphone'] ?? null),
                'email' => $this->normalizeEmail($row['Email'] ?? null),
                'ville' => trim((string) ($row['Ville'] ?? '')) ?: null,
                'code_postal' => $this->formatCodePostal($row['Code postal'] ?? null),
                'adresse' => trim((string) ($row['Adresse'] ?? '')) ?: null,
                'entreprise' => trim((string) ($row['Entreprise'] ?? '')) ?: null,
                'date_naissance' => $this->parseDate($row['Date de naissance'] ?? null),
                'ne_plus_contacter' => $this->parseBool($row['Ne plus contacter'] ?? false),
                '_partenaire_nomenclature' => $interlocuteur ?: null,
                'extra_data' => array_filter([
                    'interlocuteur' => $interlocuteur ?: null,
                    'suivi_client' => $suiviClient ?: null,
                ], fn ($v) => $v !== null && $v !== ''),
            ], fn ($v) => $v !== null && $v !== ''),

            // ── DossierFormation ─────────────────────────────────────────
            'dossier' => array_filter([
                'ref_client' => $ref ?: null,
                'intitule_programme' => $ref && $tiers
                    ? $this->extractProgrammeAopia($ref, $tiers)
                    : null,
                'etat' => $this->mapEtat($row['État'] ?? ''),
                'statut_formation' => $this->mapStatutFormation($row['Statut formation'] ?? ''),
                'montant_ht' => $this->parseFloat($row['Montant HT'] ?? null),
                'montant_cpf' => $this->parseFloat($row['Montant cpf'] ?? null),
                'date_vente' => $this->parseDate($row['Date de vente'] ?? null),
                'no_dossier_edof' => trim((string) ($row['(C) N° dossier EDOF'] ?? '')) ?: null,
                '_consultant_formateur_nom' => trim((string) ($row['Consultant Formateur'] ?? '')) ?: null,
                '_entite_code' => $this->entiteCode,
                // Pas de Consultant 1er accueil dans cet onglet
            ], fn ($v) => $v !== null && $v !== ''),

            // ── HeuresFormation ──────────────────────────────────────────
            'heures' => array_filter([
                'heures_obligatoires' => $this->parseFloat($row['Heures de formation obligatoires'] ?? null),
                'heures_complementaires' => $this->parseFloat($row['Heures de formation complémentaires'] ?? null),
                'total_heures' => $this->parseFloat($row["Nombre d'heures de formation"] ?? null),
                'heures_realisees' => $this->parseFloat($row['Heures réalisées'] ?? null),
                'heures_restantes' => $this->parseFloat($row['Heures restantes'] ?? null),
            ], fn ($v) => $v !== null),

            // ── PlanningFormation ────────────────────────────────────────
            'planning' => array_filter([
                'date_lancement' => $this->parseDate($row['Date de lancement'] ?? null),
                'date_debut' => $this->parseDate($row['(C) Date début de formation'] ?? null),
                'date_fin_theorique' => $this->parseDate($row['(C) Date de fin formation théorique'] ?? null),
                'date_certification' => $this->parseDate($row['Date certification'] ?? null),
                'date_questionnaire_chaud' => $this->parseDate($row['Date envoi questionnaire à chaud'] ?? null),
            ], fn ($v) => $v !== null),

            // ── Parrain ──────────────────────────────────────────────────
            'parrain' => $this->mapParrainBlock($row),
        ];
    }

    // ── Bloc Parrain ────────────────────────────────────────────────────────

    private function mapParrainBlock(array $row): array
    {
        $nomPrenom = trim((string) ($row['NOM PRENOM'] ?? ''));
        if ($nomPrenom === '') {
            return [];
        }

        $villeRaw = trim((string) ($row['Ville.1'] ?? ''));
        $ville = $villeRaw !== '' ? explode(',', $villeRaw)[0] : null;

        $cpRaw = $row['Code postal.1'] ?? null;
        $codePostal = $cpRaw !== null && $cpRaw !== ''
            ? str_pad((string) (int) $cpRaw, 5, '0', STR_PAD_LEFT)
            : null;

        return array_filter([
            'nom_prenom' => $nomPrenom,
            'telephone' => $this->formatTelephone($row['Tél'] ?? null),
            'email' => $this->normalizeEmail($row['email'] ?? null),
            'adresse' => trim((string) ($row['Adresse postale'] ?? '')) ?: null,
            'code_postal' => $codePostal,
            'ville' => $ville,
            'super_parrain' => $this->parseBool($row['Super parrain'] ?? false),
            'date_creation' => $this->parseDate($row['Date de création du parrain'] ?? null),
        ], fn ($v) => $v !== null && $v !== '');
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