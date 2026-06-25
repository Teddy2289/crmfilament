<?php

namespace Database\Factories;

use App\Models\Partenaire;
use App\Enums\OrganizationType;
use App\Enums\OrganizationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partenaire>
 */
class PartenaireFactory extends Factory
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
            'nom_retenu' => fake()->company(),
            'type' => fake()->randomElement(OrganizationType::cases()),
            'statut' => fake()->randomElement(OrganizationStatus::cases()),
            'entreprise' => fake()->company(),
            'siret' => fake()->numerify('#############'),
            'adresse' => fake()->streetAddress(),
            'code_postal' => fake()->postcode(),
            'ville' => fake()->city(),
            'departement' => fake()->department(),
            'region' => fake()->region(),
            'telephone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'site_web' => fake()->url(),
            'commercial_id' => \App\Models\User::factory(),
            'actif' => true,
        ];
    }
}
