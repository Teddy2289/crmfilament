# Requirements Document

## Introduction

Cette fonctionnalité ajoute, dans la section emails du panel NsConseil (`ns-conseil/emails`), un sélecteur de boîte mail (mailbox switcher). Il permet à l'utilisateur connecté de voir quelle boîte mail est actuellement active et de basculer vers une autre boîte connectée si plusieurs configurations `EmailConfiguration` sont disponibles (configuration personnelle et/ou configurations globales).

Le contexte actuel : la page `ListEmails` filtre les emails par `user_id` et synchronise uniquement la première config active trouvée via `EmailConfiguration::forUser()`. Un utilisateur peut avoir accès à plusieurs configs (la sienne + les globales). Le switcher doit mémoriser la boîte active choisie et appliquer ce choix à l'affichage des emails et aux synchronisations.

## Glossaire

- **Mailbox_Switcher** : Composant UI affiché dans l'en-tête de la page liste des emails, permettant d'afficher et de changer la boîte mail active.
- **Active_Mailbox** : L'`EmailConfiguration` actuellement sélectionnée pour filtrer et synchroniser les emails dans la session de l'utilisateur.
- **EmailConfiguration** : Modèle Laravel représentant un compte email connecté (IMAP/SMTP), lié à un utilisateur ou global.
- **Email_List** : La page Filament `ListEmails` affichant les emails de la boîte active.
- **Session** : La session HTTP de l'utilisateur connecté, utilisée pour persister le choix de la boîte active.
- **NsConseil_Panel** : Le panel Filament principal du CRM, accessible à l'URL `/ns-conseil`.
- **ImapService** : Le service Laravel qui exécute la synchronisation IMAP pour une `EmailConfiguration` donnée.

---

## Requirements

### Requirement 1 : Affichage de la boîte mail active

**User Story :** En tant qu'utilisateur du CRM, je veux voir quelle boîte mail est actuellement active dans la section emails, afin de savoir depuis quel compte je consulte mes messages.

#### Acceptance Criteria

1. WHEN l'utilisateur accède à la page `ns-conseil/emails`, THE Mailbox_Switcher SHALL afficher l'adresse email et le nom de l'expéditeur de l'Active_Mailbox.
2. WHEN une seule `EmailConfiguration` active est disponible pour l'utilisateur, THE Mailbox_Switcher SHALL afficher cette configuration sans proposer d'options de changement.
3. WHEN aucune `EmailConfiguration` active n'est disponible pour l'utilisateur, THE Mailbox_Switcher SHALL afficher un message indiquant qu'aucune boîte mail n'est configurée.
4. THE Mailbox_Switcher SHALL être visible dans l'en-tête de la page `Email_List`, en haut à gauche ou à droite des actions existantes.

---

### Requirement 2 : Sélection d'une boîte mail alternative

**User Story :** En tant qu'utilisateur du CRM ayant plusieurs comptes mail connectés, je veux pouvoir basculer vers une autre boîte mail, afin de consulter et synchroniser les emails d'un compte différent.

#### Acceptance Criteria

1. WHEN plusieurs `EmailConfiguration` actives sont disponibles pour l'utilisateur, THE Mailbox_Switcher SHALL afficher une liste déroulante listant toutes les boîtes disponibles.
2. WHEN l'utilisateur sélectionne une `EmailConfiguration` dans la liste déroulante, THE Mailbox_Switcher SHALL mettre à jour l'Active_Mailbox dans la Session de l'utilisateur.
3. WHEN l'Active_Mailbox change, THE Email_List SHALL se recharger et n'afficher que les emails appartenant à la nouvelle Active_Mailbox.
4. WHEN l'Active_Mailbox est une configuration globale (`is_global = true`), THE Mailbox_Switcher SHALL l'indiquer visuellement (ex. badge "Globale" ou icône distincte).
5. THE Mailbox_Switcher SHALL lister les `EmailConfiguration` dans l'ordre suivant : configurations personnelles de l'utilisateur en premier, configurations globales ensuite.

---

### Requirement 3 : Persistance du choix de boîte mail

**User Story :** En tant qu'utilisateur du CRM, je veux que ma boîte mail sélectionnée soit mémorisée pendant ma session, afin de ne pas avoir à la re-sélectionner à chaque navigation dans le module emails.

#### Acceptance Criteria

1. WHEN l'utilisateur sélectionne une Active_Mailbox, THE Session SHALL conserver l'identifiant de l'`EmailConfiguration` choisie sous la clé `active_mailbox_id`.
2. WHEN l'utilisateur navigue hors de la page `Email_List` puis y revient, THE Mailbox_Switcher SHALL afficher l'Active_Mailbox précédemment sélectionnée si elle est toujours disponible et active.
3. WHEN l'Active_Mailbox stockée en Session n'est plus active ou accessible pour l'utilisateur, THE Mailbox_Switcher SHALL sélectionner automatiquement la première `EmailConfiguration` active disponible pour l'utilisateur.
4. WHEN aucune `EmailConfiguration` n'est disponible en Session et qu'une seule configuration existe, THE Mailbox_Switcher SHALL sélectionner automatiquement cette configuration unique comme Active_Mailbox.

---

### Requirement 4 : Filtrage des emails par boîte active

**User Story :** En tant qu'utilisateur du CRM, je veux que la liste des emails affiche uniquement les messages de la boîte mail active, afin d'éviter de mélanger les emails de plusieurs comptes.

#### Acceptance Criteria

1. WHEN une Active_Mailbox est sélectionnée, THE Email_List SHALL filtrer les emails en appliquant la condition `from_email = active_mailbox.email` OU `user_id = active_mailbox.user_id` selon le type de configuration.
2. WHILE une configuration globale est l'Active_Mailbox, THE Email_List SHALL afficher les emails dont le champ `from_email` correspond à l'adresse de cette configuration globale.
3. WHILE une configuration personnelle est l'Active_Mailbox, THE Email_List SHALL afficher les emails de l'utilisateur connecté correspondant à cette configuration.
4. WHEN l'Active_Mailbox change, THE Email_List SHALL réinitialiser la pagination à la première page.

---

### Requirement 5 : Synchronisation liée à la boîte active

**User Story :** En tant qu'utilisateur du CRM, je veux que le bouton "Synchroniser" synchronise uniquement la boîte mail active, afin de ne pas déclencher une synchronisation sur un compte qui n'est pas en cours de consultation.

#### Acceptance Criteria

1. WHEN l'utilisateur clique sur le bouton "Synchroniser la boîte mail", THE ImapService SHALL être invoqué uniquement pour l'Active_Mailbox.
2. IF aucune Active_Mailbox n'est définie en Session, THEN THE Email_List SHALL afficher une notification d'avertissement indiquant qu'aucune boîte mail active n'est configurée.
3. WHEN la synchronisation se termine avec succès, THE Email_List SHALL afficher le nombre d'emails synchronisés dans la notification de succès.
4. IF une erreur survient pendant la synchronisation de l'Active_Mailbox, THEN THE ImapService SHALL journaliser l'erreur et THE Email_List SHALL afficher une notification d'erreur avec le message de l'exception.

---

### Requirement 6 : Accessibilité et comportement UI du Mailbox_Switcher

**User Story :** En tant qu'utilisateur du CRM, je veux que le sélecteur de boîte mail soit intuitif et accessible, afin de pouvoir changer de boîte rapidement sans friction.

#### Acceptance Criteria

1. THE Mailbox_Switcher SHALL être rendu sous la forme d'un composant `Select` Filament ou d'un menu déroulant intégré à l'en-tête de la page `Email_List`.
2. WHEN une seule configuration est disponible, THE Mailbox_Switcher SHALL désactiver l'élément de sélection (`disabled`) pour indiquer qu'aucun switch n'est possible.
3. THE Mailbox_Switcher SHALL afficher pour chaque option : l'adresse email de la configuration et le nom de l'expéditeur (`from_name`) s'il est renseigné.
4. WHEN le Mailbox_Switcher est rendu, THE Mailbox_Switcher SHALL être accessible au clavier (navigation par tabulation et sélection par touches directionnelles / Entrée).
