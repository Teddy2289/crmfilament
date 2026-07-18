<?php

namespace App\Filament\NsConseil\Resources\ClientResource\Import;

use App\Models\Client;
use App\Models\Consultant;
use App\Models\DossierFormation;
use App\Models\EntiteCommerciale;
use App\Models\HeuresFormation;
use App\Models\Parrain;
use App\Models\Partenaire;
use App\Models\PlanningFormation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date;

abstract class BaseClientImporter
{
    protected array $errors = [];

    protected int $created = 0;

    protected int $updated = 0;

    protected int $skipped = 0;

    protected string $strategy = 'merge'; // 'overwrite', 'merge', 'skip'

    public const STRATEGY_OVERWRITE = 'overwrite';

    public const STRATEGY_MERGE = 'merge';

    public const STRATEGY_SKIP = 'skip';

    // ── Cache par requête pour éviter N+1 sur firstOrCreate ────────────────
    /** @var array<string, int|null> */
    protected array $consultantCache = [];

    /** @var array<string, int|null> */
    protected array $entiteCache = [];

    /** @var array<string, Partenaire|null> */
    protected array $partenaireCache = [];

    // ── Interface publique ──────────────────────────────────────────────────

    abstract public static function getName(): string;

    abstract public static function getRequiredColumns(): array;

    /**
     * Mappe une ligne brute vers un tableau structuré :
     * [
     *   'client'   => [...],           // champs Client
     *   'dossier'  => [...],           // champs DossierFormation
     *   'heures'   => [...],           // champs HeuresFormation (optionnel)
     *   'planning' => [...],           // champs PlanningFormation (optionnel)
     *   'parrain'  => [...],           // champs Parrain (optionnel)
     * ]
     */
    abstract protected function mapRow(array $row): array;

    public static function matches(array $fileColumns): bool
    {
        $fileColumns = array_map(fn ($c) => mb_strtolower(trim((string) $c)), $fileColumns);
        foreach (static::getRequiredColumns() as $required) {
            if (! in_array(mb_strtolower(trim($required)), $fileColumns)) {
                return false;
            }
        }

        return true;
    }

    // ── Import principal ────────────────────────────────────────────────────

    /**
     * @param  (callable(int $processed): void)|null  $onProgress  Appelé après chaque ligne avec le nombre de lignes traitées dans ce lot.
     */
    public function import(array $rows, string $sourceSheet = '', string $strategy = self::STRATEGY_MERGE, ?callable $onProgress = null): array
    {
        $this->strategy = $strategy;

        foreach ($rows as $index => $row) {
            try {
                $mapped = $this->mapRow($row);

                $clientData = $mapped['client'] ?? [];
                $dossierData = $mapped['dossier'] ?? [];
                $heuresData = $mapped['heures'] ?? [];
                $planningData = $mapped['planning'] ?? [];
                $parrainData = $mapped['parrain'] ?? [];

                if (empty($clientData['nom_tiers'])) {
                    $this->skipped++;

                    continue;
                }

                $clientData['source_sheet'] = $sourceSheet ?: static::getName();

                // ── 1. Client ────────────────────────────────────────────
                $client = $this->upsertClient($clientData, $parrainData);

                // ── 2. DossierFormation ──────────────────────────────────
                // Vérifier que le dossier a bien un ref_client
                if (empty($dossierData['ref_client'])) {
                    $this->errors[] = 'Ligne '.($index + 2).' : ref_client manquant pour le dossier';

                    continue;
                }

                $dossier = $this->upsertDossier($client, $dossierData);

                // ⚠️ Vérification CRITIQUE : Le dossier doit avoir un ID
                if (! $dossier || ! $dossier->id) {
                    $this->errors[] = 'Ligne '.($index + 2)." : impossible de récupérer l'ID du dossier";

                    continue;
                }

                // ── 3. HeuresFormation ───────────────────────────────
                if (! empty($heuresData)) {
                    $this->upsertHeures($dossier->id, $heuresData);
                }

                // ── 4. PlanningFormation ─────────────────────────────
                if (! empty($planningData)) {
                    $this->upsertPlanning($dossier->id, $planningData);
                }
            } catch (\Throwable $e) {
                $this->errors[] = 'Ligne '.($index + 2).' : '.$e->getMessage();
            } finally {
                if ($onProgress) {
                    $onProgress($index + 1);
                }
            }
        }

        return $this->getResult();
    }

    // ── Upserts ─────────────────────────────────────────────────────────────

    protected function upsertClient(array $clientData, array $parrainData): Client
    {
        // Résoudre le parrain si présent
        if (! empty($parrainData['nom_prenom'])) {
            $parrain = $this->resolveParrain($parrainData);
            if ($parrain) {
                $clientData['parrain_id'] = $parrain->id;
            }
        }

        // Clé de match : email > téléphone > ref_client > création brute
        $partenaireNomenclature = $this->takeClientDataValue($clientData, '_partenaire_nomenclature');
        if ($partenaireNomenclature) {
            $partenaire = $this->resolvePartenaireByNomenclature($partenaireNomenclature);

            if ($partenaire) {
                $clientData['partenaire_id'] = $partenaire->id;
            }

            $clientData['extra_data'] = array_replace_recursive($clientData['extra_data'] ?? [], [
                'partenaire_import' => [
                    'nomenclature' => $partenaireNomenclature,
                    'statut' => $partenaire ? 'rattache' : 'partenaire_non_rattache',
                ],
            ]);
        }

        $existingClient = null;

        // 1. Chercher par email
        if (! empty($clientData['email'])) {
            $existingClient = Client::where('email', $clientData['email'])->first();
        }

        // 2. Chercher par téléphone si pas trouvé par email
        if (! $existingClient && ! empty($clientData['telephone'])) {
            $existingClient = Client::where('telephone', $clientData['telephone'])->first();
        }

        // 3. Chercher par ref_client si pas trouvé par email/téléphone
        if (! $existingClient && ! empty($clientData['ref_client_client'])) {
            $existingClient = Client::where('ref_client', $clientData['ref_client_client'])->first();
        }

        // Nettoyer la clé temporaire
        unset($clientData['ref_client_client']);

        if ($existingClient) {
            // Client existe déjà - appliquer la stratégie
            if ($this->strategy === self::STRATEGY_SKIP) {
                $this->skipped++;
                return $existingClient;
            }

            if ($this->strategy === self::STRATEGY_MERGE) {
                // Fusion intelligente : ne pas écraser les données importantes
                $clientData = $this->mergeClientData($existingClient, $clientData);

                // Accumuler les ref_clients
                if (! empty($clientData['ref_client'])) {
                    $existingRefs = $existingClient->ref_clients ?? [];
                    if (! in_array($clientData['ref_client'], $existingRefs)) {
                        $existingRefs[] = $clientData['ref_client'];
                        $clientData['ref_clients'] = $existingRefs;
                    }
                    unset($clientData['ref_client']);
                }

                $existingClient->update($clientData);
                $this->updated++;
                return $existingClient;
            }

            // STRATEGY_OVERWRITE : écraser tout
            $existingClient->update($clientData);
            $this->updated++;
            return $existingClient;
        }

        // Nouveau client
        if (! empty($clientData['ref_client'])) {
            $clientData['ref_clients'] = [$clientData['ref_client']];
            unset($clientData['ref_client']);
        }
        $client = Client::create($clientData);
        $this->created++;

        return $client;
    }

    protected function upsertDossier(Client $client, array $dossierData): DossierFormation
    {
        $dossierData['personne_id'] = $client->id;

        // Résoudre les consultants
        if (! empty($dossierData['_consultant_accueil_nom'])) {
            $dossierData['consultant_accueil_id'] = $this->resolveConsultant(
                $dossierData['_consultant_accueil_nom']
            );
        }
        if (! empty($dossierData['_consultant_formateur_nom'])) {
            $dossierData['consultant_formateur_id'] = $this->resolveConsultant(
                $dossierData['_consultant_formateur_nom']
            );
        }
        // Résoudre l'entité commerciale
        if (! empty($dossierData['_entite_code'])) {
            $dossierData['entite_id'] = $this->resolveEntite($dossierData['_entite_code']);
        }

        // Supprimer les clés temporaires
        unset(
            $dossierData['_consultant_accueil_nom'],
            $dossierData['_consultant_formateur_nom'],
            $dossierData['_entite_code']
        );

        // 🔴 Vérifier que 'ref_client' existe avant updateOrCreate
        if (empty($dossierData['ref_client'])) {
            throw new \Exception('Impossible de créer/mettre à jour le dossier : ref_client manquant');
        }

        // Appliquer la stratégie pour les dossiers existants
        $existingDossier = DossierFormation::where('ref_client', $dossierData['ref_client'])->first();

        if ($existingDossier && $this->strategy === self::STRATEGY_MERGE) {
            // Fusion intelligente : ne pas écraser les statuts critiques
            $dossierData = $this->mergeDossierData($existingDossier, $dossierData);
        }

        if ($existingDossier) {
            $existingDossier->update($dossierData);
            $dossier = $existingDossier;
        } else {
            $dossier = DossierFormation::create($dossierData);
        }

        // 🔴 Vérifier que le dossier a bien un ID
        if (! $dossier || ! $dossier->id) {
            throw new \Exception("Le dossier a été créé mais n'a pas d'ID. ref_client: ".$dossierData['ref_client']);
        }

        return $dossier;
    }

    protected function upsertHeures(int $dossierId, array $heuresData): void
    {
        // 🔴 Vérification CRITIQUE : Ne pas exécuter si l'ID est invalide
        if ($dossierId <= 0 || $dossierId === null) {
            Log::error('upsertHeures : dossier_id invalide', [
                'dossier_id' => $dossierId,
                'heuresData' => $heuresData,
            ]);

            return;
        }

        // Vérifier que le dossier existe réellement
        $dossierExists = DossierFormation::query()->where('id', $dossierId)->exists();
        if (! $dossierExists) {
            Log::error('upsertHeures : dossier inexistant', [
                'dossier_id' => $dossierId,
            ]);

            return;
        }

        HeuresFormation::updateOrCreate(
            ['dossier_id' => $dossierId],
            $heuresData
        );
    }

    protected function upsertPlanning(int $dossierId, array $planningData): void
    {
        PlanningFormation::updateOrCreate(
            ['dossier_id' => $dossierId],
            $planningData
        );
    }

    // ── Résolution entités tierces ──────────────────────────────────────────

    /**
     * Résout ou crée un Consultant depuis un nom brut ("BENOIT", "LE CALVE Sonia").
     * Split simple : dernier mot = prénom si le nom contient plusieurs mots,
     * sinon tout va dans `nom`.
     */
    protected function takeClientDataValue(array &$clientData, string $key): ?string
    {
        $value = trim((string) ($clientData[$key] ?? ''));
        unset($clientData[$key]);

        return $value !== '' ? $value : null;
    }

    protected function resolvePartenaireByNomenclature(string $nomenclature): ?Partenaire
    {
        $nomenclature = trim($nomenclature);
        if ($nomenclature === '') {
            return null;
        }

        if (array_key_exists($nomenclature, $this->partenaireCache)) {
            return $this->partenaireCache[$nomenclature];
        }

        return $this->partenaireCache[$nomenclature] = Partenaire::resolveByNomenclature($nomenclature);
    }

    protected function resolveConsultant(string $nomBrut): ?int
    {
        $nomBrut = trim($nomBrut);
        if ($nomBrut === '') {
            return null;
        }

        if (isset($this->consultantCache[$nomBrut])) {
            return $this->consultantCache[$nomBrut];
        }

        $parts = preg_split('/\s+/', $nomBrut, 2);
        // Format "NOM Prenom" ou "NOM" seul
        // prenom vaut '' si absent pour satisfaire la contrainte NOT NULL
        $nom = $parts[0] ?? $nomBrut;
        $prenom = $parts[1] ?? '';

        $consultant = Consultant::firstOrCreate(
            ['nom' => $nom, 'prenom' => $prenom],
            []
        );

        $this->consultantCache[$nomBrut] = $consultant->id;

        return $consultant->id;
    }

    /**
     * Résout ou crée une EntiteCommerciale depuis son code (LIKE, AOPIA-ABO, 01FC).
     */
    protected function resolveEntite(string $code): ?int
    {
        if (isset($this->entiteCache[$code])) {
            return $this->entiteCache[$code];
        }

        $entite = EntiteCommerciale::firstOrCreate(
            ['code' => $code],
            ['nom' => $code]
        );

        $this->entiteCache[$code] = $entite->id;

        return $entite->id;
    }

    /**
     * Résout ou crée un Parrain depuis le bloc parrain de la ligne.
     */
    protected function resolveParrain(array $parrainData): ?Parrain
    {
        $nomPrenom = trim($parrainData['nom_prenom'] ?? '');
        if ($nomPrenom === '') {
            return null;
        }

        $fill = array_filter([
            'telephone' => $parrainData['telephone'] ?? null,
            'email' => $parrainData['email'] ?? null,
            'adresse' => $parrainData['adresse'] ?? null,
            'code_postal' => $parrainData['code_postal'] ?? null,
            'ville' => $parrainData['ville'] ?? null,
            'super_parrain' => $parrainData['super_parrain'] ?? false,
            'date_creation' => $parrainData['date_creation'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        return Parrain::firstOrCreate(
            ['nom_prenom' => $nomPrenom],
            $fill
        );
    }

    // ── Extraction du programme ─────────────────────────────────────────────

    /**
     * Format LIKE / mixte :
     * [AOPIA2|01FC] NOM_TIERS PROGRAMME [N°]
     * => retire préfixe, nom du tiers, numéro final
     */
    protected function extractProgrammeLike(string $ref, string $tiers): string
    {
        $ref = trim($ref);
        $tiers = trim(preg_replace('/\s*\(.*?\)/', '', $tiers));

        foreach (['AOPIA2 ', '01FC ', 'LIKE '] as $prefix) {
            if (stripos($ref, $prefix) === 0) {
                $ref = substr($ref, strlen($prefix));
                break;
            }
        }

        if (stripos($ref, $tiers) === 0) {
            $ref = substr($ref, strlen($tiers));
        }

        $ref = rtrim(preg_replace('/\s+\d+$/', '', trim($ref)));

        return $ref !== '' ? $ref : $this->fallbackProgramme($ref);
    }

    /**
     * Format AOPIA-ABO :
     * PROGRAMME NOM_TIERS AOPIA2
     * => retire suffixe AOPIA2, puis nom du tiers en fin
     */
    protected function extractProgrammeAopia(string $ref, string $tiers): string
    {
        $ref = trim($ref);
        $tiers = trim(preg_replace('/\s*\(.*?\)/', '', $tiers));

        foreach ([' AOPIA2', ' AOPIA', ' ABO'] as $suffix) {
            if (stripos(substr($ref, -strlen($suffix)), $suffix) === 0) {
                $ref = substr($ref, 0, strlen($ref) - strlen($suffix));
                break;
            }
        }
        $ref = trim($ref);

        // Retirer le nom du tiers en fin (peut y avoir des variantes avec "/")
        $tiersNom = explode('/', $tiers)[0]; // "HADAK/MARTIN" => "HADAK"
        $parts = preg_split('/\s+/', trim($tiersNom));

        for ($i = count($parts); $i > 0; $i--) {
            $candidate = implode(' ', array_slice($parts, 0, $i));
            $len = strlen($candidate);
            if (stripos(substr($ref, -$len), $candidate) === 0) {
                $ref = trim(substr($ref, 0, strlen($ref) - $len));
                break;
            }
        }

        return $ref !== '' ? $ref : 'Programme inconnu';
    }

    /**
     * Format 01FC :
     * PROGRAMME [NOM_TIERS]?
     * => le programme est en tête ; on retire le nom s'il apparaît en fin
     */
    protected function extractProgramme01Fc(string $ref, string $tiers): string
    {
        $ref = trim($ref);
        $tiers = trim(preg_replace('/\s*\(.*?\)/', '', $tiers));

        if ($tiers !== '') {
            $parts = preg_split('/\s+/', $tiers);
            for ($i = count($parts); $i > 0; $i--) {
                $candidate = implode(' ', array_slice($parts, 0, $i));
                $len = strlen($candidate);
                if (stripos(substr($ref, -$len), $candidate) === 0) {
                    $ref = trim(substr($ref, 0, strlen($ref) - $len));
                    break;
                }
            }
        }

        return $ref !== '' ? $ref : 'Programme inconnu';
    }

    protected function fallbackProgramme(string $ref): string
    {
        return $ref !== '' ? $ref : 'Programme inconnu';
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Normalise un numéro de téléphone français au format "XX XX XX XX XX".
     *
     * Cas gérés :
     * - Zéro initial perdu par Excel quand la cellule est un nombre et non
     *   du texte : "0782216546" (10 chiffres, valide) devient "782216546"
     *   (9 chiffres) à la lecture. On le restitue si le résultat commence
     *   par un indicatif français plausible (1-5 fixe, 6-7 mobile, 9 VoIP).
     * - Indicatif international +33 / 0033 ramené au format national.
     * - Espaces, points, tirets, parenthèses de saisie libre nettoyés.
     *
     * Si le résultat ne fait toujours pas 10 chiffres après ces
     * corrections, on renvoie les chiffres bruts (plutôt que de perdre
     * l'info) afin qu'un numéro atypique (étranger, incomplet, invalide)
     * reste visible et corrigeable manuellement au lieu de disparaître
     * silencieusement.
     */
    protected function formatTelephone(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', (string) $value);

        if ($digits === '') {
            return null;
        }

        // +33 6 12 34 56 78 / 0033612345678 → 0612345678
        if (strlen($digits) === 13 && str_starts_with($digits, '0033')) {
            $digits = '0'.substr($digits, 4);
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '33')) {
            $digits = '0'.substr($digits, 2);
        }

        // Zéro initial perdu par Excel (cellule numérique) : 9 chiffres
        // commençant par un indicatif FR plausible → on rajoute le 0.
        if (strlen($digits) === 9 && preg_match('/^[1-79]/', $digits)) {
            $digits = '0'.$digits;
        }

        if (strlen($digits) === 10) {
            return implode(' ', str_split($digits, 2));
        }

        return $digits;
    }

    protected function parseDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        if (is_numeric($value)) {
            try {
                return Date::excelToDateTimeObject((float) $value)
                    ->format('Y-m-d');
            } catch (\Throwable) {
            }
        }
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function parseFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $clean = str_replace([' ', '€', ','], ['', '', '.'], (string) $value);

        return is_numeric($clean) ? (float) $clean : null;
    }

    protected function parseBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'oui', 'yes', 'true', 'o', 'x']);
    }

    protected function mapEtat(mixed $value): ?string
    {
        $map = [
            'prospect' => 'prospect',
            'en cours' => 'en_cours',
            'à venir' => 'prospect',
            'a venir' => 'prospect',
            'terminé' => 'termine',
            'termine' => 'termine',
            'certifié' => 'certifie',
            'certifie' => 'certifie',
            'abandonné' => 'abandonne',
            'abandonne' => 'abandonne',
            'signée' => 'en_cours',
            'signee' => 'en_cours',
            'facturée' => 'termine',
            'facturee' => 'termine',
        ];

        return $map[mb_strtolower(trim((string) $value))] ?? null;
    }

    protected function mapStatutFormation(mixed $value): ?string
    {
        $map = [
            'à venir' => 'a_venir',
            'a venir' => 'a_venir',
            'en cours' => 'en_cours',
            'terminé' => 'termine',
            'termine' => 'termine',
            'interrompu' => 'interrompu',
            'abandon' => 'abandon',
        ];

        return $map[mb_strtolower(trim((string) $value))] ?? null;
    }

    // ── Résultat ────────────────────────────────────────────────────────────

    protected function getResult(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
    }

    // ── Fusion intelligente des données Client ─────────────────────────────
    protected function mergeClientData(Client $existing, array $newData): array
    {
        // Champs à préserver (ne pas écraser s'ils existent)
        $preserveFields = [
            'etat',
            'parrain_id',
            'source_sheet',
            'notes_commerciales',
            'ne_plus_contacter',
        ];

        // Si l'état n'est pas "prospect", le préserver absolument
        if ($existing->etat && $existing->etat !== 'prospect') {
            unset($newData['etat']);
        }

        // Fusionner : ne mettre à jour que si vide dans l'existant
        foreach ($preserveFields as $field) {
            if (isset($existing->$field) && $existing->$field !== null && $existing->$field !== '') {
                unset($newData[$field]);
            }
        }

        if (isset($newData['extra_data'])) {
            $newData['extra_data'] = array_replace_recursive($existing->extra_data ?? [], $newData['extra_data']);
        }

        return $newData;
    }

    // ── Fusion intelligente des données DossierFormation ─────────────────────
    protected function mergeDossierData(DossierFormation $existing, array $newData): array
    {
        // Champs à préserver (ne pas écraser s'ils existent)
        $preserveFields = [
            'statut_formation',
            'etat',
            'consultant_accueil_id',
            'consultant_formateur_id',
            'entite_id',
        ];

        // Si le statut_formation n'est pas "a_venir", le préserver absolument
        if ($existing->statut_formation && $existing->statut_formation !== 'a_venir') {
            unset($newData['statut_formation']);
        }

        // Si l'état n'est pas "brouillon", le préserver absolument
        if ($existing->etat && $existing->etat !== 'brouillon') {
            unset($newData['etat']);
        }

        // Fusionner : ne mettre à jour que si vide dans l'existant
        foreach ($preserveFields as $field) {
            if (isset($existing->$field) && $existing->$field !== null && $existing->$field !== '') {
                unset($newData[$field]);
            }
        }

        return $newData;
    }
}
