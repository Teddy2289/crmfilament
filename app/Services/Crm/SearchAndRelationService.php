<?php

namespace App\Services\Crm;

use App\Models\Client;
use App\Models\Entreprise;
use App\Models\Partenaire;
use App\Models\Prospect;
use App\Support\PhoneNumber;

class SearchAndRelationService
{
    public function searchGlobal(string $query): array
    {
        if (strlen($query) < 3) {
            return [];
        }

        return [
            'prospects' => $this->searchProspects($query),
            'clients' => $this->searchClients($query),
            'partenaires' => $this->searchPartenaires($query),
            'entreprises' => $this->searchEntreprises($query),
        ];
    }

    protected function searchProspects(string $query): array
    {
        $query = trim($query);
        $phoneColumns = [
            'telephone', 'telephone_alt', 'interlocuteur_telephone', 'interlocuteur_add_telephone',
            'cse_secretaire_tel_direct', 'cse_secretaire_tel_perso',
            'cse_tresorier_tel_direct', 'cse_tresorier_tel_perso',
            'syndicat_tel_direct', 'syndicat_tel_perso', 'dirigeant_telephone',
        ];
        $textColumns = [
            'email', 'interlocuteur_email', 'interlocuteur_add_email',
            'cse_secretaire_nom', 'cse_secretaire_prenom', 'cse_secretaire_email_pro', 'cse_secretaire_email_perso',
            'cse_tresorier_nom', 'cse_tresorier_prenom', 'cse_tresorier_email_pro', 'cse_tresorier_email_perso',
            'cse_existence_juridique', 'cse_notes', 'presence_cse',
            'syndicat_appartenance', 'syndicat_nom_organisation', 'syndicat_responsable_nom',
            'syndicat_responsable_prenom', 'syndicat_responsable_fonction', 'syndicat_email_pro',
            'syndicat_email_perso', 'syndicat_perimetre', 'syndicat_notes',
            'dirigeant_nom', 'dirigeant_prenom', 'dirigeant_email',
            'nom', 'ville', 'departement', 'siret', 'secteur_activite',
        ];
        $variants = PhoneNumber::searchVariants($query);

        return Prospect::where(function ($q) use ($query, $phoneColumns, $textColumns, $variants) {
            $first = true;
            if ($variants !== []) {
                foreach ($phoneColumns as $column) {
                    $qualified = $q->getModel()->getTable() . '.' . $column;
                    $expression = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE($qualified, ''), ' ', ''), '.', ''), '-', ''), '(', ''), ')', ''), '+', '')";
                    foreach ($variants as $variant) {
                        $method = $first ? 'whereRaw' : 'orWhereRaw';
                        $q->{$method}($expression . ' LIKE ?', ['%' . $variant . '%']);
                        $first = false;
                    }
                }
            }
            foreach ($textColumns as $column) {
                $method = $first ? 'where' : 'orWhere';
                $q->{$method}($column, 'like', '%' . $query . '%');
                $first = false;
            }
        })
        ->with(['teleprospecteur', 'commercial'])
        ->limit(30)
        ->get()
        ->map(fn ($prospect) => [
            'id' => $prospect->id,
            'type' => 'prospect',
            'nom' => $prospect->nom,
            'telephone' => $prospect->telephone,
            'email' => $prospect->email,
            'statut' => $prospect->statut,
            'ville' => $prospect->ville,
            'presence_cse' => $prospect->presence_cse,
            'cse_contact' => trim(($prospect->cse_secretaire_prenom ?? '') . ' ' . ($prospect->cse_secretaire_nom ?? '')),
            'teleprospecteur' => $prospect->teleprospecteur ? "{$prospect->teleprospecteur->prenom} {$prospect->teleprospecteur->nom}" : null,
            'url' => \App\Filament\NsConseil\Resources\ProspectResource::getUrl('view', ['record' => $prospect->id]),
        ])
        ->toArray();
    }
    protected function searchClients(string $query): array
    {
        return Client::where(function ($q) use ($query) {
            $q->where('telephone', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->orWhere('nom_tiers', 'like', "%{$query}%")
                ->orWhere('ref_client', 'like', "%{$query}%")
                ->orWhere('ville', 'like', "%{$query}%");
        })
        ->with(['commercial', 'partenaire'])
        ->limit(20)
        ->get()
        ->map(fn ($client) => [
            'id' => $client->id,
            'type' => 'client',
            'nom' => $client->nom_tiers,
            'telephone' => $client->telephone,
            'email' => $client->email,
            'ref_client' => $client->ref_client,
            'ref_clients' => $client->ref_clients,
            'etat' => $client->etat,
            'ville' => $client->ville,
            'commercial' => $client->commercial ? "{$client->commercial->prenom} {$client->commercial->nom}" : null,
            'url' => \App\Filament\NsConseil\Resources\ClientResource::getUrl('view', ['record' => $client->id]),
        ])
        ->toArray();
    }

    protected function searchPartenaires(string $query): array
    {
        return Partenaire::where(function ($q) use ($query) {
            $q->where('telephone', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->orWhere('nom', 'like', "%{$query}%")
                ->orWhere('nom_retenu', 'like', "%{$query}%")
                ->orWhere('nomenclature_interne', 'like', "%{$query}%")
                ->orWhere('ville', 'like', "%{$query}%");
        })
        ->with(['conseiller', 'entite'])
        ->limit(20)
        ->get()
        ->map(fn ($partenaire) => [
            'id' => $partenaire->id,
            'type' => 'partenaire',
            'nom' => $partenaire->nom,
            'telephone' => $partenaire->telephone,
            'email' => $partenaire->email,
            'statut' => $partenaire->statut,
            'ville' => $partenaire->ville,
            'conseiller' => $partenaire->conseiller ? "{$partenaire->conseiller->prenom} {$partenaire->conseiller->nom}" : null,
            'url' => \App\Filament\NsConseil\Resources\PartenaireResource::getUrl('view', ['record' => $partenaire->id]),
        ])
        ->toArray();
    }

    protected function searchEntreprises(string $query): array
    {
        return Entreprise::where(function ($q) use ($query) {
            $q->where('raison_sociale', 'like', "%{$query}%")
                ->orWhere('siret', 'like', "%{$query}%")
                ->orWhere('siren', 'like', "%{$query}%")
                ->orWhere('telephone', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->orWhere('ville', 'like', "%{$query}%");
        })
        ->with(['partenaires', 'clients'])
        ->limit(20)
        ->get()
        ->map(fn ($entreprise) => [
            'id' => $entreprise->id,
            'type' => 'entreprise',
            'nom' => $entreprise->raison_sociale,
            'siret' => $entreprise->siret,
            'telephone' => $entreprise->telephone,
            'email' => $entreprise->email,
            'ville' => $entreprise->ville,
            'secteur' => $entreprise->secteur_activite,
            'nb_partenaires' => $entreprise->partenaires->count(),
            'nb_clients' => $entreprise->clients->count(),
            'url' => \App\Filament\NsConseil\Resources\EntrepriseResource::getUrl('view', ['record' => $entreprise->id]),
        ])
        ->toArray();
    }

    public function findRelatedEntities(string $type, int $id): array
    {
        $relations = [];

        switch ($type) {
            case 'prospect':
                $prospect = Prospect::find($id);
                if ($prospect) {
                    $relations = $this->findRelatedToProspect($prospect);
                }
                break;
            case 'client':
                $client = Client::find($id);
                if ($client) {
                    $relations = $this->findRelatedToClient($client);
                }
                break;
            case 'partenaire':
                $partenaire = Partenaire::find($id);
                if ($partenaire) {
                    $relations = $this->findRelatedToPartenaire($partenaire);
                }
                break;
        }

        return $relations;
    }

    protected function findRelatedToProspect(Prospect $prospect): array
    {
        $related = [];

        // Chercher par téléphone
        if ($prospect->telephone) {
            $related['clients'] = Client::where('telephone', $prospect->telephone)->get();
            $related['partenaires'] = Partenaire::where('telephone', $prospect->telephone)->get();
        }

        // Chercher par email
        if ($prospect->email) {
            $related['clients'] = ($related['clients'] ?? collect())->merge(
                Client::where('email', $prospect->email)->get()
            );
            $related['partenaires'] = ($related['partenaires'] ?? collect())->merge(
                Partenaire::where('email', $prospect->email)->get()
            );
        }

        return $related;
    }

    protected function findRelatedToClient(Client $client): array
    {
        $related = [];

        // Chercher par téléphone
        if ($client->telephone) {
            $related['prospects'] = Prospect::where('telephone', $client->telephone)->get();
            $related['partenaires'] = Partenaire::where('telephone', $client->telephone)->get();
        }

        // Chercher par email
        if ($client->email) {
            $related['prospects'] = ($related['prospects'] ?? collect())->merge(
                Prospect::where('email', $client->email)->get()
            );
            $related['partenaires'] = ($related['partenaires'] ?? collect())->merge(
                Partenaire::where('email', $client->email)->get()
            );
        }

        return $related;
    }

    protected function findRelatedToPartenaire(Partenaire $partenaire): array
    {
        $related = [];

        // Chercher par téléphone
        if ($partenaire->telephone) {
            $related['prospects'] = Prospect::where('telephone', $partenaire->telephone)->get();
            $related['clients'] = Client::where('telephone', $partenaire->telephone)->get();
        }

        // Chercher par email
        if ($partenaire->email) {
            $related['prospects'] = ($related['prospects'] ?? collect())->merge(
                Prospect::where('email', $partenaire->email)->get()
            );
            $related['clients'] = ($related['clients'] ?? collect())->merge(
                Client::where('email', $partenaire->email)->get()
            );
        }

        // Entreprise liée
        if ($partenaire->entreprise) {
            $related['entreprise'] = $partenaire->entreprise;
        }

        return $related;
    }
}
