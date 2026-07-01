<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'name' => 'ns-conseil-default',
                'label' => 'NS Conseil - Filament',
                'panel' => 'ns-conseil',
                'primary_color' => 'blue',
                'brand_name' => 'NS CONSEIL - CRM Partenaires',
            ],
            [
                'name' => 'admin-default',
                'label' => 'Admin - Filament',
                'panel' => 'admin',
                'primary_color' => 'indigo',
            ],
            [
                'name' => 'super-admin-default',
                'label' => 'Super administrateur - Filament',
                'panel' => 'super-admin',
                'primary_color' => 'purple',
                'info_color' => 'cyan',
                'gray_color' => 'zinc',
                'brand_name' => 'Super administration',
            ],
            [
                'name' => 'allopro-default',
                'label' => 'Allopro - Filament',
                'panel' => 'allopro',
                'primary_color' => 'orange',
                'brand_name' => 'AlloPro 24/24 - Centre de Contact',
            ],
        ];

        foreach ($defaults as $theme) {
            Theme::updateOrCreate(
                ['name' => $theme['name']],
                [
                    'label' => $theme['label'],
                    'panel' => $theme['panel'],
                    'is_default' => true,
                    'is_active' => true,
                    'primary_color' => $theme['primary_color'],
                    'success_color' => 'emerald',
                    'warning_color' => 'amber',
                    'danger_color' => 'rose',
                    'info_color' => $theme['info_color'] ?? 'sky',
                    'gray_color' => $theme['gray_color'] ?? 'slate',
                    'brand_name' => $theme['brand_name'] ?? null,
                    'metadata' => [
                        'chrome' => Theme::CHROME_FILAMENT,
                        'apply_colors' => false,
                    ],
                ],
            );
        }

        foreach ($defaults as $theme) {
            Theme::updateOrCreate(
                ['name' => $theme['panel'] . '-espo'],
                [
                    'label' => $theme['label'] . ' EspoCRM',
                    'panel' => $theme['panel'],
                    'is_default' => false,
                    'is_active' => true,
                    'primary_color' => $theme['primary_color'],
                    'success_color' => 'emerald',
                    'warning_color' => 'amber',
                    'danger_color' => 'rose',
                    'info_color' => $theme['info_color'] ?? 'sky',
                    'gray_color' => $theme['gray_color'] ?? 'slate',
                    'brand_name' => $theme['brand_name'] ?? null,
                    'metadata' => [
                        'chrome' => Theme::CHROME_ESPO,
                        'apply_colors' => true,
                    ],
                ],
            );
        }
    }
}
