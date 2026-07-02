<?php

/**
 * Scripts d'appel Ns Conseil / AOPIA pour le workflow de phoning.
 */
return [
    [
        'titre' => 'Prospection CSE - Accroche standard',
        'slug' => 'ns-conseil-prospect-cse-accroche',
        'type_contact' => 'prospect',
        'campagne_id' => null,
        'onglet' => 'accroche',
        'contenu' => <<<'TEXT'
Bonjour, je cherche a joindre la personne en charge du CSE ou des avantages salaries.

Je suis {commercial_nom}, pour AOPIA / LIKE Formation. Nous accompagnons les CSE, syndicats et entreprises qui souhaitent proposer des solutions de formation utiles aux salaries.

Est-ce que vous pouvez me mettre en relation avec l'elu CSE, le secretaire CSE ou la personne qui gere ce sujet ?
TEXT,
        'conseil' => 'Objectif principal : obtenir le bon interlocuteur CSE. Si le standard bloque, renseigner le nom du standard et utiliser RAPL-STD ou BLOC selon le cas.',
        'variables_disponibles' => [
            ['cle' => 'contact_nom', 'description' => 'Nom de la fiche appelee'],
            ['cle' => 'commercial_nom', 'description' => 'Nom du conseiller connecte'],
        ],
        'actif' => true,
        'ordre' => 10,
    ],
    [
        'titre' => 'Prospection CSE - Decouverte',
        'slug' => 'ns-conseil-prospect-cse-decouverte',
        'type_contact' => 'prospect',
        'campagne_id' => null,
        'onglet' => 'decouverte',
        'contenu' => <<<'TEXT'
Questions a qualifier :

- Quel est votre role au sein du CSE ?
- Combien de salaries sont rattaches a l'entreprise ?
- Avez-vous deja des actions formation ou accompagnement pour les salaries ?
- Qui valide les propositions adressees au CSE ?
- Quel canal preferez-vous pour recevoir une presentation : email, rendez-vous telephonique, visio ?
- Quel serait le meilleur creneau pour un echange avec notre commercial ?

Informations indispensables a noter : nom, prenom, fonction, telephone direct, email, disponibilites et contexte de l'echange.
TEXT,
        'conseil' => 'Une fiche exploitable doit permettre au commercial de rappeler sans reposer les memes questions.',
        'actif' => true,
        'ordre' => 20,
    ],
    [
        'titre' => 'Prospection CSE - Argumentaire',
        'slug' => 'ns-conseil-prospect-cse-argumentaire',
        'type_contact' => 'prospect',
        'campagne_id' => null,
        'onglet' => 'argumentaire',
        'contenu' => <<<'TEXT'
AOPIA / LIKE Formation accompagne les salaries dans leurs projets de formation professionnelle, avec un suivi clair et une approche adaptee aux contraintes des CSE.

L'objectif n'est pas de vous faire perdre du temps : nous proposons un echange court pour identifier si nos dispositifs peuvent interesser vos salaries, puis nous envoyons une synthese exploitable.

Si le sujet est pertinent, nous planifions un rendez-vous avec le commercial pour presenter les solutions et repondre aux questions du CSE.
TEXT,
        'conseil' => 'Rester sobre : obtenir un rendez-vous qualifie, pas vendre tout le dispositif au premier appel.',
        'kpis' => [
            ['valeur' => 'CSE', 'label' => 'cible prioritaire', 'couleur' => 'blue'],
            ['valeur' => 'J+7', 'label' => 'relance commercial si CSE non interesse', 'couleur' => 'orange'],
            ['valeur' => 'DEP_XX', 'label' => 'tag departement obligatoire', 'couleur' => 'green'],
        ],
        'actif' => true,
        'ordre' => 30,
    ],
    [
        'titre' => 'Prospection CSE - Objections',
        'slug' => 'ns-conseil-prospect-cse-objections',
        'type_contact' => 'prospect',
        'campagne_id' => null,
        'onglet' => 'objections',
        'contenu' => 'Traiter l\'objection, puis revenir vers une demande simple : obtenir le bon interlocuteur, un email ou un créneau de rappel.',
        'conseil' => 'Ne pas forcer. Si l\'élu demande un rappel, utiliser RAPL-ELU. Si le standard suggère un rappel, utiliser RAPL-STD.',
        'objections' => [
            [
                'question' => 'Envoyez un email',
                'reponse' => 'Bien sûr. Pour que le message arrive à la bonne personne, pouvez-vous me confirmer le nom et l\'email de l\'élu ou du secrétaire CSE ?',
            ],
            [
                'question' => 'Nous ne sommes pas interesses',
                'reponse' => 'Je comprends. Je note le retour. Est-ce que je peux verifier rapidement si vous preferez ne plus etre recontacte ou si un rappel commercial dans quelques jours est pertinent ?',
            ],
            [
                'question' => 'La personne n\'est pas disponible',
                'reponse' => 'À quel moment puis-je la rappeler ? Je note le jour, l\'heure et votre nom pour éviter de vous solliciter inutilement.',
            ],
            [
                'question' => 'Il n\'y a pas de CSE',
                'reponse' => 'Merci pour l\'information. Savez-vous si l\'entreprise compte moins ou plus de 50 salariés ? Cela me permet de renseigner correctement la fiche.',
            ],
            [
                'question' => 'Nous travaillons deja avec un organisme',
                'reponse' => 'C est justement utile de le savoir. Notre echange peut simplement servir a comparer les besoins des salaries et voir s il existe un sujet complementaire.',
            ],
        ],
        'actif' => true,
        'ordre' => 40,
    ],
    [
        'titre' => 'Prospection CSE - Closing RDV ou rappel',
        'slug' => 'ns-conseil-prospect-cse-closing',
        'type_contact' => 'prospect',
        'campagne_id' => null,
        'onglet' => 'closing',
        'contenu' => <<<'TEXT'
Pour avancer proprement, je vous propose de caler un rendez-vous court avec notre commercial.

Quel creneau vous conviendrait le mieux : debut de semaine ou fin de semaine ?

Avant de terminer, je confirme les informations :
- nom et fonction de l'interlocuteur CSE ;
- telephone direct et email ;
- date, heure et lieu ou mode du rendez-vous ;
- besoins exprimes et points d'attention ;
- tag departement DEP_XX + statut d'appel.
TEXT,
        'conseil' => 'Si le rendez-vous est confirme : statut RDV et fiche bleue. Si rappel demande : RAPL-ELU. Si blocage standard persiste : BLOC ou BLOC2.',
        'actif' => true,
        'ordre' => 50,
    ],
    [
        'titre' => 'Partenaire - Accroche suivi',
        'slug' => 'ns-conseil-partenaire-accroche',
        'type_contact' => 'partenaire',
        'campagne_id' => null,
        'onglet' => 'accroche',
        'contenu' => 'Bonjour {contact_prenom} {contact_nom}, je suis {commercial_nom} d\'AOPIA / LIKE Formation. Je vous appelle pour faire un point rapide sur notre partenariat et les actions en cours.',
        'conseil' => 'Identifier rapidement si l\'appel concerne un suivi, une relance de convention, une permanence ou un point commercial.',
        'actif' => true,
        'ordre' => 110,
    ],
    [
        'titre' => 'Partenaire - Decouverte suivi',
        'slug' => 'ns-conseil-partenaire-decouverte',
        'type_contact' => 'partenaire',
        'campagne_id' => null,
        'onglet' => 'decouverte',
        'contenu' => "Points a verifier :\n\n- statut de l'accord ou de la convention ;\n- prochaines permanences ou actions prevues ;\n- demandes des salaries ;\n- interlocuteur decisionnaire ;\n- irritants, documents manquants, prochaine echeance.",
        'conseil' => 'Noter une prochaine action claire avec responsable et date.',
        'actif' => true,
        'ordre' => 120,
    ],
    [
        'titre' => 'Partenaire - Argumentaire suivi',
        'slug' => 'ns-conseil-partenaire-argumentaire',
        'type_contact' => 'partenaire',
        'campagne_id' => null,
        'onglet' => 'argumentaire',
        'contenu' => "Notre objectif est de faciliter la mise en relation entre vos beneficiaires et nos equipes, avec un suivi lisible pour le CSE ou la structure partenaire.\n\nNous pouvons organiser un point, preparer une permanence ou transmettre une synthese selon votre besoin.",
        'conseil' => 'Rester oriente action : prochaine permanence, document, relance ou rendez-vous.',
        'actif' => true,
        'ordre' => 130,
    ],
    [
        'titre' => 'Partenaire - Objections suivi',
        'slug' => 'ns-conseil-partenaire-objections',
        'type_contact' => 'partenaire',
        'campagne_id' => null,
        'onglet' => 'objections',
        'contenu' => 'Utiliser ces reponses pour relancer sans mettre le partenaire en difficulte.',
        'conseil' => 'Si aucune action n\'est possible, qualifier le motif et programmer une relance.',
        'objections' => [
            ['question' => 'Nous n\'avons pas de retour salariés', 'reponse' => 'Je comprends. Voulez-vous que l\'on prépare un message ou une action simple pour relancer la communication ?'],
            ['question' => 'Ce n\'est pas le bon moment', 'reponse' => 'Pas de souci. Quelle période serait plus adaptée pour refaire un point utile ?'],
            ['question' => 'Il manque un document', 'reponse' => 'Je note le document attendu et je le fais suivre a la bonne personne avec une date de retour.'],
        ],
        'actif' => true,
        'ordre' => 140,
    ],
    [
        'titre' => 'Partenaire - Closing suivi',
        'slug' => 'ns-conseil-partenaire-closing',
        'type_contact' => 'partenaire',
        'campagne_id' => null,
        'onglet' => 'closing',
        'contenu' => "Je récapitule notre prochaine action :\n\n- action à réaliser ;\n- responsable ;\n- date cible ;\n- pièces ou informations attendues.\n\nJe vous envoie la confirmation si nécessaire et je mets la fiche à jour.",
        'conseil' => 'Toujours terminer avec une action datee ou un motif clair.',
        'actif' => true,
        'ordre' => 150,
    ],
    [
        'titre' => 'Client - Accroche suivi formation',
        'slug' => 'ns-conseil-client-accroche',
        'type_contact' => 'client',
        'campagne_id' => null,
        'onglet' => 'accroche',
        'contenu' => 'Bonjour {contact_prenom} {contact_nom}, je suis {commercial_nom} de LIKE Formation. Je vous appelle concernant votre dossier de formation et pour verifier le meilleur suivi a vous proposer.',
        'conseil' => 'Vérifier l\'identité et rester centré sur le dossier client, pas sur la prospection CSE.',
        'actif' => true,
        'ordre' => 210,
    ],
    [
        'titre' => 'Client - Decouverte besoin',
        'slug' => 'ns-conseil-client-decouverte',
        'type_contact' => 'client',
        'campagne_id' => null,
        'onglet' => 'decouverte',
        'contenu' => "Questions utiles :\n\n- Quelle formation ou quel projet souhaitez-vous avancer ?\n- Avez-vous deja un dossier en cours ?\n- Quel est le meilleur creneau pour vous rappeler ?\n- Avez-vous besoin d un accompagnement administratif ?\n- Souhaitez-vous recevoir les informations par email ?",
        'conseil' => 'Si la personne demande a ne plus etre contactee, utiliser le statut KO client et renseigner la note.',
        'actif' => true,
        'ordre' => 220,
    ],
    [
        'titre' => 'Client - Argumentaire accompagnement',
        'slug' => 'ns-conseil-client-argumentaire',
        'type_contact' => 'client',
        'campagne_id' => null,
        'onglet' => 'argumentaire',
        'contenu' => 'LIKE Formation vous accompagne dans la comprehension du parcours, les etapes administratives et le suivi de votre projet afin que vous sachiez toujours quelle est la prochaine action.',
        'conseil' => 'Adapter le discours à l\'état du dossier : prospect, en cours, terminé ou relance.',
        'actif' => true,
        'ordre' => 230,
    ],
    [
        'titre' => 'Client - Objections',
        'slug' => 'ns-conseil-client-objections',
        'type_contact' => 'client',
        'campagne_id' => null,
        'onglet' => 'objections',
        'contenu' => 'Reponses courtes pour traiter les freins les plus frequents.',
        'conseil' => 'Ne pas insister si le client refuse explicitement le contact.',
        'objections' => [
            ['question' => 'Je ne suis plus interesse', 'reponse' => 'Je comprends. Voulez-vous que je note de ne plus vous recontacter a ce sujet ?'],
            ['question' => 'Je n\'ai pas le temps', 'reponse' => 'À quel moment puis-je vous rappeler pour que ce soit plus confortable pour vous ?'],
            ['question' => 'J ai besoin de reflechir', 'reponse' => 'Bien sur. Je peux vous envoyer les informations utiles et programmer un rappel a la date qui vous convient.'],
        ],
        'actif' => true,
        'ordre' => 240,
    ],
    [
        'titre' => 'Client - Closing rappel',
        'slug' => 'ns-conseil-client-closing',
        'type_contact' => 'client',
        'campagne_id' => null,
        'onglet' => 'closing',
        'contenu' => "Je récapitule : je note votre situation, votre préférence de contact et la prochaine action.\n\nSouhaitez-vous que je vous rappelle à une date précise ou que je vous envoie les informations par email ?",
        'conseil' => 'Si rappel : renseigner date et heure. Si refus : noter clairement le motif.',
        'actif' => true,
        'ordre' => 250,
    ],
];
