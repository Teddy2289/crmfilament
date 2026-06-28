<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\Import;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Models\ActivitePermanence;
use App\Models\ActiviteVente;          // fichier : ActivitePartenaire.php
use App\Models\AdresseCse;
use App\Models\AutresInterlocuteurs;
use App\Models\Consultant;
use App\Models\ContactPartenaire;
use App\Models\EntiteCommerciale;
use App\Models\HistoriqueConseiller;
use App\Models\Partenaire;
use App\Models\Tarification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Importe la feuille MAJ (format unique de référence).
 *
 * Index des colonnes (row 0 = en-tête ignoré, données à partir de row 1) :
 *   0  Entité            14  TYPE                28  Tél portable
 *   1  ENTREPRISE        15  Origine             29  Tél fixe
 *   2  NOM RETENU        16  PARRAIN/MARRAINE    30  Préf. contact
 *   3  Nb salariés       17  Conseiller          31  Autres interlocuteurs
 *   4  Statut            18  Ancien conseiller   32  Parrainage entreprise ?
 *   5  Année             19  Mandataire/VDI      33  Possibilité permanence ?
 *   6  Date signature    20  Dept conseiller     34  Réplicable
 *   7  Nb ventes         21  Adresse CSE         35  Prix du PC
 *   8  Dernière vente    22  Code postal CSE     36  Aopia (part)
 *   9  Ventes 2025       23  Commune CSE         37  Tarifs
 *  10  Ventes 2026       24  Nom du contact      38  Part CSE
 *  11  Dernière perm.    25  Prénom du contact   39  Part salarié
 *  12  Nbre perm. 2025   26  Fonction du contact 40  Tarifs affichage comm
 *  13  Nbre perm. 2026   27  Mail                41  Adresse facturation
 *                                                42  COMMENTAIRES
 */
class PartenaireImporter
{
    private const COL = [
        'entite' => 0,
        'entreprise' => 1,
        'nom_retenu' => 2,
        'nb_salaries' => 3,
        'statut' => 4,
        'annee_signature' => 5,
        'date_signature' => 6,
        'nb_ventes' => 7,
        'derniere_vente' => 8,
        'ventes_2025' => 9,
        'ventes_2026' => 10,
        'derniere_permanence' => 11,
        'nbre_perm_2025' => 12,
        'nbre_perm_2026' => 13,
        'type' => 14,
        'origine' => 15,
        'parrain' => 16,
        'conseiller' => 17,
        'ancien_conseiller' => 18,
        'statut_vdi' => 19,
        'dept_conseiller' => 20,
        'adresse_cse' => 21,
        'cp_cse' => 22,
        'commune_cse' => 23,
        'contact_nom' => 24,
        'contact_prenom' => 25,
        'contact_fonction' => 26,
        'contact_mail' => 27,
        'contact_tel_portable' => 28,
        'contact_tel_fixe' => 29,
        'contact_preference' => 30,
        'autres_interlocuteurs' => 31,
        'parrainage_entreprise' => 32,
        'possibilite_perm' => 33,
        'replicable' => 34,
        'prix_pc' => 35,
        'part_aopia' => 36,
        'tarifs' => 37,
        'part_cse' => 38,
        'part_salarie' => 39,
        'tarifs_affichage' => 40,
        'adresse_facturation' => 41,
        'commentaires' => 42,
    ];

    private const MIN_COLS = 17;

    protected array $errors = [];

    protected int $created = 0;

    protected int $updated = 0;

    protected int $skipped = 0;

    protected string $strategy = 'merge'; // 'overwrite', 'merge', 'skip'

    public const STRATEGY_OVERWRITE = 'overwrite';

    public const STRATEGY_MERGE = 'merge';

    public const STRATEGY_SKIP = 'skip';

    private array $entiteCache = [];

    private array $conseillerCache = [];

    // ─── Point d'entrée ──────────────────────────────────────────────

    public function import(array $rows, array $defaults = [], string $strategy = self::STRATEGY_MERGE): array
    {
        $this->strategy = $strategy;

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            if ($this->isEmptyRow($row)) {
                continue;
            }

            if (count($row) < self::MIN_COLS) {
                $this->skipped++;

                continue;
            }

            try {
                $this->processRow($row, $i + 1, $defaults);
            } catch (\Throwable $e) {
                $this->errors[] = 'Ligne '.($i + 1).' : '.$e->getMessage();
            }
        }

        return $this->getResult();
    }

    // ─── Traitement d'une ligne ───────────────────────────────────────

    private function processRow(array $row, int $lineNumber, array $defaults): void
    {
        $get = fn (string $col): mixed => $this->cell($row, $col);

        $nom = $this->str($get('entreprise'));
        if (empty($nom)) {
            $this->skipped++;

            return;
        }

        // ── FK ────────────────────────────────────────────────────────
        $entiteId = $this->resolveEntiteId($this->str($get('entite')))
            ?? ($defaults['entite_id'] ?? null);
        $conseillerId = $this->resolveConseillerId($this->str($get('conseiller')))
            ?? ($defaults['conseiller_id'] ?? null);

        // ── Localisation ──────────────────────────────────────────────
        [$cp, $commune] = $this->cleanCpCommune($get('cp_cse'), $get('commune_cse'));
        $departement = $this->cleanDepartement($get('dept_conseiller'))
            ?? ($cp ? substr($cp, 0, 2) : null);

        // ── Données Partenaire ────────────────────────────────────────
        $data = array_filter([
            'nom' => $nom,
            'entreprise' => $nom,
            'nom_retenu' => $this->str($get('nom_retenu')),
            'type' => $this->resolveType($get('type'))
                ?? ($defaults['type'] ?? OrganizationType::CSE->value),
            'statut' => $this->resolveStatut($get('statut'))
                ?? ($defaults['statut'] ?? OrganizationStatus::AProspecter->value),
            'annee_signature' => $this->cleanInt($get('annee_signature')),
            'date_signature' => $this->parseDate($get('date_signature')),
            'nb_salaries' => $this->cleanNbSalaries($get('nb_salaries')),
            'adresse' => $this->str($get('adresse_cse')),
            'code_postal' => $cp,
            'ville' => $commune,
            'departement' => $departement,
            'origine_contact' => $this->str($get('origine')),
            'parrain_marraine_texte' => mb_substr((string) ($this->str($get('parrain')) ?? ''), 0, 255) ?: null,
            'parrainage_entreprise' => $this->parseBool($get('parrainage_entreprise')),
            'possibilite_permanence' => $this->str($get('possibilite_perm')),
            'replicable' => $this->str($get('replicable')), // colonne TEXT en BDD
            'commentaires' => $this->str($get('commentaires')),
            'entite_id' => $entiteId,
            'conseiller_id' => $conseillerId,
            'nomenclature_interne' => $defaults['nomenclature_interne'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        // ── Upsert Partenaire avec stratégie ─────────────────────────────
        $existingPartenaire = Partenaire::where('nom', $nom)
            ->where('ville', $commune ?: null)
            ->first();

        if ($existingPartenaire) {
            // Partenaire existe déjà - appliquer la stratégie
            if ($this->strategy === self::STRATEGY_SKIP) {
                $this->skipped++;
                return;
            }

            if ($this->strategy === self::STRATEGY_MERGE) {
                // Fusion intelligente : ne pas écraser les données importantes
                $data = $this->mergePartenaireData($existingPartenaire, $data);
                $existingPartenaire->update($data);
                $this->updated++;
            } else {
                // STRATEGY_OVERWRITE : écraser tout
                $existingPartenaire->update($data);
                $this->updated++;
            }
            $partenaire = $existingPartenaire;
        } else {
            // Nouveau partenaire
            $partenaire = Partenaire::create($data);
            $this->created++;
        }

        // ── Relations ─────────────────────────────────────────────────

        if ($cp || $commune) {
            AdresseCse::updateOrCreate(
                ['partenaire_id' => $partenaire->id],
                array_filter([
                    'adresse' => $this->str($get('adresse_cse')),
                    'code_postal' => $cp,
                    'commune' => $commune,
                    'partenaire_id' => $partenaire->id,
                ], fn ($v) => $v !== null)
            );
        }

        $this->upsertContacts($partenaire, $row);

        $autresText = $this->str($get('autres_interlocuteurs'));
        if ($autresText) {
            AutresInterlocuteurs::updateOrCreate(
                ['partenaire_id' => $partenaire->id],
                ['texte_libre' => $autresText, 'partenaire_id' => $partenaire->id]
            );
        }

        $ancienNom = $this->str($get('ancien_conseiller'));
        if ($ancienNom) {
            $ancienId = $this->resolveConseillerId($ancienNom);
            if ($ancienId && $ancienId !== $conseillerId) {
                HistoriqueConseiller::firstOrCreate(
                    ['partenaire_id' => $partenaire->id, 'ancien_conseiller_id' => $ancienId],
                    [
                        'ancien_conseiller_id' => $ancienId,
                        'nouveau_conseiller_id' => $conseillerId,
                        'date_changement' => now()->toDateString(),
                        'partenaire_id' => $partenaire->id,
                    ]
                );
            }
        }

        $nbVentes = $this->cleanInt($get('nb_ventes'));
        $derniereVente = $this->parseDate($get('derniere_vente'));
        $ventes2025 = $this->cleanInt($get('ventes_2025'));
        $ventes2026 = $this->cleanInt($get('ventes_2026'));
        if ($nbVentes !== null || $derniereVente || $ventes2025 !== null || $ventes2026 !== null) {
            ActiviteVente::updateOrCreate(
                ['partenaire_id' => $partenaire->id],
                array_filter([
                    'partenaire_id' => $partenaire->id,
                    'consultant_id' => $conseillerId,
                    'nombre_ventes_total' => $nbVentes,
                    'derniere_vente' => $derniereVente,
                    'ventes_2025' => $ventes2025,
                    'ventes_2026' => $ventes2026,
                ], fn ($v) => $v !== null)
            );
        }

        $dernierePerm = $this->parseDate($get('derniere_permanence'));
        $nbre2025 = $this->cleanInt($get('nbre_perm_2025'));
        $nbre2026 = $this->cleanInt($get('nbre_perm_2026'));
        if ($dernierePerm || $nbre2025 !== null || $nbre2026 !== null) {
            ActivitePermanence::updateOrCreate(
                ['partenaire_id' => $partenaire->id],
                array_filter([
                    'partenaire_id' => $partenaire->id,
                    'consultant_id' => $conseillerId,
                    'derniere_permanence' => $dernierePerm,
                    'nbre_2025' => $nbre2025,
                    'nbre_2026' => $nbre2026,
                ], fn ($v) => $v !== null)
            );
        }

        $prixPc = $this->cleanDecimal($get('prix_pc'));
        $partAopia = $this->cleanDecimal($get('part_aopia'));
        $tarifs = $this->cleanDecimal($get('tarifs'));
        $partCse = $this->cleanDecimal($get('part_cse'));
        $partSal = $this->cleanDecimal($get('part_salarie'));
        if ($prixPc || $partAopia || $tarifs || $partCse || $partSal) {
            Tarification::updateOrCreate(
                ['partenaire_id' => $partenaire->id],
                array_filter([
                    'partenaire_id' => $partenaire->id,
                    'prix_pc' => $prixPc,
                    'part_aopia' => $partAopia,
                    'tarifs' => $tarifs,
                    'part_cse' => $partCse,
                    'part_salarie' => $partSal,
                    'tarifs_affichage_comm' => $this->cleanDecimal($get('tarifs_affichage')),
                    'adresse_facturation' => $this->str($get('adresse_facturation')),
                ], fn ($v) => $v !== null && $v !== '')
            );
        }
    }

    // ─── Contacts multi-lignes ────────────────────────────────────────

    private function upsertContacts(Partenaire $partenaire, array $row): void
    {
        $split = fn (mixed $v): array => array_values(array_filter(
            array_map('trim', explode("\n", (string) ($v ?? ''))),
            fn ($s) => $s !== '' && $s !== '/'
        ));

        $noms = $split($this->cell($row, 'contact_nom'));
        $prenoms = $split($this->cell($row, 'contact_prenom'));
        $fonctions = $split($this->cell($row, 'contact_fonction'));
        $mails = $split($this->cell($row, 'contact_mail'));
        $portables = $split($this->cell($row, 'contact_tel_portable'));
        $fixes = $split($this->cell($row, 'contact_tel_fixe'));
        $pref = $this->str($this->cell($row, 'contact_preference'));

        $count = max(count($noms), count($prenoms), count($mails));
        if ($count === 0) {
            return;
        }

        for ($i = 0; $i < $count; $i++) {
            $nom = $this->str($noms[$i] ?? null);
            $prenom = $this->str($prenoms[$i] ?? null);
            $mail = $this->str($mails[$i] ?? null);
            $portable = $this->str($portables[$i] ?? null);
            $fixe = $this->str($fixes[$i] ?? null);
            $fonction = $this->str($fonctions[$i] ?? null);

            // Décalage de saisie : nom vide → toute la ligne décalée d'une colonne
            if (! $nom && $prenom) {
                $nom = $prenom;
                $prenom = $this->str($fonctions[$i] ?? null);
                $fonction = $this->str($mails[$i] ?? null);
                $mail = $this->str($portables[$i] ?? null);
                $portable = $this->str($fixes[$i] ?? null);
                $fixe = null;
            }

            if (! $nom) {
                continue;
            }

            // Valider que mail ressemble à un email (pas une fonction ou tel)
            if ($mail && ! str_contains($mail, '@')) {
                $mail = null;
            }

            $role = $this->detectRole($fonction);

            ContactPartenaire::updateOrCreate(
                ['partenaire_id' => $partenaire->id, 'nom' => $nom, 'prenom' => $prenom],
                array_filter([
                    'partenaire_id' => $partenaire->id,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'fonction' => $fonction ? mb_substr($fonction, 0, 100) : null,
                    'role' => $role,
                    'email' => $mail,
                    'telephone_mobile' => $this->cleanTelephone($portable),
                    'telephone_direct' => $this->cleanTelephone($fixe),
                    'preference_contact' => $pref,
                    'est_principal' => ($i === 0),
                ], fn ($v) => $v !== null && $v !== '')
            );
        }
    }

    private function detectRole(?string $fonction): string
    {
        if (! $fonction) {
            return 'AUTRE';
        }
        $l = mb_strtolower($fonction);
        if (str_contains($l, 'secr')) {
            return 'SECRETAIRE';
        }
        if (str_contains($l, 'trésor') || str_contains($l, 'tresor')) {
            return 'TRESORIER';
        }
        if (str_contains($l, 'syndicat') || str_contains($l, 'ds ') || str_contains($l, 'délégué')) {
            return 'SYNDICAT_DS';
        }

        return 'AUTRE';
    }

    // ─── Résolution FK ────────────────────────────────────────────────

    private function resolveEntiteId(?string $nom): ?int
    {
        if (! $nom) {
            return null;
        }

        $key = mb_strtoupper(trim($nom));

        if (! array_key_exists($key, $this->entiteCache)) {
            // Chercher par nom exact
            $entite = EntiteCommerciale::where('nom', $key)->first();

            // Si pas trouvé, chercher par LIKE
            if (! $entite) {
                $entite = EntiteCommerciale::where('nom', 'LIKE', "%{$key}%")->first();
            }

            // Si pas trouvé, chercher par code
            if (! $entite) {
                $entite = EntiteCommerciale::where('code', $key)->first();
            }

            // ✅ SOLUTION 2 : Créer automatiquement l'entité si elle n'existe pas
            if (! $entite) {
                $entite = EntiteCommerciale::create([
                    'code' => $key,
                    'nom' => $key,
                ]);

                Log::info("Entité créée automatiquement: {$key} avec ID: ".$entite->id);
            }

            $this->entiteCache[$key] = $entite->id;
        }

        return $this->entiteCache[$key];
    }

    private function resolveConseillerId(?string $name): ?int
    {
        if (! $name) {
            return null;
        }
        $name = trim($name);
        if (array_key_exists($name, $this->conseillerCache)) {
            return $this->conseillerCache[$name];
        }
        $parts = preg_split('/\s+/', $name);
        $query = Consultant::query();
        if (count($parts) >= 2) {
            $query->where(function ($q) use ($parts) {
                $q->where(function ($q2) use ($parts) {
                    $q2->where('prenom', 'like', "%{$parts[0]}%")
                        ->where('nom', 'like', "%{$parts[1]}%");
                })->orWhere(function ($q2) use ($parts) {
                    $q2->where('nom', 'like', "%{$parts[0]}%")
                        ->where('prenom', 'like', "%{$parts[1]}%");
                });
            });
        } else {
            $query->where(function ($q) use ($name) {
                $q->where('nom', 'like', "%{$name}%")->orWhere('prenom', 'like', "%{$name}%");
            });
        }
        $this->conseillerCache[$name] = $query->value('id');

        return $this->conseillerCache[$name];
    }

    // ─── Nettoyage spécifique aux colonnes problématiques ─────────────

    /**
     * CP et commune peuvent être :
     *  - inversés (cp=ville, commune=code)
     *  - CP = float Excel (65000.0)
     *  - CP = multi-valeurs \n (plusieurs sites) → premier seulement
     * Retourne [cp_5_chiffres|null, commune|null]
     */
    private function cleanCpCommune(mixed $cpRaw, mixed $communeRaw): array
    {
        // Séparer sur \n si multi-valeurs → prendre la première paire
        $cpList = array_filter(array_map('trim', explode("\n", (string) ($cpRaw ?? ''))), fn ($s) => $s !== '');
        $communeList = array_filter(array_map('trim', explode("\n", (string) ($communeRaw ?? ''))), fn ($s) => $s !== '');

        $cp = array_values($cpList)[0] ?? null;
        $commune = array_values($communeList)[0] ?? null;

        // Float Excel : 65000.0 → "65000"
        if (is_float($cpRaw)) {
            $cp = (string) (int) $cpRaw;
        }
        if (is_float($communeRaw)) {
            $commune = (string) (int) $communeRaw;
        }

        // Détection inversion : cp ressemble à une ville (aucun chiffre) et commune à un code
        $cpDigits = preg_replace('/\D/', '', (string) ($cp ?? ''));
        $communeDigits = preg_replace('/\D/', '', (string) ($commune ?? ''));

        if (
            $cp && ! $cpDigits             // cp = texte sans chiffre → c'est une ville
            && $commune && ctype_digit($communeDigits) && strlen($communeDigits) >= 4
        ) {
            [$cp, $commune] = [$commune, $cp]; // swap
            $cpDigits = preg_replace('/\D/', '', $cp);
        }

        // Normalisation finale du CP
        if ($cpDigits !== '') {
            $cp = str_pad(substr($cpDigits, 0, 5), 5, '0', STR_PAD_LEFT);
        } else {
            $cp = null;
        }

        // Commune : nettoyer et tronquer à 100 chars
        $commune = $commune ? mb_substr(trim((string) $commune), 0, 100) : null;
        if ($commune === '' || $commune === '/') {
            $commune = null;
        }

        return [$cp, $commune];
    }

    /**
     * Département : float (65.0), code postal entier (13130), texte "17 - 79".
     * → Retourne toujours 2 chiffres max, VARCHAR(3) en BDD.
     */
    private function cleanDepartement(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Float Excel : 65.0 → "65"
        if (is_float($value)) {
            return str_pad((string) (int) $value, 2, '0', STR_PAD_LEFT);
        }

        $s = trim((string) $value);

        // Multi-valeurs "17 - 79" → premier
        if (preg_match('/^(\d{2,3})/', $s, $m)) {
            $num = (int) $m[1];
            // Code postal entier (13130) → extraire les 2 premiers chiffres
            if ($num > 999) {
                $num = (int) substr((string) $num, 0, 2);
            }

            return str_pad((string) $num, 2, '0', STR_PAD_LEFT);
        }

        return null;
    }

    /**
     * Nombre de salariés : entier ou texte "50 à 99", "plus de 1000", "300 à 499".
     * → Extraire le premier nombre trouvé (borne basse).
     */
    private function cleanNbSalaries(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === '/') {
            return null;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value)) {
            return (int) $value;
        }

        $s = preg_replace('/\s+/', '', (string) $value);
        // Extraire le premier bloc numérique
        if (preg_match('/(\d+)/', $s, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * Téléphone : supprimer annotations (Nom), multi-numéros → garder le premier,
     * tronquer à 20 caractères.
     */
    private function cleanTelephone(mixed $value): ?string
    {
        $s = $this->str($value);
        if (! $s) {
            return null;
        }

        // Premier numéro si plusieurs séparés par \n ou /
        $s = preg_split('/[\n\r\/]+/', $s)[0] ?? '';
        $s = trim($s);

        // Supprimer annotations entre parenthèses : "06 12 (Nom)" → "06 12"
        $s = preg_replace('/\s*\([^)]*\)\s*/', ' ', $s);
        $s = trim($s);

        // Garder chiffres, espaces, +, point, tiret
        $s = preg_replace('/[^\d\s+.\-]/', '', $s);
        $s = trim(preg_replace('/\s+/', ' ', $s));

        return $s !== '' ? mb_substr($s, 0, 20) : null;
    }

    // ─── Helpers génériques ───────────────────────────────────────────

    private function cell(array $row, string $col): mixed
    {
        $index = self::COL[$col] ?? null;
        if ($index === null || ! array_key_exists($index, $row)) {
            return null;
        }

        return $row[$index];
    }

    private function str(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);

        return ($s === '' || $s === '/' || $s === '-') ? null : $s;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function resolveType(mixed $value): ?string
    {
        $v = mb_strtolower(trim((string) ($value ?? '')));
        $map = [
            'cse' => OrganizationType::CSE->value,
            'entreprise' => OrganizationType::EntrepriseDirecte->value,
            'boutique' => OrganizationType::EntrepriseDirecte->value,
            'syndicat' => OrganizationType::Syndicat->value,
            'association' => OrganizationType::Association->value,
            'ass' => OrganizationType::Association->value,
        ];
        foreach ($map as $key => $enumVal) {
            if (str_contains($v, $key)) {
                return $enumVal;
            }
        }

        return null;
    }

    private function resolveStatut(mixed $value): ?string
    {
        $v = mb_strtolower(trim((string) ($value ?? '')));
        // Trier par longueur desc pour matcher "signé nouveau process" avant "signé"
        $map = [
            'signé nouveau process' => OrganizationStatus::ConventionEngagement->value,
            'signe nouveau process' => OrganizationStatus::ConventionEngagement->value,
            'convention' => OrganizationStatus::ConventionEngagement->value,
            'en prospect' => OrganizationStatus::AProspecter->value,
            'à prospecter' => OrganizationStatus::AProspecter->value,
            'a prospecter' => OrganizationStatus::AProspecter->value,
            'prospect' => OrganizationStatus::AProspecter->value,
            'en cours' => OrganizationStatus::EnCoursProspection->value,
            'rdv' => OrganizationStatus::RdvEnCours->value,
            'signée' => OrganizationStatus::SigneAccordCadre->value,
            'signee' => OrganizationStatus::SigneAccordCadre->value,
            'signé' => OrganizationStatus::SigneAccordCadre->value,
            'signe' => OrganizationStatus::SigneAccordCadre->value,
            'refus' => OrganizationStatus::Refus->value,
        ];
        uksort($map, fn ($a, $b) => strlen($b) - strlen($a));
        foreach ($map as $key => $enumVal) {
            if (str_contains($v, $key)) {
                return $enumVal;
            }
        }

        return null;
    }

    private function parseBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = mb_strtolower(trim((string) $value));
        if (in_array($v, ['oui', 'yes', '1'])) {
            return true;
        }
        if (in_array($v, ['non', 'no', '0'])) {
            return false;
        }

        return null;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === '/') {
            return null;
        }
        try {
            if ($value instanceof \DateTime) {
                return $value->format('Y-m-d');
            }
            if (is_numeric($value)) {
                $ts = Date::excelToDateTimeObject((float) $value);
                $y = (int) $ts->format('Y');
                if ($y < 1900 || $y > 2100) {
                    return null;
                }

                return $ts->format('Y-m-d');
            }

            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    private function cleanInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === '/') {
            return null;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value)) {
            return (int) $value;
        }
        $digits = preg_replace('/[^\d]/', '', (string) $value);

        return $digits !== '' ? (int) $digits : null;
    }

    private function cleanDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === '/') {
            return null;
        }
        if (is_numeric($value)) {
            return (string) $value;
        }
        $clean = str_replace([' ', "\u{00A0}", "\u{202F}", ','], ['', '', '', '.'], (string) $value);
        $clean = preg_replace('/[^\d.]/', '', $clean);

        return is_numeric($clean) ? $clean : null;
    }

    private function getResult(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
    }

    // ── Fusion intelligente des données Partenaire ─────────────────────
    protected function mergePartenaireData(Partenaire $existing, array $newData): array
    {
        // Champs à préserver (ne pas écraser s'ils existent)
        $preserveFields = [
            'statut',
            'commentaires',
            'date_signature',
            'conseiller_id',
            'entite_id',
            'nomenclature_interne',
        ];

        // Si le statut n'est pas "À prospecter", le préserver absolument
        if ($existing->statut && $existing->statut !== 'a_prospecter') {
            unset($newData['statut']);
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
