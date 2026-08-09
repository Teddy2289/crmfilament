<?php

namespace Database\Seeders;

use App\Models\WorkflowGroupe;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;

class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        // Workflow pour les prospects - utiliser le groupe existant ou créer un nouveau
        $prospectGroupe = WorkflowGroupe::firstOrCreate(
            ['model_type' => 'prospect', 'code' => 'validation_standard'],
            [
                'label' => 'Validation Prospect Standard',
                'ordre' => 10,
                'actif' => true,
            ]
        );

        // Créer les étapes si elles n'existent pas déjà
        if (!WorkflowStep::where('workflow_groupe_id', $prospectGroupe->id)->exists()) {
            WorkflowStep::create([
                'workflow_groupe_id' => $prospectGroupe->id,
                'label' => 'Vérification initiale',
                'code' => 'verification_initiale',
                'type' => 'task',
                'ordre' => 0,
                'config' => null,
                'actif' => true,
                'est_final' => false,
            ]);

            WorkflowStep::create([
                'workflow_groupe_id' => $prospectGroupe->id,
                'label' => 'Validation commerciale',
                'code' => 'validation_commerciale',
                'type' => 'approval',
                'ordre' => 1,
                'config' => null,
                'actif' => true,
                'est_final' => false,
            ]);

            WorkflowStep::create([
                'workflow_groupe_id' => $prospectGroupe->id,
                'label' => 'Validation finale',
                'code' => 'validation_finale',
                'type' => 'approval',
                'ordre' => 2,
                'config' => null,
                'actif' => true,
                'est_final' => true,
            ]);
        }

        // Workflow pour les partenaires
        $partenaireGroupe = WorkflowGroupe::firstOrCreate(
            ['model_type' => 'partenaire', 'code' => 'validation_standard'],
            [
                'label' => 'Validation Partenaire Standard',
                'ordre' => 10,
                'actif' => true,
            ]
        );

        if (!WorkflowStep::where('workflow_groupe_id', $partenaireGroupe->id)->exists()) {
            WorkflowStep::create([
                'workflow_groupe_id' => $partenaireGroupe->id,
                'label' => 'Vérification documents',
                'code' => 'verification_documents',
                'type' => 'task',
                'ordre' => 0,
                'config' => null,
                'actif' => true,
                'est_final' => false,
            ]);

            WorkflowStep::create([
                'workflow_groupe_id' => $partenaireGroupe->id,
                'label' => 'Validation direction',
                'code' => 'validation_direction',
                'type' => 'approval',
                'ordre' => 1,
                'config' => null,
                'actif' => true,
                'est_final' => true,
            ]);
        }

        // Workflow pour les dossiers de formation
        $dossierGroupe = WorkflowGroupe::firstOrCreate(
            ['model_type' => 'dossier_formation', 'code' => 'validation_standard'],
            [
                'label' => 'Validation Dossier Formation Standard',
                'ordre' => 10,
                'actif' => true,
            ]
        );

        if (!WorkflowStep::where('workflow_groupe_id', $dossierGroupe->id)->exists()) {
            WorkflowStep::create([
                'workflow_groupe_id' => $dossierGroupe->id,
                'label' => 'Vérification inscription',
                'code' => 'verification_inscription',
                'type' => 'task',
                'ordre' => 0,
                'config' => null,
                'actif' => true,
                'est_final' => false,
            ]);

            WorkflowStep::create([
                'workflow_groupe_id' => $dossierGroupe->id,
                'label' => 'Validation pédagogique',
                'code' => 'validation_pedagogique',
                'type' => 'approval',
                'ordre' => 1,
                'config' => null,
                'actif' => true,
                'est_final' => false,
            ]);

            WorkflowStep::create([
                'workflow_groupe_id' => $dossierGroupe->id,
                'label' => 'Validation administrative',
                'code' => 'validation_administrative',
                'type' => 'approval',
                'ordre' => 2,
                'config' => null,
                'actif' => true,
                'est_final' => true,
            ]);
        }
    }
}
