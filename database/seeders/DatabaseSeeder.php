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
            // 1. Permissions, rôles et profils CRM (RolesAndPermissionsSeeder
            //    appelle déjà CrmProfileSeeder en interne)
            RolesAndPermissionsSeeder::class,

            // 2. Utilisateurs (nécessite les rôles ci-dessus)
            UsersSeeder::class,

            // 3. Paramètres applicatifs et thème
            CrmSettingSeeder::class,
            ThemeSeeder::class,

            // 4. Référentiels : groupes de workflow, pipeline, statuts
            //    (StatutPhoningSeeder dépend de WorkflowGroupeSeeder)
            WorkflowGroupeSeeder::class,
            PipelineStatutSeeder::class,
            StatutPhoningSeeder::class,
            CseWorkflowSeeder::class,

            // 5. Modèles de fiches et emails
            FicheTemplateSeeder::class,
            TemplateFicheSeeder::class,
            EmailTemplateSeeder::class,

            // 6. Contenu de démonstration
            //    (DemoCustomFieldValuesSeeder dépend de TestCustomFieldsSeeder)
            ArtisanSeeder::class,
            DocumentKnowledgeSeeder::class,
            TestCustomFieldsSeeder::class,
            DemoCustomFieldValuesSeeder::class,
        ]);
    }
}
