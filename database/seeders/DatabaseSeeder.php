<?php

// ╔════════════════════════════════════════════════════════════════════╗
// ║  database/seeders/DatabaseSeeder.php                            ║
// ╚═══════════════════════════════════════════════════════════════════╝

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            UsersSeeder::class,
            CrmSettingSeeder::class,
            ThemeSeeder::class,
            WorkflowGroupeSeeder::class,
            PipelineStatutSeeder::class,
            StatutPhoningSeeder::class,
            DiagnosticSeeder::class,
            FixAlexSeeder::class,
            AlloproUsersSeeder::class,
            ArtisanSeeder::class,
            FicheTemplateSeeder::class,
            TemplateFicheSeeder::class,
            EmailTemplateSeeder::class,
            DocumentKnowledgeSeeder::class,
            DemoCustomFieldValuesSeeder::class,
            
        ]);
    }
}
