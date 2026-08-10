<?php

namespace Database\Factories;

use App\Models\FieldPermission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FieldPermission>
 */
class FieldPermissionFactory extends Factory
{
    protected $model = FieldPermission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role'         => fake()->randomElement(['commercial', 'teleprospecteur', 'operateur_n1', 'administrateur']),
            'resource'     => fake()->randomElement(['prospects', 'partenaires', 'clients']),
            'field_name'   => fake()->randomElement(['nom', 'siret', 'telephone', 'email', 'ville', 'departement']),
            'visible_list' => true,
            'visible_view' => true,
            'visible_edit' => true,
            'read_only'    => false,
        ];
    }
}
