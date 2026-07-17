<?php

namespace App\Services\Phoning;

use App\Models\ArtisanProspection;
use App\Models\Client;
use App\Models\ContactParticulier;
use App\Models\ContactPartenaire;
use App\Models\Prospect;
use Illuminate\Database\Eloquent\Model;

/**
 * Resolves a phoning queue entry ('type' + 'id') to its Eloquent model,
 * and flattens that model into the array shape the phoning workflow view expects.
 */
class PhoningContactResolver
{
    public function resolveModel(string $type, int $id): ?Model
    {
        return match ($type) {
            'prospect' => Prospect::find($id),
            'artisan' => ArtisanProspection::find($id),
            'partenaire' => ContactPartenaire::find($id),
            'particulier' => ContactParticulier::find($id),
            'client' => Client::find($id),
            default => null,
        };
    }

    public function buildContactData(Model $model, string $type): array
    {
        return match ($type) {
            'prospect' => [
                'nom' => $model->nom,
                'prenom' => null,
                'siret' => $model->siret,
                'type_pressenti' => $model->type_pressenti_label,
                'secteur_activite' => $model->secteur_activite,
                'nb_salaries' => $model->nb_salaries,
                'chiffre_affaires' => $model->chiffre_affaires
                    ? number_format($model->chiffre_affaires, 0, ',', ' ').' €'
                    : null,
                'telephone' => $model->telephone,
                'telephone_alt' => $model->telephone_alt,
                'email' => $model->email,
                'adresse' => $model->adresse,
                'ville' => $model->ville,
                'code_postal' => $model->code_postal,
                'departement' => $model->departement,
                'adresse_complete' => $model->adresse_complete,
                'interlocuteur_nom' => $model->interlocuteur_nom,
                'interlocuteur_fonction' => $model->interlocuteur_fonction,
                'interlocuteur_telephone' => $model->interlocuteur_telephone,
                'interlocuteur_email' => $model->interlocuteur_email,
                'interlocuteur' => $model->interlocuteur_complet,
                'statut' => $model->statut_label,
                'statut_color' => $model->statut_color,
                'statut_description' => $model->statut_description,
                'taux_engagement' => $model->taux_engagement,
                'priorite' => $model->type_pressenti
                    ? ucfirst(str_replace('_', ' ', $model->type_pressenti))
                    : 'Standard',
                'teleprospecteur' => $model->teleprospecteur
                    ? trim("{$model->teleprospecteur->prenom} {$model->teleprospecteur->nom}")
                    : null,
                'commercial' => $model->commercial
                    ? trim("{$model->commercial->prenom} {$model->commercial->nom}")
                    : null,
                'date_premier_contact' => $model->date_premier_contact?->format('d/m/Y'),
                'rappel_planifie_at' => $model->rappel_planifie_at?->format('d/m/Y à H:i'),
                'rappel_en_retard' => $model->rappel_est_en_retard,
                'jours_depuis_contact' => $model->jours_depuis_premier_contact,
                'notes' => $model->description,
                'motif_ko' => $model->motif_ko,
                'qf_valide' => $model->qf_valide,
                'id' => $model->id,
                'type' => 'prospect',
            ],
            'artisan' => [
                'nom' => $model->nom,
                'prenom' => null,
                'telephone' => $model->telephone,
                'telephone_alt' => null,
                'email' => null,
                'statut' => $model->statut_campagne->label(),
                'statut_color' => 'info',
                'priorite' => $model->priorite_segment->label(),
                'metier' => $model->corps_de_metier?->label(),
                'notes' => $model->notes,
                'id' => $model->id,
                'type' => 'artisan',
                'adresse_complete' => null,
                'interlocuteur' => null,
                'nb_salaries'     => null,
                'chiffre_affaires' => null,
                'siret'           => null,
            ],
            'partenaire' => [
                'nom' => $model->nom,
                'prenom' => $model->prenom,
                'telephone' => $model->telephone_direct ?? $model->telephone_mobile ?? $model->telephone_perso,
                'telephone_alt' => null,
                'email' => $model->email ?? $model->email_perso,
                'statut' => $model->est_principal ? 'Principal' : 'Contact',
                'statut_color' => 'success',
                'priorite' => $model->niveau_influence_label ?? 'Standard',
                'notes' => $model->notes,
                'id' => $model->id,
                'type' => 'partenaire',
                'interlocuteur' => $model->fonction,
                'adresse_complete' => null,
                'nb_salaries'     => null,
                'chiffre_affaires' => null,
                'siret'           => null,
                'ville'           => null,
                'departement'     => null,
                'code_postal'     => null,
                'type_pressenti'  => null,
                'secteur_activite' => null,
            ],
            'particulier' => [
                'nom' => $model->nom,
                'prenom' => $model->prenom,
                'telephone' => $model->telephone,
                'telephone_alt' => null,
                'email' => $model->email,
                'statut' => $model->statut_occupant?->label() ?? 'Contact',
                'statut_color' => 'gray',
                'priorite' => $model->type_logement?->label() ?? 'Standard',
                'notes' => $model->adresse_complete,
                'id' => $model->id,
                'type' => 'particulier',
                'adresse_complete' => $model->adresse_complete ?? null,
                'interlocuteur' => null,
            ],
            'client' => [
                'nom' => $model->nom_tiers,
                'prenom' => $model->prenom,
                'telephone' => $model->telephone,
                'telephone_alt' => null,
                'email' => $model->email,
                'statut' => $model->etat ?? 'Client',
                'statut_color' => 'success',
                'priorite' => $model->type_tiers ?? 'Standard',
                'notes' => $model->entreprise,
                'adresse_complete' => $model->adresse_complete ?? null,
                'interlocuteur' => null,
                'id' => $model->id,
                'type' => 'client',
            ],
            default => [],
        };
    }
}
