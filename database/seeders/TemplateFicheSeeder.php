<?php

namespace Database\Seeders;

use App\Models\TemplateFiche;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class TemplateFicheSeeder extends Seeder
{
    public function run(): void
    {
        $this->publierTemplates();

        $templates = [
            [
                'code' => 'BLEUE_RDV_PRIS',
                'nom' => 'Récap RDV pris',
                'type' => 'bleue',
                'description' => 'Fiche bleue — générée lorsqu\'un rendez-vous est confirmé avec un élu CSE. Contient toutes les informations de l\'entreprise, de l\'interlocuteur et du RDV.',
                'fichier_path' => 'fiche-templates/FICHE_BLEUE_RECAP_RDV_PRIS.docx',
                'variables' => [
                    '{{NOM_CLIENT}}',
                    '{{TELEPHONE_CLIENT}}',
                    '{{EMAIL_CLIENT}}',
                    '{{ADRESSE_CLIENT}}',
                    '{{CODE_POSTAL_CLIENT}}',
                    '{{VILLE_CLIENT}}',
                    '{{NOM_INTERLOCUTEUR}}',
                    '{{POSTE_INTERLOCUTEUR}}',
                    '{{TELEPHONE_INTERLOCUTEUR}}',
                    '{{EMAIL_INTERLOCUTEUR}}',
                    '{{DATE_RDV}}',
                    '{{HEURE_RDV}}',
                    '{{LIEN_VISIO}}',
                    '{{COMMENTAIRES}}'
                ],
                'actif' => true,
            ],
            [
                'code' => 'JAUNE_CSE_PAS_INTERESSE_J7',
                'nom' => 'CSE pas intéressé — Rappel J+7',
                'type' => 'jaune',
                'description' => 'Fiche jaune — générée lorsque le CSE n\'est pas intéressé. Les coordonnées sont transmises au commercial pour un rappel à J+7.',
                'fichier_path' => 'fiche-templates/FICHE_JAUNE_CSE_PAS_INTERESSE_RAPPEL_A_FAIRE_J+7.docx',
                'variables' => [
                    '{{NOM_CLIENT}}',
                    '{{TELEPHONE_CLIENT}}',
                    '{{EMAIL_CLIENT}}',
                    '{{ADRESSE_CLIENT}}',
                    '{{CODE_POSTAL_CLIENT}}',
                    '{{VILLE_CLIENT}}',
                    '{{NOM_INTERLOCUTEUR}}',
                    '{{POSTE_INTERLOCUTEUR}}',
                    '{{TELEPHONE_INTERLOCUTEUR}}',
                    '{{EMAIL_INTERLOCUTEUR}}',
                    '{{DATE_REFUS}}',
                    '{{MOTIF_REFUS}}',
                    '{{COMMENTAIRES}}'
                ],
                'actif' => true,
            ],
            [
                'code' => 'VERTE_RDV_A_CONCLURE',
                'nom' => 'RDV à conclure',
                'type' => 'verte',
                'description' => 'Fiche verte — générée lorsque l\'élu reste inaccessible après blocage standard ou entreprise sans CSE < 50 salariés. Coordonnées transmises au commercial pour relance.',
                'fichier_path' => 'fiche-templates/FICHE_VERTE_RDV_A_CONCLURE.docx',
                'variables' => [
                    '{{NOM_CLIENT}}',
                    '{{TELEPHONE_CLIENT}}',
                    '{{EMAIL_CLIENT}}',
                    '{{ADRESSE_CLIENT}}',
                    '{{CODE_POSTAL_CLIENT}}',
                    '{{VILLE_CLIENT}}',
                    '{{NOM_INTERLOCUTEUR}}',
                    '{{POSTE_INTERLOCUTEUR}}',
                    '{{TELEPHONE_INTERLOCUTEUR}}',
                    '{{EMAIL_INTERLOCUTEUR}}',
                    '{{DATE_TENTATIVE}}',
                    '{{NOMBRE_TENTATIVES}}',
                    '{{COMMENTAIRES}}'
                ],
                'actif' => true,
            ],
        ];

        foreach ($templates as $data) {
            TemplateFiche::updateOrCreate(
                ['code' => $data['code']],
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