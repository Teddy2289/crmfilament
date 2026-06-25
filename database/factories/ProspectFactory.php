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
            'nom' => fake()->lastName(),
            'prenom' => fake()->firstName(),
            'raison_sociale' => fake()->company(),
            'telephone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'adresse' => fake()->streetAddress(),
            'code_postal' => fake()->postcode(),
            'ville' => fake()->city(),
            'departement' => fake()->department(),
            'region' => fake()->region(),
            'siret' => fake()->numerify('#############'),
            'statut' => 'a_contacter',
            'statut_phoning_code' => 'AC',
            'teleprospecteur_id' => User::factory(),
            'commercial_id' => User::factory(),
            'actif' => true,
        ];
    }
}
