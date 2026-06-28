<?php

namespace Database\Seeders;

use App\Models\WorkflowGroupe;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CseWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        WorkflowStep::truncate();
        WorkflowGroupe::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Création du workflow CSE v2...');

        // ── CAS 1: Appel non abouti ───────────────────────────────────────
        $cas1 = WorkflowGroupe::create([
            'model_type' => 'prospect',
            'code' => 'cas_1',
            'label' => 'Cas 1 — Appel non abouti',
            'ordre' => 1,
            'actif' => true,
        ]);

        $this->createStep($cas1, 1, 'Vérification du numéro', 'task', [
            'description' => 'Prise en charge par l\'équipe Nirina — tag NRP ou FAX posé à l\'appel',
            'tags' => ['NRP', 'FAX'],
        ]);

        $this->createStep($cas1, 2, 'Décision : numéro trouvé ?', 'condition', [
            'branches' => ['non_trouve', 'nouveau_numero'],
        ]);

        $this->createStep($cas1, 3, 'Supprimer la ligne', 'action', [
            'branch' => 'non_trouve',
            'tag' => 'SUPP',
            'description' => 'Retrait définitif du fichier de prospection',
        ]);

        $this->createStep($cas1, 4, 'Mettre à jour le fichier', 'action', [
            'branch' => 'nouveau_numero',
            'tag' => 'MAJ',
            'description' => 'Corriger le numéro dans la fiche de prospection',
        ]);

        // ── CAS 2: Élu CSE joint directement ─────────────────────────────────
        $cas2 = WorkflowGroupe::create([
            'model_type' => 'prospect',
            'code' => 'cas_2',
            'label' => 'Cas 2 — Élu CSE joint',
            'ordre' => 2,
            'actif' => true,
        ]);

        $this->createStep($cas2, 1, 'Argumentation', 'task', [
            'description' => 'Présenter les offres et services, proposer un rendez-vous avec le commercial',
        ]);

        $this->createStep($cas2, 2, 'Résultat de l\'échange', 'condition', [
            'branches' => ['non_interesse', 'rdv_accepte', 'rappel_demande'],
        ]);

        $this->createStep($cas2, 3, 'Fiche jaune → commercial', 'action', [
            'branch' => 'non_interesse',
            'tag' => 'CSE-NI',
            'description' => 'Renseigner : nom, prénom, téléphone, mail, fonction de l\'élu. Rappel commercial à J+7',
            'template' => 'jaune',
        ]);

        $this->createStep($cas2, 4, 'Confirmer et envoyer', 'action', [
            'branch' => 'rdv_accepte',
            'tag' => 'RDV',
            'description' => 'Mail de confirmation au prospect, invitation calendrier au commercial, fiche récap + enregistrement, équipes internes en copie',
            'template' => 'bleue',
        ]);

        $this->createStep($cas2, 5, 'Programmer le rappel', 'action', [
            'branch' => 'rappel_demande',
            'tag' => 'RAPL-ELU',
            'description' => 'L\'élu est disponible mais pas maintenant. Il donne lui-même un créneau précis. Signal positif — prioritaire.',
            'note_obligatoire' => 'date + heure + nom élu',
        ]);

        // ── CAS 3: Blocage au standard ───────────────────────────────────────
        $cas3 = WorkflowGroupe::create([
            'model_type' => 'prospect',
            'code' => 'cas_3',
            'label' => 'Cas 3 — Blocage au standard',
            'ordre' => 3,
            'actif' => true,
        ]);

        $this->createStep($cas3, 1, 'Obtenir coordonnées élu', 'task', [
            'description' => 'Demander : nom, prénom, mail, téléphone, disponibilités',
        ]);

        $this->createStep($cas3, 2, 'Réponse du standard', 'condition', [
            'branches' => ['creneau_propose', 'aucun_creneau'],
        ]);

        $this->createStep($cas3, 3, 'Programmer le rappel suggéré', 'action', [
            'branch' => 'creneau_propose',
            'tag' => 'RAPL-STD',
            'description' => 'Le standard indique quand rappeler. Signal neutre.',
            'note_obligatoire' => 'date + heure + nom du standard',
        ]);

        $this->createStep($cas3, 4, 'Collecter coords + envoyer mail', 'action', [
            'branch' => 'aucun_creneau',
            'tag' => 'BLOC',
            'description' => 'Mail de prise de contact avec présentation et demande de disponibilité',
        ]);

        $this->createStep($cas3, 5, 'Rappeler après 48h (BLOC uniquement)', 'condition', [
            'branches' => ['elu_joint', 'toujours_bloque'],
            'condition' => 'tag = BLOC',
        ]);

        $this->createStep($cas3, 6, 'Suivre procédure cas 2', 'action', [
            'branch' => 'elu_joint',
            'description' => 'Argumentation → RDV accepté / refus / rappel demandé',
        ]);

        $this->createStep($cas3, 7, 'Fiche verte → commercial', 'action', [
            'branch' => 'toujours_bloque',
            'tag' => 'BLOC2',
            'description' => 'Transmettre les coordonnées de l\'élu au commercial pour relance',
            'template' => 'verte',
        ]);

        // ── CAS 4: Pas de CSE dans l'entreprise ─────────────────────────────
        $cas4 = WorkflowGroupe::create([
            'model_type' => 'prospect',
            'code' => 'cas_4',
            'label' => 'Cas 4 — Pas de CSE',
            'ordre' => 4,
            'actif' => true,
        ]);

        $this->createStep($cas4, 1, 'Demander nombre de salariés', 'task', [
            'description' => 'Demander le nombre de salariés au standard',
        ]);

        $this->createStep($cas4, 2, 'Effectif entreprise', 'condition', [
            'branches' => ['moins_50', '50_plus'],
        ]);

        $this->createStep($cas4, 3, 'Contact personne formation', 'action', [
            'branch' => 'moins_50',
            'tag' => 'NCSE-50',
            'description' => 'Demander la personne qui gère les droits à la formation (délégué du personnel ou responsable actions sociales)',
        ]);

        $this->createStep($cas4, 4, 'Obtenir coordonnées contact', 'task', [
            'branch' => 'moins_50',
            'description' => 'Nom, prénom, mail, téléphone, fonction',
        ]);

        $this->createStep($cas4, 5, 'Envoi mail + fiche verte', 'action', [
            'branch' => 'moins_50',
            'tag' => 'NCSE-50',
            'description' => 'Envoi mail de contact, fiche verte au commercial. Bien indiquer : pas de CSE dans cette entreprise',
            'template' => 'verte',
        ]);

        $this->createStep($cas4, 6, 'Reformuler la demande', 'action', [
            'branch' => '50_plus',
            'tag' => 'NCSE+50',
            'description' => 'Insister auprès du standard pour obtenir un élu CSE (l\'obligation légale s\'applique)',
        ]);

        $this->createStep($cas4, 7, 'Élu CSE trouvé ?', 'condition', [
            'branch' => '50_plus',
            'branches' => ['elu_trouve', 'toujours_sans_elu'],
        ]);

        $this->createStep($cas4, 8, 'Reprendre cas 2 ou 3', 'action', [
            'branch' => 'elu_trouve',
            'description' => 'Reprendre le cas 2 ou le cas 3 selon la situation',
        ]);

        $this->createStep($cas4, 9, 'Reprendre procédure 4.1', 'action', [
            'branch' => 'toujours_sans_elu',
            'description' => 'Reprendre la procédure pour moins de 50 salariés',
        ]);

        // ── CAS PARTICULIER: CSE centralisé ─────────────────────────────────
        $casParticulier = WorkflowGroupe::create([
            'model_type' => 'prospect',
            'code' => 'cas_particulier',
            'label' => 'Cas particulier — CSE centralisé',
            'ordre' => 5,
            'actif' => true,
        ]);

        $this->createStep($casParticulier, 1, 'Obtenir coordonnées CSE centralisé', 'task', [
            'description' => 'Nom, prénom, mail, téléphone, département et ville du site concerné',
        ]);

        $this->createStep($casParticulier, 2, 'Département dans zone ?', 'condition', [
            'branches' => ['dans_zone', 'hors_zone'],
        ]);

        $this->createStep($casParticulier, 3, 'Planifier l\'appel', 'action', [
            'branch' => 'dans_zone',
            'tag' => 'CSE-ZONE',
            'description' => 'Programmer un appel vers ce contact → passer au cas 2',
        ]);

        $this->createStep($casParticulier, 4, 'Envoi fiche → Bruno', 'action', [
            'branch' => 'hors_zone',
            'tag' => 'CSE-HZ',
            'description' => 'Transmettre les coordonnées complètes de l\'élu à Bruno pour traitement',
        ]);

        $this->command->info('Workflow CSE v2 créé avec succès.');
        $this->command->info('Groupes: 5, Étapes: 28');
    }

    private function createStep(WorkflowGroupe $groupe, int $ordre, string $label, string $type, array $config = []): WorkflowStep
    {
        return WorkflowStep::create([
            'workflow_groupe_id' => $groupe->id,
            'label' => $label,
            'code' => strtoupper(str_replace([' ', '—'], '_', $label)),
            'type' => $type,
            'ordre' => $ordre,
            'config' => $config,
            'actif' => true,
        ]);
    }
}
