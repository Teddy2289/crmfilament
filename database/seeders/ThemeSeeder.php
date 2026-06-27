<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // NS Conseil Theme
        Theme::create([
            'name' => 'ns-conseil-default',
            'label' => 'NS Conseil - Défaut',
            'panel' => 'ns-conseil',
            'is_default' => true,
            'is_active' => true,
            'primary_color' => 'blue',
            'success_color' => 'emerald',
            'warning_color' => 'amber',
            'danger_color' => 'rose',
            'info_color' => 'sky',
            'gray_color' => 'slate',
            'brand_name' => 'NS CONSEIL — CRM Partenaires',
        ]);

        // Admin Theme
        Theme::create([
            'name' => 'admin-default',
            'label' => 'Admin - Défaut',
            'panel' => 'admin',
            'is_default' => true,
            'is_active' => true,
            'primary_color' => 'indigo',
            'success_color' => 'emerald',
            'warning_color' => 'amber',
            'danger_color' => 'rose',
            'info_color' => 'sky',
            'gray_color' => 'slate',
        ]);

        // Super Admin Theme
        Theme::create([
            'name' => 'super-admin-default',
            'label' => 'Super Admin - Défaut',
            'panel' => 'super-admin',
            'is_default' => true,
            'is_active' => true,
            'primary_color' => 'purple',
            'success_color' => 'emerald',
            'warning_color' => 'amber',
            'danger_color' => 'rose',
            'info_color' => 'cyan',
            'gray_color' => 'zinc',
            'brand_name' => '⚙️ Super Administration',
        ]);

        // Allopro Theme
        Theme::create([
            'name' => 'allopro-default',
            'label' => 'Allopro - Défaut',
            'panel' => 'allopro',
            'is_default' => true,
            'is_active' => true,
            'primary_color' => 'orange',
            'success_color' => 'emerald',
            'warning_color' => 'amber',
            'danger_color' => 'rose',
            'info_color' => 'sky',
            'gray_color' => 'slate',
            'brand_name' => 'AlloPro 24/24 — Centre de Contact',
        ]);
    }
}
