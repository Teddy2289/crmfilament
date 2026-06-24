<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'nom'         => 'Confirmation RDV — CSE',
                'cle'         => 'rdv.confirmation_cse',
                'sujet'       => 'Confirmation de votre rendez-vous AOPIA Formation — {{rdv_date}} à {{rdv_heure}}',
                'description' => 'Mail 1 AOPIA — Envoyé au CSE après prise de RDV (statut RPC)',
                'corps'       => "Bonjour {{cse_prenom}},\n\nComme convenu lors de notre échange, je vous confirme votre rendez-vous avec notre Responsable de Secteur :\n\nDate : {{rdv_jour}} {{rdv_date}}\nHeure : {{rdv_heure}}\nLieu : {{rdv_lieu}}\n\nVotre interlocuteur : {{responsable_prenom_nom}}\n\nNotre Responsable de Secteur vous présentera les modalités de formation pour vos collègues ainsi que des exemples de communications déjà mises en place dans d'autres entreprises de votre département.\n\nN'hésitez pas à me contacter si vous souhaitez modifier ce créneau.\n\n{{teleprospecteur_prenom}} — AOPIA Formation",
            ],
            [
                'nom'         => 'Invitation Agenda — Responsable de Secteur',
                'cle'         => 'rdv.invitation_responsable',
                'sujet'       => '[RDV AOPIA] {{raison_sociale}} — {{rdv_date}} à {{rdv_heure}} — {{cse_prenom}}',
                'description' => 'Mail 2 AOPIA — Invitation agenda envoyée au Responsable de Secteur avec .ics en PJ',
                'corps'       => "Bonjour {{responsable_prenom}},\n\nTu trouveras ci-dessous et en pièces jointes tous les éléments pour ton rendez-vous.\nMerci d'accepter l'invitation agenda ci-jointe.\n\n{{rdv_jour}} {{rdv_date}} à {{rdv_heure}}  —  {{rdv_lieu}}\n\n{{cse_prenom_nom}} — {{cse_fonction}}\n{{cse_email}}\n{{cse_telephone_direct}}\n\n{{raison_sociale}} — {{secteur_activite}} — {{effectif}} salariés\n\nPoints clés :\n{{notes_appel}}\n\nLe RDV a été confirmé par email au CSE.\nLes pièces jointes incluent la fiche récap et l'enregistrement audio.\n\n{{teleprospecteur_prenom}} — AOPIA Formation",
            ],
            [
                'nom'         => 'Rappel J-1 RDV — CSE',
                'cle'         => 'rdv.rappel_cse',
                'sujet'       => 'Rappel : votre rendez-vous AOPIA Formation demain — {{rdv_heure}}',
                'description' => 'Rappel automatique J-1 envoyé au CSE',
                'corps'       => "Bonjour {{cse_prenom}},\n\nJe vous rappelle votre rendez-vous de demain avec notre Responsable de Secteur :\n\nDate : {{rdv_date}}\nHeure : {{rdv_heure}}\nLieu : {{rdv_lieu}}\n\nVotre interlocuteur : {{responsable_prenom_nom}}\n\nÀ demain !\n\nAOPIA Formation",
            ],
            [
                'nom'         => 'Rappel J-1 RDV — Responsable de Secteur',
                'cle'         => 'rdv.rappel_responsable',
                'sujet'       => 'Rappel RDV demain — {{raison_sociale}} — {{rdv_heure}}',
                'description' => 'Rappel automatique J-1 envoyé au Responsable de Secteur',
                'corps'       => "Bonjour {{responsable_prenom}},\n\nRappel de ton rendez-vous de demain :\n\n{{raison_sociale}} — {{rdv_date}} à {{rdv_heure}}\n\n{{cse_prenom_nom}}\n{{cse_email}}\n\nBonne journée !\n\nAOPIA Formation",
            ],
            [
                'nom'         => 'Relance prospect RPC',
                'cle'         => 'prospect.relance_rpc',
                'sujet'       => '[Relance] {{raison_sociale}} — RDV à planifier',
                'description' => 'Relance interne quand un prospect RPC reste sans RDV > 48h',
                'corps'       => "Bonjour {{teleprospecteur_prenom}},\n\nLe prospect {{raison_sociale}} ({{cse_prenom_nom}}) est en statut RPC depuis le {{date_dernier_contact}} sans RDV planifié.\n\nMerci de recontacter ce prospect rapidement.\n\nAOPIA Formation",
            ],
            [
                'nom'         => 'Bienvenue — Artisan',
                'cle'         => 'artisan.bienvenue',
                'sujet'       => 'Bienvenue dans le réseau AOPIA — {{raison_sociale}}',
                'description' => 'Email de bienvenue envoyé au nouvel artisan',
                'corps'       => "Bonjour {{artisan_prenom_nom}},\n\nNous avons le plaisir de vous accueillir dans le réseau AlloPro 24/24 !\n\nVotre entreprise : {{raison_sociale}}\nVotre métier : {{metier}}\nVotre conseiller : {{conseiller_nom}}\n\nVotre espace artisan sera activé sous 24h. Vous recevrez vos identifiants de connexion par email.\n\nBienvenue dans l'équipe !\n\nAlloPro 24/24",
            ],
            [
                'nom'         => 'Activation compte Artisan',
                'cle'         => 'artisan.activation',
                'sujet'       => 'Votre compte artisan est maintenant actif — {{raison_sociale}}',
                'description' => 'Notification envoyée à l\'artisan quand son compte est activé',
                'corps'       => "Bonjour {{artisan_prenom_nom}},\n\nVotre compte AlloPro est maintenant actif !\n\nEntreprise : {{raison_sociale}}\nDate d'activation : {{date_activation}}\n\nVous pouvez dès à présent recevoir des appels et des demandes d'intervention via notre centrale.\n\nAlloPro 24/24",
            ],
            [
                'nom'         => 'Notification ouverture ticket',
                'cle'         => 'ticket.ouverture',
                'sujet'       => 'Votre demande a bien été enregistrée — Réf. {{ticket_reference}}',
                'description' => 'Email envoyé au client/contact à l\'ouverture d\'un ticket',
                'corps'       => "Bonjour {{contact_prenom_nom}},\n\nVotre demande a bien été enregistrée dans notre système.\n\nRéférence : {{ticket_reference}}\nObjet : {{ticket_objet}}\nPriorité : {{ticket_priorite}}\nOpérateur en charge : {{operateur_nom}}\n\nNous vous recontacterons dans les meilleurs délais.\n\nAlloPro 24/24",
            ],
            [
                'nom'         => 'Notification résolution ticket',
                'cle'         => 'ticket.resolution',
                'sujet'       => 'Votre demande est résolue — Réf. {{ticket_reference}}',
                'description' => 'Email envoyé au client/contact quand un ticket est résolu',
                'corps'       => "Bonjour {{contact_prenom_nom}},\n\nVotre demande {{ticket_reference}} — {{ticket_objet}} a été résolue.\n\nRésolution : {{resolution_description}}\n\nOpérateur : {{operateur_nom}}\n\nN'hésitez pas à nous recontacter si besoin.\n\nAlloPro 24/24",
            ],
            [
                'nom'         => 'Envoi devis Artisan',
                'cle'         => 'devis.envoi',
                'sujet'       => 'Votre devis AOPIA — Réf. {{devis_reference}}',
                'description' => 'Email d\'envoi de devis à un artisan',
                'corps'       => "Bonjour {{artisan_prenom_nom}},\n\nVeuillez trouver ci-joint votre devis.\n\nEntreprise : {{raison_sociale}}\nRéférence devis : {{devis_reference}}\nMontant HT : {{montant_ht}}\nMontant TTC : {{montant_ttc}}\nValidité : {{validite_date}}\n\nPour toute question, contactez-nous.\n\nAlloPro 24/24",
            ],
            [
                'nom'         => 'Envoi facture',
                'cle'         => 'facture.envoi',
                'sujet'       => 'Facture {{facture_numero}} — {{raison_sociale}}',
                'description' => 'Email d\'envoi de facture à un artisan',
                'corps'       => "Bonjour {{artisan_prenom_nom}},\n\nVeuillez trouver ci-joint votre facture.\n\nEntreprise : {{raison_sociale}}\nNuméro de facture : {{facture_numero}}\nMontant TTC : {{montant_ttc}}\nÉchéance : {{echeance_date}}\nMode de paiement : {{mode_paiement}}\n\nAlloPro 24/24",
            ],
            [
                'nom'         => 'Relance facture impayée',
                'cle'         => 'facture.relance',
                'sujet'       => '[Rappel paiement] Facture {{facture_numero}} — échéance dépassée',
                'description' => 'Relance envoyée à un artisan pour facture en retard de paiement',
                'corps'       => "Bonjour {{artisan_prenom_nom}},\n\nSauf erreur ou omission de notre part, votre facture ci-dessous reste impayée à ce jour.\n\nEntreprise : {{raison_sociale}}\nFacture : {{facture_numero}}\nMontant TTC : {{montant_ttc}}\nÉchéance : {{echeance_date}}\nRetard : {{jours_retard}} jours\n\nMerci de régulariser cette situation dans les meilleurs délais.\n\nAlloPro 24/24",
            ],
            [
                'nom'         => 'Bienvenue — Partenaire',
                'cle'         => 'partenaire.bienvenue',
                'sujet'       => 'Bienvenue — Accord AOPIA Formation — {{raison_sociale}}',
                'description' => 'Email de bienvenue envoyé au nouveau partenaire',
                'corps'       => "Bonjour {{contact_prenom_nom}},\n\nNous sommes ravis de vous accueillir en tant que partenaire AOPIA Formation.\n\nOrganisation : {{raison_sociale}}\nType de partenariat : {{type_partenaire}}\nVotre conseiller référent : {{conseiller_nom}}\n\nNous reviendrons vers vous très prochainement pour démarrer notre collaboration.\n\nAOPIA Formation — NS Conseil",
            ],
            [
                'nom'         => 'Ouverture réclamation P8',
                'cle'         => 'reclamation.ouverture',
                'sujet'       => 'Votre réclamation a été enregistrée — Réf. {{reclamation_reference}}',
                'description' => 'Email de confirmation d\'ouverture d\'une réclamation (fiche P8)',
                'corps'       => "Bonjour {{contact_prenom_nom}},\n\nNous accusons réception de votre réclamation.\n\nRéférence : {{reclamation_reference}}\nObjet : {{objet}}\nDélai de traitement : {{delai_traitement}}\n\nNous faisons le nécessaire pour traiter votre demande dans les meilleurs délais.\n\nAlloPro 24/24",
            ],
            [
                'nom'         => 'Clôture réclamation P8',
                'cle'         => 'reclamation.resolution',
                'sujet'       => 'Votre réclamation est résolue — Réf. {{reclamation_reference}}',
                'description' => 'Email de clôture d\'une réclamation (fiche P8)',
                'corps'       => "Bonjour {{contact_prenom_nom}},\n\nNous vous informons que votre réclamation {{reclamation_reference}} a été traitée.\n\nRésolution : {{resolution_description}}\n\nNous espérons que cette réponse vous convient. N'hésitez pas à nous contacter pour tout renseignement complémentaire.\n\nAlloPro 24/24",
            ],
            [
                'nom'         => 'Invitation utilisateur CRM',
                'cle'         => 'user.invitation',
                'sujet'       => 'Votre accès au CRM NS Conseil',
                'description' => 'Email d\'invitation envoyé à un nouvel utilisateur CRM',
                'corps'       => "Bonjour {{user_prenom_nom}},\n\nVotre compte CRM NS Conseil a été créé.\n\nEmail : {{user_email}}\nRôle : {{role}}\n\nConnectez-vous ici : {{lien_connexion}}\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez cet email.\n\nNS Conseil",
            ],
        ];

        foreach ($templates as $data) {
            EmailTemplate::updateOrCreate(
                ['cle' => $data['cle']],
                array_merge($data, ['actif' => true])
            );
        }

        $this->command->info('16 templates email créés/mis à jour avec succès.');
    }
}
