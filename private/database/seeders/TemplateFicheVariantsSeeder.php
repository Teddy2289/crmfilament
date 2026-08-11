<?php

namespace Database\Seeders;

use App\Models\TemplateFiche;
use Illuminate\Database\Seeder;

class TemplateFicheVariantsSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // ── Variantes Fiches Bleues ─────────────────────────────────────
            [
                'code' => 'BLEUE_RDV_PRIS_V2',
                'nom' => 'Récap RDV pris - Version Simplifiée',
                'type' => 'bleue',
                'description' => 'Version simplifiée de la fiche bleue avec moins de champs pour les RDV rapides.',
                'fichier_path' => 'fiche-templates/FICHE_BLEUE_RECAP_RDV_PRIS_V2.docx',
                'variables' => [
                    '{{NOM_CLIENT}}',
                    '{{TELEPHONE_CLIENT}}',
                    '{{NOM_INTERLOCUTEUR}}',
                    '{{DATE_RDV}}',
                    '{{HEURE_RDV}}',
                ],
                'actif' => true,
            ],
            [
                'code' => 'BLEUE_RDV_VISIO',
                'nom' => 'Récap RDV Visio',
                'type' => 'bleue',
                'description' => 'Fiche bleue spécifique pour les rendez-vous en visioconférence avec lien de connexion.',
                'fichier_path' => 'fiche-templates/FICHE_BLEUE_RDV_VISIO.docx',
                'variables' => [
                    '{{NOM_CLIENT}}',
                    '{{EMAIL_CLIENT}}',
                    '{{NOM_INTERLOCUTEUR}}',
                    '{{EMAIL_INTERLOCUTEUR}}',
                    '{{DATE_RDV}}',
                    '{{HEURE_RDV}}',
                    '{{LIEN_VISIO}}',
                    '{{ID_VISIO}}',
                    '{{MOT_DE_PASSE_VISIO}}',
                ],
                'actif' => true,
            ],
            [
                'code' => 'BLEUE_RDV_PHYSIQUE',
                'nom' => 'Récap RDV Physique',
                'type' => 'bleue',
                'description' => 'Fiche bleue pour les rendez-vous physiques avec informations de localisation.',
                'fichier_path' => 'fiche-templates/FICHE_BLEUE_RDV_PHYSIQUE.docx',
                'variables' => [
                    '{{NOM_CLIENT}}',
                    '{{ADRESSE_CLIENT}}',
                    '{{VILLE_CLIENT}}',
                    '{{NOM_INTERLOCUTEUR}}',
                    '{{DATE_RDV}}',
                    '{{HEURE_RDV}}',
                    '{{LIEU_RDV}}',
                    '{{INSTRUCTIONS_ACCES}}',
                ],
                'actif' => true,
            ],

            // ── Variantes Fiches Jaunes ────────────────────────────────────
            [
                'code' => 'JAUNE_CSE_PAS_INTERESSE_J14',
                'nom' => 'CSE pas intéressé — Rappel J+14',
                'type' => 'jaune',
                'description' => 'Fiche jaune pour rappel à J+14 au lieu de J+7 pour les prospects froids.',
                'fichier_path' => 'fiche-templates/FICHE_JAUNE_CSE_PAS_INTERESSE_RAPPEL_J+14.docx',
                'variables' => [
                    '{{NOM_CLIENT}}',
                    '{{TELEPHONE_CLIENT}}',
                    '{{EMAIL_CLIENT}}',
                    '{{NOM_INTERLOCUTEUR}}',
                    '{{DATE_REFUS}}',
                    '{{MOTIF_REFUS}}',
                    '{{DATE_RAPPEL_PREVU}}',
                ],
                'actif' => true,
            ],
            [
                'code' => 'JAUNE_CSE_ATTENTE_INFO',
                'nom' => 'CSE en attente d\'informations',
                'type' => 'jaune',
                'description' => 'Fiche jaune quand le CSE demande des informations complémentaires avant décision.',
                'fichier_path' => 'fiche-templates/FICHE_JAUNE_CSE_ATTENTE_INFO.docx',
                'variables' => [
                    '{{NOM_CLIENT}}',
                    '{{TELEPHONE_CLIENT}}',
                    '{{EMAIL_CLIENT}}',
                    '{{NOM_INTERLOCUTEUR}}',
                    '{{INFORMATIONS_DEMANDEES}}',
                    '{{DATE_RELANCE}}',
                ],
                'actif' => true,
            ],
            [
                'code' => 'JAUNE_CSE_BUDGET',
                'nom' => 'CSE - Contrainte budgétaire',
                'type' => 'jaune',
                'description' => 'Fiche jaune pour les CSE intéressés mais avec contraintes budgétaires à négocier.',
                'fichier_path' => 'fiche-templates/FICHE_JAUNE_CSE_BUDGET.docx',
                'variables' => [
                    '{{NOM_CLIENT}}',
                    '{{TELEPHONE_CLIENT}}',
                    '{{NOM_INTERLOCUTEUR}}',
                    '{{BUDGET_ESTIME}}',
                    '{{BUDGET_CSE}}',
                    '{{ECART}}',
                    '{{PROPOSITION_ALTERNATIVE}}',
                ],
                'actif' => true,
            ],

            // ── Variantes Fiches Vertes ────────────────────────────────────
            [
                'code' => 'VERTE_RDV_A_CONCLURE_V2',
                'nom' => 'RDV à conclure - Version Prioritaire',
                'type' => 'verte',
                'description' => 'Fiche verte marquée comme prioritaire pour les prospects à fort potentiel.',
                'fichier_path' => 'fiche-templates/FICHE_VERTE_RDV_A_CONCLURE_PRIORITAIRE.docx',
                'variables' => [
                    '{{NOM_CLIENT}}',
                    '{{TELEPHONE_CLIENT}}',
                    '{{EMAIL_CLIENT}}',
                    '{{NOM_INTERLOCUTEUR}}',
                    '{{POTENTIEL_ESTIME}}',
                    '{{DATE_TENTATIVE}}',
                    '{{PRIORITE}}',
                ],
                'actif' => true,
            ],
            [
                'code' => 'VERTE_ENTREPRISE_SANS_CSE',
                'nom' => 'Entreprise sans CSE',
                'type' => 'verte',
                'description' => 'Fiche verte pour les entreprises sans CSE (moins de 50 salariés) à contacter directement.',
                'fichier_path' => 'fiche-templates/FICHE_VERTE_ENTREPRISE_SANS_CSE.docx',
                'variables' => [
                    '{{NOM_CLIENT}}',
                    '{{TELEPHONE_CLIENT}}',
                    '{{EMAIL_CLIENT}}',
                    '{{NOM_INTERLOCUTEUR}}',
                    '{{POSTE_INTERLOCUTEUR}}',
                    '{{EFFECTIF_ENTREPRISE}}',
                    '{{SECTEUR_ACTIVITE}}',
                ],
                'actif' => true,
            ],
            [
                'code' => 'VERTE_BLOCAGE_STANDARD',
                'nom' => 'Blocage au standard',
                'type' => 'verte',
                'description' => 'Fiche verte pour les prospects bloqués au standard après plusieurs tentatives.',
                'fichier_path' => 'fiche-templates/FICHE_VERTE_BLOCAGE_STANDARD.docx',
                'variables' => [
                    '{{NOM_CLIENT}}',
                    '{{TELEPHONE_STANDARD}}',
                    '{{NOMBRE_TENTATIVES}}',
                    '{{DATES_TENTATIVES}}',
                    '{{COMMENTAIRES}}',
                ],
                'actif' => true,
            ],

            // ── Nouveaux types de templates ─────────────────────────────────
            [
                'code' => 'ROUGE_URGENCE',
                'nom' => 'Traitement Urgent',
                'type' => 'rouge',
                'description' => 'Fiche rouge pour les situations urgentes nécessitant un traitement prioritaire.',
                'fichier_path' => 'fiche-templates/FICHE_ROUGE_URGENCE.docx',
                'variables' => [
                    '{{NOM_CLIENT}}',
                    '{{TELEPHONE_CLIENT}}',
                    '{{MOTIF_URGENCE}}',
                    '{{DATE_ECHEANCE}}',
                    '{{ACTION_REQUISE}}',
                ],
                'actif' => true,
            ],
            [
                'code' => 'ORANGE_NEGOCIATION',
                'nom' => 'Phase de Négociation',
                'type' => 'orange',
                'description' => 'Fiche orange pour les prospects en phase active de négociation commerciale.',
                'fichier_path' => 'fiche-templates/FICHE_ORANGE_NEGOCIATION.docx',
                'variables' => [
                    '{{NOM_CLIENT}}',
                    '{{NOM_INTERLOCUTEUR}}',
                    '{{PROPOSITION_COMMERCIALE}}',
                    '{{MONTANT_NEGOCIE}}',
                    '{{CONDITIONS}}',
                    '{{DATE_SIGNATURE_PREVUE}}',
                ],
                'actif' => true,
            ],
        ];

        foreach ($templates as $data) {
            TemplateFiche::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
            
            $this->command->line("  ✓ {$data['code']} - {$data['nom']}");
        }

        $this->command->info('Variantes de templates de fiches créées avec succès.');
    }
}
