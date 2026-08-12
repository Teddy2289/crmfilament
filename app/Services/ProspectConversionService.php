<?php

namespace App\Services;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Models\ContactPartenaire;
use App\Models\Partenaire;
use App\Models\Prospect;
use Illuminate\Support\Facades\DB;

class ProspectConversionService
{
    /**
     * Convert a qualified prospect into a partner.
     *
     * This service handles the complete conversion process:
     * - Validates the prospect is convertible (QF validated, not already converted)
     * - Creates a new Partenaire with all relevant data
     * - Migrates contacts (dirigeant, CSE secretary/treasurer, syndicat responsible)
     * - Updates the prospect with conversion reference
     * - Soft deletes the prospect
     * - All operations are wrapped in a database transaction
     *
     * @param Prospect $prospect The prospect to convert
     * @return Partenaire The created partner
     * @throws \Exception If the prospect is not convertible
     */
    public function convertProspectToPartenaire(Prospect $prospect): Partenaire
    {
        if (! $prospect->est_convertible_en_partenaire) {
            throw new \Exception('Seuls les prospects QF validés et non déjà convertis peuvent être convertis en partenaire.');
        }

        return DB::transaction(function () use ($prospect): Partenaire {
            $partenaire = $this->createPartenaireFromProspect($prospect);
            $this->migrateContacts($prospect, $partenaire);
            $this->finalizeConversion($prospect, $partenaire);

            return $partenaire;
        });
    }

    /**
     * Create a Partenaire from prospect data.
     */
    protected function createPartenaireFromProspect(Prospect $prospect): Partenaire
    {
        return Partenaire::create([
            'nom' => $prospect->nom,
            'type' => $prospect->type_pressenti ?? OrganizationType::EntrepriseDirecte->value,
            'siret' => $prospect->siret,
            'telephone' => $prospect->telephone,
            'email' => $prospect->email,
            'adresse' => $prospect->adresse,
            'code_postal' => $prospect->code_postal,
            'ville' => $prospect->ville,
            'departement' => $prospect->departement,
            'secteur_activite' => $prospect->secteur_activite,
            'nb_salaries' => $prospect->nb_salaries,
            'chiffre_affaires' => $prospect->chiffre_affaires,
            'commercial_id' => $prospect->commercial_id,
            'statut' => OrganizationStatus::SigneAccordCadre,
            'prospect_id' => $prospect->id,
            'notes' => "Converti depuis prospect #{$prospect->id}\n{$prospect->description}",
        ]);
    }

    /**
     * Migrate all contact types from prospect to partner.
     */
    protected function migrateContacts(Prospect $prospect, Partenaire $partenaire): void
    {
        $this->migrateDirigeant($prospect, $partenaire);
        $this->migrateCseSecretaire($prospect, $partenaire);
        $this->migrateCseTresorier($prospect, $partenaire);
        $this->migrateSyndicatResponsable($prospect, $partenaire);
    }

    /**
     * Migrate the dirigeant (director) to ContactPartenaire.
     */
    protected function migrateDirigeant(Prospect $prospect, Partenaire $partenaire): void
    {
        if (! $prospect->dirigeant_nom) {
            return;
        }

        ContactPartenaire::create([
            'partenaire_id' => $partenaire->id,
            'civilite' => 'M.',
            'nom' => $prospect->dirigeant_nom,
            'prenom' => $prospect->dirigeant_prenom,
            'fonction' => $prospect->dirigeant_fonction,
            'email' => $prospect->dirigeant_email,
            'telephone_direct' => $prospect->dirigeant_telephone,
            'est_principal' => true,
            'est_decisionnaire' => true,
            'niveau_influence' => 5,
            'notes' => 'Migré depuis prospect (dirigeant)',
        ]);
    }

    /**
     * Migrate the CSE secretary to ContactPartenaire.
     */
    protected function migrateCseSecretaire(Prospect $prospect, Partenaire $partenaire): void
    {
        if (! $prospect->cse_secretaire_nom) {
            return;
        }

        ContactPartenaire::create([
            'partenaire_id' => $partenaire->id,
            'civilite' => 'Mme',
            'nom' => $prospect->cse_secretaire_nom,
            'prenom' => $prospect->cse_secretaire_prenom,
            'fonction' => 'Secrétaire CSE',
            'email' => $prospect->cse_secretaire_email_pro,
            'email_perso' => $prospect->cse_secretaire_email_perso,
            'telephone_direct' => $prospect->cse_secretaire_tel_direct,
            'telephone_perso' => $prospect->cse_secretaire_tel_perso,
            'est_principal' => false,
            'notes' => 'Migré depuis prospect (secrétaire CSE)',
        ]);
    }

    /**
     * Migrate the CSE treasurer to ContactPartenaire.
     */
    protected function migrateCseTresorier(Prospect $prospect, Partenaire $partenaire): void
    {
        if (! $prospect->cse_tresorier_nom) {
            return;
        }

        ContactPartenaire::create([
            'partenaire_id' => $partenaire->id,
            'civilite' => 'M.',
            'nom' => $prospect->cse_tresorier_nom,
            'prenom' => $prospect->cse_tresorier_prenom,
            'fonction' => 'Trésorier CSE',
            'email' => $prospect->cse_tresorier_email_pro,
            'email_perso' => $prospect->cse_tresorier_email_perso,
            'telephone_direct' => $prospect->cse_tresorier_tel_direct,
            'telephone_perso' => $prospect->cse_tresorier_tel_perso,
            'est_principal' => false,
            'notes' => 'Migré depuis prospect (trésorier CSE)',
        ]);
    }

    /**
     * Migrate the syndicat responsible to ContactPartenaire.
     */
    protected function migrateSyndicatResponsable(Prospect $prospect, Partenaire $partenaire): void
    {
        if (! $prospect->syndicat_responsable_nom) {
            return;
        }

        ContactPartenaire::create([
            'partenaire_id' => $partenaire->id,
            'civilite' => 'M.',
            'nom' => $prospect->syndicat_responsable_nom,
            'prenom' => $prospect->syndicat_responsable_prenom,
            'fonction' => $prospect->syndicat_responsable_fonction ?: 'Délégué syndical',
            'nom_syndicat' => $prospect->syndicat_appartenance,
            'email' => $prospect->syndicat_email_pro,
            'email_perso' => $prospect->syndicat_email_perso,
            'telephone_direct' => $prospect->syndicat_tel_direct,
            'telephone_perso' => $prospect->syndicat_tel_perso,
            'est_principal' => false,
            'notes' => 'Migré depuis prospect (responsable syndical)',
        ]);
    }

    /**
     * Finalize the conversion by updating the prospect and soft-deleting it.
     */
    protected function finalizeConversion(Prospect $prospect, Partenaire $partenaire): void
    {
        $prospect->update([
            'converti_partenaire_id' => $partenaire->id,
            'description' => $prospect->description."\n[Conversion] Partenaire créé le ".now()->format('d/m/Y H:i'),
        ]);

        $prospect->delete();
    }
}
