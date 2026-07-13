<?php

namespace App\Filament\NsConseil\Resources\ClientResource\Import;

/**
 * Importer pour l'onglet "CRM LIKE" (51 colonnes, ~7 700 dossiers).
 *
 * Structure de la ref_client :
 *   [AOPIA2|01FC] NOM_TIERS PROGRAMME [N°]
 *   ex: "AOPIA2 CANARD David PHOTOSHOP BAS TOSA 2"
 *
 * Bloc Parrain : colonnes NOM PRENOM / Tél / email / Adresse postale /
 *               Code postal.1 / Ville.1 / Commentaires / Super parrain /
 *               Programme Super Parrain / Date de création du parrain
 *
 * Partenaire : "Partenaire Like" (accord-cadre) + "Partenaire Boutique".
 * Les valeurs source sont conservees dans Client.extra_data et le rattachement
 * partenaire est tente par nomenclature exacte.
 */
class CrmLikeImporter extends BaseClientImporter
{
    public static function getName(): string
    {
        return 'CRM LIKE';
    }

    public static function getRequiredColumns(): array
    {
        return ['Civilité', 'Réf. client', 'Partenaire Like'];
    }

    // ── Entité commerciale ──────────────────────────────────────────────────
    protected string $entiteCode = 'LIKE';

    // ── Mapping principal ───────────────────────────────────────────────────

    protected function mapRow(array $row): array
    {
        $ref = trim((string) ($row['Réf. client'] ?? ''));
        $tiers = trim((string) ($row['Tiers'] ?? ''));
        $partenaireLike = trim((string) ($row['Partenaire Like'] ?? ''));
        $partenaireBoutique = trim((string) ($row['Partenaire Boutique'] ?? ''));
        $operation = trim((string) ($row['Opération'] ?? $row['OpÃ©ration'] ?? ''));

        return [
            // ── Client (personne physique) ───────────────────────────────
            'client' => array_filter([
                // Clé temporaire pour upsertClient() ; sera supprimée avant Client::updateOrCreate
                // On utilise email ou nom car ref_client encode programme+nom (pas clé client unique)
                'civilite' => trim((string) ($row['Civilité'] ?? '')) ?: null,
                'nom_tiers' => $tiers ?: null,
                'telephone' => $this->formatTelephone($row['Téléphone'] ?? null),
                'email' => $this->normalizeEmail($row['Email'] ?? null),
                'ville' => trim((string) ($row['Ville'] ?? '')) ?: null,
                'code_postal' => $this->formatCodePostal($row['Code postal'] ?? null),
                'adresse' => trim((string) ($row['Adresse'] ?? '')) ?: null,
                'entreprise' => trim((string) ($row['Entreprise'] ?? '')) ?: null,
                'date_naissance' => $this->parseDate($row['Date de naissance'] ?? null),
                'ne_plus_contacter' => $this->parseBool($row['Ne plus contacter'] ?? false),
                'avis_google' => $this->parseBool($row['Avis Google'] ?? false),
                '_partenaire_nomenclature' => $partenaireLike ?: null,
                'extra_data' => array_filter([
                    'partenaire_like' => $partenaireLike ?: null,
                    'partenaire_boutique' => $partenaireBoutique ?: null,
                    'operation' => $operation ?: null,
                ], fn ($v) => $v !== null && $v !== ''),
            ], fn ($v) => $v !== null && $v !== ''),

            // ── DossierFormation ─────────────────────────────────────────
            'dossier' => array_filter([
                'ref_client' => $ref ?: null,
                'intitule_programme' => $ref && $tiers
                    ? $this->extractProgrammeLike($ref, $tiers)
                    : null,
                'etat' => $this->mapEtat($row['État'] ?? ''),
                'statut_formation' => $this->mapStatutFormation($row['Statut formation'] ?? ''),
                'montant_ht' => $this->parseFloat($row['Montant HT'] ?? null),
                'montant_cpf' => $this->parseFloat($row['Montant CPF'] ?? null),
                'date_vente' => $this->parseDate($row['Date de vente'] ?? null),
                'no_dossier_edof' => trim((string) ($row['(C) N° dossier EDOF'] ?? '')) ?: null,
                '_consultant_accueil_nom' => trim((string) ($row['Consultant 1er accueil'] ?? '')) ?: null,
                '_consultant_formateur_nom' => trim((string) ($row['Consultant Formateur'] ?? '')) ?: null,
                '_entite_code' => $this->entiteCode,
            ], fn ($v) => $v !== null && $v !== ''),

            // ── HeuresFormation ──────────────────────────────────────────
            'heures' => array_filter([
                'heures_obligatoires' => $this->parseFloat($row['Heures de formation obligatoires'] ?? null),
                'heures_complementaires' => $this->parseFloat($row['Heures de formation complémentaires'] ?? null),
                'heures_elearning' => $this->parseFloat($row["Heures d'E-learning"] ?? null),
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

        // Ville peut être "MASLIVES, France - Loir-et-Cher" => on garde la partie avant la virgule
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

    // ── Helpers spécifiques ─────────────────────────────────────────────────

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