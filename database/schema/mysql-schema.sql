/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `activite_partenaires`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activite_partenaires` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nb_ventes` int NOT NULL DEFAULT '0',
  `derniere_vente` date DEFAULT NULL,
  `nb_permanences` int NOT NULL DEFAULT '0',
  `derniere_permanence` date DEFAULT NULL,
  `partenaire_id` bigint unsigned NOT NULL,
  `consultant_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activite_partenaires_partenaire_id_index` (`partenaire_id`),
  KEY `activite_partenaires_consultant_id_index` (`consultant_id`),
  CONSTRAINT `activite_partenaires_consultant_id_foreign` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `activite_partenaires_partenaire_id_foreign` FOREIGN KEY (`partenaire_id`) REFERENCES `partenaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `activite_permanences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activite_permanences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `partenaire_id` bigint unsigned NOT NULL,
  `consultant_id` bigint unsigned DEFAULT NULL,
  `derniere_permanence` date DEFAULT NULL,
  `nbre_2025` int NOT NULL DEFAULT '0',
  `nbre_2026` int NOT NULL DEFAULT '0',
  `prc_2026` int NOT NULL DEFAULT '0' COMMENT 'Nombre de PRC 2026',
  `rdv_physique` int DEFAULT NULL COMMENT 'Nb RDV physiques',
  `rdv_telephonique` int DEFAULT NULL COMMENT 'Nb RDV téléphoniques',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activite_permanences_partenaire_id_index` (`partenaire_id`),
  KEY `activite_permanences_consultant_id_index` (`consultant_id`),
  CONSTRAINT `activite_permanences_consultant_id_foreign` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `activite_permanences_partenaire_id_foreign` FOREIGN KEY (`partenaire_id`) REFERENCES `partenaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `activite_ventes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activite_ventes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `partenaire_id` bigint unsigned NOT NULL,
  `consultant_id` bigint unsigned DEFAULT NULL,
  `nombre_ventes_total` int NOT NULL DEFAULT '0',
  `derniere_vente` date DEFAULT NULL,
  `ventes_2025` int NOT NULL DEFAULT '0',
  `ventes_2026` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activite_ventes_partenaire_id_index` (`partenaire_id`),
  KEY `activite_ventes_consultant_id_index` (`consultant_id`),
  CONSTRAINT `activite_ventes_consultant_id_foreign` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `activite_ventes_partenaire_id_foreign` FOREIGN KEY (`partenaire_id`) REFERENCES `partenaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `adresse_cses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `adresse_cses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `partenaire_id` bigint unsigned NOT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `code_postal` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `commune` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `adresse_cses_partenaire_id_index` (`partenaire_id`),
  CONSTRAINT `adresse_cses_partenaire_id_foreign` FOREIGN KEY (`partenaire_id`) REFERENCES `partenaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `affaire_interventions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `affaire_interventions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'AFF-YYYY-NNNNN',
  `ticket_id` bigint unsigned NOT NULL,
  `artisan_id` bigint unsigned NOT NULL,
  `operateur_dispatch_id` bigint unsigned DEFAULT NULL,
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `numero_tentative` tinyint unsigned NOT NULL DEFAULT '1' COMMENT 'Nième tentative de dispatch pour ce ticket',
  `motif_annulation` text COLLATE utf8mb4_unicode_ci,
  `date_rdv_prevue` datetime DEFAULT NULL,
  `creneau_debut` time DEFAULT NULL,
  `creneau_fin` time DEFAULT NULL,
  `date_notification_artisan` datetime DEFAULT NULL COMMENT 'Heure d''envoi du dispatch',
  `date_confirmation_artisan` datetime DEFAULT NULL,
  `delai_confirmation_minutes` smallint unsigned DEFAULT NULL COMMENT 'SLA P4 : délai entre notification et confirmation',
  `canal_notification` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Canal utilisé : appel, sms, email',
  `date_debut_reelle` datetime DEFAULT NULL,
  `date_fin_reelle` datetime DEFAULT NULL,
  `duree_reelle_minutes` smallint unsigned DEFAULT NULL,
  `description_travaux_realises` text COLLATE utf8mb4_unicode_ci,
  `compte_rendu_artisan` text COLLATE utf8mb4_unicode_ci,
  `signature_client` tinyint(1) NOT NULL DEFAULT '0',
  `date_signature_client` datetime DEFAULT NULL,
  `satisfaction_immediate` tinyint unsigned DEFAULT NULL COMMENT 'Note immédiate client 1–5 à chaud',
  `notes_dispatch` text COLLATE utf8mb4_unicode_ci,
  `notes_intervention` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `affaire_interventions_reference_unique` (`reference`),
  KEY `affaire_interventions_operateur_dispatch_id_foreign` (`operateur_dispatch_id`),
  KEY `affaire_interventions_statut_index` (`statut`),
  KEY `affaire_interventions_ticket_id_index` (`ticket_id`),
  KEY `affaire_interventions_artisan_id_index` (`artisan_id`),
  KEY `affaire_interventions_date_rdv_prevue_index` (`date_rdv_prevue`),
  CONSTRAINT `affaire_interventions_artisan_id_foreign` FOREIGN KEY (`artisan_id`) REFERENCES `artisans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `affaire_interventions_operateur_dispatch_id_foreign` FOREIGN KEY (`operateur_dispatch_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `affaire_interventions_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `analytic_dashboards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytic_dashboards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permissions` json DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_tokens_token_unique` (`token`),
  KEY `api_tokens_user_id_index` (`user_id`),
  KEY `api_tokens_token_index` (`token`),
  CONSTRAINT `api_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `appels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `appels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `appelable_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `appelable_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `type` enum('Appel','Permanence','Présentation') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Appel',
  `resultat` enum('Réalisé','Annulé','Décalé','Non abouti','Rappel') COLLATE utf8mb4_unicode_ci DEFAULT 'Réalisé',
  `date_heure` datetime NOT NULL,
  `duree_secondes` int DEFAULT NULL,
  `commentaire` text COLLATE utf8mb4_unicode_ci,
  `compte_comme_tentative` tinyint(1) NOT NULL DEFAULT '0',
  `enregistrement_audio` text COLLATE utf8mb4_unicode_ci,
  `ringover_call_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ringover_number_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ringover_user_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ringover_agent_nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ringover_tags` json DEFAULT NULL,
  `ringover_department_tag` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ringover_status_tag` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ringover_tag_validation` json DEFAULT NULL,
  `ringover_tag_is_complete` tinyint(1) NOT NULL DEFAULT '0',
  `ringover_payload` json DEFAULT NULL,
  `ringover_synced_at` timestamp NULL DEFAULT NULL,
  `ringover_webhook_received_at` timestamp NULL DEFAULT NULL,
  `ringover_sync_source` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direction` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_appelant` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `phoning_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phoning_result` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phoning_notes` text COLLATE utf8mb4_unicode_ci,
  `phoning_completed_at` timestamp NULL DEFAULT NULL,
  `phoning_skipped_at` timestamp NULL DEFAULT NULL,
  `phoning_agent_id` bigint unsigned DEFAULT NULL,
  `campagne_id` bigint unsigned DEFAULT NULL,
  `fiche_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fiche_data` json DEFAULT NULL,
  `fiche_word_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fiche_word_generated_at` timestamp NULL DEFAULT NULL,
  `fiche_jaune_j7_envoye_at` timestamp NULL DEFAULT NULL,
  `fiche_verte_envoyee_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `appels_appelable_type_appelable_id_index` (`appelable_type`,`appelable_id`),
  KEY `appels_phoning_agent_id_foreign` (`phoning_agent_id`),
  KEY `appels_phoning_status_index` (`phoning_status`),
  KEY `appels_campagne_id_foreign` (`campagne_id`),
  KEY `idx_appels_user_date` (`user_id`,`date_heure`),
  KEY `appels_ringover_department_tag_index` (`ringover_department_tag`),
  KEY `appels_ringover_status_tag_index` (`ringover_status_tag`),
  KEY `appels_ringover_tag_is_complete_index` (`ringover_tag_is_complete`),
  KEY `appels_ringover_webhook_received_at_index` (`ringover_webhook_received_at`),
  KEY `appels_appelable_type_id` (`appelable_type`,`appelable_id`),
  KEY `appels_date_heure_user_id` (`date_heure`,`user_id`),
  KEY `appels_phoning_status` (`phoning_status`),
  CONSTRAINT `appels_campagne_id_foreign` FOREIGN KEY (`campagne_id`) REFERENCES `campagne_phonings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appels_phoning_agent_id_foreign` FOREIGN KEY (`phoning_agent_id`) REFERENCES `users` (`id`),
  CONSTRAINT `appels_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `artisan_prospections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `artisan_prospections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `corps_de_metier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zone_geo` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut_campagne` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AC',
  `date_dernier_contact` datetime DEFAULT NULL,
  `teleprospecteur_id` bigint unsigned DEFAULT NULL,
  `priorite_segment` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Standard',
  `accord_verbal` tinyint(1) NOT NULL DEFAULT '0',
  `date_envoi_document` datetime DEFAULT NULL,
  `artisan_id` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `artisan_prospections_teleprospecteur_id_foreign` (`teleprospecteur_id`),
  KEY `artisan_prospections_artisan_id_foreign` (`artisan_id`),
  KEY `artisan_prospections_statut_campagne_index` (`statut_campagne`),
  KEY `artisan_prospections_priorite_segment_index` (`priorite_segment`),
  CONSTRAINT `artisan_prospections_artisan_id_foreign` FOREIGN KEY (`artisan_id`) REFERENCES `artisans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `artisan_prospections_teleprospecteur_id_foreign` FOREIGN KEY (`teleprospecteur_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `artisans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `artisans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `raison_sociale` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `siret` varchar(14) COLLATE utf8mb4_unicode_ci NOT NULL,
  `corps_de_metier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zone_intervention` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone_principal` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone_secondaire` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_cti_transfert` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Numéro affiché lors du transfert CTI vers l''artisan (Screen Pop)',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `canal_alerte` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Les deux',
  `statut_compte` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente_activation',
  `formule_souscrite` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mode_agenda` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mode_a',
  `plages_disponibilite` json DEFAULT NULL,
  `date_souscription` date NOT NULL,
  `date_activation` date DEFAULT NULL,
  `agenda_disponibilites` tinyint(1) NOT NULL DEFAULT '0',
  `note_moyenne` decimal(3,2) DEFAULT NULL,
  `nb_interventions` int NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `artisans_siret_unique` (`siret`),
  KEY `artisans_statut_compte_index` (`statut_compte`),
  KEY `artisans_corps_de_metier_index` (`corps_de_metier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `model_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_model_id` bigint unsigned DEFAULT NULL,
  `related_model_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `audit_logs_user_id_index` (`user_id`),
  KEY `audit_logs_action_index` (`action`),
  KEY `audit_logs_created_at_index` (`created_at`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `autres_interlocuteurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `autres_interlocuteurs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `partenaire_id` bigint unsigned NOT NULL,
  `texte_libre` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `autres_interlocuteurs_partenaire_id_index` (`partenaire_id`),
  CONSTRAINT `autres_interlocuteurs_partenaire_id_foreign` FOREIGN KEY (`partenaire_id`) REFERENCES `partenaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `backups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'full',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `tables` json DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `automatic` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `backups_type_index` (`type`),
  KEY `backups_status_index` (`status`),
  KEY `backups_created_by_index` (`created_by`),
  KEY `backups_created_at_index` (`created_at`),
  CONSTRAINT `backups_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bon_de_commandes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bon_de_commandes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Format BC-AAAA-NNNN',
  `devis_id` bigint unsigned NOT NULL,
  `ticket_id` bigint unsigned NOT NULL,
  `artisan_id` bigint unsigned NOT NULL,
  `contact_particulier_id` bigint unsigned NOT NULL,
  `lignes` json NOT NULL COMMENT 'Reprises du devis accepté',
  `montant_total_ttc` decimal(10,2) NOT NULL DEFAULT '0.00',
  `acompte_montant` decimal(10,2) DEFAULT NULL,
  `acompte_encaisse` tinyint(1) NOT NULL DEFAULT '0',
  `date_intervention_prevue` timestamp NULL DEFAULT NULL,
  `duree_estimee_heures` int DEFAULT NULL,
  `instructions_artisan` text COLLATE utf8mb4_unicode_ci COMMENT 'Accès, outils particuliers…',
  `conditions_paiement` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'solde_intervention',
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente' COMMENT 'en_attente / confirme / en_cours / realise / annule',
  `date_confirmation` timestamp NULL DEFAULT NULL COMMENT 'Quand artisan confirme',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bon_de_commandes_numero_unique` (`numero`),
  KEY `bon_de_commandes_ticket_id_foreign` (`ticket_id`),
  KEY `bon_de_commandes_contact_particulier_id_foreign` (`contact_particulier_id`),
  KEY `bon_de_commandes_statut_index` (`statut`),
  KEY `bon_de_commandes_date_intervention_prevue_index` (`date_intervention_prevue`),
  KEY `bon_de_commandes_artisan_id_statut_index` (`artisan_id`,`statut`),
  KEY `bon_de_commandes_devis_id_index` (`devis_id`),
  CONSTRAINT `bon_de_commandes_artisan_id_foreign` FOREIGN KEY (`artisan_id`) REFERENCES `artisans` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `bon_de_commandes_contact_particulier_id_foreign` FOREIGN KEY (`contact_particulier_id`) REFERENCES `contact_particuliers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `bon_de_commandes_devis_id_foreign` FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `bon_de_commandes_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campagne_phonings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `campagne_phonings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `statut` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'brouillon',
  `type_entite` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `criteres` json DEFAULT NULL,
  `max_tentatives` int NOT NULL DEFAULT '4',
  `jours_refroidissement` int NOT NULL DEFAULT '15',
  `exclure_autres_campagnes` tinyint(1) NOT NULL DEFAULT '1',
  `exclure_sans_telephone` tinyint(1) NOT NULL DEFAULT '1',
  `script_appel` text COLLATE utf8mb4_unicode_ci,
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `groupe_telepro_id` bigint unsigned DEFAULT NULL,
  `entite_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campagne_phonings_user_id_index` (`user_id`),
  KEY `campagne_phonings_entite_id_index` (`entite_id`),
  KEY `campagne_phonings_statut_index` (`statut`),
  KEY `campagne_phonings_groupe_telepro_id_index` (`groupe_telepro_id`),
  CONSTRAINT `campagne_phonings_entite_id_foreign` FOREIGN KEY (`entite_id`) REFERENCES `entite_commerciales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `campagne_phonings_groupe_telepro_id_foreign` FOREIGN KEY (`groupe_telepro_id`) REFERENCES `groupes_telepro` (`id`) ON DELETE SET NULL,
  CONSTRAINT `campagne_phonings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campagnes_marketing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `campagnes_marketing` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime DEFAULT NULL,
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'brouillon',
  `cibles` json DEFAULT NULL,
  `contenu` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campagnes_marketing_created_by_foreign` (`created_by`),
  KEY `campagnes_marketing_type_index` (`type`),
  KEY `campagnes_marketing_statut_index` (`statut`),
  KEY `campagnes_marketing_date_debut_index` (`date_debut`),
  CONSTRAINT `campagnes_marketing_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaigns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `budget` decimal(10,2) DEFAULT NULL,
  `budget_depense` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_by` bigint unsigned NOT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `cibles` json DEFAULT NULL,
  `contenu` json DEFAULT NULL,
  `envois_total` int NOT NULL DEFAULT '0',
  `ouvertures` int NOT NULL DEFAULT '0',
  `clics` int NOT NULL DEFAULT '0',
  `conversions` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campaigns_type_index` (`type`),
  KEY `campaigns_statut_index` (`statut`),
  KEY `campaigns_date_debut_index` (`date_debut`),
  KEY `campaigns_created_by_index` (`created_by`),
  KEY `campaigns_assigned_to_index` (`assigned_to`),
  CONSTRAINT `campaigns_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `campaigns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chat_room_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_room_participants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `chat_room_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  `last_read_at` timestamp NULL DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chat_room_participants_chat_room_id_user_id_unique` (`chat_room_id`,`user_id`),
  KEY `chat_room_participants_user_id_index` (`user_id`),
  CONSTRAINT `chat_room_participants_chat_room_id_foreign` FOREIGN KEY (`chat_room_id`) REFERENCES `chat_rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_room_participants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chat_rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_rooms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'direct',
  `created_by` bigint unsigned NOT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_rooms_type_index` (`type`),
  KEY `chat_rooms_created_by_index` (`created_by`),
  CONSTRAINT `chat_rooms_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source_sheet` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_client` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_clients` json DEFAULT NULL,
  `civilite` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prenom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nom_tiers` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code_postal` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ville` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departement` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `entreprise` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_tiers` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avis_google` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `etat` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `montant_cpf` decimal(10,2) DEFAULT NULL,
  `ne_plus_contacter` tinyint(1) NOT NULL DEFAULT '0',
  `extra_data` json DEFAULT NULL,
  `notes_commerciales` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `partenaire_id` bigint unsigned DEFAULT NULL,
  `commercial_id` bigint unsigned DEFAULT NULL,
  `parrain_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clients_ref_client_index` (`ref_client`),
  KEY `clients_partenaire_id_index` (`partenaire_id`),
  KEY `clients_parrain_id_index` (`parrain_id`),
  KEY `clients_commercial_id_foreign` (`commercial_id`),
  KEY `clients_email_index` (`email`),
  KEY `clients_telephone_index` (`telephone`),
  KEY `clients_etat_commercial_id` (`etat`,`commercial_id`),
  KEY `clients_etat_partenaire_id` (`etat`,`partenaire_id`),
  KEY `clients_ne_plus_contacter` (`ne_plus_contacter`),
  CONSTRAINT `clients_commercial_id_foreign` FOREIGN KEY (`commercial_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clients_parrain_id_foreign` FOREIGN KEY (`parrain_id`) REFERENCES `parrains` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clients_partenaire_id_foreign` FOREIGN KEY (`partenaire_id`) REFERENCES `partenaires` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `commentable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `commentable_id` bigint unsigned NOT NULL,
  `contenu` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comments_commentable_type_commentable_id_index` (`commentable_type`,`commentable_id`),
  KEY `comments_user_id_index` (`user_id`),
  KEY `comments_parent_id_index` (`parent_id`),
  CONSTRAINT `comments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `consultants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consultants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mandataire / VDI / Salarié / PRC / PIP',
  `departement` int DEFAULT NULL,
  `entite_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consultants_entite_id_index` (`entite_id`),
  CONSTRAINT `consultants_entite_id_foreign` FOREIGN KEY (`entite_id`) REFERENCES `entite_commerciales` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_partenaires`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_partenaires` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `partenaire_id` bigint unsigned NOT NULL,
  `civilite` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fonction` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nom_syndicat` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nom du syndicat — renseigné uniquement si role = SYNDICAT_DS',
  `service` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preference_contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone_direct` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone_mobile` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone_perso` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_perso` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `est_principal` tinyint(1) NOT NULL DEFAULT '0',
  `est_decisionnaire` tinyint(1) NOT NULL DEFAULT '0',
  `niveau_influence` int DEFAULT NULL,
  `canal_prefere` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_partenaires_partenaire_id_index` (`partenaire_id`),
  KEY `contact_partenaires_est_principal_index` (`est_principal`),
  KEY `contact_partenaires_fonction_index` (`fonction`),
  CONSTRAINT `contact_partenaires_partenaire_id_foreign` FOREIGN KEY (`partenaire_id`) REFERENCES `partenaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_particuliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_particuliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `canal_contact_preferentiel` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'appel' COMMENT 'Canal de contact préféré: appel / sms / email',
  `adresse_complete` text COLLATE utf8mb4_unicode_ci,
  `code_postal` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Code postal extrait de l''adresse complète',
  `ville` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ville extraite de l''adresse complète',
  `type_logement` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut_occupant` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_particuliers_telephone_index` (`telephone`),
  KEY `contact_particuliers_code_postal_index` (`code_postal`),
  KEY `contact_particuliers_ville_index` (`ville`),
  KEY `contact_particuliers_canal_contact_preferentiel_index` (`canal_contact_preferentiel`),
  KEY `contact_particuliers_code_postal_ville_index` (`code_postal`,`ville`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crm_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `panels` json DEFAULT NULL,
  `landing_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `theme_id` bigint unsigned DEFAULT NULL,
  `couleur` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'gray',
  `icone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ordre` smallint unsigned NOT NULL DEFAULT '0',
  `can_validate_qf` tinyint(1) NOT NULL DEFAULT '0',
  `can_import` tinyint(1) NOT NULL DEFAULT '0',
  `is_supervisor` tinyint(1) NOT NULL DEFAULT '0',
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_profiles_role_name_unique` (`role_name`),
  KEY `crm_profiles_theme_id_foreign` (`theme_id`),
  CONSTRAINT `crm_profiles_theme_id_foreign` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crm_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `default_crm` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ns-conseil',
  `groupe` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ordre` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_settings_groupe_cle_unique` (`groupe`,`cle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_field_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_field_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `custom_field_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_field_model` (`custom_field_id`,`model_type`,`model_id`),
  KEY `custom_field_values_model_type_model_id_index` (`model_type`,`model_id`),
  CONSTRAINT `custom_field_values_custom_field_id_foreign` FOREIGN KEY (`custom_field_id`) REFERENCES `custom_fields` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_fields` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `options` text COLLATE utf8mb4_unicode_ci,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `target_model` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `placeholder` text COLLATE utf8mb4_unicode_ci,
  `helper_text` text COLLATE utf8mb4_unicode_ci,
  `order` int NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `custom_fields_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `lien` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lu` tinyint(1) NOT NULL DEFAULT '0',
  `lu_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `custom_notifications_user_id_index` (`user_id`),
  KEY `custom_notifications_lu_index` (`lu`),
  KEY `custom_notifications_type_index` (`type`),
  CONSTRAINT `custom_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `data_deletion_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_deletion_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `devis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `devis` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Format DEV-AAAA-NNNN',
  `ticket_id` bigint unsigned NOT NULL,
  `artisan_id` bigint unsigned NOT NULL,
  `contact_particulier_id` bigint unsigned NOT NULL,
  `lignes` json NOT NULL COMMENT '[{libelle, quantite, prix_unitaire_ht, taux_tva}]',
  `remise_montant` decimal(10,2) NOT NULL DEFAULT '0.00',
  `remise_pourcentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `conditions_paiement` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'solde_intervention',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `date_validite` date NOT NULL COMMENT 'Par défaut J+30',
  `date_emission` date DEFAULT NULL,
  `date_acceptation_refus` timestamp NULL DEFAULT NULL,
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'brouillon',
  `mode_acceptation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'signature_electronique / appel / email',
  `total_ht` decimal(10,2) NOT NULL DEFAULT '0.00',
  `montant_tva` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_ttc` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `devis_numero_unique` (`numero`),
  KEY `devis_ticket_id_foreign` (`ticket_id`),
  KEY `devis_statut_index` (`statut`),
  KEY `devis_date_validite_index` (`date_validite`),
  KEY `devis_artisan_id_statut_index` (`artisan_id`,`statut`),
  KEY `devis_contact_particulier_id_statut_index` (`contact_particulier_id`,`statut`),
  CONSTRAINT `devis_artisan_id_foreign` FOREIGN KEY (`artisan_id`) REFERENCES `artisans` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `devis_contact_particulier_id_foreign` FOREIGN KEY (`contact_particulier_id`) REFERENCES `contact_particuliers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `devis_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `document_knowledges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_knowledges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `contenu` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fichier_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fichier_nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fichier_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `taille_octets` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `est_publique` tinyint(1) NOT NULL DEFAULT '1',
  `ordre` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `document_knowledges_created_by_foreign` (`created_by`),
  KEY `document_knowledges_updated_by_foreign` (`updated_by`),
  KEY `document_knowledges_type_categorie_index` (`type`,`categorie`),
  KEY `document_knowledges_est_publique_index` (`est_publique`),
  CONSTRAINT `document_knowledges_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `document_knowledges_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `document_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint unsigned NOT NULL,
  `fichier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `chemin` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` int NOT NULL DEFAULT '1',
  `commentaire` text COLLATE utf8mb4_unicode_ci,
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `document_versions_uploaded_by_foreign` (`uploaded_by`),
  KEY `document_versions_document_id_index` (`document_id`),
  KEY `document_versions_version_index` (`version`),
  CONSTRAINT `document_versions_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_versions_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom_fichier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'partenaires',
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `taille` bigint DEFAULT NULL,
  `documentable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `documentable_id` bigint unsigned NOT NULL,
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documents_documentable_type_documentable_id_index` (`documentable_type`,`documentable_id`),
  KEY `documents_uploaded_by_foreign` (`uploaded_by`),
  KEY `documents_documentable_type_id` (`documentable_type`,`documentable_id`),
  CONSTRAINT `documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dossier_formations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dossier_formations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ref_client` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `intitule_programme` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entite_id` bigint unsigned DEFAULT NULL,
  `personne_id` bigint unsigned DEFAULT NULL,
  `montant_ht` decimal(10,2) DEFAULT NULL,
  `montant_cpf` decimal(10,2) DEFAULT NULL,
  `date_vente` date DEFAULT NULL,
  `statut_formation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_dossier_edof` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `etat` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `consultant_accueil_id` bigint unsigned DEFAULT NULL,
  `consultant_formateur_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dossier_formations_consultant_accueil_id_foreign` (`consultant_accueil_id`),
  KEY `dossier_formations_consultant_formateur_id_foreign` (`consultant_formateur_id`),
  KEY `dossier_formations_entite_id_index` (`entite_id`),
  KEY `dossier_formations_personne_id_index` (`personne_id`),
  KEY `dossier_formations_ref_client_index` (`ref_client`),
  CONSTRAINT `dossier_formations_consultant_accueil_id_foreign` FOREIGN KEY (`consultant_accueil_id`) REFERENCES `consultants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dossier_formations_consultant_formateur_id_foreign` FOREIGN KEY (`consultant_formateur_id`) REFERENCES `consultants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dossier_formations_entite_id_foreign` FOREIGN KEY (`entite_id`) REFERENCES `entite_commerciales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dossier_formations_personne_id_foreign` FOREIGN KEY (`personne_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_configurations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `is_global` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Configuration globale pour tous les utilisateurs',
  `imap_host` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'imap.gmail.com',
  `imap_port` int NOT NULL DEFAULT '993',
  `imap_encryption` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ssl' COMMENT 'ssl, tls, starttls, none',
  `imap_protocol` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'imap' COMMENT 'imap, pop3',
  `smtp_host` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'smtp.gmail.com',
  `smtp_port` int NOT NULL DEFAULT '587',
  `smtp_encryption` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tls' COMMENT 'ssl, tls, starttls, none',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sync_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `sync_interval` int NOT NULL DEFAULT '5' COMMENT 'Intervalle en minutes',
  `sync_limit` int NOT NULL DEFAULT '50' COMMENT 'Nombre d''emails à synchroniser',
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_global_config` (`user_id`,`is_global`),
  KEY `email_configurations_user_id_active_index` (`user_id`,`active`),
  KEY `email_configurations_is_global_index` (`is_global`),
  KEY `email_configurations_sync_enabled_index` (`sync_enabled`),
  CONSTRAINT `email_configurations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sujet` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `corps` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `corps_plain` text COLLATE utf8mb4_unicode_ci,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_templates_cle_unique` (`cle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emails` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `emailable_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emailable_id` bigint unsigned DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'received',
  `message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `from_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `from_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_email` text COLLATE utf8mb4_unicode_ci,
  `cc_email` text COLLATE utf8mb4_unicode_ci,
  `bcc_email` text COLLATE utf8mb4_unicode_ci,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body_text` longtext COLLATE utf8mb4_unicode_ci,
  `body_html` longtext COLLATE utf8mb4_unicode_ci,
  `attachments` json DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `folder` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inbox',
  `priority` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `labels` json DEFAULT NULL,
  `in_reply_to` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emails_message_id_unique` (`message_id`),
  KEY `emails_emailable_type_emailable_id_index` (`emailable_type`,`emailable_id`),
  KEY `emails_type_folder_index` (`type`,`folder`),
  KEY `emails_user_id_folder_index` (`user_id`,`folder`),
  KEY `emails_received_at_index` (`received_at`),
  KEY `emails_sent_at_index` (`sent_at`),
  KEY `emails_read_at_index` (`read_at`),
  KEY `emails_priority_index` (`priority`),
  CONSTRAINT `emails_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entite_commerciales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entite_commerciales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entite_commerciales_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entity_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entity_relations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `from_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_id` bigint unsigned NOT NULL,
  `to_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_id` bigint unsigned NOT NULL,
  `relation_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'related',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_relation` (`from_type`,`from_id`,`to_type`,`to_id`),
  KEY `entity_relations_created_by_foreign` (`created_by`),
  KEY `entity_relations_from_type_from_id_index` (`from_type`,`from_id`),
  KEY `entity_relations_to_type_to_id_index` (`to_type`,`to_id`),
  CONSTRAINT `entity_relations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entreprises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entreprises` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `raison_sociale` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `siret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `siren` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_tva` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `forme_juridique` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capital` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code_postal` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ville` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pays` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'France',
  `telephone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_web` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secteur_activite` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `effectif` int DEFAULT NULL,
  `code_naf` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `extra_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entreprises_siret_unique` (`siret`),
  KEY `entreprises_siret_index` (`siret`),
  KEY `entreprises_siren_index` (`siren`),
  KEY `entreprises_raison_sociale_index` (`raison_sociale`),
  KEY `entreprises_ville_index` (`ville`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `env_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `env_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `group` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `is_sensitive` tinyint(1) NOT NULL DEFAULT '0',
  `is_editable` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `env_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `evenements_calendrier`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evenements_calendrier` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `debut` datetime NOT NULL,
  `fin` datetime NOT NULL,
  `journee_entiere` tinyint(1) NOT NULL DEFAULT '0',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rdv',
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planifie',
  `lieu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `participants` json DEFAULT NULL,
  `couleur` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'blue',
  `user_id` bigint unsigned NOT NULL,
  `rendez_vous_id` bigint unsigned DEFAULT NULL,
  `task_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `evenements_calendrier_rendez_vous_id_foreign` (`rendez_vous_id`),
  KEY `evenements_calendrier_task_id_foreign` (`task_id`),
  KEY `evenements_calendrier_user_id_index` (`user_id`),
  KEY `evenements_calendrier_debut_index` (`debut`),
  KEY `evenements_calendrier_fin_index` (`fin`),
  KEY `evenements_calendrier_type_index` (`type`),
  KEY `evenements_calendrier_statut_index` (`statut`),
  CONSTRAINT `evenements_calendrier_rendez_vous_id_foreign` FOREIGN KEY (`rendez_vous_id`) REFERENCES `rendez_vous` (`id`) ON DELETE SET NULL,
  CONSTRAINT `evenements_calendrier_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `evenements_calendrier_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `factures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `factures` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Format FAC-AAAA-NNNN — séquence chronologique obligatoire',
  `bon_de_commande_id` bigint unsigned NOT NULL,
  `ticket_id` bigint unsigned NOT NULL,
  `artisan_id` bigint unsigned NOT NULL,
  `contact_particulier_id` bigint unsigned NOT NULL,
  `lignes` json NOT NULL COMMENT '[{libelle, quantite, prix_unitaire_ht, taux_tva}]',
  `total_ht` decimal(10,2) NOT NULL DEFAULT '0.00',
  `montant_tva` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_ttc` decimal(10,2) NOT NULL DEFAULT '0.00',
  `acompte_deja_verse` decimal(10,2) DEFAULT NULL,
  `solde_restant_du` decimal(10,2) NOT NULL DEFAULT '0.00',
  `date_emission` date DEFAULT NULL,
  `date_echeance` date DEFAULT NULL COMMENT 'Date limite de règlement (obligatoire)',
  `date_paiement_effectif` date DEFAULT NULL COMMENT 'À la réception du règlement',
  `mode_paiement` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'virement / cb / cheque / especes',
  `statut_paiement` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente' COMMENT 'en_attente / partiel / paye / en_retard / litigieux',
  `conditions_paiement` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `penalites_retard` decimal(10,2) NOT NULL DEFAULT '0.00',
  `avoir_id` bigint unsigned DEFAULT NULL,
  `fichier_pdf` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path vers le PDF généré',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `factures_numero_unique` (`numero`),
  KEY `factures_ticket_id_foreign` (`ticket_id`),
  KEY `factures_statut_paiement_index` (`statut_paiement`),
  KEY `factures_date_echeance_index` (`date_echeance`),
  KEY `factures_artisan_id_statut_paiement_index` (`artisan_id`,`statut_paiement`),
  KEY `factures_contact_particulier_id_statut_paiement_index` (`contact_particulier_id`,`statut_paiement`),
  KEY `factures_bon_de_commande_id_index` (`bon_de_commande_id`),
  KEY `factures_avoir_id_index` (`avoir_id`),
  CONSTRAINT `factures_artisan_id_foreign` FOREIGN KEY (`artisan_id`) REFERENCES `artisans` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `factures_avoir_id_foreign` FOREIGN KEY (`avoir_id`) REFERENCES `factures` (`id`) ON DELETE SET NULL,
  CONSTRAINT `factures_bon_de_commande_id_foreign` FOREIGN KEY (`bon_de_commande_id`) REFERENCES `bon_de_commandes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `factures_contact_particulier_id_foreign` FOREIGN KEY (`contact_particulier_id`) REFERENCES `contact_particuliers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `factures_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fiche_p2s`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fiche_p2s` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `corps_de_metier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nature_probleme` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description_detaillee` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `localisation_precise` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `anciennete_probleme` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `niveau_priorite` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `justificatif_priorite` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `bascule_p5_requise` tinyint(1) DEFAULT NULL,
  `presence_client` tinyint(1) NOT NULL DEFAULT '1',
  `type_logement` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut_occupant` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `garantie_contrat` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code_acces_interphone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_alternatif` text COLLATE utf8mb4_unicode_ci,
  `etage_ascenseur` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nom_client` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone_client` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_client` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse_intervention` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_postal_ville` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `canal_contact_preferentiel` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reponses_metier` json DEFAULT NULL,
  `fiche_complete` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `agent_qualificateur_id` bigint unsigned DEFAULT NULL,
  `date_qualification_complete` datetime DEFAULT NULL,
  `duree_appel_p2` int unsigned DEFAULT NULL COMMENT 'Durée en secondes',
  `source_appel_ligne` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fiche_p2s_ticket_id_unique` (`ticket_id`),
  KEY `fiche_p2s_agent_qualificateur_id_foreign` (`agent_qualificateur_id`),
  CONSTRAINT `fiche_p2s_agent_qualificateur_id_foreign` FOREIGN KEY (`agent_qualificateur_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fiche_p2s_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fiche_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fiche_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `template_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `placeholders` json DEFAULT NULL,
  `statut_phoning_codes` json DEFAULT NULL,
  `auto_generation` tinyint(1) NOT NULL DEFAULT '0',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fiche_templates_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `field_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `field_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resource` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visible_list` tinyint(1) NOT NULL DEFAULT '1',
  `visible_view` tinyint(1) NOT NULL DEFAULT '1',
  `visible_edit` tinyint(1) NOT NULL DEFAULT '1',
  `read_only` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `field_permissions_role_resource_field_name_unique` (`role`,`resource`,`field_name`),
  KEY `field_permissions_role_resource_index` (`role`,`resource`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `field_visibility`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `field_visibility` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `table_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `column_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `field_visibility_table_name_column_name_role_name_unique` (`table_name`,`column_name`,`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gantt_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gantt_tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `project_id` bigint unsigned DEFAULT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `prospect_id` bigint unsigned DEFAULT NULL,
  `client_id` bigint unsigned DEFAULT NULL,
  `partenaire_id` bigint unsigned DEFAULT NULL,
  `opportunite_id` bigint unsigned DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `duration` int DEFAULT NULL,
  `progress` int NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `order` int NOT NULL DEFAULT '0',
  `milestone` tinyint(1) NOT NULL DEFAULT '0',
  `color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `google_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `google_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `access_token` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `refresh_token` text COLLATE utf8mb4_unicode_ci,
  `token_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Bearer',
  `expires_at` timestamp NULL DEFAULT NULL,
  `calendar_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'primary',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `google_tokens_user_id_unique` (`user_id`),
  CONSTRAINT `google_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `groupe_telepro_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `groupe_telepro_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `groupe_telepro_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupe_telepro_user_groupe_telepro_id_user_id_unique` (`groupe_telepro_id`,`user_id`),
  KEY `groupe_telepro_user_user_id_foreign` (`user_id`),
  CONSTRAINT `groupe_telepro_user_groupe_telepro_id_foreign` FOREIGN KEY (`groupe_telepro_id`) REFERENCES `groupes_telepro` (`id`) ON DELETE CASCADE,
  CONSTRAINT `groupe_telepro_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `groupes_telepro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `groupes_telepro` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `heures_formations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `heures_formations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dossier_id` bigint unsigned NOT NULL,
  `heures_obligatoires` decimal(8,2) NOT NULL DEFAULT '0.00',
  `heures_complementaires` decimal(8,2) NOT NULL DEFAULT '0.00',
  `heures_elearning` decimal(8,2) NOT NULL DEFAULT '0.00',
  `total_heures` decimal(8,2) NOT NULL DEFAULT '0.00',
  `heures_realisees` decimal(8,2) NOT NULL DEFAULT '0.00',
  `heures_restantes` decimal(8,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `heures_formations_dossier_id_index` (`dossier_id`),
  CONSTRAINT `heures_formations_dossier_id_foreign` FOREIGN KEY (`dossier_id`) REFERENCES `dossier_formations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `historique_conseillers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historique_conseillers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `partenaire_id` bigint unsigned NOT NULL,
  `ancien_conseiller_id` bigint unsigned DEFAULT NULL,
  `nouveau_conseiller_id` bigint unsigned DEFAULT NULL,
  `date_changement` date NOT NULL,
  `motif` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `historique_conseillers_nouveau_conseiller_id_foreign` (`nouveau_conseiller_id`),
  KEY `historique_conseillers_partenaire_id_index` (`partenaire_id`),
  KEY `historique_conseillers_ancien_conseiller_id_index` (`ancien_conseiller_id`),
  KEY `historique_conseillers_date_changement_index` (`date_changement`),
  CONSTRAINT `historique_conseillers_ancien_conseiller_id_foreign` FOREIGN KEY (`ancien_conseiller_id`) REFERENCES `consultants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `historique_conseillers_nouveau_conseiller_id_foreign` FOREIGN KEY (`nouveau_conseiller_id`) REFERENCES `consultants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `historique_conseillers_partenaire_id_foreign` FOREIGN KEY (`partenaire_id`) REFERENCES `partenaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `historique_interactions_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historique_interactions_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `interactable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `interactable_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `type_interaction` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'consultation',
  `description` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `date_interaction` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `interactable_idx` (`interactable_type`,`interactable_id`),
  KEY `historique_interactions_users_user_id_index` (`user_id`),
  KEY `historique_interactions_users_date_interaction_index` (`date_interaction`),
  KEY `historique_interactions_interactable_type_id` (`interactable_type`,`interactable_id`),
  CONSTRAINT `historique_interactions_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `historique_modifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historique_modifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `champ` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Champ modifié ou null pour création/suppression',
  `ancienne_valeur` json DEFAULT NULL COMMENT 'Valeur avant modification',
  `nouvelle_valeur` json DEFAULT NULL COMMENT 'Valeur après modification',
  `type_modification` enum('creation','modification','suppression','restauration') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'modification' COMMENT 'Type de modification',
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `historique_modifications_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `historique_modifications_user_id_index` (`user_id`),
  KEY `historique_modifications_type_modification_index` (`type_modification`),
  KEY `historique_modifications_champ_index` (`champ`),
  KEY `historique_modifications_date_modification_index` (`date_modification`),
  CONSTRAINT `historique_modifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `import_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `import_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sheet_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_type` enum('client','proposition') COLLATE utf8mb4_unicode_ci NOT NULL,
  `rows_imported` int NOT NULL DEFAULT '0',
  `rows_skipped` int NOT NULL DEFAULT '0',
  `rows_failed` int NOT NULL DEFAULT '0',
  `errors` json DEFAULT NULL,
  `column_mapping` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `import_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `import_mappings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mapping` json NOT NULL,
  `options` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `import_mappings_user_id_index` (`user_id`),
  KEY `import_mappings_model_type_index` (`model_type`),
  CONSTRAINT `import_mappings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `integrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `integrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `config` json NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `integrations_type_index` (`type`),
  KEY `integrations_user_id_index` (`user_id`),
  KEY `integrations_actif_index` (`actif`),
  CONSTRAINT `integrations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kpis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kpis` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'count',
  `model` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filters` json DEFAULT NULL,
  `aggregation_period` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'daily',
  `user_id` bigint unsigned DEFAULT NULL,
  `public` tinyint(1) NOT NULL DEFAULT '0',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `target_value` decimal(10,2) DEFAULT NULL,
  `target_operator` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '>=',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kpis_type_index` (`type`),
  KEY `kpis_model_index` (`model`),
  KEY `kpis_user_id_index` (`user_id`),
  KEY `kpis_public_index` (`public`),
  CONSTRAINT `kpis_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `chat_room_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `contenu` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `lu` tinyint(1) NOT NULL DEFAULT '0',
  `lu_at` timestamp NULL DEFAULT NULL,
  `reply_to` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_reply_to_foreign` (`reply_to`),
  KEY `messages_chat_room_id_created_at_index` (`chat_room_id`,`created_at`),
  KEY `messages_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `messages_chat_room_id_foreign` FOREIGN KEY (`chat_room_id`) REFERENCES `chat_rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_reply_to_foreign` FOREIGN KEY (`reply_to`) REFERENCES `messages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `milestones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `milestones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `project_id` bigint unsigned DEFAULT NULL,
  `date_prevue` date NOT NULL,
  `date_reelle` date DEFAULT NULL,
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `progress` int NOT NULL DEFAULT '0',
  `assigned_to` bigint unsigned DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `milestones_project_id_index` (`project_id`),
  KEY `milestones_date_prevue_index` (`date_prevue`),
  KEY `milestones_statut_index` (`statut`),
  KEY `milestones_assigned_to_index` (`assigned_to`),
  CONSTRAINT `milestones_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `opportunites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `opportunites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom_entite` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_pressenti` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departement` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `siret` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secteur_activite` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nb_salaries` int DEFAULT NULL,
  `chiffre_affaires` decimal(15,2) DEFAULT NULL,
  `source_detection` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details_source` text COLLATE utf8mb4_unicode_ci,
  `potentiel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nouveau',
  `interlocuteur_nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interlocuteur_fonction` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interlocuteur_telephone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interlocuteur_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigne_a` bigint unsigned DEFAULT NULL,
  `date_detection` date DEFAULT NULL,
  `date_premier_contact` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `raison_perte` text COLLATE utf8mb4_unicode_ci,
  `converti_en_prospect_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `opportunites_assigne_a_foreign` (`assigne_a`),
  KEY `opportunites_converti_en_prospect_id_foreign` (`converti_en_prospect_id`),
  CONSTRAINT `opportunites_assigne_a_foreign` FOREIGN KEY (`assigne_a`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `opportunites_converti_en_prospect_id_foreign` FOREIGN KEY (`converti_en_prospect_id`) REFERENCES `prospects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `parrains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parrains` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom_prenom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `code_postal` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ville` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `super_parrain` tinyint(1) NOT NULL DEFAULT '0',
  `date_creation` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `partenaires`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `partenaires` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entreprise` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nom_retenu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `siret` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CSE',
  `nomenclature_interne` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entreprise_mere_id` bigint unsigned DEFAULT NULL,
  `entite_id` bigint unsigned DEFAULT NULL,
  `entreprise_id` bigint unsigned DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `code_postal` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ville` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departement` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secteur_activite` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nb_salaries` int DEFAULT NULL,
  `chiffre_affaires` decimal(15,2) DEFAULT NULL,
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'a_prospecter',
  `date_modification_statut` timestamp NULL DEFAULT NULL,
  `date_signature` date DEFAULT NULL,
  `annee_signature` int DEFAULT NULL,
  `date_evaluation` date DEFAULT NULL,
  `conseiller_id` bigint unsigned DEFAULT NULL,
  `parrain_partenaire_id` bigint unsigned DEFAULT NULL,
  `origine_contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parrain_marraine` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parrain_marraine_texte` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parrainage_entreprise` tinyint(1) DEFAULT NULL,
  `nombre_ventes_liees` int NOT NULL DEFAULT '0',
  `possibilite_permanence` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `replicable` text COLLATE utf8mb4_unicode_ci,
  `syndicat_majoritaire` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `commentaires` text COLLATE utf8mb4_unicode_ci,
  `commentaire_import` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `prospect_id` bigint unsigned DEFAULT NULL,
  `commercial_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `partenaires_siret_unique` (`siret`),
  KEY `partenaires_entreprise_mere_id_foreign` (`entreprise_mere_id`),
  KEY `partenaires_parrain_partenaire_id_foreign` (`parrain_partenaire_id`),
  KEY `partenaires_statut_index` (`statut`),
  KEY `partenaires_conseiller_id_index` (`conseiller_id`),
  KEY `partenaires_entite_id_index` (`entite_id`),
  KEY `partenaires_departement_index` (`departement`),
  KEY `partenaires_type_index` (`type`),
  KEY `partenaires_prospect_id_index` (`prospect_id`),
  KEY `idx_partenaires_conseiller_statut` (`conseiller_id`,`statut`),
  KEY `partenaires_entreprise_id_index` (`entreprise_id`),
  KEY `partenaires_commercial_id_foreign` (`commercial_id`),
  KEY `partenaires_statut_commercial_id` (`statut`,`commercial_id`),
  KEY `partenaires_statut_entite_id` (`statut`,`entite_id`),
  KEY `partenaires_date_modification_statut` (`date_modification_statut`),
  CONSTRAINT `partenaires_commercial_id_foreign` FOREIGN KEY (`commercial_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `partenaires_conseiller_id_foreign` FOREIGN KEY (`conseiller_id`) REFERENCES `consultants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `partenaires_entite_id_foreign` FOREIGN KEY (`entite_id`) REFERENCES `entite_commerciales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `partenaires_entreprise_id_foreign` FOREIGN KEY (`entreprise_id`) REFERENCES `entreprises` (`id`) ON DELETE SET NULL,
  CONSTRAINT `partenaires_entreprise_mere_id_foreign` FOREIGN KEY (`entreprise_mere_id`) REFERENCES `partenaires` (`id`) ON DELETE SET NULL,
  CONSTRAINT `partenaires_parrain_partenaire_id_foreign` FOREIGN KEY (`parrain_partenaire_id`) REFERENCES `partenaires` (`id`) ON DELETE SET NULL,
  CONSTRAINT `partenaires_prospect_id_foreign` FOREIGN KEY (`prospect_id`) REFERENCES `prospects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `partner_import_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `partner_import_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sheet_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `rows_total` int NOT NULL DEFAULT '0',
  `rows_imported` int NOT NULL DEFAULT '0',
  `rows_skipped` int NOT NULL DEFAULT '0',
  `rows_failed` int NOT NULL DEFAULT '0',
  `completed_at` timestamp NULL DEFAULT NULL,
  `errors` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `partner_import_batches_user_id_foreign` (`user_id`),
  CONSTRAINT `partner_import_batches_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `partner_import_rows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `partner_import_rows` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` bigint unsigned NOT NULL,
  `row_index` int NOT NULL,
  `raw_data` json NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'imported',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `partner_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `partner_import_rows_batch_id_foreign` (`batch_id`),
  KEY `partner_import_rows_partner_id_foreign` (`partner_id`),
  CONSTRAINT `partner_import_rows_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `partner_import_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `partner_import_rows_partner_id_foreign` FOREIGN KEY (`partner_id`) REFERENCES `partenaires` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pipeline_statuts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pipeline_statuts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `couleur` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'gray',
  `icone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transitions` json DEFAULT NULL,
  `ordre` smallint unsigned NOT NULL DEFAULT '0',
  `is_terminal` tinyint(1) NOT NULL DEFAULT '0',
  `is_archive` tinyint(1) NOT NULL DEFAULT '0',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pipeline_statuts_model_type_code_unique` (`model_type`,`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `planning_formations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `planning_formations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dossier_id` bigint unsigned NOT NULL,
  `date_lancement` date DEFAULT NULL,
  `date_debut` date DEFAULT NULL,
  `date_fin_theorique` date DEFAULT NULL,
  `date_certification` date DEFAULT NULL,
  `date_questionnaire_chaud` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `planning_formations_dossier_id_index` (`dossier_id`),
  CONSTRAINT `planning_formations_dossier_id_foreign` FOREIGN KEY (`dossier_id`) REFERENCES `dossier_formations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `propositions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `propositions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source_sheet` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_client` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tiers` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `etat` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_lancement` date DEFAULT NULL,
  `date_vente` date DEFAULT NULL,
  `nb_heures_formation` int DEFAULT NULL,
  `heures_realisees` int DEFAULT NULL,
  `heures_restantes` int DEFAULT NULL,
  `date_debut_formation` date DEFAULT NULL,
  `date_fin_formation` date DEFAULT NULL,
  `consultant_formateur` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_certification` date DEFAULT NULL,
  `extra_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `propositions_ref_client_index` (`ref_client`),
  KEY `propositions_etat_date_lancement` (`etat`,`date_lancement`),
  KEY `propositions_etat_date_vente` (`etat`,`date_vente`),
  KEY `propositions_date_debut_formation` (`date_debut_formation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prospect_import_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prospect_import_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sheet_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `rows_total` int NOT NULL DEFAULT '0',
  `rows_imported` int NOT NULL DEFAULT '0',
  `rows_skipped` int NOT NULL DEFAULT '0',
  `rows_failed` int NOT NULL DEFAULT '0',
  `completed_at` timestamp NULL DEFAULT NULL,
  `errors` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prospect_import_batches_user_id_foreign` (`user_id`),
  CONSTRAINT `prospect_import_batches_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prospect_import_rows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prospect_import_rows` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` bigint unsigned NOT NULL,
  `row_index` int NOT NULL,
  `raw_data` json NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'imported',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `prospect_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prospect_import_rows_batch_id_foreign` (`batch_id`),
  KEY `prospect_import_rows_prospect_id_foreign` (`prospect_id`),
  CONSTRAINT `prospect_import_rows_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `prospect_import_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prospect_import_rows_prospect_id_foreign` FOREIGN KEY (`prospect_id`) REFERENCES `prospects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prospects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prospects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `numero_ordre` int DEFAULT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `raison_sociale` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_pressenti` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departement` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone_alt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `code_postal` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ville` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `siret` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secteur_activite` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nb_salaries` int DEFAULT NULL,
  `chiffre_affaires` decimal(15,2) DEFAULT NULL,
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AC',
  `mail1_envoye` tinyint(1) NOT NULL DEFAULT '0',
  `mail1_envoye_at` timestamp NULL DEFAULT NULL,
  `mail2_envoye` tinyint(1) NOT NULL DEFAULT '0',
  `mail2_envoye_at` timestamp NULL DEFAULT NULL,
  `ordre_priorite` int DEFAULT NULL,
  `teleprospecteur_id` bigint unsigned DEFAULT NULL,
  `commercial_id` bigint unsigned DEFAULT NULL,
  `date_premier_contact` date DEFAULT NULL,
  `rappel_planifie_at` datetime DEFAULT NULL,
  `rappel_notifie_retard_at` timestamp NULL DEFAULT NULL,
  `difficile` tinyint(1) NOT NULL DEFAULT '0',
  `difficile_at` timestamp NULL DEFAULT NULL,
  `interlocuteur_nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interlocuteur_prenom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interlocuteur_fonction` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interlocuteur_telephone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interlocuteur_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interlocuteur_add_nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interlocuteur_add_fonction` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interlocuteur_add_telephone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interlocuteur_add_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nom_interlocuteur_standard` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creneaux_permanence_cse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_general_standard` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `motif_ko` text COLLATE utf8mb4_unicode_ci,
  `qf_valide` tinyint(1) NOT NULL DEFAULT '0',
  `valide_par` bigint unsigned DEFAULT NULL,
  `qf_valide_at` datetime DEFAULT NULL,
  `cse_secretaire_nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cse_secretaire_prenom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cse_secretaire_tel_direct` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cse_secretaire_tel_perso` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cse_secretaire_email_pro` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cse_secretaire_email_perso` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cse_tresorier_nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cse_tresorier_prenom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cse_tresorier_tel_direct` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cse_tresorier_tel_perso` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cse_tresorier_email_pro` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cse_tresorier_email_perso` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cse_nb_elus` int DEFAULT NULL,
  `cse_date_fin_mandat` date DEFAULT NULL,
  `cse_existence_juridique` tinyint(1) NOT NULL DEFAULT '0',
  `cse_notes` text COLLATE utf8mb4_unicode_ci,
  `syndicat_appartenance` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `syndicat_nom_organisation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `syndicat_responsable_nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `syndicat_responsable_prenom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `syndicat_responsable_fonction` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `syndicat_tel_direct` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `syndicat_tel_perso` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `syndicat_email_pro` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `syndicat_email_perso` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `syndicat_perimetre` text COLLATE utf8mb4_unicode_ci,
  `syndicat_notes` text COLLATE utf8mb4_unicode_ci,
  `dirigeant_nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dirigeant_prenom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dirigeant_fonction` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dirigeant_telephone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dirigeant_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `campagne_id` bigint unsigned DEFAULT NULL,
  `converti_partenaire_id` bigint unsigned DEFAULT NULL,
  `rappel_std_nr_envoye_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prospects_commercial_id_foreign` (`commercial_id`),
  KEY `prospects_valide_par_foreign` (`valide_par`),
  KEY `prospects_ordre_priorite_index` (`ordre_priorite`),
  KEY `prospects_campagne_id_index` (`campagne_id`),
  KEY `prospects_converti_partenaire_id_index` (`converti_partenaire_id`),
  KEY `idx_prospects_teleprospecteur_statut` (`teleprospecteur_id`,`statut`),
  KEY `idx_prospects_statut_updated_at` (`statut`,`updated_at`),
  KEY `idx_prospects_conversion` (`teleprospecteur_id`,`converti_partenaire_id`,`updated_at`),
  KEY `prospects_statut_commercial_id` (`statut`,`commercial_id`),
  KEY `prospects_statut_teleprospecteur_id` (`statut`,`teleprospecteur_id`),
  KEY `prospects_rappel_planifie_at` (`rappel_planifie_at`),
  CONSTRAINT `prospects_campagne_id_foreign` FOREIGN KEY (`campagne_id`) REFERENCES `campagne_phonings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prospects_commercial_id_foreign` FOREIGN KEY (`commercial_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prospects_converti_partenaire_id_foreign` FOREIGN KEY (`converti_partenaire_id`) REFERENCES `partenaires` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prospects_teleprospecteur_id_foreign` FOREIGN KEY (`teleprospecteur_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prospects_valide_par_foreign` FOREIGN KEY (`valide_par`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rapport_satisfaction_p6s`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rapport_satisfaction_p6s` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `artisan_id` bigint unsigned NOT NULL,
  `operateur_id` bigint unsigned DEFAULT NULL,
  `date_appel_j1` date NOT NULL,
  `note_nps` int NOT NULL,
  `verbatim_client` text COLLATE utf8mb4_unicode_ci,
  `feedback_artisan` tinyint(1) NOT NULL DEFAULT '0',
  `statut_cloture` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rapport_satisfaction_p6s_ticket_id_foreign` (`ticket_id`),
  KEY `rapport_satisfaction_p6s_artisan_id_foreign` (`artisan_id`),
  KEY `rapport_satisfaction_p6s_operateur_id_foreign` (`operateur_id`),
  CONSTRAINT `rapport_satisfaction_p6s_artisan_id_foreign` FOREIGN KEY (`artisan_id`) REFERENCES `artisans` (`id`),
  CONSTRAINT `rapport_satisfaction_p6s_operateur_id_foreign` FOREIGN KEY (`operateur_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rapport_satisfaction_p6s_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rdv_associations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rdv_associations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rendez_vous_id` bigint unsigned NOT NULL,
  `rdvable_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rdvable_id` bigint unsigned DEFAULT NULL,
  `method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rdv_associations_rendez_vous_id_foreign` (`rendez_vous_id`),
  KEY `rdv_associations_user_id_foreign` (`user_id`),
  CONSTRAINT `rdv_associations_rendez_vous_id_foreign` FOREIGN KEY (`rendez_vous_id`) REFERENCES `rendez_vous` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rdv_associations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reclamation_p8s`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reclamation_p8s` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `rapport_satisfaction_id` bigint unsigned DEFAULT NULL,
  `date_ouverture` datetime NOT NULL,
  `description_reclamation` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ouverte',
  `date_resolution_cible` date NOT NULL,
  `date_resolution_effective` date DEFAULT NULL,
  `validation_superviseur` tinyint(1) NOT NULL DEFAULT '0',
  `superviseur_id` bigint unsigned DEFAULT NULL,
  `notes_resolution` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reclamation_p8s_ticket_id_foreign` (`ticket_id`),
  KEY `reclamation_p8s_rapport_satisfaction_id_foreign` (`rapport_satisfaction_id`),
  KEY `reclamation_p8s_superviseur_id_foreign` (`superviseur_id`),
  CONSTRAINT `reclamation_p8s_rapport_satisfaction_id_foreign` FOREIGN KEY (`rapport_satisfaction_id`) REFERENCES `rapport_satisfaction_p6s` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reclamation_p8s_superviseur_id_foreign` FOREIGN KEY (`superviseur_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reclamation_p8s_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `remboursements_employeur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `remboursements_employeur` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `partenaire_id` bigint unsigned NOT NULL,
  `date_demande` date DEFAULT NULL,
  `montant` decimal(8,2) NOT NULL DEFAULT '100.00' COMMENT 'Montant fixe 100 €',
  `commentaires` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `remboursements_employeur_partenaire_id_index` (`partenaire_id`),
  CONSTRAINT `remboursements_employeur_partenaire_id_foreign` FOREIGN KEY (`partenaire_id`) REFERENCES `partenaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rendez_vous`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rendez_vous` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rdvable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rdvable_id` bigint unsigned NOT NULL,
  `commercial_id` bigint unsigned DEFAULT NULL,
  `teleprospecteur_id` bigint unsigned DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Appel',
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Planifié',
  `date_heure` datetime NOT NULL,
  `lieu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse_lieu` text COLLATE utf8mb4_unicode_ci,
  `interlocuteur_nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interlocuteur_tel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interlocuteur_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `pdf_recap` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enregistrement_audio` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_confirmation_envoye` tinyint(1) NOT NULL DEFAULT '0',
  `email_invitation_envoye` tinyint(1) NOT NULL DEFAULT '0',
  `rappel_envoye_at` timestamp NULL DEFAULT NULL,
  `outlook_event_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_event_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rendez_vous_rdvable_type_rdvable_id_index` (`rdvable_type`,`rdvable_id`),
  KEY `rendez_vous_teleprospecteur_id_foreign` (`teleprospecteur_id`),
  KEY `idx_rendez_vous_commercial_statut_date` (`commercial_id`,`statut`,`date_heure`),
  KEY `rendez_vous_statut_commercial_id` (`statut`,`commercial_id`),
  KEY `rendez_vous_statut_teleprospecteur_id` (`statut`,`teleprospecteur_id`),
  KEY `rendez_vous_date_heure_statut` (`date_heure`,`statut`),
  KEY `rendez_vous_rdvable_type_id` (`rdvable_type`,`rdvable_id`),
  CONSTRAINT `rendez_vous_commercial_id_foreign` FOREIGN KEY (`commercial_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rendez_vous_teleprospecteur_id_foreign` FOREIGN KEY (`teleprospecteur_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'custom',
  `config` json NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `public` tinyint(1) NOT NULL DEFAULT '0',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reports_type_index` (`type`),
  KEY `reports_created_by_index` (`created_by`),
  KEY `reports_public_index` (`public`),
  CONSTRAINT `reports_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ringover_api_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ringover_api_keys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'standard',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `user_id` bigint unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `click_to_call_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ringover_api_keys_api_key_unique` (`api_key`),
  KEY `ringover_api_keys_user_id_foreign` (`user_id`),
  CONSTRAINT `ringover_api_keys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `resource` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fields` json DEFAULT NULL,
  `autorise` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permissions_role_id_resource_action_unique` (`role_id`,`resource`,`action`),
  KEY `role_permissions_role_id_index` (`role_id`),
  KEY `role_permissions_resource_index` (`resource`),
  CONSTRAINT `role_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sent_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sent_emails` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `emailable_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emailable_id` bigint unsigned DEFAULT NULL,
  `template_cle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sujet` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destinataire` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `cc` text COLLATE utf8mb4_unicode_ci,
  `corps` longtext COLLATE utf8mb4_unicode_ci,
  `envoye_par` bigint unsigned DEFAULT NULL,
  `envoye_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sent_emails_emailable_type_emailable_id_index` (`emailable_type`,`emailable_id`),
  KEY `sent_emails_envoye_par_foreign` (`envoye_par`),
  KEY `sent_emails_emailable_type_id` (`emailable_type`,`emailable_id`),
  CONSTRAINT `sent_emails_envoye_par_foreign` FOREIGN KEY (`envoye_par`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `statut_phonings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `statut_phonings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `groupe` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `groupe_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_immediate` text COLLATE utf8mb4_unicode_ci,
  `note_obligatoire` tinyint(1) NOT NULL DEFAULT '0',
  `message_note_obligatoire` text COLLATE utf8mb4_unicode_ci,
  `delai_rappel_jours` tinyint unsigned DEFAULT NULL,
  `prioritaire` tinyint(1) NOT NULL DEFAULT '0',
  `fiche_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pipeline_statut` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `compte_comme_tentative` tinyint(1) NOT NULL DEFAULT '0',
  `retire_de_file` tinyint(1) NOT NULL DEFAULT '0',
  `couleur` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'gray',
  `icone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 0xF09F939E,
  `ordre` int NOT NULL DEFAULT '0',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `statut_phonings_model_type_code_unique` (`model_type`,`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `taggables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `taggables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tag_id` bigint unsigned NOT NULL,
  `taggable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taggable_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `taggables_tag_id_taggable_type_taggable_id_unique` (`tag_id`,`taggable_type`,`taggable_id`),
  KEY `taggables_created_by_foreign` (`created_by`),
  KEY `taggables_taggable_type_taggable_id_index` (`taggable_type`,`taggable_id`),
  KEY `taggables_tag_id_index` (`tag_id`),
  CONSTRAINT `taggables_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `taggables_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `couleur` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'gray',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_slug_unique` (`slug`),
  KEY `tags_created_by_foreign` (`created_by`),
  KEY `tags_slug_index` (`slug`),
  CONSTRAINT `tags_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tarifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tarifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `partenaire_id` bigint unsigned NOT NULL,
  `prix_pc` decimal(10,2) DEFAULT NULL,
  `part_aopia` decimal(10,2) DEFAULT NULL,
  `tarifs` decimal(8,2) DEFAULT NULL COMMENT 'Tarifs = prix net salarié (colonne Excel)',
  `tarifs_affichage_comm` decimal(8,2) DEFAULT NULL COMMENT 'Tarifs à afficher sur la comm partenaire',
  `part_cse` decimal(10,2) DEFAULT NULL,
  `part_salarie` decimal(10,2) DEFAULT NULL,
  `adresse_facturation` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tarifications_partenaire_id_index` (`partenaire_id`),
  CONSTRAINT `tarifications_partenaire_id_foreign` FOREIGN KEY (`partenaire_id`) REFERENCES `partenaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tache',
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'a_faire',
  `priorite` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normale',
  `date_echeance` datetime DEFAULT NULL,
  `date_realisation` datetime DEFAULT NULL,
  `rappel_envoye` tinyint(1) NOT NULL DEFAULT '0',
  `date_rappel` timestamp NULL DEFAULT NULL,
  `assigne_a` bigint unsigned DEFAULT NULL,
  `prospect_id` bigint unsigned DEFAULT NULL,
  `partenaire_id` bigint unsigned DEFAULT NULL,
  `client_id` bigint unsigned DEFAULT NULL,
  `rendez_vous_id` bigint unsigned DEFAULT NULL,
  `opportunite_id` bigint unsigned DEFAULT NULL,
  `appel_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_assigne_a_foreign` (`assigne_a`),
  KEY `tasks_prospect_id_foreign` (`prospect_id`),
  KEY `tasks_partenaire_id_foreign` (`partenaire_id`),
  KEY `tasks_client_id_foreign` (`client_id`),
  CONSTRAINT `tasks_assigne_a_foreign` FOREIGN KEY (`assigne_a`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_partenaire_id_foreign` FOREIGN KEY (`partenaire_id`) REFERENCES `partenaires` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_prospect_id_foreign` FOREIGN KEY (`prospect_id`) REFERENCES `prospects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_entries` (
  `sequence` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `family_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `should_display_on_index` tinyint(1) NOT NULL DEFAULT '1',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`sequence`),
  UNIQUE KEY `telescope_entries_uuid_unique` (`uuid`),
  KEY `telescope_entries_batch_id_index` (`batch_id`),
  KEY `telescope_entries_family_hash_index` (`family_hash`),
  KEY `telescope_entries_created_at_index` (`created_at`),
  KEY `telescope_entries_type_should_display_on_index_index` (`type`,`should_display_on_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_entries_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_entries_tags` (
  `entry_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tag` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`entry_uuid`,`tag`),
  KEY `telescope_entries_tags_tag_index` (`tag`),
  CONSTRAINT `telescope_entries_tags_entry_uuid_foreign` FOREIGN KEY (`entry_uuid`) REFERENCES `telescope_entries` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_monitoring`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_monitoring` (
  `tag` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `templates_fiches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `templates_fiches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bleue',
  `description` text COLLATE utf8mb4_unicode_ci,
  `fichier_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `variables` json DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `templates_fiches_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `themes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `themes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `panel` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ns-conseil',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `primary_color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'blue',
  `success_color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'emerald',
  `warning_color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'amber',
  `danger_color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rose',
  `info_color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sky',
  `gray_color` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'slate',
  `primary_color_dark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `success_color_dark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warning_color_dark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `danger_color_dark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `info_color_dark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gray_color_dark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brand_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brand_logo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `favicon_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custom_css` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `themes_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_particulier_id` bigint unsigned NOT NULL,
  `artisan_id` bigint unsigned DEFAULT NULL,
  `operateur_id` bigint unsigned DEFAULT NULL,
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'appel_recu',
  `niveau_priorite` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `corps_de_metier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` datetime NOT NULL,
  `date_cloture` datetime DEFAULT NULL,
  `rdv_planifie_at` datetime DEFAULT NULL,
  `debut_intervention_at` timestamp NULL DEFAULT NULL COMMENT 'Date et heure réelle de début d''intervention',
  `fin_intervention_at` timestamp NULL DEFAULT NULL COMMENT 'Date et heure réelle de fin d''intervention',
  `duree_reelle_minutes` int DEFAULT NULL COMMENT 'Durée réelle de l''intervention en minutes',
  `rappel_promise_at` datetime DEFAULT NULL,
  `ringover_call_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_appel` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Source de l''appel (via CTI/téléphonie) : web, mobile, partenaire, etc.',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tickets_reference_unique` (`reference`),
  KEY `tickets_contact_particulier_id_foreign` (`contact_particulier_id`),
  KEY `tickets_artisan_id_foreign` (`artisan_id`),
  KEY `tickets_operateur_id_foreign` (`operateur_id`),
  KEY `tickets_statut_index` (`statut`),
  KEY `tickets_niveau_priorite_index` (`niveau_priorite`),
  KEY `tickets_date_creation_index` (`date_creation`),
  KEY `tickets_source_appel_index` (`source_appel`),
  CONSTRAINT `tickets_artisan_id_foreign` FOREIGN KEY (`artisan_id`) REFERENCES `artisans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_contact_particulier_id_foreign` FOREIGN KEY (`contact_particulier_id`) REFERENCES `contact_particuliers` (`id`),
  CONSTRAINT `tickets_operateur_id_foreign` FOREIGN KEY (`operateur_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `time_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `time_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `task_id` bigint unsigned DEFAULT NULL,
  `prospect_id` bigint unsigned DEFAULT NULL,
  `client_id` bigint unsigned DEFAULT NULL,
  `partenaire_id` bigint unsigned DEFAULT NULL,
  `opportunite_id` bigint unsigned DEFAULT NULL,
  `rendez_vous_id` bigint unsigned DEFAULT NULL,
  `appel_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` timestamp NOT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `duration` int DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'work',
  `billable` tinyint(1) NOT NULL DEFAULT '1',
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `time_entries_task_id_foreign` (`task_id`),
  KEY `time_entries_prospect_id_foreign` (`prospect_id`),
  KEY `time_entries_client_id_foreign` (`client_id`),
  KEY `time_entries_partenaire_id_foreign` (`partenaire_id`),
  KEY `time_entries_opportunite_id_foreign` (`opportunite_id`),
  KEY `time_entries_rendez_vous_id_foreign` (`rendez_vous_id`),
  KEY `time_entries_appel_id_foreign` (`appel_id`),
  KEY `time_entries_user_id_index` (`user_id`),
  KEY `time_entries_start_time_index` (`start_time`),
  KEY `time_entries_end_time_index` (`end_time`),
  KEY `time_entries_type_index` (`type`),
  CONSTRAINT `time_entries_appel_id_foreign` FOREIGN KEY (`appel_id`) REFERENCES `appels` (`id`) ON DELETE SET NULL,
  CONSTRAINT `time_entries_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `time_entries_opportunite_id_foreign` FOREIGN KEY (`opportunite_id`) REFERENCES `opportunites` (`id`) ON DELETE SET NULL,
  CONSTRAINT `time_entries_partenaire_id_foreign` FOREIGN KEY (`partenaire_id`) REFERENCES `partenaires` (`id`) ON DELETE SET NULL,
  CONSTRAINT `time_entries_prospect_id_foreign` FOREIGN KEY (`prospect_id`) REFERENCES `prospects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `time_entries_rendez_vous_id_foreign` FOREIGN KEY (`rendez_vous_id`) REFERENCES `rendez_vous` (`id`) ON DELETE SET NULL,
  CONSTRAINT `time_entries_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `time_entries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_dashboards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_dashboards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `par_defaut` tinyint(1) NOT NULL DEFAULT '0',
  `widgets_config` json NOT NULL,
  `layout_config` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_dashboards_user_id_nom_unique` (`user_id`,`nom`),
  KEY `user_dashboards_user_id_index` (`user_id`),
  CONSTRAINT `user_dashboards_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_views` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `resource` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `config` json NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_views_user_id_resource_name_unique` (`user_id`,`resource`,`name`),
  KEY `user_views_user_id_resource_index` (`user_id`,`resource`),
  CONSTRAINT `user_views_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_last_sync` timestamp NULL DEFAULT NULL,
  `ringover_user_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ringover_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secteur` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `role_cache` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_token` json DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `theme_preference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'light',
  `theme_mode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'light',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_ringover_user_id_unique` (`ringover_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhooks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `description` text COLLATE utf8mb4_unicode_ci,
  `headers` json DEFAULT NULL,
  `secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `webhooks_user_id_foreign` (`user_id`),
  CONSTRAINT `webhooks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workflow_groupes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_groupes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'prospect',
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ordre` smallint unsigned NOT NULL DEFAULT '0',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `workflow_groupes_model_type_code_unique` (`model_type`,`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workflow_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `workflow_instance_id` bigint unsigned NOT NULL,
  `from_step_id` bigint unsigned DEFAULT NULL,
  `to_step_id` bigint unsigned DEFAULT NULL,
  `commentaire` text COLLATE utf8mb4_unicode_ci,
  `user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_histories_from_step_id_foreign` (`from_step_id`),
  KEY `workflow_histories_to_step_id_foreign` (`to_step_id`),
  KEY `workflow_histories_workflow_instance_id_index` (`workflow_instance_id`),
  KEY `workflow_histories_user_id_index` (`user_id`),
  CONSTRAINT `workflow_histories_from_step_id_foreign` FOREIGN KEY (`from_step_id`) REFERENCES `workflow_steps` (`id`) ON DELETE SET NULL,
  CONSTRAINT `workflow_histories_to_step_id_foreign` FOREIGN KEY (`to_step_id`) REFERENCES `workflow_steps` (`id`) ON DELETE SET NULL,
  CONSTRAINT `workflow_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `workflow_histories_workflow_instance_id_foreign` FOREIGN KEY (`workflow_instance_id`) REFERENCES `workflow_instances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workflow_instances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_instances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `workflow_groupe_id` bigint unsigned NOT NULL,
  `current_step_id` bigint unsigned DEFAULT NULL,
  `instanceable_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instanceable_id` bigint unsigned DEFAULT NULL,
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_cours',
  `date_debut` datetime DEFAULT NULL,
  `date_fin` datetime DEFAULT NULL,
  `initiated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_instances_initiated_by_foreign` (`initiated_by`),
  KEY `workflow_instances_instanceable_type_instanceable_id_index` (`instanceable_type`,`instanceable_id`),
  KEY `workflow_instances_workflow_groupe_id_index` (`workflow_groupe_id`),
  KEY `workflow_instances_current_step_id_index` (`current_step_id`),
  KEY `workflow_instances_statut_index` (`statut`),
  CONSTRAINT `workflow_instances_current_step_id_foreign` FOREIGN KEY (`current_step_id`) REFERENCES `workflow_steps` (`id`) ON DELETE SET NULL,
  CONSTRAINT `workflow_instances_initiated_by_foreign` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `workflow_instances_workflow_groupe_id_foreign` FOREIGN KEY (`workflow_groupe_id`) REFERENCES `workflow_groupes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workflow_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_steps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `workflow_groupe_id` bigint unsigned NOT NULL,
  `parent_step_id` bigint unsigned DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'task',
  `ordre` int NOT NULL DEFAULT '0',
  `config` json DEFAULT NULL,
  `condition_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `est_final` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `workflow_steps_code_unique` (`code`),
  KEY `workflow_steps_workflow_groupe_id_ordre_index` (`workflow_groupe_id`,`ordre`),
  KEY `workflow_steps_parent_step_id_foreign` (`parent_step_id`),
  CONSTRAINT `workflow_steps_parent_step_id_foreign` FOREIGN KEY (`parent_step_id`) REFERENCES `workflow_steps` (`id`) ON DELETE SET NULL,
  CONSTRAINT `workflow_steps_workflow_groupe_id_foreign` FOREIGN KEY (`workflow_groupe_id`) REFERENCES `workflow_groupes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workflows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflows` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2019_12_14_000001_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_05_29_104703_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_05_29_104934_create_entite_commerciales_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_05_29_104936_create_consultants_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_05_29_104937_create_partenaires_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_05_29_105359_create_contact_partenaires_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_05_29_105430_create_documents_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_05_29_105619_create_appels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_05_29_105915_create_rendez_vous_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_05_29_110156_create_prospects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_05_29_110437_create_opportunites_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_05_29_110719_create_clients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_05_29_111011_create_artisans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_05_29_111238_create_contact_particuliers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_05_29_111517_create_tickets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_05_29_111659_create_fiche__p2_s_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_05_29_112101_create_rapport_satisfaction_p6_s_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_05_29_112659_create_reclamation_p8_s_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_05_29_112942_create_artisan_prospections_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_05_29_140510_create_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_05_29_140828_create_telescope_entries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_05_31_090440_add_aircall_fields_to_appels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_05_31_092727_make_user_id_nullable_in_appels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_05_31_093238_make_appelable_nullable_and_fix_enregistrement_in_appels',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_05_31_093710_add_aircall_user_id_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_05_31_095336_add_aircall_agent_nom_to_appels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_05_31_111054_add_phoning_fields_to_appels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_06_01_084359_google_tokens',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_06_02_084649_create_devis_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_06_02_084659_create_bon_de_commandes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_06_02_084708_create_factures_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_06_02_084714_add_financial_fields_to_artisans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_06_02_084722_add_contact_fields_to_contact_particuliers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_06_02_084728_add_source_appel_to_tickets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_06_02_155005_create_field_visibility_table[_c',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_06_05_094709_add_scripts_appel',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_06_09_093752_add_ordre_priorite_to_prospects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_06_12_000001_add_missing_fields_to_fiche_p2s_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_06_12_000002_add_cti_numero_to_artisans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_06_12_000003_create_affaire_interventions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_06_16_000002_create_parrains_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_06_16_000004_create_campagne_phonings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_06_16_000005_create_adresse_cses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_06_16_000006_create_tarifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_06_16_000007_create_activite_partenaires_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_06_16_000008_create_dossier_formations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_06_16_000009_create_heures_formations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_06_16_000010_create_planning_formations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_06_16_000011_add_fields_to_prospects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_06_16_000012_add_fields_to_partenaires_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_06_16_000013_add_fields_to_contact_partenaires_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_06_16_000014_add_fields_to_clients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_06_16_000015_create_historique_conseillers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_06_16_000016_create_autres_interlocuteurs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_06_16_000017_create_activite_ventes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_06_16_000018_create_activite_permanences_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_06_16_000019_create_remboursements_employeur_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_06_16_000020_alter_partenaires_add_mea_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_06_16_000021_alter_contact_partenaires_add_role',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_06_16_000022_alter_tarifications_add_tarifs_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_06_16_000023_alter_consultants_rename_statut_vdi',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_06_16_123543_update_partenaire',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_06_16_144033_alter_campagne_phonings_table_add_new_fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_06_16_153624_add_campagne_id_to_scripts_appel',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_06_16_160208_add_campagne_id_to_appels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_06_17_000001_create_statut_phonings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_06_17_000002_add_standard_interlocutor_to_prospects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_06_17_000003_add_fiche_fields_to_appels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_06_17_090001_create_prospect_import_batches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_06_17_090002_create_prospect_import_rows_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_06_17_090003_create_partner_import_batches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_06_17_090004_create_partner_import_rows_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_06_17_100000_add_cse_workflow_fields_to_statut_phonings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_06_17_110000_create_crm_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_06_17_110001_create_crm_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_06_17_110002_create_pipeline_statuts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_06_17_110003_create_workflow_groupes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_06_17_110004_add_mapping_fields_to_statut_phonings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_06_17_150000_create_fiche_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2026_06_18_083859_create_email_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_06_18_083907_create_sent_emails_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_06_18_084003_add_mail_flags_to_prospects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_06_24_075556_create_templates_fiches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_06_24_075819_add_fiche_word_fields_to_appels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_06_24_090000_add_fiche_jaune_j7_envoye_at_to_appels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2026_06_24_131046_add_commercial_and_notes_to_clients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2026_06_24_131307_create_tasks_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2026_06_24_132930_add_rappel_std_nr_to_prospects_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2026_06_25_180317_create_historique_interactions_users_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2026_06_25_190626_create_document_knowledges_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2026_06_27_065653_add_indexes_for_stats_widget_optimization',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2026_06_27_120000_rename_aircall_columns_to_ringover',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2026_06_27_130000_add_ringover_tags_and_webhook_fields_to_appels_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2026_06_27_144121_add_theme_preferences_to_users_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2026_06_27_144601_create_themes_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2026_06_27_155832_create_settings_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2026_06_27_155955_add_crm_choice_to_crm_settings_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2026_06_28_070806_create_workflow_steps_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2026_06_28_181843_modify_clients_for_multiple_ref_clients',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2026_06_28_182952_create_user_views_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2026_06_28_183520_create_entity_relations_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2026_06_28_183812_create_entreprises_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2026_06_28_183915_add_entreprise_id_to_partenaires_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2026_06_28_185433_create_field_permissions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2026_06_29_073922_add_theme_id_to_crm_profiles_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2026_06_29_121226_add_parent_step_to_workflow_steps_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2026_06_29_130000_add_commercial_id_to_partenaires_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2026_06_29_135629_create_custom_fields_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2026_06_29_135645_create_custom_field_values_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2026_07_01_075801_create_ringover_api_keys_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2026_07_01_103133_create_webhooks_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2026_07_02_071506_add_click_to_call_url_to_ringover_api_keys_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2026_07_03_071144_create_env_settings_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2026_07_17_000001_drop_dirigeant_cse_syndicat_columns_from_partenaires_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2026_07_17_000002_drop_date_convention_from_partenaires_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2026_07_17_000003_drop_role_from_contact_partenaires_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2026_07_17_000004_add_interlocuteur_prenom_to_prospects_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2026_07_17_000005_create_groupes_telepro_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2026_07_17_000006_add_groupe_telepro_id_to_users_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2026_07_17_000007_add_groupe_telepro_id_to_campagne_phonings_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2026_07_17_000008_drop_scripts_appel_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2026_07_18_000002_add_rappel_envoye_at_to_rendez_vous_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2026_07_21_000001_add_difficile_to_prospects_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2026_07_21_000001_create_groupe_telepro_user_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2026_07_27_120000_add_fiche_verte_envoyee_at_to_appels_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2026_07_28_100640_add_rappel_notifie_retard_at_to_prospects_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2026_07_31_122227_add_advanced_rules_to_campagne_phonings_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2026_08_01_000001_create_emails_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2026_08_01_000002_add_email_password_to_users_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2026_08_01_000003_create_email_configurations_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2026_08_01_000004_add_compte_comme_tentative_to_appels_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2026_08_01_000005_create_historique_modifications_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2026_08_01_000006_add_timestamps_to_historique_modifications_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2026_08_03_000001_add_strategic_indexes',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2026_08_03_000002_add_morph_relation_indexes',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2026_08_03_113957_encrypt_user_sensitive_fields',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2026_08_04_181938_add_interlocuteur_add_fields_to_prospects_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2026_08_05_000001_add_corps_plain_to_email_templates',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2026_08_05_000002_create_rdv_associations_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2026_08_05_000003_add_contenu_to_document_knowledges_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2026_08_09_000002_create_workflow_instances_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2026_08_09_000003_create_workflow_histories_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2026_08_09_000004_create_user_dashboards_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2026_08_09_000005_create_import_mappings_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2026_08_09_000006_create_tags_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2026_08_09_000007_create_taggables_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2026_08_09_000008_create_notifications_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2026_08_09_000009_create_document_versions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2026_08_09_000010_create_comments_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2026_08_09_000011_create_api_tokens_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2026_08_09_000013_create_campagnes_marketing_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2026_08_09_000014_create_evenements_calendrier_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2026_08_09_000015_create_role_permissions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2026_08_09_171429_create_telescope_entries_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2026_08_09_193649_add_est_final_to_workflow_steps_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2026_08_09_205655_create_chat_rooms_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2026_08_09_205656_create_messages_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2026_08_09_205717_create_chat_room_participants_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2026_08_10_033356_create_reports_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2026_08_10_033824_create_time_entries_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2026_08_10_033929_create_kpis_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2026_08_10_034157_create_integrations_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2026_08_10_041720_add_two_factor_auth_to_users_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2026_08_10_041837_create_gantt_tasks_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2026_08_10_042137_create_audit_logs_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2026_08_10_042308_create_data_deletion_requests_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2026_08_10_042756_create_backups_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2026_08_10_043020_create_analytic_dashboards_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2026_08_10_043741_create_milestones_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2026_08_10_043830_create_campaigns_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2026_08_10_044340_add_priority_and_reminders_to_tasks_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2026_08_10_044758_create_workflows_table',32);
