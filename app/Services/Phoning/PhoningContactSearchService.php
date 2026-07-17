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
}
