<?php

namespace App\Services\Phoning;

use App\Enums\ProspectStatut;
use App\Models\Client;
use App\Models\ContactPartenaire;
use App\Models\ContactParticulier;
use App\Models\Partenaire;
use App\Models\Prospect;

/**
 * Universal contact search across prospects, clients, partenaires and their
 * contacts, used by the phoning workflow's "search a contact" box to let an
 * agent jump straight to a specific contact instead of following the queue.
 *
 * findByPhone() uses SQL LIKE on a 9-digit suffix of the normalized number
 * instead of loading all rows in memory — keeps response time sub-100ms
 * even with tens of thousands of records.
 */
class PhoningContactSearchService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function search(string $query): array
    {
        // Guard : la spec (Req 2.6) impose d'ignorer les requêtes < 2 chars
        if (strlen(trim($query)) < 2) {
            return [];
        }

        $results = [];

        // Prospects
        $prospects = Prospect::where(function ($q) use ($query) {
            $q->where('nom', 'LIKE', "%{$query}%")
                ->orWhere('telephone', 'LIKE', "%{$query}%")
                ->orWhere('siret', 'LIKE', "%{$query}%")
                ->orWhere('ville', 'LIKE', "%{$query}%")
                ->orWhere('email', 'LIKE', "%{$query}%");
        })
            ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
            ->whereNull('deleted_at')
            ->limit(20)
            ->get();

        foreach ($prospects as $prospect) {
            $results[] = [
                'id'          => $prospect->id,
                'type'        => 'prospect',
                'nom'         => $prospect->nom,
                'telephone'   => $prospect->telephone,
                'ville'       => $prospect->ville,
                'statut'      => $prospect->statut_label,
                'type_entite' => 'Prospect',
                'label'       => $prospect->nom.' - '.($prospect->ville ?? 'Sans ville'),
            ];
        }

        // Clients
        $clients = Client::where(function ($q) use ($query) {
            $q->where('nom_tiers', 'LIKE', "%{$query}%")
                ->orWhere('telephone', 'LIKE', "%{$query}%")
                ->orWhere('email', 'LIKE', "%{$query}%");
        })
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('ne_plus_contacter')->orWhere('ne_plus_contacter', false);
            })
            ->limit(10)
            ->get();

        foreach ($clients as $client) {
            $results[] = [
                'id'          => $client->id,
                'type'        => 'client',
                'nom'         => $client->nom_tiers,
                'telephone'   => $client->telephone,
                'ville'       => null,
                'statut'      => $client->etat ?? 'Client',
                'type_entite' => 'Client',
                'label'       => $client->nom_tiers.' - '.($client->entreprise ?? ''),
            ];
        }

        // Partenaires
        $partenaires = Partenaire::where(function ($q) use ($query) {
            $q->where('nom', 'LIKE', "%{$query}%")
                ->orWhere('entreprise', 'LIKE', "%{$query}%")
                ->orWhere('telephone', 'LIKE', "%{$query}%")
                ->orWhere('email', 'LIKE', "%{$query}%")
                ->orWhere('siret', 'LIKE', "%{$query}%")
                ->orWhere('ville', 'LIKE', "%{$query}%");
        })
            ->whereNull('deleted_at')
            ->limit(10)
            ->get();

        foreach ($partenaires as $partenaire) {
            $results[] = [
                'id'          => $partenaire->id,
                'type'        => 'partenaire',
                'nom'         => $partenaire->nom,
                'telephone'   => $partenaire->telephone,
                'ville'       => $partenaire->ville,
                'statut'      => $partenaire->statut_label,
                'type_entite' => 'Partenaire',
                'label'       => $partenaire->nom.' - '.($partenaire->entreprise ?? ''),
            ];
        }

        // Contacts Partenaires (personnes)
        $contactsPartenaire = ContactPartenaire::where(function ($q) use ($query) {
            $q->where('nom', 'LIKE', "%{$query}%")
                ->orWhere('prenom', 'LIKE', "%{$query}%")
                ->orWhere('email', 'LIKE', "%{$query}%")
                ->orWhere('telephone_direct', 'LIKE', "%{$query}%")
                ->orWhere('telephone_perso', 'LIKE', "%{$query}%")
                ->orWhere('telephone_mobile', 'LIKE', "%{$query}%");
        })
            ->whereNull('deleted_at')
            ->with('partenaire')
            ->limit(10)
            ->get();

        foreach ($contactsPartenaire as $contact) {
            $results[] = [
                'id'          => $contact->partenaire_id,
                'type'        => 'partenaire',
                'nom'         => trim($contact->prenom.' '.$contact->nom).' ('.($contact->partenaire->nom ?? '').')',
                'telephone'   => $contact->telephone_direct ?? $contact->telephone_mobile ?? $contact->telephone_perso,
                'ville'       => $contact->partenaire->ville ?? null,
                'statut'      => $contact->partenaire->statut_label ?? 'Contact',
                'type_entite' => 'Contact Partenaire',
                'label'       => trim($contact->prenom.' '.$contact->nom).' - '.($contact->fonction ?? ''),
            ];
        }

        return $results;
    }

    /**
     * Recherche par numéro de téléphone exact (normalisé).
     *
     * Stratégie SQL : on filtre en base sur les 9 derniers chiffres du numéro
     * normalisé (suffix LIKE) pour éviter de charger toute la table en mémoire.
     * Un filtre PHP secondaire garantit la correspondance exacte après normalisation
     * (cas où deux numéros différents partagent le même suffixe à 9 chiffres).
     *
     * @return list<array<string, mixed>>
     */
    public function findByPhone(string $phone): array
    {
        $normalized = $this->normalizePhone($phone);

        if (! $normalized) {
            return [];
        }

        $results = [];

        foreach ($this->searchMatchesForProspects($normalized) as $record) {
            $results[] = [
                'id'          => $record->id,
                'type'        => 'prospect',
                'nom'         => $record->nom,
                'telephone'   => $record->telephone,
                'ville'       => $record->ville,
                'statut'      => $record->statut_label,
                'type_entite' => 'Prospect',
                'label'       => $record->nom.' - '.($record->ville ?? 'Sans ville'),
            ];
        }

        foreach ($this->searchMatchesForClients($normalized) as $record) {
            $results[] = [
                'id'          => $record->id,
                'type'        => 'client',
                'nom'         => $record->nom_tiers,
                'telephone'   => $record->telephone,
                'ville'       => null,
                'statut'      => $record->etat ?? 'Client',
                'type_entite' => 'Client',
                'label'       => $record->nom_tiers.' - '.($record->entreprise ?? ''),
            ];
        }

        foreach ($this->searchMatchesForPartenaires($normalized) as $record) {
            $results[] = [
                'id'          => $record->id,
                'type'        => 'partenaire',
                'nom'         => $record->nom,
                'telephone'   => $record->telephone,
                'ville'       => $record->ville,
                'statut'      => $record->statut_label,
                'type_entite' => 'Partenaire',
                'label'       => $record->nom.' - '.($record->entreprise ?? ''),
            ];
        }

        foreach ($this->searchMatchesForContactsPartenaire($normalized) as $record) {
            $results[] = [
                'id'          => $record->id,
                'type'        => 'partenaire',
                'nom'         => trim($record->prenom.' '.$record->nom).' ('.($record->partenaire->nom ?? '').')',
                'telephone'   => $record->telephone_direct ?? $record->telephone_mobile ?? $record->telephone_perso,
                'ville'       => $record->partenaire->ville ?? null,
                'statut'      => $record->partenaire->statut_label ?? 'Contact',
                'type_entite' => 'Contact Partenaire',
                'label'       => trim($record->prenom.' '.$record->nom).' - '.($record->fonction ?? ''),
            ];
        }

        foreach ($this->searchMatchesForContactsParticuliers($normalized) as $record) {
            $results[] = [
                'id'          => $record->id,
                'type'        => 'particulier',
                'nom'         => trim($record->prenom.' '.$record->nom),
                'telephone'   => $record->telephone,
                'ville'       => null,
                'statut'      => $record->statut_occupant?->label() ?? 'Particulier',
                'type_entite' => 'Particulier',
                'label'       => trim($record->prenom.' '.$record->nom),
            ];
        }

        return $this->deduplicateResults($results);
    }

    // ── Normalisation ────────────────────────────────────────────────

    /**
     * Retourne les chiffres uniquement, convertit 33XXXXXXXXX → 0XXXXXXXXX.
     */
    public function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $phone) ?: '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '33') && strlen($digits) === 11) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Retourne les 9 derniers chiffres du numéro normalisé pour le filtre SQL.
     * Permet d'ignorer les préfixes variables (0, +33, 33) sans stocker un champ normalisé.
     */
    private function phoneSuffix(string $normalized): string
    {
        return strlen($normalized) >= 9
            ? substr($normalized, -9)
            : $normalized;
    }

    private function matchesPhone(?string $candidate, string $needle): bool
    {
        if (! filled($candidate)) {
            return false;
        }

        return $this->normalizePhone($candidate) === $needle;
    }

    // ── Requêtes SQL optimisées (filtre suffix + vérification PHP exacte) ──

    /**
     * @return list<Prospect>
     */
    private function searchMatchesForProspects(string $normalized): array
    {
        $suffix = $this->phoneSuffix($normalized);

        // Filtre SQL sur le suffix → charge uniquement les lignes candidates
        $candidates = Prospect::query()
            ->whereNull('deleted_at')
            ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
            ->where(function ($q) use ($suffix) {
                $q->where('telephone', 'LIKE', "%{$suffix}")
                    ->orWhere('telephone_alt', 'LIKE', "%{$suffix}")
                    ->orWhere('interlocuteur_telephone', 'LIKE', "%{$suffix}");
            })
            ->get();

        // Vérification PHP exacte pour éliminer les faux positifs de suffix
        return $candidates->filter(function ($prospect) use ($normalized) {
            return $this->matchesPhone($prospect->telephone, $normalized)
                || $this->matchesPhone($prospect->telephone_alt, $normalized)
                || $this->matchesPhone($prospect->interlocuteur_telephone, $normalized);
        })->values()->all();
    }

    /**
     * @return list<Client>
     */
    private function searchMatchesForClients(string $normalized): array
    {
        $suffix = $this->phoneSuffix($normalized);

        $candidates = Client::query()
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('ne_plus_contacter')->orWhere('ne_plus_contacter', false);
            })
            ->where('telephone', 'LIKE', "%{$suffix}")
            ->get();

        return $candidates->filter(fn ($c) => $this->matchesPhone($c->telephone, $normalized))
            ->values()->all();
    }

    /**
     * @return list<Partenaire>
     */
    private function searchMatchesForPartenaires(string $normalized): array
    {
        $suffix = $this->phoneSuffix($normalized);

        $candidates = Partenaire::query()
            ->whereNull('deleted_at')
            ->where('telephone', 'LIKE', "%{$suffix}")
            ->get();

        return $candidates->filter(fn ($p) => $this->matchesPhone($p->telephone, $normalized))
            ->values()->all();
    }

    /**
     * @return list<ContactPartenaire>
     */
    private function searchMatchesForContactsPartenaire(string $normalized): array
    {
        $suffix = $this->phoneSuffix($normalized);

        $candidates = ContactPartenaire::query()
            ->whereNull('deleted_at')
            ->where(function ($q) use ($suffix) {
                $q->where('telephone_direct', 'LIKE', "%{$suffix}")
                    ->orWhere('telephone_mobile', 'LIKE', "%{$suffix}")
                    ->orWhere('telephone_perso', 'LIKE', "%{$suffix}");
            })
            ->with('partenaire')
            ->get();

        return $candidates->filter(function ($contact) use ($normalized) {
            return $this->matchesPhone($contact->telephone_direct, $normalized)
                || $this->matchesPhone($contact->telephone_mobile, $normalized)
                || $this->matchesPhone($contact->telephone_perso, $normalized);
        })->values()->all();
    }

    /**
     * @return list<ContactParticulier>
     */
    private function searchMatchesForContactsParticuliers(string $normalized): array
    {
        $suffix = $this->phoneSuffix($normalized);

        $candidates = ContactParticulier::query()
            ->where('telephone', 'LIKE', "%{$suffix}")
            ->get();

        return $candidates->filter(fn ($c) => $this->matchesPhone($c->telephone, $normalized))
            ->values()->all();
    }

    // ── Dédoublonnage ────────────────────────────────────────────────

    private function deduplicateResults(array $results): array
    {
        $seen = [];

        return array_values(array_filter($results, function ($result) use (&$seen) {
            $key = $result['type'].'-'.$result['id'];

            if (isset($seen[$key])) {
                return false;
            }

            $seen[$key] = true;

            return true;
        }));
    }
}
