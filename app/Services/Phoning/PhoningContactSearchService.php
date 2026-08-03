<?php

namespace App\Services\Phoning;

use App\Enums\ProspectStatut;
use App\Models\Client;
use App\Models\ContactPartenaire;
use App\Models\Partenaire;
use App\Models\Prospect;

/**
 * Universal contact search across prospects, clients, partenaires and their
 * contacts, used by the phoning workflow's "search a contact" box to let an
 * agent jump straight to a specific contact instead of following the queue.
 */
class PhoningContactSearchService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function search(string $query): array
    {
        $results = [];

        // Recherche dans les prospects
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
                'id' => $prospect->id,
                'type' => 'prospect',
                'nom' => $prospect->nom,
                'telephone' => $prospect->telephone,
                'ville' => $prospect->ville,
                'statut' => $prospect->statut_label,
                'type_entite' => 'Prospect',
                'label' => $prospect->nom.' - '.($prospect->ville ?? 'Sans ville'),
            ];
        }

        // Recherche dans les clients
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
                'id' => $client->id,
                'type' => 'client',
                'nom' => $client->nom_tiers,
                'telephone' => $client->telephone,
                'ville' => null,
                'statut' => $client->etat ?? 'Client',
                'type_entite' => 'Client',
                'label' => $client->nom_tiers.' - '.($client->entreprise ?? ''),
            ];
        }

        // Recherche dans les partenaires
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
                'id' => $partenaire->id,
                'type' => 'partenaire',
                'nom' => $partenaire->nom,
                'telephone' => $partenaire->telephone,
                'ville' => $partenaire->ville,
                'statut' => $partenaire->statut_label,
                'type_entite' => 'Partenaire',
                'label' => $partenaire->nom.' - '.($partenaire->entreprise ?? ''),
            ];
        }

        // Recherche dans les contacts partenaires (personnes)
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
                'id' => $contact->partenaire_id,
                'type' => 'partenaire',
                'nom' => trim($contact->prenom.' '.$contact->nom).' ('.($contact->partenaire->nom ?? '').')',
                'telephone' => $contact->telephone_direct ?? $contact->telephone_mobile ?? $contact->telephone_perso,
                'ville' => $contact->partenaire->ville ?? null,
                'statut' => $contact->partenaire->statut_label ?? 'Contact',
                'type_entite' => 'Contact Partenaire',
                'label' => trim($contact->prenom.' '.$contact->nom).' - '.($contact->fonction ?? ''),
            ];
        }

        return $results;
    }

    /**
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
                'id' => $record->id,
                'type' => 'prospect',
                'nom' => $record->nom,
                'telephone' => $record->telephone,
                'ville' => $record->ville,
                'statut' => $record->statut_label,
                'type_entite' => 'Prospect',
                'label' => $record->nom.' - '.($record->ville ?? 'Sans ville'),
            ];
        }

        foreach ($this->searchMatchesForClients($normalized) as $record) {
            $results[] = [
                'id' => $record->id,
                'type' => 'client',
                'nom' => $record->nom_tiers,
                'telephone' => $record->telephone,
                'ville' => null,
                'statut' => $record->etat ?? 'Client',
                'type_entite' => 'Client',
                'label' => $record->nom_tiers.' - '.($record->entreprise ?? ''),
            ];
        }

        foreach ($this->searchMatchesForPartenaires($normalized) as $record) {
            $results[] = [
                'id' => $record->id,
                'type' => 'partenaire',
                'nom' => $record->nom,
                'telephone' => $record->telephone,
                'ville' => $record->ville,
                'statut' => $record->statut_label,
                'type_entite' => 'Partenaire',
                'label' => $record->nom.' - '.($record->entreprise ?? ''),
            ];
        }

        foreach ($this->searchMatchesForContactsPartenaire($normalized) as $record) {
            $results[] = [
                'id' => $record->id,
                'type' => 'partenaire',
                'nom' => trim($record->prenom.' '.$record->nom).' ('.($record->partenaire->nom ?? '').')',
                'telephone' => $record->telephone_direct ?? $record->telephone_mobile ?? $record->telephone_perso,
                'ville' => $record->partenaire->ville ?? null,
                'statut' => $record->partenaire->statut_label ?? 'Contact',
                'type_entite' => 'Contact Partenaire',
                'label' => trim($record->prenom.' '.$record->nom).' - '.($record->fonction ?? ''),
            ];
        }

        foreach ($this->searchMatchesForContactsParticuliers($normalized) as $record) {
            $results[] = [
                'id' => $record->id,
                'type' => 'particulier',
                'nom' => trim($record->prenom.' '.$record->nom),
                'telephone' => $record->telephone,
                'ville' => null,
                'statut' => $record->statut_occupant?->label() ?? 'Particulier',
                'type_entite' => 'Particulier',
                'label' => trim($record->prenom.' '.$record->nom),
            ];
        }

        return $this->deduplicateResults($results);
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $phone) ?: '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '33') && strlen($digits) === 11) {
            $digits = '0' . substr($digits, 2);
        }

        return $digits;
    }

    private function matchesPhone(?string $candidate, string $needle): bool
    {
        if (! filled($candidate)) {
            return false;
        }

        return $this->normalizePhone($candidate) === $needle;
    }

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

    private function searchMatchesForProspects(string $normalized): array
    {
        $prospects = Prospect::query()
            ->whereNull('deleted_at')
            ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
            ->get();

        return $prospects->filter(function ($prospect) use ($normalized) {
            return $this->matchesPhone($prospect->telephone, $normalized)
                || $this->matchesPhone($prospect->telephone_alt, $normalized)
                || $this->matchesPhone($prospect->interlocuteur_telephone, $normalized);
        })->values()->all();
    }

    private function searchMatchesForClients(string $normalized): array
    {
        $clients = Client::query()
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereNull('ne_plus_contacter')->orWhere('ne_plus_contacter', false);
            })
            ->get();

        return $clients->filter(function ($client) use ($normalized) {
            return $this->matchesPhone($client->telephone, $normalized);
        })->values()->all();
    }

    private function searchMatchesForPartenaires(string $normalized): array
    {
        $partenaires = Partenaire::query()
            ->whereNull('deleted_at')
            ->get();

        return $partenaires->filter(function ($partenaire) use ($normalized) {
            return $this->matchesPhone($partenaire->telephone, $normalized);
        })->values()->all();
    }

    private function searchMatchesForContactsPartenaire(string $normalized): array
    {
        $contacts = \App\Models\ContactPartenaire::query()
            ->whereNull('deleted_at')
            ->with('partenaire')
            ->get();

        return $contacts->filter(function ($contact) use ($normalized) {
            return $this->matchesPhone($contact->telephone_direct, $normalized)
                || $this->matchesPhone($contact->telephone_mobile, $normalized)
                || $this->matchesPhone($contact->telephone_perso, $normalized);
        })->values()->all();
    }

    private function searchMatchesForContactsParticuliers(string $normalized): array
    {
        $contacts = \App\Models\ContactParticulier::query()->get();

        return $contacts->filter(function ($contact) use ($normalized) {
            return $this->matchesPhone($contact->telephone, $normalized);
        })->values()->all();
    }
}
