<?php

namespace Database\Factories;

use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Theme>
 */
class ThemeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'label' => fake()->words(2, true),
            'panel' => fake()->randomElement(['ns-conseil', 'admin', 'super-admin', 'allopro']),
            'is_default' => false,
            'is_active' => true,
            'primary_color' => fake()->randomElement(['blue', 'indigo', 'purple', 'orange', 'emerald', 'rose']),
            'success_color' => 'emerald',
            'warning_color' => 'amber',
            'danger_color' => 'rose',
            'info_color' => 'sky',
            'gray_color' => 'slate',
            'primary_color_dark' => null,
            'success_color_dark' => null,
            'warning_color_dark' => null,
            'danger_color_dark' => null,
            'info_color_dark' => null,
            'gray_color_dark' => null,
            'brand_name' => fake()->company(),
            'brand_logo_path' => null,
            'favicon_path' => null,
            'custom_css' => null,
            'metadata' => null,
        ];
    }
}
