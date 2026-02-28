-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : sam. 28 fév. 2026 à 06:54
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `uscoud_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `securite_pv_logs`
--

DROP TABLE IF EXISTS `securite_pv_logs`;
CREATE TABLE IF NOT EXISTS `securite_pv_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_concernee` varchar(100) NOT NULL,
  `id_enregistrement` int DEFAULT NULL,
  `ancienne_valeurs` text,
  `nouvelles_valeurs` text,
  `adresse_ip` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_utilisateur` (`id_utilisateur`),
  KEY `idx_action` (`action`),
  KEY `idx_table` (`table_concernee`),
  KEY `idx_date` (`created_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `uscoud_pv_assaillants`
--

DROP TABLE IF EXISTS `uscoud_pv_assaillants`;
CREATE TABLE IF NOT EXISTS `uscoud_pv_assaillants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_constat` int NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenoms` varchar(150) NOT NULL,
  `description_physique` text,
  `signes_distinctifs` text,
  `statut` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_constat` (`id_constat`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `uscoud_pv_auditions`
--

DROP TABLE IF EXISTS `uscoud_pv_auditions`;
CREATE TABLE IF NOT EXISTS `uscoud_pv_auditions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_constat` int NOT NULL,
  `temoin_nom` varchar(100) NOT NULL,
  `temoin_prenoms` varchar(150) NOT NULL,
  `temoin_telephone` varchar(20) DEFAULT NULL,
  `temoin_statut` enum('etudiant','personnel','externe') NOT NULL,
  `declaration` text,
  `date_audition` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_constat` (`id_constat`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `uscoud_pv_blesses`
--

DROP TABLE IF EXISTS `uscoud_pv_blesses`;
CREATE TABLE IF NOT EXISTS `uscoud_pv_blesses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_constat` int NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenoms` varchar(150) NOT NULL,
  `type_blessure` varchar(100) DEFAULT NULL,
  `description_blessure` text,
  `evacuation` tinyint(1) DEFAULT '0',
  `hopital` varchar(200) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_constat` (`id_constat`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `uscoud_pv_constat`
--

DROP TABLE IF EXISTS `uscoud_pv_constat`;
CREATE TABLE IF NOT EXISTS `uscoud_pv_constat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_pv` varchar(50) NOT NULL,
  `id_etudiant` int DEFAULT NULL,
  `carte_etudiant` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenoms` varchar(150) NOT NULL,
  `campus` varchar(100) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `type_incident` enum('vol','agression','degradation','perte','incendie','autre') NOT NULL,
  `description_incident` text NOT NULL,
  `lieu_incident` varchar(200) NOT NULL,
  `date_incident` datetime NOT NULL,
  `heure_incident` time DEFAULT NULL,
  `suites_blesses` text,
  `suites_dommages` text,
  `suites_assaillants` text,
  `observations` text,
  `statut` enum('en_cours','traite','archive') DEFAULT 'en_cours',
  `id_agent` int DEFAULT NULL,
  `date_cloture` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_pv` (`numero_pv`),
  KEY `id_etudiant` (`id_etudiant`),
  KEY `id_agent` (`id_agent`),
  KEY `idx_constat_carte_etudiant` (`carte_etudiant`),
  KEY `idx_constat_statut` (`statut`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déclencheurs `uscoud_pv_constat`
--
DROP TRIGGER IF EXISTS `generer_numero_pv_constat`;
DELIMITER $$
CREATE TRIGGER `generer_numero_pv_constat` BEFORE INSERT ON `uscoud_pv_constat` FOR EACH ROW BEGIN
    IF NEW.numero_pv IS NULL OR NEW.numero_pv = '' THEN
        SET NEW.numero_pv = CONCAT('PV-CONSTAT-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD((SELECT COUNT(*) + 1 FROM uscoud_pv_constat WHERE DATE(created_at) = CURDATE()), 3, '0'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `uscoud_pv_denonciation`
--

DROP TABLE IF EXISTS `uscoud_pv_denonciation`;
CREATE TABLE IF NOT EXISTS `uscoud_pv_denonciation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_pv` varchar(50) NOT NULL,
  `id_etudiant` int DEFAULT NULL,
  `denonciateur_nom` varchar(100) NOT NULL,
  `denonciateur_prenoms` varchar(150) NOT NULL,
  `denonciateur_telephone` varchar(20) DEFAULT NULL,
  `denonciateur_email` varchar(150) DEFAULT NULL,
  `denonciateur_adresse` varchar(300) DEFAULT NULL,
  `denonciateur_anonyme` tinyint(1) DEFAULT '0',
  `type_denonciation` enum('violence','harcelement','diffamation','vol','fraude','autre') NOT NULL,
  `motif_denonciation` text NOT NULL,
  `description_denonciation` text NOT NULL,
  `date_denonciation` date NOT NULL,
  `date_faits` date DEFAULT NULL,
  `lieu_faits` varchar(200) DEFAULT NULL,
  `statut` enum('en_attente','en_cours','traite','archive') DEFAULT 'en_attente',
  `id_agent` int DEFAULT NULL,
  `date_cloture` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_pv` (`numero_pv`),
  KEY `id_etudiant` (`id_etudiant`),
  KEY `id_agent` (`id_agent`),
  KEY `idx_denonciation_statut` (`statut`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déclencheurs `uscoud_pv_denonciation`
--
DROP TRIGGER IF EXISTS `generer_numero_pv_denonciation`;
DELIMITER $$
CREATE TRIGGER `generer_numero_pv_denonciation` BEFORE INSERT ON `uscoud_pv_denonciation` FOR EACH ROW BEGIN
    IF NEW.numero_pv IS NULL OR NEW.numero_pv = '' THEN
        SET NEW.numero_pv = CONCAT('PV-DENON-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD((SELECT COUNT(*) + 1 FROM uscoud_pv_denonciation WHERE DATE(created_at) = CURDATE()), 3, '0'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `uscoud_pv_dommages`
--

DROP TABLE IF EXISTS `uscoud_pv_dommages`;
CREATE TABLE IF NOT EXISTS `uscoud_pv_dommages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_constat` int NOT NULL,
  `type_domage` varchar(100) DEFAULT NULL,
  `description_domage` text NOT NULL,
  `estimation_valeur` decimal(10,2) DEFAULT NULL,
  `proprietaire` varchar(200) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_constat` (`id_constat`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `uscoud_pv_etudiants`
--

DROP TABLE IF EXISTS `uscoud_pv_etudiants`;
CREATE TABLE IF NOT EXISTS `uscoud_pv_etudiants` (
  `id_etu` int NOT NULL AUTO_INCREMENT,
  `etablissement` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `departement` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `niveauFormation` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `num_etu` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenoms` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateNaissance` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lieuNaissance` varchar(35) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sexe` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nationalite` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numIdentite` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `typeEtudiant` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'NR',
  `moyenne` int DEFAULT NULL,
  `sessionId` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Session1',
  `niveau` int DEFAULT NULL,
  `email_perso` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `email_ucad` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` int DEFAULT NULL,
  `var` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `annee` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '2025',
  `regime` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'non-payant',
  PRIMARY KEY (`id_etu`),
  UNIQUE KEY `num_etu` (`num_etu`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `uscoud_pv_faux`
--

DROP TABLE IF EXISTS `uscoud_pv_faux`;
CREATE TABLE IF NOT EXISTS `uscoud_pv_faux` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_pv` varchar(50) NOT NULL,
  `id_etudiant` int DEFAULT NULL,
  `carte_etudiant` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenoms` varchar(150) NOT NULL,
  `campus` varchar(100) NOT NULL,
  `telephone_principal` varchar(20) NOT NULL,
  `telephone_resistant` varchar(20) DEFAULT NULL,
  `identite_faux` varchar(200) DEFAULT NULL,
  `empreinte` varchar(255) DEFAULT NULL,
  `type_document` enum('carte_etudiant','cni','passeport','permis','autre') NOT NULL,
  `charge_enquete` text,
  `agent_action` text,
  `observations` text,
  `statut` enum('en_cours','traite','archive') DEFAULT 'en_cours',
  `date_pv` date NOT NULL,
  `id_agent` int DEFAULT NULL,
  `date_cloture` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_pv` (`numero_pv`),
  KEY `id_etudiant` (`id_etudiant`),
  KEY `id_agent` (`id_agent`),
  KEY `idx_faux_carte_etudiant` (`carte_etudiant`),
  KEY `idx_faux_statut` (`statut`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déclencheurs `uscoud_pv_faux`
--
DROP TRIGGER IF EXISTS `generer_numero_pv_faux`;
DELIMITER $$
CREATE TRIGGER `generer_numero_pv_faux` BEFORE INSERT ON `uscoud_pv_faux` FOR EACH ROW BEGIN
    IF NEW.numero_pv IS NULL OR NEW.numero_pv = '' THEN
        SET NEW.numero_pv = CONCAT('PV-FAUX-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD((SELECT COUNT(*) + 1 FROM uscoud_pv_faux WHERE DATE(created_at) = CURDATE()), 3, '0'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `uscoud_pv_logs`
--

DROP TABLE IF EXISTS `uscoud_pv_logs`;
CREATE TABLE IF NOT EXISTS `uscoud_pv_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_concernee` varchar(100) DEFAULT NULL,
  `id_enregistrement` int DEFAULT NULL,
  `ancienne_valeurs` json DEFAULT NULL,
  `nouvelles_valeurs` json DEFAULT NULL,
  `adresse_ip` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_utilisateur` (`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `uscoud_pv_preuves_denonciation`
--

DROP TABLE IF EXISTS `uscoud_pv_preuves_denonciation`;
CREATE TABLE IF NOT EXISTS `uscoud_pv_preuves_denonciation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_denonciation` int NOT NULL,
  `type_preuve` enum('document','photo','video','temoignage','autre') NOT NULL,
  `description_preuve` text,
  `chemin_fichier` varchar(500) DEFAULT NULL,
  `date_preuve` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_denonciation` (`id_denonciation`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `uscoud_pv_rapports`
--

DROP TABLE IF EXISTS `uscoud_pv_rapports`;
CREATE TABLE IF NOT EXISTS `uscoud_pv_rapports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type_rapport` enum('journalier','hebdomadaire','mensuel','annuel') NOT NULL,
  `periode_rapport` date NOT NULL,
  `total_faux` int DEFAULT '0',
  `total_constat` int DEFAULT '0',
  `total_denonciation` int DEFAULT '0',
  `faux_traites` int DEFAULT '0',
  `constats_traites` int DEFAULT '0',
  `denonciations_traites` int DEFAULT '0',
  `id_agent_createur` int DEFAULT NULL,
  `fichier_rapport` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_agent_createur` (`id_agent_createur`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `uscoud_pv_temoignages`
--

DROP TABLE IF EXISTS `uscoud_pv_temoignages`;
CREATE TABLE IF NOT EXISTS `uscoud_pv_temoignages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_constat` int NOT NULL,
  `temoin_nom` varchar(100) NOT NULL,
  `temoin_prenoms` varchar(150) NOT NULL,
  `temoin_telephone` varchar(20) DEFAULT NULL,
  `temoin_adresse` varchar(300) DEFAULT NULL,
  `temoin_statut` enum('etudiant','personnel','externe') NOT NULL,
  `temoignage` text NOT NULL,
  `date_temoignage` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_constat` (`id_constat`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `uscoud_pv_utilisateurs`
--

DROP TABLE IF EXISTS `uscoud_pv_utilisateurs`;
CREATE TABLE IF NOT EXISTS `uscoud_pv_utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matricule` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenoms` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','superviseur','agent','operateur') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'agent',
  `statut` enum('actif','inactif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'actif',
  `mot_de_passe` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `derniere_connexion` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `doit_changer_mdp` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricule` (`matricule`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_statut` (`statut`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table des utilisateurs du système USCOUD';

--
-- Déchargement des données de la table `uscoud_pv_utilisateurs`
--

INSERT INTO `uscoud_pv_utilisateurs` (`id`, `matricule`, `nom`, `prenoms`, `email`, `telephone`, `role`, `statut`, `mot_de_passe`, `derniere_connexion`, `created_at`, `updated_at`, `doit_changer_mdp`) VALUES
(1, 'ADMIN001', 'Administrateur', 'Système', 'admin@uscoud.sn', '770000000', 'admin', 'actif', '$2y$10$tiI9wZi6DHfg7kpzjYqSkegz6nn5B/FLnPxkAUUJ.TYnAMqYky7ES', '2026-02-28 05:57:07', '2026-01-06 10:19:51', '2026-02-28 05:57:07', 0);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
