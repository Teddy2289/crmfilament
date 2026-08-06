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
                'corps'       => "Bonjour {{contact_prenom_nom}},\n\nNous sommes ravis de vous accueillir en tant que partenaire AOPIA Formation.\n\nOrganisation : {{raison_sociale}}\nType de partenariat : {{type_partenaire}}\nVotre conseiller référent : {{conseiller_nom}}\n\nNous reviendrons vers vous très prochainement pour démarrer notre collaboration.\n\nAOPIA Formation — AOPIA LIKE Formation",
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
                'sujet'       => 'Votre accès au CRM AOPIA LIKE Formation',
                'description' => 'Email d\'invitation envoyé à un nouvel utilisateur CRM',
                'corps'       => "Bonjour {{user_prenom_nom}},\n\nVotre compte CRM AOPIA LIKE Formation a été créé.\n\nEmail : {{user_email}}\nRôle : {{role}}\n\nConnectez-vous ici : {{lien_connexion}}\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez cet email.\n\nAOPIA LIKE Formation",
            ],
            [
                'nom'         => 'Confirmation RDV Prospect (CSE)',
                'cle'         => 'prospect.confirmation_rdv',
                'sujet'       => 'Confirmation de votre rendez-vous avec AOPIA LIKE Formation — {{rdv_date}} à {{rdv_heure}}',
                'description' => 'Email envoyé au CSE après la prise de RDV (statut rdv)',
                'corps'       => "Bonjour {{prospect_prenom}},\n\nNous confirmons votre rendez-vous avec notre Responsable de Secteur :\n\nDate : {{rdv_jour}} {{rdv_date}}\nHeure : {{rdv_heure}}\nLieu : {{rdv_lieu}}\n\nVotre interlocuteur : {{commercial_nom}}\n\nNous restons à votre disposition.\n\nCordialement,\nL'équipe AOPIA LIKE Formation",
            ],
            [
                'nom'         => 'Prise de contact — Standard bloqué (BLOC)',
                'cle'         => 'prospect.prise_contact_bloc',
                'sujet'       => 'Prise de contact — {{entreprise_nom}}',
                'description' => 'Envoyé à l\'élu CSE quand le standard de l\'entreprise a transmis ses coordonnées mais bloque le contact téléphonique direct (statut bloc)',
                'corps'       => "Bonjour {{elu_prenom}} {{elu_nom}},\n\nJe me permets de vous contacter par email suite à notre appel auprès du standard de {{entreprise_nom}}, qui m'a transmis vos coordonnées en tant qu'élu(e) du CSE.\n\nJe suis {{teleprospecteur_nom}}, en charge du développement de AOPIA LIKE Formation dans votre secteur. Nous accompagnons les CSE dans la mise en place de formations et d'actions dédiées aux salariés.\n\nJe souhaiterais échanger avec vous pour vous présenter notre accompagnement. Vos disponibilités éventuelles : {{disponibilites}}\n\nN'hésitez pas à me recontacter directement par retour de mail ou au {{elu_telephone}}.\n\nCordialement,\n{{teleprospecteur_nom}} — AOPIA LIKE Formation",
            ],
            [
                'nom'         => 'Prise de contact — Sans CSE (NCSE-50)',
                'cle'         => 'prospect.contact_sans_cse',
                'sujet'       => 'AOPIA LIKE Formation — {{entreprise_nom}}',
                'description' => 'Envoyé au contact identifié quand l\'entreprise n\'a pas de CSE (statut ncse_50, moins de 50 salariés)',
                'corps'       => "Bonjour {{contact_prenom}} {{contact_nom}},\n\nJe vous contacte au sujet de {{entreprise_nom}} ({{nb_salaries}} salarié(s)), suite à notre échange téléphonique.\n\nJe suis {{teleprospecteur_nom}}, en charge du développement de AOPIA LIKE Formation dans votre secteur. Nous accompagnons les entreprises et leurs salariés au travers de formations et d'actions dédiées, y compris en l'absence de CSE.\n\nJe reste à votre disposition pour vous présenter notre accompagnement.\n\nCordialement,\n{{teleprospecteur_nom}} — AOPIA LIKE Formation",
            ],
            [
                'nom'         => 'CSE hors zone — Transmission interne',
                'cle'         => 'interne.cse_hors_zone',
                'sujet'       => '[CSE hors zone] {{entreprise_nom}} — {{departement}}',
                'description' => 'Email interne (non envoyé au prospect) transmettant un CSE identifié hors de la zone de prospection pour traitement centralisé (statut cse_hz)',
                'corps'       => "Bonjour,\n\nLe CSE suivant a été identifié hors de notre zone de prospection habituelle et vous est transmis pour traitement centralisé :\n\nEntreprise : {{entreprise_nom}}\nÉlu contact : {{elu_nom}}\nEmail : {{elu_email}}\nTéléphone : {{elu_telephone}}\nDépartement : {{departement}}\nVille : {{ville}}\n\nMerci de prendre le relais sur ce dossier.\n\nAOPIA LIKE Formation",
            ],
        ];

        foreach ($templates as $data) {
            EmailTemplate::updateOrCreate(
                ['cle' => $data['cle']],
                array_merge($data, ['actif' => true])
            );
        }

        $this->command->info(count($templates) . ' templates email créés/mis à jour avec succès.');
    }
}
