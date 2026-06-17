<?php

namespace Database\Seeders;

use App\Models\FicheTemplate;
use App\Services\Aopia\FicheGenerationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class FicheTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->publierTemplates();

        $mapping = FicheGenerationService::mappingParDefaut();

        $templates = [
            [
                'type' => 'bleue',
                'nom' => 'Récap RDV pris',
                'description' => 'Fiche bleue — générée lorsqu\'un rendez-vous est confirmé avec un élu CSE. Contient toutes les informations de l\'entreprise, de l\'interlocuteur et du RDV.',
                'template_path' => 'fiche-templates/FICHE_BLEUE_RECAP_RDV_PRIS.docx',
                'placeholders' => $mapping,
                'statut_phoning_codes' => ['RDV'],
                'auto_generation' => true,
                'actif' => true,
            ],
            [
                'type' => 'jaune',
                'nom' => 'CSE pas intéressé — Rappel J+7',
                'description' => 'Fiche jaune — générée lorsque le CSE n\'est pas intéressé. Les coordonnées sont transmises au commercial pour un rappel à J+7.',
                'template_path' => 'fiche-templates/FICHE_JAUNE_CSE_PAS_INTERESSE_RAPPEL_A_FAIRE_J+7.docx',
                'placeholders' => $mapping,
                'statut_phoning_codes' => ['CSE-NI'],
                'auto_generation' => true,
                'actif' => true,
            ],
            [
                'type' => 'verte',
                'nom' => 'RDV à conclure',
                'description' => 'Fiche verte — générée lorsque l\'élu reste inaccessible après blocage standard ou entreprise sans CSE < 50 salariés. Coordonnées transmises au commercial pour relance.',
                'template_path' => 'fiche-templates/FICHE_VERTE_RDV_A_CONCLURE.docx',
                'placeholders' => $mapping,
                'statut_phoning_codes' => ['BLOC2', 'NCSE-50'],
                'auto_generation' => true,
                'actif' => true,
            ],
        ];

        foreach ($templates as $data) {
            FicheTemplate::updateOrCreate(
                ['type' => $data['type'], 'nom' => $data['nom']],
                $data
            );
        }
    }

    /**
     * Copie les templates Word depuis resources/stubs vers storage/app.
     */
    private function publierTemplates(): void
    {
        $source = resource_path('stubs/fiche-templates');
        $dest = 'fiche-templates';

        Storage::makeDirectory($dest);

        foreach (File::files($source) as $file) {
            $target = $dest.'/'.$file->getFilename();

            if (! Storage::exists($target)) {
                Storage::put($target, File::get($file->getPathname()));
            }
        }
    }
}
