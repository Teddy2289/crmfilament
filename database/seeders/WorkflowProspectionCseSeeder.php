<?php

namespace Database\Seeders;

use App\Models\WorkflowGroupe;
use App\Models\WorkflowStep;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkflowProspectionCseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Supprimer le workflow s'il existe déjà
        $existing = WorkflowGroupe::where('code', 'prospection_cse_v2')->first();
        if ($existing) {
            $existing->workflowSteps()->delete();
            $existing->delete();
        }

        $workflow = WorkflowGroupe::create([
            'model_type' => 'prospect',
            'code' => 'prospection_cse_v2',
            'label' => 'Prospection CSE - Version 2',
            'ordre' => 1,
            'actif' => true,
        ]);

        // CAS 1: Appel non abouti
        $step1 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'CAS 1: Appel non abouti',
            'code' => 'cse_v2_cas_1_appel_non_abouti',
            'type' => 'task',
            'ordre' => 1,
            'actif' => true,
            'config' => [
                'description' => 'Personne non jointe, numéro incorrect ou sans réponse',
                'x' => 50,
                'y' => 50,
            ],
        ]);

        $step1_1 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'Vérification du numéro',
            'code' => 'cse_v2_verification_numero',
            'type' => 'task',
            'ordre' => 2,
            'actif' => true,
            'config' => [
                'description' => 'Prise en charge par l\'équipe Nirina — tag NRP ou FAX posé à l\'appel',
                'x' => 250,
                'y' => 50,
            ],
        ]);

        $step1_2 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'Aucun numéro trouvé',
            'code' => 'cse_v2_aucun_numero_trouve',
            'type' => 'action',
            'ordre' => 3,
            'actif' => true,
            'parent_step_id' => $step1_1->id,
            'condition_label' => '✗ Non',
            'config' => [
                'description' => 'Supprimer la ligne - Retrait définitif du fichier de prospection',
                'tag' => 'SUPP',
                'x' => 450,
                'y' => 30,
            ],
        ]);

        $step1_3 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'Nouveau numéro identifié',
            'code' => 'cse_v2_nouveau_numero_identifie',
            'type' => 'action',
            'ordre' => 4,
            'actif' => true,
            'parent_step_id' => $step1_1->id,
            'condition_label' => '✓ Oui',
            'config' => [
                'description' => 'Mettre à jour le fichier - Corriger le numéro dans la fiche de prospection',
                'tag' => 'MAJ',
                'x' => 450,
                'y' => 100,
            ],
        ]);

        // CAS 2: Élu CSE joint directement
        $step2 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'CAS 2: Élu CSE joint directement',
            'code' => 'cse_v2_cas_2_elu_cse_joint',
            'type' => 'task',
            'ordre' => 5,
            'actif' => true,
            'config' => [
                'description' => 'Le standard transfère le secrétaire, trésorier ou élu CSE',
                'x' => 650,
                'y' => 50,
            ],
        ]);

        $step2_1 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'Argumentation',
            'code' => 'cse_v2_argumentation',
            'type' => 'task',
            'ordre' => 6,
            'actif' => true,
            'config' => [
                'description' => 'Présenter les offres et services, proposer un rendez-vous avec le commercial',
                'x' => 850,
                'y' => 50,
            ],
        ]);

        $step2_2 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'CSE non intéressé',
            'code' => 'cse_v2_cse_non_interesse',
            'type' => 'action',
            'ordre' => 7,
            'actif' => true,
            'parent_step_id' => $step2_1->id,
            'condition_label' => '✗ Non',
            'config' => [
                'description' => 'Fiche jaune → commercial - Renseigner nom, prénom, téléphone, mail, fonction de l\'élu. Rappel commercial à J+7',
                'tag' => 'CSE-NI',
                'x' => 1050,
                'y' => 20,
            ],
        ]);

        $step2_3 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'RDV accepté',
            'code' => 'cse_v2_rdv_accepte',
            'type' => 'action',
            'ordre' => 8,
            'actif' => true,
            'parent_step_id' => $step2_1->id,
            'condition_label' => '✓ Oui',
            'config' => [
                'description' => 'Confirmer et envoyer - Mail de confirmation, invitation calendrier, fiche récap, équipes en copie',
                'tag' => 'RDV',
                'x' => 1050,
                'y' => 80,
            ],
        ]);

        $step2_4 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'Rappel demandé par l\'élu',
            'code' => 'cse_v2_rappel_demande_elu',
            'type' => 'action',
            'ordre' => 9,
            'actif' => true,
            'parent_step_id' => $step2_1->id,
            'condition_label' => '⏱ Rappel',
            'config' => [
                'description' => 'Programmer le rappel - L\'élu donne un créneau précis. Signal positif — prioritaire',
                'tag' => 'RAPL-ELU',
                'x' => 1050,
                'y' => 140,
            ],
        ]);

        // CAS 3: Blocage au standard
        $step3 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'CAS 3: Blocage au standard',
            'code' => 'cse_v2_cas_3_blocage_standard',
            'type' => 'task',
            'ordre' => 10,
            'actif' => true,
            'config' => [
                'description' => 'Le standard refuse de transférer ou ne peut pas joindre l\'élu',
                'x' => 50,
                'y' => 200,
            ],
        ]);

        $step3_1 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'Obtenir coordonnées élu',
            'code' => 'cse_v2_obtenir_coords_elu',
            'type' => 'task',
            'ordre' => 11,
            'actif' => true,
            'config' => [
                'description' => 'Obtenir les coordonnées de l\'élu auprès du standard - nom, prénom, mail, téléphone, disponibilités',
                'x' => 250,
                'y' => 200,
            ],
        ]);

        $step3_2 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'Standard propose créneau',
            'code' => 'cse_v2_standard_propose_creneau',
            'type' => 'action',
            'ordre' => 12,
            'actif' => true,
            'parent_step_id' => $step3_1->id,
            'condition_label' => '⏱ Créneau',
            'config' => [
                'description' => 'Programmer le rappel suggéré - Signal neutre. Au rappel: si élu joint → cas 2, si bloqué → BLOC',
                'tag' => 'RAPL-STD',
                'x' => 450,
                'y' => 180,
            ],
        ]);

        $step3_3 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'Aucun créneau donné',
            'code' => 'cse_v2_aucun_creneau',
            'type' => 'action',
            'ordre' => 13,
            'actif' => true,
            'parent_step_id' => $step3_1->id,
            'condition_label' => '✗ Aucun',
            'config' => [
                'description' => 'Collecter coords + envoyer mail de prise de contact',
                'tag' => 'BLOC',
                'x' => 450,
                'y' => 240,
            ],
        ]);

        // CAS 4: Pas de CSE dans l'entreprise
        $step4 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'CAS 4: Pas de CSE dans l\'entreprise',
            'code' => 'cse_v2_cas_4_pas_cse',
            'type' => 'task',
            'ordre' => 14,
            'actif' => true,
            'config' => [
                'description' => 'Le standard indique qu\'il n\'existe pas de CSE',
                'x' => 650,
                'y' => 200,
            ],
        ]);

        $step4_1 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'Demander nombre salariés',
            'code' => 'cse_v2_demander_salaries',
            'type' => 'task',
            'ordre' => 15,
            'actif' => true,
            'config' => [
                'description' => 'Demander le nombre de salariés au standard',
                'x' => 850,
                'y' => 200,
            ],
        ]);

        $step4_2 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'Moins de 50 salariés',
            'code' => 'cse_v2_moins_50_salaries',
            'type' => 'action',
            'ordre' => 16,
            'actif' => true,
            'parent_step_id' => $step4_1->id,
            'condition_label' => '< 50',
            'config' => [
                'description' => 'Demander personne qui gère droits formation - Envoi mail + fiche verte au commercial',
                'tag' => 'NCSE-50',
                'x' => 1050,
                'y' => 180,
            ],
        ]);

        $step4_3 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => '50 salariés ou plus',
            'code' => 'cse_v2_plus_50_salaries',
            'type' => 'action',
            'ordre' => 17,
            'actif' => true,
            'parent_step_id' => $step4_1->id,
            'condition_label' => '≥ 50',
            'config' => [
                'description' => 'Reformuler la demande - Insister pour obtenir un élu CSE',
                'tag' => 'NCSE+50',
                'x' => 1050,
                'y' => 240,
            ],
        ]);

        // CAS PARTICULIER: CSE centralisé
        $step5 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'CAS PARTICULIER: CSE centralisé',
            'code' => 'cse_v2_cas_particulier_cse_centralise',
            'type' => 'task',
            'ordre' => 18,
            'actif' => true,
            'config' => [
                'description' => 'Le standard indique que le CSE est rattaché à un autre site',
                'x' => 50,
                'y' => 350,
            ],
        ]);

        $step5_1 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'Obtenir coords CSE centralisé',
            'code' => 'cse_v2_obtenir_coords_cse_centralise',
            'type' => 'task',
            'ordre' => 19,
            'actif' => true,
            'config' => [
                'description' => 'Obtenir coordonnées complètes: nom, prénom, mail, téléphone, département et ville du site',
                'x' => 250,
                'y' => 350,
            ],
        ]);

        $step5_2 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'Département dans zone',
            'code' => 'cse_v2_departement_zone',
            'type' => 'action',
            'ordre' => 20,
            'actif' => true,
            'parent_step_id' => $step5_1->id,
            'condition_label' => '✓ Zone',
            'config' => [
                'description' => 'Planifier l\'appel vers ce contact → passer au cas 2',
                'tag' => 'CSE-ZONE',
                'x' => 450,
                'y' => 330,
            ],
        ]);

        $step5_3 = WorkflowStep::create([
            'workflow_groupe_id' => $workflow->id,
            'label' => 'Département hors zone',
            'code' => 'cse_v2_departement_hors_zone',
            'type' => 'action',
            'ordre' => 21,
            'actif' => true,
            'parent_step_id' => $step5_1->id,
            'condition_label' => '✗ Hors zone',
            'config' => [
                'description' => 'Envoi fiche → Bruno pour traitement',
                'tag' => 'CSE-HZ',
                'x' => 450,
                'y' => 390,
            ],
        ]);

        $this->command->info('Workflow Prospection CSE v2 créé avec succès.');
    }
}
