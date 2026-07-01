<?php

namespace Database\Seeders;

use App\Models\WorkflowGroupe;
use App\Models\WorkflowStep;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        // Créer un parcours de test
        $workflow = WorkflowGroupe::firstOrCreate(
            [
                'model_type' => 'App\Models\Prospect',
                'code' => 'prospection_cse_test',
            ],
            [
                'label' => 'Parcours de prospection CSE - Test',
                'ordre' => 1,
                'actif' => true,
            ]
        );

        // Supprimer les étapes existantes pour ce parcours
        WorkflowStep::where('workflow_groupe_id', $workflow->id)->delete();

        // Cas 1: Appel non abouti
        $step1 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'Vérification du numéro',
            'code' => 'verification_numero_test',
            'type' => 'task',
            'ordre' => 1,
            'actif' => true,
            'config' => [
                'case_id' => 'case_1',
                'case_title' => 'Appel non abouti',
                'case_subtitle' => 'Personne non jointe, numéro incorrect ou sans réponse',
                'description' => 'Prise en charge par l\'équipe Nirina — tag NRP ou FAX posé à l\'appel',
                'branches' => [
                    [
                        'type' => 'no',
                        'label' => '✗ Aucun numéro trouvé',
                        'content' => 'Supprimer la ligne',
                        'detail' => 'Retrait définitif du fichier de prospection',
                        'tag' => 'SUPP',
                        'tagColor' => 'red',
                    ],
                    [
                        'type' => 'yes',
                        'label' => '✓ Nouveau numéro identifié',
                        'content' => 'Mettre à jour le fichier',
                        'detail' => 'Corriger le numéro dans la fiche de prospection',
                        'tag' => 'MAJ',
                        'tagColor' => 'teal',
                    ],
                ],
            ],
        ]);

        // Cas 2: Élu CSE joint
        $step2 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'Argumentation',
            'code' => 'argumentation_test',
            'type' => 'condition',
            'ordre' => 2,
            'actif' => true,
            'config' => [
                'case_id' => 'case_2',
                'case_title' => 'Élu CSE joint directement',
                'case_subtitle' => 'Le standard transfère le secrétaire, trésorier ou élu CSE',
                'description' => 'Présenter les offres et services, proposer un rendez-vous avec le commercial',
                'branches' => [
                    [
                        'type' => 'no',
                        'label' => '✗ CSE non intéressé',
                        'content' => 'Fiche jaune → commercial',
                        'detail' => 'Renseigner : nom, prénom, téléphone, mail, fonction de l\'élu. Rappel commercial à J+7',
                        'tag' => 'CSE-NI',
                        'tagColor' => 'amber',
                    ],
                    [
                        'type' => 'yes',
                        'label' => '✓ RDV accepté',
                        'content' => 'Confirmer et envoyer',
                        'detail' => '→ Mail de confirmation au prospect → Invitation calendrier au commercial → Fiche récap + enregistrement → Équipes internes en copie',
                        'tag' => 'RDV',
                        'tagColor' => 'green',
                    ],
                    [
                        'type' => 'mint',
                        'label' => '⏱ Rappel demandé par l\'élu',
                        'content' => 'Programmer le rappel',
                        'detail' => 'L\'élu est disponible mais pas maintenant. Il donne lui-même un créneau précis. Signal positif — prioritaire.',
                        'tag' => 'RAPL-ELU',
                        'tagColor' => 'mint',
                    ],
                ],
            ],
        ]);

        // Cas 3: Blocage au standard
        $step3 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'Obtenir les coordonnées de l\'élu',
            'code' => 'obtenir_coords_test',
            'type' => 'action',
            'ordre' => 3,
            'actif' => true,
            'config' => [
                'case_id' => 'case_3',
                'case_title' => 'Blocage au standard',
                'case_subtitle' => 'Le standard refuse de transférer ou ne peut pas joindre l\'élu',
                'description' => 'Demander : nom, prénom, mail, téléphone, disponibilités',
                'branches' => [
                    [
                        'type' => 'mint',
                        'label' => '⏱ Standard propose un créneau',
                        'content' => 'Programmer le rappel suggéré',
                        'detail' => 'Le standard indique quand rappeler. Ce n\'est pas l\'élu lui-même qui demande. Signal neutre.',
                        'tag' => 'RAPL-STD',
                        'tagColor' => 'mint',
                    ],
                    [
                        'type' => 'no',
                        'label' => '✗ Aucun créneau donné',
                        'content' => 'Collecter coords + envoyer mail',
                        'detail' => 'Mail de prise de contact avec présentation et demande de disponibilité',
                        'tag' => 'BLOC',
                        'tagColor' => 'coral',
                    ],
                ],
            ],
        ]);

        $this->command->info('Parcours de test créé avec succès.');
        $this->command->info('ID du parcours: ' . $workflow->id);
    }
}
