-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 14 juil. 2026 à 19:42
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
-- Structure de la table `admin_announcements`
--

CREATE TABLE `admin_announcements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admin_user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(190) NOT NULL,
  `body` text NOT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `sent_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('draft','pending_review','published','archived') NOT NULL DEFAULT 'draft',
  `audience` enum('all','student','teacher') NOT NULL DEFAULT 'all',
  `category` enum('general','system','learning','support','promo') NOT NULL DEFAULT 'general',
  `importance` enum('info','important','urgent') NOT NULL DEFAULT 'info',
  `summary` varchar(200) DEFAULT NULL,
  `read_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `click_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `published_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `admin_announcements`
--

INSERT INTO `admin_announcements` (`id`, `admin_user_id`, `title`, `body`, `action_url`, `sent_count`, `created_at`, `updated_at`, `status`, `audience`, `category`, `importance`, `summary`, `read_count`, `click_count`, `published_by`, `approved_by`, `published_at`, `approved_at`, `archived_at`) VALUES
(6, 4, 'svsdvsdvsvsdv', 'vsdvdv', '/ecolenumerique/notifications?scope=admin', 9, '2026-03-25 11:44:14', '2026-06-23 12:19:09', 'archived', 'all', 'general', 'info', 'vsdvdv', 3, 0, 4, 4, '2026-03-25 16:12:29', '2026-03-25 16:12:29', '2026-06-23 12:19:09'),
(9, 4, 'Test annonce', 'Corps test', 'http://example.com', 0, '2026-03-25 12:03:44', '2026-06-23 12:19:09', 'archived', 'all', 'general', 'info', 'Resume test', 1, 0, NULL, NULL, NULL, NULL, '2026-06-23 12:19:09'),
(12, 4, 'Test Debug', 'Corps debug', '/ecolenumerique/notifications?scope=admin', 9, '2026-03-25 12:13:20', '2026-05-14 12:29:41', 'archived', 'all', 'general', 'info', 'Corps debug', 3, 0, 4, 4, '2026-03-25 12:13:20', '2026-03-25 12:13:20', '2026-04-03 17:53:40'),
(13, 4, 'y(((((((((\'', 'rgerheeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 9, '2026-04-03 18:25:26', '2026-07-10 12:39:12', 'archived', 'all', 'general', 'info', 'rgerheeeeeeeeeeeeeeeeeeee', 3, 0, 4, 4, '2026-04-03 18:25:28', '2026-04-03 18:25:28', '2026-07-10 12:39:12'),
(14, 4, 'eeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 8, '2026-06-20 18:30:02', '2026-06-22 10:38:23', 'published', 'all', 'general', 'info', 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', 0, 0, 4, 4, '2026-06-20 18:30:02', '2026-06-20 18:30:02', NULL),
(15, 4, 'Je viens', 'feeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 8, '2026-06-22 15:29:41', '2026-06-22 15:29:41', 'published', 'all', 'general', 'info', 'feffffffffffeeeeeeee', 0, 0, 4, 4, '2026-06-22 15:29:41', '2026-06-22 15:29:41', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `admin_announcement_events`
--

CREATE TABLE `admin_announcement_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `announcement_id` bigint(20) UNSIGNED NOT NULL,
  `actor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(40) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `admin_announcement_events`
--

INSERT INTO `admin_announcement_events` (`id`, `announcement_id`, `actor_id`, `action`, `note`, `created_at`) VALUES
(5, 6, 4, 'created', NULL, '2026-03-25 11:44:14'),
(6, 6, 4, 'draft_saved', NULL, '2026-03-25 11:44:14'),
(11, 12, 4, 'created', NULL, '2026-03-25 12:13:20'),
(12, 12, 4, 'published', NULL, '2026-03-25 12:13:20'),
(13, 6, 4, 'published', 'Audience: all', '2026-03-25 16:12:29'),
(14, 13, 4, 'created', NULL, '2026-04-03 18:25:26'),
(15, 13, 4, 'published', 'Audience: all', '2026-04-03 18:25:28'),
(16, 12, 4, 'archived', NULL, '2026-04-03 17:53:43'),
(17, 14, 4, 'created', NULL, '2026-06-20 18:30:02'),
(18, 14, 4, 'published', 'Audience: all', '2026-06-20 18:30:02'),
(19, 14, 4, 'archived', NULL, '2026-06-22 10:37:33'),
(20, 14, 4, 'restored', NULL, '2026-06-22 10:38:23'),
(21, 15, 4, 'created', NULL, '2026-06-22 15:29:41'),
(22, 15, 4, 'published', 'Audience: all', '2026-06-22 15:29:41');

-- --------------------------------------------------------

--
-- Structure de la table `admin_secondary_permissions`
--

CREATE TABLE `admin_secondary_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `permissions_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`permissions_json`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `admin_secondary_permissions`
--

INSERT INTO `admin_secondary_permissions` (`id`, `user_id`, `created_by`, `permissions_json`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 13, 4, '[\"dashboard\",\"support\",\"teacher_applications\",\"sales\"]', 1, '2026-02-17 15:38:06', '2026-03-24 12:16:01');

-- --------------------------------------------------------

--
-- Structure de la table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(150) NOT NULL,
  `target_type` varchar(80) NOT NULL,
  `target_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip` varbinary(16) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_json`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `target_type`, `target_id`, `ip`, `user_agent`, `meta_json`, `created_at`) VALUES
(1, 1, 'course_create', 'course', 1, NULL, NULL, NULL, '2025-11-29 09:30:20'),
(2, 1, 'course_create', 'course', 2, NULL, NULL, NULL, '2025-11-29 14:53:25'),
(3, 1, 'course_save_draft', 'course', 2, NULL, NULL, NULL, '2025-12-01 11:38:51'),
(4, 1, 'course_save_draft', 'course', 2, NULL, NULL, NULL, '2025-12-01 12:55:41'),
(5, 1, 'course_save_draft', 'course', 2, NULL, NULL, NULL, '2025-12-01 13:02:53'),
(6, 1, 'course_submit', 'course', 2, NULL, NULL, NULL, '2025-12-01 13:07:47'),
(7, 4, 'course_approve', 'course', 2, NULL, NULL, NULL, '2025-12-02 17:05:39'),
(8, 1, 'course_soft_delete_with_enrollments', 'course', 2, NULL, NULL, NULL, '2025-12-08 10:56:38'),
(9, 1, 'course_create', 'course', 3, NULL, NULL, NULL, '2025-12-08 16:26:58'),
(10, 1, 'course_soft_delete', 'course', 3, NULL, NULL, NULL, '2025-12-10 10:10:06'),
(11, 1, 'course_soft_delete', 'course', 1, NULL, NULL, NULL, '2025-12-10 10:10:07'),
(12, 1, 'course_create', 'course', 4, NULL, NULL, NULL, '2025-12-10 10:12:17'),
(13, 1, 'course_submit', 'course', 4, NULL, NULL, NULL, '2025-12-10 10:42:48'),
(14, 4, 'course_approve', 'course', 4, NULL, NULL, NULL, '2025-12-10 10:53:12'),
(15, 1, 'course_create', 'course', 5, NULL, NULL, NULL, '2025-12-16 15:25:06'),
(16, 1, 'course_create', 'course', 6, NULL, NULL, NULL, '2025-12-16 15:26:46'),
(17, 1, 'course_create', 'course', 7, NULL, NULL, NULL, '2025-12-16 15:27:28'),
(18, 1, 'course_new_version', 'course', 8, NULL, NULL, NULL, '2025-12-18 11:13:35'),
(19, 1, 'course_update_finished', 'course', 4, NULL, NULL, '{\"label\":\"Mise a jour terminee\",\"source\":\"course_edit\"}', '2025-12-18 12:02:20'),
(20, 1, 'course_update_finished', 'course', 4, NULL, NULL, '{\"label\":\"Mise a jour terminee\",\"source\":\"course_edit\"}', '2025-12-18 12:07:45'),
(21, 1, 'course_update', 'course', 4, NULL, NULL, '{\"label\":\"Rendez-vous modifie.\",\"source\":\"course_edit\"}', '2025-12-18 12:08:05'),
(22, 1, 'course_update_started', 'course', 4, NULL, NULL, '{\"label\":\"Mise a jour demarree\",\"source\":\"course_edit\"}', '2025-12-18 12:08:05'),
(23, 1, 'course_update_finished', 'course', 4, NULL, NULL, '{\"label\":\"Mise a jour terminee\",\"source\":\"course_edit\"}', '2025-12-18 12:10:46'),
(24, 1, 'course_create', 'course', 9, NULL, NULL, NULL, '2026-01-07 12:27:56'),
(25, 1, 'course_update', 'course', 9, NULL, NULL, '{\"label\":\"Chapitre ajoute.\",\"source\":\"course_content\"}', '2026-01-07 12:30:12'),
(26, 1, 'course_update', 'course', 9, NULL, NULL, '{\"label\":\"Chapitre ajoute.\",\"source\":\"course_content\"}', '2026-01-07 12:30:23'),
(27, 1, 'course_update', 'course', 9, NULL, NULL, '{\"label\":\"Chapitre ajoute.\",\"source\":\"course_content\"}', '2026-01-07 12:30:41'),
(28, 1, 'course_update', 'course', 9, NULL, NULL, '{\"label\":\"Rendez-vous ajoute.\",\"source\":\"course_content\"}', '2026-01-07 12:31:07'),
(29, 1, 'course_update', 'course', 9, NULL, NULL, '{\"label\":\"leçon ajoutee.\",\"source\":\"course_content\"}', '2026-01-07 12:34:58'),
(30, 1, 'course_update', 'course', 9, NULL, NULL, '{\"label\":\"leçon ajoutee.\",\"source\":\"course_content\"}', '2026-01-07 12:35:21'),
(31, 1, 'course_update', 'course', 9, NULL, NULL, '{\"label\":\"leçon ajoutee.\",\"source\":\"course_content\"}', '2026-01-07 12:36:02'),
(32, 1, 'course_update', 'course', 9, NULL, NULL, '{\"label\":\"leçon ajoutee.\",\"source\":\"course_content\"}', '2026-01-07 12:36:29'),
(33, 1, 'course_update', 'course', 9, NULL, NULL, '{\"label\":\"leçon ajoutee.\",\"source\":\"course_content\"}', '2026-01-07 12:37:02'),
(34, 1, 'course_update', 'course', 9, NULL, NULL, '{\"label\":\"leçon ajoutee.\",\"source\":\"course_content\"}', '2026-01-07 12:37:34'),
(35, 1, 'course_update', 'course', 9, NULL, NULL, '{\"label\":\"Ressource ajoutée.\",\"source\":\"course_content\"}', '2026-01-07 12:38:35'),
(36, 1, 'course_update', 'course', 9, NULL, NULL, '{\"label\":\"Ressource ajoutée.\",\"source\":\"course_content\"}', '2026-01-07 12:39:32'),
(37, 1, 'course_update', 'course', 9, NULL, NULL, '{\"label\":\"Ressource ajoutée.\",\"source\":\"course_content\"}', '2026-01-07 12:40:31'),
(38, 4, 'course_reject', 'course', 9, NULL, NULL, NULL, '2026-01-07 12:49:40'),
(39, 4, 'course_reject', 'course', 9, NULL, NULL, NULL, '2026-01-07 12:52:34'),
(40, 1, 'course_submit', 'course', 9, NULL, NULL, NULL, '2026-01-07 12:53:56'),
(41, 1, 'course_soft_delete', 'course', 7, NULL, NULL, NULL, '2026-01-07 12:54:02'),
(42, 4, 'course_approve', 'course', 9, NULL, NULL, NULL, '2026-01-07 12:54:22'),
(43, 10, 'course_create', 'course', 10, NULL, NULL, NULL, '2026-01-10 19:31:18'),
(44, 10, 'course_update', 'course', 10, NULL, NULL, '{\"label\":\"Chapitre ajoute.\",\"source\":\"course_edit\"}', '2026-01-10 19:32:48'),
(45, 10, 'course_update', 'course', 10, NULL, NULL, '{\"label\":\"leçon ajoutee.\",\"source\":\"course_content\"}', '2026-01-10 19:35:32'),
(46, 10, 'course_update', 'course', 10, NULL, NULL, '{\"label\":\"Rendez-vous ajoute.\",\"source\":\"course_content\"}', '2026-01-10 19:40:58'),
(47, 10, 'course_submit', 'course', 10, NULL, NULL, NULL, '2026-01-10 19:57:17'),
(48, 4, 'course_approve', 'course', 10, NULL, NULL, NULL, '2026-01-10 20:01:06'),
(49, 10, 'course_update', 'course', 10, NULL, NULL, '{\"label\":\"Cours mis a jour.\",\"source\":\"course_edit\"}', '2026-01-16 15:23:56'),
(50, 10, 'course_update_started', 'course', 10, NULL, NULL, '{\"label\":\"Mise a jour demarree\",\"source\":\"course_edit\"}', '2026-01-16 15:23:56'),
(51, 10, 'course_update_finished', 'course', 10, NULL, NULL, '{\"label\":\"Mise a jour terminee\",\"source\":\"course_edit\"}', '2026-01-16 15:23:59'),
(52, 1, 'course_duplicate', 'course', 11, NULL, NULL, NULL, '2026-02-16 18:07:10'),
(53, 1, 'course_update', 'course', 5, NULL, NULL, '{\"label\":\"Chapitre ajoute.\",\"source\":\"course_content\"}', '2026-02-16 18:07:51'),
(54, 1, 'course_update', 'course', 5, NULL, NULL, '{\"label\":\"Lecon ajoutee\",\"source\":\"course_content\"}', '2026-02-17 12:10:25'),
(55, 1, 'course_update', 'course', 5, NULL, NULL, '{\"label\":\"Rendez-vous ajoute\",\"source\":\"course_content\"}', '2026-02-17 12:11:18'),
(56, 4, 'course_approve', 'course', 5, NULL, NULL, NULL, '2026-02-17 12:42:25'),
(57, 1, 'course_duplicate', 'course', 12, NULL, NULL, NULL, '2026-02-17 12:47:12'),
(58, 12, 'invite_sent', 'company', 1, NULL, NULL, '{\"email\":\"m45885679@gmail.com\"}', '2026-03-07 15:22:33'),
(59, 12, 'invite_resent', 'company', 1, NULL, NULL, '{\"invite_id\":2,\"mode\":\"email\"}', '2026-03-09 16:05:25'),
(60, 12, 'team_created', 'company', 1, NULL, NULL, '{\"team_name\":\"mike\"}', '2026-03-09 17:18:37'),
(61, 12, 'team_archived', 'company', 1, NULL, NULL, '{\"team_id\":1}', '2026-03-09 17:18:42'),
(62, 8, 'course_create', 'course', 13, NULL, NULL, NULL, '2026-03-11 13:32:10'),
(63, 1, 'course_create', 'course', 14, NULL, NULL, NULL, '2026-03-25 16:25:53'),
(64, 1, 'course_update', 'course', 14, NULL, NULL, '{\"label\":\"Chapitre ajouté\",\"source\":\"course_content\"}', '2026-03-26 12:51:29'),
(65, 1, 'course_update', 'course', 14, NULL, NULL, '{\"label\":\"Leçon ajoutée\",\"source\":\"course_content\"}', '2026-03-26 18:53:23'),
(66, 1, 'course_update', 'course', 14, NULL, NULL, '{\"label\":\"Ressource ajoutée\",\"source\":\"course_content\"}', '2026-03-26 19:14:29'),
(67, 1, 'course_update', 'course', 14, NULL, NULL, '{\"label\":\"Ressource ajoutée\",\"source\":\"course_content\"}', '2026-03-26 19:15:05'),
(68, 1, 'course_update', 'course', 14, NULL, NULL, '{\"label\":\"Rendez-vous ajouté\",\"source\":\"course_content\"}', '2026-03-26 19:29:30'),
(69, 1, 'course_update', 'course', 14, NULL, NULL, '{\"label\":\"Ressource ajoutée\",\"source\":\"course_content\"}', '2026-03-26 19:30:16'),
(70, 1, 'course_update', 'course', 14, NULL, NULL, '{\"label\":\"Rendez-vous ajouté\",\"source\":\"course_content\"}', '2026-03-26 19:33:37'),
(71, 1, 'course_update', 'course', 14, NULL, NULL, '{\"label\":\"Ressource ajoutée\",\"source\":\"course_content\"}', '2026-03-26 19:44:28'),
(72, 1, 'course_create', 'course', 15, NULL, NULL, NULL, '2026-04-07 17:30:05'),
(73, 1, 'course_create', 'course', 16, NULL, NULL, NULL, '2026-04-02 18:39:26'),
(74, 4, 'course_approve', 'course', 15, NULL, NULL, NULL, '2026-04-17 21:08:51'),
(75, 1, 'course_create', 'course', 17, NULL, NULL, NULL, '2026-05-20 20:07:02'),
(76, 1, 'course_update', 'course', 17, NULL, NULL, '{\"label\":\"Chapitre ajouté\",\"source\":\"course_content\"}', '2026-05-20 20:07:11'),
(77, 1, 'course_update', 'course', 17, NULL, NULL, '{\"label\":\"Leçon ajoutée\",\"source\":\"course_content\"}', '2026-05-20 20:18:49'),
(78, 1, 'course_update', 'course', 17, NULL, NULL, '{\"label\":\"Ressource ajoutée\",\"source\":\"course_content\"}', '2026-05-20 20:20:02'),
(79, 1, 'course_update', 'course', 17, NULL, NULL, '{\"label\":\"Rendez-vous ajouté\",\"source\":\"course_content\"}', '2026-05-20 20:20:11'),
(80, 1, 'course_create', 'course', 18, NULL, NULL, NULL, '2026-05-21 17:17:39'),
(81, 1, 'course_update', 'course', 18, NULL, NULL, '{\"label\":\"Chapitre ajouté\",\"source\":\"course_content\"}', '2026-05-21 17:17:46'),
(82, 1, 'course_create', 'course', 19, NULL, NULL, NULL, '2026-06-04 18:36:43'),
(83, 1, 'course_create', 'course', 20, NULL, NULL, NULL, '2026-06-19 14:49:24'),
(84, 1, 'course_create', 'course', 21, NULL, NULL, NULL, '2026-06-19 15:28:02'),
(85, 1, 'course_update', 'course', 20, NULL, NULL, NULL, '2026-06-19 15:37:16'),
(86, 4, 'teacher_suspend', 'user', 1, NULL, NULL, '{\"previous_status\":\"active\",\"actor_role\":\"admin\"}', '2026-06-23 09:15:38'),
(87, 4, 'teacher_reactivate', 'user', 1, NULL, NULL, '{\"previous_status\":\"suspended\",\"actor_role\":\"admin\"}', '2026-06-23 09:15:51'),
(88, 1, 'course_update', 'course', 22, NULL, NULL, NULL, '2026-07-02 16:27:20');

-- --------------------------------------------------------

--
-- Structure de la table `catalog_event_logs`
--

CREATE TABLE `catalog_event_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_type` enum('course_click','preview_open','add_to_cart','buy_now','purchase') NOT NULL,
  `block_key` varchar(64) NOT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `session_key` varchar(96) DEFAULT NULL,
  `metadata_json` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `catalog_event_logs`
--

INSERT INTO `catalog_event_logs` (`id`, `event_type`, `block_key`, `course_id`, `user_id`, `session_key`, `metadata_json`, `created_at`) VALUES
(1, 'course_click', 'all', 10, 12, 's:d0e9d1d493cd0ce57107ec0bcd29ede9ad12d575e39b3b40', '{\"sponsored\":\"0\",\"surface\":\"catalog\"}', '2026-02-18 18:38:00'),
(2, 'add_to_cart', 'new', 9, 12, 's:d0e9d1d493cd0ce57107ec0bcd29ede9ad12d575e39b3b40', '{\"surface\":\"catalog\"}', '2026-02-19 13:28:23'),
(3, 'buy_now', 'new', 10, 9, 's:513a0bb1513b0c7572006d831667bd91356b8f049a31e98c', '{\"surface\":\"catalog\"}', '2026-02-20 17:58:47'),
(4, 'buy_now', 'new', 9, 9, 's:513a0bb1513b0c7572006d831667bd91356b8f049a31e98c', '{\"surface\":\"catalog\"}', '2026-02-20 17:59:00'),
(5, 'buy_now', 'all', 4, 12, 's:a3c207f5bf477e89bd93d4a1a588e4174f8b4c4e5f8a6126', '{\"surface\":\"catalog\"}', '2026-02-20 18:04:23'),
(6, 'buy_now', 'all', 5, 12, 's:a3c207f5bf477e89bd93d4a1a588e4174f8b4c4e5f8a6126', '{\"surface\":\"catalog\"}', '2026-02-20 18:08:24'),
(7, 'buy_now', 'all', 4, 12, 's:a3c207f5bf477e89bd93d4a1a588e4174f8b4c4e5f8a6126', '{\"surface\":\"catalog\"}', '2026-02-20 18:08:45'),
(8, 'buy_now', 'all', 4, 12, 's:a3c207f5bf477e89bd93d4a1a588e4174f8b4c4e5f8a6126', '{\"surface\":\"catalog\"}', '2026-02-20 18:28:10'),
(9, 'purchase', 'all', 4, 12, NULL, '{\"order_id\":32,\"order_item_id\":36}', '2026-02-20 18:28:26'),
(10, 'buy_now', 'all', 4, 9, 's:513a0bb1513b0c7572006d831667bd91356b8f049a31e98c', '{\"surface\":\"catalog\"}', '2026-02-20 18:30:26'),
(11, 'buy_now', 'all', 4, 9, 's:513a0bb1513b0c7572006d831667bd91356b8f049a31e98c', '{\"surface\":\"catalog\"}', '2026-02-20 18:35:07'),
(12, 'buy_now', 'all', 4, 9, 's:513a0bb1513b0c7572006d831667bd91356b8f049a31e98c', '{\"surface\":\"catalog\"}', '2026-02-20 19:04:32'),
(13, 'course_click', 'new', 10, 12, 's:990674d0d6ae04c60e74583748e6b80a8bc846358e494678', '{\"sponsored\":\"0\",\"surface\":\"catalog\"}', '2026-02-26 14:17:14'),
(14, 'course_click', 'best', 9, 12, 's:990674d0d6ae04c60e74583748e6b80a8bc846358e494678', '{\"sponsored\":\"0\",\"surface\":\"catalog\"}', '2026-02-26 14:17:43'),
(15, 'course_click', 'best', 10, 9, 's:3ff39d09a4895bed70725eccf843160006b16f350a4ed957', '{\"sponsored\":\"0\",\"surface\":\"catalog\"}', '2026-02-28 12:42:55'),
(16, 'course_click', 'new', 10, 9, 's:3ff39d09a4895bed70725eccf843160006b16f350a4ed957', '{\"sponsored\":\"0\",\"surface\":\"catalog\"}', '2026-02-28 13:24:38'),
(17, 'add_to_cart', 'all', 5, NULL, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"surface\":\"catalog\"}', '2026-03-02 16:07:18'),
(18, 'add_to_cart', 'new', 5, NULL, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"surface\":\"catalog\"}', '2026-03-02 16:53:08'),
(19, 'add_to_cart', 'new', 9, NULL, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"surface\":\"catalog\"}', '2026-03-02 16:53:14'),
(20, 'add_to_cart', 'all', 5, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"surface\":\"catalog\"}', '2026-03-02 17:27:30'),
(21, 'course_click', 'all', 5, NULL, 's:0a785843262e49b21a7807e85e84e07b4d51221658c37dd6', '{\"sponsored\":\"0\",\"surface\":\"catalog\"}', '2026-03-02 17:29:03'),
(22, 'add_to_cart', 'new', 5, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"surface\":\"catalog\"}', '2026-03-02 18:00:25'),
(23, '', 'top', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"surface\":\"catalog\"}', '2026-03-02 18:37:06'),
(24, '', 'best', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"surface\":\"catalog\"}', '2026-03-02 18:37:06'),
(25, '', 'all', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"surface\":\"catalog\"}', '2026-03-02 18:37:06'),
(26, '', 'top', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"surface\":\"catalog\"}', '2026-03-02 18:43:10'),
(27, '', 'best', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"surface\":\"catalog\"}', '2026-03-02 18:43:10'),
(28, '', 'all', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"surface\":\"catalog\"}', '2026-03-02 18:43:11'),
(29, '', 'catalogue-all', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"surface\":\"catalog\"}', '2026-03-02 18:43:32'),
(30, '', 'top', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"surface\":\"catalog\"}', '2026-03-02 18:48:48'),
(31, '', 'best', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"surface\":\"catalog\"}', '2026-03-02 18:48:49'),
(32, '', 'all', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"surface\":\"catalog\"}', '2026-03-02 18:48:49'),
(33, '', 'top', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"surface\":\"catalog\"}', '2026-03-02 18:58:56'),
(34, '', 'best', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"surface\":\"catalog\"}', '2026-03-02 18:58:56'),
(35, '', 'all', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"surface\":\"catalog\"}', '2026-03-02 18:58:57'),
(36, '', 'all', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"enabled\":\"1\"}', '2026-03-02 18:59:14'),
(37, '', 'all', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"enabled\":\"0\"}', '2026-03-02 18:59:16'),
(38, '', 'all', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"enabled\":\"1\"}', '2026-03-02 18:59:17'),
(39, '', 'all', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"enabled\":\"1\"}', '2026-03-02 18:59:25'),
(40, '', 'all', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"enabled\":\"0\"}', '2026-03-02 18:59:27'),
(41, '', 'all', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"enabled\":\"0\"}', '2026-03-02 18:59:30'),
(42, '', 'all', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"mode\":\"compact\"}', '2026-03-02 19:00:33'),
(43, '', 'all', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"mode\":\"comfort\"}', '2026-03-02 19:00:34'),
(44, '', 'all', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"mode\":\"compact\"}', '2026-03-02 19:00:35'),
(45, '', 'all', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"mode\":\"comfort\"}', '2026-03-02 19:00:36'),
(46, '', 'all', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"mode\":\"compact\"}', '2026-03-02 19:00:39'),
(47, '', 'all', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"mode\":\"comfort\"}', '2026-03-02 19:00:40'),
(48, '', 'all', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"mode\":\"comfort\"}', '2026-03-02 19:00:41'),
(49, '', 'all', NULL, 12, 's:9fd2152e52be7063c2d1a172b0703fa783b6f35c532b5ec7', '{\"mode\":\"compact\"}', '2026-03-02 19:00:42'),
(50, '', 'top', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"surface\":\"catalog\"}', '2026-03-02 19:03:28'),
(51, '', 'best', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"surface\":\"catalog\"}', '2026-03-02 19:03:28'),
(52, '', 'all', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"surface\":\"catalog\"}', '2026-03-02 19:03:28'),
(53, '', 'all', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"surface\":\"catalog\"}', '2026-03-02 19:03:37'),
(54, '', 'all', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"surface\":\"catalog\"}', '2026-03-02 19:03:42'),
(55, '', 'all', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"label\":\"Section: Cours sponsorisés\"}', '2026-03-02 19:03:45'),
(56, '', 'all', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"enabled\":\"1\"}', '2026-03-02 19:03:47'),
(57, '', 'all', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"enabled\":\"0\"}', '2026-03-02 19:03:49'),
(58, '', 'all', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"enabled\":\"1\"}', '2026-03-02 19:03:50'),
(59, '', 'all', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"enabled\":\"0\"}', '2026-03-02 19:03:52'),
(60, '', 'all', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"enabled\":\"1\"}', '2026-03-02 19:03:54'),
(61, '', 'all', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"enabled\":\"0\"}', '2026-03-02 19:03:55'),
(62, '', 'all', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"enabled\":\"1\"}', '2026-03-02 19:03:56'),
(63, '', 'all', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"enabled\":\"1\"}', '2026-03-02 19:03:57'),
(64, '', 'all', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"enabled\":\"0\"}', '2026-03-02 19:03:59'),
(65, '', 'all', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"enabled\":\"0\"}', '2026-03-02 19:04:01'),
(66, '', 'all', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"surface\":\"catalog\"}', '2026-03-02 19:04:35'),
(67, '', 'all', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"surface\":\"catalog\"}', '2026-03-02 19:04:38'),
(68, '', 'all', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"surface\":\"catalog\"}', '2026-03-02 19:04:40'),
(69, '', 'top', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"surface\":\"catalog\"}', '2026-03-02 19:23:06'),
(70, '', 'best', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"surface\":\"catalog\"}', '2026-03-02 19:23:06'),
(71, '', 'all', NULL, 12, 's:d4dec3fa49744ea703efac95b43b46c24c597f57393befa7', '{\"surface\":\"catalog\"}', '2026-03-02 19:23:06'),
(72, '', 'top', NULL, NULL, 's:7b18108bbf1248ea91a00e81750b05c3db7fe44e231cb245', '{\"surface\":\"catalog\"}', '2026-03-02 19:34:52'),
(73, '', 'best', NULL, NULL, 's:7b18108bbf1248ea91a00e81750b05c3db7fe44e231cb245', '{\"surface\":\"catalog\"}', '2026-03-02 19:34:52'),
(74, '', 'all', NULL, NULL, 's:7b18108bbf1248ea91a00e81750b05c3db7fe44e231cb245', '{\"surface\":\"catalog\"}', '2026-03-02 19:34:52'),
(75, '', 'top', NULL, 9, 's:6327ae50bd5678ba354ae677e967f24c41037761255b567b', '{\"surface\":\"catalog\"}', '2026-03-03 15:02:43'),
(76, '', 'best', NULL, 9, 's:6327ae50bd5678ba354ae677e967f24c41037761255b567b', '{\"surface\":\"catalog\"}', '2026-03-03 15:02:43'),
(77, '', 'all', NULL, 9, 's:6327ae50bd5678ba354ae677e967f24c41037761255b567b', '{\"surface\":\"catalog\"}', '2026-03-03 15:02:44'),
(78, '', 'top', NULL, 10, 's:1bc711213e748c5c4baa6589d42b906c96afe4e249af1955', '{\"surface\":\"catalog\"}', '2026-03-03 17:02:21'),
(79, '', 'best', NULL, 10, 's:1bc711213e748c5c4baa6589d42b906c96afe4e249af1955', '{\"surface\":\"catalog\"}', '2026-03-03 17:02:21'),
(80, '', 'all', NULL, 10, 's:1bc711213e748c5c4baa6589d42b906c96afe4e249af1955', '{\"surface\":\"catalog\"}', '2026-03-03 17:02:23'),
(81, '', 'all', NULL, 10, 's:1bc711213e748c5c4baa6589d42b906c96afe4e249af1955', '{\"mode\":\"compact\"}', '2026-03-03 17:02:57'),
(82, '', 'all', NULL, 10, 's:1bc711213e748c5c4baa6589d42b906c96afe4e249af1955', '{\"mode\":\"compact\"}', '2026-03-03 17:02:59'),
(83, '', 'all', NULL, 10, 's:1bc711213e748c5c4baa6589d42b906c96afe4e249af1955', '{\"surface\":\"catalog\"}', '2026-03-03 17:03:00'),
(84, '', 'top', NULL, 9, 's:6327ae50bd5678ba354ae677e967f24c41037761255b567b', '{\"surface\":\"catalog\"}', '2026-03-03 18:17:56'),
(85, '', 'best', NULL, 9, 's:6327ae50bd5678ba354ae677e967f24c41037761255b567b', '{\"surface\":\"catalog\"}', '2026-03-03 18:17:56'),
(86, '', 'all', NULL, 9, 's:6327ae50bd5678ba354ae677e967f24c41037761255b567b', '{\"surface\":\"catalog\"}', '2026-03-03 18:17:56'),
(87, '', 'top', NULL, 9, 's:28e5fdf9bdb44a1f4ffd972fad965944560f0f8558a8547c', '{\"surface\":\"catalog\"}', '2026-03-04 20:09:28'),
(88, '', 'best', NULL, 9, 's:28e5fdf9bdb44a1f4ffd972fad965944560f0f8558a8547c', '{\"surface\":\"catalog\"}', '2026-03-04 20:09:28'),
(89, '', 'all', NULL, 9, 's:28e5fdf9bdb44a1f4ffd972fad965944560f0f8558a8547c', '{\"surface\":\"catalog\"}', '2026-03-04 20:09:28'),
(90, '', 'top', NULL, 12, 's:0a2652a5394f2cd8ba8d95037c6f967b343ffe87ab2c7eff', '{\"surface\":\"catalog\"}', '2026-03-06 11:53:06'),
(91, '', 'best', NULL, 12, 's:0a2652a5394f2cd8ba8d95037c6f967b343ffe87ab2c7eff', '{\"surface\":\"catalog\"}', '2026-03-06 11:53:06'),
(92, '', 'all', NULL, 12, 's:0a2652a5394f2cd8ba8d95037c6f967b343ffe87ab2c7eff', '{\"surface\":\"catalog\"}', '2026-03-06 11:53:07'),
(93, '', 'top', NULL, 9, 's:2d4ed91ff427b3472abd2b2d2ab68b39069f6b5678f4afc5', '{\"surface\":\"catalog\"}', '2026-03-06 12:08:32'),
(94, '', 'best', NULL, 9, 's:2d4ed91ff427b3472abd2b2d2ab68b39069f6b5678f4afc5', '{\"surface\":\"catalog\"}', '2026-03-06 12:08:32'),
(95, '', 'top', NULL, 5, 's:8262d4fd07c155ec699e514256b2ae4503e25971e8f427c7', '{\"surface\":\"catalog\"}', '2026-03-06 19:51:03'),
(96, '', 'best', NULL, 5, 's:8262d4fd07c155ec699e514256b2ae4503e25971e8f427c7', '{\"surface\":\"catalog\"}', '2026-03-06 19:51:03'),
(97, '', 'all', NULL, 5, 's:8262d4fd07c155ec699e514256b2ae4503e25971e8f427c7', '{\"surface\":\"catalog\"}', '2026-03-06 19:51:05'),
(98, '', 'top', NULL, 10, 's:22becd8e54e2026d632557050f8f628f26427f767716fa09', '{\"surface\":\"catalog\"}', '2026-03-07 14:44:07'),
(99, '', 'best', NULL, 10, 's:22becd8e54e2026d632557050f8f628f26427f767716fa09', '{\"surface\":\"catalog\"}', '2026-03-07 14:44:07'),
(100, '', 'all', NULL, 10, 's:22becd8e54e2026d632557050f8f628f26427f767716fa09', '{\"surface\":\"catalog\"}', '2026-03-07 14:44:09'),
(101, '', 'top', NULL, 12, 's:b06e1d4b2b5ea55e9ad83006b833c3d65d9566453de24424', '{\"surface\":\"catalog\"}', '2026-03-07 14:58:11'),
(102, '', 'best', NULL, 12, 's:b06e1d4b2b5ea55e9ad83006b833c3d65d9566453de24424', '{\"surface\":\"catalog\"}', '2026-03-07 14:58:11'),
(103, '', 'all', NULL, 12, 's:b06e1d4b2b5ea55e9ad83006b833c3d65d9566453de24424', '{\"surface\":\"catalog\"}', '2026-03-07 14:58:11'),
(104, '', 'top', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 14:55:03'),
(105, '', 'best', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 14:55:03'),
(106, 'buy_now', 'new', 10, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 14:55:06'),
(107, '', 'top', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 16:04:40'),
(108, '', 'best', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 16:04:40'),
(109, '', 'all', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 16:04:40'),
(110, '', 'top', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 17:24:01'),
(111, '', 'best', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 17:24:01'),
(112, '', 'all', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 17:24:01'),
(113, '', 'top', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 17:24:46'),
(114, '', 'best', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 17:24:46'),
(115, '', 'all', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 17:24:46'),
(116, '', 'top', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 17:25:14'),
(117, '', 'best', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 17:25:14'),
(118, '', 'all', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 17:25:14'),
(119, '', 'top', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 18:48:26'),
(120, '', 'best', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 18:48:26'),
(121, '', 'all', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 18:48:26'),
(122, '', 'top', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 18:48:34'),
(123, '', 'best', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 18:48:34'),
(124, '', 'all', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 18:48:35'),
(125, '', 'top', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 18:48:51'),
(126, '', 'best', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 18:48:51'),
(127, '', 'all', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 18:48:51'),
(128, '', 'top', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 18:49:08'),
(129, '', 'best', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 18:49:08'),
(130, '', 'all', NULL, 12, 's:d1e803131953e312406265b05aa09eedb259aee1cdb7fba0', '{\"surface\":\"catalog\"}', '2026-03-09 18:49:08'),
(131, '', 'top', NULL, 12, 's:e5967b70a4f9df3a9b894a43a4d20dd5609ee797e5a37441', '{\"surface\":\"catalog\"}', '2026-03-10 15:54:35'),
(132, '', 'best', NULL, 12, 's:e5967b70a4f9df3a9b894a43a4d20dd5609ee797e5a37441', '{\"surface\":\"catalog\"}', '2026-03-10 15:54:39'),
(133, '', 'all', NULL, 12, 's:e5967b70a4f9df3a9b894a43a4d20dd5609ee797e5a37441', '{\"surface\":\"catalog\"}', '2026-03-10 15:54:39'),
(134, '', 'top', NULL, 9, 's:9a75b84c3289bd72af4e786e24e5a865e56fedd0ec0d06b3', '{\"surface\":\"catalog\"}', '2026-03-10 16:14:24'),
(135, '', 'best', NULL, 9, 's:9a75b84c3289bd72af4e786e24e5a865e56fedd0ec0d06b3', '{\"surface\":\"catalog\"}', '2026-03-10 16:14:24'),
(136, '', 'top', NULL, 12, 's:d61343a7ed44c417057deaf03f85abe2a6b60b6f1ccfce2d', '{\"surface\":\"catalog\"}', '2026-03-11 14:43:02'),
(137, '', 'best', NULL, 12, 's:d61343a7ed44c417057deaf03f85abe2a6b60b6f1ccfce2d', '{\"surface\":\"catalog\"}', '2026-03-11 14:43:02'),
(138, '', 'top', NULL, 8, 's:49fecd2341192c9090e576cdb48802c972fc911b7fbb3285', '{\"surface\":\"catalog\"}', '2026-03-12 17:44:15'),
(139, '', 'best', NULL, 8, 's:49fecd2341192c9090e576cdb48802c972fc911b7fbb3285', '{\"surface\":\"catalog\"}', '2026-03-12 17:44:15'),
(140, '', 'all', NULL, 8, 's:49fecd2341192c9090e576cdb48802c972fc911b7fbb3285', '{\"surface\":\"catalog\"}', '2026-03-12 17:44:16'),
(141, '', 'top', NULL, 9, 's:af1e7eb69b28bc13df31745b0036be522e07e5945daaf61e', '{\"surface\":\"catalog\"}', '2026-03-12 19:14:28'),
(142, '', 'best', NULL, 9, 's:af1e7eb69b28bc13df31745b0036be522e07e5945daaf61e', '{\"surface\":\"catalog\"}', '2026-03-12 19:14:28'),
(143, '', 'top', NULL, 5, 's:d021cafddbe6ea0ceea556ae2ad328bed5859933a668366a', '{\"surface\":\"catalog\"}', '2026-03-18 13:33:26'),
(144, '', 'best', NULL, 5, 's:d021cafddbe6ea0ceea556ae2ad328bed5859933a668366a', '{\"surface\":\"catalog\"}', '2026-03-18 13:33:26'),
(145, 'course_click', 'new', 10, 5, 's:d021cafddbe6ea0ceea556ae2ad328bed5859933a668366a', '{\"sponsored\":\"0\",\"surface\":\"catalog\"}', '2026-03-18 13:33:31'),
(146, '', 'top', NULL, 8, 's:7e3b78921c1d42e33ce2d0c4daaefc49dc66611ebb1aee33', '{\"surface\":\"catalog\"}', '2026-03-19 11:06:09'),
(147, '', 'best', NULL, 8, 's:7e3b78921c1d42e33ce2d0c4daaefc49dc66611ebb1aee33', '{\"surface\":\"catalog\"}', '2026-03-19 11:06:09'),
(148, '', 'all', NULL, 8, 's:7e3b78921c1d42e33ce2d0c4daaefc49dc66611ebb1aee33', '{\"surface\":\"catalog\"}', '2026-03-19 11:06:10'),
(149, '', 'top', NULL, 10, 's:a011e39f1926b035d5a0fbced2aa69b0615aef860595a007', '{\"surface\":\"catalog\"}', '2026-03-20 17:24:21'),
(150, '', 'best', NULL, 10, 's:a011e39f1926b035d5a0fbced2aa69b0615aef860595a007', '{\"surface\":\"catalog\"}', '2026-03-20 17:24:21'),
(151, '', 'all', NULL, 10, 's:a011e39f1926b035d5a0fbced2aa69b0615aef860595a007', '{\"surface\":\"catalog\"}', '2026-03-20 17:24:22'),
(152, '', 'top', NULL, 10, 's:a011e39f1926b035d5a0fbced2aa69b0615aef860595a007', '{\"surface\":\"catalog\"}', '2026-03-20 18:25:36'),
(153, '', 'best', NULL, 10, 's:a011e39f1926b035d5a0fbced2aa69b0615aef860595a007', '{\"surface\":\"catalog\"}', '2026-03-20 18:25:36'),
(154, '', 'all', NULL, 10, 's:a011e39f1926b035d5a0fbced2aa69b0615aef860595a007', '{\"surface\":\"catalog\"}', '2026-03-20 18:25:36'),
(155, '', 'top', NULL, 10, 's:a011e39f1926b035d5a0fbced2aa69b0615aef860595a007', '{\"surface\":\"catalog\"}', '2026-03-20 19:37:22'),
(156, '', 'best', NULL, 10, 's:a011e39f1926b035d5a0fbced2aa69b0615aef860595a007', '{\"surface\":\"catalog\"}', '2026-03-20 19:37:23'),
(157, '', 'all', NULL, 10, 's:a011e39f1926b035d5a0fbced2aa69b0615aef860595a007', '{\"surface\":\"catalog\"}', '2026-03-20 19:37:23'),
(158, '', 'top', NULL, 10, 's:fd908ac7af3c510e9d0421c3b0cb6b585ccb22e207429080', '{\"surface\":\"catalog\"}', '2026-03-21 10:50:34'),
(159, '', 'best', NULL, 10, 's:fd908ac7af3c510e9d0421c3b0cb6b585ccb22e207429080', '{\"surface\":\"catalog\"}', '2026-03-21 10:50:34'),
(160, '', 'all', NULL, 10, 's:fd908ac7af3c510e9d0421c3b0cb6b585ccb22e207429080', '{\"surface\":\"catalog\"}', '2026-03-21 10:50:34'),
(161, '', 'top', NULL, 10, 's:fd908ac7af3c510e9d0421c3b0cb6b585ccb22e207429080', '{\"surface\":\"catalog\"}', '2026-03-21 11:20:26'),
(162, '', 'best', NULL, 10, 's:fd908ac7af3c510e9d0421c3b0cb6b585ccb22e207429080', '{\"surface\":\"catalog\"}', '2026-03-21 11:20:26'),
(163, '', 'all', NULL, 10, 's:fd908ac7af3c510e9d0421c3b0cb6b585ccb22e207429080', '{\"surface\":\"catalog\"}', '2026-03-21 11:20:33'),
(164, '', 'top', NULL, 10, 's:de7803acc5e1ea792bc4bd8f868280e57d88425f9af85658', '{\"surface\":\"catalog\"}', '2026-03-23 10:13:47'),
(165, '', 'best', NULL, 10, 's:de7803acc5e1ea792bc4bd8f868280e57d88425f9af85658', '{\"surface\":\"catalog\"}', '2026-03-23 10:13:47'),
(166, '', 'all', NULL, 10, 's:de7803acc5e1ea792bc4bd8f868280e57d88425f9af85658', '{\"surface\":\"catalog\"}', '2026-03-23 10:13:47'),
(167, '', 'top', NULL, 10, 's:de7803acc5e1ea792bc4bd8f868280e57d88425f9af85658', '{\"surface\":\"catalog\"}', '2026-03-23 16:58:09'),
(168, '', 'best', NULL, 10, 's:de7803acc5e1ea792bc4bd8f868280e57d88425f9af85658', '{\"surface\":\"catalog\"}', '2026-03-23 16:58:09'),
(169, '', 'all', NULL, 10, 's:de7803acc5e1ea792bc4bd8f868280e57d88425f9af85658', '{\"surface\":\"catalog\"}', '2026-03-23 16:58:10'),
(170, '', 'top', NULL, NULL, 's:a389e24cb3caa919fbb94d6aa84b8051edb67df571c7a8b5', '{\"surface\":\"catalog\"}', '2026-03-23 16:58:41'),
(171, '', 'best', NULL, NULL, 's:a389e24cb3caa919fbb94d6aa84b8051edb67df571c7a8b5', '{\"surface\":\"catalog\"}', '2026-03-23 16:58:41'),
(172, '', 'all', NULL, NULL, 's:a389e24cb3caa919fbb94d6aa84b8051edb67df571c7a8b5', '{\"surface\":\"catalog\"}', '2026-03-23 16:58:43'),
(173, '', 'top', NULL, NULL, 's:a389e24cb3caa919fbb94d6aa84b8051edb67df571c7a8b5', '{\"surface\":\"catalog\"}', '2026-03-23 17:43:03'),
(174, '', 'best', NULL, NULL, 's:a389e24cb3caa919fbb94d6aa84b8051edb67df571c7a8b5', '{\"surface\":\"catalog\"}', '2026-03-23 17:43:05'),
(175, '', 'all', NULL, NULL, 's:a389e24cb3caa919fbb94d6aa84b8051edb67df571c7a8b5', '{\"surface\":\"catalog\"}', '2026-03-23 17:43:05'),
(176, '', 'top', NULL, 12, 's:f38e3c353dbfb41d0b7b869eaa2ac4a24c3f49082d67429f', '{\"surface\":\"catalog\"}', '2026-03-27 12:39:40'),
(177, '', 'best', NULL, 12, 's:f38e3c353dbfb41d0b7b869eaa2ac4a24c3f49082d67429f', '{\"surface\":\"catalog\"}', '2026-03-27 12:39:40'),
(178, '', 'all', NULL, 12, 's:f38e3c353dbfb41d0b7b869eaa2ac4a24c3f49082d67429f', '{\"surface\":\"catalog\"}', '2026-03-27 12:39:40'),
(179, '', 'all', NULL, 12, 's:f38e3c353dbfb41d0b7b869eaa2ac4a24c3f49082d67429f', '{\"mode\":\"comfort\"}', '2026-03-27 12:40:08'),
(180, '', 'all', NULL, 12, 's:f38e3c353dbfb41d0b7b869eaa2ac4a24c3f49082d67429f', '{\"mode\":\"compact\"}', '2026-03-27 12:40:09'),
(181, '', 'top', NULL, 12, 's:f38e3c353dbfb41d0b7b869eaa2ac4a24c3f49082d67429f', '{\"surface\":\"catalog\"}', '2026-03-27 19:56:06'),
(182, '', 'best', NULL, 12, 's:f38e3c353dbfb41d0b7b869eaa2ac4a24c3f49082d67429f', '{\"surface\":\"catalog\"}', '2026-03-27 19:56:06'),
(183, '', 'all', NULL, 12, 's:f38e3c353dbfb41d0b7b869eaa2ac4a24c3f49082d67429f', '{\"surface\":\"catalog\"}', '2026-03-27 19:56:07'),
(184, 'course_click', 'best', 9, 12, 's:f38e3c353dbfb41d0b7b869eaa2ac4a24c3f49082d67429f', '{\"sponsored\":\"0\",\"surface\":\"catalog\"}', '2026-03-27 19:56:10'),
(185, '', 'top', NULL, 12, 's:f38e3c353dbfb41d0b7b869eaa2ac4a24c3f49082d67429f', '{\"surface\":\"catalog\"}', '2026-03-28 19:34:19'),
(186, '', 'best', NULL, 12, 's:f38e3c353dbfb41d0b7b869eaa2ac4a24c3f49082d67429f', '{\"surface\":\"catalog\"}', '2026-03-28 19:34:19'),
(187, '', 'all', NULL, 12, 's:f38e3c353dbfb41d0b7b869eaa2ac4a24c3f49082d67429f', '{\"surface\":\"catalog\"}', '2026-03-28 19:34:19'),
(188, 'purchase', 'unknown', 15, 14, NULL, '{\"order_id\":45,\"order_item_id\":49}', '2026-06-23 10:49:13');

-- --------------------------------------------------------

--
-- Structure de la table `catalog_order_item_attributions`
--

CREATE TABLE `catalog_order_item_attributions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_item_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `block_key` varchar(64) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `catalog_order_item_attributions`
--

INSERT INTO `catalog_order_item_attributions` (`id`, `order_item_id`, `order_id`, `course_id`, `user_id`, `block_key`, `created_at`) VALUES
(1, 35, 31, 4, 12, 'all', '2026-02-20 18:04:41'),
(2, 36, 32, 4, 12, 'all', '2026-02-20 18:28:26'),
(3, 44, 40, 9, 5, 'catalogue', '2026-06-04 17:29:31'),
(4, 45, 41, 9, 5, 'catalogue', '2026-06-04 17:39:27'),
(5, 46, 42, 9, 5, 'catalogue', '2026-06-04 17:44:21'),
(6, 47, 43, 15, 5, 'catalogue', '2026-06-10 15:50:36'),
(7, 48, 44, 15, 5, 'catalogue', '2026-06-20 19:13:06');

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `is_deleted`, `created_at`, `updated_at`) VALUES
(1, NULL, 'mike', 'evveveev', 0, '2025-11-29 08:46:12', '2025-11-29 08:46:55'),
(3, NULL, 'ouwade Michael', 'rggr', 1, '2025-11-29 08:47:19', '2026-02-23 18:05:19'),
(4, 1, 'Michaël', 'fef', 1, '2025-11-29 08:48:22', '2025-11-29 09:10:12'),
(6, NULL, 'ecole numerique', 'eegg', 1, '2025-11-29 09:10:33', '2025-11-29 09:13:59'),
(9, NULL, 'marketing', 'marketing', 0, '2026-01-10 19:25:37', '2026-03-24 12:10:42'),
(10, NULL, 'comptabilité', 'comptabilit-e', 0, '2026-03-25 11:27:19', '2026-03-25 11:27:19'),
(11, NULL, 'journalisme', 'journalisme', 0, '2026-06-20 17:10:12', '2026-06-20 17:10:12'),
(12, NULL, 'Bureautique', 'bureautique', 0, '2026-07-01 18:42:29', '2026-07-01 18:43:54'),
(13, NULL, 'Comptabilité', 'comptabilite', 0, '2026-07-01 18:42:29', '2026-07-01 18:43:54'),
(14, NULL, 'Marketing digital', 'marketing-digital', 0, '2026-07-01 18:42:29', '2026-07-01 18:43:54'),
(15, NULL, 'Développement web', 'developpement-web', 0, '2026-07-01 18:42:29', '2026-07-01 18:43:54'),
(16, NULL, 'Développement mobile', 'developpement-mobile', 0, '2026-07-01 18:42:29', '2026-07-01 18:43:54'),
(17, NULL, 'Design graphique', 'design-graphique', 0, '2026-07-01 18:42:29', '2026-07-01 18:43:54'),
(18, NULL, 'UI/UX Design', 'ui-ux-design', 0, '2026-07-01 18:42:29', '2026-07-01 18:43:54'),
(19, NULL, 'Data & IA', 'data-ia', 0, '2026-07-01 18:42:29', '2026-07-01 18:43:54'),
(20, NULL, 'Cybersécurité', 'cybersecurite', 0, '2026-07-01 18:42:29', '2026-07-01 18:43:54'),
(21, NULL, 'Réseaux & systèmes', 'reseaux-systemes', 0, '2026-07-01 18:42:29', '2026-07-01 18:43:54'),
(22, NULL, 'Gestion de projet', 'gestion-projet', 0, '2026-07-01 18:42:29', '2026-07-01 18:43:54'),
(23, NULL, 'Entrepreneuriat', 'entrepreneuriat', 0, '2026-07-01 18:42:29', '2026-07-01 18:43:54'),
(24, NULL, 'Vente & commerce', 'vente-commerce', 0, '2026-07-01 18:42:29', '2026-07-01 18:43:54'),
(25, NULL, 'Communication', 'communication', 0, '2026-07-01 18:42:29', '2026-07-01 18:43:54'),
(26, NULL, 'Référencement SEO', 'referencement-seo', 0, '2026-07-01 18:42:29', '2026-07-01 18:43:54'),
(27, NULL, 'Création de contenu', 'creation-de-contenu', 0, '2026-07-01 18:42:29', '2026-07-01 18:43:54'),
(28, NULL, 'Montage vidéo', 'montage-video', 0, '2026-07-01 18:42:29', '2026-07-01 18:43:54'),
(29, NULL, 'Langues', 'langues', 0, '2026-07-01 18:42:29', '2026-07-01 18:43:54'),
(30, NULL, 'Ressources humaines', 'ressources-humaines', 0, '2026-07-01 18:42:29', '2026-07-01 18:43:54'),
(31, NULL, 'Productivité personnelle', 'productivite-personnelle', 0, '2026-07-01 18:42:29', '2026-07-01 18:43:54');

-- --------------------------------------------------------

--
-- Structure de la table `category_proposals`
--

CREATE TABLE `category_proposals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_user_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `normalized_name` varchar(180) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `admin_note` varchar(255) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `certificates`
--

CREATE TABLE `certificates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `exam_id` bigint(20) UNSIGNED NOT NULL,
  `attempt_id` bigint(20) UNSIGNED DEFAULT NULL,
  `score` int(10) UNSIGNED DEFAULT NULL,
  `code` varchar(120) NOT NULL,
  `status` enum('issued','revoked') NOT NULL DEFAULT 'issued',
  `issued_at` datetime NOT NULL DEFAULT current_timestamp(),
  `revoked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `certificates`
--

INSERT INTO `certificates` (`id`, `user_id`, `course_id`, `exam_id`, `attempt_id`, `score`, `code`, `status`, `issued_at`, `revoked_at`, `created_at`, `updated_at`) VALUES
(1, 10, 4, 4, 2, 0, 'CERT-103C0090B936CD83', 'issued', '2026-01-08 16:39:08', NULL, '2026-01-08 16:39:08', '2026-01-08 16:39:08');

-- --------------------------------------------------------

--
-- Structure de la table `chapter_documents`
--

CREATE TABLE `chapter_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `chapter_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `type` enum('exercise','exam','resource') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime` varchar(120) NOT NULL,
  `size` bigint(20) UNSIGNED NOT NULL,
  `position` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `chapter_documents`
--

INSERT INTO `chapter_documents` (`id`, `chapter_id`, `title`, `type`, `file_path`, `mime`, `size`, `position`, `created_at`, `updated_at`) VALUES
(1, 3, 'geggege', 'exam', 'https://docs.google.com/presentation/d/1cR3ua8u8CAeLSUrUtyQXyQ9a2UYMyOT6SZdp57qQK9E/edit?usp=sharing', 'link/url', 0, 1, '2025-12-01 13:07:44', '2025-12-01 13:07:44'),
(2, 12, 'rien', 'resource', 'https://docs.google.com/presentation/d/1cR3ua8u8CAeLSUrUtyQXyQ9a2UYMyOT6SZdp57qQK9E/edit?usp=drive_link', 'url', 0, 1, '2025-12-10 10:18:15', '2025-12-10 10:18:15'),
(3, 13, 'exercice', 'exercise', 'https://docs.google.com/presentation/d/1cR3ua8u8CAeLSUrUtyQXyQ9a2UYMyOT6SZdp57qQK9E/edit?usp=drive_link', 'url', 0, 1, '2025-12-10 10:18:39', '2025-12-10 10:18:39'),
(4, 18, 'rien', 'resource', 'https://docs.google.com/presentation/d/1cR3ua8u8CAeLSUrUtyQXyQ9a2UYMyOT6SZdp57qQK9E/edit?usp=drive_link', 'url', 0, 1, '2025-12-18 11:13:35', '2025-12-18 11:13:35'),
(5, 19, 'exercice', 'exercise', 'https://docs.google.com/presentation/d/1cR3ua8u8CAeLSUrUtyQXyQ9a2UYMyOT6SZdp57qQK9E/edit?usp=drive_link', 'url', 0, 1, '2025-12-18 11:13:35', '2025-12-18 11:13:35'),
(6, 20, 'exercice', 'resource', 'https://docs.google.com/document/d/1Y-HXRJwX8bbvPPvGq7mCT0nTW-V5HrIo/edit?usp=drivesdk&ouid=113809619878434382501&rtpof=true&sd=true', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document|doc:file|lesson:13', 19261, 1, '2026-01-07 12:38:34', '2026-01-07 12:38:34'),
(7, 21, 'rien', 'resource', 'https://docs.google.com/document/d/18RMZtPYq5T5DNI6dy8AArDfePKX_CSGy/edit?usp=drivesdk&ouid=113809619878434382501&rtpof=true&sd=true', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document|doc:file|lesson:16', 19261, 1, '2026-01-07 12:39:32', '2026-01-07 12:39:32'),
(8, 22, 'rien', 'resource', 'https://docs.google.com/document/d/12ceV5ls5nvKyB2gZ8MmKSXxx73r9OZKb/edit?usp=drivesdk&ouid=113809619878434382501&rtpof=true&sd=true', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document|doc:document', 2531158, 1, '2026-01-07 12:40:30', '2026-01-07 12:40:30'),
(9, 26, 'rien', 'resource', 'https://docs.google.com/document/d/1TDCOk7ACcXDddRszQEV2lySFM0Du1zK5/edit?usp=drivesdk&ouid=113809619878434382501&rtpof=true&sd=true', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document|doc:document|lesson:22', 59819, 1, '2026-03-26 19:14:29', '2026-03-26 19:14:29'),
(10, 26, 'rien', 'resource', 'https://drive.google.com/file/d/1AxC1cO_ocakL6OtgbnYRd0X5ORIZWSMJ/view?usp=drivesdk', 'application/pdf|doc:document', 68189, 2, '2026-03-26 19:15:05', '2026-03-26 19:15:05'),
(11, 26, 'rien', 'exercise', 'https://docs.google.com/spreadsheets/d/1c18bn75_EGCWn9pcsPoDskba3wZA8G9T/edit?usp=drivesdk&ouid=113809619878434382501&rtpof=true&sd=true', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 39504, 3, '2026-03-26 19:30:16', '2026-03-26 19:30:16'),
(12, 26, 'exercice', 'exercise', 'https://docs.google.com/spreadsheets/d/1ZEn_t2EYdu67Q6OkVuMTIaSZrMUtrFgP/edit?usp=drivesdk&ouid=113809619878434382501&rtpof=true&sd=true', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 67245, 4, '2026-03-26 19:44:28', '2026-03-26 19:44:28'),
(14, 30, 'rien', 'resource', 'http://localhost/ecolenumerique/storage/uploads/drive/2026/05/ressource_17_20260520_202002_Capture_d_cran_2026-05-19_155500_20260520_202002_4438df1c.png', 'image/png|doc:file', 329826, 1, '2026-05-20 20:20:02', '2026-05-20 20:20:02');

-- --------------------------------------------------------

--
-- Structure de la table `companies`
--

CREATE TABLE `companies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(190) NOT NULL,
  `admin_user_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `company_email` varchar(190) DEFAULT NULL,
  `company_phone` varchar(30) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `address_line` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `companies`
--

INSERT INTO `companies` (`id`, `name`, `admin_user_id`, `status`, `created_at`, `updated_at`, `company_email`, `company_phone`, `country`, `city`, `address_line`, `logo_path`) VALUES
(1, 'michael', 12, 'active', '2026-01-15 14:04:08', '2026-01-15 14:04:08', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'rrrrrrrrrrrr', 14, 'active', '2026-05-25 19:46:18', '2026-05-25 19:46:18', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `company_activity_events`
--

CREATE TABLE `company_activity_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `actor_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `event_type` varchar(80) NOT NULL,
  `title` varchar(190) NOT NULL,
  `meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_json`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `company_activity_events`
--

INSERT INTO `company_activity_events` (`id`, `company_id`, `actor_user_id`, `event_type`, `title`, `meta_json`, `created_at`) VALUES
(1, 1, 12, 'invite_resent', 'Invitation relancee: m45885679@gmail.com', '{\"invite_id\":2,\"mode\":\"email\"}', '2026-03-09 16:05:25'),
(2, 1, 12, 'team_created', 'Nouvelle equipe: mike', '{\"team_name\":\"mike\"}', '2026-03-09 17:18:37'),
(3, 1, 12, 'team_archived', 'Equipe archivee', '{\"team_id\":1}', '2026-03-09 17:18:42');

-- --------------------------------------------------------

--
-- Structure de la table `company_course_seat_assignments`
--

CREATE TABLE `company_course_seat_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `member_user_id` bigint(20) UNSIGNED NOT NULL,
  `assigned_by` bigint(20) UNSIGNED NOT NULL,
  `status` enum('active','revoked') NOT NULL DEFAULT 'active',
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `revoked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `company_course_seat_assignments`
--

INSERT INTO `company_course_seat_assignments` (`id`, `company_id`, `course_id`, `member_user_id`, `assigned_by`, `status`, `assigned_at`, `revoked_at`, `created_at`, `updated_at`) VALUES
(1, 2, 15, 14, 14, 'active', '2026-06-23 10:54:29', NULL, '2026-06-23 10:54:29', '2026-06-23 10:54:29');

-- --------------------------------------------------------

--
-- Structure de la table `company_course_seat_purchases`
--

CREATE TABLE `company_course_seat_purchases` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `order_item_id` bigint(20) UNSIGNED NOT NULL,
  `purchased_by` bigint(20) UNSIGNED NOT NULL,
  `seats_total` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `unit_price_xof` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_xof` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('active','canceled') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `company_course_seat_purchases`
--

INSERT INTO `company_course_seat_purchases` (`id`, `company_id`, `course_id`, `order_id`, `order_item_id`, `purchased_by`, `seats_total`, `unit_price_xof`, `total_xof`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 15, 45, 49, 14, 1, 2000, 2000, 'active', '2026-06-23 10:49:13', '2026-06-23 10:49:13');

-- --------------------------------------------------------

--
-- Structure de la table `company_invites`
--

CREATE TABLE `company_invites` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `invite_mode` enum('email','link') NOT NULL DEFAULT 'email',
  `token_hash` char(64) NOT NULL,
  `status` enum('pending','awaiting_approval','accepted','revoked','expired') NOT NULL DEFAULT 'pending',
  `approval_status` enum('none','pending','approved','rejected') NOT NULL DEFAULT 'none',
  `approval_required` tinyint(1) NOT NULL DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `invited_by` bigint(20) UNSIGNED NOT NULL,
  `requested_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `company_invites`
--

INSERT INTO `company_invites` (`id`, `company_id`, `email`, `invite_mode`, `token_hash`, `status`, `approval_status`, `approval_required`, `expires_at`, `invited_by`, `requested_user_id`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'ouwade4@gmail.com', 'email', '8f010c6e6603e12d0fbe0e1ab159ce1a1c12464d1bf5e97fac2175e4d8adae72', 'pending', 'none', 0, '2026-01-22 14:05:10', 12, NULL, NULL, NULL, '2026-01-15 14:05:10', '2026-01-15 14:05:10'),
(2, 1, 'm45885679@gmail.com', 'email', '22e30c5e7beaa778db9eb6cfa8c6df6fa9ed3f975a50a7d0d82a1203e29dbd13', 'pending', 'none', 0, '2026-03-16 16:05:25', 12, NULL, NULL, NULL, '2026-03-07 15:22:33', '2026-03-09 16:05:25'),
(3, 2, 'ekim65500@gmail.com', 'email', '56ee29a13fddf6d286519b893e54fbb2445bbb4005d0452578a0a025029f4422', 'pending', 'none', 0, '2026-06-01 19:47:36', 14, NULL, NULL, NULL, '2026-05-25 19:47:36', '2026-05-25 19:47:36');

-- --------------------------------------------------------

--
-- Structure de la table `company_members`
--

CREATE TABLE `company_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role` enum('owner','admin','manager','member','viewer') NOT NULL DEFAULT 'member',
  `status` enum('active','blocked') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `company_members`
--

INSERT INTO `company_members` (`id`, `company_id`, `user_id`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 12, 'admin', 'active', '2026-01-15 14:04:08', '2026-01-15 14:04:08'),
(2, 2, 14, 'owner', 'active', '2026-05-25 19:46:18', '2026-05-25 19:46:18');

-- --------------------------------------------------------

--
-- Structure de la table `company_metrics_daily`
--

CREATE TABLE `company_metrics_daily` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `metric_date` date NOT NULL,
  `members_total` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `members_active` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `invites_pending` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `activation_30d` decimal(5,2) NOT NULL DEFAULT 0.00,
  `wau` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `mau` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `wau_mau_ratio` decimal(5,2) NOT NULL DEFAULT 0.00,
  `retention_90d` decimal(5,2) NOT NULL DEFAULT 0.00,
  `expansion_30d` int(11) NOT NULL DEFAULT 0,
  `churn_30d` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `company_onboarding_tasks`
--

CREATE TABLE `company_onboarding_tasks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `task_key` varchar(80) NOT NULL,
  `title` varchar(190) NOT NULL,
  `description` text DEFAULT NULL,
  `due_at` datetime DEFAULT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('pending','in_progress','done','blocked') NOT NULL DEFAULT 'pending',
  `owner_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `company_onboarding_tasks`
--

INSERT INTO `company_onboarding_tasks` (`id`, `company_id`, `task_key`, `title`, `description`, `due_at`, `sort_order`, `status`, `owner_user_id`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'profile', 'Configurer l organisation', 'Nom, referents, objectifs de depart.', '2026-03-09 14:54:38', 10, 'pending', 12, NULL, '2026-03-09 14:54:38', '2026-03-09 14:54:38'),
(2, 1, 'invites', 'Inviter les membres cles', 'Invitez au moins 3 membres de l equipe.', '2026-03-16 14:54:38', 20, 'pending', 12, NULL, '2026-03-09 14:54:38', '2026-03-09 14:54:38'),
(3, 1, 'first_review', 'Programmer la revue de pilotage', 'Planifiez une revue de suivi a 30 jours.', '2026-04-08 14:54:38', 30, 'pending', 12, NULL, '2026-03-09 14:54:38', '2026-03-09 14:54:38');

-- --------------------------------------------------------

--
-- Structure de la table `company_sso_configs`
--

CREATE TABLE `company_sso_configs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `provider` enum('saml','oidc') NOT NULL DEFAULT 'oidc',
  `domain` varchar(190) DEFAULT NULL,
  `issuer` varchar(255) DEFAULT NULL,
  `entry_point` varchar(255) DEFAULT NULL,
  `cert_pem` text DEFAULT NULL,
  `client_id` varchar(190) DEFAULT NULL,
  `client_secret_enc` text DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `company_success_reviews`
--

CREATE TABLE `company_success_reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `owner_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `review_date` date NOT NULL,
  `cadence` enum('monthly','quarterly') NOT NULL DEFAULT 'monthly',
  `risk_level` enum('low','medium','high') NOT NULL DEFAULT 'low',
  `status` enum('planned','done','blocked') NOT NULL DEFAULT 'planned',
  `notes` text DEFAULT NULL,
  `next_actions` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `company_teams`
--

CREATE TABLE `company_teams` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(160) NOT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `company_teams`
--

INSERT INTO `company_teams` (`id`, `company_id`, `name`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'mike', 'archived', 12, '2026-03-09 17:18:37', '2026-03-09 17:18:42');

-- --------------------------------------------------------

--
-- Structure de la table `company_team_members`
--

CREATE TABLE `company_team_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `team_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` bigint(20) UNSIGNED NOT NULL,
  `assigned_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `courses`
--

CREATE TABLE `courses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `share_token` varchar(120) DEFAULT NULL,
  `level` enum('beginner','intermediate','advanced') NOT NULL,
  `delivery_mode` enum('online','presential') NOT NULL DEFAULT 'online',
  `course_location` varchar(190) DEFAULT NULL,
  `description` mediumtext NOT NULL,
  `objectives` mediumtext DEFAULT NULL,
  `prerequisites` mediumtext DEFAULT NULL,
  `equipment` mediumtext DEFAULT NULL,
  `duration_hours` decimal(6,2) DEFAULT NULL,
  `price_xof` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `enterprise_price_xof` int(10) UNSIGNED DEFAULT NULL,
  `enterprise_discount_percent` decimal(5,2) DEFAULT NULL,
  `referral_override` tinyint(1) NOT NULL DEFAULT 0,
  `referral_l1_rate` decimal(5,2) DEFAULT NULL,
  `referral_l2_rate` decimal(5,2) DEFAULT NULL,
  `referral_l2_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft',
  `update_in_progress` tinyint(1) NOT NULL DEFAULT 0,
  `cover_path` varchar(255) DEFAULT NULL,
  `cover_alt` varchar(180) DEFAULT NULL,
  `cover_variants_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cover_variants_json`)),
  `general_file_path` varchar(255) DEFAULT NULL,
  `general_file_mime` varchar(120) DEFAULT NULL,
  `general_file_size` bigint(20) UNSIGNED DEFAULT NULL,
  `seo_title` varchar(160) DEFAULT NULL,
  `seo_description` varchar(255) DEFAULT NULL,
  `version_of` bigint(20) UNSIGNED DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `last_rejection_reason` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `promo_type` enum('amount','percent') DEFAULT NULL,
  `promo_value` decimal(10,2) DEFAULT NULL,
  `promo_start_at` datetime DEFAULT NULL,
  `promo_end_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `courses`
--

INSERT INTO `courses` (`id`, `teacher_id`, `category_id`, `title`, `slug`, `share_token`, `level`, `delivery_mode`, `course_location`, `description`, `objectives`, `prerequisites`, `equipment`, `duration_hours`, `price_xof`, `enterprise_price_xof`, `enterprise_discount_percent`, `referral_override`, `referral_l1_rate`, `referral_l2_rate`, `referral_l2_enabled`, `status`, `update_in_progress`, `cover_path`, `cover_alt`, `cover_variants_json`, `general_file_path`, `general_file_mime`, `general_file_size`, `seo_title`, `seo_description`, `version_of`, `deleted_at`, `submitted_at`, `last_rejection_reason`, `created_at`, `updated_at`, `promo_type`, `promo_value`, `promo_start_at`, `promo_end_at`) VALUES
(1, 1, 3, 'ertyu', 'ertyu', NULL, 'beginner', 'online', NULL, 'qwersrxdcfgvyubhjni,k', 'hjnklxrctvuybhijn,k\r\nhbjnk,m', 'wxecrfvygbuhijno', 'xrrxdcfgvybhjn', 4.00, 3, NULL, NULL, 0, NULL, NULL, 0, 'draft', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-10 10:10:07', NULL, NULL, '2025-11-29 09:30:20', '2025-12-10 10:10:07', NULL, NULL, NULL, NULL),
(2, 1, 3, 'rgrrb', 'rgrrb', NULL, 'advanced', 'online', NULL, 'egegegeegegeg', 'rrrb', 'rbrbrb', 'brbbrb', 5.00, 2000, NULL, NULL, 0, NULL, NULL, 0, 'approved', 0, 'storage/uploads/covers/37d62fb1af66c753.png', 'rien', NULL, 'https://docs.google.com/presentation/d/1cR3ua8u8CAeLSUrUtyQXyQ9a2UYMyOT6SZdp57qQK9E/edit?usp=sharing', NULL, NULL, NULL, NULL, NULL, '2025-12-08 10:56:38', '2025-12-01 13:07:47', NULL, '2025-11-29 14:53:25', '2025-12-08 10:56:38', NULL, NULL, NULL, NULL),
(3, 1, 1, 'scsvsvsvssvsv', 'scsvsvsvssvsv', NULL, 'beginner', 'online', NULL, 'dvedvdvdvdv', 'dvdvdvd', 'dvdvdvdvdvd', 'vdvdvdvdvd', 20.00, 2000, NULL, NULL, 0, NULL, NULL, 0, 'draft', 0, 'storage/uploads/covers/b1ff4ca0da5c65fd.png', 'vdvdvvd', NULL, 'https://docs.google.com/presentation/d/1cR3ua8u8CAeLSUrUtyQXyQ9a2UYMyOT6SZdp57qQK9E/edit?usp=sharing', NULL, NULL, NULL, NULL, NULL, '2025-12-10 10:10:06', NULL, NULL, '2025-12-08 16:26:58', '2025-12-10 10:10:06', NULL, NULL, NULL, NULL),
(4, 1, 1, 'word', 'word', '4@aCt73pTavUMGQcLeF8yJrKAhNZ-sHuyVl2PBjU5D', 'advanced', 'online', NULL, 'apprendre les base de word', 'maitriser word en 20h', 'maitriser la dactylo', 'ordinateur', 20.00, 2000, NULL, NULL, 0, NULL, NULL, 0, 'approved', 0, 'storage/uploads/covers/0082e6085a644bb4.png', 'image', NULL, 'https://docs.google.com/presentation/d/1cR3ua8u8CAeLSUrUtyQXyQ9a2UYMyOT6SZdp57qQK9E/edit?usp=sharing', NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-10 10:42:48', NULL, '2025-12-10 10:12:17', '2026-06-12 00:46:31', NULL, NULL, NULL, NULL),
(5, 1, 1, 'Excel', 'excel', '5@uoYnFpPutVlNFZqB-yBoA_CnzRtY0Z-_g3RvD5Aa', 'advanced', 'online', NULL, 'Exemples de problèmes que tu sais résoudre\r\n\r\nOptimiser les tournées de livraison (moins de km, moins de carburant)\r\n\r\nPlanifier des horaires d’employés (respect contraintes + coût minimum)\r\n\r\nGérer les stocks (éviter ruptures + réduire immobilisation)\r\n\r\nAffecter des ressources (véhicules, équipes, machines) au meilleur coût\r\n\r\nChoisir où placer des dépôts (localisation optimale)\r\n\r\nRéduire le temps d’attente dans un service (banque, hôpital, guichet)', '[\"Premier projet Flutter mobile\",\"Configuration du simulateur iOS (macOS)\",\"Installation des émulateurs Android (AVD)\",\"Installation de Flutter SDK\",\"Configuration Android Studio \\/ VS Code\"]', '[\"flutter\",\"js\",\"php\"]', NULL, 0.42, 3000, NULL, NULL, 0, NULL, NULL, 0, 'approved', 0, 'storage/uploads/covers/465b664f57bd3e0d.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-17 12:12:21', NULL, '2025-12-16 15:25:06', '2026-07-01 14:14:41', 'percent', 50.00, '2026-07-01 14:14:41', '2026-07-31 14:14:41'),
(6, 1, 1, 'Excel', 'excel-2', NULL, 'advanced', 'online', NULL, 'Exemples de problèmes que tu sais résoudre\r\nOptimiser les tournées de livraison (moins de km, moins de carburant)\r\nPlanifier des horaires d’employés (respect contraintes + coût minimum)\r\nGérer les stocks (éviter ruptures + réduire immobilisation)\r\nAffecter des ressources (véhicules, équipes, machines) au meilleur coût\r\nChoisir où placer des dépôts (localisation optimale)\r\nRéduire le temps d’attente dans un service (banque, hôpital, guichet)', '[\"Premier projet Flutter mobile\"]', '[\"flutter\"]', NULL, 2.00, 3000, NULL, NULL, 0, NULL, NULL, 0, 'draft', 0, 'storage/uploads/covers/1b05c10905a67179.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-16 15:26:46', '2025-12-16 15:26:46', NULL, NULL, NULL, NULL),
(7, 1, 1, 'Excel', 'excel-3', NULL, 'advanced', 'online', NULL, 'Exemples de problèmes que tu sais résoudre\r\nOptimiser les tournées de livraison (moins de km, moins de carburant)\r\nPlanifier des horaires d’employés (respect contraintes + coût minimum)\r\nGérer les stocks (éviter ruptures + réduire immobilisation)\r\nAffecter des ressources (véhicules, équipes, machines) au meilleur coût\r\nChoisir où placer des dépôts (localisation optimale)\r\nRéduire le temps d’attente dans un service (banque, hôpital, guichet)', '[\"Premier projet Flutter mobile\",\"Configuration du simulateur iOS (macOS)\"]', '[\"flutter\",\"js\"]', '[\"pc\",\"ordi\"]', 2.00, 3000, NULL, NULL, 0, NULL, NULL, 0, 'draft', 0, 'storage/uploads/covers/220cb4bef2506dfe.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-07 12:54:02', NULL, NULL, '2025-12-16 15:27:28', '2026-01-07 12:54:02', NULL, NULL, NULL, NULL),
(8, 1, 1, 'word', 'word-v1766056415', NULL, 'advanced', 'online', NULL, 'apprendre les base de word', 'maitriser word en 20h', 'maitriser la dactylo', 'ordinateur', 20.00, 2000, NULL, NULL, 0, NULL, NULL, 0, 'draft', 0, 'storage/uploads/covers/0082e6085a644bb4.png', 'image', NULL, 'https://docs.google.com/presentation/d/1cR3ua8u8CAeLSUrUtyQXyQ9a2UYMyOT6SZdp57qQK9E/edit?usp=sharing', NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, '2025-12-18 11:13:35', '2025-12-18 11:13:35', NULL, NULL, NULL, NULL),
(9, 1, 1, 'Excel', 'excel-4', '9@5UEkhK2m7AjMP-96728QRxeWNqFcfJTPBOx_m26-', 'advanced', 'online', NULL, 'grrererrregergregrg', '[\"Premier projet Flutter mobile\",\"Premier projet Flutter mobile\"]', '[\"flutter\",\"flutter\"]', '[\"pc\",\"pc\"]', 3.60, 2000, NULL, NULL, 0, NULL, NULL, 0, 'approved', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-07 12:53:56', NULL, '2026-01-07 12:27:56', '2026-06-10 18:45:58', NULL, NULL, NULL, NULL),
(10, 10, 9, 'Marketing digital', 'marketing-digital', 'a@zkKpu3e9xa0tgp4qpLVNJYnxqK6D7RzK0Q2FeJjJ', 'beginner', 'online', NULL, 'Apprenez a structurer votre presence en ligne, a publier du contenu utile et a transformer Facebook, WhatsApp et Instagram en leviers de vente.', '[\"Definir une offre claire pour votre activite.\",\"Choisir les canaux les plus utiles pour votre public.\",\"Creer des publications simples et convaincantes.\",\"Lancer des campagnes de base et suivre les resultats.\",\"Utiliser WhatsApp Business pour convertir davantage.\"]', '[\"Savoir utiliser un smartphone ou un ordinateur.\",\"Avoir une connexion Internet stable.\",\"Disposer d\'un compte Facebook ou WhatsApp.\"]', '[\"Un smartphone ou un ordinateur.\",\"Une connexion Internet.\",\"Un compte Facebook Business ou WhatsApp Business recommande.\"]', 1.00, 5000, NULL, NULL, 0, NULL, NULL, 0, 'approved', 0, 'storage/uploads/covers/marketing-digital-cover.svg', 'Illustration marketing digital', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-10 19:57:17', NULL, '2026-01-10 19:31:18', '2026-06-10 19:53:32', NULL, NULL, NULL, NULL),
(11, 1, 1, 'Excel', 'excel-2-copy-20260216180710-6869', NULL, 'advanced', 'online', NULL, 'Exemples de problèmes que tu sais résoudre\r\nOptimiser les tournées de livraison (moins de km, moins de carburant)\r\nPlanifier des horaires d’employés (respect contraintes + coût minimum)\r\nGérer les stocks (éviter ruptures + réduire immobilisation)\r\nAffecter des ressources (véhicules, équipes, machines) au meilleur coût\r\nChoisir où placer des dépôts (localisation optimale)\r\nRéduire le temps d’attente dans un service (banque, hôpital, guichet)', '[\"Premier projet Flutter mobile\"]', '[\"flutter\"]', NULL, 2.00, 3000, NULL, NULL, 0, NULL, NULL, 0, 'draft', 0, 'storage/uploads/covers/1b05c10905a67179.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, NULL, NULL, NULL, '2026-02-16 18:07:10', '2026-02-16 18:07:10', NULL, NULL, NULL, NULL),
(12, 1, 1, 'Excel', 'excel-copy-20260217124712-e617', NULL, 'advanced', 'online', NULL, 'Exemples de problèmes que tu sais résoudre\r\n\r\nOptimiser les tournées de livraison (moins de km, moins de carburant)\r\n\r\nPlanifier des horaires d’employés (respect contraintes + coût minimum)\r\n\r\nGérer les stocks (éviter ruptures + réduire immobilisation)\r\n\r\nAffecter des ressources (véhicules, équipes, machines) au meilleur coût\r\n\r\nChoisir où placer des dépôts (localisation optimale)\r\n\r\nRéduire le temps d’attente dans un service (banque, hôpital, guichet)', '[\"Premier projet Flutter mobile\",\"Configuration du simulateur iOS (macOS)\",\"Installation des émulateurs Android (AVD)\",\"Installation de Flutter SDK\",\"Configuration Android Studio \\/ VS Code\"]', '[\"flutter\",\"js\",\"php\"]', NULL, 0.42, 3000, NULL, NULL, 0, NULL, NULL, 0, 'draft', 0, 'storage/uploads/covers/465b664f57bd3e0d.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, NULL, NULL, NULL, '2026-02-17 12:47:12', '2026-02-17 12:47:12', NULL, NULL, NULL, NULL),
(13, 8, 9, 'React js', 'react-js', NULL, 'intermediate', 'online', NULL, 'detaille ça au complet # Cours complet de JavaScript \r\n\r\nJe prépare ici un support structuré de formation JavaScript en français, pensé comme un vrai cours progressif : bases, logique, fonctions, objets, DOM, asynchronisme, API, modules, bonnes pratiques, mini-projets et exercices.\r\n\r\nLe document final sera également exporté en PDF avec une mise en page professionnelle.\r\n\r\n## Plan du cours\r\n\r\n1. Introduction à JavaScript\r\n2. Installation et environnement de travail\r\n3. Syntaxe de base\r\n4. Variables, types et conversions\r\n5. Opérateurs\r\n6. Conditions\r\n7. Boucles\r\n8. Fonctions\r\n9. Tableaux\r\n10. Objets\r\n11. Chaînes de caractères\r\n12. Nombres, dates et mathématiques\r\n13. Manipulation du DOM\r\n14. Événements\r\n15. Classes et programmation orientée objet\r\n16. Gestion des erreurs\r\n17. JavaScript asynchrone\r\n18. Fetch et APIs\r\n19. Modules JavaScript\r\n20. Stockage local\r\n21. Bonnes pratiques\r\n22. Débogage\r\n23. Exercices et mini-projets\r\n24. Glossaire\r\n25. Conclusion', '[\"fondement\",\"pratique\"]', '[\"javascript\",\"html\",\"css\"]', '[\"ordinateur\",\"connexion\"]', 2.00, 0, 1000, NULL, 0, NULL, NULL, 0, 'draft', 0, 'storage/uploads/covers/2e7a4ca564981df2.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:32:10', '2026-03-11 13:32:10', NULL, NULL, NULL, NULL),
(14, 1, 10, 'rgrrgrggrrerr', 'rgrrgrggrrerr', NULL, 'intermediate', 'online', NULL, 'rgggggggggggggggggggggggggggggggggggggggggggggggggggggerrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrr', '[\"Premier projet Flutter mobile\"]', '[\"flutter\"]', '[\"pc\"]', 0.33, 2500, NULL, NULL, 0, NULL, NULL, 0, 'draft', 0, 'storage/uploads/covers/736a8dcfd4f198ca.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-25 16:25:53', '2026-03-26 18:53:23', NULL, NULL, NULL, NULL),
(15, 1, 10, 'mike php', 'mike-php', 'f@kZHf0K5QxUjhLbUrN7vI3XG6Jb4VyqKHBDCgOK5F', 'intermediate', 'online', NULL, 'Un brouillon local est disponible. Vous pouvez restaurer les champs tels qu\'ils étaient. Restaurer Ignorer Un brouillon local est disponible. Vous pouvez restaurer les champs tels qu\'ils étaient. Restaurer Ignorer', '[\"Premier projet Flutter mobile\"]', '[\"connaissances php\"]', '[\"SOURIS, PC Laptop\"]', 0.42, 2000, NULL, NULL, 0, NULL, NULL, 0, 'approved', 0, 'storage/uploads/covers/c0ff2b96b0134f62.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-17 21:04:14', NULL, '2026-04-07 17:30:05', '2026-07-01 13:21:48', NULL, NULL, NULL, NULL),
(16, 1, 10, 'bonjours', 'bonjours', NULL, 'intermediate', 'online', NULL, 'hrhrhrhrhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhh', '[\"Premier projet Flutter mobile\"]', '[\"flutter\"]', '[\"pc\"]', 2.00, 2500, 5000, NULL, 1, NULL, NULL, 0, 'draft', 0, 'storage/uploads/covers/69fabfa63164f4bb.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-02 18:39:26', '2026-04-17 16:06:03', NULL, NULL, NULL, NULL),
(17, 1, 10, 'hhhhhhhhhhh', 'hhhhhhhhhhh', NULL, 'intermediate', 'online', NULL, 'hhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkk', '[\",jhhhhhhhhhh\"]', '[\"n jjjjjjjjj\"]', '[\"kjkkkkkkkkkkkkkkkk\"]', 0.42, 52, NULL, NULL, 0, NULL, NULL, 0, 'draft', 0, 'storage/uploads/covers/67a20e09e9c4cb8f.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-20 20:07:02', '2026-05-20 20:18:49', NULL, NULL, NULL, NULL),
(18, 1, 10, 'rgrtrrrrrrrrrrrrrrrrr', 'rgrtrrrrrrrrrrrrrrrrr', NULL, 'intermediate', 'online', NULL, 'tttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttttt', '[\"tttttttttttttttt\",\"666\"]', '[\"666666\"]', '[\"6666666666\"]', 5.00, 15000, NULL, NULL, 0, NULL, NULL, 0, 'draft', 0, 'storage/uploads/covers/316890a4efa2e3b4.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-21 17:17:39', '2026-05-21 17:17:39', NULL, NULL, NULL, NULL),
(19, 1, NULL, 'eee', 'eee', NULL, 'beginner', 'online', NULL, 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', '[\"Premier projet Flutter mobile\"]', '[\"flutter\"]', '[\"pc\"]', 0.25, 5000, NULL, NULL, 0, NULL, NULL, 0, 'submitted', 0, 'storage/uploads/covers/945ac2679fa781bd.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-04 18:36:43', NULL, '2026-06-04 18:36:43', '2026-06-04 18:36:43', NULL, NULL, NULL, NULL),
(20, 1, 10, 'mike', 'mike', NULL, 'intermediate', 'online', NULL, 'bonjoureeeeeeeeeeeeeeeeee', '[\"Premier projet Flutter mobile\",\"Premier projet Flutter mobile\"]', '[\"flutter\",\"connaissances php\"]', '[\"pc\",\"SOURIS, PC Laptop\"]', 0.25, 1500, NULL, NULL, 0, NULL, NULL, 0, 'draft', 0, 'storage/uploads/covers/2c25a53d6dd3ab04.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-19 14:49:24', '2026-06-19 15:37:16', NULL, NULL, NULL, NULL),
(21, 1, 10, 'mike', 'mike-2', NULL, 'intermediate', 'online', NULL, 'bonjoureeeeeeeeeeeeeeeeee', '[\"Premier projet Flutter mobile\",\"Premier projet Flutter mobile\"]', '[\"flutter\",\"connaissances php\"]', '[\"pc\",\"SOURIS, PC Laptop\"]', 0.25, 1500, NULL, NULL, 0, NULL, NULL, 0, 'draft', 0, 'storage/uploads/covers/27b7e14816c934a8.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-19 15:28:02', '2026-06-19 15:28:02', NULL, NULL, NULL, NULL),
(22, 1, 9, 'eeeeeeeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeee', NULL, 'beginner', 'online', NULL, 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeefeeeeeeeeeeeeeeeeeeeeeeeeeeeee', '[\"jkkkkkkkkkkkkk\"]', '[\"flutter\"]', '[\"SOURIS, PC Laptop\"]', 0.25, 1500, NULL, NULL, 0, NULL, NULL, 0, 'submitted', 0, 'storage/uploads/covers/3cc4d57cd8b775ca.png', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-02 16:27:20', NULL, '2026-06-25 21:29:48', '2026-07-02 16:27:20', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `course_chapters`
--

CREATE TABLE `course_chapters` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `position` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `course_chapters`
--

INSERT INTO `course_chapters` (`id`, `course_id`, `title`, `description`, `position`, `created_at`, `updated_at`) VALUES
(1, 2, 'hgfdx', 'nbvcx', 1, '2025-11-29 14:54:10', '2025-11-29 14:54:10'),
(3, 2, 'fdgfd', 'gfdfd', 2, '2025-11-29 14:54:32', '2025-11-29 14:54:32'),
(6, 2, 'efeefe', 'fefef', 3, '2025-11-29 16:59:59', '2025-11-29 17:29:14'),
(8, 2, 'yuiop', 'hjkl', 4, '2025-12-01 10:39:10', '2025-12-01 10:39:10'),
(9, 1, 'efeefefe', NULL, 1, '2025-12-01 16:47:02', '2025-12-01 16:47:02'),
(10, 3, 'dvdvdvdvd', 'vdvdvdv', 1, '2025-12-08 16:27:22', '2025-12-08 16:27:22'),
(11, 3, 'dvdvdvdvd', 'vdvdvdv', 2, '2025-12-08 16:31:01', '2025-12-08 16:31:01'),
(12, 4, 'Introduction', 'ce qu\'on doit s\'attendre', 1, '2025-12-10 10:13:51', '2025-12-10 10:13:51'),
(13, 4, 'Dactylo', 'ce qu\'on doit s\'attendre', 2, '2025-12-10 10:14:22', '2025-12-10 10:14:22'),
(14, 7, 'Introduction', 'introduire', 1, '2025-12-16 15:56:05', '2025-12-16 15:56:05'),
(15, 7, 'clavier', 'clavier', 2, '2025-12-16 15:56:23', '2025-12-16 15:56:23'),
(16, 7, 'fichier', 'fichier', 3, '2025-12-16 15:56:41', '2025-12-16 15:56:41'),
(17, 7, 'fichier', 'fichier', 4, '2025-12-16 16:27:24', '2025-12-16 16:27:24'),
(18, 8, 'Introduction', 'ce qu\'on doit s\'attendre', 1, '2025-12-18 11:13:35', '2025-12-18 11:13:35'),
(19, 8, 'Dactylo', 'ce qu\'on doit s\'attendre', 2, '2025-12-18 11:13:35', '2025-12-18 11:13:35'),
(20, 9, 'Introduction', 'ce qu\'on doit s\'attendre', 1, '2026-01-07 12:30:12', '2026-01-07 12:30:12'),
(21, 9, 'Dactylo', NULL, 2, '2026-01-07 12:30:23', '2026-01-07 12:30:23'),
(22, 9, 'conclusion', NULL, 3, '2026-01-07 12:30:41', '2026-01-07 12:30:41'),
(23, 10, 'Introduction', 'Les bases du marketing digital et la logique de conversion.', 1, '2026-01-10 19:32:48', '2026-06-10 19:53:32'),
(24, 5, 'Introduction', 'cscscsscs', 1, '2026-02-16 18:07:51', '2026-02-16 18:07:51'),
(25, 12, 'Introduction', 'cscscsscs', 1, '2026-02-17 12:47:12', '2026-02-17 12:47:12'),
(26, 14, 'Introduction', 'ce qu\'on doit s\'attendre', 1, '2026-03-26 12:51:29', '2026-03-26 12:51:29'),
(27, 15, 'Introduction', NULL, 1, '2026-04-13 15:11:38', '2026-04-17 16:06:06'),
(29, 15, 'conclusion', 'ce qu\'on doit s\'attendre', 2, '2026-04-17 15:10:15', '2026-04-17 15:10:15'),
(30, 17, 'Introduction', 'ce qu\'on doit s\'attendre', 1, '2026-05-20 20:07:11', '2026-05-20 20:07:11'),
(31, 18, 'Introduction', NULL, 1, '2026-05-21 17:17:46', '2026-05-21 17:17:46'),
(32, 19, 'ssssssssssssssssssss', NULL, 1, '2026-06-04 18:36:43', '2026-06-04 18:36:43'),
(34, 21, 'introduction', 'bonjour', 1, '2026-06-19 15:28:02', '2026-06-19 15:28:02'),
(35, 20, 'introduction', 'bonjour', 1, '2026-06-19 15:37:16', '2026-06-19 15:37:16'),
(36, 22, 'gggggggggggggggggg', NULL, 1, '2026-07-02 16:27:20', '2026-07-02 16:27:20');

-- --------------------------------------------------------

--
-- Structure de la table `course_favorites`
--

CREATE TABLE `course_favorites` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `course_favorites`
--

INSERT INTO `course_favorites` (`id`, `user_id`, `course_id`, `created_at`) VALUES
(1, 9, 9, '2026-02-19 16:41:03'),
(2, 9, 10, '2026-02-19 16:41:05'),
(12, 5, 15, '2026-06-16 13:18:26'),
(13, 5, 5, '2026-06-16 13:18:32');

-- --------------------------------------------------------

--
-- Structure de la table `course_lessons`
--

CREATE TABLE `course_lessons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `chapter_id` bigint(20) UNSIGNED NOT NULL,
  `lesson_type` enum('video','document') NOT NULL,
  `youtube_url` varchar(255) DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `document_mime` varchar(120) DEFAULT NULL,
  `document_size` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `position` int(10) UNSIGNED NOT NULL,
  `duration_estimate_min` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `course_lessons`
--

INSERT INTO `course_lessons` (`id`, `chapter_id`, `lesson_type`, `youtube_url`, `document_path`, `document_mime`, `document_size`, `title`, `description`, `position`, `duration_estimate_min`, `created_at`, `updated_at`) VALUES
(1, 1, 'video', 'https://youtu.be/1tAr2NTDC8I?si=KeeuhO-6j-ZpEvMb', NULL, NULL, NULL, 'erherrzehe', 'egegegegzzgzegz', 1, 10, '2025-12-01 10:42:27', '2025-12-01 10:42:27'),
(2, 1, 'document', NULL, 'https://docs.google.com/presentation/d/1cR3ua8u8CAeLSUrUtyQXyQ9a2UYMyOT6SZdp57qQK9E/edit?usp=sharing', NULL, NULL, 'erherrzehe', 'vdvdvd', 2, 10, '2025-12-01 10:44:59', '2025-12-01 10:44:59'),
(3, 3, 'video', 'https://youtu.be/jZf7sXuC7Io?si=fUx1hC_30GdGY8Qi', NULL, NULL, NULL, 'erherrzehe', 'cscscs', 1, 10, '2025-12-01 10:45:31', '2025-12-01 10:45:31'),
(4, 3, 'document', NULL, 'https://docs.google.com/presentation/d/1cR3ua8u8CAeLSUrUtyQXyQ9a2UYMyOT6SZdp57qQK9E/edit?usp=sharing', NULL, NULL, 'cscsdvs', 'gfd', 2, 20, '2025-12-01 10:46:31', '2025-12-01 10:46:31'),
(5, 6, 'video', 'https://youtu.be/pajGFG_6eHg?si=Wabeca4a97gS6wyq', NULL, NULL, NULL, 'kjhgdfs', 'jythrgef', 1, 20, '2025-12-01 10:46:55', '2025-12-01 10:46:55'),
(6, 8, 'video', 'https://youtu.be/ZAfAud_M_mg?si=Y8-0mrOzuCkzF6xd', NULL, NULL, NULL, 'kjhgdfs', 'hjgfds', 1, 15, '2025-12-01 10:47:20', '2025-12-01 10:47:20'),
(7, 12, 'video', 'https://youtu.be/1tAr2NTDC8I?si=KeeuhO-6j-ZpEvMb', NULL, NULL, NULL, 'initiation', 'apprendre les', 1, 20, '2025-12-10 10:15:33', '2025-12-10 10:15:33'),
(8, 13, 'video', 'https://youtu.be/ZAfAud_M_mg?si=Y8-0mrOzuCkzF6xd', NULL, NULL, NULL, 'initiation', 'rien', 1, 50, '2025-12-10 10:16:21', '2025-12-10 10:16:21'),
(10, 18, 'video', 'https://youtu.be/1tAr2NTDC8I?si=KeeuhO-6j-ZpEvMb', NULL, NULL, NULL, 'initiation', 'apprendre les', 1, 20, '2025-12-18 11:13:35', '2025-12-18 11:13:35'),
(11, 19, 'video', 'https://youtu.be/ZAfAud_M_mg?si=Y8-0mrOzuCkzF6xd', NULL, NULL, NULL, 'initiation', 'rien', 1, 50, '2025-12-18 11:13:35', '2025-12-18 11:13:35'),
(13, 20, 'video', 'https://youtu.be/1tAr2NTDC8I?si=KeeuhO-6j-ZpEvMb', NULL, NULL, NULL, 'document', NULL, 1, 25, '2026-01-07 12:34:58', '2026-01-07 12:34:58'),
(14, 20, 'video', 'https://youtu.be/1tAr2NTDC8I?si=KeeuhO-6j-ZpEvMb', NULL, NULL, NULL, 'initiation', NULL, 2, 65, '2026-01-07 12:35:21', '2026-01-07 12:35:21'),
(15, 21, 'video', 'https://youtu.be/pajGFG_6eHg?si=Wabeca4a97gS6wyq', NULL, NULL, NULL, 'initiation', NULL, 1, NULL, '2026-01-07 12:36:01', '2026-01-07 12:36:01'),
(16, 21, 'video', 'https://youtu.be/ZAfAud_M_mg?si=Y8-0mrOzuCkzF6xd', NULL, NULL, NULL, 'initiation', NULL, 2, 25, '2026-01-07 12:36:28', '2026-01-07 12:36:28'),
(17, 22, 'video', 'https://youtu.be/pajGFG_6eHg?si=Wabeca4a97gS6wyq', NULL, NULL, NULL, 'initiation', NULL, 1, 60, '2026-01-07 12:37:02', '2026-01-07 12:37:02'),
(18, 22, 'video', 'https://youtu.be/pajGFG_6eHg?si=Wabeca4a97gS6wyq', NULL, NULL, NULL, 'document', NULL, 2, 41, '2026-01-07 12:37:33', '2026-01-07 12:37:33'),
(19, 23, 'video', 'https://youtu.be/4O8F54TzaYE', NULL, NULL, NULL, 'Fondamentaux du marketing digital', 'Comprendre les outils, les canaux et les premiers reflexes pour developper votre visibilite.', 1, 62, '2026-01-10 19:35:32', '2026-06-10 19:53:32'),
(20, 24, 'video', 'https://youtu.be/pajGFG_6eHg?si=Wabeca4a97gS6wyq', NULL, NULL, NULL, 'rgrgr', 'grgrgrgr', 1, 25, '2026-02-17 12:10:25', '2026-02-17 12:10:25'),
(21, 25, 'video', 'https://youtu.be/pajGFG_6eHg?si=Wabeca4a97gS6wyq', NULL, NULL, NULL, 'rgrgr', 'grgrgrgr', 1, 25, '2026-02-17 12:47:12', '2026-02-17 12:47:12'),
(22, 26, 'video', 'https://youtu.be/pajGFG_6eHg?si=Wabeca4a97gS6wyq', NULL, NULL, NULL, 'document', 'n,nnnnnnnnnnnn', 1, 20, '2026-03-26 18:53:23', '2026-03-26 18:53:23'),
(23, 27, 'video', 'https://youtu.be/pajGFG_6eHg?si=Wabeca4a97gS6wyq', NULL, NULL, NULL, 'initiation', 'fbfbfbf', 1, 25, '2026-04-13 15:12:01', '2026-04-17 16:06:06'),
(25, 30, 'video', 'https://youtu.be/ZAfAud_M_mg?si=Y8-0mrOzuCkzF6xd', NULL, NULL, NULL, 'initiation', NULL, 1, 25, '2026-05-20 20:18:49', '2026-05-20 20:18:49'),
(26, 32, 'video', 'https://www.youtube.com/watch?v=4lD0-1Uplms&list=RD4lD0-1Uplms&start_radio=1', NULL, NULL, NULL, 'rrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrr', 'rggghrhhhhhhhhhhhhhhh', 1, 15, '2026-06-04 18:36:43', '2026-06-04 18:36:43'),
(28, 34, 'video', 'https://www.youtube.com/watch?v=cleQjLtKNGM&list=RD85B_DpmMunk&index=5', NULL, NULL, NULL, 'Leçon', 'eeeeeeeeeeee', 1, 15, '2026-06-19 15:28:02', '2026-06-19 15:28:02'),
(29, 35, 'video', 'https://www.youtube.com/watch?v=cleQjLtKNGM&list=RD85B_DpmMunk&index=5', NULL, NULL, NULL, 'Leçon', 'eeeeeeeeeeee', 1, 15, '2026-06-19 15:37:16', '2026-06-19 15:37:16'),
(30, 36, 'video', 'https://youtu.be/4lD0-1Uplms?si=kvxfzD1mCC_zKIP8', NULL, NULL, NULL, 'hggg', NULL, 1, 15, '2026-07-02 16:27:20', '2026-07-02 16:27:20');

-- --------------------------------------------------------

--
-- Structure de la table `course_meetings`
--

CREATE TABLE `course_meetings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `chapter_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `mode` enum('online','onsite') NOT NULL DEFAULT 'online',
  `duration_min` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  `student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `confirmed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `meeting_link` varchar(255) DEFAULT NULL,
  `meeting_place` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `course_meetings`
--

INSERT INTO `course_meetings` (`id`, `course_id`, `chapter_id`, `title`, `mode`, `duration_min`, `status`, `student_id`, `confirmed_by`, `start_at`, `end_at`, `meeting_link`, `meeting_place`, `created_at`, `updated_at`) VALUES
(1, 4, 13, 'mercredi', 'online', 60, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-10 10:42:38', '2025-12-18 12:08:05'),
(2, 4, 13, 'mercredi', 'online', 60, 'cancelled', 5, NULL, '2025-12-18 20:53:00', '2025-12-18 21:53:00', NULL, NULL, '2025-12-18 13:47:36', '2025-12-18 14:38:46'),
(3, 4, 13, 'mercredi', 'online', 60, 'cancelled', 5, NULL, '2026-01-10 19:52:00', '2026-01-10 20:52:00', NULL, NULL, '2025-12-18 13:47:36', '2025-12-18 14:38:46'),
(4, 4, 13, 'mercredi', 'online', 60, 'cancelled', 5, 1, '2025-12-18 19:52:00', '2025-12-18 20:52:00', 'https://docs.google.com/presentation/d/1cR3ua8u8CAeLSUrUtyQXyQ9a2UYMyOT6SZdp57qQK9E/edit?usp=drive_link', NULL, '2025-12-18 13:47:36', '2025-12-18 14:55:56'),
(5, 4, 13, 'mercredi', 'online', 60, 'cancelled', 5, NULL, '2025-12-19 15:59:00', '2025-12-19 16:59:00', NULL, NULL, '2025-12-18 14:59:53', '2025-12-18 16:19:44'),
(6, 4, 13, 'mercredi', 'online', 60, 'cancelled', 5, NULL, '2026-01-02 15:59:00', '2026-01-02 16:59:00', NULL, NULL, '2025-12-18 14:59:53', '2025-12-18 16:19:44'),
(7, 4, 13, 'mercredi', 'online', 60, 'cancelled', 5, NULL, '2026-01-11 15:04:00', '2026-01-11 16:04:00', NULL, NULL, '2025-12-18 14:59:53', '2025-12-18 16:19:44'),
(8, 4, 13, 'mercredi', 'online', 60, 'cancelled', 5, NULL, '2026-01-01 21:27:00', '2026-01-01 22:27:00', NULL, NULL, '2025-12-18 17:24:56', '2025-12-18 17:25:39'),
(9, 4, 13, 'mercredi', 'online', 60, 'cancelled', 5, 1, '2025-12-27 22:28:00', '2025-12-27 23:28:00', 'https://docs.google.com/presentation/d/1cR3ua8u8CAeLSUrUtyQXyQ9a2UYMyOT6SZdp57qQK9E/edit?usp=drive_link', NULL, '2025-12-18 17:24:56', '2026-05-30 10:10:52'),
(10, 4, 13, 'mercredi', 'online', 60, 'cancelled', 5, NULL, '2026-01-09 00:30:00', '2026-01-09 01:30:00', NULL, NULL, '2025-12-18 17:24:56', '2025-12-18 17:25:39'),
(11, 9, 22, 'mercredi', 'onsite', 60, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-07 12:31:06', NULL),
(12, 10, 23, 'Session questions-reponses', 'online', 60, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-10 18:40:58', '2026-06-10 17:53:32'),
(13, 5, 24, 'mercredi', 'online', 60, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-17 11:11:18', NULL),
(14, 14, 26, 'mercredi', 'onsite', 60, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-26 18:29:30', NULL),
(15, 14, 26, 'mercredi', 'online', 60, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-26 18:33:37', NULL),
(17, 15, 27, 'mercredi', 'online', 60, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-17 14:10:38', NULL),
(18, 17, 30, 'mercredi', 'online', 60, 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-20 19:20:11', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `course_promotions`
--

CREATE TABLE `course_promotions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `promo_mode` enum('promotion','coupon') NOT NULL DEFAULT 'promotion',
  `promo_type` enum('amount','percent') NOT NULL,
  `promo_value` decimal(10,2) NOT NULL,
  `coupon_code` varchar(32) DEFAULT NULL,
  `promo_start_at` datetime NOT NULL,
  `promo_end_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `course_promotions`
--

INSERT INTO `course_promotions` (`id`, `course_id`, `teacher_id`, `promo_mode`, `promo_type`, `promo_value`, `coupon_code`, `promo_start_at`, `promo_end_at`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 9, 1, 'promotion', 'amount', 1000.00, NULL, '2026-01-08 17:05:57', '2026-01-15 17:05:57', '2026-01-08 16:21:07', '2026-01-08 16:05:57', '2026-01-08 16:21:07'),
(2, 9, 1, 'promotion', 'amount', 1000.00, NULL, '2026-01-08 17:21:27', '2026-01-13 17:21:27', '2026-01-08 18:17:45', '2026-01-08 16:21:27', '2026-01-08 18:17:45'),
(3, 9, 1, 'promotion', 'percent', 40.00, NULL, '2026-01-08 18:24:56', '2026-01-09 18:24:56', '2026-01-08 18:26:41', '2026-01-08 18:24:56', '2026-01-08 18:26:41'),
(4, 9, 1, 'promotion', 'percent', 30.00, NULL, '2026-01-08 18:30:29', '2026-01-09 18:30:29', '2026-03-20 17:05:08', '2026-01-08 18:30:29', '2026-03-20 17:05:08'),
(5, 4, 1, 'promotion', 'amount', 1000.00, NULL, '2026-01-08 19:04:00', '2026-01-10 19:04:00', '2026-01-08 19:04:16', '2026-01-08 19:04:00', '2026-01-08 19:04:16'),
(6, 4, 1, 'coupon', 'percent', 100.00, 'CPN-4-FA4146', '2026-02-20 18:01:29', '2026-02-22 18:01:29', '2026-02-20 18:30:06', '2026-02-20 18:01:29', '2026-02-20 18:30:06'),
(7, 4, 1, 'promotion', 'percent', 100.00, NULL, '2026-02-20 18:34:47', '2026-02-22 18:34:47', '2026-02-20 18:35:43', '2026-02-20 18:34:47', '2026-02-20 18:35:43'),
(8, 9, 1, 'promotion', 'amount', 10.00, NULL, '2026-03-20 19:09:30', '2026-03-25 19:09:30', NULL, '2026-03-20 19:09:30', '2026-03-20 19:09:30'),
(9, 4, 1, 'promotion', 'amount', 1000.00, NULL, '2026-04-15 15:57:25', '2026-04-17 15:57:25', '2026-06-10 18:46:11', '2026-04-15 15:57:25', '2026-06-10 18:46:11'),
(10, 4, 1, 'coupon', 'percent', 10.00, 'CPN-4-B8A83D', '2026-04-17 21:03:24', '2026-04-23 21:03:24', NULL, '2026-04-17 21:03:24', '2026-04-17 21:03:24'),
(11, 5, 1, 'promotion', 'percent', 50.00, NULL, '2026-05-16 18:01:29', '2026-05-21 18:01:29', '2026-06-10 18:46:05', '2026-05-16 18:01:29', '2026-06-10 18:46:05'),
(12, 9, 1, 'promotion', 'percent', 25.00, NULL, '2026-05-16 18:33:47', '2026-05-18 18:33:47', '2026-06-10 18:45:58', '2026-05-16 18:33:47', '2026-06-10 18:45:58'),
(13, 5, 1, 'promotion', 'percent', 50.00, NULL, '2026-05-30 16:35:28', '2026-06-04 16:35:28', '2026-06-10 18:45:50', '2026-05-30 16:35:28', '2026-06-10 18:45:50'),
(14, 5, 1, 'coupon', 'percent', 50.00, 'MIKE-6326', '2026-06-03 00:00:00', '2026-06-04 23:59:59', NULL, '2026-06-03 11:02:05', '2026-06-03 11:02:05'),
(15, 15, 1, 'promotion', 'percent', 50.00, NULL, '2026-06-10 18:47:00', '2026-06-12 18:47:00', NULL, '2026-06-10 18:47:15', '2026-06-10 18:47:15'),
(16, 5, 1, 'promotion', 'percent', 30.00, NULL, '2026-06-11 23:23:48', '2026-06-15 23:23:48', '2026-07-01 13:20:24', '2026-06-11 23:23:48', '2026-07-01 13:20:24'),
(17, 4, 1, 'coupon', 'percent', 50.00, 'CPN-4-100190', '2026-06-12 00:46:31', '2026-06-17 00:46:31', NULL, '2026-06-12 00:46:31', '2026-06-12 00:46:31'),
(18, 15, 1, 'coupon', 'percent', 50.00, 'CPN-F-21FFAF', '2026-07-01 13:21:48', '2026-07-06 13:21:48', NULL, '2026-07-01 13:21:48', '2026-07-01 13:21:48'),
(19, 5, 1, 'promotion', 'percent', 50.00, NULL, '2026-07-01 14:14:41', '2026-07-31 14:14:41', NULL, '2026-07-01 14:14:41', '2026-07-01 14:14:41');

-- --------------------------------------------------------

--
-- Structure de la table `course_promotion_company_targets`
--

CREATE TABLE `course_promotion_company_targets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `promotion_id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `seat_limit` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `course_promotion_company_uses`
--

CREATE TABLE `course_promotion_company_uses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `promotion_id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `used_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `course_promotion_targets`
--

CREATE TABLE `course_promotion_targets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `promotion_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `course_ratings`
--

CREATE TABLE `course_ratings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `is_hidden_by_teacher` tinyint(1) NOT NULL DEFAULT 0,
  `hidden_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hidden_at` datetime DEFAULT NULL,
  `teacher_reply_text` text DEFAULT NULL,
  `teacher_reply_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `teacher_reply_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `course_ratings`
--

INSERT INTO `course_ratings` (`id`, `course_id`, `user_id`, `rating`, `comment`, `is_hidden_by_teacher`, `hidden_by_user_id`, `hidden_at`, `teacher_reply_text`, `teacher_reply_user_id`, `teacher_reply_at`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 5, 'efefefe', 0, NULL, NULL, NULL, NULL, NULL, '2025-12-04 16:15:52', '2025-12-04 16:36:07'),
(4, 2, 5, 4, 'merci', 0, NULL, NULL, NULL, NULL, NULL, '2025-12-05 12:25:00', '2025-12-05 12:25:00'),
(5, 4, 5, 3, 'bonjour', 0, NULL, NULL, NULL, NULL, NULL, '2026-02-17 14:40:18', '2026-04-05 20:22:20');

-- --------------------------------------------------------

--
-- Structure de la table `course_reports`
--

CREATE TABLE `course_reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `reason` text NOT NULL,
  `status` enum('open','in_progress','resolved','rejected') NOT NULL DEFAULT 'open',
  `admin_note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `course_reports`
--

INSERT INTO `course_reports` (`id`, `course_id`, `user_id`, `reason`, `status`, `admin_note`, `created_at`, `updated_at`) VALUES
(1, 4, 5, 'azertyuiopqsdfghjklm', 'open', NULL, '2025-12-10 13:12:58', '2025-12-10 13:12:58'),
(2, 4, 5, 'azertyuimlkjhgfdsqwxcvbn', 'resolved', 'c\'est bien', '2025-12-10 14:21:31', '2025-12-10 14:22:30');

-- --------------------------------------------------------

--
-- Structure de la table `course_views`
--

CREATE TABLE `course_views` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `viewer_key` varchar(96) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_viewed_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `course_views`
--

INSERT INTO `course_views` (`id`, `course_id`, `user_id`, `viewer_key`, `created_at`, `last_viewed_at`) VALUES
(1, 4, 5, 'u:5', '2026-02-17 11:24:07', '2026-07-04 14:30:33'),
(5, 10, 6, 'u:6', '2026-02-17 14:09:33', '2026-02-17 14:09:33'),
(11, 4, 1, 'u:1', '2026-02-17 17:58:05', '2026-03-12 18:11:09'),
(13, 10, 12, 'u:12', '2026-02-18 18:38:01', '2026-02-26 14:17:14'),
(14, 10, 9, 'u:9', '2026-02-19 16:41:21', '2026-03-02 13:18:53'),
(15, 4, 12, 'u:12', '2026-02-20 18:28:32', '2026-03-07 12:39:59'),
(23, 9, 12, 'u:12', '2026-02-26 14:17:43', '2026-03-28 19:35:19'),
(37, 5, NULL, 'g:901c62fffb1e4608eb3e446e5d08242ee8172a7e9e46bf812835ee6dc5b4b0f5', '2026-03-02 17:29:03', '2026-03-02 17:29:03'),
(38, 10, 10, 'u:10', '2026-03-03 17:09:18', '2026-03-03 17:09:18'),
(55, 4, 8, 'u:8', '2026-03-09 18:52:47', '2026-03-10 19:18:17'),
(56, 4, NULL, 'g:6f2315a1b9abe2bc2a838bec02662f849f8d9bfb265e25c672f56ebe84cde125', '2026-03-18 13:22:57', '2026-03-18 13:27:53'),
(61, 10, 5, 'u:5', '2026-03-18 13:33:32', '2026-06-25 11:13:29'),
(66, 5, 10, 'u:10', '2026-03-20 17:24:55', '2026-03-20 17:24:55'),
(67, 9, 5, 'u:5', '2026-03-26 17:02:12', '2026-06-26 01:08:18'),
(68, 4, 5, 'g:d287c78f7edf6d0a83c3b0d08debb8e48cd10816566290ca8f8b20189fb7bb0f', '2026-04-10 10:58:12', '2026-04-17 21:37:39'),
(69, 9, 9, 'u:9', '2026-04-17 20:43:39', '2026-04-17 20:43:39'),
(73, 15, 5, 'u:5', '2026-05-06 11:41:29', '2026-06-25 20:07:12'),
(85, 9, NULL, 'g:a6cb9c186dd7936356ae68f44e5c5f507d1ef70c7fead57aeb80da26e47827f3', '2026-05-06 14:41:09', '2026-05-06 14:41:09'),
(86, 9, NULL, 'g:ad0251d2823c8f8cabdad84ff1afa9a943e6b7924abccc0127250075e3be7bb0', '2026-05-06 14:41:18', '2026-05-06 14:41:18'),
(87, 9, NULL, 'g:19b89c9ed03d39dd33f48bc0a715963d52c0c6692e9482ad088595388ee84d80', '2026-05-06 14:42:30', '2026-05-06 14:42:30'),
(88, 9, NULL, 'g:d28972ef33153af705846fcc99e5939c63441da35cf7beb9d34b0d7b6e39c6c2', '2026-05-06 14:42:50', '2026-05-06 14:42:50'),
(89, 9, NULL, 'g:357e60e3712a04fcf767bb8f83f9c5ad39142878146a6fa6e67f6a85087d4482', '2026-05-06 14:42:50', '2026-05-06 14:42:50'),
(90, 9, NULL, 'g:96a0b815d2ee9b3fed5137a213ff3db0f43d7f2e8428e180d53fde148e30f819', '2026-05-06 14:43:49', '2026-05-06 14:43:49'),
(91, 9, NULL, 'g:60933168ae44ab793bc3dfe1b7b6bc10f0d4ca87150c6c4bdf45030f63b01fd6', '2026-05-06 14:43:58', '2026-05-06 14:43:58'),
(92, 9, NULL, 'g:c2a35d943d854862a1a98b9aff7987e9f1715c4da10edd3d7e26cf196b99e9da', '2026-05-06 14:44:32', '2026-05-06 14:44:32'),
(93, 9, NULL, 'g:aeb596657885c673902d4a63e0489eed424e4c3c26ba5ab933979cbd9a76dbaf', '2026-05-06 14:44:32', '2026-05-06 14:44:32'),
(94, 9, NULL, 'g:cc597e0b034b4e2092c94f8645f28ff2a7f3c4cc0459f7ad48eaae3e0c031425', '2026-05-06 14:44:43', '2026-05-06 14:44:43'),
(95, 9, NULL, 'g:5358b71c902a5a4d6be00023b5525c253a21db8f451881be35dede8ad78185c4', '2026-05-06 14:45:38', '2026-05-06 14:45:38'),
(98, 9, NULL, 'g:4c2fe711c8289797476a20fba832241dcaaf73509f2ecac5e07240a03f961336', '2026-05-06 14:47:13', '2026-05-06 14:47:13'),
(100, 9, NULL, 'g:d0cd9fc5dc00bf994775b8247bb8732a114444ab5286ee076671b5d04f6fc75b', '2026-05-06 14:48:37', '2026-05-06 14:48:37'),
(101, 9, NULL, 'g:2ed7e9b4a19e29d7b30f0d4c71d979dae0922f91ff0911a23bf41ece8e465c00', '2026-05-06 14:48:46', '2026-05-06 14:48:46'),
(102, 9, NULL, 'g:fb55aa839441b9470a6b79f687ca89bf57bb34207bfbd77dea0363aa542e60f5', '2026-05-06 14:50:09', '2026-05-06 14:50:09'),
(103, 9, NULL, 'g:f81b88202efc8e077a62f8838b9be062fa67a615e5be39bffaa83a0eb5314a4c', '2026-05-06 14:50:16', '2026-05-06 14:50:16'),
(127, 4, NULL, 'g:045dafbdcd72734e25a1ba814e27827ef86e6b03c222a0f653edde99ef074e78', '2026-05-21 11:11:43', '2026-05-21 11:11:58'),
(141, 5, 5, 'u:5', '2026-05-22 20:10:03', '2026-05-22 20:10:03'),
(151, 15, NULL, 'g:cfbd76c6f5c526dab618df10465c1dde061d8701a4c71684f8de3737468813ee', '2026-06-04 18:28:44', '2026-06-04 18:28:44'),
(152, 4, NULL, 'g:ab8c21fff30e8333fde6b67ad535b58b8a9a025b2d19676dade72f36782a3f5a', '2026-06-04 18:30:04', '2026-06-04 18:30:04'),
(161, 15, 1, 'u:1', '2026-06-10 18:23:40', '2026-06-11 00:19:35'),
(165, 10, NULL, 'g:560db5ca9cfb3876d4f46c745cbc99ed01c37dc23b5de87d3f28b6ba793b31e7', '2026-06-10 18:55:18', '2026-06-10 18:55:18'),
(166, 10, NULL, 'g:06a5ded13d4001025787f4273c8b6938edb9d9f7302027042097cf47d6fc90b4', '2026-06-10 18:55:53', '2026-06-10 18:55:53'),
(167, 10, NULL, 'g:8b9d945f319c4a0241d382ca30f1bb5e7c1645f2c67a65274cd339e7941ee334', '2026-06-10 18:55:53', '2026-06-10 18:55:53'),
(168, 10, NULL, 'g:461f2958fa92e95c561f905691897f6327b7d7b96188a910fc7eb39521459855', '2026-06-10 18:57:32', '2026-06-10 18:57:32'),
(172, 10, NULL, 'g:43f4956b14953193f7d43a2bc628a74626ab1727fbaf2a2d8ab4fb7350b9aa91', '2026-06-10 19:27:53', '2026-06-10 19:27:53'),
(173, 10, NULL, 'g:b2e339d0c69fe629c35cd035da84e7d2e3c169c378ef51315b26fb7fcc703728', '2026-06-10 19:28:20', '2026-06-10 19:28:20'),
(174, 10, NULL, 'g:e518160eeaf23a5ec6db8dc482af117ce235e017e8a6723f0725681fec832f5a', '2026-06-10 19:28:48', '2026-06-10 19:28:48'),
(175, 10, NULL, 'g:68b88ab4ab213af067eb8a4899b8cda79b9c094cb41eafebf28e9b8547e28e61', '2026-06-10 19:29:30', '2026-06-10 19:29:30'),
(178, 10, NULL, 'g:3c58647f605dc08e266212b4c25044e83fd2349f245ac870c74befaa9dfedf57', '2026-06-10 23:33:23', '2026-06-10 23:33:23'),
(179, 10, NULL, 'g:90a75bed62c8c9ad2b1cd18c93822db88e428fb21e757a77792c0a1bd3ace933', '2026-06-10 23:33:25', '2026-06-10 23:33:25'),
(182, 9, NULL, 'g:d52b7b9ec08b8707ae44c5830d8666023f376f46c846394896043011e5e16eab', '2026-06-11 00:36:56', '2026-06-11 00:36:56'),
(183, 15, NULL, 'g:d52b7b9ec08b8707ae44c5830d8666023f376f46c846394896043011e5e16eab', '2026-06-11 00:56:32', '2026-06-11 01:03:07'),
(185, 15, NULL, 'g:80ad43d8b677b7b204379e596220473adc0415c6bdac677f4c58a4e862821ae8', '2026-06-11 02:03:46', '2026-06-11 02:03:46'),
(186, 15, NULL, 'g:735a6792472e917a4b58b58770c3a7753e27b50b7f8fa132a97f8e8d1d4f3eb6', '2026-06-11 02:04:19', '2026-06-11 02:04:19'),
(187, 15, NULL, 'g:9fad25cc0c60b131022d982379dcf6603010e90e0942412846135b2d9e656c67', '2026-06-11 02:05:09', '2026-06-11 02:05:09'),
(189, 15, NULL, 'g:511cc2331868d6a0446a41916d7e8d0687fdea49e6888ff3e1d1550bc67c46d0', '2026-06-11 02:06:11', '2026-06-11 02:06:11'),
(190, 15, NULL, 'g:2e9666d05621ed5f19c0b8f32b776bcbeec565c77638623128c4c6fd4ef6e57c', '2026-06-11 02:06:38', '2026-06-11 02:06:38'),
(191, 15, NULL, 'g:22c526725ec85bb287906af5ad57c691bb5b082c4b935f688a946e2188f9b51c', '2026-06-11 02:10:57', '2026-06-11 02:10:57'),
(192, 15, NULL, 'g:faa088097d6ec5857d104a1b13007c6b10708cf62a046308a4efac9baba78d15', '2026-06-13 01:46:37', '2026-06-13 01:46:37'),
(213, 15, 14, 'u:14', '2026-06-23 12:25:34', '2026-06-23 12:25:34'),
(217, 9, 1, 'u:1', '2026-06-24 11:39:18', '2026-06-24 11:39:18'),
(218, 9, NULL, 'g:d664762b08adde843dbac5c46cb34fcd6c19b0b5356afa0aeeb7d4e87ebb2898', '2026-06-24 12:33:47', '2026-06-24 12:33:47'),
(219, 9, NULL, 'g:71b41d74d5559dac021916a35bedf24c371c2e924e13b833cedc5f9b3b5e26a1', '2026-06-24 14:52:30', '2026-06-24 14:52:30'),
(220, 9, NULL, 'g:5aebdce43186cc24bb6924124f8953f8fd953716306509fc4c11d74448e4d72d', '2026-06-24 14:56:53', '2026-06-24 14:56:53'),
(223, 15, NULL, 'g:a1819bf0793bd60440cfe87dbae00469b1e6d8791ed0cd00b5a8c09810af2307', '2026-06-24 19:26:38', '2026-06-24 19:26:38'),
(224, 15, NULL, 'g:5d415a7e2946c6dea1d53fd6e94cd3eae20d8e51c12fc5db251f88feff16c62e', '2026-06-24 19:28:49', '2026-06-24 19:28:49'),
(225, 15, NULL, 'g:1d34fe72d0af46e2aac381821e52c64fff6567bf52635da1064c0c3e30c5ca36', '2026-06-24 19:28:51', '2026-06-24 19:28:51'),
(226, 15, NULL, 'g:f1b4d3b91263abed918a6489830c2a67560195ff397c4dcaf87b23668374a509', '2026-06-24 19:28:51', '2026-06-24 19:28:51'),
(227, 15, NULL, 'g:b647eab297e10fdbb372a8c40578df81d7860a06efc32917a567863c86d6615b', '2026-06-24 19:29:04', '2026-06-24 19:29:04'),
(228, 15, NULL, 'g:1aacf1f815cf06984086e7513140b17fdd533bc26cc12187981a1f910c7893b4', '2026-06-24 19:29:37', '2026-06-24 19:29:37'),
(229, 15, NULL, 'g:78122b8c1ad164284743ad9036e5e526ac98d4f2647d3f1d4d43c0851ecfe15d', '2026-06-24 19:30:06', '2026-06-24 19:30:06'),
(231, 15, NULL, 'g:ab2f4fb160c88725d70d43b03d6d924e0bc83029ca65fcb0c4b94ebae33b6c5c', '2026-06-24 19:31:15', '2026-06-24 19:31:15'),
(232, 15, NULL, 'g:de5ed774fffaf05147e8e6b389827e251354b4e4c19eaefb5a0f05f5ff395737', '2026-06-24 19:31:16', '2026-06-24 19:31:16'),
(233, 15, NULL, 'g:24ecc78d3b50a3cb6d3d75a3872b315a8de631ec4d33af45c8eb72970644f754', '2026-06-24 19:31:17', '2026-06-24 19:31:17'),
(234, 15, NULL, 'g:bcba1baf350b18cb736bb2bc11507c3a6254017b468ca97b5b05f298e5f25722', '2026-06-24 19:32:04', '2026-06-24 19:32:04'),
(236, 15, NULL, 'g:c2974cbf893b0bc0d24bec91a6aea5fb2b858f8172868ea21c74e583039868b2', '2026-06-24 19:32:30', '2026-06-24 19:32:30'),
(237, 15, NULL, 'g:57d44f066585b9c3ad39ce4910318170c852f681306d0b8c86c937b6638fb123', '2026-06-24 19:32:46', '2026-06-24 19:32:46'),
(238, 15, NULL, 'g:7d3739a05cb4f39dc4beebfae8e79c9f74090695920098df57546721dfeca567', '2026-06-24 19:33:31', '2026-06-24 19:33:31'),
(239, 15, NULL, 'g:ba434f572040065d3d6dadb23dbfc13365ee98138831650a5a16609202749903', '2026-06-24 19:33:57', '2026-06-24 19:33:57'),
(242, 15, NULL, 'g:bef85cd197e1beeaed41a2a0ff45021c74f16d0558b6896ad3d1fe5d82bb19fd', '2026-06-25 09:23:03', '2026-06-25 09:23:03'),
(243, 15, NULL, 'g:2b97b7d81c3a1fe927ca6af5b35e6c1d12a117dc60f65b2ea3df8988e5ae0628', '2026-06-25 09:24:12', '2026-06-25 09:24:12'),
(244, 15, NULL, 'g:6a93f9cade261c36edb64d7961c75c49be5273ef498b30fa2299aa2b0b2f2d60', '2026-06-25 09:28:05', '2026-06-25 09:28:05'),
(245, 15, NULL, 'g:51b62066ae3104a8d98de3215915c046dbda173bc155145b8d7e2bf3827fc3ec', '2026-06-25 09:28:47', '2026-06-25 09:28:47'),
(246, 15, NULL, 'g:d08b1c5f8be309ab24596333bd8f50356e90e8f9f8a69f46a0d8f6cca1a6633b', '2026-06-25 09:31:05', '2026-06-25 09:31:05'),
(247, 15, NULL, 'g:f0f934b92c3c06c10bd9275a3ef06b89c86065dff5cb29e352c1fd8dffad5b0d', '2026-06-25 09:32:05', '2026-06-25 09:32:05'),
(248, 15, NULL, 'g:d5b1e2aaa8004cd9f21fbe2bd6578fef607880cca9a8fba55a9d99ea76ac1f1b', '2026-06-25 09:34:00', '2026-06-25 09:34:00'),
(249, 15, NULL, 'g:612f9164ed36844c029f7e80b6b6921c40641e6ad216e142b74c71cde6f00664', '2026-06-25 09:34:34', '2026-06-25 09:34:34'),
(252, 15, NULL, 'g:88be66a7116c27924756bc4337b17015457e108eeb8e535f807a3339ce40a083', '2026-06-25 10:16:26', '2026-06-25 10:16:26'),
(253, 15, NULL, 'g:7f83bde3344890288ed20059341bd94635224182a2d5fb5b34640b5bf6cca905', '2026-06-25 10:18:23', '2026-06-25 10:18:23'),
(254, 15, NULL, 'g:eb05b60da7cc1c5aa8f14cb51aef5f1d2fca6d7337615e9e0512603ca65284bd', '2026-06-25 10:34:41', '2026-06-25 10:34:41'),
(255, 15, NULL, 'g:0c5aa203b35e892d2e68dba5bf6f05c0bbcb45c2129c035d19398d114a8d0130', '2026-06-25 10:34:43', '2026-06-25 10:34:43'),
(256, 15, NULL, 'g:9f26c6c0dfce1b44bc122c05558a353fd90b019325c1bdc07e6587be9bc45a9e', '2026-06-25 10:36:29', '2026-06-25 10:36:29'),
(257, 15, NULL, 'g:c60eab1d3d04ac5f863fc5a16dba8006fb3ceb93e24ce4725a0fcba737b62dcb', '2026-06-25 10:36:30', '2026-06-25 10:36:30'),
(259, 15, NULL, 'g:392ac1689c86d668600eace4ec6da18e263fecaa3c351a8eb71395b0f3f1b2f5', '2026-06-25 10:41:06', '2026-06-25 10:41:06'),
(260, 15, NULL, 'g:07dc8eba52a0c60751910610d3f971998196b04e32065efafdbfe88d8d8f682a', '2026-06-25 10:45:00', '2026-06-25 10:45:00'),
(262, 15, NULL, 'g:f626c4b57d77969ec5c16b03b4bb6c59dbf1bfa83e842a82d4483600e6724314', '2026-06-25 10:59:40', '2026-06-25 10:59:40'),
(263, 15, NULL, 'g:57d55008fdbe46f4fa6796e990fbe645ca2e16259bda49dbf866757eb5c6ee3b', '2026-06-25 11:00:32', '2026-06-25 11:00:32'),
(266, 15, NULL, 'g:47fcbce3b01612a19bf20b79d209c07d664ade0337aaee00761ea4dba24e2bef', '2026-06-25 15:22:50', '2026-06-25 15:22:50'),
(267, 15, NULL, 'g:2087186004078df1e8247b1ed1699ebc0350d7003e3528068b9d9a9b54a922fa', '2026-06-25 15:56:24', '2026-06-25 15:56:24'),
(268, 15, NULL, 'g:ebdcc7fd29f9322b5775d6b19698ca62f3f95ffd3a1a9ba31685b35316ec88b7', '2026-06-25 16:10:44', '2026-06-25 16:10:44'),
(269, 15, NULL, 'g:f4eb65fbf1bdc1dddd00b8f051d4c80537d19a87261a39dfb00c9e05ae30c27c', '2026-06-25 16:11:26', '2026-06-25 16:11:26'),
(270, 15, NULL, 'g:184b08ba03f96c0566ddf74eaa14e6bd306402644202c4523954cfc7f9b99a38', '2026-06-25 16:14:44', '2026-06-25 16:14:44'),
(271, 15, NULL, 'g:f8e7eb883be326afbb41b42843ae1d2d4183598b49fa8d238fc903196e9ecc6e', '2026-06-25 16:15:41', '2026-06-25 16:15:41'),
(272, 15, NULL, 'g:fb54565895b745e801249118ff4f04f0667d999ab6e86f7d9ff6582248dd6188', '2026-06-25 16:24:42', '2026-06-25 16:24:42'),
(273, 15, NULL, 'g:92085c878271c2c9a91a42ae8977ee555028a25fddb835e4aae1530d41c1af33', '2026-06-25 16:32:02', '2026-06-25 16:32:02'),
(274, 15, NULL, 'g:71e55c83dcec9528409230b5fd334c34d5bfc392632059ff212e5bf723566494', '2026-06-25 16:45:58', '2026-06-25 16:45:58'),
(275, 15, NULL, 'g:09488e1eb230399f3cd60f212678a9de13af850282fff8a1ec1e4fd63a520f8e', '2026-06-25 16:47:30', '2026-06-25 16:47:30'),
(276, 15, NULL, 'g:f3b62d1cff93b9c345743433ea3fbf9e16b5e0dda591ed143f2a247abf909cc7', '2026-06-25 16:48:58', '2026-06-25 16:48:58'),
(277, 15, NULL, 'g:99ab87c2c306bce380d7c6bfb41ba476f1e61bc3221231052dc8df856aab2a4c', '2026-06-25 16:50:50', '2026-06-25 16:50:50'),
(278, 15, NULL, 'g:7f68020e6d9365103d5cd1cf0735051beb54b0ad5c0fc5a198aa1a94f434e16e', '2026-06-25 16:51:56', '2026-06-25 16:51:56'),
(279, 15, NULL, 'g:ae752a05050642064d2704fe7f850c2378c95bd3c9bc008719499ec284e899ee', '2026-06-25 16:55:59', '2026-06-25 16:55:59'),
(280, 15, NULL, 'g:66f82bf0f75f040fd56efed96f29fa6571413859e1b5f8cc4e58646d39d09ec0', '2026-06-25 16:57:49', '2026-06-25 16:57:49'),
(281, 15, NULL, 'g:936da1d35c1acee7ee4cf7807405ec011fb0ec045767fe95fb7cd31604be5afe', '2026-06-25 17:13:52', '2026-06-25 17:13:52'),
(282, 15, NULL, 'g:a13d82cbcf5a6917e62dc7774227429b8e697828046e850f136804cf538edd5c', '2026-06-25 17:14:46', '2026-06-25 17:14:46'),
(283, 15, NULL, 'g:786c62a10c638f2a60c5a580a553b862e40ea2be877bfea1d6a76f6fda018660', '2026-06-25 17:15:19', '2026-06-25 17:15:19'),
(287, 15, NULL, 'g:034c979612d2fa1074350d87c749b67ef3ad465878e9c91614728c10a99321f4', '2026-06-25 20:11:34', '2026-06-25 20:11:34'),
(288, 15, NULL, 'g:c523d046d4120edb7a0f36a41ffd39b2b3a42d0cc7c81885e02c8890eab79126', '2026-06-25 20:11:47', '2026-06-25 20:11:47'),
(289, 15, NULL, 'g:9c13491e02465a0bd8fc0b60857df75731b774ae7708646f0dfe41118044c093', '2026-06-25 20:11:57', '2026-06-25 20:11:57'),
(291, 9, NULL, 'g:14f2a5159a4d980d2156ac0ee20f366631bda69c92d2d716ded28e63ded55030', '2026-06-25 20:21:56', '2026-06-25 20:21:56'),
(293, 9, NULL, 'g:f3d0bc341b1503d5cc505caf4795c62158e2474ca2d8af78e64b2daf89d4a86a', '2026-06-25 20:43:06', '2026-06-25 20:43:06'),
(294, 9, NULL, 'g:6e310deee7bdb0ee7bd13b804be8d352278c61c96dfd53221ae3bbb0510c825a', '2026-06-25 20:50:55', '2026-06-25 20:50:55'),
(298, 9, NULL, 'g:9502251cf86452f0c7ca2e46f9c5078be46f2b2a2cb3f40bb3d1733f08dd5195', '2026-06-25 21:48:27', '2026-06-25 21:48:27'),
(304, 9, NULL, 'g:17923cce369288917f0e91fbfe14e574a83237cdd0d9b3995825645f88025fea', '2026-06-25 23:03:04', '2026-06-25 23:03:04'),
(309, 9, NULL, 'g:450fd2e634a8865f070e91389fae5a2d2e0493983a7ceecf07586dadee207876', '2026-06-25 23:46:02', '2026-06-25 23:46:02'),
(310, 9, NULL, 'g:cf452a2b38c19080061c6d130c65f29040486d7ac87c86dd6948903dff54e5de', '2026-06-25 23:46:02', '2026-06-25 23:46:02'),
(311, 9, NULL, 'g:0c5e327f24d6b7cf10657b2c4efef8088ec88a99fe24bb0076d0a9d1a30942a9', '2026-06-25 23:46:35', '2026-06-25 23:46:35'),
(313, 9, NULL, 'g:58b1b8ca50697e95d1b52eaa2d3600e59dcc9a9a46869d7495e4db75526fc727', '2026-06-26 00:14:56', '2026-06-26 00:14:56'),
(316, 9, NULL, 'g:0d222bf68fdc73ec938d6df5b3d03596a7569fe6eb1fb2c7debcede9a9cee4f6', '2026-06-26 00:40:18', '2026-06-26 00:40:18'),
(318, 9, NULL, 'g:59ecd0f29e14d932a533c86ae00db9bfa9b4a8632c54bebf89ea872362ef12ec', '2026-06-26 01:04:52', '2026-06-26 01:04:52'),
(321, 5, 9, 'u:9', '2026-06-30 17:49:22', '2026-06-30 17:49:22'),
(322, 4, 9, 'u:9', '2026-07-02 14:06:35', '2026-07-02 14:06:35'),
(323, 9, NULL, 'g:3a3dbaa75c4a94039a9ba7c418edb762bdf2d12dbfddaceef5095f9cbdd5b29e', '2026-07-02 17:12:12', '2026-07-02 17:12:24'),
(325, 9, NULL, 'g:dad67962b79eaf4c853aea8333d7cc032928db91d9dff2469e7e108ba0de6b03', '2026-07-02 18:01:04', '2026-07-02 18:03:35'),
(327, 9, NULL, 'g:cf88fe510098fa09b34f85674dc3e0ceb49a0f570c5c5ff06ff0f88e70565d43', '2026-07-03 13:13:48', '2026-07-03 13:13:48'),
(328, 9, NULL, 'g:422cfdfaf8a583b8328dceb7cb270901847b5dc26b90a551409ee988a81e6159', '2026-07-03 13:44:15', '2026-07-03 13:44:15'),
(329, 9, NULL, 'g:4b7a3dddaf7b05dc9985eca23bdd1a03462f2a1eb1126b434a1ef639153a7381', '2026-07-03 17:45:58', '2026-07-03 17:45:58'),
(330, 10, 1, 'u:1', '2026-07-04 11:05:10', '2026-07-04 11:05:10');

-- --------------------------------------------------------

--
-- Structure de la table `download_tokens`
--

CREATE TABLE `download_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `max_uses` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `used_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `email_verifications`
--

INSERT INTO `email_verifications` (`id`, `user_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 1, 'd4ffc68786fcf3f386b6a80b88fde121aa2e0a8d595c1a7e9793c197e62ed9e6', '2025-11-27 14:57:29', '2025-11-26 13:57:40', '2025-11-26 13:57:29'),
(4, 5, 'ddad4f3f1cb4e6e2fbebe3cf0cddad398b15cf192974e200cbd83623afd62a28', '2025-12-06 13:22:47', '2025-12-05 12:22:50', '2025-12-05 12:22:47'),
(5, 6, '1597f1cb19eaad5bd5976d60d6732fd7bb8003bccce8885720b2a36b21628fe9', '2026-01-07 18:11:46', '2026-01-06 17:11:52', '2026-01-06 17:11:46'),
(7, 8, '16dd95ea940846358d4fe37743ed59fddac4fa6f2460a85834b09b107b75bf54', '2026-01-08 11:17:00', '2026-01-07 10:17:02', '2026-01-07 10:17:00'),
(8, 9, '3287aaeed64dbef9cd3e1312bcc97cc6dedf0860454fda3f4c21b9574ce6a33b', '2026-01-08 11:18:30', '2026-01-07 10:18:32', '2026-01-07 10:18:30'),
(9, 10, '514feb8c7e9aaa16da8ef1200bded991c05034ae9a91a8e39c903c406aaa6979', '2026-01-08 11:25:22', '2026-01-07 10:25:27', '2026-01-07 10:25:22'),
(10, 11, '1557f8e23da85803b6131c918d96eb040694746b602ccb1d0d1421d01bb855f3', '2026-01-11 17:53:32', NULL, '2026-01-10 17:53:32'),
(11, 12, 'c7311ab7f1ce9724984275374f0b672f5253ed15b149a6165fd93aaba5eb9a52', '2026-01-16 14:04:08', '2026-01-15 14:04:11', '2026-01-15 14:04:08'),
(12, 14, '448d79bf60b0c21f2ef9b5952e59cdde864c88007ac7d4100c1822adb0559201', '2026-05-26 19:46:18', '2026-05-25 19:46:22', '2026-05-25 19:46:18');

-- --------------------------------------------------------

--
-- Structure de la table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('active','revoked') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `enrollments`
--

INSERT INTO `enrollments` (`id`, `user_id`, `course_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'active', '2025-12-03 13:09:33', '2025-12-03 13:09:33'),
(2, 5, 2, 'active', '2025-12-05 12:24:33', '2025-12-05 12:24:33'),
(3, 5, 4, 'active', '2025-12-10 17:07:30', '2025-12-10 17:07:30'),
(4, 1, 4, 'active', '2025-12-17 14:12:33', '2025-12-17 14:12:33'),
(6, 10, 4, 'active', '2026-01-08 15:09:21', '2026-01-08 15:09:21'),
(7, 10, 9, 'active', '2026-01-10 18:16:29', '2026-01-10 18:16:29'),
(8, 8, 4, 'active', '2026-01-19 12:30:15', '2026-01-19 12:30:15'),
(9, 8, 9, 'active', '2026-01-19 12:30:15', '2026-01-19 12:30:15'),
(12, 8, 10, 'active', '2026-01-19 12:30:26', '2026-01-19 12:30:26'),
(14, 10, 10, 'active', '2026-01-19 12:30:28', '2026-01-19 12:30:28'),
(24, 12, 4, 'active', '2026-02-20 18:28:26', '2026-02-20 18:28:26'),
(25, 14, 15, 'active', '2026-06-23 10:54:29', '2026-06-23 10:54:29');

-- --------------------------------------------------------

--
-- Structure de la table `exam_answers`
--

CREATE TABLE `exam_answers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `attempt_id` bigint(20) UNSIGNED NOT NULL,
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `option_id` bigint(20) UNSIGNED DEFAULT NULL,
  `answer_text` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `exam_answers`
--

INSERT INTO `exam_answers` (`id`, `attempt_id`, `question_id`, `option_id`, `answer_text`) VALUES
(1, 1, 4, 9, NULL),
(2, 2, 4, 9, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `exam_attempts`
--

CREATE TABLE `exam_attempts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `variant_index` tinyint(1) NOT NULL DEFAULT 1,
  `meeting_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('in_progress','submitted','graded') NOT NULL DEFAULT 'in_progress',
  `started_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `auto_score` int(11) DEFAULT NULL,
  `manual_score` int(11) DEFAULT NULL,
  `grader_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `exam_attempts`
--

INSERT INTO `exam_attempts` (`id`, `exam_id`, `user_id`, `variant_index`, `meeting_id`, `status`, `started_at`, `submitted_at`, `score`, `auto_score`, `manual_score`, `grader_user_id`, `created_at`, `updated_at`) VALUES
(1, 4, 5, 1, NULL, 'graded', '2025-12-19 15:28:14', '2025-12-19 15:28:14', 0, 0, NULL, NULL, '2025-12-19 15:28:14', NULL),
(2, 4, 10, 1, NULL, 'graded', '2026-01-08 16:39:08', '2026-01-08 16:39:08', 0, 0, NULL, NULL, '2026-01-08 16:39:08', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `exam_files`
--

CREATE TABLE `exam_files` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `attempt_id` bigint(20) UNSIGNED NOT NULL,
  `drive_link` varchar(255) DEFAULT NULL,
  `drive_file_id` varchar(128) DEFAULT NULL,
  `drive_view_link` varchar(255) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `size_bytes` bigint(20) UNSIGNED DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `exam_meetings`
--

CREATE TABLE `exam_meetings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_id` bigint(20) UNSIGNED NOT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `mode` enum('online','onsite') NOT NULL DEFAULT 'online',
  `link` varchar(255) DEFAULT NULL,
  `place` varchar(255) DEFAULT NULL,
  `status` enum('available','booked','done','cancelled') NOT NULL DEFAULT 'available',
  `booked_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `exam_options`
--

CREATE TABLE `exam_options` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `option_text` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `exam_options`
--

INSERT INTO `exam_options` (`id`, `question_id`, `option_text`, `is_correct`) VALUES
(1, 1, 'A . grgrgrr', 1),
(2, 1, 'b fegegegege', 1),
(3, 1, 'grgrgrgrgrgrgr', 1),
(4, 2, 'A . grgrgrr', 1),
(5, 2, 'b fegegegege', 1),
(6, 2, 'grgrgrgrgrgrgr', 1),
(7, 3, 'uil', 1),
(8, 3, 'lui', 0),
(9, 4, 'un jour', 0),
(10, 4, 'un jour de la semaine', 1),
(11, 4, 'un week-end', 0),
(26, 11, 'fgfgdf', 0),
(27, 11, 'gfgfdgdfg', 1),
(28, 11, 'fggf', 0),
(29, 12, 'vxwbvcbxxbxb', 1),
(30, 12, 'dhd', 0),
(31, 12, 'kijh', 0),
(32, 13, 'gfsgfsg', 0),
(33, 13, 'sfgsfgfsgfsg', 1),
(34, 14, 'fgfgf', 1),
(35, 14, 'gsfgg', 0),
(36, 15, 'dgdsgdsgds', 0),
(37, 15, 'dgsgg', 1),
(38, 16, 'sfgfsgfsgsf', 1),
(39, 16, 'sfgsfgsfgsf', 0);

-- --------------------------------------------------------

--
-- Structure de la table `exam_questions`
--

CREATE TABLE `exam_questions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `exam_id` bigint(20) UNSIGNED NOT NULL,
  `variant` tinyint(1) NOT NULL DEFAULT 1,
  `question_text` text NOT NULL,
  `question_type` enum('single','multiple','text') NOT NULL DEFAULT 'single',
  `order_index` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `points` int(10) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `exam_questions`
--

INSERT INTO `exam_questions` (`id`, `exam_id`, `variant`, `question_text`, `question_type`, `order_index`, `points`) VALUES
(1, 1, 1, 'bonjour :', 'single', 1, 1),
(2, 2, 1, 'bonjour :', 'single', 1, 1),
(3, 3, 1, 'huil', 'single', 1, 1),
(4, 4, 1, 'lundi c\'est ?', 'single', 1, 1),
(11, 6, 1, 'gffgf', 'single', 1, 1),
(12, 6, 1, 'fhghcgnfh', 'single', 2, 1),
(13, 6, 2, 'gfggfsgfs', 'single', 1, 1),
(14, 6, 2, 'fdfgg', 'single', 2, 1),
(15, 6, 3, 'gsdfgsgd', 'single', 1, 1),
(16, 6, 3, 'fdgsfgfgfs', 'single', 2, 1);

-- --------------------------------------------------------

--
-- Structure de la table `exam_sessions`
--

CREATE TABLE `exam_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('qcm','drive','meeting') NOT NULL DEFAULT 'qcm',
  `duration_min` int(10) UNSIGNED DEFAULT NULL,
  `due_at` datetime DEFAULT NULL,
  `max_attempts` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `min_score` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `meeting_mode` enum('online','onsite') DEFAULT NULL,
  `meeting_link` varchar(255) DEFAULT NULL,
  `meeting_place` varchar(255) DEFAULT NULL,
  `drive_expected` tinyint(1) NOT NULL DEFAULT 0,
  `drive_subject_link` varchar(255) DEFAULT NULL,
  `drive_subject_name` varchar(255) DEFAULT NULL,
  `settings_json` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `exam_sessions`
--

INSERT INTO `exam_sessions` (`id`, `course_id`, `title`, `description`, `type`, `duration_min`, `due_at`, `max_attempts`, `min_score`, `meeting_mode`, `meeting_link`, `meeting_place`, `drive_expected`, `drive_subject_link`, `drive_subject_name`, `settings_json`, `created_at`, `updated_at`) VALUES
(1, 2, 'bon', 'rgrgrgrgrgrgrgr', 'qcm', 7, '2000-12-22 22:10:00', 1, 10, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-06 11:22:29', NULL),
(2, 2, 'bon', 'rgrgrgrgrgrgrgr', 'qcm', 7, '2000-12-22 22:10:00', 1, 10, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-06 11:27:56', NULL),
(3, 2, 'jjjjjj', 'jjuu', 'qcm', 10, NULL, 1, 10, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-06 12:09:59', NULL),
(4, 4, 'bonjour', 'fefefefefefe', 'qcm', 20, NULL, 1, 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2025-12-10 10:20:20', NULL),
(5, 9, 'essam', 'jyjyj', 'drive', 25, NULL, 1, 10, NULL, NULL, NULL, 1, 'https://docs.google.com/document/d/1LA0ag1mC3cLEs_rl1Iy4DD085x920hOc/edit?usp=drivesdk&ouid=113809619878434382501&rtpof=true&sd=true', 'DEDICACES.docx', NULL, '2026-01-07 12:42:02', NULL),
(6, 10, 'Quiz de validation', 'Evaluation finale sur les notions cles du module.', 'qcm', 15, NULL, 3, 12, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-01-10 18:56:38', '2026-06-10 17:53:32'),
(7, 5, 'vddd', 'vdvs', 'drive', 20, NULL, 1, 10, NULL, NULL, NULL, 1, 'https://drive.google.com/file/d/1Go1OeOFsOChuSOsRx-hsgFAmDaP8nHB6/view?usp=drivesdk', 'Cahier_des_charges_Mirhe_TV_v2.pdf', '{\"randomization\":{\"question_shuffle\":true,\"option_shuffle\":true,\"variant_strategy\":\"sequential\"},\"scoring\":{\"multiple_partial_credit\":true,\"wrong_penalty\":1},\"availability\":{\"enabled\":false,\"start_at\":\"\",\"end_at\":\"\"},\"anti_cheat\":{\"fullscreen_required\":true,\"copy_paste_block\":true,\"tab_switch_warning\":true,\"camera_required\":true}}', '2026-02-17 11:05:16', NULL),
(8, 15, 'gegegeg', 'hthththrthhhhhhhhhhhhhhhhhhhhhhhh', 'drive', 14, NULL, 1, 15, NULL, NULL, NULL, 1, 'https://docs.google.com/spreadsheets/d/1wk6c-lSWbL3Oj_5MKcu9QmOQiRRFyGJT/edit?usp=drivesdk&ouid=113809619878434382501&rtpof=true&sd=true', 'Classeur1.xlsx', '{\"randomization\":{\"question_shuffle\":true,\"option_shuffle\":true,\"variant_strategy\":\"sequential\"},\"scoring\":{\"multiple_partial_credit\":false,\"wrong_penalty\":0},\"availability\":{\"enabled\":false,\"start_at\":\"\",\"end_at\":\"\"},\"anti_cheat\":{\"fullscreen_required\":false,\"copy_paste_block\":false,\"tab_switch_warning\":false,\"camera_required\":false}}', '2026-04-13 14:11:05', '2026-04-17 14:06:07'),
(10, 19, 'Examen 1', 'ssssssssssssssssssssssssssssssss', 'meeting', 45, NULL, 1, 50, 'online', NULL, NULL, 0, NULL, NULL, '{\"randomization\":{\"question_shuffle\":true,\"option_shuffle\":true,\"variant_strategy\":\"sequential\"},\"scoring\":{\"multiple_partial_credit\":false,\"wrong_penalty\":0},\"availability\":{\"enabled\":false,\"start_at\":\"\",\"end_at\":\"\"},\"anti_cheat\":{\"fullscreen_required\":false,\"copy_paste_block\":false,\"tab_switch_warning\":false,\"camera_required\":false}}', '2026-06-04 17:36:43', NULL),
(12, 21, 'Évaluation finale', ',,,,,,,,,,,,,,,,,,,', 'qcm', 45, NULL, 3, 50, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-06-19 14:28:02', NULL),
(13, 20, 'Évaluation finale', ',,,,,,,,,,,,,,,,,,,', 'qcm', 45, NULL, 3, 50, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2026-06-19 14:37:16', NULL),
(14, 22, 'Évaluation finale', 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', 'drive', 45, NULL, 1, 50, NULL, NULL, NULL, 1, 'http://localhost/ecolenumerique/storage/uploads/drive/2026/07/exam_sujet_22_20260702_162720_Capture_d_cran_2026-07-02_172633_20260702_162720_4dd4f1dd.png', 'Capture_d_cran_2026-07-02_172633.png', NULL, '2026-07-02 15:27:20', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `lesson_bookmarks`
--

CREATE TABLE `lesson_bookmarks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `lesson_id` bigint(20) UNSIGNED NOT NULL,
  `timestamp_seconds` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `label` varchar(120) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lesson_completions`
--

CREATE TABLE `lesson_completions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `lesson_id` bigint(20) UNSIGNED NOT NULL,
  `completed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `lesson_completions`
--

INSERT INTO `lesson_completions` (`id`, `user_id`, `course_id`, `lesson_id`, `completed_at`) VALUES
(1, 5, 4, 7, '2025-12-13 18:17:19'),
(22, 5, 4, 8, '2025-12-15 12:15:49'),
(74, 10, 4, 7, '2026-01-08 15:04:44'),
(75, 10, 4, 8, '2026-01-08 16:38:34'),
(76, 10, 9, 13, '2026-01-15 16:36:22'),
(77, 10, 9, 14, '2026-01-15 16:39:35'),
(78, 10, 9, 15, '2026-01-15 16:42:24'),
(79, 5, 4, 7, '2026-05-22 18:52:08'),
(80, 5, 4, 7, '2026-05-22 18:52:11'),
(81, 5, 4, 7, '2026-05-22 18:52:18'),
(82, 5, 4, 7, '2026-05-22 18:52:21'),
(83, 5, 4, 7, '2026-05-22 18:52:22'),
(84, 5, 4, 7, '2026-05-22 18:52:23'),
(85, 5, 4, 7, '2026-05-22 18:52:23'),
(86, 5, 4, 7, '2026-05-22 18:52:23'),
(87, 5, 4, 7, '2026-05-22 18:52:23'),
(88, 5, 4, 7, '2026-05-22 18:52:23'),
(89, 5, 4, 7, '2026-05-22 18:52:24'),
(90, 5, 4, 7, '2026-05-22 18:52:24'),
(91, 5, 4, 7, '2026-05-22 18:52:24'),
(92, 5, 4, 7, '2026-05-22 18:52:24'),
(93, 5, 4, 7, '2026-05-22 18:52:24'),
(94, 5, 4, 7, '2026-05-22 18:59:11'),
(95, 5, 4, 7, '2026-05-22 18:59:13'),
(96, 5, 4, 7, '2026-05-22 18:59:14'),
(97, 5, 4, 7, '2026-06-13 02:25:07');

-- --------------------------------------------------------

--
-- Structure de la table `lesson_positions`
--

CREATE TABLE `lesson_positions` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `lesson_id` bigint(20) UNSIGNED NOT NULL,
  `seconds` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(190) NOT NULL,
  `body` text DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `popup_shown_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `announcement_id` bigint(20) UNSIGNED DEFAULT NULL,
  `importance` enum('info','important','urgent') NOT NULL DEFAULT 'info',
  `summary` varchar(200) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `clicked_at` datetime DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `body`, `url`, `is_read`, `popup_shown_at`, `created_at`, `announcement_id`, `importance`, `summary`, `read_at`, `clicked_at`, `archived_at`) VALUES
(1, 4, 'Nouvelle demande enseignant', 'Michaël ouwade a soumis une demande.', '/ecolenumerique/admin/teacher_applications.php', 1, NULL, '2025-11-28 13:55:17', NULL, 'info', NULL, NULL, NULL, NULL),
(2, 4, 'Cours soumis', 'rgrrb a été soumis pour validation.', '/ecolenumerique/admin/dashboard.php', 1, NULL, '2025-12-01 13:07:47', NULL, 'info', NULL, NULL, NULL, NULL),
(3, 1, 'Cours approuvé', 'rgrrb a été approuvé.', '/ecolenumerique/teacher/courses.php', 1, NULL, '2025-12-02 17:05:39', NULL, 'info', NULL, NULL, NULL, NULL),
(4, 1, 'Nouvel avis', 'Michaël a noté \"rgrrb\" : 5/5', '/ecolenumerique/course.php?id=2', 1, NULL, '2025-12-04 16:36:07', NULL, 'info', NULL, NULL, NULL, NULL),
(5, 1, 'Nouvel avis', 'Michaël a noté \"rgrrb\" : 4/5', '/ecolenumerique/course.php?id=2', 1, NULL, '2025-12-05 12:25:00', NULL, 'info', NULL, NULL, NULL, NULL),
(6, 4, 'Cours soumis', 'word a ?t? soumis pour validation.', '/ecolenumerique/admin/courses.php?course_id=4', 1, NULL, '2025-12-10 10:42:48', NULL, 'info', NULL, NULL, NULL, NULL),
(7, 1, 'Cours approuvé', 'word a été approuvé.', '/ecolenumerique/teacher/courses.php', 1, NULL, '2025-12-10 10:53:12', NULL, 'info', NULL, NULL, NULL, NULL),
(8, 4, 'Signalement de cours', 'Michaël a signale \"word\". Motif : mauvais cours', 'http://localhost/ecolenumerique/admin/courses.php?course_id=4', 1, NULL, '2025-12-10 12:16:13', NULL, 'info', NULL, NULL, NULL, NULL),
(9, 4, 'Signalement de cours', 'Michaël a signale \"word\". Motif : azertyuiopqsdfghjklm', 'http://localhost/ecolenumerique/admin/courses.php?course_id=4', 1, NULL, '2025-12-10 13:12:58', NULL, 'info', NULL, NULL, NULL, NULL),
(10, 4, 'Signalement de cours', 'Michaël a signale \"word\". Motif : azertyuimlkjhgfdsqwxcvbn', 'http://localhost/ecolenumerique/admin/reports.php?status=open', 1, NULL, '2025-12-10 14:21:31', NULL, 'info', NULL, NULL, NULL, NULL),
(11, 5, 'Signalement mis à jour', 'Votre signalement sur \"word\" est maintenant : Résolu', '/ecolenumerique/course.php?slug=word', 1, NULL, '2025-12-10 14:22:30', NULL, 'info', NULL, NULL, NULL, NULL),
(12, 5, 'Mise a jour terminee', 'Le cours \"word\" a ete mis a jour. Vous pouvez consulter les nouveautes.', 'http://localhost/ecolenumerique/course.php?slug=word', 1, NULL, '2025-12-18 12:02:20', NULL, 'info', NULL, NULL, NULL, NULL),
(13, 1, 'Mise a jour terminee', 'Le cours \"word\" a ete mis a jour. Vous pouvez consulter les nouveautes.', 'http://localhost/ecolenumerique/course.php?slug=word', 1, NULL, '2025-12-18 12:02:20', NULL, 'info', NULL, NULL, NULL, NULL),
(14, 5, 'Mise a jour terminee', 'Le cours \"word\" a ete mis a jour. Vous pouvez consulter les nouveautes.', 'http://localhost/ecolenumerique/course.php?slug=word', 1, NULL, '2025-12-18 12:07:45', NULL, 'info', NULL, NULL, NULL, NULL),
(15, 1, 'Mise a jour terminee', 'Le cours \"word\" a ete mis a jour. Vous pouvez consulter les nouveautes.', 'http://localhost/ecolenumerique/course.php?slug=word', 1, NULL, '2025-12-18 12:07:45', NULL, 'info', NULL, NULL, NULL, NULL),
(16, 5, 'Cours en mise a jour', 'Le cours \"word\" est en cours de mise a jour. Certaines lecons ou ressources peuvent changer.', 'http://localhost/ecolenumerique/course.php?slug=word', 1, NULL, '2025-12-18 12:08:05', NULL, 'info', NULL, NULL, NULL, NULL),
(17, 1, 'Cours en mise a jour', 'Le cours \"word\" est en cours de mise a jour. Certaines lecons ou ressources peuvent changer.', 'http://localhost/ecolenumerique/course.php?slug=word', 1, NULL, '2025-12-18 12:08:05', NULL, 'info', NULL, NULL, NULL, NULL),
(18, 5, 'Mise a jour terminee', 'Le cours \"word\" a ete mis a jour. Vous pouvez consulter les nouveautes.', 'http://localhost/ecolenumerique/course.php?slug=word', 1, NULL, '2025-12-18 12:10:46', NULL, 'info', NULL, NULL, NULL, NULL),
(19, 1, 'Mise a jour terminee', 'Le cours \"word\" a ete mis a jour. Vous pouvez consulter les nouveautes.', 'http://localhost/ecolenumerique/course.php?slug=word', 1, NULL, '2025-12-18 12:10:46', NULL, 'info', NULL, NULL, NULL, NULL),
(20, 1, 'Nouvelle demande de rendez-vous', 'Michaël Ouwade a propose des creneaux pour \"word\" : 2025-12-18 20:53:00 - 2025-12-18 21:53:00 | 2026-01-10 19:52:00 - 2026-01-10 20:52:00 | 2025-12-18 19:52:00 - 2025-12-18 20:52:00', 'http://localhost/ecolenumerique/teacher/meetings.php?status=pending&course_id=4', 1, NULL, '2025-12-18 13:47:36', NULL, 'info', NULL, NULL, NULL, NULL),
(21, 5, 'Rendez-vous confirme', 'Votre rendez-vous pour \"word\" a ete confirme. Debut : 2025-12-18 19:52:00 - Fin : 2025-12-18 20:52:00. Lien : https://docs.google.com/presentation/d/1cR3ua8u8CAeLSUrUtyQXyQ9a2UYMyOT6SZdp57qQK9E/edit?usp=drive_link', 'http://localhost/ecolenumerique/course.php?slug=word', 1, NULL, '2025-12-18 14:38:46', NULL, 'info', NULL, NULL, NULL, NULL),
(22, 5, 'Rendez-vous annule', 'Le rendez-vous \"mercredi\" a ete annule par le formateur.', 'http://localhost/ecolenumerique/course.php?slug=word', 1, NULL, '2025-12-18 14:55:56', NULL, 'info', NULL, '2026-05-14 12:29:41', NULL, NULL),
(23, 1, 'Nouvelle demande de rendez-vous', 'Michaël Ouwade a propose des creneaux pour \"word\" : 2025-12-19 15:59:00 - 2025-12-19 16:59:00 | 2026-01-02 15:59:00 - 2026-01-02 16:59:00 | 2026-01-11 15:04:00 - 2026-01-11 16:04:00', 'http://localhost/ecolenumerique/teacher/meetings.php?status=pending&course_id=4', 1, NULL, '2025-12-18 14:59:53', NULL, 'info', NULL, NULL, NULL, NULL),
(24, 5, 'Nouveaux creneaux demandes', 'Le formateur n est disponible sur aucun de vos creneaux proposes pour \"word\". Merci de proposer 3 nouveaux creneaux.', 'http://localhost/ecolenumerique/course.php?slug=word', 1, NULL, '2025-12-18 16:19:44', NULL, 'info', NULL, NULL, NULL, NULL),
(25, 1, 'Nouvelle demande de rendez-vous', 'Michaël Ouwade a propose des creneaux pour \"word\" : 2026-01-01 21:27:00 - 2026-01-01 22:27:00 | 2025-12-27 22:28:00 - 2025-12-27 23:28:00 | 2026-01-09 00:30:00 - 2026-01-09 01:30:00', 'http://localhost/ecolenumerique/teacher/meetings.php?status=pending&course_id=4', 1, NULL, '2025-12-18 17:24:56', NULL, 'info', NULL, NULL, NULL, NULL),
(26, 5, 'Rendez-vous confirme', 'Votre rendez-vous pour \"word\" a ete confirme. Debut : 2025-12-27 22:28:00 - Fin : 2025-12-27 23:28:00. Lien : https://docs.google.com/presentation/d/1cR3ua8u8CAeLSUrUtyQXyQ9a2UYMyOT6SZdp57qQK9E/edit?usp=drive_link', 'http://localhost/ecolenumerique/course.php?slug=word', 1, '2026-03-09 18:23:21', '2025-12-18 17:25:39', NULL, 'info', NULL, '2026-05-14 12:29:41', NULL, NULL),
(27, 4, 'Cours soumis', 'Excel a ete soumis pour validation.', 'http://localhost/ecolenumerique/admin/courses.php?course_id=9', 1, NULL, '2026-01-07 12:42:16', NULL, 'info', NULL, NULL, NULL, NULL),
(28, 1, 'Cours rejeté', 'Excel a été rejeté. Motif : miniature', '/ecolenumerique/teacher/courses.php', 1, NULL, '2026-01-07 12:49:40', NULL, 'info', NULL, NULL, NULL, NULL),
(29, 4, 'Cours soumis', 'Excel a ete soumis pour validation.', 'http://localhost/ecolenumerique/admin/courses.php?course_id=9', 1, NULL, '2026-01-07 12:51:36', NULL, 'info', NULL, NULL, NULL, NULL),
(30, 1, 'Cours rejeté', 'Excel a été rejeté. Motif : miniature', '/ecolenumerique/teacher/courses.php', 1, NULL, '2026-01-07 12:52:34', NULL, 'info', NULL, NULL, NULL, NULL),
(31, 4, 'Cours soumis', 'Excel a ?t? soumis pour validation.', '/ecolenumerique/admin/courses.php?course_id=9', 1, NULL, '2026-01-07 12:53:56', NULL, 'info', NULL, NULL, NULL, NULL),
(32, 1, 'Cours approuvé', 'Excel a été approuvé par l’admin.', '/ecolenumerique/teacher/courses.php', 1, NULL, '2026-01-07 12:54:22', NULL, 'info', NULL, NULL, NULL, NULL),
(33, 10, 'Certificat disponible', 'word : votre certificat est disponible.', '/ecolenumerique/certificate.php?code=CERT-103C0090B936CD83', 0, '2026-03-18 14:39:29', '2026-01-08 16:39:08', NULL, 'info', NULL, NULL, NULL, NULL),
(34, 4, 'Nouvelle demande support', 'Sujet: hhtht', '/ecolenumerique/admin/support.php?conv=1', 1, NULL, '2026-01-09 10:00:40', NULL, 'info', NULL, NULL, NULL, NULL),
(35, 4, 'Nouvelle demande support', 'Sujet: rrrrgrg', '/ecolenumerique/admin/support.php?conv=2', 1, NULL, '2026-01-09 10:39:19', NULL, 'info', NULL, NULL, NULL, NULL),
(36, 10, 'Support: nouveau message', 'Un agent support a repondu a votre demande.', '/ecolenumerique/support.php?conv=2', 0, '2026-03-18 14:39:29', '2026-01-09 12:47:38', NULL, 'info', NULL, NULL, NULL, NULL),
(37, 10, 'Support: nouveau message', 'Un agent support a repondu a votre demande.', '/ecolenumerique/support.php?conv=2', 0, '2026-03-18 14:39:29', '2026-01-09 12:48:59', NULL, 'info', NULL, NULL, NULL, NULL),
(38, 4, 'Nouvelle demande enseignant', 'mica mica a soumis une demande.', '/ecolenumerique/admin/teacher_applications.php', 1, NULL, '2026-01-10 19:12:57', NULL, 'info', NULL, NULL, NULL, NULL),
(39, 10, 'Demande enseignant approuvée', 'Votre demande a été approuvée. Vous pouvez accéder au dashboard enseignant.', '/ecolenumerique/teacher/dashboard.php', 0, '2026-03-18 14:39:29', '2026-01-10 19:20:39', NULL, 'info', NULL, NULL, NULL, NULL),
(40, 4, 'Cours soumis', 'marketing digital a ?t? soumis pour validation.', '/ecolenumerique/admin/courses.php?course_id=10', 1, NULL, '2026-01-10 19:57:17', NULL, 'info', NULL, NULL, NULL, NULL),
(41, 10, 'Cours approuvé', 'marketing digital a été approuvé.', '/ecolenumerique/teacher/courses.php', 0, '2026-03-18 14:39:29', '2026-01-10 20:01:06', NULL, 'info', NULL, NULL, NULL, NULL),
(42, 4, 'Cours soumis', 'Excel a ete soumis pour validation.', 'http://localhost/ecolenumerique/admin/courses?course_id=5', 1, NULL, '2026-02-17 12:12:21', NULL, 'info', NULL, NULL, NULL, NULL),
(43, 1, '[TEST PUSH 20260217124014] Etudiant', 'Test popup+push genere le 2026-02-17 12:40:14 (role: student).', '/student/dashboard', 1, NULL, '2026-02-17 12:40:14', NULL, 'info', NULL, NULL, NULL, NULL),
(44, 8, '[TEST PUSH 20260217124014] Enseignant', 'Test popup+push genere le 2026-02-17 12:40:14 (role: teacher).', '/teacher/dashboard', 1, '2026-03-09 17:19:23', '2026-02-17 12:40:14', NULL, 'info', NULL, NULL, NULL, NULL),
(45, 4, '[TEST PUSH 20260217124014] Admin', 'Test popup+push genere le 2026-02-17 12:40:14 (role: admin).', '/admin/dashboard', 1, NULL, '2026-02-17 12:40:14', NULL, 'info', NULL, NULL, NULL, NULL),
(46, 12, '[TEST PUSH 20260217124014] Entreprise', 'Test popup+push genere le 2026-02-17 12:40:14 (role: company_admin).', '/company/dashboard', 1, NULL, '2026-02-17 12:40:14', NULL, 'info', NULL, NULL, NULL, NULL),
(47, 1, 'Cours approuve', 'Excel a ete approuve.', '/ecolenumerique/teacher/courses', 1, NULL, '2026-02-17 12:42:25', NULL, 'info', NULL, NULL, NULL, NULL),
(48, 1, 'Nouvel avis', 'Michaël a note \"word\" : 3/5', '/ecolenumerique/course?id=4', 1, NULL, '2026-02-17 14:40:18', NULL, 'info', NULL, NULL, NULL, NULL),
(49, 4, 'Nouvelle demande support', 'Sujet: nogrnogrgrrrrrr', '/ecolenumerique/admin/support?conv=3', 1, NULL, '2026-02-19 15:09:32', NULL, 'info', NULL, NULL, NULL, NULL),
(50, 6, 'Nouvelle demande support', 'Sujet: nogrnogrgrrrrrr', '/ecolenumerique/admin/support?conv=3', 1, NULL, '2026-02-19 15:09:32', NULL, 'info', NULL, NULL, NULL, NULL),
(51, 13, 'Nouvelle demande support', 'Sujet: nogrnogrgrrrrrr', '/ecolenumerique/admin/support?conv=3', 0, NULL, '2026-02-19 15:09:32', NULL, 'info', NULL, NULL, NULL, NULL),
(52, 4, 'Nouvelle demande support', 'Sujet: eeevevevevee', '/ecolenumerique/admin/support?conv=4', 1, NULL, '2026-02-19 15:26:46', NULL, 'info', NULL, NULL, NULL, NULL),
(53, 6, 'Nouvelle demande support', 'Sujet: eeevevevevee', '/ecolenumerique/admin/support?conv=4', 1, NULL, '2026-02-19 15:26:46', NULL, 'info', NULL, NULL, NULL, NULL),
(54, 13, 'Nouvelle demande support', 'Sujet: eeevevevevee', '/ecolenumerique/admin/support?conv=4', 0, NULL, '2026-02-19 15:26:46', NULL, 'info', NULL, NULL, NULL, NULL),
(55, 12, 'Support: nouveau message', 'Un agent support a repondu a votre demande.', '/ecolenumerique/support?conv=4', 1, NULL, '2026-02-19 15:27:21', NULL, 'info', NULL, NULL, NULL, NULL),
(56, 12, 'Support: nouveau message', 'Un agent support a repondu a votre demande.', '/ecolenumerique/support?conv=4', 1, NULL, '2026-02-19 15:47:48', NULL, 'info', NULL, NULL, NULL, NULL),
(57, 4, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', '/ecolenumerique/admin/support?conv=4', 1, NULL, '2026-02-19 15:48:37', NULL, 'info', NULL, NULL, NULL, NULL),
(58, 6, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', '/ecolenumerique/admin/support?conv=4', 1, NULL, '2026-02-19 15:48:37', NULL, 'info', NULL, NULL, NULL, NULL),
(59, 13, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', '/ecolenumerique/admin/support?conv=4', 0, NULL, '2026-02-19 15:48:37', NULL, 'info', NULL, NULL, NULL, NULL),
(60, 4, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', 'admin/support?conv=4', 1, '2026-03-25 18:23:41', '2026-02-19 15:57:14', NULL, 'info', NULL, '2026-05-25 16:53:59', NULL, NULL),
(61, 6, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', 'admin/support?conv=4', 1, NULL, '2026-02-19 15:57:14', NULL, 'info', NULL, NULL, NULL, NULL),
(62, 13, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', 'admin/support?conv=4', 0, NULL, '2026-02-19 15:57:14', NULL, 'info', NULL, NULL, NULL, NULL),
(63, 12, 'Support: nouveau message', 'Un agent support a repondu a votre demande.', 'support?conv=4', 1, NULL, '2026-02-19 15:57:37', NULL, 'info', NULL, NULL, NULL, NULL),
(64, 12, 'Support: nouveau message', 'Un agent support a repondu a votre demande.', 'support?conv=4', 1, NULL, '2026-02-19 15:58:44', NULL, 'info', NULL, NULL, NULL, NULL),
(65, 4, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', 'admin/support?conv=4', 1, NULL, '2026-02-19 15:59:14', NULL, 'info', NULL, NULL, NULL, NULL),
(66, 6, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', 'admin/support?conv=4', 1, NULL, '2026-02-19 15:59:14', NULL, 'info', NULL, NULL, NULL, NULL),
(67, 13, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', 'admin/support?conv=4', 0, NULL, '2026-02-19 15:59:14', NULL, 'info', NULL, NULL, NULL, NULL),
(68, 12, 'Support: nouveau message', 'Un agent support a repondu a votre demande.', 'support?conv=4', 1, NULL, '2026-02-19 16:09:47', NULL, 'info', NULL, NULL, NULL, NULL),
(69, 4, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', 'admin/support?conv=4', 1, '2026-03-11 14:55:35', '2026-02-19 16:18:29', NULL, 'info', NULL, NULL, NULL, NULL),
(70, 6, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', 'admin/support?conv=4', 1, NULL, '2026-02-19 16:18:29', NULL, 'info', NULL, NULL, NULL, NULL),
(71, 13, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', 'admin/support?conv=4', 0, NULL, '2026-02-19 16:18:29', NULL, 'info', NULL, NULL, NULL, NULL),
(72, 12, 'Support: nouveau message', 'Un agent support a repondu a votre demande.', 'support?conv=4', 1, NULL, '2026-02-19 16:42:23', NULL, 'info', NULL, NULL, NULL, NULL),
(73, 12, 'Support: nouveau message', 'Un agent support a repondu a votre demande.', 'support?conv=4', 1, NULL, '2026-02-19 16:43:14', NULL, 'info', NULL, NULL, NULL, NULL),
(74, 1, 'Annonce: jhvvjkvvkvkkv', 'ooooobjbllblbjlblblblbllj', '/ecolenumerique/notifications?scope=admin', 1, '2026-03-11 12:35:37', '2026-02-23 11:21:40', NULL, 'info', NULL, '2026-05-14 09:44:45', NULL, NULL),
(75, 4, 'Annonce: jhvvjkvvkvkkv', 'ooooobjbllblbjlblblblbllj', '/ecolenumerique/notifications?scope=admin', 1, NULL, '2026-02-23 11:21:40', NULL, 'info', NULL, NULL, NULL, NULL),
(76, 5, 'Annonce: jhvvjkvvkvkkv', 'ooooobjbllblbjlblblblbllj', '/ecolenumerique/notifications?scope=admin', 1, '2026-03-09 18:23:21', '2026-02-23 11:21:40', NULL, 'info', NULL, '2026-05-14 12:29:41', NULL, NULL),
(77, 6, 'Annonce: jhvvjkvvkvkkv', 'ooooobjbllblbjlblblblbllj', '/ecolenumerique/notifications?scope=admin', 0, '2026-03-10 18:08:06', '2026-02-23 11:21:40', NULL, 'info', NULL, NULL, NULL, NULL),
(78, 8, 'Annonce: jhvvjkvvkvkkv', 'ooooobjbllblbjlblblblbllj', '/ecolenumerique/notifications?scope=admin', 1, '2026-03-09 17:19:23', '2026-02-23 11:21:40', NULL, 'info', NULL, NULL, NULL, NULL),
(79, 9, 'Annonce: jhvvjkvvkvkkv', 'ooooobjbllblbjlblblblbllj', '/ecolenumerique/notifications?scope=admin', 0, '2026-03-10 12:53:28', '2026-02-23 11:21:40', NULL, 'info', NULL, NULL, NULL, NULL),
(80, 10, 'Annonce: jhvvjkvvkvkkv', 'ooooobjbllblbjlblblblbllj', '/ecolenumerique/notifications?scope=admin', 0, '2026-03-18 14:39:29', '2026-02-23 11:21:40', NULL, 'info', NULL, NULL, NULL, NULL),
(81, 12, 'Annonce: jhvvjkvvkvkkv', 'ooooobjbllblbjlblblblbllj', '/ecolenumerique/notifications?scope=admin', 1, NULL, '2026-02-23 11:21:40', NULL, 'info', NULL, NULL, NULL, NULL),
(82, 13, 'Annonce: jhvvjkvvkvkkv', 'ooooobjbllblbjlblblblbllj', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-02-23 11:21:40', NULL, 'info', NULL, NULL, NULL, NULL),
(83, 8, 'Invitation entreprise', 'Vous avez ete invite a rejoindre \"michael\".', 'http://localhost/ecolenumerique/company/invite_accept?token=9ba5666e70de3170a8fed14f4c8e0b1d85e564f973275f851de46ba3bc3b207c', 1, '2026-03-09 17:19:23', '2026-03-09 16:05:25', NULL, 'info', NULL, NULL, NULL, NULL),
(84, 1, 'Demande de retrait envoyee', 'Votre demande de retrait enseignant de 23820 XOF est en attente de validation.', '/ecolenumerique/teacher/withdrawals', 1, '2026-03-21 14:04:58', '2026-03-21 14:04:58', NULL, 'info', NULL, '2026-05-14 09:44:45', NULL, NULL),
(85, 1, 'Demande de retrait envoyee', 'Votre demande de retrait enseignant de 23820 XOF est en attente de validation.', '/ecolenumerique/teacher/withdrawals', 1, '2026-03-21 14:22:36', '2026-03-21 14:22:36', NULL, 'info', NULL, '2026-05-14 09:44:45', NULL, NULL),
(86, 1, 'Demande de retrait envoyee', 'Votre demande de retrait enseignant de 23820 XOF est en attente de validation.', '/ecolenumerique/teacher/withdrawals', 1, '2026-03-21 14:34:00', '2026-03-21 14:34:00', NULL, 'info', NULL, '2026-05-14 09:44:45', NULL, NULL),
(87, 1, 'Demande de retrait envoyee', 'Votre demande de retrait enseignant de 23820 XOF est en attente de validation.', '/ecolenumerique/teacher/withdrawals', 1, '2026-03-21 14:43:51', '2026-03-21 14:43:51', NULL, 'info', NULL, '2026-05-14 09:44:45', NULL, NULL),
(88, 1, 'Demande de retrait envoyee', 'Votre demande de retrait enseignant de 23820 XOF est en attente de validation.', '/ecolenumerique/teacher/withdrawals', 1, '2026-03-21 14:44:02', '2026-03-21 14:44:00', NULL, 'info', NULL, '2026-05-14 09:39:55', NULL, NULL),
(143, 1, 'Test annonce', 'Corps test', 'http://example.com', 1, '2026-03-25 12:11:54', '2026-03-25 12:03:56', 9, 'info', 'Resume test', '2026-05-14 09:44:45', NULL, NULL),
(162, 1, 'Mise à jour administrative', 'Vos informations de compte ont été vérifiées. Cette mise à jour ne demande aucune action immédiate.', '/ecolenumerique/profile?space=student', 1, '2026-03-25 15:30:48', '2026-03-25 12:13:20', 12, 'info', 'Vos informations de compte ont été vérifiées. Cette mise à jour ne demande aucune action immédiate.', '2026-05-14 09:44:45', NULL, NULL),
(163, 4, 'Mise à jour administrative', 'Vos informations de compte ont été vérifiées. Cette mise à jour ne demande aucune action immédiate.', '/ecolenumerique/profile?space=student', 1, '2026-03-25 16:11:51', '2026-03-25 12:13:20', 12, 'info', 'Vos informations de compte ont été vérifiées. Cette mise à jour ne demande aucune action immédiate.', '2026-03-25 16:12:01', NULL, NULL),
(164, 5, 'Mise à jour administrative', 'Vos informations de compte ont été vérifiées. Cette mise à jour ne demande aucune action immédiate.', '/ecolenumerique/profile?space=student', 1, '2026-03-25 12:18:20', '2026-03-25 12:13:20', 12, 'info', 'Vos informations de compte ont été vérifiées. Cette mise à jour ne demande aucune action immédiate.', '2026-05-14 12:29:41', NULL, NULL),
(165, 6, 'Mise à jour administrative', 'Vos informations de compte ont été vérifiées. Cette mise à jour ne demande aucune action immédiate.', '/ecolenumerique/profile?space=student', 0, '2026-03-26 18:42:29', '2026-03-25 12:13:20', 12, 'info', 'Vos informations de compte ont été vérifiées. Cette mise à jour ne demande aucune action immédiate.', NULL, NULL, NULL),
(166, 8, 'Mise à jour administrative', 'Vos informations de compte ont été vérifiées. Cette mise à jour ne demande aucune action immédiate.', '/ecolenumerique/profile?space=student', 0, '2026-03-25 16:57:26', '2026-03-25 12:13:20', 12, 'info', 'Vos informations de compte ont été vérifiées. Cette mise à jour ne demande aucune action immédiate.', NULL, NULL, NULL),
(167, 9, 'Mise à jour administrative', 'Vos informations de compte ont été vérifiées. Cette mise à jour ne demande aucune action immédiate.', '/ecolenumerique/profile?space=student', 0, '2026-04-03 18:49:36', '2026-03-25 12:13:20', 12, 'info', 'Vos informations de compte ont été vérifiées. Cette mise à jour ne demande aucune action immédiate.', NULL, NULL, NULL),
(168, 10, 'Mise à jour administrative', 'Vos informations de compte ont été vérifiées. Cette mise à jour ne demande aucune action immédiate.', '/ecolenumerique/profile?space=student', 0, '2026-03-25 12:15:59', '2026-03-25 12:13:20', 12, 'info', 'Vos informations de compte ont été vérifiées. Cette mise à jour ne demande aucune action immédiate.', NULL, NULL, NULL),
(169, 12, 'Mise à jour administrative', 'Vos informations de compte ont été vérifiées. Cette mise à jour ne demande aucune action immédiate.', '/ecolenumerique/profile?space=student', 0, '2026-03-26 19:01:14', '2026-03-25 12:13:20', 12, 'info', 'Vos informations de compte ont été vérifiées. Cette mise à jour ne demande aucune action immédiate.', NULL, NULL, NULL),
(170, 13, 'Mise à jour administrative', 'Vos informations de compte ont été vérifiées. Cette mise à jour ne demande aucune action immédiate.', '/ecolenumerique/profile?space=student', 0, NULL, '2026-03-25 12:13:20', 12, 'info', 'Vos informations de compte ont été vérifiées. Cette mise à jour ne demande aucune action immédiate.', NULL, NULL, NULL),
(172, 4, 'Document administratif disponible', 'Un nouveau document lié à votre parcours est disponible dans votre espace personnel.', '/ecolenumerique/profile?space=student', 0, '2026-03-25 16:12:29', '2026-03-25 16:12:29', 6, 'info', 'Un nouveau document lié à votre parcours est disponible dans votre espace personnel.', NULL, NULL, NULL),
(173, 5, 'Document administratif disponible', 'Un nouveau document lié à votre parcours est disponible dans votre espace personnel.', '/ecolenumerique/profile?space=student', 1, '2026-03-25 16:45:09', '2026-03-25 16:12:29', 6, 'info', 'Un nouveau document lié à votre parcours est disponible dans votre espace personnel.', '2026-05-14 12:29:41', NULL, NULL),
(174, 6, 'Document administratif disponible', 'Un nouveau document lié à votre parcours est disponible dans votre espace personnel.', '/ecolenumerique/profile?space=student', 0, '2026-03-26 18:42:29', '2026-03-25 16:12:29', 6, 'info', 'Un nouveau document lié à votre parcours est disponible dans votre espace personnel.', NULL, NULL, NULL),
(175, 8, 'Document administratif disponible', 'Un nouveau document lié à votre parcours est disponible dans votre espace personnel.', '/ecolenumerique/profile?space=student', 0, '2026-03-25 16:57:26', '2026-03-25 16:12:29', 6, 'info', 'Un nouveau document lié à votre parcours est disponible dans votre espace personnel.', NULL, NULL, NULL),
(176, 9, 'Document administratif disponible', 'Un nouveau document lié à votre parcours est disponible dans votre espace personnel.', '/ecolenumerique/profile?space=student', 0, '2026-04-03 18:49:36', '2026-03-25 16:12:29', 6, 'info', 'Un nouveau document lié à votre parcours est disponible dans votre espace personnel.', NULL, NULL, NULL),
(177, 10, 'Document administratif disponible', 'Un nouveau document lié à votre parcours est disponible dans votre espace personnel.', '/ecolenumerique/profile?space=student', 0, '2026-03-26 19:00:11', '2026-03-25 16:12:29', 6, 'info', 'Un nouveau document lié à votre parcours est disponible dans votre espace personnel.', NULL, NULL, NULL),
(178, 12, 'Document administratif disponible', 'Un nouveau document lié à votre parcours est disponible dans votre espace personnel.', '/ecolenumerique/profile?space=student', 0, '2026-03-26 19:01:14', '2026-03-25 16:12:29', 6, 'info', 'Un nouveau document lié à votre parcours est disponible dans votre espace personnel.', NULL, NULL, NULL),
(179, 13, 'Document administratif disponible', 'Un nouveau document lié à votre parcours est disponible dans votre espace personnel.', '/ecolenumerique/profile?space=student', 0, NULL, '2026-03-25 16:12:29', 6, 'info', 'Un nouveau document lié à votre parcours est disponible dans votre espace personnel.', NULL, NULL, NULL),
(180, 4, 'Nouvelle demande support', 'Sujet: bonjours', 'admin/support?conv=5', 1, '2026-03-25 16:58:57', '2026-03-25 16:58:41', NULL, 'info', NULL, '2026-05-25 16:53:59', NULL, NULL),
(181, 13, 'Nouvelle demande support', 'Sujet: bonjours', 'admin/support?conv=5', 0, NULL, '2026-03-25 16:58:41', NULL, 'info', NULL, NULL, NULL, NULL),
(182, 1, 'Demande de retrait envoyée', 'Votre demande de retrait enseignant de 23820 F CFA est en attente de validation.', '/ecolenumerique/teacher/withdrawals', 1, '2026-03-28 14:14:07', '2026-03-28 14:14:07', NULL, 'info', NULL, '2026-05-14 09:44:45', NULL, NULL),
(183, 1, 'y(((((((((\'', 'rgerheeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 1, '2026-05-14 10:27:49', '2026-04-03 18:25:27', 13, 'info', 'rgerheeeeeeeeeeeeeeeeeeee', '2026-04-04 15:53:23', NULL, NULL),
(184, 4, 'y(((((((((\'', 'rgerheeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, '2026-04-03 18:25:31', '2026-04-03 18:25:27', 13, 'info', 'rgerheeeeeeeeeeeeeeeeeeee', NULL, NULL, NULL),
(186, 6, 'y(((((((((\'', 'rgerheeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-04-03 18:25:27', 13, 'info', 'rgerheeeeeeeeeeeeeeeeeeee', NULL, NULL, NULL),
(187, 8, 'y(((((((((\'', 'rgerheeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-04-03 18:25:27', 13, 'info', 'rgerheeeeeeeeeeeeeeeeeeee', NULL, NULL, NULL),
(188, 9, 'y(((((((((\'', 'rgerheeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, '2026-04-03 18:49:36', '2026-04-03 18:25:27', 13, 'info', 'rgerheeeeeeeeeeeeeeeeeeee', NULL, NULL, NULL),
(189, 10, 'y(((((((((\'', 'rgerheeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-04-03 18:25:27', 13, 'info', 'rgerheeeeeeeeeeeeeeeeeeee', NULL, NULL, NULL),
(190, 12, 'y(((((((((\'', 'rgerheeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-04-03 18:25:27', 13, 'info', 'rgerheeeeeeeeeeeeeeeeeeee', NULL, NULL, NULL),
(191, 13, 'y(((((((((\'', 'rgerheeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-04-03 18:25:27', 13, 'info', 'rgerheeeeeeeeeeeeeeeeeeee', NULL, NULL, NULL),
(192, 4, 'Nouvelle demande support', 'Sujet: salut', 'admin/support?conv=6', 1, '2026-04-05 20:29:01', '2026-04-05 20:27:28', NULL, 'info', NULL, '2026-05-25 16:53:59', NULL, NULL),
(193, 13, 'Nouvelle demande support', 'Sujet: salut', 'admin/support?conv=6', 0, NULL, '2026-04-05 20:27:28', NULL, 'info', NULL, NULL, NULL, NULL),
(194, 5, 'Support: demande résolue', 'Votre demande précédente a été clôturée après la réponse du support.', 'support?conv=6', 1, '2026-04-05 20:32:58', '2026-04-05 20:29:40', NULL, 'info', 'Votre demande précédente a été clôturée après la réponse du support.', '2026-05-14 12:29:41', NULL, NULL),
(195, 4, 'Nouvelle demande support', 'Sujet: salut', 'admin/support?conv=7', 1, '2026-04-04 16:04:36', '2026-04-04 15:49:34', NULL, 'info', NULL, '2026-05-25 16:53:59', NULL, NULL),
(196, 13, 'Nouvelle demande support', 'Sujet: salut', 'admin/support?conv=7', 0, NULL, '2026-04-04 15:49:34', NULL, 'info', NULL, NULL, NULL, NULL),
(197, 4, 'Nouvelle demande support', 'Sujet: salut', 'admin/support?conv=8', 1, '2026-04-04 16:04:36', '2026-04-04 15:49:35', NULL, 'info', NULL, '2026-05-25 16:53:59', NULL, NULL),
(198, 13, 'Nouvelle demande support', 'Sujet: salut', 'admin/support?conv=8', 0, NULL, '2026-04-04 15:49:35', NULL, 'info', NULL, NULL, NULL, NULL),
(199, 4, 'Cours soumis', 'mike php a été soumis pour validation.', 'http://localhost/ecolenumerique/admin/courses?course_id=15', 1, '2026-04-17 21:05:09', '2026-04-17 21:04:14', NULL, 'info', NULL, '2026-05-25 16:53:59', NULL, NULL),
(200, 1, 'Cours approuvé', 'mike php a été approuvé.', '/ecolenumerique/teacher/courses', 1, NULL, '2026-04-17 21:08:51', NULL, 'info', NULL, '2026-05-14 09:44:24', NULL, NULL),
(201, 4, 'Nouvelle demande de retrait enseignant', 'Michaël ouwade a demandé un retrait de 25 F CFA.', '/ecolenumerique/admin/sales', 1, '2026-05-16 16:07:31', '2026-05-14 12:13:04', NULL, 'info', NULL, '2026-05-25 16:53:59', NULL, NULL),
(202, 13, 'Nouvelle demande de retrait enseignant', 'Michaël ouwade a demandé un retrait de 25 F CFA.', '/ecolenumerique/admin/sales', 0, NULL, '2026-05-14 12:13:04', NULL, 'info', NULL, NULL, NULL, NULL),
(203, 4, 'Nouvelle demande de retrait enseignant', 'Michaël ouwade a demandé un retrait de 25 F CFA.', '/ecolenumerique/admin/sales', 1, '2026-05-16 16:07:31', '2026-05-14 12:19:46', NULL, 'info', NULL, '2026-05-25 16:53:59', NULL, NULL),
(204, 13, 'Nouvelle demande de retrait enseignant', 'Michaël ouwade a demandé un retrait de 25 F CFA.', '/ecolenumerique/admin/sales', 0, NULL, '2026-05-14 12:19:46', NULL, 'info', NULL, NULL, NULL, NULL),
(205, 4, 'Nouvelle demande de retrait enseignant', 'Michaël ouwade a demandé un retrait de 25 F CFA.', '/ecolenumerique/admin/sales', 1, '2026-05-19 09:48:46', '2026-05-16 18:04:03', NULL, 'info', NULL, '2026-05-25 16:53:59', NULL, NULL),
(206, 13, 'Nouvelle demande de retrait enseignant', 'Michaël ouwade a demandé un retrait de 25 F CFA.', '/ecolenumerique/admin/sales', 0, NULL, '2026-05-16 18:04:03', NULL, 'info', NULL, NULL, NULL, NULL),
(207, 4, 'Nouvelle demande support', 'Sujet: salut', 'admin/support?conv=9', 0, '2026-06-09 18:45:43', '2026-05-19 09:47:23', NULL, 'info', NULL, '2026-05-25 16:53:59', NULL, NULL),
(208, 13, 'Nouvelle demande support', 'Sujet: salut', 'admin/support?conv=9', 0, NULL, '2026-05-19 09:47:23', NULL, 'info', NULL, NULL, NULL, NULL),
(209, 1, 'Support: nouveau message', 'Un agent support a répondu à votre demande.', 'support?conv=9', 0, NULL, '2026-05-19 10:18:07', NULL, 'info', NULL, '2026-05-30 12:25:21', NULL, NULL),
(210, 4, 'Nouvelle demande support', 'Sujet: rrr', 'admin/support?conv=10', 0, '2026-06-09 18:45:43', '2026-05-27 19:20:41', NULL, 'info', NULL, NULL, NULL, NULL),
(211, 13, 'Nouvelle demande support', 'Sujet: rrr', 'admin/support?conv=10', 0, NULL, '2026-05-27 19:20:41', NULL, 'info', NULL, NULL, NULL, NULL),
(212, 5, 'Support: nouveau message', 'Un agent support a répondu à votre demande.', 'support?conv=10', 1, '2026-05-29 19:27:46', '2026-05-27 20:13:45', NULL, 'info', NULL, '2026-05-27 21:08:43', NULL, NULL),
(213, 4, 'Support: nouveau message', 'Nouvelle réponse utilisateur sur une demande.', 'admin/support?conv=9', 0, '2026-06-09 18:45:43', '2026-05-27 20:47:14', NULL, 'info', NULL, NULL, NULL, NULL),
(214, 13, 'Support: nouveau message', 'Nouvelle réponse utilisateur sur une demande.', 'admin/support?conv=9', 0, NULL, '2026-05-27 20:47:14', NULL, 'info', NULL, NULL, NULL, NULL),
(215, 4, 'Support: nouveau message', 'Nouvelle réponse utilisateur sur une demande.', 'admin/support?conv=10', 0, '2026-06-09 18:45:43', '2026-05-27 21:03:38', NULL, 'info', NULL, NULL, NULL, NULL),
(216, 13, 'Support: nouveau message', 'Nouvelle réponse utilisateur sur une demande.', 'admin/support?conv=10', 0, NULL, '2026-05-27 21:03:38', NULL, 'info', NULL, NULL, NULL, NULL),
(217, 5, 'Rendez-vous annulé', 'Le rendez-vous \"mercredi\" a été annulé par le formateur.', 'http://localhost/ecolenumerique/course?slug=word', 0, NULL, '2026-05-30 11:10:52', NULL, 'info', NULL, '2026-06-05 17:17:51', NULL, NULL),
(218, 4, 'Support: nouveau message', 'Nouvelle réponse utilisateur sur une demande.', 'admin/support?conv=9', 0, '2026-06-09 18:47:57', '2026-06-09 18:47:47', NULL, 'info', NULL, NULL, NULL, NULL),
(219, 13, 'Support: nouveau message', 'Nouvelle réponse utilisateur sur une demande.', 'admin/support?conv=9', 0, NULL, '2026-06-09 18:47:47', NULL, 'info', NULL, NULL, NULL, NULL),
(220, 1, 'eeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-06-20 18:30:02', 14, 'info', 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', NULL, NULL, NULL),
(221, 5, 'eeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-06-20 18:30:02', 14, 'info', 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', NULL, NULL, NULL),
(222, 6, 'eeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-06-20 18:30:02', 14, 'info', 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', NULL, NULL, NULL),
(223, 8, 'eeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-06-20 18:30:02', 14, 'info', 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', NULL, NULL, NULL),
(224, 9, 'eeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-06-20 18:30:02', 14, 'info', 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', NULL, NULL, NULL),
(225, 10, 'eeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-06-20 18:30:02', 14, 'info', 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', NULL, NULL, NULL),
(226, 12, 'eeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-06-20 18:30:02', 14, 'info', 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', NULL, NULL, NULL),
(227, 14, 'eeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-06-20 18:30:02', 14, 'info', 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', NULL, NULL, NULL),
(228, 1, 'Je viens', 'feeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-06-22 15:29:41', 15, 'info', 'feffffffffffeeeeeeee', NULL, NULL, NULL),
(229, 5, 'Je viens', 'feeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-06-22 15:29:41', 15, 'info', 'feffffffffffeeeeeeee', NULL, NULL, NULL),
(230, 6, 'Je viens', 'feeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-06-22 15:29:41', 15, 'info', 'feffffffffffeeeeeeee', NULL, NULL, NULL),
(231, 8, 'Je viens', 'feeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-06-22 15:29:41', 15, 'info', 'feffffffffffeeeeeeee', NULL, NULL, NULL),
(232, 9, 'Je viens', 'feeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-06-22 15:29:41', 15, 'info', 'feffffffffffeeeeeeee', NULL, NULL, NULL),
(233, 10, 'Je viens', 'feeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-06-22 15:29:41', 15, 'info', 'feffffffffffeeeeeeee', NULL, NULL, NULL),
(234, 12, 'Je viens', 'feeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-06-22 15:29:41', 15, 'info', 'feffffffffffeeeeeeee', NULL, NULL, NULL),
(235, 14, 'Je viens', 'feeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 0, NULL, '2026-06-22 15:29:41', 15, 'info', 'feffffffffffeeeeeeee', NULL, NULL, NULL),
(236, 1, 'Compte enseignant suspendu', 'Votre compte enseignant a été suspendu par l’administration. Contactez le support pour plus d’informations.', '/ecolenumerique/support', 0, NULL, '2026-06-23 09:15:38', NULL, 'info', NULL, NULL, NULL, NULL),
(237, 1, 'Compte enseignant réactivé', 'Votre accès enseignant est de nouveau actif.', '/ecolenumerique/teacher/dashboard', 0, NULL, '2026-06-23 09:15:51', NULL, 'info', NULL, NULL, NULL, NULL),
(238, 14, 'Nouvelle formation entreprise', 'Une place vous a été attribuée pour \"mike php\".', 'student/my_courses', 0, NULL, '2026-06-23 10:54:29', NULL, 'info', NULL, NULL, NULL, NULL);

--
-- Déclencheurs `notifications`
--
DELIMITER $$
CREATE TRIGGER `trg_notifications_push_queue` AFTER INSERT ON `notifications` FOR EACH ROW BEGIN
    INSERT INTO push_notification_queue (
        notification_id,
        user_id,
        title,
        body,
        url,
        status,
        attempt_count,
        next_attempt_at,
        created_at,
        updated_at
    ) VALUES (
        NEW.id,
        NEW.user_id,
        NEW.title,
        NEW.body,
        NEW.url,
        'pending',
        0,
        NOW(),
        NOW(),
        NOW()
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('pending','paid','failed','canceled','refunded') NOT NULL DEFAULT 'pending',
  `total_xof` int(10) UNSIGNED NOT NULL,
  `provider` enum('fedapay','crypto','free') NOT NULL,
  `reference` varchar(120) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `status`, `total_xof`, `provider`, `reference`, `created_at`, `updated_at`) VALUES
(1, 1, 'paid', 2000, 'fedapay', 'SIM-1998C202', '2025-12-03 13:09:33', '2025-12-03 13:09:33'),
(2, 5, 'paid', 2000, 'fedapay', 'SIM-2F4B1FE5', '2025-12-05 12:24:33', '2025-12-05 12:24:33'),
(3, 5, 'paid', 2000, 'fedapay', 'SIM-DCB2B950', '2025-12-10 17:07:30', '2025-12-10 17:07:30'),
(4, 1, 'paid', 2000, 'fedapay', 'SIM-37243DF3', '2025-12-17 14:12:33', '2025-12-17 14:12:33'),
(5, 10, 'paid', 2000, 'fedapay', 'SIM-0549D8DC', '2026-01-07 16:06:32', '2026-01-07 16:06:32'),
(6, 10, 'paid', 2000, 'fedapay', 'SIM-412ACCEF', '2026-01-08 15:09:21', '2026-01-08 15:09:21'),
(7, 10, 'paid', 2000, 'fedapay', 'SIM-A9A12322', '2026-01-10 18:16:29', '2026-01-10 18:16:29'),
(8, 8, 'failed', 5000, 'fedapay', 'ORD-76AA2159', '2026-01-16 15:04:50', '2026-01-16 15:04:51'),
(9, 8, 'paid', 5000, 'fedapay', 'ORD-0E59E253', '2026-01-16 15:08:56', '2026-01-19 12:30:36'),
(10, 8, 'paid', 5000, 'fedapay', 'ORD-F3F43A20', '2026-01-16 15:16:10', '2026-01-19 12:30:35'),
(11, 8, 'paid', 5000, 'fedapay', 'ORD-806165EA', '2026-01-16 15:16:33', '2026-01-19 12:30:35'),
(12, 8, 'paid', 5000, 'fedapay', 'ORD-DF6F7DDE', '2026-01-16 15:18:09', '2026-01-19 12:30:34'),
(13, 8, 'paid', 5000, 'fedapay', 'ORD-46992453', '2026-01-16 15:19:34', '2026-01-19 12:30:33'),
(14, 8, 'paid', 2000, 'fedapay', 'ORD-C33E37EA', '2026-01-16 15:20:16', '2026-01-19 12:30:32'),
(15, 8, 'paid', 2000, 'fedapay', 'ORD-850C1DED', '2026-01-16 15:21:50', '2026-01-19 12:30:31'),
(16, 10, 'failed', 5, 'fedapay', 'ORD-6077E28F', '2026-01-16 15:24:27', '2026-01-16 15:24:28'),
(17, 10, 'failed', 5, 'fedapay', 'ORD-499F2925', '2026-01-16 15:33:35', '2026-01-16 15:33:35'),
(18, 10, 'failed', 5, 'fedapay', 'ORD-28E7249E', '2026-01-16 15:33:44', '2026-01-16 15:33:44'),
(19, 10, 'failed', 5, 'fedapay', 'ORD-7929D4FE', '2026-01-16 15:33:46', '2026-01-16 15:33:46'),
(20, 8, 'paid', 2000, 'fedapay', 'ORD-7D0BCA9E', '2026-01-16 16:34:49', '2026-01-19 12:30:30'),
(21, 10, 'failed', 5, 'fedapay', 'ORD-654AE9D2', '2026-01-16 16:34:59', '2026-01-16 16:34:59'),
(22, 10, 'paid', 5, 'crypto', 'ORD-08EC8144', '2026-01-16 17:52:17', '2026-01-19 12:30:29'),
(23, 10, 'paid', 5, 'crypto', 'ORD-67AAC9A0', '2026-01-16 17:53:29', '2026-01-19 12:30:28'),
(24, 8, 'paid', 2000, 'fedapay', 'ORD-7BBDDBF6', '2026-01-16 18:35:19', '2026-01-19 12:30:27'),
(25, 8, 'paid', 4005, 'fedapay', 'ORD-06DAFD3E', '2026-01-16 18:37:15', '2026-01-19 12:30:26'),
(26, 8, 'paid', 4000, 'fedapay', 'ORD-59FF926E', '2026-01-16 18:42:32', '2026-01-19 12:30:15'),
(27, 6, 'failed', 5, 'fedapay', 'ORD-5F247823', '2026-01-19 12:31:37', '2026-01-19 12:31:37'),
(28, 6, 'pending', 2005, 'fedapay', 'ORD-858851F2', '2026-01-19 12:32:47', '2026-01-19 12:32:47'),
(29, 9, 'pending', 2000, 'fedapay', 'ORD-3764C11B', '2026-02-11 17:38:50', '2026-02-11 17:38:50'),
(30, 5, 'pending', 3000, 'crypto', 'ORD-0BA92CA7', '2026-02-17 12:45:09', '2026-02-17 12:45:09'),
(31, 12, 'failed', 0, 'fedapay', 'ORD-DBB81AB8', '2026-02-20 18:04:41', '2026-02-20 18:04:41'),
(32, 12, 'paid', 0, 'fedapay', 'ORD-CC0A837E', '2026-02-20 18:28:26', '2026-02-20 18:28:26'),
(33, 5, 'pending', 2000, 'fedapay', 'ORD-8EB53829', '2026-03-26 17:02:48', '2026-03-26 17:02:48'),
(34, 5, 'pending', 2000, 'fedapay', 'ORD-EFDF75E0', '2026-03-26 17:12:16', '2026-03-26 17:12:16'),
(35, 5, 'pending', 2000, 'fedapay', 'ORD-2392A3E6', '2026-03-26 19:35:00', '2026-03-26 19:35:00'),
(36, 5, 'pending', 2000, 'fedapay', 'ORD-8BD63A30', '2026-03-26 19:35:58', '2026-03-26 19:35:58'),
(37, 5, 'pending', 2000, 'fedapay', 'ORD-B984C613', '2026-03-26 19:51:39', '2026-03-26 19:51:39'),
(38, 5, 'pending', 2000, 'fedapay', 'ORD-E145600E', '2026-03-26 19:58:18', '2026-03-26 19:58:18'),
(39, 5, 'pending', 2000, 'fedapay', 'ORD-CD4038E7', '2026-03-26 20:08:04', '2026-03-26 20:08:04'),
(40, 5, 'failed', 2000, 'fedapay', 'ORD-2ADD0588', '2026-06-04 17:29:30', '2026-06-04 17:29:31'),
(41, 5, 'failed', 2000, 'fedapay', 'ORD-D0A7115D', '2026-06-04 17:39:27', '2026-06-04 17:39:28'),
(42, 5, 'pending', 2000, 'fedapay', 'ORD-653FD2FA', '2026-06-04 17:44:21', '2026-06-04 17:44:21'),
(43, 5, 'pending', 2000, 'fedapay', 'ORD-473EAD97', '2026-06-10 15:50:36', '2026-06-10 15:50:36'),
(44, 5, 'pending', 2000, 'fedapay', 'ORD-64304213', '2026-06-20 19:13:06', '2026-06-20 19:13:06'),
(45, 14, 'paid', 2000, 'fedapay', 'ORD-8036A48F', '2026-06-23 10:49:13', '2026-06-23 10:49:13');

-- --------------------------------------------------------

--
-- Structure de la table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `unit_price_xof` int(10) UNSIGNED NOT NULL,
  `promo_scope` enum('none','promotion','targeted','company','coupon') NOT NULL DEFAULT 'none',
  `promotion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `coupon_code` varchar(32) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `course_id`, `company_id`, `quantity`, `unit_price_xof`, `promo_scope`, `promotion_id`, `coupon_code`, `created_at`) VALUES
(1, 1, 2, NULL, 1, 2000, 'none', NULL, NULL, '2025-12-03 13:09:33'),
(2, 2, 2, NULL, 1, 2000, 'none', NULL, NULL, '2025-12-05 12:24:33'),
(3, 3, 4, NULL, 1, 2000, 'none', NULL, NULL, '2025-12-10 17:07:30'),
(4, 4, 4, NULL, 1, 2000, 'none', NULL, NULL, '2025-12-17 14:12:33'),
(5, 5, 4, NULL, 1, 2000, 'none', NULL, NULL, '2026-01-07 16:06:33'),
(6, 6, 4, NULL, 1, 2000, 'none', NULL, NULL, '2026-01-08 15:09:21'),
(7, 7, 9, NULL, 1, 2000, 'none', NULL, NULL, '2026-01-10 18:16:29'),
(8, 8, 10, NULL, 1, 5000, 'none', NULL, NULL, '2026-01-16 15:04:50'),
(9, 9, 10, NULL, 1, 5000, 'none', NULL, NULL, '2026-01-16 15:08:56'),
(10, 10, 10, NULL, 1, 5000, 'none', NULL, NULL, '2026-01-16 15:16:10'),
(11, 11, 10, NULL, 1, 5000, 'none', NULL, NULL, '2026-01-16 15:16:33'),
(12, 12, 10, NULL, 1, 5000, 'none', NULL, NULL, '2026-01-16 15:18:09'),
(13, 13, 10, NULL, 1, 5000, 'none', NULL, NULL, '2026-01-16 15:19:34'),
(14, 14, 9, NULL, 1, 2000, 'none', NULL, NULL, '2026-01-16 15:20:16'),
(15, 15, 9, NULL, 1, 2000, 'none', NULL, NULL, '2026-01-16 15:21:50'),
(16, 16, 10, NULL, 1, 5, 'none', NULL, NULL, '2026-01-16 15:24:27'),
(17, 17, 10, NULL, 1, 5, 'none', NULL, NULL, '2026-01-16 15:33:35'),
(18, 18, 10, NULL, 1, 5, 'none', NULL, NULL, '2026-01-16 15:33:44'),
(19, 19, 10, NULL, 1, 5, 'none', NULL, NULL, '2026-01-16 15:33:46'),
(20, 20, 9, NULL, 1, 2000, 'none', NULL, NULL, '2026-01-16 16:34:49'),
(21, 21, 10, NULL, 1, 5, 'none', NULL, NULL, '2026-01-16 16:34:59'),
(22, 22, 10, NULL, 1, 5, 'none', NULL, NULL, '2026-01-16 17:52:17'),
(23, 23, 10, NULL, 1, 5, 'none', NULL, NULL, '2026-01-16 17:53:29'),
(24, 24, 9, NULL, 1, 2000, 'none', NULL, NULL, '2026-01-16 18:35:19'),
(25, 25, 4, NULL, 1, 2000, 'none', NULL, NULL, '2026-01-16 18:37:15'),
(26, 25, 9, NULL, 1, 2000, 'none', NULL, NULL, '2026-01-16 18:37:15'),
(27, 25, 10, NULL, 1, 5, 'none', NULL, NULL, '2026-01-16 18:37:15'),
(28, 26, 4, NULL, 1, 2000, 'none', NULL, NULL, '2026-01-16 18:42:32'),
(29, 26, 9, NULL, 1, 2000, 'none', NULL, NULL, '2026-01-16 18:42:32'),
(30, 27, 10, NULL, 1, 5, 'none', NULL, NULL, '2026-01-19 12:31:37'),
(31, 28, 9, NULL, 1, 2000, 'none', NULL, NULL, '2026-01-19 12:32:47'),
(32, 28, 10, NULL, 1, 5, 'none', NULL, NULL, '2026-01-19 12:32:47'),
(33, 29, 9, NULL, 1, 2000, 'none', NULL, NULL, '2026-02-11 17:38:50'),
(34, 30, 5, NULL, 1, 3000, 'none', NULL, NULL, '2026-02-17 12:45:09'),
(35, 31, 4, NULL, 1, 0, 'none', NULL, NULL, '2026-02-20 18:04:41'),
(36, 32, 4, NULL, 1, 0, 'none', NULL, NULL, '2026-02-20 18:28:26'),
(37, 33, 9, NULL, 1, 2000, 'none', NULL, NULL, '2026-03-26 17:02:48'),
(38, 34, 9, NULL, 1, 2000, 'none', NULL, NULL, '2026-03-26 17:12:16'),
(39, 35, 9, NULL, 1, 2000, 'none', NULL, NULL, '2026-03-26 19:35:00'),
(40, 36, 9, NULL, 1, 2000, 'none', NULL, NULL, '2026-03-26 19:35:58'),
(41, 37, 9, NULL, 1, 2000, 'none', NULL, NULL, '2026-03-26 19:51:39'),
(42, 38, 9, NULL, 1, 2000, 'none', NULL, NULL, '2026-03-26 19:58:18'),
(43, 39, 9, NULL, 1, 2000, 'none', NULL, NULL, '2026-03-26 20:08:04'),
(44, 40, 9, NULL, 1, 2000, 'none', NULL, NULL, '2026-06-04 17:29:30'),
(45, 41, 9, NULL, 1, 2000, 'none', NULL, NULL, '2026-06-04 17:39:27'),
(46, 42, 9, NULL, 1, 2000, 'none', NULL, NULL, '2026-06-04 17:44:21'),
(47, 43, 15, NULL, 1, 2000, 'none', NULL, NULL, '2026-06-10 15:50:36'),
(48, 44, 15, NULL, 1, 2000, 'none', NULL, NULL, '2026-06-20 19:13:06'),
(49, 45, 15, 2, 1, 2000, 'none', NULL, NULL, '2026-06-23 10:49:13');

-- --------------------------------------------------------

--
-- Structure de la table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `provider` enum('fedapay','crypto') NOT NULL,
  `reference` varchar(120) NOT NULL,
  `status` enum('initiated','pending','paid','failed','refunded') NOT NULL DEFAULT 'initiated',
  `raw_payload_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_payload_json`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `provider`, `reference`, `status`, `raw_payload_json`, `created_at`, `updated_at`) VALUES
(1, 9, 'fedapay', '108759929', 'paid', '{\"manual\":true,\"admin_id\":4}', '2026-01-16 15:08:58', '2026-01-19 12:30:36'),
(2, 10, 'fedapay', '108760097', 'paid', '{\"manual\":true,\"admin_id\":4}', '2026-01-16 15:16:12', '2026-01-19 12:30:35'),
(3, 11, 'fedapay', '108760107', 'paid', '{\"manual\":true,\"admin_id\":4}', '2026-01-16 15:16:35', '2026-01-19 12:30:35'),
(4, 12, 'fedapay', '108760137', 'paid', '{\"manual\":true,\"admin_id\":4}', '2026-01-16 15:18:11', '2026-01-19 12:30:34'),
(5, 13, 'fedapay', '108760156', 'paid', '{\"manual\":true,\"admin_id\":4}', '2026-01-16 15:19:36', '2026-01-19 12:30:33'),
(6, 14, 'fedapay', '108760170', 'paid', '{\"manual\":true,\"admin_id\":4}', '2026-01-16 15:20:17', '2026-01-19 12:30:32'),
(7, 15, 'fedapay', '108760212', 'paid', '{\"manual\":true,\"admin_id\":4}', '2026-01-16 15:21:52', '2026-01-19 12:30:31'),
(8, 20, 'fedapay', '108761787', 'paid', '{\"manual\":true,\"admin_id\":4}', '2026-01-16 16:34:50', '2026-01-19 12:30:30'),
(9, 22, 'crypto', 'CR-51869E8537', 'paid', '{\"manual\":true,\"admin_id\":4}', '2026-01-16 17:52:17', '2026-01-19 12:30:29'),
(10, 23, 'crypto', 'CR-3DAB2F64EE', 'paid', '{\"manual\":true,\"admin_id\":4}', '2026-01-16 17:53:29', '2026-01-19 12:30:28'),
(11, 24, 'fedapay', '108764604', 'paid', '{\"manual\":true,\"admin_id\":4}', '2026-01-16 18:35:21', '2026-01-19 12:30:27'),
(12, 25, 'fedapay', '108764642', 'paid', '{\"manual\":true,\"admin_id\":4}', '2026-01-16 18:37:16', '2026-01-19 12:30:26'),
(13, 26, 'fedapay', '108764774', 'paid', '{\"manual\":true,\"admin_id\":4}', '2026-01-16 18:42:33', '2026-01-19 12:30:15'),
(27, 28, 'fedapay', '108828666', 'pending', '{\"transaction\":{\"klass\":\"v1\\/transaction\",\"id\":108828666,\"reference\":\"trx_QRQ_1768822370923\",\"amount\":2005,\"description\":\"Commande ORD-858851F2\",\"callback_url\":\"http:\\/\\/localhost\\/ecolenumerique\\/payment_return.php\",\"status\":\"pending\",\"customer_id\":6302506,\"currency_id\":1,\"mode\":null,\"operation\":\"payment\",\"metadata\":{\"expire_schedule_jobid\":\"d7069be18e9c9fc8b5bf4277\"},\"commission\":null,\"fees\":null,\"fixed_commission\":0,\"amount_transferred\":null,\"created_at\":\"2026-01-19T11:32:50.923Z\",\"updated_at\":\"2026-01-19T11:32:51.496Z\",\"approved_at\":null,\"canceled_at\":null,\"declined_at\":null,\"refunded_at\":null,\"transferred_at\":null,\"deleted_at\":null,\"last_error_code\":null,\"custom_metadata\":null,\"amount_debited\":null,\"receipt_url\":null,\"payment_method_id\":null,\"sub_accounts_commissions\":null,\"transaction_key\":null,\"merchant_reference\":null,\"account_id\":53176,\"balance_id\":null,\"payment_token\":\"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjEwODgyODY2NiwiZXhwIjoxNzY4OTA4NzcxfQ.0NYSdxF8EL1VfkD5mjCHp91BWWpzq7zK2u-ZYYmb2jw\",\"payment_url\":\"https:\\/\\/process.fedapay.com\\/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjEwODgyODY2NiwiZXhwIjoxNzY4OTA4NzcxfQ.0NYSdxF8EL1VfkD5mjCHp91BWWpzq7zK2u-ZYYmb2jw\",\"flags\":[],\"to_be_transferred_at\":null}}', '2026-01-19 12:32:48', '2026-01-19 12:32:48'),
(28, 29, 'fedapay', '109467179', 'pending', '{\"transaction\":{\"klass\":\"v1\\/transaction\",\"id\":109467179,\"reference\":\"trx_F5g_1770827945162\",\"amount\":2000,\"description\":\"Commande ORD-3764C11B\",\"callback_url\":\"http:\\/\\/localhost\\/ecolenumerique\\/payment_return\",\"status\":\"pending\",\"customer_id\":6691534,\"currency_id\":1,\"mode\":null,\"operation\":\"payment\",\"metadata\":{\"expire_schedule_jobid\":\"cd789ca0e12493ebd65ad85a\"},\"commission\":null,\"fees\":null,\"fixed_commission\":0,\"amount_transferred\":null,\"created_at\":\"2026-02-11T16:39:05.162Z\",\"updated_at\":\"2026-02-11T16:39:05.792Z\",\"approved_at\":null,\"canceled_at\":null,\"declined_at\":null,\"refunded_at\":null,\"transferred_at\":null,\"deleted_at\":null,\"last_error_code\":null,\"custom_metadata\":null,\"amount_debited\":null,\"receipt_url\":null,\"payment_method_id\":null,\"sub_accounts_commissions\":null,\"transaction_key\":null,\"merchant_reference\":null,\"account_id\":53176,\"balance_id\":null,\"payment_token\":\"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjEwOTQ2NzE3OSwiZXhwIjoxNzcwOTE0MzQ2fQ.-2FCO_B7EDHqvLkOgM856OTjBUXC9elQxDOsd9jL4Sg\",\"payment_url\":\"https:\\/\\/process.fedapay.com\\/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjEwOTQ2NzE3OSwiZXhwIjoxNzcwOTE0MzQ2fQ.-2FCO_B7EDHqvLkOgM856OTjBUXC9elQxDOsd9jL4Sg\",\"flags\":[],\"to_be_transferred_at\":null}}', '2026-02-11 17:38:52', '2026-02-11 17:38:52'),
(29, 30, 'crypto', 'CR-6277DFC078', 'pending', '{\"address\":\"TSqw89oJ2nXtxbJ6LVwKVkFBc7efnMvM2Q\",\"network\":\"TRC20\",\"rate_xof\":600}', '2026-02-17 12:45:09', '2026-02-17 12:45:09'),
(30, 33, 'fedapay', '110201304', 'pending', '{\"transaction\":{\"klass\":\"v1\\/transaction\",\"id\":110201304,\"reference\":\"trx_eZI_1774540972814\",\"amount\":2000,\"description\":\"Commande ORD-8EB53829\",\"callback_url\":\"http:\\/\\/localhost\\/ecolenumerique\\/payment_return\",\"status\":\"pending\",\"customer_id\":3423169,\"currency_id\":1,\"mode\":null,\"operation\":\"payment\",\"metadata\":{\"expire_schedule_jobid\":\"f3f447338c5b35718ab2ca3c\"},\"commission\":null,\"fees\":null,\"fixed_commission\":0,\"amount_transferred\":null,\"created_at\":\"2026-03-26T16:02:52.814Z\",\"updated_at\":\"2026-03-26T16:02:53.450Z\",\"approved_at\":null,\"canceled_at\":null,\"declined_at\":null,\"refunded_at\":null,\"transferred_at\":null,\"deleted_at\":null,\"last_error_code\":null,\"custom_metadata\":null,\"amount_debited\":null,\"receipt_url\":null,\"payment_method_id\":null,\"sub_accounts_commissions\":null,\"transaction_key\":null,\"merchant_reference\":null,\"account_id\":53176,\"balance_id\":null,\"payment_token\":\"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjExMDIwMTMwNCwiZXhwIjoxNzc0NjI3MzczfQ.cnWfItchUphuKr6fmTeeU8-SoWuHuVsXtEO6FKPFwO8\",\"payment_url\":\"https:\\/\\/process.fedapay.com\\/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjExMDIwMTMwNCwiZXhwIjoxNzc0NjI3MzczfQ.cnWfItchUphuKr6fmTeeU8-SoWuHuVsXtEO6FKPFwO8\",\"flags\":[],\"to_be_transferred_at\":null}}', '2026-03-26 17:02:50', '2026-03-26 17:02:50'),
(31, 34, 'fedapay', '110201473', 'pending', '{\"transaction\":{\"klass\":\"v1\\/transaction\",\"id\":110201473,\"reference\":\"trx_veE_1774541541321\",\"amount\":2000,\"description\":\"Commande ORD-EFDF75E0\",\"callback_url\":\"http:\\/\\/localhost\\/ecolenumerique\\/payment_return\",\"status\":\"pending\",\"customer_id\":3423169,\"currency_id\":1,\"mode\":null,\"operation\":\"payment\",\"metadata\":{\"expire_schedule_jobid\":\"d0ea03f65046e9e99a6a09fb\"},\"commission\":null,\"fees\":null,\"fixed_commission\":0,\"amount_transferred\":null,\"created_at\":\"2026-03-26T16:12:21.321Z\",\"updated_at\":\"2026-03-26T16:12:21.888Z\",\"approved_at\":null,\"canceled_at\":null,\"declined_at\":null,\"refunded_at\":null,\"transferred_at\":null,\"deleted_at\":null,\"last_error_code\":null,\"custom_metadata\":null,\"amount_debited\":null,\"receipt_url\":null,\"payment_method_id\":null,\"sub_accounts_commissions\":null,\"transaction_key\":null,\"merchant_reference\":null,\"account_id\":53176,\"balance_id\":null,\"payment_token\":\"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjExMDIwMTQ3MywiZXhwIjoxNzc0NjI3OTQyfQ.Hoq3Kffl_yguW07S1IYvNKX1N1_a8ATNmKXx-8iQVzo\",\"payment_url\":\"https:\\/\\/process.fedapay.com\\/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjExMDIwMTQ3MywiZXhwIjoxNzc0NjI3OTQyfQ.Hoq3Kffl_yguW07S1IYvNKX1N1_a8ATNmKXx-8iQVzo\",\"flags\":[],\"to_be_transferred_at\":null}}', '2026-03-26 17:12:18', '2026-03-26 17:12:18'),
(32, 35, 'fedapay', '110204748', 'pending', '{\"transaction\":{\"klass\":\"v1\\/transaction\",\"id\":110204748,\"reference\":\"trx_tew_1774550104947\",\"amount\":2000,\"description\":\"Commande ORD-2392A3E6\",\"callback_url\":\"http:\\/\\/localhost\\/ecolenumerique\\/payment_return\",\"status\":\"pending\",\"customer_id\":3423169,\"currency_id\":1,\"mode\":null,\"operation\":\"payment\",\"metadata\":{\"expire_schedule_jobid\":\"8f5e35f7ba9d4f2f14193439\"},\"commission\":null,\"fees\":null,\"fixed_commission\":0,\"amount_transferred\":null,\"created_at\":\"2026-03-26T18:35:04.947Z\",\"updated_at\":\"2026-03-26T18:35:05.575Z\",\"approved_at\":null,\"canceled_at\":null,\"declined_at\":null,\"refunded_at\":null,\"transferred_at\":null,\"deleted_at\":null,\"last_error_code\":null,\"custom_metadata\":null,\"amount_debited\":null,\"receipt_url\":null,\"payment_method_id\":null,\"sub_accounts_commissions\":null,\"transaction_key\":null,\"merchant_reference\":null,\"account_id\":53176,\"balance_id\":null,\"payment_token\":\"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjExMDIwNDc0OCwiZXhwIjoxNzc0NjM2NTA1fQ.aujbuQSqm3ciIETH7vymMJS_4CFM7Jz_fB1sOHNkIcQ\",\"payment_url\":\"https:\\/\\/process.fedapay.com\\/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjExMDIwNDc0OCwiZXhwIjoxNzc0NjM2NTA1fQ.aujbuQSqm3ciIETH7vymMJS_4CFM7Jz_fB1sOHNkIcQ\",\"flags\":[],\"to_be_transferred_at\":null}}', '2026-03-26 19:35:02', '2026-03-26 19:35:02'),
(33, 36, 'fedapay', '110204766', 'pending', '{\"transaction\":{\"klass\":\"v1\\/transaction\",\"id\":110204766,\"reference\":\"trx_auA_1774550162973\",\"amount\":2000,\"description\":\"Commande ORD-8BD63A30\",\"callback_url\":\"http:\\/\\/localhost\\/ecolenumerique\\/payment_return\",\"status\":\"pending\",\"customer_id\":3423169,\"currency_id\":1,\"mode\":null,\"operation\":\"payment\",\"metadata\":{\"expire_schedule_jobid\":\"fb33cdea3a4f185d48180e88\"},\"commission\":null,\"fees\":null,\"fixed_commission\":0,\"amount_transferred\":null,\"created_at\":\"2026-03-26T18:36:02.973Z\",\"updated_at\":\"2026-03-26T18:36:03.578Z\",\"approved_at\":null,\"canceled_at\":null,\"declined_at\":null,\"refunded_at\":null,\"transferred_at\":null,\"deleted_at\":null,\"last_error_code\":null,\"custom_metadata\":null,\"amount_debited\":null,\"receipt_url\":null,\"payment_method_id\":null,\"sub_accounts_commissions\":null,\"transaction_key\":null,\"merchant_reference\":null,\"account_id\":53176,\"balance_id\":null,\"payment_token\":\"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjExMDIwNDc2NiwiZXhwIjoxNzc0NjM2NTYzfQ.pSw4B4z2_lK1SMzOS1hZMk78cihsdd8PkoyAi3JkHI8\",\"payment_url\":\"https:\\/\\/process.fedapay.com\\/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjExMDIwNDc2NiwiZXhwIjoxNzc0NjM2NTYzfQ.pSw4B4z2_lK1SMzOS1hZMk78cihsdd8PkoyAi3JkHI8\",\"flags\":[],\"to_be_transferred_at\":null}}', '2026-03-26 19:36:00', '2026-03-26 19:36:00'),
(34, 37, 'fedapay', '110205030', 'pending', '{\"transaction\":{\"klass\":\"v1\\/transaction\",\"id\":110205030,\"reference\":\"trx_QXU_1774551104790\",\"amount\":2000,\"description\":\"Commande ORD-B984C613\",\"callback_url\":\"http:\\/\\/localhost\\/ecolenumerique\\/payment_return\",\"status\":\"pending\",\"customer_id\":3423169,\"currency_id\":1,\"mode\":null,\"operation\":\"payment\",\"metadata\":{\"expire_schedule_jobid\":\"179e8921083d80abf00ace30\"},\"commission\":null,\"fees\":null,\"fixed_commission\":0,\"amount_transferred\":null,\"created_at\":\"2026-03-26T18:51:44.790Z\",\"updated_at\":\"2026-03-26T18:51:45.376Z\",\"approved_at\":null,\"canceled_at\":null,\"declined_at\":null,\"refunded_at\":null,\"transferred_at\":null,\"deleted_at\":null,\"last_error_code\":null,\"custom_metadata\":null,\"amount_debited\":null,\"receipt_url\":null,\"payment_method_id\":null,\"sub_accounts_commissions\":null,\"transaction_key\":null,\"merchant_reference\":null,\"account_id\":53176,\"balance_id\":null,\"payment_token\":\"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjExMDIwNTAzMCwiZXhwIjoxNzc0NjM3NTA1fQ.lc-Drpwyeb6GfjMEb91qd4clH_kxrworxuTX4c96Zv4\",\"payment_url\":\"https:\\/\\/process.fedapay.com\\/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjExMDIwNTAzMCwiZXhwIjoxNzc0NjM3NTA1fQ.lc-Drpwyeb6GfjMEb91qd4clH_kxrworxuTX4c96Zv4\",\"flags\":[],\"to_be_transferred_at\":null}}', '2026-03-26 19:51:41', '2026-03-26 19:51:41'),
(35, 38, 'fedapay', '110205132', 'pending', '{\"transaction\":{\"klass\":\"v1\\/transaction\",\"id\":110205132,\"reference\":\"trx_eVc_1774551505525\",\"amount\":2000,\"description\":\"Commande ORD-E145600E\",\"callback_url\":\"http:\\/\\/localhost\\/ecolenumerique\\/payment_return\",\"status\":\"pending\",\"customer_id\":3423169,\"currency_id\":1,\"mode\":null,\"operation\":\"payment\",\"metadata\":{\"expire_schedule_jobid\":\"4a747c513aebfa9308e1ebe4\"},\"commission\":null,\"fees\":null,\"fixed_commission\":0,\"amount_transferred\":null,\"created_at\":\"2026-03-26T18:58:25.525Z\",\"updated_at\":\"2026-03-26T18:58:26.126Z\",\"approved_at\":null,\"canceled_at\":null,\"declined_at\":null,\"refunded_at\":null,\"transferred_at\":null,\"deleted_at\":null,\"last_error_code\":null,\"custom_metadata\":null,\"amount_debited\":null,\"receipt_url\":null,\"payment_method_id\":null,\"sub_accounts_commissions\":null,\"transaction_key\":null,\"merchant_reference\":null,\"account_id\":53176,\"balance_id\":null,\"payment_token\":\"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjExMDIwNTEzMiwiZXhwIjoxNzc0NjM3OTA2fQ.flTA7kcgwQcTnsklWxtNgsLtaMDrfbhvWDuHKrqvkNk\",\"payment_url\":\"https:\\/\\/process.fedapay.com\\/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjExMDIwNTEzMiwiZXhwIjoxNzc0NjM3OTA2fQ.flTA7kcgwQcTnsklWxtNgsLtaMDrfbhvWDuHKrqvkNk\",\"flags\":[],\"to_be_transferred_at\":null}}', '2026-03-26 19:58:22', '2026-03-26 19:58:22'),
(36, 39, 'fedapay', '110205309', 'pending', '{\"transaction\":{\"klass\":\"v1\\/transaction\",\"id\":110205309,\"reference\":\"trx_pMo_1774552089126\",\"amount\":2000,\"description\":\"Commande ORD-CD4038E7\",\"callback_url\":\"http:\\/\\/localhost\\/ecolenumerique\\/payment_return\",\"status\":\"pending\",\"customer_id\":3423169,\"currency_id\":1,\"mode\":null,\"operation\":\"payment\",\"metadata\":{\"expire_schedule_jobid\":\"c0db8808f6cceaa6b7e7db94\"},\"commission\":null,\"fees\":null,\"fixed_commission\":0,\"amount_transferred\":null,\"created_at\":\"2026-03-26T19:08:09.126Z\",\"updated_at\":\"2026-03-26T19:08:09.697Z\",\"approved_at\":null,\"canceled_at\":null,\"declined_at\":null,\"refunded_at\":null,\"transferred_at\":null,\"deleted_at\":null,\"last_error_code\":null,\"custom_metadata\":null,\"amount_debited\":null,\"receipt_url\":null,\"payment_method_id\":null,\"sub_accounts_commissions\":null,\"transaction_key\":null,\"merchant_reference\":null,\"account_id\":53176,\"balance_id\":null,\"payment_token\":\"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjExMDIwNTMwOSwiZXhwIjoxNzc0NjM4NDkwfQ.E7X9CC_Ql2SoPKlAl4rZf80rWJKJ-5GQZbwjijFEgP0\",\"payment_url\":\"https:\\/\\/process.fedapay.com\\/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjExMDIwNTMwOSwiZXhwIjoxNzc0NjM4NDkwfQ.E7X9CC_Ql2SoPKlAl4rZf80rWJKJ-5GQZbwjijFEgP0\",\"flags\":[],\"to_be_transferred_at\":null}}', '2026-03-26 20:08:06', '2026-03-26 20:08:06'),
(37, 42, 'fedapay', '111303519', 'pending', '{\"transaction\":{\"klass\":\"v1\\/transaction\",\"id\":111303519,\"reference\":\"trx_2Nk_1780591462531\",\"amount\":2000,\"description\":\"Commande ORD-653FD2FA\",\"callback_url\":\"http:\\/\\/localhost\\/ecolenumerique\\/payment_return\",\"status\":\"pending\",\"customer_id\":3423169,\"currency_id\":1,\"mode\":null,\"operation\":\"payment\",\"metadata\":{\"expire_schedule_jobid\":\"5bb3f17cc19b4a51d4088180\"},\"commission\":null,\"fees\":null,\"fixed_commission\":0,\"amount_transferred\":null,\"created_at\":\"2026-06-04T16:44:22.531Z\",\"updated_at\":\"2026-06-04T16:44:23.140Z\",\"approved_at\":null,\"canceled_at\":null,\"declined_at\":null,\"refunded_at\":null,\"transferred_at\":null,\"deleted_at\":null,\"last_error_code\":null,\"custom_metadata\":null,\"amount_debited\":null,\"receipt_url\":null,\"payment_method_id\":null,\"sub_accounts_commissions\":null,\"transaction_key\":null,\"merchant_reference\":null,\"account_id\":53176,\"balance_id\":null,\"payment_token\":\"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjExMTMwMzUxOSwiZXhwIjoxNzgwNjc3ODYzfQ.xeH1YQI8wXipGUxlls3Y9gwzXfrhlbiWL45HlxH0tQc\",\"payment_url\":\"https:\\/\\/process.fedapay.com\\/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjExMTMwMzUxOSwiZXhwIjoxNzgwNjc3ODYzfQ.xeH1YQI8wXipGUxlls3Y9gwzXfrhlbiWL45HlxH0tQc\",\"flags\":[],\"to_be_transferred_at\":null}}', '2026-06-04 17:44:23', '2026-06-04 17:44:23'),
(38, 43, 'fedapay', '111393510', 'pending', '{\"transaction\":{\"klass\":\"v1\\/transaction\",\"id\":111393510,\"reference\":\"trx_FJo_1781103037039\",\"amount\":2000,\"description\":\"Commande ORD-473EAD97\",\"callback_url\":\"http:\\/\\/localhost\\/ecolenumerique\\/payment_return\",\"status\":\"pending\",\"customer_id\":3423169,\"currency_id\":1,\"mode\":null,\"operation\":\"payment\",\"metadata\":{\"expire_schedule_jobid\":\"b0d34cbad220773164524674\"},\"commission\":null,\"fees\":null,\"fixed_commission\":0,\"amount_transferred\":null,\"created_at\":\"2026-06-10T14:50:37.039Z\",\"updated_at\":\"2026-06-10T14:50:37.612Z\",\"approved_at\":null,\"canceled_at\":null,\"declined_at\":null,\"refunded_at\":null,\"transferred_at\":null,\"deleted_at\":null,\"last_error_code\":null,\"custom_metadata\":null,\"amount_debited\":null,\"receipt_url\":null,\"payment_method_id\":null,\"sub_accounts_commissions\":null,\"transaction_key\":null,\"merchant_reference\":null,\"account_id\":53176,\"balance_id\":null,\"payment_token\":\"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjExMTM5MzUxMCwiZXhwIjoxNzgxMTg5NDM3fQ.V48OW0ZOsCHmAGo-lWCWfaYuLVgp7KvEWaTDiqOPxAY\",\"payment_url\":\"https:\\/\\/process.fedapay.com\\/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjExMTM5MzUxMCwiZXhwIjoxNzgxMTg5NDM3fQ.V48OW0ZOsCHmAGo-lWCWfaYuLVgp7KvEWaTDiqOPxAY\",\"flags\":[],\"to_be_transferred_at\":null}}', '2026-06-10 15:50:38', '2026-06-10 15:50:38'),
(39, 44, 'fedapay', '111537592', 'pending', '{\"transaction\":{\"klass\":\"v1\\/transaction\",\"id\":111537592,\"reference\":\"trx_VHY_1781979180239\",\"amount\":2000,\"description\":\"Commande ORD-64304213\",\"callback_url\":\"http:\\/\\/localhost\\/ecolenumerique\\/payment_return\",\"status\":\"pending\",\"customer_id\":3423169,\"currency_id\":1,\"mode\":null,\"operation\":\"payment\",\"metadata\":{\"expire_schedule_jobid\":\"7216e9faae25e12761d05e17\"},\"commission\":null,\"fees\":null,\"fixed_commission\":0,\"amount_transferred\":null,\"created_at\":\"2026-06-20T18:13:00.239Z\",\"updated_at\":\"2026-06-20T18:13:00.813Z\",\"approved_at\":null,\"canceled_at\":null,\"declined_at\":null,\"refunded_at\":null,\"transferred_at\":null,\"deleted_at\":null,\"last_error_code\":null,\"custom_metadata\":null,\"amount_debited\":null,\"receipt_url\":null,\"payment_method_id\":null,\"sub_accounts_commissions\":null,\"transaction_key\":null,\"merchant_reference\":null,\"account_id\":53176,\"balance_id\":null,\"payment_token\":\"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjExMTUzNzU5MiwiZXhwIjoxNzgyMDY1NTgxfQ.hckh449v1HZDVr2s_sYh-tMhVRk-MRSHwqqngJ-JJ34\",\"payment_url\":\"https:\\/\\/process.fedapay.com\\/eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjExMTUzNzU5MiwiZXhwIjoxNzgyMDY1NTgxfQ.hckh449v1HZDVr2s_sYh-tMhVRk-MRSHwqqngJ-JJ34\",\"flags\":[],\"to_be_transferred_at\":null}}', '2026-06-20 19:13:08', '2026-06-20 19:13:08'),
(40, 45, 'fedapay', 'LOCAL-ENT-ORD-8036A48F', 'paid', '{\"manual\":true,\"company_id\":2,\"course_id\":15,\"note\":\"purchase executed by assistant\"}', '2026-06-23 10:49:13', '2026-06-23 10:49:13');

-- --------------------------------------------------------

--
-- Structure de la table `payment_webhook_events`
--

CREATE TABLE `payment_webhook_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `provider` varchar(32) NOT NULL,
  `event_key` char(64) NOT NULL,
  `event_name` varchar(120) NOT NULL DEFAULT '',
  `transaction_reference` varchar(120) DEFAULT NULL,
  `payload_json` longtext DEFAULT NULL,
  `processed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `platform_settings`
--

CREATE TABLE `platform_settings` (
  `setting_key` varchar(80) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `platform_settings`
--

INSERT INTO `platform_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('commission_percent', '20', '2026-01-07 14:22:56'),
('maintenance_enabled', '0', '2026-07-04 11:33:29'),
('maintenance_message', 'Maintenance temporaire en cours. Merci de revenir dans quelques minutes.', '2026-07-04 11:33:29'),
('min_withdrawal_xof', '0', '2026-01-07 14:22:56'),
('payment_fedapay_enabled', '1', '2026-07-04 11:33:29'),
('payment_fedapay_environment', 'live', '2026-07-04 11:33:29'),
('payment_fedapay_public_key', 'pk_live_PgawXDIxhMf14ZtZJnbx5oWx', '2026-07-04 11:33:29'),
('payment_fedapay_secret_key', 'sk_live_3TlwZVfDRrf5Ct-AOx4LFjCp', '2026-07-04 11:33:29'),
('payment_fedapay_webhook_secret', 'wh_live_tIhIk-E0HdVEJYlts0FnDZ-6', '2026-07-04 11:33:29'),
('platform_dg_name', 'Ouwade', '2026-07-04 11:33:29'),
('platform_dg_signature_path', 'storage/uploads/site/dg_signature_20260704_113329_206608a3.png', '2026-07-04 11:33:29'),
('referral_l1_rate', '5.00', '2026-03-24 19:50:38'),
('referral_l2_rate', '2.00', '2026-03-24 19:50:38'),
('referral_min_withdrawal_xof', '0', '2026-03-09 17:24:53'),
('registrations_enabled', '1', '2026-07-04 11:33:29'),
('seo_description', '', '2026-07-04 11:33:29'),
('seo_keywords', '', '2026-07-04 11:33:29'),
('seo_title', 'École Numérique', '2026-07-04 11:33:29'),
('site_address', '', '2026-07-04 11:33:29'),
('site_description', 'rrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrr', '2026-07-04 11:33:29'),
('site_footer_text', '222222222222222222222222222222222222222222', '2026-07-04 11:33:29'),
('site_logo_path', 'storage/uploads/site/site_logo_20260107_152519_5e191c04.jpg', '2026-01-07 14:25:19'),
('site_name', 'École Numérique', '2026-07-04 11:33:29'),
('site_tagline', 'Formations tech par des experts.', '2026-07-04 11:33:29'),
('support_email', 'no-reply@ecolenumerique.test', '2026-07-04 11:33:29'),
('support_phone', '5055555555', '2026-07-04 11:33:29'),
('support_whatsapp', '55555555555555555555', '2026-07-04 11:33:29'),
('teacher_applications_enabled', '1', '2026-07-04 11:33:29'),
('teacher_min_withdrawal_xof', '0', '2026-03-09 17:24:53'),
('teacher_withdrawal_methods', 'fedapay', '2026-06-04 16:34:34');

-- --------------------------------------------------------

--
-- Structure de la table `push_notification_queue`
--

CREATE TABLE `push_notification_queue` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `notification_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(190) NOT NULL,
  `body` text DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `status` enum('pending','processing','sent','failed') NOT NULL DEFAULT 'pending',
  `attempt_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_error` varchar(255) DEFAULT NULL,
  `next_attempt_at` datetime NOT NULL DEFAULT current_timestamp(),
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `push_notification_queue`
--

INSERT INTO `push_notification_queue` (`id`, `notification_id`, `user_id`, `title`, `body`, `url`, `status`, `attempt_count`, `last_error`, `next_attempt_at`, `sent_at`, `created_at`, `updated_at`) VALUES
(1, 42, 4, 'Cours soumis', 'Excel a ete soumis pour validation.', 'http://localhost/ecolenumerique/admin/courses?course_id=5', 'sent', 0, NULL, '2026-02-17 12:12:21', '2026-06-09 18:42:17', '2026-02-17 12:12:21', '2026-06-09 18:42:17'),
(2, 43, 1, '[TEST PUSH 20260217124014] Etudiant', 'Test popup+push genere le 2026-02-17 12:40:14 (role: student).', '/student/dashboard', 'sent', 0, NULL, '2026-02-17 12:40:14', '2026-06-09 18:42:17', '2026-02-17 12:40:14', '2026-06-09 18:42:17'),
(3, 44, 8, '[TEST PUSH 20260217124014] Enseignant', 'Test popup+push genere le 2026-02-17 12:40:14 (role: teacher).', '/teacher/dashboard', 'sent', 0, NULL, '2026-02-17 12:40:14', '2026-06-09 18:42:17', '2026-02-17 12:40:14', '2026-06-09 18:42:17'),
(4, 45, 4, '[TEST PUSH 20260217124014] Admin', 'Test popup+push genere le 2026-02-17 12:40:14 (role: admin).', '/admin/dashboard', 'sent', 0, NULL, '2026-02-17 12:40:14', '2026-06-09 18:42:17', '2026-02-17 12:40:14', '2026-06-09 18:42:17'),
(5, 46, 12, '[TEST PUSH 20260217124014] Entreprise', 'Test popup+push genere le 2026-02-17 12:40:14 (role: company_admin).', '/company/dashboard', 'sent', 0, NULL, '2026-02-17 12:40:14', '2026-06-09 18:42:17', '2026-02-17 12:40:14', '2026-06-09 18:42:17'),
(6, 47, 1, 'Cours approuve', 'Excel a ete approuve.', '/ecolenumerique/teacher/courses', 'sent', 0, NULL, '2026-02-17 12:42:25', '2026-06-09 18:42:17', '2026-02-17 12:42:25', '2026-06-09 18:42:17'),
(7, 48, 1, 'Nouvel avis', 'Michaël a note \"word\" : 3/5', '/ecolenumerique/course?id=4', 'sent', 0, NULL, '2026-02-17 14:40:18', '2026-06-09 18:42:17', '2026-02-17 14:40:18', '2026-06-09 18:42:17'),
(8, 49, 4, 'Nouvelle demande support', 'Sujet: nogrnogrgrrrrrr', '/ecolenumerique/admin/support?conv=3', 'sent', 0, NULL, '2026-02-19 15:09:32', '2026-06-09 18:42:17', '2026-02-19 15:09:32', '2026-06-09 18:42:17'),
(9, 50, 6, 'Nouvelle demande support', 'Sujet: nogrnogrgrrrrrr', '/ecolenumerique/admin/support?conv=3', 'sent', 0, NULL, '2026-02-19 15:09:32', '2026-06-09 18:42:17', '2026-02-19 15:09:32', '2026-06-09 18:42:17'),
(10, 51, 13, 'Nouvelle demande support', 'Sujet: nogrnogrgrrrrrr', '/ecolenumerique/admin/support?conv=3', 'sent', 0, NULL, '2026-02-19 15:09:32', '2026-06-09 18:42:17', '2026-02-19 15:09:32', '2026-06-09 18:42:17'),
(11, 52, 4, 'Nouvelle demande support', 'Sujet: eeevevevevee', '/ecolenumerique/admin/support?conv=4', 'sent', 0, NULL, '2026-02-19 15:26:46', '2026-06-09 18:42:17', '2026-02-19 15:26:46', '2026-06-09 18:42:17'),
(12, 53, 6, 'Nouvelle demande support', 'Sujet: eeevevevevee', '/ecolenumerique/admin/support?conv=4', 'sent', 0, NULL, '2026-02-19 15:26:46', '2026-06-09 18:42:17', '2026-02-19 15:26:46', '2026-06-09 18:42:17'),
(13, 54, 13, 'Nouvelle demande support', 'Sujet: eeevevevevee', '/ecolenumerique/admin/support?conv=4', 'sent', 0, NULL, '2026-02-19 15:26:46', '2026-06-09 18:42:17', '2026-02-19 15:26:46', '2026-06-09 18:42:17'),
(14, 55, 12, 'Support: nouveau message', 'Un agent support a repondu a votre demande.', '/ecolenumerique/support?conv=4', 'sent', 0, NULL, '2026-02-19 15:27:21', '2026-06-09 18:42:17', '2026-02-19 15:27:21', '2026-06-09 18:42:17'),
(15, 56, 12, 'Support: nouveau message', 'Un agent support a repondu a votre demande.', '/ecolenumerique/support?conv=4', 'sent', 0, NULL, '2026-02-19 15:47:48', '2026-06-09 18:42:17', '2026-02-19 15:47:48', '2026-06-09 18:42:17'),
(16, 57, 4, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', '/ecolenumerique/admin/support?conv=4', 'sent', 0, NULL, '2026-02-19 15:48:37', '2026-06-09 18:42:18', '2026-02-19 15:48:37', '2026-06-09 18:42:18'),
(17, 58, 6, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', '/ecolenumerique/admin/support?conv=4', 'sent', 0, NULL, '2026-02-19 15:48:37', '2026-06-09 18:42:18', '2026-02-19 15:48:37', '2026-06-09 18:42:18'),
(18, 59, 13, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', '/ecolenumerique/admin/support?conv=4', 'sent', 0, NULL, '2026-02-19 15:48:37', '2026-06-09 18:42:18', '2026-02-19 15:48:37', '2026-06-09 18:42:18'),
(19, 60, 4, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', 'admin/support?conv=4', 'sent', 0, NULL, '2026-02-19 15:57:14', '2026-06-09 18:42:18', '2026-02-19 15:57:14', '2026-06-09 18:42:18'),
(20, 61, 6, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', 'admin/support?conv=4', 'sent', 0, NULL, '2026-02-19 15:57:14', '2026-06-09 18:42:18', '2026-02-19 15:57:14', '2026-06-09 18:42:18'),
(21, 62, 13, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', 'admin/support?conv=4', 'sent', 0, NULL, '2026-02-19 15:57:14', '2026-06-09 18:42:18', '2026-02-19 15:57:14', '2026-06-09 18:42:18'),
(22, 63, 12, 'Support: nouveau message', 'Un agent support a repondu a votre demande.', 'support?conv=4', 'sent', 0, NULL, '2026-02-19 15:57:37', '2026-06-09 18:42:18', '2026-02-19 15:57:37', '2026-06-09 18:42:18'),
(23, 64, 12, 'Support: nouveau message', 'Un agent support a repondu a votre demande.', 'support?conv=4', 'sent', 0, NULL, '2026-02-19 15:58:44', '2026-06-09 18:42:18', '2026-02-19 15:58:44', '2026-06-09 18:42:18'),
(24, 65, 4, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', 'admin/support?conv=4', 'sent', 0, NULL, '2026-02-19 15:59:14', '2026-06-09 18:42:18', '2026-02-19 15:59:14', '2026-06-09 18:42:18'),
(25, 66, 6, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', 'admin/support?conv=4', 'sent', 0, NULL, '2026-02-19 15:59:14', '2026-06-09 18:42:18', '2026-02-19 15:59:14', '2026-06-09 18:42:18'),
(26, 67, 13, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', 'admin/support?conv=4', 'sent', 0, NULL, '2026-02-19 15:59:14', '2026-06-09 18:42:18', '2026-02-19 15:59:14', '2026-06-09 18:42:18'),
(27, 68, 12, 'Support: nouveau message', 'Un agent support a repondu a votre demande.', 'support?conv=4', 'sent', 0, NULL, '2026-02-19 16:09:47', '2026-06-09 18:42:18', '2026-02-19 16:09:47', '2026-06-09 18:42:18'),
(28, 69, 4, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', 'admin/support?conv=4', 'sent', 0, NULL, '2026-02-19 16:18:29', '2026-06-09 18:42:18', '2026-02-19 16:18:29', '2026-06-09 18:42:18'),
(29, 70, 6, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', 'admin/support?conv=4', 'sent', 0, NULL, '2026-02-19 16:18:29', '2026-06-09 18:42:18', '2026-02-19 16:18:29', '2026-06-09 18:42:18'),
(30, 71, 13, 'Support: nouveau message', 'Nouvelle reponse utilisateur sur une demande.', 'admin/support?conv=4', 'sent', 0, NULL, '2026-02-19 16:18:29', '2026-06-09 18:42:18', '2026-02-19 16:18:29', '2026-06-09 18:42:18'),
(31, 72, 12, 'Support: nouveau message', 'Un agent support a repondu a votre demande.', 'support?conv=4', 'sent', 0, NULL, '2026-02-19 16:42:23', '2026-06-09 18:45:43', '2026-02-19 16:42:23', '2026-06-09 18:45:43'),
(32, 73, 12, 'Support: nouveau message', 'Un agent support a repondu a votre demande.', 'support?conv=4', 'sent', 0, NULL, '2026-02-19 16:43:14', '2026-06-09 18:45:43', '2026-02-19 16:43:14', '2026-06-09 18:45:43'),
(33, 74, 1, 'Annonce: jhvvjkvvkvkkv', 'ooooobjbllblbjlblblblbllj', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-02-23 11:21:40', '2026-06-09 18:45:43', '2026-02-23 11:21:40', '2026-06-09 18:45:43'),
(34, 75, 4, 'Annonce: jhvvjkvvkvkkv', 'ooooobjbllblbjlblblblbllj', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-02-23 11:21:40', '2026-06-09 18:45:43', '2026-02-23 11:21:40', '2026-06-09 18:45:43'),
(35, 76, 5, 'Annonce: jhvvjkvvkvkkv', 'ooooobjbllblbjlblblblbllj', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-02-23 11:21:40', '2026-06-09 18:45:43', '2026-02-23 11:21:40', '2026-06-09 18:45:43'),
(36, 77, 6, 'Annonce: jhvvjkvvkvkkv', 'ooooobjbllblbjlblblblbllj', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-02-23 11:21:40', '2026-06-09 18:45:43', '2026-02-23 11:21:40', '2026-06-09 18:45:43'),
(37, 78, 8, 'Annonce: jhvvjkvvkvkkv', 'ooooobjbllblbjlblblblbllj', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-02-23 11:21:40', '2026-06-09 18:45:43', '2026-02-23 11:21:40', '2026-06-09 18:45:43'),
(38, 79, 9, 'Annonce: jhvvjkvvkvkkv', 'ooooobjbllblbjlblblblbllj', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-02-23 11:21:40', '2026-06-09 18:45:43', '2026-02-23 11:21:40', '2026-06-09 18:45:43'),
(39, 80, 10, 'Annonce: jhvvjkvvkvkkv', 'ooooobjbllblbjlblblblbllj', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-02-23 11:21:40', '2026-06-09 18:45:43', '2026-02-23 11:21:40', '2026-06-09 18:45:43'),
(40, 81, 12, 'Annonce: jhvvjkvvkvkkv', 'ooooobjbllblbjlblblblbllj', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-02-23 11:21:40', '2026-06-09 18:45:43', '2026-02-23 11:21:40', '2026-06-09 18:45:43'),
(41, 82, 13, 'Annonce: jhvvjkvvkvkkv', 'ooooobjbllblbjlblblblbllj', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-02-23 11:21:40', '2026-06-09 18:45:43', '2026-02-23 11:21:40', '2026-06-09 18:45:43'),
(42, 83, 8, 'Invitation entreprise', 'Vous avez ete invite a rejoindre \"michael\".', 'http://localhost/ecolenumerique/company/invite_accept?token=9ba5666e70de3170a8fed14f4c8e0b1d85e564f973275f851de46ba3bc3b207c', 'sent', 0, NULL, '2026-03-09 16:05:25', '2026-06-09 18:45:43', '2026-03-09 16:05:25', '2026-06-09 18:45:43'),
(43, 84, 1, 'Demande de retrait envoyee', 'Votre demande de retrait enseignant de 23820 XOF est en attente de validation.', '/ecolenumerique/teacher/withdrawals', 'sent', 0, NULL, '2026-03-21 14:04:58', '2026-06-09 18:45:43', '2026-03-21 14:04:58', '2026-06-09 18:45:43'),
(44, 85, 1, 'Demande de retrait envoyee', 'Votre demande de retrait enseignant de 23820 XOF est en attente de validation.', '/ecolenumerique/teacher/withdrawals', 'sent', 0, NULL, '2026-03-21 14:22:36', '2026-06-09 18:45:43', '2026-03-21 14:22:36', '2026-06-09 18:45:43'),
(45, 86, 1, 'Demande de retrait envoyee', 'Votre demande de retrait enseignant de 23820 XOF est en attente de validation.', '/ecolenumerique/teacher/withdrawals', 'sent', 0, NULL, '2026-03-21 14:34:00', '2026-06-09 18:45:43', '2026-03-21 14:34:00', '2026-06-09 18:45:43'),
(46, 87, 1, 'Demande de retrait envoyee', 'Votre demande de retrait enseignant de 23820 XOF est en attente de validation.', '/ecolenumerique/teacher/withdrawals', 'sent', 0, NULL, '2026-03-21 14:43:51', '2026-06-09 18:45:44', '2026-03-21 14:43:51', '2026-06-09 18:45:44'),
(47, 88, 1, 'Demande de retrait envoyee', 'Votre demande de retrait enseignant de 23820 XOF est en attente de validation.', '/ecolenumerique/teacher/withdrawals', 'sent', 0, NULL, '2026-03-21 14:44:00', '2026-06-09 18:45:44', '2026-03-21 14:44:00', '2026-06-09 18:45:44'),
(102, 143, 1, 'Test annonce', 'Corps test', 'http://example.com', 'sent', 0, NULL, '2026-03-25 12:03:56', '2026-06-09 18:45:44', '2026-03-25 12:03:56', '2026-06-09 18:45:44'),
(121, 162, 1, 'Test Debug', 'Corps debug', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-03-25 12:13:20', '2026-06-09 18:45:44', '2026-03-25 12:13:20', '2026-06-09 18:45:44'),
(122, 163, 4, 'Test Debug', 'Corps debug', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-03-25 12:13:20', '2026-06-09 18:45:44', '2026-03-25 12:13:20', '2026-06-09 18:45:44'),
(123, 164, 5, 'Test Debug', 'Corps debug', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-03-25 12:13:20', '2026-06-09 18:45:44', '2026-03-25 12:13:20', '2026-06-09 18:45:44'),
(124, 165, 6, 'Test Debug', 'Corps debug', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-03-25 12:13:20', '2026-06-09 18:45:44', '2026-03-25 12:13:20', '2026-06-09 18:45:44'),
(125, 166, 8, 'Test Debug', 'Corps debug', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-03-25 12:13:20', '2026-06-09 18:45:44', '2026-03-25 12:13:20', '2026-06-09 18:45:44'),
(126, 167, 9, 'Test Debug', 'Corps debug', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-03-25 12:13:20', '2026-06-09 18:45:44', '2026-03-25 12:13:20', '2026-06-09 18:45:44'),
(127, 168, 10, 'Test Debug', 'Corps debug', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-03-25 12:13:20', '2026-06-09 18:45:44', '2026-03-25 12:13:20', '2026-06-09 18:45:44'),
(128, 169, 12, 'Test Debug', 'Corps debug', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-03-25 12:13:20', '2026-06-09 18:45:44', '2026-03-25 12:13:20', '2026-06-09 18:45:44'),
(129, 170, 13, 'Test Debug', 'Corps debug', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-03-25 12:13:20', '2026-06-09 18:45:44', '2026-03-25 12:13:20', '2026-06-09 18:45:44'),
(131, 172, 4, 'svsdvsdvsvsdv', 'vsdvdv', '/ecolenumerique/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-03-25 16:12:29', '2026-06-09 18:45:44', '2026-03-25 16:12:29', '2026-06-09 18:45:44'),
(132, 173, 5, 'svsdvsdvsvsdv', 'vsdvdv', '/ecolenumerique/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-03-25 16:12:29', '2026-06-09 18:45:44', '2026-03-25 16:12:29', '2026-06-09 18:45:44'),
(133, 174, 6, 'svsdvsdvsvsdv', 'vsdvdv', '/ecolenumerique/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-03-25 16:12:29', '2026-06-09 18:45:44', '2026-03-25 16:12:29', '2026-06-09 18:45:44'),
(134, 175, 8, 'svsdvsdvsvsdv', 'vsdvdv', '/ecolenumerique/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-03-25 16:12:29', '2026-06-09 18:45:44', '2026-03-25 16:12:29', '2026-06-09 18:45:44'),
(135, 176, 9, 'svsdvsdvsvsdv', 'vsdvdv', '/ecolenumerique/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-03-25 16:12:29', '2026-06-09 18:45:44', '2026-03-25 16:12:29', '2026-06-09 18:45:44'),
(136, 177, 10, 'svsdvsdvsvsdv', 'vsdvdv', '/ecolenumerique/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-03-25 16:12:29', '2026-06-09 18:45:44', '2026-03-25 16:12:29', '2026-06-09 18:45:44'),
(137, 178, 12, 'svsdvsdvsvsdv', 'vsdvdv', '/ecolenumerique/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-03-25 16:12:29', '2026-06-09 18:45:44', '2026-03-25 16:12:29', '2026-06-09 18:45:44'),
(138, 179, 13, 'svsdvsdvsvsdv', 'vsdvdv', '/ecolenumerique/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-03-25 16:12:29', '2026-06-09 18:45:44', '2026-03-25 16:12:29', '2026-06-09 18:45:44'),
(139, 180, 4, 'Nouvelle demande support', 'Sujet: bonjours', 'admin/support?conv=5', 'sent', 0, NULL, '2026-03-25 16:58:41', '2026-06-09 18:45:44', '2026-03-25 16:58:41', '2026-06-09 18:45:44'),
(140, 181, 13, 'Nouvelle demande support', 'Sujet: bonjours', 'admin/support?conv=5', 'sent', 0, NULL, '2026-03-25 16:58:41', '2026-06-09 18:45:44', '2026-03-25 16:58:41', '2026-06-09 18:45:44'),
(141, 182, 1, 'Demande de retrait envoyée', 'Votre demande de retrait enseignant de 23820 F CFA est en attente de validation.', '/ecolenumerique/teacher/withdrawals', 'sent', 0, NULL, '2026-03-28 14:14:07', '2026-06-09 18:45:44', '2026-03-28 14:14:07', '2026-06-09 18:45:44'),
(142, 183, 1, 'y(((((((((\'', 'rgerheeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-04-03 18:25:27', '2026-06-09 18:45:44', '2026-04-03 18:25:27', '2026-06-09 18:45:44'),
(143, 184, 4, 'y(((((((((\'', 'rgerheeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-04-03 18:25:27', '2026-06-09 18:45:44', '2026-04-03 18:25:27', '2026-06-09 18:45:44'),
(145, 186, 6, 'y(((((((((\'', 'rgerheeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-04-03 18:25:27', '2026-06-09 18:45:44', '2026-04-03 18:25:27', '2026-06-09 18:45:44'),
(146, 187, 8, 'y(((((((((\'', 'rgerheeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-04-03 18:25:27', '2026-06-09 18:45:44', '2026-04-03 18:25:27', '2026-06-09 18:45:44'),
(147, 188, 9, 'y(((((((((\'', 'rgerheeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-04-03 18:25:27', '2026-06-09 18:45:44', '2026-04-03 18:25:27', '2026-06-09 18:45:44'),
(148, 189, 10, 'y(((((((((\'', 'rgerheeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-04-03 18:25:27', '2026-06-09 18:45:44', '2026-04-03 18:25:27', '2026-06-09 18:45:44'),
(149, 190, 12, 'y(((((((((\'', 'rgerheeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-04-03 18:25:27', '2026-06-09 18:45:44', '2026-04-03 18:25:27', '2026-06-09 18:45:44'),
(150, 191, 13, 'y(((((((((\'', 'rgerheeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-04-03 18:25:27', '2026-06-09 18:45:45', '2026-04-03 18:25:27', '2026-06-09 18:45:45'),
(151, 195, 4, 'Nouvelle demande support', 'Sujet: salut', 'admin/support?conv=7', 'sent', 0, NULL, '2026-04-04 15:49:34', '2026-06-09 18:45:45', '2026-04-04 15:49:34', '2026-06-09 18:45:45'),
(152, 196, 13, 'Nouvelle demande support', 'Sujet: salut', 'admin/support?conv=7', 'sent', 0, NULL, '2026-04-04 15:49:34', '2026-06-09 18:45:45', '2026-04-04 15:49:34', '2026-06-09 18:45:45'),
(153, 197, 4, 'Nouvelle demande support', 'Sujet: salut', 'admin/support?conv=8', 'sent', 0, NULL, '2026-04-04 15:49:35', '2026-06-09 18:45:45', '2026-04-04 15:49:35', '2026-06-09 18:45:45'),
(154, 198, 13, 'Nouvelle demande support', 'Sujet: salut', 'admin/support?conv=8', 'sent', 0, NULL, '2026-04-04 15:49:35', '2026-06-09 18:45:45', '2026-04-04 15:49:35', '2026-06-09 18:45:45'),
(155, 192, 4, 'Nouvelle demande support', 'Sujet: salut', 'admin/support?conv=6', 'sent', 0, NULL, '2026-04-05 20:27:28', '2026-06-09 18:45:45', '2026-04-05 20:27:28', '2026-06-09 18:45:45'),
(156, 193, 13, 'Nouvelle demande support', 'Sujet: salut', 'admin/support?conv=6', 'sent', 0, NULL, '2026-04-05 20:27:28', '2026-06-09 18:45:45', '2026-04-05 20:27:28', '2026-06-09 18:45:45'),
(157, 194, 5, 'Support: nouveau message', 'Un agent support a répondu à votre demande.', 'support?conv=6', 'sent', 0, NULL, '2026-04-05 20:29:40', '2026-06-09 18:45:45', '2026-04-05 20:29:40', '2026-06-09 18:45:45'),
(158, 199, 4, 'Cours soumis', 'mike php a été soumis pour validation.', 'http://localhost/ecolenumerique/admin/courses?course_id=15', 'sent', 0, NULL, '2026-04-17 21:04:14', '2026-06-09 18:45:45', '2026-04-17 21:04:14', '2026-06-09 18:45:45'),
(159, 200, 1, 'Cours approuvé', 'mike php a été approuvé.', '/ecolenumerique/teacher/courses', 'sent', 0, NULL, '2026-04-17 21:08:51', '2026-06-09 18:45:45', '2026-04-17 21:08:51', '2026-06-09 18:45:45'),
(160, 201, 4, 'Nouvelle demande de retrait enseignant', 'Michaël ouwade a demandé un retrait de 25 F CFA.', '/ecolenumerique/admin/sales', 'sent', 0, NULL, '2026-05-14 12:13:04', '2026-06-09 18:45:45', '2026-05-14 12:13:04', '2026-06-09 18:45:45'),
(161, 202, 13, 'Nouvelle demande de retrait enseignant', 'Michaël ouwade a demandé un retrait de 25 F CFA.', '/ecolenumerique/admin/sales', 'sent', 0, NULL, '2026-05-14 12:13:04', '2026-06-09 18:45:45', '2026-05-14 12:13:04', '2026-06-09 18:45:45'),
(162, 203, 4, 'Nouvelle demande de retrait enseignant', 'Michaël ouwade a demandé un retrait de 25 F CFA.', '/ecolenumerique/admin/sales', 'sent', 0, NULL, '2026-05-14 12:19:46', '2026-06-09 18:45:45', '2026-05-14 12:19:46', '2026-06-09 18:45:45'),
(163, 204, 13, 'Nouvelle demande de retrait enseignant', 'Michaël ouwade a demandé un retrait de 25 F CFA.', '/ecolenumerique/admin/sales', 'sent', 0, NULL, '2026-05-14 12:19:46', '2026-06-09 18:45:45', '2026-05-14 12:19:46', '2026-06-09 18:45:45'),
(164, 205, 4, 'Nouvelle demande de retrait enseignant', 'Michaël ouwade a demandé un retrait de 25 F CFA.', '/ecolenumerique/admin/sales', 'sent', 0, NULL, '2026-05-16 18:04:03', '2026-06-09 18:45:45', '2026-05-16 18:04:03', '2026-06-09 18:45:45'),
(165, 206, 13, 'Nouvelle demande de retrait enseignant', 'Michaël ouwade a demandé un retrait de 25 F CFA.', '/ecolenumerique/admin/sales', 'sent', 0, NULL, '2026-05-16 18:04:03', '2026-06-09 18:45:46', '2026-05-16 18:04:03', '2026-06-09 18:45:46'),
(166, 207, 4, 'Nouvelle demande support', 'Sujet: salut', 'admin/support?conv=9', 'sent', 3, NULL, '2026-06-09 18:49:16', '2026-06-09 18:49:46', '2026-05-19 09:47:23', '2026-06-09 18:49:46'),
(167, 208, 13, 'Nouvelle demande support', 'Sujet: salut', 'admin/support?conv=9', 'sent', 0, NULL, '2026-05-19 09:47:23', '2026-06-09 18:45:46', '2026-05-19 09:47:23', '2026-06-09 18:45:46'),
(168, 209, 1, 'Support: nouveau message', 'Un agent support a répondu à votre demande.', 'support?conv=9', 'sent', 0, NULL, '2026-05-19 10:18:07', '2026-06-09 18:45:46', '2026-05-19 10:18:07', '2026-06-09 18:45:46'),
(169, 210, 4, 'Nouvelle demande support', 'Sujet: rrr', 'admin/support?conv=10', 'sent', 3, NULL, '2026-06-09 18:49:19', '2026-06-09 18:49:47', '2026-05-27 19:20:41', '2026-06-09 18:49:47'),
(170, 211, 13, 'Nouvelle demande support', 'Sujet: rrr', 'admin/support?conv=10', 'sent', 0, NULL, '2026-05-27 19:20:41', '2026-06-09 18:45:46', '2026-05-27 19:20:41', '2026-06-09 18:45:46'),
(171, 212, 5, 'Support: nouveau message', 'Un agent support a répondu à votre demande.', 'support?conv=10', 'sent', 0, NULL, '2026-05-27 20:13:45', '2026-06-09 18:45:46', '2026-05-27 20:13:45', '2026-06-09 18:45:46'),
(172, 213, 4, 'Support: nouveau message', 'Nouvelle réponse utilisateur sur une demande.', 'admin/support?conv=9', 'sent', 3, NULL, '2026-06-09 18:49:20', '2026-06-09 18:49:47', '2026-05-27 20:47:14', '2026-06-09 18:49:47'),
(173, 214, 13, 'Support: nouveau message', 'Nouvelle réponse utilisateur sur une demande.', 'admin/support?conv=9', 'sent', 0, NULL, '2026-05-27 20:47:14', '2026-06-09 18:45:47', '2026-05-27 20:47:14', '2026-06-09 18:45:47'),
(174, 215, 4, 'Support: nouveau message', 'Nouvelle réponse utilisateur sur une demande.', 'admin/support?conv=10', 'sent', 3, NULL, '2026-06-09 18:49:21', '2026-06-09 18:49:48', '2026-05-27 21:03:38', '2026-06-09 18:49:48'),
(175, 216, 13, 'Support: nouveau message', 'Nouvelle réponse utilisateur sur une demande.', 'admin/support?conv=10', 'sent', 0, NULL, '2026-05-27 21:03:38', '2026-06-09 18:45:47', '2026-05-27 21:03:38', '2026-06-09 18:45:47'),
(176, 217, 5, 'Rendez-vous annulé', 'Le rendez-vous \"mercredi\" a été annulé par le formateur.', 'http://localhost/ecolenumerique/course?slug=word', 'sent', 0, NULL, '2026-05-30 11:10:52', '2026-06-09 18:45:47', '2026-05-30 11:10:52', '2026-06-09 18:45:47'),
(177, 218, 4, 'Support: nouveau message', 'Nouvelle réponse utilisateur sur une demande.', 'admin/support?conv=9', 'sent', 2, NULL, '2026-06-09 18:49:18', '2026-06-09 18:49:49', '2026-06-09 18:47:47', '2026-06-09 18:49:49'),
(178, 219, 13, 'Support: nouveau message', 'Nouvelle réponse utilisateur sur une demande.', 'admin/support?conv=9', 'sent', 0, NULL, '2026-06-09 18:47:47', '2026-06-09 18:47:48', '2026-06-09 18:47:47', '2026-06-09 18:47:48'),
(179, 220, 1, 'eeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-06-20 18:30:02', '2026-06-20 18:30:03', '2026-06-20 18:30:02', '2026-06-20 18:30:03'),
(180, 221, 5, 'eeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-06-20 18:30:02', '2026-06-20 18:30:05', '2026-06-20 18:30:02', '2026-06-20 18:30:05'),
(181, 222, 6, 'eeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-06-20 18:30:02', '2026-06-20 18:30:05', '2026-06-20 18:30:02', '2026-06-20 18:30:05'),
(182, 223, 8, 'eeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-06-20 18:30:02', '2026-06-20 18:30:05', '2026-06-20 18:30:02', '2026-06-20 18:30:05'),
(183, 224, 9, 'eeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-06-20 18:30:02', '2026-06-20 18:30:05', '2026-06-20 18:30:02', '2026-06-20 18:30:05'),
(184, 225, 10, 'eeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-06-20 18:30:02', '2026-06-20 18:30:05', '2026-06-20 18:30:02', '2026-06-20 18:30:05'),
(185, 226, 12, 'eeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-06-20 18:30:02', '2026-06-20 18:30:05', '2026-06-20 18:30:02', '2026-06-20 18:30:05'),
(186, 227, 14, 'eeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-06-20 18:30:02', '2026-06-20 18:30:06', '2026-06-20 18:30:02', '2026-06-20 18:30:06'),
(187, 228, 1, 'Je viens', 'feeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-06-22 15:29:41', '2026-06-22 15:29:42', '2026-06-22 15:29:41', '2026-06-22 15:29:42'),
(188, 229, 5, 'Je viens', 'feeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-06-22 15:29:41', '2026-06-22 15:29:43', '2026-06-22 15:29:41', '2026-06-22 15:29:43'),
(189, 230, 6, 'Je viens', 'feeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-06-22 15:29:41', '2026-06-22 15:29:43', '2026-06-22 15:29:41', '2026-06-22 15:29:43'),
(190, 231, 8, 'Je viens', 'feeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-06-22 15:29:41', '2026-06-22 15:29:43', '2026-06-22 15:29:41', '2026-06-22 15:29:43'),
(191, 232, 9, 'Je viens', 'feeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-06-22 15:29:41', '2026-06-22 15:29:43', '2026-06-22 15:29:41', '2026-06-22 15:29:43'),
(192, 233, 10, 'Je viens', 'feeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-06-22 15:29:41', '2026-06-22 15:29:43', '2026-06-22 15:29:41', '2026-06-22 15:29:43'),
(193, 234, 12, 'Je viens', 'feeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-06-22 15:29:41', '2026-06-22 15:29:43', '2026-06-22 15:29:41', '2026-06-22 15:29:43'),
(194, 235, 14, 'Je viens', 'feeeeeeeeeeeeeeeeeeeee', '/ecolenumerique/notifications?scope=admin', 'sent', 0, NULL, '2026-06-22 15:29:41', '2026-06-22 15:29:43', '2026-06-22 15:29:41', '2026-06-22 15:29:43'),
(195, 236, 1, 'Compte enseignant suspendu', 'Votre compte enseignant a été suspendu par l’administration. Contactez le support pour plus d’informations.', '/ecolenumerique/support', 'sent', 0, NULL, '2026-06-23 09:15:38', '2026-06-23 09:15:39', '2026-06-23 09:15:38', '2026-06-23 09:15:39'),
(196, 237, 1, 'Compte enseignant réactivé', 'Votre accès enseignant est de nouveau actif.', '/ecolenumerique/teacher/dashboard', 'sent', 0, NULL, '2026-06-23 09:15:51', '2026-06-23 09:15:52', '2026-06-23 09:15:51', '2026-06-23 09:15:52'),
(197, 238, 14, 'Nouvelle formation entreprise', 'Une place vous a été attribuée pour \"mike php\".', 'student/my_courses', 'sent', 0, NULL, '2026-06-23 10:54:29', '2026-06-23 10:54:29', '2026-06-23 10:54:29', '2026-06-23 10:54:29');

-- --------------------------------------------------------

--
-- Structure de la table `push_subscriptions`
--

CREATE TABLE `push_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `endpoint_hash` char(64) NOT NULL,
  `endpoint` text NOT NULL,
  `p256dh` varchar(255) NOT NULL,
  `auth` varchar(255) NOT NULL,
  `content_encoding` varchar(32) NOT NULL DEFAULT 'aes128gcm',
  `user_agent` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `failure_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_success_at` datetime DEFAULT NULL,
  `last_failure_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `push_subscriptions`
--

INSERT INTO `push_subscriptions` (`id`, `user_id`, `endpoint_hash`, `endpoint`, `p256dh`, `auth`, `content_encoding`, `user_agent`, `is_active`, `failure_count`, `last_success_at`, `last_failure_at`, `created_at`, `updated_at`) VALUES
(1, 4, 'e46684a21d0369c723f8aea4d4bcb3f6f042a4304557685a9b998571d1d98723', 'https://fcm.googleapis.com/fcm/send/eYSbSLIpY-k:APA91bFY9rr71SdXAyuKwu6EOQksLL9yStKQKsleWVbBDGQtWFSh_-wGWqKV7Ey5njlneU0WuhaJ-b1JZ5-IMyHuCtOiMMAoXeKHa489_591qdd1qOdIeuIRAMIYaHEU5WvykDZn-skA', 'BHMWNxnzkDnOtjU3_zLz0NQNKG7Iqtt7cSyzkKW27Vg09MZbf3m6JJZIhR43wv0bCdKwnNwd6M1qw6KyR7rLvcQ', 'TuAlybSijRAkcWOJ4056fA', 'aes128gcm', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, 0, '2026-06-09 18:49:49', '2026-06-09 18:48:18', '2026-06-09 18:45:46', '2026-06-26 09:06:54'),
(5, 1, '976b85b3b1e2c49cd710418e22c27e5dfac6b786cdab59f45c68cdcfcc170424', 'https://fcm.googleapis.com/fcm/send/cHLU0GvJyfY:APA91bEixDLVrLDmfeMX-pF9Mac-adARcMyKHrGhcOw3H0t95HFjcwVyGneUwf_wuuliaBUTkVnvxin5uoraSQMMVX5R4xQzjQjb6hm8GTg3OmvFXfrOStuBFcrZ4zPRRV_xwZy3jDRq', 'BF6XCUIdolitj2s0blVUEtEQtR2OHofWNdJAdVI9jfpeF_Xum_EVtyIs-d_Haj9oTT3wUJ392zRQBQrOXGLN6YY', 'fM67T6qfrjrXJmwdSXDtxQ', 'aes128gcm', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, 0, '2026-06-23 09:15:52', NULL, '2026-06-09 18:47:35', '2026-06-26 09:14:53'),
(8, 5, '396067be039bf5620c75d391d39b8b9ea0e178a6771667a363ff4bf8f4015743', 'https://fcm.googleapis.com/fcm/send/dSm0LcKpyDM:APA91bHiKAD0MsbMIPipX45QY2z8D3_eRUFvCdwfjDsN7cqaev1JVHeMMgCZJThWSRqLUJDynFimUB23ST-rMM6McQM2iv0xNyX_2iLzgtbfRnrq7S_SDqLnr3vUGRb7Q4IbOiLB6Vyt', 'BMnI18IdktRXziWgUmxhlKleWaM-FCK5FhBDk0WLiTYqDuvpGocRHMu7in2tA_Atj8vvECMCMVcFW1TOlYZXpws', 'ypU2rneGb4_5_bWdUXVGoQ', 'aes128gcm', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, 1, NULL, '2026-06-20 18:30:04', '2026-06-09 18:53:16', '2026-06-20 18:30:04'),
(1027, 5, '756a98c396af0bef4655c743306aa3db2b8a4f17257eacd84a1293a836955a52', 'https://fcm.googleapis.com/fcm/send/fMn9GSn89hA:APA91bHy_olb_sQXKZObKgeNAzC4CGD7P2FrMYkgCzsms4g7DYsYtt3LBRihu4HWQWpTxCvfDglPxf8_uavY4E3pB3wCwDgsjSGw2mwv4z6bFMPXJhlA25B1BQI3gKeYVpBp3YNOSVES', 'BOAtc858bav4pL_LXF-7HYgP39k10fOnee46B0o089-T5fgQm32Aq0ITZgvfnP6ld9oydFdMVUsOYl88NvkGuwg', 'EQr5yrke9H9_-LOKfGVxFQ', 'aes128gcm', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, 1, NULL, '2026-06-20 18:30:04', '2026-06-17 10:53:30', '2026-06-20 18:30:04'),
(1050, 5, 'f07db54607430987e513df2797d4081646247d2d710b95413608c7daed018deb', 'https://fcm.googleapis.com/fcm/send/fGwxb3oz9Fc:APA91bHd8j8tMX5v6wXNQCxnJJdUrpw3li6OVFhoHvSxUKutljZYKzBzwafFrkvdboTfQc5yyIp7xsz6bDoYAoZ3HP8DMAkq_FWJi0hr_eeJQKo7UjySajVv8e0q2gyJ9hy5kSyxATcB', 'BI8q8CDaAAlcTSwHpeAqexXWMIFIfOlyaqIIkzmSVdxTC7-Ys_WqfXEuJy58oD8NSeVXZQwM1JwKSFi9kip_Tns', 'hWp3kJaOFRFPIKHACwZlqQ', 'aes128gcm', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 0, 1, NULL, '2026-06-20 18:30:05', '2026-06-17 22:36:36', '2026-06-20 18:30:05'),
(1063, 5, 'e7eae0d5fc48268ef2b5d2658898449a1c364012b0185eef7a93b28a79051dcf', 'https://fcm.googleapis.com/fcm/send/eNb24RRfdt8:APA91bH1q1NJVkNEZBf5NgHR1UCG-PbR6FMqitpwWP0UzDNearCi48qBvizcR-FmEYK910VLIMlNfHSQiUHzAUsGhkM3jIjWRX_vCcWody7EqlTWuGV0e_breMII-1zBiQq32ckTQTLv', 'BIseNr_sXyjAFPvELXkIKqo1U06zJJWC-5g19vhtyZQ9nOuEY0R1aU-vmaN-TVZsMwCWC6PvYSGWozThRi61aa4', 'TDgVGH6DooJ3kaahu6DQCw', 'aes128gcm', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, 0, '2026-06-22 15:29:43', NULL, '2026-06-18 01:04:28', '2026-06-24 18:17:56'),
(1873, 5, 'f82d58bb8c4bfb59bec3ee148ca10db3787f4a185ba6f354a9efdbfa4fc542a0', 'https://fcm.googleapis.com/fcm/send/dGZW6KwpETc:APA91bH8LiPT5iG62nHxRpQDBgd9hsCxmKNN-UTSwfMknf0laMBbZFnb6OgAuwjjag1V0i90PO1W1xzRj3HndCI8DYiU9Oatd7YLde2bupV0JCsB9y8_nTW4kKk-7eRMITsHmqWTqx-4', 'BKfhol10ACsgWS9JKQzwAF0MOUex17r6zZYitBpXToCVU-MMNPABqWShjsYWOZeJL0TGRakBpPjIhfyEJ3nQ_OA', '4jbtfALeUfJNXZtomAEtKA', 'aes128gcm', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 1, 0, NULL, NULL, '2026-06-24 19:20:22', '2026-06-26 08:17:37'),
(2081, 9, '1cfd363e2b4e10b9142b0ff602124478e79e3cae51d42b98b4b2d3bb27834e61', 'https://fcm.googleapis.com/fcm/send/escusPnP4EU:APA91bGvebJ_J1Lfzi0r9un1eNfhsuPUB7bG9mdMJj5OE0hlHBP6yLnzsXEhMuNrQ_bVufGFgeaPLxEJ2cujBd2jDb1WLIb3CgxqKVTdgDqG54zHpu_CQlugjoJiAErbP7wRC48Mu7u3', 'BJ7a561Ea5zmmfKoLxB_wsSMBdyzxQuqu39bVoT_KjqNhL5HQM8mmJVU8XoPb6JHQ6NrVYThk1FTA_TTXTAE_ig', 'FOMaTejmnjKmh3f4fpcGJA', 'aes128gcm', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 1, 0, NULL, NULL, '2026-07-01 11:27:10', '2026-07-14 16:16:09'),
(2102, 1, '3f3ee2daa1c0373d4c8eccc778987cb3538496c81d5c4d1e5c2807996501bda2', 'https://fcm.googleapis.com/fcm/send/fVy_1mnkZuc:APA91bEXNoaAZTibTsEWPm-4GBAVV6ZH5s0IBTzGgaXWxrUTB9bzzVZ47_1gZI04VfulIi79XCtEY4d7iibrXAKP09MjgcdWbhvykBxjYSRMxljf5vDFtYY2PL3MqTtkojeJ7Xq45pSd', 'BN-86FJhIiTInTjGApk2lslaY8tT-lzpFHSaQFckdQdfLRSSZ9E5c9EkDbZAKHDEz14g2pFDmYqlf7Hi6-0lzPs', '1jrwy4NqPlenlft8sGnOYw', 'aes128gcm', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 1, 0, NULL, NULL, '2026-07-01 13:20:37', '2026-07-14 10:24:38'),
(2207, 4, 'ab0e39cc6ba9ee0d8fcfeaf406e1bd400ca51b7160d75dea99224b921ac5b94d', 'https://fcm.googleapis.com/fcm/send/fuWvJhSiiMc:APA91bFoJvywI51zcVmQKBrO4giaJphMzzCaDG7HLemKB7xklAIzYpxaQIMsjncQJyyTSy0Dg86kIeJMCihir3ZdiEemSIr-f7Mxmlfd1f_uZTAgQqGpTlxBM_kOJow33J4XLqZkTtxJ', 'BBRG6oEdbcafeINLRVV9eyIzuvFMPchOnspwo3GqLNZtBPQk8uw_aIgDdRBEh-Hc8dcZkeCdHKxyzGODpF4XTvs', 'lfsc3p4Fl73DosYhwiTn0Q', 'aes128gcm', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 1, 0, NULL, NULL, '2026-07-02 13:44:06', '2026-07-14 17:56:46'),
(2694, 5, 'a57a5e8170cf70a943db4fb1269ebdd8384a9ca2dc6f117476d93f34bda4a6b2', 'https://fcm.googleapis.com/fcm/send/cGB3DuHHMSo:APA91bEiaq-gvi0seB0JCG6YKMDKwL-FOaXfDyPgsGmbpc1sKA8T5q_rLMyq3BQYy1LwjtK-hk9ZwKXzFPVYD_VocmzOw_TSaTzn-LY7y2e1ipEdodx1oESQshny1gZmLtlNDyh73L84', 'BFIlIPKC2lLyvSy0dkVZXgJbql2qNZnNsC7uhtEkNIyUtTaciX58TxWTvIjAzahIduT7Dr7I4Cz8uc59gA12moA', 'tpUuMjzHOJKFHt0FdVZxng', 'aes128gcm', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 1, 0, NULL, NULL, '2026-07-13 13:27:23', '2026-07-14 17:56:26');

-- --------------------------------------------------------

--
-- Structure de la table `referrals`
--

CREATE TABLE `referrals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `referrer_user_id` bigint(20) UNSIGNED NOT NULL,
  `referred_user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `referral_commissions`
--

CREATE TABLE `referral_commissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `order_item_id` bigint(20) UNSIGNED NOT NULL,
  `course_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `buyer_id` bigint(20) UNSIGNED NOT NULL,
  `referrer_id` bigint(20) UNSIGNED NOT NULL,
  `level` tinyint(1) NOT NULL,
  `rate_percent` decimal(5,2) NOT NULL,
  `amount_xof` int(10) UNSIGNED NOT NULL,
  `status` enum('earned','reversed') NOT NULL DEFAULT 'earned',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `referral_commissions`
--

INSERT INTO `referral_commissions` (`id`, `order_id`, `order_item_id`, `course_id`, `teacher_id`, `buyer_id`, `referrer_id`, `level`, `rate_percent`, `amount_xof`, `status`, `created_at`) VALUES
(1, 5, 5, 4, 1, 10, 1, 1, 5.00, 80, 'earned', '2026-01-07 16:06:33'),
(2, 6, 6, 4, 1, 10, 1, 1, 5.00, 100, 'earned', '2026-01-08 15:09:21');

-- --------------------------------------------------------

--
-- Structure de la table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `certificate_display_name` varchar(180) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- --------------------------------------------------------

--
-- Structure de la table `support_conversation_reads`
--

CREATE TABLE `support_conversation_reads` (
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `last_read_message_id` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `support_conversation_reads`
--

INSERT INTO `support_conversation_reads` (`conversation_id`, `user_id`, `last_read_message_id`, `updated_at`) VALUES
(1, 4, 1, '2026-03-25 17:55:27'),
(1, 6, 1, '2026-01-09 12:30:35'),
(1, 10, 1, '2026-01-09 10:00:40'),
(2, 4, 4, '2026-05-25 18:58:55'),
(2, 6, 4, '2026-01-09 12:48:59'),
(2, 10, 4, '2026-03-26 19:00:43'),
(2, 13, 4, '2026-02-17 15:47:55'),
(3, 4, 5, '2026-05-25 18:58:54'),
(3, 9, 5, '2026-07-14 12:22:15'),
(4, 4, 17, '2026-03-25 17:55:24'),
(4, 6, 17, '2026-02-20 19:12:34'),
(4, 12, 16, '2026-02-19 16:42:51'),
(5, 4, 18, '2026-05-25 18:58:52'),
(5, 8, 18, '2026-03-25 16:58:41'),
(6, 4, 20, '2026-07-13 19:37:38'),
(6, 5, 20, '2026-07-14 12:22:39'),
(7, 4, 21, '2026-06-20 18:00:03'),
(7, 5, 21, '2026-05-27 19:19:30'),
(8, 5, 22, '2026-06-16 16:51:08'),
(9, 1, 29, '2026-07-14 12:22:23'),
(9, 4, 29, '2026-07-14 12:58:02'),
(10, 4, 28, '2026-07-13 19:53:18'),
(10, 5, 28, '2026-06-12 12:05:26');

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

-- --------------------------------------------------------

--
-- Structure de la table `support_message_attachments`
--

CREATE TABLE `support_message_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime` varchar(120) NOT NULL,
  `size` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `support_message_attachments`
--

INSERT INTO `support_message_attachments` (`id`, `message_id`, `file_path`, `original_name`, `mime`, `size`, `created_at`) VALUES
(1, 25, 'support://support_20260527_192041_eb73716f4374485f.png', 'ChatGPT Image 11 mai 2026, 15_19_22.png', 'image/png', 1896474, '2026-05-27 19:20:41');

-- --------------------------------------------------------

--
-- Structure de la table `teacher_applications`
--

CREATE TABLE `teacher_applications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `bio` text NOT NULL,
  `teaching_experience` varchar(80) DEFAULT NULL,
  `cv_path` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `decided_by` bigint(20) UNSIGNED DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `teacher_applications`
--

INSERT INTO `teacher_applications` (`id`, `user_id`, `title`, `bio`, `teaching_experience`, `cv_path`, `status`, `reason`, `decided_by`, `decided_at`, `created_at`, `updated_at`) VALUES
(2, 1, 'mike', 'oui', NULL, 'https://docs.google.com/presentation/d/1cR3ua8u8CAeLSUrUtyQXyQ9a2UYMyOT6SZdp57qQK9E/edit?usp=sharing', 'approved', NULL, 4, '2025-11-28 14:04:00', '2025-11-28 13:55:17', '2025-11-28 14:04:00'),
(3, 10, 'Formateur', 'az', NULL, 'https://drive.google.com/file/d/1rSxbs-0yl_TmdtlBJ7RcTkkUae1J9BRr/view?usp=drivesdk', 'approved', NULL, 4, '2026-01-10 19:20:39', '2026-01-10 19:12:57', '2026-01-10 19:20:39');

-- --------------------------------------------------------

--
-- Structure de la table `teacher_payouts`
--

CREATE TABLE `teacher_payouts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `total_sales_xof` int(10) UNSIGNED NOT NULL,
  `platform_fee_xof` int(10) UNSIGNED NOT NULL,
  `teacher_amount_xof` int(10) UNSIGNED NOT NULL,
  `commission_percent` decimal(5,2) NOT NULL,
  `status` enum('simulated','paid') NOT NULL DEFAULT 'simulated',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `teacher_profiles`
--

CREATE TABLE `teacher_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `professional_title` varchar(150) DEFAULT NULL,
  `short_bio` text DEFAULT NULL,
  `portfolio_url` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `cv_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `teacher_referral_settings`
--

CREATE TABLE `teacher_referral_settings` (
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `l1_rate` decimal(5,2) NOT NULL DEFAULT 5.00,
  `l2_rate` decimal(5,2) NOT NULL DEFAULT 2.00,
  `l2_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `teacher_referral_settings`
--

INSERT INTO `teacher_referral_settings` (`teacher_id`, `l1_rate`, `l2_rate`, `l2_enabled`, `updated_at`) VALUES
(1, 5.00, 2.00, 0, '2026-01-06 17:15:16'),
(8, 5.00, 2.00, 0, '2026-01-07 15:32:21'),
(10, 5.00, 2.00, 0, '2026-01-10 19:23:02');

-- --------------------------------------------------------

--
-- Structure de la table `uploads`
--

CREATE TABLE `uploads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `owner_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime` varchar(120) NOT NULL,
  `size` bigint(20) UNSIGNED NOT NULL,
  `scan_status` enum('pending','clean','infected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(190) NOT NULL,
  `referral_code` varchar(32) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `signature_path` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('pending_email','active','suspended') NOT NULL DEFAULT 'pending_email',
  `email_verified_at` datetime DEFAULT NULL,
  `auth_provider` enum('local','google') NOT NULL DEFAULT 'local',
  `mfa_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `mfa_enabled_at` datetime DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `roles_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`roles_json`)),
  `remember_selector` char(24) DEFAULT NULL,
  `remember_validator_hash` char(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notification_preferences_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_preferences_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `referral_code`, `avatar_url`, `signature_path`, `password_hash`, `status`, `email_verified_at`, `auth_provider`, `mfa_enabled`, `mfa_enabled_at`, `phone`, `country`, `roles_json`, `remember_selector`, `remember_validator_hash`, `created_at`, `updated_at`, `notification_preferences_json`) VALUES
(1, 'Michaël', 'ouwade', 'ouwadem@gmail.com', 'REF52850C3924B2', NULL, 'storage/uploads/signatures/signature_d5adde1451ec3a55528a.jpg', '$2y$10$dd8WcoRjcH1ChGi7nf9V3ejU6I3hF.pW5jw9rrZUd4pE7/atL9YW2', 'active', '2025-11-26 13:57:40', 'local', 0, NULL, '0195715119', 'BJ', '[\"student\",\"teacher\"]', 'bb4b191a83b84316afe1a13b', 'dee4fd1f0a94a1c5db44ee81026696f750afde6fb08fb9b1317d80a947c2019b', '2025-11-26 13:57:29', '2026-07-14 10:07:36', NULL),
(4, 'Michaël', 'Michael', 'sagui@gmail.com', NULL, NULL, NULL, '$2y$10$yjrpwllTfgT08BlI9XylieiB67ZZDrdgHk1.xONQGVCoYFbvK6eze', 'active', '2025-11-28 12:55:19', 'local', 0, NULL, NULL, NULL, '[\"admin\",\"support\"]', '88bb6e8f859306d51adac746', 'c482c3e534f1e553d39b1b6db7b518476ca9babda5c226a365aa196e0a606abb', '2025-11-28 12:55:19', '2026-07-14 09:52:13', NULL),
(5, 'Michaël', 'Ouwade', 'ouwade@gmail.com', 'REF63C021BC3C3C', NULL, NULL, '$2y$10$AVIHJ6a9OsBFqNJwRkj.8.XYTRq1XPa3j5sOUu9Z59O34wKg6a2da', 'active', '2025-12-05 12:22:50', 'local', 0, NULL, NULL, NULL, '[\"student\"]', '48b51a435f805cac2e8e2a0d', 'b0d29045d576c07601e48756694b0187d0301c6b0038eb77de52847220739d30', '2025-12-05 12:22:47', '2026-07-14 16:23:15', NULL),
(6, 'mike', 'mike', 'mic65500@gmail.com', 'REFCC68CF55D5CD', NULL, NULL, '$2y$10$o2ZZ0N6yu4RKbNUsOgaxqeesysFDcDsKEYITostHuKRWLU/wIHEHO', 'active', '2026-01-06 17:11:52', 'local', 0, NULL, NULL, NULL, '[\"student\"]', NULL, NULL, '2026-01-06 17:11:45', '2026-03-26 19:03:02', NULL),
(8, 'michelle', 'michelle', 'm45885679@gmail.com', 'REFB57EB237FF90', 'storage/uploads/avatars/3910d488cc473aa35b60.jpg', 'storage/uploads/signatures/signature_7c042507f9494efec696.jpg', '$2y$10$P5aOSvLfa0l4xML5xOD8OeHhU2CkSW8h8ds19iDX8Cl9mWWPFSlSe', 'active', '2026-01-07 10:17:02', 'local', 0, NULL, '0151312605', 'Bénin', '[\"student\",\"teacher\"]', 'd53e47d5bc124d894fc334db', 'e4481be88b1034ff2e7630721b923315b147729b280698890311483d78f967c5', '2026-01-07 10:17:00', '2026-03-29 14:45:10', NULL),
(9, 'Meyandé Michaël', 'OUWADE', 'm48910503@gmail.com', 'REF53FEAA5E1F34', 'storage/uploads/avatars/cd095ca6a97bff692caf.jpg', NULL, '$2y$10$O5qsfrLUEmzWraORqGcjwel6./x/v0MgrjJfBmfNsT8el9YKQgCUa', 'active', '2026-01-07 10:18:32', 'local', 0, NULL, '0151312605', 'BJ', '[\"student\"]', NULL, NULL, '2026-01-07 10:18:29', '2026-07-14 16:16:11', NULL),
(10, 'Micael', 'Ahouansi', 'ouwade4@gmail.com', 'REF27B1877182C6', 'storage/uploads/avatars/bbfbef067c42d93a9657.jpg', NULL, '$2y$10$lGNUH2XBJ4RYD.B16L8ggub4CQd9xyAFC8xq2uKNcg16H/PTufhrC', 'active', '2026-01-07 10:25:27', 'local', 0, NULL, '0151312605', 'benin', '[\"student\",\"teacher\"]', NULL, NULL, '2026-01-07 10:25:22', '2026-06-10 19:53:32', NULL),
(11, 'PASCAL', 'DJIVON', 'affairiste190@gmail.com', 'REFC57ADBBD40CA', NULL, NULL, '$2y$10$61kruiYHYkJK4bG960PzJePoIgOs88/Fn61YeZo4HaAvcWw1.YI86', 'pending_email', NULL, 'local', 0, NULL, NULL, NULL, '[\"student\"]', NULL, NULL, '2026-01-10 17:53:32', '2026-01-10 17:53:32', NULL),
(12, 'michelle', 'michelle', 'ouwade40@gmail.com', 'REF0945D91C8CED', NULL, NULL, '$2y$10$na9HH7o6D4DfcrmV67erLOgWPo397GKwQQ/RPwSm/obqG2HIt7y2C', 'active', '2026-01-15 14:04:11', 'local', 0, NULL, NULL, NULL, '[\"student\",\"company_admin\"]', '06b2b1c6d988161d4c19d441', '16dac0f2555b9e6076509401b94e6d387c9a83ca6c92fc8c32d71159ffb3f6be', '2026-01-15 14:04:08', '2026-03-30 18:57:47', NULL),
(13, 'Michaël', 'Michael', 'sagu@gmail.com', NULL, NULL, NULL, '$2y$10$YVC9hvxuMGEQvfXVMIc/1eWLx.qYI/nTLOilBOlGrGryHk8W8t5Qu', 'active', '2026-02-17 15:38:06', 'local', 0, NULL, NULL, NULL, '[\"admin_secondary\"]', NULL, NULL, '2026-02-17 15:38:06', '2026-02-17 15:38:06', NULL),
(14, 'hhhhhhhhhhhh', 'Ekim', 'ekim6550@gmail.com', 'REFF6B25FD8A590', NULL, NULL, '$2y$10$em/N6huwn61kbKwhf5AfQO/eKKGztRJ3W.Zw4kE0HtILL0A1ESB1K', 'active', '2026-05-25 19:46:22', 'local', 0, NULL, NULL, NULL, '[\"student\",\"company_admin\"]', '418644cd769bd58b35552a10', '3b8f256a876869b2de4ef36f9b162df90817fd796acf85197dd21f015ae6c1ef', '2026-05-25 19:46:18', '2026-06-25 09:17:25', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `withdrawals`
--

CREATE TABLE `withdrawals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `withdrawal_type` enum('teacher_sales','referral') NOT NULL DEFAULT 'teacher_sales',
  `amount_xof` int(10) UNSIGNED NOT NULL,
  `method` enum('fedapay','crypto') NOT NULL,
  `destination` varchar(190) NOT NULL,
  `status` enum('requested','approved','rejected','paid') NOT NULL DEFAULT 'requested',
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `payout_reference` varchar(160) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `withdrawals`
--

INSERT INTO `withdrawals` (`id`, `user_id`, `withdrawal_type`, `amount_xof`, `method`, `destination`, `status`, `reviewed_by`, `reviewed_at`, `payout_reference`, `reason`, `created_at`, `updated_at`) VALUES
(1, 1, 'teacher_sales', 23820, 'fedapay', '+229', '', NULL, '2026-03-21 14:18:41', NULL, NULL, '2026-03-21 14:04:58', '2026-03-21 14:18:41'),
(2, 1, 'teacher_sales', 23820, 'fedapay', '+229', '', NULL, '2026-03-21 14:22:45', NULL, NULL, '2026-03-21 14:22:36', '2026-03-21 14:22:45'),
(3, 1, 'teacher_sales', 23820, 'fedapay', '+229', 'rejected', NULL, '2026-03-21 14:34:06', NULL, NULL, '2026-03-21 14:34:00', '2026-03-21 14:34:06'),
(4, 1, 'teacher_sales', 23820, 'fedapay', '+229', 'rejected', NULL, '2026-03-21 14:43:54', NULL, NULL, '2026-03-21 14:43:51', '2026-03-21 14:43:54'),
(5, 1, 'teacher_sales', 23820, 'fedapay', '+229', 'rejected', NULL, '2026-03-21 14:50:55', NULL, NULL, '2026-03-21 14:44:00', '2026-03-21 14:50:55'),
(6, 1, 'teacher_sales', 23820, 'fedapay', '+2290195715119', 'rejected', NULL, '2026-03-28 14:43:29', NULL, NULL, '2026-03-28 14:14:07', '2026-03-28 14:43:29'),
(7, 1, 'teacher_sales', 25, 'fedapay', '+2290195715119', 'rejected', NULL, '2026-05-14 12:13:18', NULL, NULL, '2026-05-14 12:13:04', '2026-05-14 12:13:18'),
(8, 1, 'teacher_sales', 25, 'fedapay', '+2290195715119', 'rejected', NULL, '2026-05-14 12:19:56', NULL, NULL, '2026-05-14 12:19:46', '2026-05-14 12:19:56'),
(9, 1, 'teacher_sales', 25, 'fedapay', '+2290195715119', 'rejected', NULL, '2026-05-16 18:04:20', NULL, NULL, '2026-05-16 18:04:03', '2026-05-16 18:04:20');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admin_announcements`
--
ALTER TABLE `admin_announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_announcements_created` (`created_at`),
  ADD KEY `idx_admin_announcements_admin` (`admin_user_id`),
  ADD KEY `idx_admin_announcements_status` (`status`),
  ADD KEY `idx_admin_announcements_audience` (`audience`),
  ADD KEY `idx_admin_announcements_importance` (`importance`),
  ADD KEY `idx_admin_announcements_category` (`category`),
  ADD KEY `idx_admin_announcements_published` (`published_at`);

--
-- Index pour la table `admin_announcement_events`
--
ALTER TABLE `admin_announcement_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ann_events_announcement` (`announcement_id`),
  ADD KEY `idx_ann_events_action` (`action`),
  ADD KEY `fk_ann_events_actor` (`actor_id`);

--
-- Index pour la table `admin_secondary_permissions`
--
ALTER TABLE `admin_secondary_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_admin_secondary_permissions_user` (`user_id`),
  ADD KEY `idx_admin_secondary_permissions_active` (`is_active`),
  ADD KEY `fk_admin_secondary_permissions_creator` (`created_by`);

--
-- Index pour la table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_audit_user` (`user_id`),
  ADD KEY `idx_audit_action` (`action`),
  ADD KEY `idx_audit_target` (`target_type`,`target_id`);

--
-- Index pour la table `catalog_event_logs`
--
ALTER TABLE `catalog_event_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_catalog_events_type_block_date` (`event_type`,`block_key`,`created_at`),
  ADD KEY `idx_catalog_events_course` (`course_id`),
  ADD KEY `idx_catalog_events_user` (`user_id`),
  ADD KEY `idx_catalog_events_session` (`session_key`);

--
-- Index pour la table `catalog_order_item_attributions`
--
ALTER TABLE `catalog_order_item_attributions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_catalog_attr_order_item` (`order_item_id`),
  ADD KEY `idx_catalog_attr_order` (`order_id`),
  ADD KEY `idx_catalog_attr_block` (`block_key`),
  ADD KEY `idx_catalog_attr_course` (`course_id`),
  ADD KEY `fk_catalog_attr_user` (`user_id`);

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_categories_parent` (`parent_id`);

--
-- Index pour la table `category_proposals`
--
ALTER TABLE `category_proposals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_category_proposals_category` (`category_id`),
  ADD KEY `idx_category_proposals_status` (`status`),
  ADD KEY `idx_category_proposals_teacher` (`teacher_user_id`),
  ADD KEY `idx_category_proposals_name` (`normalized_name`);

--
-- Index pour la table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cert_code` (`code`),
  ADD UNIQUE KEY `uq_cert_user_course` (`user_id`,`course_id`),
  ADD KEY `fk_cert_course` (`course_id`),
  ADD KEY `fk_cert_attempt` (`attempt_id`),
  ADD KEY `idx_cert_exam` (`exam_id`),
  ADD KEY `idx_cert_status` (`status`);

--
-- Index pour la table `chapter_documents`
--
ALTER TABLE `chapter_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_chapter_docs_position` (`chapter_id`,`position`);

--
-- Index pour la table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_companies_admin` (`admin_user_id`),
  ADD KEY `idx_companies_email` (`company_email`);

--
-- Index pour la table `company_activity_events`
--
ALTER TABLE `company_activity_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company_activity_company_created` (`company_id`,`created_at`),
  ADD KEY `idx_company_activity_actor` (`actor_user_id`);

--
-- Index pour la table `company_course_seat_assignments`
--
ALTER TABLE `company_course_seat_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_company_course_member` (`company_id`,`course_id`,`member_user_id`),
  ADD KEY `idx_company_course_assignments_company_course_status` (`company_id`,`course_id`,`status`),
  ADD KEY `idx_company_course_assignments_member` (`member_user_id`,`status`),
  ADD KEY `fk_company_course_assignments_course` (`course_id`),
  ADD KEY `fk_company_course_assignments_assigned_by` (`assigned_by`);

--
-- Index pour la table `company_course_seat_purchases`
--
ALTER TABLE `company_course_seat_purchases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_company_course_seat_purchase_order_item` (`order_item_id`),
  ADD KEY `idx_company_course_seat_purchases_company_course` (`company_id`,`course_id`,`status`),
  ADD KEY `idx_company_course_seat_purchases_order` (`order_id`),
  ADD KEY `fk_company_course_seat_purchases_course` (`course_id`),
  ADD KEY `fk_company_course_seat_purchases_user` (`purchased_by`);

--
-- Index pour la table `company_invites`
--
ALTER TABLE `company_invites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_company_invites_token` (`token_hash`),
  ADD KEY `idx_company_invites_company` (`company_id`),
  ADD KEY `idx_company_invites_email` (`email`),
  ADD KEY `fk_company_invites_inviter` (`invited_by`),
  ADD KEY `idx_company_invites_mode_status` (`company_id`,`invite_mode`,`status`),
  ADD KEY `idx_company_invites_requested_user` (`requested_user_id`),
  ADD KEY `idx_company_invites_approval_status` (`company_id`,`approval_status`),
  ADD KEY `fk_company_invites_approved_by` (`approved_by`);

--
-- Index pour la table `company_members`
--
ALTER TABLE `company_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_company_members` (`company_id`,`user_id`),
  ADD KEY `idx_company_members_user` (`user_id`),
  ADD KEY `idx_company_members_role` (`role`);

--
-- Index pour la table `company_metrics_daily`
--
ALTER TABLE `company_metrics_daily`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_company_metrics_daily` (`company_id`,`metric_date`),
  ADD KEY `idx_company_metrics_daily_date` (`metric_date`);

--
-- Index pour la table `company_onboarding_tasks`
--
ALTER TABLE `company_onboarding_tasks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_company_onboarding_task` (`company_id`,`task_key`),
  ADD KEY `idx_company_onboarding_status` (`company_id`,`status`),
  ADD KEY `idx_company_onboarding_due_at` (`due_at`),
  ADD KEY `fk_company_onboarding_owner` (`owner_user_id`);

--
-- Index pour la table `company_sso_configs`
--
ALTER TABLE `company_sso_configs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_company_sso_company` (`company_id`),
  ADD KEY `idx_company_sso_enabled` (`is_enabled`),
  ADD KEY `fk_company_sso_created_by` (`created_by`);

--
-- Index pour la table `company_success_reviews`
--
ALTER TABLE `company_success_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company_success_reviews_company_date` (`company_id`,`review_date`),
  ADD KEY `idx_company_success_reviews_status` (`status`),
  ADD KEY `fk_company_success_reviews_owner` (`owner_user_id`);

--
-- Index pour la table `company_teams`
--
ALTER TABLE `company_teams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_company_teams_name` (`company_id`,`name`),
  ADD KEY `idx_company_teams_company_status` (`company_id`,`status`),
  ADD KEY `fk_company_teams_created_by` (`created_by`);

--
-- Index pour la table `company_team_members`
--
ALTER TABLE `company_team_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_company_team_member` (`team_id`,`member_id`),
  ADD KEY `idx_company_team_members_company` (`company_id`),
  ADD KEY `idx_company_team_members_member` (`member_id`),
  ADD KEY `fk_company_team_members_assigned_by` (`assigned_by`);

--
-- Index pour la table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `uq_courses_share_token` (`share_token`),
  ADD KEY `fk_courses_category` (`category_id`),
  ADD KEY `idx_courses_status` (`status`),
  ADD KEY `idx_courses_deleted` (`deleted_at`),
  ADD KEY `idx_courses_teacher` (`teacher_id`),
  ADD KEY `fk_courses_version_of` (`version_of`),
  ADD KEY `idx_courses_enterprise_price` (`enterprise_price_xof`),
  ADD KEY `idx_courses_enterprise_discount` (`enterprise_discount_percent`);

--
-- Index pour la table `course_chapters`
--
ALTER TABLE `course_chapters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_chapters_position` (`course_id`,`position`),
  ADD KEY `idx_chapters_course` (`course_id`);

--
-- Index pour la table `course_favorites`
--
ALTER TABLE `course_favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_course_favorites_user_course` (`user_id`,`course_id`),
  ADD KEY `idx_course_favorites_user_created` (`user_id`,`created_at`),
  ADD KEY `idx_course_favorites_course` (`course_id`);

--
-- Index pour la table `course_lessons`
--
ALTER TABLE `course_lessons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_lessons_position` (`chapter_id`,`position`),
  ADD KEY `idx_lessons_type` (`lesson_type`);

--
-- Index pour la table `course_meetings`
--
ALTER TABLE `course_meetings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_course_meetings_course` (`course_id`),
  ADD KEY `fk_course_meetings_student` (`student_id`),
  ADD KEY `fk_course_meetings_confirmed` (`confirmed_by`),
  ADD KEY `fk_course_meetings_chapter` (`chapter_id`);

--
-- Index pour la table `course_promotions`
--
ALTER TABLE `course_promotions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_course_promotions_coupon_code` (`coupon_code`),
  ADD KEY `idx_course_promotions_course` (`course_id`),
  ADD KEY `idx_course_promotions_teacher` (`teacher_id`),
  ADD KEY `idx_course_promotions_end` (`promo_end_at`),
  ADD KEY `idx_course_promotions_mode_end` (`promo_mode`,`promo_end_at`);

--
-- Index pour la table `course_promotion_company_targets`
--
ALTER TABLE `course_promotion_company_targets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_course_promo_company` (`promotion_id`,`company_id`),
  ADD KEY `idx_course_promo_company` (`company_id`);

--
-- Index pour la table `course_promotion_company_uses`
--
ALTER TABLE `course_promotion_company_uses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_course_promo_company_use` (`promotion_id`,`company_id`,`user_id`),
  ADD KEY `idx_course_promo_company_user` (`user_id`),
  ADD KEY `fk_course_promo_company_use_company` (`company_id`);

--
-- Index pour la table `course_promotion_targets`
--
ALTER TABLE `course_promotion_targets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_course_promotion_targets` (`promotion_id`,`user_id`),
  ADD KEY `idx_course_promotion_targets_user` (`user_id`);

--
-- Index pour la table `course_ratings`
--
ALTER TABLE `course_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rating_user_course` (`course_id`,`user_id`),
  ADD KEY `idx_rating_course` (`course_id`),
  ADD KEY `idx_rating_user` (`user_id`),
  ADD KEY `idx_rating_course_hidden` (`course_id`,`is_hidden_by_teacher`,`created_at`),
  ADD KEY `fk_rating_hidden_by_user` (`hidden_by_user_id`),
  ADD KEY `idx_rating_course_reply` (`course_id`,`teacher_reply_at`),
  ADD KEY `fk_rating_teacher_reply_user` (`teacher_reply_user_id`);

--
-- Index pour la table `course_reports`
--
ALTER TABLE `course_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reports_user` (`user_id`),
  ADD KEY `idx_reports_status` (`status`),
  ADD KEY `idx_reports_course` (`course_id`);

--
-- Index pour la table `course_views`
--
ALTER TABLE `course_views`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_course_views_course_viewer` (`course_id`,`viewer_key`),
  ADD KEY `idx_course_views_course` (`course_id`),
  ADD KEY `idx_course_views_user` (`user_id`);

--
-- Index pour la table `download_tokens`
--
ALTER TABLE `download_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_dl_tokens_user` (`user_id`),
  ADD KEY `idx_dl_tokens_exp` (`expires_at`);

--
-- Index pour la table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email_verif_token` (`token_hash`),
  ADD KEY `fk_email_verif_user` (`user_id`),
  ADD KEY `idx_email_verif_exp` (`expires_at`);

--
-- Index pour la table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_enrollment_user_course` (`user_id`,`course_id`),
  ADD KEY `fk_enrollments_course` (`course_id`),
  ADD KEY `idx_enrollments_status` (`status`);

--
-- Index pour la table `exam_answers`
--
ALTER TABLE `exam_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exam_answers_attempt` (`attempt_id`),
  ADD KEY `fk_exam_answers_question` (`question_id`),
  ADD KEY `fk_exam_answers_option` (`option_id`);

--
-- Index pour la table `exam_attempts`
--
ALTER TABLE `exam_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exam_attempts_exam` (`exam_id`),
  ADD KEY `fk_exam_attempts_user` (`user_id`),
  ADD KEY `fk_exam_attempts_grader` (`grader_user_id`),
  ADD KEY `idx_exam_attempts_meeting_id` (`meeting_id`),
  ADD KEY `idx_exam_attempts_variant` (`exam_id`,`user_id`,`variant_index`);

--
-- Index pour la table `exam_files`
--
ALTER TABLE `exam_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exam_files_attempt` (`attempt_id`);

--
-- Index pour la table `exam_meetings`
--
ALTER TABLE `exam_meetings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exam_meetings_exam` (`exam_id`),
  ADD KEY `fk_exam_meetings_user` (`booked_by`);

--
-- Index pour la table `exam_options`
--
ALTER TABLE `exam_options`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `exam_questions`
--
ALTER TABLE `exam_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_exam_questions_variant` (`exam_id`,`variant`,`order_index`);

--
-- Index pour la table `exam_sessions`
--
ALTER TABLE `exam_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `lesson_bookmarks`
--
ALTER TABLE `lesson_bookmarks`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `lesson_completions`
--
ALTER TABLE `lesson_completions`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_notifications_created` (`created_at`),
  ADD KEY `idx_notifications_popup_shown` (`user_id`,`is_read`,`popup_shown_at`,`id`),
  ADD KEY `idx_notifications_announcement` (`announcement_id`),
  ADD KEY `idx_notifications_importance` (`importance`),
  ADD KEY `idx_notifications_read_at` (`read_at`),
  ADD KEY `idx_notifications_archived` (`user_id`,`archived_at`,`created_at`);

--
-- Index pour la table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_items_promo_scope` (`promo_scope`),
  ADD KEY `idx_order_items_coupon_code` (`coupon_code`),
  ADD KEY `idx_order_items_promotion_id` (`promotion_id`),
  ADD KEY `fk_order_items_company` (`company_id`);

--
-- Index pour la table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `payment_webhook_events`
--
ALTER TABLE `payment_webhook_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_payment_webhook_events_provider_key` (`provider`,`event_key`),
  ADD KEY `idx_payment_webhook_events_processed` (`processed_at`);

--
-- Index pour la table `platform_settings`
--
ALTER TABLE `platform_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Index pour la table `push_notification_queue`
--
ALTER TABLE `push_notification_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_push_queue_status_next` (`status`,`next_attempt_at`),
  ADD KEY `idx_push_queue_user` (`user_id`),
  ADD KEY `idx_push_queue_notification` (`notification_id`);

--
-- Index pour la table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_push_subscriptions_endpoint_hash` (`endpoint_hash`),
  ADD KEY `idx_push_subscriptions_user_active` (`user_id`,`is_active`);

--
-- Index pour la table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `referral_commissions`
--
ALTER TABLE `referral_commissions`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`id`);

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
-- Index pour la table `support_conversation_reads`
--
ALTER TABLE `support_conversation_reads`
  ADD PRIMARY KEY (`conversation_id`,`user_id`),
  ADD KEY `fk_support_reads_user` (`user_id`);

--
-- Index pour la table `support_messages`
--
ALTER TABLE `support_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_support_msg_conv` (`conversation_id`,`id`),
  ADD KEY `idx_support_msg_sender` (`sender_id`);

--
-- Index pour la table `support_message_attachments`
--
ALTER TABLE `support_message_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_support_attach_msg` (`message_id`);

--
-- Index pour la table `teacher_applications`
--
ALTER TABLE `teacher_applications`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `teacher_payouts`
--
ALTER TABLE `teacher_payouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_teacher_payouts_teacher` (`teacher_id`),
  ADD KEY `idx_teacher_payouts_status` (`status`),
  ADD KEY `fk_teacher_payouts_admin` (`created_by`);

--
-- Index pour la table `teacher_profiles`
--
ALTER TABLE `teacher_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `uploads`
--
ALTER TABLE `uploads`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_referral_code` (`referral_code`),
  ADD KEY `idx_users_auth_provider` (`auth_provider`),
  ADD KEY `idx_users_mfa_enabled` (`mfa_enabled`);

--
-- Index pour la table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admin_announcements`
--
ALTER TABLE `admin_announcements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `admin_announcement_events`
--
ALTER TABLE `admin_announcement_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT pour la table `admin_secondary_permissions`
--
ALTER TABLE `admin_secondary_permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT pour la table `catalog_event_logs`
--
ALTER TABLE `catalog_event_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=189;

--
-- AUTO_INCREMENT pour la table `catalog_order_item_attributions`
--
ALTER TABLE `catalog_order_item_attributions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT pour la table `category_proposals`
--
ALTER TABLE `category_proposals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `chapter_documents`
--
ALTER TABLE `chapter_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `company_activity_events`
--
ALTER TABLE `company_activity_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `company_course_seat_assignments`
--
ALTER TABLE `company_course_seat_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `company_course_seat_purchases`
--
ALTER TABLE `company_course_seat_purchases`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `company_invites`
--
ALTER TABLE `company_invites`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `company_members`
--
ALTER TABLE `company_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `company_metrics_daily`
--
ALTER TABLE `company_metrics_daily`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `company_onboarding_tasks`
--
ALTER TABLE `company_onboarding_tasks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `company_sso_configs`
--
ALTER TABLE `company_sso_configs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `company_success_reviews`
--
ALTER TABLE `company_success_reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `company_teams`
--
ALTER TABLE `company_teams`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `company_team_members`
--
ALTER TABLE `company_team_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT pour la table `course_chapters`
--
ALTER TABLE `course_chapters`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT pour la table `course_favorites`
--
ALTER TABLE `course_favorites`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `course_lessons`
--
ALTER TABLE `course_lessons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT pour la table `course_meetings`
--
ALTER TABLE `course_meetings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `course_promotions`
--
ALTER TABLE `course_promotions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT pour la table `course_promotion_company_targets`
--
ALTER TABLE `course_promotion_company_targets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `course_promotion_company_uses`
--
ALTER TABLE `course_promotion_company_uses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `course_promotion_targets`
--
ALTER TABLE `course_promotion_targets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `course_ratings`
--
ALTER TABLE `course_ratings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `course_reports`
--
ALTER TABLE `course_reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `course_views`
--
ALTER TABLE `course_views`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=334;

--
-- AUTO_INCREMENT pour la table `download_tokens`
--
ALTER TABLE `download_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT pour la table `exam_answers`
--
ALTER TABLE `exam_answers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `exam_attempts`
--
ALTER TABLE `exam_attempts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `exam_files`
--
ALTER TABLE `exam_files`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `exam_meetings`
--
ALTER TABLE `exam_meetings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `exam_options`
--
ALTER TABLE `exam_options`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT pour la table `exam_questions`
--
ALTER TABLE `exam_questions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `exam_sessions`
--
ALTER TABLE `exam_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `lesson_bookmarks`
--
ALTER TABLE `lesson_bookmarks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lesson_completions`
--
ALTER TABLE `lesson_completions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=239;

--
-- AUTO_INCREMENT pour la table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT pour la table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT pour la table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT pour la table `payment_webhook_events`
--
ALTER TABLE `payment_webhook_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `push_notification_queue`
--
ALTER TABLE `push_notification_queue`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=198;

--
-- AUTO_INCREMENT pour la table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2820;

--
-- AUTO_INCREMENT pour la table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `referral_commissions`
--
ALTER TABLE `referral_commissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `student_profiles`
--
ALTER TABLE `student_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `support_conversations`
--
ALTER TABLE `support_conversations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `support_messages`
--
ALTER TABLE `support_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT pour la table `support_message_attachments`
--
ALTER TABLE `support_message_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `teacher_applications`
--
ALTER TABLE `teacher_applications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `teacher_payouts`
--
ALTER TABLE `teacher_payouts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `teacher_profiles`
--
ALTER TABLE `teacher_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `uploads`
--
ALTER TABLE `uploads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `category_proposals`
--
ALTER TABLE `category_proposals`
  ADD CONSTRAINT `fk_category_proposals_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `company_metrics_daily`
--
ALTER TABLE `company_metrics_daily`
  ADD CONSTRAINT `fk_company_metrics_daily_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `admin_announcements` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_items_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `course_promotions` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `push_notification_queue`
--
ALTER TABLE `push_notification_queue`
  ADD CONSTRAINT `fk_push_queue_notification` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_push_queue_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD CONSTRAINT `fk_push_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `support_conversations`
--
ALTER TABLE `support_conversations`
  ADD CONSTRAINT `fk_support_conv_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_support_conv_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `support_conversation_reads`
--
ALTER TABLE `support_conversation_reads`
  ADD CONSTRAINT `fk_support_reads_conv` FOREIGN KEY (`conversation_id`) REFERENCES `support_conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_support_reads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `support_messages`
--
ALTER TABLE `support_messages`
  ADD CONSTRAINT `fk_support_msg_conv` FOREIGN KEY (`conversation_id`) REFERENCES `support_conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_support_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `support_message_attachments`
--
ALTER TABLE `support_message_attachments`
  ADD CONSTRAINT `fk_support_attach_msg` FOREIGN KEY (`message_id`) REFERENCES `support_messages` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `teacher_payouts`
--
ALTER TABLE `teacher_payouts`
  ADD CONSTRAINT `fk_teacher_payouts_admin` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_teacher_payouts_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
