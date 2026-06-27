<?php

namespace Database\Factories;

use App\Models\Prospect;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prospect>
 */
class ProspectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => fake()->company(),
            'type_pressenti' => fake()->randomElement(['CSE', 'Syndicat']),
            'departement' => fake()->numberBetween(1, 95),
            'telephone' => fake()->phoneNumber(),
            'telephone_alt' => fake()->optional()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'adresse' => fake()->streetAddress(),
            'code_postal' => fake()->postcode(),
            'ville' => fake()->city(),
            'siret' => fake()->numerify('#############'),
            'secteur_activite' => fake()->word(),
            'nb_salaries' => fake()->numberBetween(1, 500),
            'chiffre_affaires' => fake()->randomFloat(2, 10000, 10000000),
            'statut' => 'AC',
            'teleprospecteur_id' => User::factory(),
            'commercial_id' => User::factory(),
            'date_premier_contact' => fake()->optional()->date(),
            'rappel_planifie_at' => fake()->optional()->dateTime(),
            'interlocuteur_nom' => fake()->optional()->lastName(),
            'interlocuteur_fonction' => fake()->optional()->jobTitle(),
            'interlocuteur_telephone' => fake()->optional()->phoneNumber(),
            'interlocuteur_email' => fake()->optional()->email(),
            'description' => fake()->optional()->text(),
            'motif_ko' => fake()->optional()->text(),
            'qf_valide' => false,
            'valide_par' => null,
            'qf_valide_at' => null,
            'cse_secretaire_nom' => fake()->optional()->lastName(),
            'cse_secretaire_prenom' => fake()->optional()->firstName(),
            'cse_secretaire_tel_direct' => fake()->optional()->phoneNumber(),
            'cse_secretaire_tel_perso' => fake()->optional()->phoneNumber(),
            'cse_secretaire_email_pro' => fake()->optional()->email(),
            'cse_secretaire_email_perso' => fake()->optional()->email(),
            'cse_tresorier_nom' => fake()->optional()->lastName(),
            'cse_tresorier_prenom' => fake()->optional()->firstName(),
            'cse_tresorier_tel_direct' => fake()->optional()->phoneNumber(),
            'cse_tresorier_tel_perso' => fake()->optional()->phoneNumber(),
            'cse_tresorier_email_pro' => fake()->optional()->email(),
            'cse_tresorier_email_perso' => fake()->optional()->email(),
            'cse_nb_elus' => fake()->optional()->numberBetween(1, 20),
            'cse_date_fin_mandat' => fake()->optional()->date(),
            'cse_existence_juridique' => fake()->boolean(),
            'cse_notes' => fake()->optional()->text(),
            'syndicat_appartenance' => fake()->optional()->word(),
            'syndicat_nom_organisation' => fake()->optional()->company(),
            'syndicat_responsable_nom' => fake()->optional()->lastName(),
            'syndicat_responsable_prenom' => fake()->optional()->firstName(),
            'syndicat_responsable_fonction' => fake()->optional()->jobTitle(),
            'syndicat_tel_direct' => fake()->optional()->phoneNumber(),
            'syndicat_tel_perso' => fake()->optional()->phoneNumber(),
            'syndicat_email_pro' => fake()->optional()->email(),
            'syndicat_email_perso' => fake()->optional()->email(),
            'syndicat_perimetre' => fake()->optional()->text(),
            'syndicat_notes' => fake()->optional()->text(),
            'dirigeant_nom' => fake()->optional()->lastName(),
            'dirigeant_prenom' => fake()->optional()->firstName(),
            'dirigeant_fonction' => fake()->optional()->jobTitle(),
            'dirigeant_telephone' => fake()->optional()->phoneNumber(),
            'dirigeant_email' => fake()->optional()->email(),
        ];
    }
}
