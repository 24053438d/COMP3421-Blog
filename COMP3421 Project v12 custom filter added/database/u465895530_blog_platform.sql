-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1:3306
-- 產生時間： 2025 年 04 月 14 日 07:54
-- 伺服器版本： 10.11.10-MariaDB
-- PHP 版本： 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `u465895530_blog_platform`
--

-- --------------------------------------------------------

--
-- 資料表結構 `analytics`
--

CREATE TABLE `analytics` (
  `id` int(11) NOT NULL,
  `page_url` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `analytics`
--

INSERT INTO `analytics` (`id`, `page_url`, `user_id`, `ip_address`, `created_at`) VALUES
(116, 'index.php', 2, '138.199.62.153', '2025-04-13 17:19:07'),
(117, 'view_post.php?id=2', 2, '138.199.62.153', '2025-04-13 17:19:08'),
(118, 'index.php', 2, '138.199.62.153', '2025-04-13 17:19:11'),
(119, 'view_post.php?id=1', 2, '138.199.62.153', '2025-04-13 17:19:12'),
(120, 'index.php', 2, '138.199.62.153', '2025-04-13 17:19:13'),
(121, 'index.php', 3, '138.199.62.153', '2025-04-13 17:19:26'),
(122, 'index.php', 3, '138.199.62.153', '2025-04-13 17:20:27'),
(123, 'index.php', NULL, '112.119.179.207', '2025-04-13 17:21:35'),
(124, 'index.php', NULL, '2a09:bac2:391b:16dc::247:4e', '2025-04-13 17:23:20'),
(125, 'index.php', NULL, '35.237.4.214', '2025-04-13 17:23:39'),
(126, 'index.php', NULL, '223.122.57.254', '2025-04-13 17:23:40'),
(127, 'index.php', NULL, '223.122.57.254', '2025-04-13 17:27:11'),
(128, 'index.php', NULL, '35.196.132.85', '2025-04-13 17:27:27'),
(129, 'index.php', NULL, '223.122.57.254', '2025-04-13 17:27:35'),
(130, 'index.php', 3, '223.122.57.254', '2025-04-13 17:27:48'),
(131, 'index.php', 3, '223.122.57.254', '2025-04-13 17:30:03'),
(132, 'index.php', 3, '223.122.57.254', '2025-04-13 17:31:29'),
(133, 'index.php', 3, '223.122.57.254', '2025-04-13 17:31:30'),
(134, 'index.php', 3, '223.122.57.254', '2025-04-13 17:31:46'),
(135, 'index.php', 4, '223.122.57.254', '2025-04-13 17:32:38'),
(136, 'index.php', NULL, '123.1.213.7', '2025-04-13 17:32:51'),
(137, 'index.php', 4, '223.122.57.254', '2025-04-13 17:33:43'),
(138, 'view_post.php?id=3', 4, '223.122.57.254', '2025-04-13 17:34:00'),
(139, 'index.php', 4, '223.122.57.254', '2025-04-13 17:34:02'),
(140, 'index.php', 3, '138.199.62.153', '2025-04-13 17:34:05'),
(141, 'view_post.php?id=3', 3, '138.199.62.153', '2025-04-13 17:34:07'),
(142, 'index.php', 4, '223.122.57.254', '2025-04-13 17:34:10'),
(143, 'index.php', 3, '138.199.62.153', '2025-04-13 17:34:11'),
(144, 'index.php', 3, '138.199.62.153', '2025-04-13 17:34:15'),
(145, 'view_post.php?id=3', 4, '223.122.57.254', '2025-04-13 17:34:15'),
(146, 'index.php', 4, '223.122.57.254', '2025-04-13 17:34:17'),
(147, 'view_post.php?id=2', 4, '223.122.57.254', '2025-04-13 17:34:18'),
(148, 'view_post.php?id=2', 4, '223.122.57.254', '2025-04-13 17:34:22'),
(149, 'index.php', 4, '223.122.57.254', '2025-04-13 17:34:26'),
(150, 'view_post.php?id=1', 4, '223.122.57.254', '2025-04-13 17:34:28'),
(151, 'view_post.php?id=1', 4, '223.122.57.254', '2025-04-13 17:34:34'),
(152, 'index.php', 4, '223.122.57.254', '2025-04-13 17:34:36'),
(153, 'view_post.php?id=2', 4, '223.122.57.254', '2025-04-13 17:34:37'),
(154, 'index.php', 4, '223.122.57.254', '2025-04-13 17:34:38'),
(155, 'index.php', 3, '223.122.57.254', '2025-04-13 17:35:07'),
(156, 'view_post.php?id=1', 3, '223.122.57.254', '2025-04-13 17:35:09'),
(157, 'view_post.php?id=2', 3, '223.122.57.254', '2025-04-13 17:35:13'),
(158, 'view_post.php?id=3', 3, '223.122.57.254', '2025-04-13 17:35:14'),
(159, 'view_post.php?id=3', 3, '223.122.57.254', '2025-04-13 17:35:22'),
(160, 'index.php', 3, '223.122.57.254', '2025-04-13 17:35:34'),
(161, 'view_post.php?id=3', 3, '223.122.57.254', '2025-04-13 17:35:35'),
(162, 'view_post.php?id=3', 3, '223.122.57.254', '2025-04-13 17:35:42'),
(163, 'view_post.php?id=3', 3, '223.122.57.254', '2025-04-13 17:35:49'),
(164, 'index.php', 3, '223.122.57.254', '2025-04-13 17:37:46'),
(165, 'index.php', 3, '223.122.57.254', '2025-04-13 17:43:02'),
(166, 'view_post.php?id=3', 3, '223.122.57.254', '2025-04-13 17:43:08'),
(167, 'index.php', NULL, '2a09:bac2:391a:16dc::247:12d', '2025-04-13 17:47:48'),
(168, 'index.php', 3, '2a09:bac2:391a:16dc::247:12d', '2025-04-13 17:48:12'),
(169, 'view_post.php?id=3', 3, '2a09:bac2:391a:16dc::247:12d', '2025-04-13 17:48:15'),
(170, 'index.php', 3, '2a09:bac2:391a:16dc::247:12d', '2025-04-13 17:48:22'),
(171, 'view_post.php?id=2', 3, '2a09:bac2:391a:16dc::247:12d', '2025-04-13 17:48:24'),
(172, 'index.php', 3, '2a09:bac2:391a:16dc::247:12d', '2025-04-13 17:48:25'),
(173, 'index.php', NULL, '112.119.179.207', '2025-04-13 17:49:00'),
(174, 'index.php', NULL, '192.175.111.248', '2025-04-13 17:51:15'),
(175, 'index.php', NULL, '192.175.111.236', '2025-04-13 17:51:16'),
(176, 'index.php', NULL, '64.15.129.123', '2025-04-13 17:51:16'),
(177, 'index.php', NULL, '64.15.129.114', '2025-04-13 17:51:16'),
(178, 'index.php', NULL, '192.175.111.239', '2025-04-13 17:51:16'),
(179, 'index.php', NULL, '192.175.111.249', '2025-04-13 17:51:17'),
(180, 'index.php', NULL, '64.15.129.122', '2025-04-13 17:51:17'),
(181, 'index.php', NULL, '192.175.111.238', '2025-04-13 17:51:17'),
(182, 'index.php', 3, '2a09:bac3:3919:16d2::246:a1', '2025-04-13 18:18:37'),
(183, 'index.php', 3, '2a09:bac3:3919:16d2::246:a1', '2025-04-13 18:18:47'),
(184, 'index.php', NULL, '54.174.140.70', '2025-04-13 19:05:27'),
(185, 'index.php', NULL, '54.174.140.70', '2025-04-13 19:05:54'),
(186, 'index.php', NULL, '135.148.100.196', '2025-04-13 19:46:08'),
(187, 'index.php', NULL, '104.232.194.41', '2025-04-13 19:56:36'),
(188, 'index.php', NULL, '104.232.194.167', '2025-04-13 19:58:09'),
(189, 'index.php', NULL, '85.254.140.36', '2025-04-13 19:58:58'),
(190, 'index.php', NULL, '54.174.140.70', '2025-04-13 20:48:43'),
(191, 'index.php', NULL, '54.174.140.70', '2025-04-13 20:49:11'),
(192, 'index.php', NULL, '3.220.101.67', '2025-04-14 00:52:53'),
(193, 'index.php', NULL, '3.220.101.67', '2025-04-14 00:52:57'),
(194, 'index.php', NULL, '2a09:bac2:391e:16dc::247:116', '2025-04-14 01:14:27'),
(195, 'index.php', 3, '2a09:bac2:391e:16dc::247:116', '2025-04-14 01:14:42'),
(196, 'index.php', NULL, '205.169.39.18', '2025-04-14 01:27:01'),
(197, 'index.php', NULL, '2600:3c0e::f03c:95ff:fe6a:55a8', '2025-04-14 02:02:19'),
(198, 'index.php', NULL, '209.242.213.96', '2025-04-14 02:08:37'),
(199, 'index.php', NULL, '94.139.233.80', '2025-04-14 02:08:37'),
(200, 'index.php', NULL, '94.139.234.43', '2025-04-14 02:10:07'),
(201, 'index.php', NULL, '154.30.97.106', '2025-04-14 02:10:10'),
(202, 'index.php', NULL, '154.30.98.229', '2025-04-14 02:10:29'),
(203, 'index.php', NULL, '154.28.229.105', '2025-04-14 02:19:44'),
(204, 'index.php', NULL, '2a09:bac3:391b:16dc::247:69', '2025-04-14 02:20:01'),
(205, 'index.php', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:26:28'),
(206, 'index.php', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:26:42'),
(207, 'index.php', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:26:42'),
(208, 'index.php', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:26:42'),
(209, 'index.php', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:26:43'),
(210, 'view_post.php?id=3', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:26:44'),
(211, 'view_post.php?id=3', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:26:44'),
(212, 'view_post.php?id=2', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:26:44'),
(213, 'view_post.php?id=2', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:26:44'),
(214, 'view_post.php?id=1', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:26:45'),
(215, 'view_post.php?id=1', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:26:45'),
(216, 'index.php', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:26:49'),
(217, 'index.php', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:26:57'),
(218, 'index.php', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:27:01'),
(219, 'index.php', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:27:04'),
(220, 'index.php', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:27:10'),
(221, 'index.php', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:27:15'),
(222, 'view_post.php?id=3', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:30:08'),
(223, 'view_post.php?id=3', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:30:13'),
(224, 'view_post.php?id=2', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:30:54'),
(225, 'view_post.php?id=2', NULL, '2402:1f00:8000:800::35f2', '2025-04-14 02:30:59'),
(226, 'index.php', NULL, '18.236.250.1', '2025-04-14 02:46:16'),
(227, 'index.php', NULL, '18.236.250.1', '2025-04-14 02:46:16'),
(228, 'index.php', NULL, '34.44.94.221', '2025-04-14 02:47:00'),
(229, 'index.php', NULL, '52.36.11.39', '2025-04-14 02:51:45'),
(230, 'index.php', NULL, '52.36.11.39', '2025-04-14 02:51:45'),
(231, 'index.php', NULL, '44.244.71.83', '2025-04-14 02:55:30'),
(232, 'index.php', NULL, '44.244.71.83', '2025-04-14 02:55:32'),
(233, 'index.php', NULL, '138.199.62.153', '2025-04-14 03:23:31'),
(234, 'index.php', NULL, '138.199.62.153', '2025-04-14 03:23:31'),
(235, 'index.php', NULL, '34.222.219.179', '2025-04-14 03:28:20'),
(236, 'index.php', NULL, '34.222.219.179', '2025-04-14 03:28:24'),
(237, 'index.php', NULL, '2a02:4780:2c:3::5', '2025-04-14 03:30:36'),
(238, 'index.php', NULL, '54.184.79.72', '2025-04-14 03:31:51'),
(239, 'index.php', NULL, '54.184.79.72', '2025-04-14 03:31:55'),
(240, 'index.php', NULL, '52.20.19.158', '2025-04-14 04:54:32'),
(241, 'index.php', NULL, '3.220.101.67', '2025-04-14 04:54:36'),
(242, 'index.php', NULL, '146.75.187.11', '2025-04-14 05:48:41'),
(243, 'index.php', 3, '146.75.187.11', '2025-04-14 05:48:58'),
(244, 'index.php', NULL, '89.104.100.4', '2025-04-14 06:08:15'),
(245, 'index.php', NULL, '52.203.121.21', '2025-04-14 06:50:11'),
(246, 'index.php', NULL, '192.36.109.84', '2025-04-14 06:50:24'),
(247, 'index.php', NULL, '2a09:bac2:3918:2646::3d0:6', '2025-04-14 07:21:49'),
(248, 'index.php', NULL, '2a09:bac2:391c:16dc::247:10c', '2025-04-14 07:46:32');

-- --------------------------------------------------------

--
-- 資料表結構 `analytics_events`
--

CREATE TABLE `analytics_events` (
  `id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `event_data` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `page_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `analytics_events`
--

INSERT INTO `analytics_events` (`id`, `event_type`, `event_data`, `user_id`, `ip_address`, `page_url`, `created_at`) VALUES
(1, 'post_submit', '{\"post_title\":\"Test 2\",\"post_status\":\"published\",\"user_id\":2}', 2, '::1', '/COMP3421%20Project/create_post.php', '2025-04-13 14:14:25'),
(2, 'post_submit', '{\"post_title\":\"assss\",\"post_status\":\"draft\",\"user_id\":4}', 4, '223.122.57.254', '/create_post.php', '2025-04-13 17:33:42'),
(3, 'comment_submit', '{\"post_id\":2,\"user_id\":4}', 4, '223.122.57.254', '/view_post.php?id=2', '2025-04-13 17:34:22'),
(4, 'comment_submit', '{\"post_id\":1,\"user_id\":4}', 4, '223.122.57.254', '/view_post.php?id=1', '2025-04-13 17:34:34'),
(5, 'comment_submit', '{\"post_id\":3,\"user_id\":3}', 3, '223.122.57.254', '/view_post.php?id=3', '2025-04-13 17:35:42');

-- --------------------------------------------------------

--
-- 資料表結構 `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `content` text NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `comments`
--

INSERT INTO `comments` (`id`, `content`, `post_id`, `user_id`, `created_at`, `status`) VALUES
(1, 'byeeeeeeeeeee', 1, 2, '2025-04-03 14:21:12', 'approved'),
(2, 'hi', 2, 4, '2025-04-13 17:34:22', 'pending'),
(3, 'hiiiiiiiiiiiiiiii', 1, 4, '2025-04-13 17:34:34', 'approved'),
(4, 'admin test', 3, 3, '2025-04-13 17:35:42', 'approved');

-- --------------------------------------------------------

--
-- 資料表結構 `geo_data`
--

CREATE TABLE `geo_data` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `country_code` varchar(2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `geo_data`
--

INSERT INTO `geo_data` (`id`, `ip_address`, `country_code`, `created_at`) VALUES
(1, '138.199.62.153', 'UN', '2025-04-13 17:20:29'),
(2, '112.119.179.207', 'UN', '2025-04-13 17:21:51'),
(3, '2a09:bac2:391b:16dc::247:4e', 'UN', '2025-04-13 17:25:27'),
(4, '35.237.4.214', 'UN', '2025-04-13 17:25:27'),
(5, '223.122.57.254', 'UN', '2025-04-13 17:25:27'),
(6, '35.196.132.85', 'UN', '2025-04-13 17:27:51'),
(7, '88.122.241.122', 'UN', '2025-04-13 17:29:40'),
(8, '123.1.213.7', 'UN', '2025-04-13 17:33:02'),
(9, '2a09:bac2:391a:16dc::247:12d', 'UN', '2025-04-13 17:48:29'),
(10, '192.175.111.248', 'UN', '2025-04-13 18:18:40'),
(11, '192.175.111.236', 'UN', '2025-04-13 18:18:40'),
(12, '64.15.129.123', 'UN', '2025-04-13 18:18:40'),
(13, '64.15.129.114', 'UN', '2025-04-13 18:18:40'),
(14, '192.175.111.239', 'UN', '2025-04-13 18:18:40'),
(15, '192.175.111.249', 'UN', '2025-04-13 18:18:40'),
(16, '64.15.129.122', 'UN', '2025-04-13 18:18:40'),
(17, '192.175.111.238', 'UN', '2025-04-13 18:18:40'),
(18, '2a09:bac3:3919:16d2::246:a1', 'UN', '2025-04-13 18:18:40'),
(19, '54.174.140.70', 'UN', '2025-04-14 01:14:46'),
(20, '135.148.100.196', 'UN', '2025-04-14 01:14:46'),
(21, '104.232.194.41', 'UN', '2025-04-14 01:14:46'),
(22, '104.232.194.167', 'UN', '2025-04-14 01:14:46'),
(23, '85.254.140.36', 'UN', '2025-04-14 01:14:46'),
(24, '3.220.101.67', 'UN', '2025-04-14 01:14:46'),
(25, '2a09:bac2:391e:16dc::247:116', 'UN', '2025-04-14 01:14:46'),
(26, '205.169.39.18', 'UN', '2025-04-14 05:49:01'),
(27, '2600:3c0e::f03c:95ff:fe6a:55a8', 'UN', '2025-04-14 05:49:01'),
(28, '209.242.213.96', 'UN', '2025-04-14 05:49:01'),
(29, '94.139.233.80', 'UN', '2025-04-14 05:49:01'),
(30, '94.139.234.43', 'UN', '2025-04-14 05:49:01'),
(31, '154.30.97.106', 'UN', '2025-04-14 05:49:01'),
(32, '154.30.98.229', 'UN', '2025-04-14 05:49:01'),
(33, '154.28.229.105', 'UN', '2025-04-14 05:49:01'),
(34, '2a09:bac3:391b:16dc::247:69', 'UN', '2025-04-14 05:49:01'),
(35, '2402:1f00:8000:800::35f2', 'UN', '2025-04-14 05:49:01'),
(36, '18.236.250.1', 'UN', '2025-04-14 05:49:01'),
(37, '34.44.94.221', 'UN', '2025-04-14 05:49:01'),
(38, '52.36.11.39', 'UN', '2025-04-14 05:49:01'),
(39, '44.244.71.83', 'UN', '2025-04-14 05:49:01'),
(40, '34.222.219.179', 'UN', '2025-04-14 05:49:01'),
(41, '2a02:4780:2c:3::5', 'UN', '2025-04-14 05:49:01'),
(42, '54.184.79.72', 'UN', '2025-04-14 05:49:01'),
(43, '52.20.19.158', 'UN', '2025-04-14 05:49:01'),
(44, '146.75.187.11', 'UN', '2025-04-14 05:49:01');

-- --------------------------------------------------------

--
-- 資料表結構 `performance_metrics`
--

CREATE TABLE `performance_metrics` (
  `id` int(11) NOT NULL,
  `page_url` varchar(255) NOT NULL,
  `load_time` float NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `performance_metrics`
--

INSERT INTO `performance_metrics` (`id`, `page_url`, `load_time`, `user_id`, `ip_address`, `created_at`) VALUES
(1, '/', 0.801, NULL, '112.119.179.207', '2025-04-13 17:21:36'),
(2, '/', 1.086, NULL, '2a09:bac2:391b:16dc::247:4e', '2025-04-13 17:23:21'),
(3, '/', 1.6744, NULL, '223.122.57.254', '2025-04-13 17:23:41'),
(4, '/', 1.2722, NULL, '223.122.57.254', '2025-04-13 17:27:12'),
(5, '/login.php', 0.2405, NULL, '223.122.57.254', '2025-04-13 17:27:13'),
(6, '/', 1.3344, NULL, '223.122.57.254', '2025-04-13 17:27:35'),
(7, '/login.php', 0.2314, NULL, '223.122.57.254', '2025-04-13 17:27:37'),
(8, '/index.php', 0.5079, NULL, '223.122.57.254', '2025-04-13 17:27:49'),
(9, '/admin/analytics.php', 0.3001, NULL, '223.122.57.254', '2025-04-13 17:27:51'),
(10, '/admin/dashboard.php', 0.2318, NULL, '223.122.57.254', '2025-04-13 17:27:52'),
(11, '/admin/debug_analytics.php', 0.4419, NULL, '223.122.57.254', '2025-04-13 17:28:16'),
(12, 'debug_analytics.php', 3.06, 3, '223.122.57.254', '2025-04-13 17:28:35'),
(13, '/my_posts.php', 0.2179, NULL, '223.122.57.254', '2025-04-13 17:29:57'),
(14, '/create_post.php', 0.2169, NULL, '223.122.57.254', '2025-04-13 17:31:47'),
(15, '/register.php', 0.2164, NULL, '223.122.57.254', '2025-04-13 17:31:53'),
(16, '/', 0.8228, NULL, '123.1.213.7', '2025-04-13 17:32:52'),
(17, '/edit_post.php', 0.2226, NULL, '223.122.57.254', '2025-04-13 17:33:47'),
(18, '/view_post.php', 0.233, NULL, '223.122.57.254', '2025-04-13 17:34:00'),
(19, '/admin/analytics.php', 1.096, NULL, '112.119.179.207', '2025-04-13 17:43:53'),
(20, '/', 0.981, NULL, '2a09:bac2:391a:16dc::247:12d', '2025-04-13 17:47:48'),
(21, '/login.php', 0.375, NULL, '2a09:bac2:391a:16dc::247:12d', '2025-04-13 17:47:54'),
(22, '/index.php', 0.676, NULL, '2a09:bac2:391a:16dc::247:12d', '2025-04-13 17:48:12'),
(23, '/view_post.php', 0.286, NULL, '2a09:bac2:391a:16dc::247:12d', '2025-04-13 17:48:16'),
(24, '/admin/analytics.php', 0.362, NULL, '2a09:bac2:391a:16dc::247:12d', '2025-04-13 17:48:29'),
(25, '/admin/debug_analytics.php', 0.319, NULL, '2a09:bac3:3919:16d2::246:a1', '2025-04-13 18:18:54'),
(26, '/', 0.276, NULL, '54.174.140.70', '2025-04-13 19:05:54'),
(27, '/', 8.5218, NULL, '85.254.140.36', '2025-04-13 19:59:02'),
(28, '/', 0.4295, NULL, '54.174.140.70', '2025-04-13 20:49:11'),
(29, '/', 0.2273, NULL, '3.220.101.67', '2025-04-14 00:52:57'),
(30, '/', 1.004, NULL, '2a09:bac2:391e:16dc::247:116', '2025-04-14 01:14:27'),
(31, '/login.php', 0.324, NULL, '2a09:bac2:391e:16dc::247:116', '2025-04-14 01:14:30'),
(32, '/index.php', 0.66, NULL, '2a09:bac2:391e:16dc::247:116', '2025-04-14 01:14:42'),
(33, '/admin/analytics.php', 0.334, NULL, '2a09:bac2:391e:16dc::247:116', '2025-04-14 01:14:47'),
(34, '/', 1.9837, NULL, '209.242.213.96', '2025-04-14 02:08:38'),
(35, '/', 2.0686, NULL, '94.139.233.80', '2025-04-14 02:08:38'),
(36, '/', 2.2526, NULL, '94.139.234.43', '2025-04-14 02:10:08'),
(37, '/', 0.5432, NULL, '154.30.97.106', '2025-04-14 02:10:10'),
(38, '/', 1.3726, NULL, '154.30.98.229', '2025-04-14 02:10:31'),
(39, '/index.php', 1.281, NULL, '2a09:bac3:391b:16dc::247:69', '2025-04-14 02:20:02'),
(40, '/', 0.4471, NULL, '44.244.71.83', '2025-04-14 02:55:30'),
(41, '/', 0.433, NULL, '44.244.71.83', '2025-04-14 02:55:33'),
(42, '/index.php', 25.765, NULL, '138.199.62.153', '2025-04-14 03:23:56'),
(43, '/', 25.5786, NULL, '138.199.62.153', '2025-04-14 03:23:56'),
(44, '/', 0.5892, NULL, '34.222.219.179', '2025-04-14 03:28:20'),
(45, '/', 0.5556, NULL, '34.222.219.179', '2025-04-14 03:28:24'),
(46, '/', 0.8171, NULL, '54.184.79.72', '2025-04-14 03:31:52'),
(47, '/', 0.6475, NULL, '54.184.79.72', '2025-04-14 03:31:55'),
(48, '/', 0.5968, NULL, '3.220.101.67', '2025-04-14 04:54:37'),
(49, '/', 2.483, NULL, '146.75.187.11', '2025-04-14 05:48:41'),
(50, '/login.php', 0.271, NULL, '146.75.187.11', '2025-04-14 05:48:50'),
(51, '/index.php', 0.887, NULL, '146.75.187.11', '2025-04-14 05:48:59'),
(52, '/admin/analytics.php', 0.672, NULL, '146.75.187.11', '2025-04-14 05:49:01'),
(53, '/admin/debug_analytics.php', 0.587, NULL, '146.75.187.11', '2025-04-14 05:49:16'),
(54, '/', 0.2341, NULL, '52.203.121.21', '2025-04-14 06:50:11'),
(55, '/index.php', 1.355, NULL, '2a09:bac2:3918:2646::3d0:6', '2025-04-14 07:21:49'),
(56, '/index.php', 1.091, NULL, '2a09:bac2:391c:16dc::247:10c', '2025-04-14 07:46:32');

-- --------------------------------------------------------

--
-- 資料表結構 `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('draft','published','archived') DEFAULT 'draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `posts`
--

INSERT INTO `posts` (`id`, `title`, `content`, `user_id`, `created_at`, `updated_at`, `status`) VALUES
(1, 'Post 101', 'hiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiii', 1, '2025-04-03 14:12:38', '2025-04-03 14:12:52', 'published'),
(2, 'Test 2', 'Testing 2222', 2, '2025-04-13 14:14:25', '2025-04-13 14:14:25', 'published'),
(3, 'assss', 'asssssssss', 4, '2025-04-13 17:33:42', '2025-04-13 17:34:14', 'published');

-- --------------------------------------------------------

--
-- 資料表結構 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 傾印資料表的資料 `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `reset_token`, `reset_token_expiry`) VALUES
(1, 'alfred', 'alfred@gmail.com', '$2y$10$NjL4uTFEr.szMYLvNE56ZO1T8MSlQdhidG/Tp6glhgjRlvq51auum', 'user', '2025-04-03 14:08:41', 'ca9f4339962b47e5c691094c57f00fc9edc54d66b23d7259921bf6d6e86f550c', '2025-04-03 17:17:29'),
(2, 'user123', 'user123@gmail.com', '$2y$10$3biC8zSQvRcsHS5P3IW6sOM.TRdYujg5j4A1KMFLQzAuw75tSAGJe', 'user', '2025-04-03 14:20:56', NULL, NULL),
(3, 'admin123', 'admin123@gmail.com', '$2y$10$kWOLUBC.OnX6eqDcyVnmG.MU7cDU06cZrNwTjfkLV55fbiTXDSVf6', 'admin', '2025-04-03 14:27:41', NULL, NULL),
(4, 'ass', 'ass@as.com', '$2y$10$Ze3YrCby8mQeQoU6JcVAYOYMtPchQdBe2975QMPn3XAFWSIM5FgEi', 'user', '2025-04-13 17:32:26', NULL, NULL);

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `analytics`
--
ALTER TABLE `analytics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 資料表索引 `analytics_events`
--
ALTER TABLE `analytics_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 資料表索引 `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- 資料表索引 `geo_data`
--
ALTER TABLE `geo_data`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip_address` (`ip_address`);

--
-- 資料表索引 `performance_metrics`
--
ALTER TABLE `performance_metrics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 資料表索引 `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 資料表索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `analytics`
--
ALTER TABLE `analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=249;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `analytics_events`
--
ALTER TABLE `analytics_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `geo_data`
--
ALTER TABLE `geo_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `performance_metrics`
--
ALTER TABLE `performance_metrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `analytics`
--
ALTER TABLE `analytics`
  ADD CONSTRAINT `analytics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- 資料表的限制式 `analytics_events`
--
ALTER TABLE `analytics_events`
  ADD CONSTRAINT `analytics_events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- 資料表的限制式 `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`),
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- 資料表的限制式 `performance_metrics`
--
ALTER TABLE `performance_metrics`
  ADD CONSTRAINT `performance_metrics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- 資料表的限制式 `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
