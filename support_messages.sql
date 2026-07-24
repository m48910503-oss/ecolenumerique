-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 14 juil. 2026 à 19:36
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
-- Structure de la table `support_messages`
--

CREATE TABLE `support_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED DEFAULT NULL,
  `body` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `support_messages`
--

INSERT INTO `support_messages` (`id`, `conversation_id`, `sender_id`, `body`, `created_at`) VALUES
(1, 1, 10, 'bonjour', '2026-01-09 10:00:40'),
(2, 2, 10, 'rrrrgrg', '2026-01-09 10:39:17'),
(3, 2, 6, 'oui comment vas tu', '2026-01-09 12:47:37'),
(4, 2, 6, 'bien', '2026-01-09 12:48:59'),
(5, 3, 9, 'nogrnogrgrrrrrr', '2026-02-19 15:09:32'),
(6, 4, 12, 'eeevevevevee', '2026-02-19 15:26:46'),
(7, 4, 4, 'bonjour', '2026-02-19 15:27:21'),
(8, 4, 4, 'bonsoir', '2026-02-19 15:47:48'),
(9, 4, 12, 'bonoir', '2026-02-19 15:48:37'),
(10, 4, 12, 'salut', '2026-02-19 15:57:14'),
(11, 4, 4, 'comment', '2026-02-19 15:57:37'),
(12, 4, 4, 'bonjour', '2026-02-19 15:58:44'),
(13, 4, 12, 'coom', '2026-02-19 15:59:14'),
(14, 4, 4, 'merci', '2026-02-19 16:09:47'),
(15, 4, 12, 'iiiiiiiiii', '2026-02-19 16:18:29'),
(16, 4, 4, 'bonsoir', '2026-02-19 16:42:23'),
(17, 4, 4, 'bonsoir', '2026-02-19 16:43:14'),
(18, 5, 8, 'bonjours', '2026-03-25 16:58:41'),
(19, 6, 5, 'Je n’arrive plus à ouvrir le cours acheté depuis mon tableau de bord.', '2026-04-05 20:27:28'),
(20, 6, 4, 'Je n’arrive plus à ouvrir le cours acheté depuis mon tableau de bord.', '2026-04-05 20:29:40'),
(21, 7, 5, 'Le paiement a été effectué mais l’inscription au cours n’est pas activée.', '2026-04-04 15:49:33'),
(22, 8, 5, 'Le certificat du cours terminé ne s’affiche pas dans mon espace.', '2026-04-04 15:49:35'),
(23, 9, 1, 'Je n’arrive plus à ouvrir le cours acheté depuis mon tableau de bord.', '2026-05-19 09:47:23'),
(24, 9, 4, 'Je n’arrive plus à ouvrir le cours acheté depuis mon tableau de bord.', '2026-05-19 10:18:07'),
(25, 10, 5, 'rrrrrrrrrrrrrrrrrrrr', '2026-05-27 19:20:41'),
(26, 10, 4, 'nnnnnnnnnnnnnnnnnnnnnnnnnnn', '2026-05-27 20:13:45'),
(27, 9, 1, 'kkkkkkkkkkk', '2026-05-27 20:47:14'),
(28, 10, 5, 'cc', '2026-05-27 21:03:38'),
(29, 9, 1, 'salut', '2026-06-09 18:47:47');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `support_messages`
--
ALTER TABLE `support_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_support_msg_conv` (`conversation_id`,`id`),
  ADD KEY `idx_support_msg_sender` (`sender_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `support_messages`
--
ALTER TABLE `support_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `support_messages`
--
ALTER TABLE `support_messages`
  ADD CONSTRAINT `fk_support_msg_conv` FOREIGN KEY (`conversation_id`) REFERENCES `support_conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_support_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
