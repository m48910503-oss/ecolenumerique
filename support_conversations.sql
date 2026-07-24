-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 14 juil. 2026 à 19:35
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `ecolenumerique`
--

-- --------------------------------------------------------

--
-- Structure de la table `support_conversations`
--

CREATE TABLE `support_conversations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `viewer_key` varchar(96) DEFAULT NULL,
  `guest_name` varchar(120) DEFAULT NULL,
  `guest_email` varchar(190) DEFAULT NULL,
  `subject` varchar(190) NOT NULL,
  `status` enum('open','waiting_support','waiting_user','closed') NOT NULL DEFAULT 'open',
  `priority` enum('low','normal','high') NOT NULL DEFAULT 'normal',
  `category` enum('technical','billing','certificate','course','lesson','meeting','withdrawal','other') NOT NULL DEFAULT 'other',
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `last_message_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `support_conversations`
--

INSERT INTO `support_conversations` (`id`, `created_by`, `viewer_key`, `guest_name`, `guest_email`, `subject`, `status`, `priority`, `category`, `assigned_to`, `last_message_at`, `created_at`, `updated_at`) VALUES
(1, 10, NULL, NULL, NULL, 'hhtht', 'closed', 'normal', 'other', NULL, '2026-01-09 10:00:40', '2026-01-09 10:00:40', '2026-03-19 12:48:36'),
(2, 10, NULL, NULL, NULL, 'rrrrgrg', 'waiting_user', 'normal', 'other', 6, '2026-01-09 12:48:59', '2026-01-09 10:39:17', '2026-01-09 12:48:59'),
(3, 9, NULL, NULL, NULL, 'nogrnogrgrrrrrr', 'waiting_support', 'normal', 'other', NULL, '2026-02-19 15:09:32', '2026-02-19 15:09:32', '2026-02-19 15:09:32'),
(4, 12, NULL, NULL, NULL, 'eeevevevevee', 'closed', 'normal', 'other', 4, '2026-02-19 16:43:14', '2026-02-19 15:26:46', '2026-02-20 19:12:48'),
(5, 8, NULL, NULL, NULL, 'bonjours', 'closed', 'normal', 'other', 4, '2026-03-25 16:58:41', '2026-03-25 16:58:41', '2026-03-25 17:57:20'),
(6, 5, NULL, NULL, NULL, 'Problème d’accès au cours', 'waiting_user', 'normal', 'course', 4, '2026-04-05 20:29:40', '2026-04-05 20:27:28', '2026-05-27 17:39:42'),
(7, 5, NULL, NULL, NULL, 'Paiement non validé', 'waiting_support', 'high', 'billing', NULL, '2026-04-04 15:49:33', '2026-04-04 15:49:33', '2026-05-27 17:39:42'),
(8, 5, NULL, NULL, NULL, 'Certificat indisponible', 'waiting_support', 'normal', 'certificate', NULL, '2026-04-04 15:49:35', '2026-04-04 15:49:35', '2026-05-27 17:38:40'),
(9, 1, NULL, NULL, NULL, 'Problème d’accès au cours', 'waiting_support', 'normal', 'course', 4, '2026-06-09 18:47:47', '2026-05-19 09:47:23', '2026-06-09 18:47:47'),
(10, 5, NULL, NULL, NULL, 'rrr', 'waiting_support', 'low', 'other', 4, '2026-05-27 21:03:38', '2026-05-27 19:20:41', '2026-05-27 21:03:38');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `support_conversations`
--
ALTER TABLE `support_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_support_conv_status` (`status`,`last_message_at`),
  ADD KEY `idx_support_conv_creator` (`created_by`,`last_message_at`),
  ADD KEY `idx_support_conv_assigned` (`assigned_to`,`last_message_at`),
  ADD KEY `idx_support_conv_viewer` (`viewer_key`,`last_message_at`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `support_conversations`
--
ALTER TABLE `support_conversations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `support_conversations`
--
ALTER TABLE `support_conversations`
  ADD CONSTRAINT `fk_support_conv_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_support_conv_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
