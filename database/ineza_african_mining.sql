-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 24, 2026 at 05:11 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ineza_african_mining`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `account_type_id` int(11) NOT NULL,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `account_types`
--

CREATE TABLE `account_types` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_editable` tinyint(1) DEFAULT 0,
  `is_deletable` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account_types`
--

INSERT INTO `account_types` (`id`, `code`, `name`, `parent_id`, `is_editable`, `is_deletable`, `created_at`, `updated_at`) VALUES
(-6, '6000', 'Operating Expenses', NULL, 0, 0, '2026-06-24 02:50:00', '2026-06-24 02:50:00'),
(-5, '5000', 'Cost of Sales', NULL, 0, 0, '2026-06-24 02:50:00', '2026-06-24 02:50:00'),
(-4, '4000', 'Revenue', NULL, 0, 0, '2026-06-24 02:50:00', '2026-06-24 02:50:00'),
(-3, '3000', 'Equity', NULL, 0, 0, '2026-06-24 02:50:00', '2026-06-24 02:50:00'),
(-2, '2000', 'Liabilities', NULL, 0, 0, '2026-06-24 02:50:00', '2026-06-24 02:50:00'),
(-1, '1000', 'Assets', NULL, 0, 0, '2026-06-24 02:50:00', '2026-06-24 02:50:00'),
(3, '2001', 'Accounts Payable', -2, 1, 1, '2026-06-24 02:53:18', '2026-06-24 02:53:18');

-- --------------------------------------------------------

--
-- Table structure for table `attachments`
--

CREATE TABLE `attachments` (
  `id` bigint(20) NOT NULL,
  `table_namee` varchar(100) DEFAULT NULL,
  `record_id` bigint(20) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_by` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_full_name` varchar(200) NOT NULL COMMENT 'Full name of the user who performed the action',
  `action` varchar(50) NOT NULL COMMENT 'Action performed e.g. CREATE, UPDATE, DELETE, VIEW, APPROVE, PRINT, EXPORT, LOGIN, LOGOUT',
  `target_table` varchar(100) NOT NULL COMMENT 'Table affected',
  `target_name` varchar(255) DEFAULT NULL COMMENT 'Human-readable name of the affected record',
  `target_description` text DEFAULT NULL COMMENT 'Summary of the affected record',
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Values before change (for UPDATE/DELETE)' CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Values after change (for CREATE/UPDATE)' CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `performed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Complete immutable audit trail - every action by every user is logged here';

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_full_name`, `action`, `target_table`, `target_name`, `target_description`, `old_values`, `new_values`, `ip_address`, `user_agent`, `session_id`, `notes`, `performed_at`) VALUES
(1, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:01:08'),
(2, 'Super Admin', 'CREATE', 'currencies', 'RWF', 'Created new currency: RWANDAN FRANGS (RWF)', NULL, '{\"id\":1,\"code\":\"RWF\",\"name\":\"RWANDAN FRANGS\",\"symbol\":\"Rwf\",\"is_base_currency\":0,\"is_active\":1,\"created_by\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:01:30'),
(3, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:01:30'),
(4, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:01:35'),
(5, 'Super Admin', 'UPDATE', 'currencies', 'RWF', 'Updated currency: RWANDAN FRANGS (RWF)', '{\"id\":\"1\",\"code\":\"RWF\",\"name\":\"RWANDAN FRANGS\",\"symbol\":\"Rwf\",\"is_base_currency\":\"0\",\"is_active\":\"1\",\"created_at\":\"2026-06-17 00:01:30\",\"created_by\":\"2\",\"updated_at\":\"2026-06-17 00:01:30\",\"updated_by\":null}', '{\"id\":1,\"code\":\"RWF\",\"name\":\"RWANDAN FRANGS\",\"symbol\":\"Rwf\",\"is_base_currency\":1,\"is_active\":1,\"updated_by\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:01:55'),
(6, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:01:55'),
(7, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:02:25'),
(8, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:02:57'),
(9, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:02:58'),
(10, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:02:58'),
(11, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:02:58'),
(12, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:02:59'),
(13, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:02:59'),
(14, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:02:59'),
(15, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:02:59'),
(16, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:02:59'),
(17, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:03:00'),
(18, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:03:00'),
(19, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:14:06'),
(20, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:14:09'),
(21, 'Super Admin', 'UPDATE', 'currencies', 'RWF', 'Updated currency: RWANDAN FRANGS (RWF)', '{\"id\":\"1\",\"code\":\"RWF\",\"name\":\"RWANDAN FRANGS\",\"symbol\":\"Rwf\",\"is_base_currency\":\"1\",\"is_active\":\"1\",\"created_at\":\"2026-06-17 00:01:30\",\"created_by\":\"2\",\"updated_at\":\"2026-06-17 00:01:55\",\"updated_by\":\"2\"}', '{\"id\":1,\"code\":\"RWF\",\"name\":\"RWANDAN FRANGS\",\"symbol\":\"Rwg\",\"is_base_currency\":1,\"is_active\":1,\"updated_by\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:14:15'),
(22, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:14:15'),
(23, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:14:18'),
(24, 'Super Admin', 'UPDATE', 'currencies', 'RWF', 'Updated currency: RWANDAN FRANGS (RWF)', '{\"id\":\"1\",\"code\":\"RWF\",\"name\":\"RWANDAN FRANGS\",\"symbol\":\"Rwg\",\"is_base_currency\":\"1\",\"is_active\":\"1\",\"created_at\":\"2026-06-17 00:01:30\",\"created_by\":\"2\",\"updated_at\":\"2026-06-17 00:14:15\",\"updated_by\":\"2\"}', '{\"id\":1,\"code\":\"RWF\",\"name\":\"RWANDAN FRANGS\",\"symbol\":\"Rwf\",\"is_base_currency\":1,\"is_active\":1,\"updated_by\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:14:25'),
(25, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:14:25'),
(26, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:18:06'),
(27, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:20:42'),
(28, 'Super Admin', 'CREATE', 'currencies', 'USD', 'Created new currency: Us Dorall (USD)', NULL, '{\"id\":2,\"code\":\"USD\",\"name\":\"Us Dorall\",\"symbol\":\"$\",\"is_base_currency\":1,\"is_active\":1,\"created_by\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:21:06'),
(29, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:21:06'),
(30, 'Super Admin', 'UPDATE', 'currencies', 'RWF', 'Updated currency: RWANDAN FRANGS (RWF)', '{\"id\":\"1\",\"code\":\"RWF\",\"name\":\"RWANDAN FRANGS\",\"symbol\":\"Rwf\",\"is_base_currency\":\"0\",\"is_active\":\"1\",\"created_at\":\"2026-06-17 00:01:30\",\"created_by\":\"2\",\"updated_at\":\"2026-06-17 00:21:06\",\"updated_by\":\"2\"}', '{\"id\":1,\"code\":\"RWF\",\"name\":\"RWANDAN FRANGS\",\"symbol\":\"Rwf\",\"is_base_currency\":1,\"is_active\":1,\"updated_by\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:21:17'),
(31, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:21:17'),
(32, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:31:26'),
(33, 'Super Admin', 'CREATE', 'exchange_rates', 'USD>RWF', 'Created rate: 1 USD = 14500 RWF', NULL, '{\"id\":1,\"from_currency_id\":2,\"to_currency_id\":1,\"from_code\":\"USD\",\"to_code\":\"RWF\",\"rate\":\"14500\",\"rate_date\":\"2026-06-17\",\"source\":\"\",\"created_by\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:31:54'),
(34, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:31:54'),
(35, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:32:08'),
(36, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:33:19'),
(37, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:33:20'),
(38, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:33:20'),
(39, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:33:20'),
(40, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:33:21'),
(41, 'Super Admin', 'DELETE', 'exchange_rates', 'USD>RWF', 'Deleted rate: 1 USD = 14500.00000000 RWF', '{\"id\":\"1\",\"from_currency_id\":\"2\",\"to_currency_id\":\"1\",\"rate\":\"14500.00000000\",\"rate_date\":\"2026-06-17\",\"source\":\"\",\"created_by\":\"2\",\"created_at\":\"2026-06-17 00:31:54\",\"updated_by\":null,\"updated_at\":\"2026-06-17 00:31:54\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:33:26'),
(42, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:33:26'),
(43, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:54:14'),
(44, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:54:16'),
(45, 'Super Admin', 'DELETE', 'users', 'Super Admin', 'Deleted user: admin@inezamining.rw', '{\"id\":\"4\",\"first_name\":\"Super\",\"last_name\":\"Admin\",\"img\":null,\"phone_number\":null,\"email\":\"admin@inezamining.rw\",\"password_hash\":\"$2y$10$mFeSPQsbadECqqL7hMfBCeCIT.qiPk6yfoUa1MFjZLYbwLMMOSr8y\",\"is_active\":\"1\",\"created_at\":\"2026-06-14 22:47:02\",\"updated_at\":\"2026-06-14 22:47:02\",\"role_id\":\"1\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:54:29'),
(46, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:54:29'),
(47, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:54:34'),
(48, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 22:58:47'),
(49, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:00:40'),
(50, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:00:57'),
(51, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:01:13'),
(52, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:06:03'),
(53, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:06:30'),
(54, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:06:32'),
(55, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:06:44'),
(56, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:08:49'),
(57, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:08:50'),
(58, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:08:52'),
(59, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:08:53'),
(60, 'Super Admin', 'CREATE', 'roles', 'secretary', 'Created role: secretary', NULL, '{\"id\":2,\"name\":\"secretary\",\"description\":\"all add\",\"permissions\":[10,11,7]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:10:23'),
(61, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:10:23'),
(62, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:13:04'),
(63, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:13:05'),
(64, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:13:08'),
(65, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:13:09'),
(66, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:13:10'),
(67, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:19:00'),
(68, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:19:20'),
(69, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:19:47'),
(70, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:21:22'),
(71, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:21:23'),
(72, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:22:10'),
(73, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:22:22'),
(74, 'Super Admin', 'CREATE', 'permissions', 'ne', 'Created permission: eugene', NULL, '{\"id\":17,\"name\":\"eugene\",\"code\":\"ne\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:23:07'),
(75, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:23:07'),
(76, 'Super Admin', 'DELETE', 'permissions', 'ne', 'Deleted permission: eugene', '{\"id\":\"17\",\"permition_name\":\"eugene\",\"permition_code\":\"ne\",\"created_at\":\"2026-06-17 01:23:07\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:23:19'),
(77, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:23:19'),
(78, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:29:56'),
(79, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:29:58'),
(80, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:30:37'),
(81, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:33:26'),
(82, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:33:28'),
(83, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:33:43'),
(84, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:33:46'),
(85, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:42:04'),
(86, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:42:07'),
(87, 'Super Admin', 'CREATE', 'products', 'SN', 'Created product: Tin', NULL, '{\"id\":1,\"code\":\"SN\",\"name\":\"Tin\",\"full_name\":\"Cass\",\"unit_of_measure\":\"kg\",\"description\":\"\",\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:43:32'),
(88, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:43:32'),
(89, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:45:07'),
(90, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:51:34'),
(91, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:51:35'),
(92, 'Test User', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '127.0.0.1', 'CLI-Test-Suite', 'bfnkndkgnn5h98pfdl50pshosi', NULL, '2026-06-16 23:52:11'),
(93, 'Test User', 'CREATE', 'product_elements', 'TEST%', 'Created product element: Test Element for product SN', NULL, '{\"id\":1,\"product_id\":1,\"element_code\":\"TEST%\",\"element_name\":\"Test Element\",\"unit\":\"%\",\"display_order\":10,\"product_code\":\"SN\",\"product_name\":\"Tin\"}', '127.0.0.1', 'CLI-Test-Suite', 'iavmlvknhaonducmh79jl8cjb9', NULL, '2026-06-16 23:52:12'),
(94, 'Test User', 'UPDATE', 'product_elements', 'TEST%', 'Updated product element: Updated Test Element for product SN', '{\"id\":\"1\",\"product_id\":\"1\",\"element_code\":\"TEST%\",\"element_name\":\"Test Element\",\"unit\":\"%\",\"display_order\":\"10\",\"created_at\":\"2026-06-17 01:52:12\",\"created_by\":\"2\",\"updated_at\":\"2026-06-17 01:52:12\",\"updated_by\":null}', '{\"id\":1,\"product_id\":1,\"element_code\":\"TEST%\",\"element_name\":\"Updated Test Element\",\"unit\":\"ppm\",\"display_order\":20,\"product_code\":\"SN\",\"product_name\":\"Tin\"}', '127.0.0.1', 'CLI-Test-Suite', 'kqcdt25gboqvtquqefd4t37fdt', NULL, '2026-06-16 23:52:12'),
(95, 'Test User', 'DELETE', 'product_elements', 'TEST%', 'Deleted product element: Updated Test Element for product SN', '{\"id\":\"1\",\"product_id\":\"1\",\"element_code\":\"TEST%\",\"element_name\":\"Updated Test Element\",\"unit\":\"ppm\",\"display_order\":\"20\",\"created_at\":\"2026-06-17 01:52:12\",\"created_by\":\"2\",\"updated_at\":\"2026-06-17 01:52:12\",\"updated_by\":\"2\",\"product_code\":\"SN\"}', NULL, '127.0.0.1', 'CLI-Test-Suite', '9km7u3dfciaf6p1jeo9lt5ec3p', NULL, '2026-06-16 23:52:13'),
(96, 'Super Admin', 'CREATE', 'product_elements', 'Sn', 'Created product element: iron for product SN', NULL, '{\"id\":2,\"product_id\":1,\"element_code\":\"Sn\",\"element_name\":\"iron\",\"unit\":\"12\",\"display_order\":0,\"product_code\":\"SN\",\"product_name\":\"Tin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:52:32'),
(97, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:52:32'),
(98, 'Super Admin', 'UPDATE', 'product_elements', 'Sn', 'Updated product element: iron for product SN', '{\"id\":\"2\",\"product_id\":\"1\",\"element_code\":\"Sn\",\"element_name\":\"iron\",\"unit\":\"12\",\"display_order\":\"0\",\"created_at\":\"2026-06-17 01:52:32\",\"created_by\":\"2\",\"updated_at\":\"2026-06-17 01:52:32\",\"updated_by\":null}', '{\"id\":2,\"product_id\":1,\"element_code\":\"Sn\",\"element_name\":\"iron\",\"unit\":\"%12\",\"display_order\":0,\"product_code\":\"SN\",\"product_name\":\"Tin\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:52:48'),
(99, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:52:48'),
(100, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:52:56'),
(101, 'Super Admin', 'DELETE', 'product_elements', 'Sn', 'Deleted product element: iron for product SN', '{\"id\":\"2\",\"product_id\":\"1\",\"element_code\":\"Sn\",\"element_name\":\"iron\",\"unit\":\"%12\",\"display_order\":\"0\",\"created_at\":\"2026-06-17 01:52:32\",\"created_by\":\"2\",\"updated_at\":\"2026-06-17 01:52:48\",\"updated_by\":\"2\",\"product_code\":\"SN\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:53:00'),
(102, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:53:00'),
(103, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:53:02'),
(104, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:58:38'),
(105, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-16 23:58:39'),
(106, 'Test User', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '127.0.0.1', 'CLI-Test-Suite', 'rmchcofjdm199ik5759rvtqhnc', NULL, '2026-06-16 23:58:57'),
(107, 'Test User', 'CREATE', 'suppliers', 'Test Supplier Coop', 'Created supplier: Test Supplier Coop (cooperative)', NULL, '{\"id\":1,\"supplier_type\":\"cooperative\",\"name\":\"Test Supplier Coop\",\"nif\":\"999999999\",\"vat_reg_no\":\"VAT-99999\",\"phone\":\"+250788111222\",\"email\":\"coop@test.com\",\"address\":\"Test Address\",\"region\":\"Western Province\",\"is_active\":1,\"notes\":\"Temporary test notes\"}', '127.0.0.1', 'CLI-Test-Suite', 'de5vsits2ktco4kp717rdpder0', NULL, '2026-06-16 23:58:58'),
(108, 'Test User', 'UPDATE', 'suppliers', 'Updated Test Supplier Corp', 'Updated supplier: Updated Test Supplier Corp (company)', '{\"id\":\"1\",\"supplier_type\":\"cooperative\",\"name\":\"Test Supplier Coop\",\"nif\":\"999999999\",\"vat_reg_no\":\"VAT-99999\",\"phone\":\"+250788111222\",\"email\":\"coop@test.com\",\"address\":\"Test Address\",\"region\":\"Western Province\",\"is_active\":\"1\",\"notes\":\"Temporary test notes\",\"created_at\":\"2026-06-17 01:58:58\",\"updated_at\":\"2026-06-17 01:58:58\",\"created_by\":\"2\",\"updated_by\":null}', '{\"id\":1,\"supplier_type\":\"company\",\"name\":\"Updated Test Supplier Corp\",\"nif\":\"888888888\",\"vat_reg_no\":\"VAT-88888\",\"phone\":\"+250788333444\",\"email\":\"corp@test.com\",\"address\":\"Updated Address\",\"region\":\"Kigali Province\",\"is_active\":0,\"notes\":\"Updated test notes\"}', '127.0.0.1', 'CLI-Test-Suite', 'kfl6h9dj2ivucs8gr2vtng15u5', NULL, '2026-06-16 23:58:58'),
(109, 'Test User', 'DELETE', 'suppliers', 'Updated Test Supplier Corp', 'Deleted supplier: Updated Test Supplier Corp', '{\"id\":\"1\",\"supplier_type\":\"company\",\"name\":\"Updated Test Supplier Corp\",\"nif\":\"888888888\",\"vat_reg_no\":\"VAT-88888\",\"phone\":\"+250788333444\",\"email\":\"corp@test.com\",\"address\":\"Updated Address\",\"region\":\"Kigali Province\",\"is_active\":\"0\",\"notes\":\"Updated test notes\",\"created_at\":\"2026-06-17 01:58:58\",\"updated_at\":\"2026-06-17 01:58:58\",\"created_by\":\"2\",\"updated_by\":\"2\"}', NULL, '127.0.0.1', 'CLI-Test-Suite', 'h77iegfniqu0b12s72ak8k93bl', NULL, '2026-06-16 23:58:58'),
(110, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-17 00:01:45'),
(111, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-17 00:03:02'),
(112, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-17 00:03:03'),
(113, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-17 00:03:04'),
(114, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-17 00:03:05'),
(115, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-17 00:03:06'),
(116, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-17 00:03:06'),
(117, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-17 00:03:08'),
(118, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-17 00:03:10'),
(119, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-17 00:03:12'),
(120, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-17 00:03:18'),
(121, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-17 00:03:24'),
(122, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'a15r2ft85hjl57i2d5tt6dd30l', NULL, '2026-06-17 00:03:31'),
(123, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'erktv7jhahdedapb1iarfgl3b7', NULL, '2026-06-17 08:12:31'),
(124, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'erktv7jhahdedapb1iarfgl3b7', NULL, '2026-06-17 08:12:42'),
(125, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'erktv7jhahdedapb1iarfgl3b7', NULL, '2026-06-17 08:25:14'),
(126, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'erktv7jhahdedapb1iarfgl3b7', NULL, '2026-06-17 08:25:15'),
(127, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'erktv7jhahdedapb1iarfgl3b7', NULL, '2026-06-17 08:25:15'),
(128, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'erktv7jhahdedapb1iarfgl3b7', NULL, '2026-06-17 08:25:26'),
(129, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'erktv7jhahdedapb1iarfgl3b7', NULL, '2026-06-17 08:31:57'),
(130, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'erktv7jhahdedapb1iarfgl3b7', NULL, '2026-06-17 08:31:57'),
(131, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'erktv7jhahdedapb1iarfgl3b7', NULL, '2026-06-17 08:31:58'),
(132, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'c7m84q48q4as0s56k9ad6lgg2n', NULL, '2026-06-17 08:32:33'),
(133, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'c7m84q48q4as0s56k9ad6lgg2n', NULL, '2026-06-17 08:32:37'),
(134, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'c7m84q48q4as0s56k9ad6lgg2n', NULL, '2026-06-17 08:32:50'),
(135, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'c7m84q48q4as0s56k9ad6lgg2n', NULL, '2026-06-17 08:32:53'),
(136, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'c7m84q48q4as0s56k9ad6lgg2n', NULL, '2026-06-17 08:37:25'),
(137, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'c7m84q48q4as0s56k9ad6lgg2n', NULL, '2026-06-17 08:37:25'),
(138, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'c7m84q48q4as0s56k9ad6lgg2n', NULL, '2026-06-17 08:37:26'),
(139, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'c7m84q48q4as0s56k9ad6lgg2n', NULL, '2026-06-17 08:37:26'),
(140, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'c7m84q48q4as0s56k9ad6lgg2n', NULL, '2026-06-17 08:52:13'),
(141, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'c7m84q48q4as0s56k9ad6lgg2n', NULL, '2026-06-17 08:52:17'),
(142, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'c7m84q48q4as0s56k9ad6lgg2n', NULL, '2026-06-17 08:52:31'),
(143, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'c7m84q48q4as0s56k9ad6lgg2n', NULL, '2026-06-17 08:52:40'),
(144, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'c7m84q48q4as0s56k9ad6lgg2n', NULL, '2026-06-17 08:52:46'),
(145, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'skaviasodm6kaapi0ureh9036r', NULL, '2026-06-17 08:54:29'),
(146, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'o0br66into3gv00cq549671blu', NULL, '2026-06-17 09:01:26'),
(147, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'o0br66into3gv00cq549671blu', NULL, '2026-06-17 09:01:29');
INSERT INTO `audit_log` (`id`, `user_full_name`, `action`, `target_table`, `target_name`, `target_description`, `old_values`, `new_values`, `ip_address`, `user_agent`, `session_id`, `notes`, `performed_at`) VALUES
(148, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'o0br66into3gv00cq549671blu', NULL, '2026-06-17 09:01:42'),
(149, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'o0br66into3gv00cq549671blu', NULL, '2026-06-17 09:01:48'),
(150, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'o0br66into3gv00cq549671blu', NULL, '2026-06-17 09:01:53'),
(151, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'o0br66into3gv00cq549671blu', NULL, '2026-06-17 09:02:00'),
(152, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'o0br66into3gv00cq549671blu', NULL, '2026-06-17 09:02:03'),
(153, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'o0br66into3gv00cq549671blu', NULL, '2026-06-17 09:07:59'),
(154, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'o0br66into3gv00cq549671blu', NULL, '2026-06-17 09:08:00'),
(155, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'o0br66into3gv00cq549671blu', NULL, '2026-06-17 09:08:00'),
(156, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'skaviasodm6kaapi0ureh9036r', NULL, '2026-06-17 09:08:16'),
(157, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'skaviasodm6kaapi0ureh9036r', NULL, '2026-06-17 09:08:16'),
(158, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'skaviasodm6kaapi0ureh9036r', NULL, '2026-06-17 09:08:17'),
(159, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'skaviasodm6kaapi0ureh9036r', NULL, '2026-06-17 09:08:17'),
(160, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'skaviasodm6kaapi0ureh9036r', NULL, '2026-06-17 09:08:19'),
(161, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'o0br66into3gv00cq549671blu', NULL, '2026-06-17 09:08:34'),
(162, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'o0br66into3gv00cq549671blu', NULL, '2026-06-17 09:08:34'),
(163, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'o0br66into3gv00cq549671blu', NULL, '2026-06-17 09:08:34'),
(164, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'o0br66into3gv00cq549671blu', NULL, '2026-06-17 09:08:34'),
(165, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'o0br66into3gv00cq549671blu', NULL, '2026-06-17 09:08:35'),
(166, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 09:08:53'),
(167, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 09:09:03'),
(168, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 09:09:04'),
(169, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 09:09:04'),
(170, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 09:09:07'),
(171, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 09:09:16'),
(172, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 11:27:25'),
(173, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 11:27:34'),
(174, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 11:27:39'),
(175, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 11:27:42'),
(176, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 11:34:07'),
(177, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 11:47:53'),
(178, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 11:48:35'),
(179, 'Super Admin', 'CREATE', 'exchange_rates', 'RWF>USD', 'Created rate: 1 RWF = 0.2 USD', NULL, '{\"id\":2,\"from_currency_id\":1,\"to_currency_id\":2,\"from_code\":\"RWF\",\"to_code\":\"USD\",\"rate\":\"0.2\",\"rate_date\":\"2026-06-17\",\"source\":\"bnr\",\"created_by\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 11:49:17'),
(180, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 11:49:17'),
(181, 'Super Admin', 'UPDATE', 'exchange_rates', 'RWF>USD', 'Updated rate: 1 RWF = 1400 USD', '{\"id\":\"2\",\"from_currency_id\":\"1\",\"to_currency_id\":\"2\",\"rate\":\"0.20000000\",\"rate_date\":\"2026-06-17\",\"source\":\"bnr\",\"created_by\":\"2\",\"created_at\":\"2026-06-17 13:49:17\",\"updated_by\":null,\"updated_at\":\"2026-06-17 13:49:17\"}', '{\"id\":2,\"from_currency_id\":1,\"to_currency_id\":2,\"from_code\":\"RWF\",\"to_code\":\"USD\",\"rate\":\"1400\",\"rate_date\":\"2026-06-17\",\"source\":\"bnr\",\"updated_by\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 11:49:48'),
(182, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 11:49:48'),
(183, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 11:54:52'),
(184, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 11:56:19'),
(185, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 11:56:26'),
(186, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 11:56:27'),
(187, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 11:56:30'),
(188, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 11:56:37'),
(189, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 11:57:17'),
(190, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 11:57:18'),
(191, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 12:30:59'),
(192, 'Super Admin', 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 12:31:00'),
(193, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 12:34:23'),
(194, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 12:35:45'),
(195, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 12:39:12'),
(196, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '855irerhbiek3ougu90nsd013q', NULL, '2026-06-17 12:40:15'),
(197, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 08:57:56'),
(198, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 08:58:26'),
(199, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 09:01:23'),
(200, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 09:01:24'),
(201, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 09:02:18'),
(202, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 13:43:43'),
(203, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 14:04:18'),
(204, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 14:04:21'),
(205, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 14:05:03'),
(206, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 14:37:33'),
(207, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 14:37:55'),
(208, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 14:40:31'),
(209, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 14:41:24'),
(210, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 14:42:10'),
(211, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 14:52:05'),
(212, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 14:56:06'),
(213, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:18:26'),
(214, 'Super Admin', 'CREATE', 'accounts', '1170', 'Created new account: neeuge (1170)', NULL, '{\"id\":26,\"account_type_id\":1,\"account_code\":\"1170\",\"account_name\":\"neeuge\",\"opening_balance\":0,\"current_balance\":0,\"is_active\":1,\"description\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:18:40'),
(215, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:18:41'),
(216, 'Super Admin', 'DELETE', 'accounts', '1170', 'Deleted account: neeuge (1170)', '{\"id\":\"26\",\"account_type_id\":\"1\",\"account_code\":\"1170\",\"account_name\":\"neeuge\",\"opening_balance\":\"0.00\",\"current_balance\":\"0.00\",\"is_active\":\"1\",\"description\":\"\",\"created_at\":\"2026-06-19 17:18:40\",\"updated_at\":\"2026-06-19 17:18:40\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:18:58'),
(217, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:18:59'),
(218, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:21:07'),
(219, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:21:22'),
(220, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:23:59'),
(221, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:24:12'),
(222, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:24:27'),
(223, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:24:31'),
(224, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:24:52'),
(225, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:24:53'),
(226, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:24:53'),
(227, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:24:53'),
(228, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:24:53'),
(229, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:24:53'),
(230, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:24:53'),
(231, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:24:53'),
(232, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:24:54'),
(233, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:24:54'),
(234, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:24:54'),
(235, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:24:54'),
(236, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:24:56'),
(237, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:40:41'),
(238, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 15:41:35'),
(239, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 17:02:21'),
(240, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 17:03:48'),
(241, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 17:04:10'),
(242, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 17:08:30'),
(243, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 17:09:11'),
(244, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 17:10:59'),
(245, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 17:33:13'),
(246, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 17:33:15'),
(247, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 17:33:25'),
(248, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 17:33:27'),
(249, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 17:33:41'),
(250, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 17:33:46'),
(252, 'Super Admin', 'CREATE', 'products', 'TST', 'Created product: Test Mineral', NULL, '{\"id\":3,\"code\":\"TST\",\"name\":\"Test Mineral\",\"full_name\":\"Test Mineral Designation\",\"unit_of_measure\":\"tonnes\",\"description\":\"A mineral for automated testing.\",\"inventory_account_id\":14,\"sales_account_id\":48,\"cogs_account_id\":49,\"is_active\":1}', '127.0.0.1', '', 'o1m11pda5at0k52if0brqfcjoa', NULL, '2026-06-19 17:49:25'),
(253, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '127.0.0.1', '', 'o1m11pda5at0k52if0brqfcjoa', NULL, '2026-06-19 17:49:25'),
(254, 'Super Admin', 'UPDATE', 'products', 'TST', 'Updated product: Test Mineral Updated', '{\"id\":\"3\",\"code\":\"TST\",\"name\":\"Test Mineral\",\"full_name\":\"Test Mineral Designation\",\"unit_of_measure\":\"tonnes\",\"description\":\"A mineral for automated testing.\",\"inventory_account_id\":\"14\",\"sales_account_id\":\"48\",\"cogs_account_id\":\"49\",\"is_active\":\"1\",\"created_at\":\"2026-06-19 19:49:25\",\"created_by\":\"2\",\"updated_at\":\"2026-06-19 19:49:25\",\"updated_by\":null}', '{\"id\":3,\"code\":\"TST\",\"name\":\"Test Mineral Updated\",\"full_name\":\"Updated Mineral Designation\",\"unit_of_measure\":\"kg\",\"description\":\"Updated description.\",\"inventory_account_id\":15,\"sales_account_id\":null,\"cogs_account_id\":50,\"is_active\":1}', '127.0.0.1', '', 'o1m11pda5at0k52if0brqfcjoa', NULL, '2026-06-19 17:49:25'),
(255, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '127.0.0.1', '', 'o1m11pda5at0k52if0brqfcjoa', NULL, '2026-06-19 17:49:25'),
(256, 'Super Admin', 'DELETE', 'products', 'TST', 'Deleted product: Test Mineral Updated', '{\"id\":\"3\",\"code\":\"TST\",\"name\":\"Test Mineral Updated\",\"full_name\":\"Updated Mineral Designation\",\"unit_of_measure\":\"kg\",\"description\":\"Updated description.\",\"inventory_account_id\":\"15\",\"sales_account_id\":null,\"cogs_account_id\":\"50\",\"is_active\":\"1\",\"created_at\":\"2026-06-19 19:49:25\",\"created_by\":\"2\",\"updated_at\":\"2026-06-19 19:49:25\",\"updated_by\":\"2\"}', NULL, '127.0.0.1', '', 'o1m11pda5at0k52if0brqfcjoa', NULL, '2026-06-19 17:49:25'),
(257, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '127.0.0.1', '', 'o1m11pda5at0k52if0brqfcjoa', NULL, '2026-06-19 17:49:25'),
(258, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 17:58:53'),
(259, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 18:01:09'),
(260, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 19:50:15'),
(261, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 19:57:28'),
(262, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 19:58:11'),
(263, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 19:58:26'),
(264, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 19:58:51'),
(265, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 19:58:57'),
(266, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 20:06:04'),
(267, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 20:07:04'),
(268, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 14:59:21'),
(269, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 14:59:25'),
(270, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 14:59:31'),
(271, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 14:59:36'),
(272, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 14:59:39'),
(273, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 14:59:41'),
(274, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 15:00:53'),
(275, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 15:01:09'),
(276, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 15:01:21'),
(277, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 15:01:24'),
(278, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 15:01:44'),
(279, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 15:01:57'),
(280, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 15:02:15'),
(281, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 15:02:33'),
(282, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 15:02:43'),
(283, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 15:02:53'),
(284, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 15:03:20'),
(285, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 15:03:23'),
(286, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 15:17:33'),
(287, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 15:17:41'),
(288, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 15:22:26'),
(289, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 15:22:29'),
(290, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ss3rmqe8asqgtidpa2k9p0sfi7', NULL, '2026-06-20 15:22:38'),
(291, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '5cl3rvv71bl1a8ea0qv6domdjf', NULL, '2026-06-20 15:23:41'),
(292, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '5cl3rvv71bl1a8ea0qv6domdjf', NULL, '2026-06-20 15:23:52'),
(293, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '5cl3rvv71bl1a8ea0qv6domdjf', NULL, '2026-06-20 15:23:56'),
(294, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '5cl3rvv71bl1a8ea0qv6domdjf', NULL, '2026-06-20 15:24:00'),
(295, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '5cl3rvv71bl1a8ea0qv6domdjf', NULL, '2026-06-20 15:24:10'),
(296, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '5cl3rvv71bl1a8ea0qv6domdjf', NULL, '2026-06-20 15:24:12'),
(297, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '5cl3rvv71bl1a8ea0qv6domdjf', NULL, '2026-06-20 15:24:14'),
(298, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '5cl3rvv71bl1a8ea0qv6domdjf', NULL, '2026-06-20 15:24:17'),
(299, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '5cl3rvv71bl1a8ea0qv6domdjf', NULL, '2026-06-20 15:24:21'),
(300, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '5cl3rvv71bl1a8ea0qv6domdjf', NULL, '2026-06-20 15:24:38'),
(301, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '5cl3rvv71bl1a8ea0qv6domdjf', NULL, '2026-06-20 15:24:41'),
(302, 'Super Admin', 'CREATE', 'roles', 'view only', 'Created role: view only', NULL, '{\"id\":3,\"name\":\"view only\",\"description\":\"to viewing all details only\",\"permissions\":[21,31,35,19]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '5cl3rvv71bl1a8ea0qv6domdjf', NULL, '2026-06-20 15:25:53'),
(303, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '5cl3rvv71bl1a8ea0qv6domdjf', NULL, '2026-06-20 15:25:53'),
(304, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '5cl3rvv71bl1a8ea0qv6domdjf', NULL, '2026-06-20 15:26:00'),
(305, 'Super Admin', 'CREATE', 'users', 'eugene ndayishimiye', 'Created user: eugene@gmail.com with role view only', NULL, '{\"id\":5,\"first_name\":\"eugene\",\"last_name\":\"ndayishimiye\",\"email\":\"eugene@gmail.com\",\"phone_number\":\"0785750117\",\"role_id\":3,\"role_name\":\"view only\",\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '5cl3rvv71bl1a8ea0qv6domdjf', NULL, '2026-06-20 15:26:41'),
(306, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '5cl3rvv71bl1a8ea0qv6domdjf', NULL, '2026-06-20 15:26:41'),
(307, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'mg4o4susgt401dguo3kjdaj09p', NULL, '2026-06-20 15:27:59'),
(308, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'mg4o4susgt401dguo3kjdaj09p', NULL, '2026-06-20 15:28:06');
INSERT INTO `audit_log` (`id`, `user_full_name`, `action`, `target_table`, `target_name`, `target_description`, `old_values`, `new_values`, `ip_address`, `user_agent`, `session_id`, `notes`, `performed_at`) VALUES
(309, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'mg4o4susgt401dguo3kjdaj09p', NULL, '2026-06-20 15:28:11'),
(310, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '44dprtnmjd2mn9plu7s37fqa2s', NULL, '2026-06-20 15:30:59'),
(311, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '44dprtnmjd2mn9plu7s37fqa2s', NULL, '2026-06-20 15:31:01'),
(312, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '44dprtnmjd2mn9plu7s37fqa2s', NULL, '2026-06-20 15:44:21'),
(313, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '44dprtnmjd2mn9plu7s37fqa2s', NULL, '2026-06-20 15:44:25'),
(314, 'eugene ndayishimiye', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'pbcngvg2af46cmncoq63kmaija', NULL, '2026-06-20 16:01:44'),
(315, 'eugene ndayishimiye', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'pbcngvg2af46cmncoq63kmaija', NULL, '2026-06-20 16:01:56'),
(316, 'eugene ndayishimiye', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'pbcngvg2af46cmncoq63kmaija', NULL, '2026-06-20 16:02:00'),
(317, 'eugene ndayishimiye', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'pbcngvg2af46cmncoq63kmaija', NULL, '2026-06-20 16:02:04'),
(318, 'eugene ndayishimiye', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'pbcngvg2af46cmncoq63kmaija', NULL, '2026-06-20 16:02:07'),
(319, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nrlbc1jb004cjpg604p81vdrnq', NULL, '2026-06-20 16:04:08'),
(320, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nrlbc1jb004cjpg604p81vdrnq', NULL, '2026-06-20 16:04:09'),
(321, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nrlbc1jb004cjpg604p81vdrnq', NULL, '2026-06-20 16:04:11'),
(322, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nrlbc1jb004cjpg604p81vdrnq', NULL, '2026-06-20 16:04:34'),
(323, 'Super Admin', 'UPDATE', 'roles', 'view only', 'Updated role: view only', '{\"id\":\"3\",\"name\":\"view only\",\"description\":\"to viewing all details only\",\"created_at\":\"2026-06-20 17:25:53\",\"permission_ids\":\"19,21,31,35\"}', '{\"id\":3,\"name\":\"view only\",\"description\":\"to viewing all details only\",\"permissions\":[31,35,19]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nrlbc1jb004cjpg604p81vdrnq', NULL, '2026-06-20 16:04:59'),
(324, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nrlbc1jb004cjpg604p81vdrnq', NULL, '2026-06-20 16:04:59'),
(325, 'eugene ndayishimiye', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '6h599qrdtr9mbevc3nssqenq7s', NULL, '2026-06-20 16:05:08'),
(326, 'eugene ndayishimiye', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '6h599qrdtr9mbevc3nssqenq7s', NULL, '2026-06-20 16:05:14'),
(327, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'pr9sq3d3kmtg8sfsenl92sss0f', NULL, '2026-06-20 16:17:10'),
(328, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'pr9sq3d3kmtg8sfsenl92sss0f', NULL, '2026-06-20 16:17:11'),
(329, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'pr9sq3d3kmtg8sfsenl92sss0f', NULL, '2026-06-20 16:17:12'),
(330, 'eugene ndayishimiye', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '647cs0ll5iom66qcrvgdsj2td4', NULL, '2026-06-20 16:17:20'),
(331, 'eugene ndayishimiye', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '647cs0ll5iom66qcrvgdsj2td4', NULL, '2026-06-20 16:17:23'),
(332, 'eugene ndayishimiye', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '647cs0ll5iom66qcrvgdsj2td4', NULL, '2026-06-20 16:17:32'),
(333, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nj3cn7mka30mb2qfht20ault9g', NULL, '2026-06-20 16:17:53'),
(334, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nj3cn7mka30mb2qfht20ault9g', NULL, '2026-06-20 16:18:06'),
(335, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nj3cn7mka30mb2qfht20ault9g', NULL, '2026-06-20 16:18:08'),
(336, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nj3cn7mka30mb2qfht20ault9g', NULL, '2026-06-20 16:18:11'),
(337, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nj3cn7mka30mb2qfht20ault9g', NULL, '2026-06-20 16:18:12'),
(338, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nj3cn7mka30mb2qfht20ault9g', NULL, '2026-06-20 16:18:14'),
(339, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nj3cn7mka30mb2qfht20ault9g', NULL, '2026-06-20 16:19:36'),
(340, 'Super Admin', 'CREATE', 'products', 'SN', 'Created product: Tin', NULL, '{\"id\":4,\"code\":\"SN\",\"name\":\"Tin\",\"full_name\":\"Cass\",\"unit_of_measure\":\"kg\",\"description\":\"all add\",\"inventory_account_id\":27,\"sales_account_id\":15,\"cogs_account_id\":17,\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nj3cn7mka30mb2qfht20ault9g', NULL, '2026-06-20 16:20:45'),
(341, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nj3cn7mka30mb2qfht20ault9g', NULL, '2026-06-20 16:20:45'),
(342, 'Super Admin', 'DELETE', 'products', 'SN', 'Deleted product: Tin', '{\"id\":\"4\",\"code\":\"SN\",\"name\":\"Tin\",\"full_name\":\"Cass\",\"unit_of_measure\":\"kg\",\"description\":\"all add\",\"inventory_account_id\":\"27\",\"sales_account_id\":\"15\",\"cogs_account_id\":\"17\",\"is_active\":\"1\",\"created_at\":\"2026-06-20 18:20:45\",\"created_by\":\"2\",\"updated_at\":\"2026-06-20 18:20:45\",\"updated_by\":null}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nj3cn7mka30mb2qfht20ault9g', NULL, '2026-06-20 16:21:09'),
(343, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nj3cn7mka30mb2qfht20ault9g', NULL, '2026-06-20 16:21:09'),
(344, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nj3cn7mka30mb2qfht20ault9g', NULL, '2026-06-20 16:42:29'),
(345, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nj3cn7mka30mb2qfht20ault9g', NULL, '2026-06-20 21:21:56'),
(346, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'fp5og6mdubatjpk4rqpajv66i6', NULL, '2026-06-21 13:34:46'),
(347, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2lbb2dl7vhkv0uef5gscmjnojp', NULL, '2026-06-21 22:44:56'),
(348, 'Test Admin', 'CREATE', 'product_categories', 'T_CAT', 'Created product category: Test Category', NULL, '{\"id\":1,\"category_code\":\"T_CAT\",\"category_name\":\"Test Category\",\"description\":\"Integration test category\",\"is_active\":1}', '', '', 's90haj6iklo2vc8po0toib2g3o', NULL, '2026-06-21 22:57:15'),
(349, 'Test Admin', 'CREATE', 'product_categories', 'T_CAT', 'Created product category: Test Category', NULL, '{\"id\":2,\"category_code\":\"T_CAT\",\"category_name\":\"Test Category\",\"description\":\"Integration test category\",\"is_active\":1}', '', '', 'bt02sies2ovrnkcvtpeu7ai87r', NULL, '2026-06-21 22:59:08'),
(350, 'Test Admin', 'VIEW', 'product_categories', 'Product Categories List', 'User viewed the product categories list', NULL, NULL, '', '', 'jg5a4863iqugrffgbhdgk72rep', NULL, '2026-06-21 22:59:08'),
(351, 'Test Admin', 'CREATE', 'products', 'T_PRD', 'Created product: Test Product', NULL, '{\"id\":5,\"code\":\"T_PRD\",\"name\":\"Test Product\",\"full_name\":\"Test Product Designation\",\"unit_of_measure\":\"kg\",\"description\":\"Product integration test\",\"inventory_account_id\":null,\"sales_account_id\":null,\"cogs_account_id\":null,\"category_id\":2,\"is_active\":1}', '', '', 'tb200gpi90k3htdvuj2ingr0o7', NULL, '2026-06-21 22:59:08'),
(352, 'Test Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '', '', 'a39rbk0k3ol9fgov5ltd17co2s', NULL, '2026-06-21 22:59:08'),
(353, 'Test Admin', 'DELETE', 'products', 'T_PRD', 'Deleted product: Test Product', '{\"id\":\"5\",\"code\":\"T_PRD\",\"name\":\"Test Product\",\"full_name\":\"Test Product Designation\",\"unit_of_measure\":\"kg\",\"description\":\"Product integration test\",\"inventory_account_id\":null,\"sales_account_id\":null,\"cogs_account_id\":null,\"is_active\":\"1\",\"created_at\":\"2026-06-22 00:59:08\",\"created_by\":\"2\",\"updated_at\":\"2026-06-22 00:59:08\",\"updated_by\":null,\"category_id\":\"2\"}', NULL, '', '', 'acao9snduhk8a1scuksjolfiks', NULL, '2026-06-21 22:59:09'),
(354, 'Test Admin', 'DELETE', 'product_categories', 'T_CAT', 'Deleted product category: Test Category', '{\"id\":\"2\",\"category_code\":\"T_CAT\",\"category_name\":\"Test Category\",\"description\":\"Integration test category\",\"is_active\":\"1\",\"created_at\":\"2026-06-22 00:59:08\"}', NULL, '', '', 'mgg8evprj6nq6cou66g6pot29r', NULL, '2026-06-21 22:59:09'),
(355, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2lbb2dl7vhkv0uef5gscmjnojp', NULL, '2026-06-21 23:00:00'),
(356, 'Super Admin', 'VIEW', 'product_categories', 'Product Categories List', 'User viewed the product categories list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2lbb2dl7vhkv0uef5gscmjnojp', NULL, '2026-06-21 23:00:03'),
(357, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2lbb2dl7vhkv0uef5gscmjnojp', NULL, '2026-06-21 23:00:14'),
(358, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2lbb2dl7vhkv0uef5gscmjnojp', NULL, '2026-06-21 23:00:18'),
(359, 'Super Admin', 'UPDATE', 'roles', 'view only', 'Updated role: view only', '{\"id\":\"3\",\"name\":\"view only\",\"description\":\"to viewing all details only\",\"created_at\":\"2026-06-20 17:25:53\",\"permission_ids\":\"19,31,35\"}', '{\"id\":3,\"name\":\"view only\",\"description\":\"to viewing all details only\",\"permissions\":[31,35,40,19]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2lbb2dl7vhkv0uef5gscmjnojp', NULL, '2026-06-21 23:00:43'),
(360, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2lbb2dl7vhkv0uef5gscmjnojp', NULL, '2026-06-21 23:00:43'),
(361, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2lbb2dl7vhkv0uef5gscmjnojp', NULL, '2026-06-21 23:01:07'),
(362, 'eugene ndayishimiye', 'VIEW', 'product_categories', 'Product Categories List', 'User viewed the product categories list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'uhf2qa3v5qcrlfo9iufsmak526', NULL, '2026-06-21 23:01:30'),
(363, 'Super Admin', 'VIEW', 'product_categories', 'Product Categories List', 'User viewed the product categories list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nngc7msff0njo3e3ep1t3ptipu', NULL, '2026-06-21 23:01:58'),
(364, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nngc7msff0njo3e3ep1t3ptipu', NULL, '2026-06-21 23:01:59'),
(365, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nngc7msff0njo3e3ep1t3ptipu', NULL, '2026-06-21 23:02:01'),
(366, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nngc7msff0njo3e3ep1t3ptipu', NULL, '2026-06-21 23:02:05'),
(367, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nngc7msff0njo3e3ep1t3ptipu', NULL, '2026-06-21 23:02:06'),
(368, 'Super Admin', 'VIEW', 'product_categories', 'Product Categories List', 'User viewed the product categories list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nngc7msff0njo3e3ep1t3ptipu', NULL, '2026-06-21 23:02:08'),
(369, 'Super Admin', 'CREATE', 'product_categories', '001', 'Created product category: Mineral', NULL, '{\"id\":3,\"category_code\":\"001\",\"category_name\":\"Mineral\",\"description\":\"\",\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nngc7msff0njo3e3ep1t3ptipu', NULL, '2026-06-21 23:02:41'),
(370, 'Super Admin', 'VIEW', 'product_categories', 'Product Categories List', 'User viewed the product categories list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nngc7msff0njo3e3ep1t3ptipu', NULL, '2026-06-21 23:02:41'),
(371, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nngc7msff0njo3e3ep1t3ptipu', NULL, '2026-06-21 23:02:43'),
(372, 'Super Admin', 'CREATE', 'products', 'SN', 'Created product: Tin', NULL, '{\"id\":6,\"code\":\"SN\",\"name\":\"Tin\",\"full_name\":\"Sn02\",\"unit_of_measure\":\"kg\",\"description\":\"\",\"inventory_account_id\":9,\"sales_account_id\":14,\"cogs_account_id\":16,\"category_id\":3,\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nngc7msff0njo3e3ep1t3ptipu', NULL, '2026-06-21 23:03:21'),
(373, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nngc7msff0njo3e3ep1t3ptipu', NULL, '2026-06-21 23:03:21'),
(374, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nngc7msff0njo3e3ep1t3ptipu', NULL, '2026-06-21 23:16:05'),
(375, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '7pg3cls8d7qujcc16qhrfdvld2', NULL, '2026-06-21 23:16:47'),
(376, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '7pg3cls8d7qujcc16qhrfdvld2', NULL, '2026-06-21 23:19:37'),
(377, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '7pg3cls8d7qujcc16qhrfdvld2', NULL, '2026-06-21 23:19:38'),
(378, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '7pg3cls8d7qujcc16qhrfdvld2', NULL, '2026-06-21 23:21:06'),
(379, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '7pg3cls8d7qujcc16qhrfdvld2', NULL, '2026-06-21 23:21:07'),
(380, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '7pg3cls8d7qujcc16qhrfdvld2', NULL, '2026-06-21 23:21:08'),
(381, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '7pg3cls8d7qujcc16qhrfdvld2', NULL, '2026-06-21 23:21:08'),
(382, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '7pg3cls8d7qujcc16qhrfdvld2', NULL, '2026-06-21 23:21:08'),
(383, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '7pg3cls8d7qujcc16qhrfdvld2', NULL, '2026-06-21 23:26:39'),
(384, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '7pg3cls8d7qujcc16qhrfdvld2', NULL, '2026-06-21 23:26:40'),
(385, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '7pg3cls8d7qujcc16qhrfdvld2', NULL, '2026-06-21 23:26:41'),
(386, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'lo7vfo8jj2ss4ve1q4e2hsj3kt', NULL, '2026-06-21 23:26:55'),
(387, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'lo7vfo8jj2ss4ve1q4e2hsj3kt', NULL, '2026-06-21 23:27:18'),
(388, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'lo7vfo8jj2ss4ve1q4e2hsj3kt', NULL, '2026-06-21 23:33:23'),
(389, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'lo7vfo8jj2ss4ve1q4e2hsj3kt', NULL, '2026-06-21 23:33:23'),
(390, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '738msttoonanpb47due24c9auv', NULL, '2026-06-21 23:33:57'),
(391, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '738msttoonanpb47due24c9auv', NULL, '2026-06-21 23:41:35'),
(392, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '738msttoonanpb47due24c9auv', NULL, '2026-06-21 23:41:37'),
(393, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '738msttoonanpb47due24c9auv', NULL, '2026-06-21 23:41:39'),
(394, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '738msttoonanpb47due24c9auv', NULL, '2026-06-21 23:41:41'),
(395, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '738msttoonanpb47due24c9auv', NULL, '2026-06-21 23:41:45'),
(396, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '738msttoonanpb47due24c9auv', NULL, '2026-06-21 23:41:48'),
(397, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '738msttoonanpb47due24c9auv', NULL, '2026-06-21 23:41:51'),
(398, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '738msttoonanpb47due24c9auv', NULL, '2026-06-21 23:41:52'),
(399, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '738msttoonanpb47due24c9auv', NULL, '2026-06-21 23:41:54'),
(400, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '738msttoonanpb47due24c9auv', NULL, '2026-06-21 23:41:57'),
(401, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '738msttoonanpb47due24c9auv', NULL, '2026-06-21 23:41:58'),
(402, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '738msttoonanpb47due24c9auv', NULL, '2026-06-21 23:43:21'),
(403, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '738msttoonanpb47due24c9auv', NULL, '2026-06-21 23:44:25'),
(404, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:44:43'),
(405, 'Super Admin', 'VIEW', 'warehouses', 'Warehouses List', 'User viewed the warehouses list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:44:49'),
(406, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:47:44'),
(407, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:48:04'),
(408, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:51:38'),
(409, 'Super Admin', 'VIEW', 'warehouses', 'Warehouses List', 'User viewed the warehouses list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:54:52'),
(410, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:54:53'),
(411, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:54:55'),
(412, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:54:56'),
(413, 'Super Admin', 'VIEW', 'users', 'Users List', 'User viewed the users list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:54:58'),
(414, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:55:01'),
(415, 'Super Admin', 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:55:06'),
(416, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:55:32'),
(417, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:02'),
(418, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:15'),
(419, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:16'),
(420, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:18'),
(421, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:19'),
(422, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:20'),
(423, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:21'),
(424, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:21'),
(425, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:22'),
(426, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:24'),
(427, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:25'),
(428, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:25'),
(429, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:26'),
(430, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:26'),
(431, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:27'),
(432, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:27'),
(433, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:27'),
(434, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:27'),
(435, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:30'),
(436, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:32'),
(437, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:33'),
(438, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:54'),
(439, 'Super Admin', 'VIEW', 'warehouses', 'Warehouses List', 'User viewed the warehouses list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:56:54'),
(440, 'Super Admin', 'CREATE', 'warehouses', 'KIGALI', 'Created warehouse: Kicukiro', NULL, '{\"id\":1,\"warehouse_code\":\"KIGALI\",\"warehouse_name\":\"Kicukiro\",\"address\":\"KK05\",\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:57:14'),
(441, 'Super Admin', 'VIEW', 'warehouses', 'Warehouses List', 'User viewed the warehouses list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:57:14'),
(442, 'Super Admin', 'VIEW', 'audit_log', 'Audit Logs List', 'User viewed the system audit logs list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:57:45'),
(443, 'Super Admin', 'VIEW', 'warehouses', 'Warehouses List', 'User viewed the warehouses list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:58:24'),
(444, 'Super Admin', 'DELETE', 'warehouses', 'KIGALI', 'Deleted warehouse: Kicukiro', '{\"id\":\"1\",\"warehouse_code\":\"KIGALI\",\"warehouse_name\":\"Kicukiro\",\"address\":\"KK05\",\"is_active\":\"1\",\"created_at\":\"2026-06-22 01:57:14\",\"created_by\":\"2\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:58:29'),
(445, 'Super Admin', 'VIEW', 'warehouses', 'Warehouses List', 'User viewed the warehouses list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:58:29'),
(446, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:58:39'),
(447, 'Super Admin', 'VIEW', 'product_categories', 'Product Categories List', 'User viewed the product categories list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:58:40'),
(448, 'Super Admin', 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:58:42'),
(449, 'Super Admin', 'VIEW', 'warehouses', 'Warehouses List', 'User viewed the warehouses list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:58:43'),
(450, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-21 23:58:44'),
(451, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-22 00:00:42'),
(452, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-22 00:00:45'),
(453, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-22 00:03:19'),
(454, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'n0ht82d18729lioqop7kbobsue', NULL, '2026-06-22 00:03:27'),
(455, 'Super Admin', 'DELETE', 'products', 'SN', 'Deleted product: Tin', '{\"id\":\"6\",\"code\":\"SN\",\"name\":\"Tin\",\"full_name\":\"Sn02\",\"unit_of_measure\":\"kg\",\"description\":\"\",\"inventory_account_id\":\"9\",\"sales_account_id\":\"14\",\"cogs_account_id\":\"16\",\"is_active\":\"1\",\"created_at\":\"2026-06-22 01:03:21\",\"created_by\":\"2\",\"updated_at\":\"2026-06-22 01:03:21\",\"updated_by\":null,\"category_id\":\"3\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'pa8j7e6m7891qrd2gfp58uk4eu', NULL, '2026-06-22 00:22:27'),
(456, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'pa8j7e6m7891qrd2gfp58uk4eu', NULL, '2026-06-22 00:22:27'),
(457, 'Super Admin', 'DELETE', 'product_categories', '001', 'Deleted product category: Mineral', '{\"id\":\"3\",\"category_code\":\"001\",\"category_name\":\"Mineral\",\"description\":\"\",\"is_active\":\"1\",\"created_at\":\"2026-06-22 01:02:41\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'pa8j7e6m7891qrd2gfp58uk4eu', NULL, '2026-06-22 00:22:32'),
(458, 'Super Admin', 'VIEW', 'product_categories', 'Product Categories List', 'User viewed the product categories list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'pa8j7e6m7891qrd2gfp58uk4eu', NULL, '2026-06-22 00:22:32'),
(459, 'Super Admin', 'CREATE', 'suppliers', 'eugene', 'Created supplier: eugene (individual)', NULL, '{\"id\":2,\"supplier_type\":\"individual\",\"name\":\"eugene\",\"nif\":\"09987\",\"vat_reg_no\":\"VAT334\",\"phone\":\"0785750123\",\"email\":\"nendayishimye@gmail.com\",\"address\":\"Unnamed Road\",\"payables_account_id\":7,\"currency_id\":2,\"region\":\"\",\"is_active\":1,\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'pa8j7e6m7891qrd2gfp58uk4eu', NULL, '2026-06-22 00:44:45'),
(460, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'pa8j7e6m7891qrd2gfp58uk4eu', NULL, '2026-06-22 00:44:45'),
(461, 'Super Admin', 'DELETE', 'suppliers', 'eugene', 'Deleted supplier: eugene', '{\"id\":\"2\",\"supplier_type\":\"individual\",\"name\":\"eugene\",\"nif\":\"09987\",\"vat_reg_no\":\"VAT334\",\"phone\":\"0785750123\",\"email\":\"nendayishimye@gmail.com\",\"address\":\"Unnamed Road\",\"payables_account_id\":\"7\",\"currency_id\":\"2\",\"region\":\"\",\"is_active\":\"1\",\"notes\":\"\",\"created_at\":\"2026-06-22 02:44:45\",\"updated_at\":\"2026-06-22 02:44:45\",\"created_by\":\"2\",\"updated_by\":null}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 't8l6oakb89jcblmr9fm3roj26n', NULL, '2026-06-22 08:52:59');
INSERT INTO `audit_log` (`id`, `user_full_name`, `action`, `target_table`, `target_name`, `target_description`, `old_values`, `new_values`, `ip_address`, `user_agent`, `session_id`, `notes`, `performed_at`) VALUES
(462, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 't8l6oakb89jcblmr9fm3roj26n', NULL, '2026-06-22 08:52:59'),
(463, 'Super Admin', 'CREATE', 'suppliers', 'eugene', 'Created supplier: eugene (cooperative)', NULL, '{\"id\":4,\"supplier_type\":\"cooperative\",\"name\":\"eugene\",\"nif\":\"09987\",\"vat_reg_no\":\"VAT334\",\"phone\":\"0785750123\",\"email\":\"nendayishimye@gmail.com\",\"address\":\"Unnamed Road\",\"payables_account_id\":4,\"currency_id\":1,\"region\":\"Rutsiro\",\"is_active\":1,\"notes\":\"done\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 't8l6oakb89jcblmr9fm3roj26n', NULL, '2026-06-22 08:53:34'),
(464, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 't8l6oakb89jcblmr9fm3roj26n', NULL, '2026-06-22 08:53:34'),
(465, 'Super Admin', 'DELETE', 'suppliers', 'eugene', 'Deleted supplier: eugene', '{\"id\":\"4\",\"supplier_type\":\"cooperative\",\"name\":\"eugene\",\"nif\":\"09987\",\"vat_reg_no\":\"VAT334\",\"phone\":\"0785750123\",\"email\":\"nendayishimye@gmail.com\",\"address\":\"Unnamed Road\",\"payables_account_id\":\"4\",\"currency_id\":\"1\",\"region\":\"Rutsiro\",\"is_active\":\"1\",\"notes\":\"done\",\"created_at\":\"2026-06-22 10:53:34\",\"updated_at\":\"2026-06-22 10:53:34\",\"created_by\":\"2\",\"updated_by\":null}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 't8l6oakb89jcblmr9fm3roj26n', NULL, '2026-06-22 08:54:09'),
(466, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 't8l6oakb89jcblmr9fm3roj26n', NULL, '2026-06-22 08:54:09'),
(467, 'Super Admin', 'UPDATE', 'roles', 'view only', 'Updated role: view only', '{\"id\":\"3\",\"name\":\"view only\",\"description\":\"to viewing all details only\",\"created_at\":\"2026-06-20 17:25:53\",\"permission_ids\":\"19,31,35,40\"}', '{\"id\":3,\"name\":\"view only\",\"description\":\"to viewing all details only\",\"permissions\":[31,35,40,19,27]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 't8l6oakb89jcblmr9fm3roj26n', NULL, '2026-06-22 08:59:07'),
(468, 'Super Admin', 'VIEW', 'roles', 'Roles List', 'User viewed the roles list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 't8l6oakb89jcblmr9fm3roj26n', NULL, '2026-06-22 08:59:07'),
(469, 'Super Admin', 'VIEW', 'lots', 'Lots List', 'User viewed the lots list', NULL, NULL, '127.0.0.1', 'CLI-Test-Suite', 'ro1u505tn1g5i3lnaq7om21lcd', NULL, '2026-06-22 10:42:07'),
(470, 'Super Admin', 'CREATE', 'warehouses', 'KIGALI', 'Created warehouse: Kicukiro', NULL, '{\"id\":2,\"warehouse_code\":\"KIGALI\",\"warehouse_name\":\"Kicukiro\",\"address\":\"KK05\",\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '1t97ijoloph0svs874a3pitahh', NULL, '2026-06-22 10:58:30'),
(471, 'Super Admin', 'VIEW', 'warehouses', 'Warehouses List', 'User viewed the warehouses list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '1t97ijoloph0svs874a3pitahh', NULL, '2026-06-22 10:58:30'),
(472, 'Super Admin', 'CREATE', 'lots', 'LOT-TST-178212-20260622-001', 'Created lot: LOT-TST-178212-20260622-001', NULL, '{\"id\":1,\"lot_number\":\"LOT-TST-178212-20260622-001\",\"product_id\":7,\"status\":\"OPEN\",\"quantity_received\":100,\"remaining_quantity\":100}', '127.0.0.1', 'CLI-Test-Suite', 'cndq76t74e45p5hah1459injh9', NULL, '2026-06-22 11:13:53'),
(473, 'Super Admin', 'UPDATE', 'lots', 'LOT-TST-178212-20260622-001', 'Closed lot: LOT-TST-178212-20260622-001', '{\"id\":\"1\",\"lot_number\":\"LOT-TST-178212-20260622-001\",\"product_id\":\"7\",\"supplier_id\":null,\"received_date\":\"2026-06-22\",\"quantity_received\":\"100.000\",\"remaining_quantity\":\"100.000\",\"unit_cost\":\"12.50\",\"currency_id\":\"2\",\"exchange_rate\":\"1.000000\",\"status\":\"OPEN\",\"closing_date\":null,\"description\":\"Test Lot 1 Description\",\"created_by\":\"2\",\"created_at\":\"2026-06-22 13:13:53\",\"warehouse_id\":\"3\"}', '{\"id\":\"1\",\"lot_number\":\"LOT-TST-178212-20260622-001\",\"product_id\":\"7\",\"supplier_id\":null,\"received_date\":\"2026-06-22\",\"quantity_received\":\"100.000\",\"remaining_quantity\":\"100.000\",\"unit_cost\":\"12.50\",\"currency_id\":\"2\",\"exchange_rate\":\"1.000000\",\"status\":\"CLOSED\",\"closing_date\":\"2026-06-22\",\"description\":\"Test Lot 1 Description\",\"created_by\":\"2\",\"created_at\":\"2026-06-22 13:13:53\",\"warehouse_id\":\"3\"}', '127.0.0.1', 'CLI-Test-Suite', 'q6150tnhp6b94mbdc8spoeac0a', NULL, '2026-06-22 11:13:53'),
(474, 'Super Admin', 'CREATE', 'lots', 'LOT-TST-178212-20260622-002', 'Created lot: LOT-TST-178212-20260622-002', NULL, '{\"id\":2,\"lot_number\":\"LOT-TST-178212-20260622-002\",\"product_id\":7,\"status\":\"OPEN\",\"quantity_received\":45.5,\"remaining_quantity\":45.5}', '127.0.0.1', 'CLI-Test-Suite', 'hsiq32g3kn3tv1oni4f2m1i91t', NULL, '2026-06-22 11:13:54'),
(475, 'Super Admin', 'UPDATE', 'lots', 'LOT-TST-178212-20260622-002', 'Closed lot: LOT-TST-178212-20260622-002', '{\"id\":\"2\",\"lot_number\":\"LOT-TST-178212-20260622-002\",\"product_id\":\"7\",\"supplier_id\":null,\"received_date\":\"2026-06-22\",\"quantity_received\":\"45.500\",\"remaining_quantity\":\"0.000\",\"unit_cost\":null,\"currency_id\":null,\"exchange_rate\":null,\"status\":\"OPEN\",\"closing_date\":null,\"description\":\"\",\"created_by\":\"2\",\"created_at\":\"2026-06-22 13:13:54\",\"warehouse_id\":\"3\"}', '{\"id\":\"2\",\"lot_number\":\"LOT-TST-178212-20260622-002\",\"product_id\":\"7\",\"supplier_id\":null,\"received_date\":\"2026-06-22\",\"quantity_received\":\"45.500\",\"remaining_quantity\":\"0.000\",\"unit_cost\":null,\"currency_id\":null,\"exchange_rate\":null,\"status\":\"CLOSED\",\"closing_date\":\"2026-06-22\",\"description\":\"\",\"created_by\":\"2\",\"created_at\":\"2026-06-22 13:13:54\",\"warehouse_id\":\"3\"}', '127.0.0.1', 'CLI-Test-Suite', 'd4j2a8ip25pan1752q7vr3fv3h', NULL, '2026-06-22 11:13:54'),
(476, 'Super Admin', 'CREATE', 'lots', 'LOT-TST-178212-20260622-003', 'Created lot: LOT-TST-178212-20260622-003', NULL, '{\"id\":3,\"lot_number\":\"LOT-TST-178212-20260622-003\",\"product_id\":7,\"status\":\"OPEN\",\"quantity_received\":250,\"remaining_quantity\":250}', '127.0.0.1', 'CLI-Test-Suite', 'u72uvlag06274g9t2sfdatav5a', NULL, '2026-06-22 11:13:54'),
(477, 'Super Admin', 'DELETE', 'lots', 'LOT-TST-178212-20260622-003', 'Deleted lot: LOT-TST-178212-20260622-003', '{\"id\":\"3\",\"lot_number\":\"LOT-TST-178212-20260622-003\",\"product_id\":\"7\",\"supplier_id\":null,\"received_date\":\"2026-06-22\",\"quantity_received\":\"250.000\",\"remaining_quantity\":\"250.000\",\"unit_cost\":null,\"currency_id\":null,\"exchange_rate\":null,\"status\":\"OPEN\",\"closing_date\":null,\"description\":\"\",\"created_by\":\"2\",\"created_at\":\"2026-06-22 13:13:54\",\"warehouse_id\":\"3\"}', NULL, '127.0.0.1', 'CLI-Test-Suite', 'bj0fn24h4d1i45tdfdi54mrsn3', NULL, '2026-06-22 11:13:54'),
(478, 'Super Admin', 'CREATE', 'lots', 'LOT-TST-178212-20260622-001', 'Created lot: LOT-TST-178212-20260622-001', NULL, '{\"id\":4,\"lot_number\":\"LOT-TST-178212-20260622-001\",\"product_id\":8,\"status\":\"OPEN\",\"quantity_received\":100,\"remaining_quantity\":100}', '127.0.0.1', 'CLI-Test-Suite', 'vdv0vol34n3i6gna5tc09jlfgl', NULL, '2026-06-22 11:15:53'),
(479, 'Super Admin', 'UPDATE', 'lots', 'LOT-TST-178212-20260622-001', 'Closed lot: LOT-TST-178212-20260622-001', '{\"id\":\"4\",\"lot_number\":\"LOT-TST-178212-20260622-001\",\"product_id\":\"8\",\"supplier_id\":null,\"received_date\":\"2026-06-22\",\"quantity_received\":\"100.000\",\"remaining_quantity\":\"100.000\",\"unit_cost\":\"12.50\",\"currency_id\":\"2\",\"exchange_rate\":\"1.000000\",\"status\":\"OPEN\",\"closing_date\":null,\"description\":\"Test Lot 1 Description\",\"created_by\":\"2\",\"created_at\":\"2026-06-22 13:15:53\",\"warehouse_id\":\"4\"}', '{\"id\":\"4\",\"lot_number\":\"LOT-TST-178212-20260622-001\",\"product_id\":\"8\",\"supplier_id\":null,\"received_date\":\"2026-06-22\",\"quantity_received\":\"100.000\",\"remaining_quantity\":\"100.000\",\"unit_cost\":\"12.50\",\"currency_id\":\"2\",\"exchange_rate\":\"1.000000\",\"status\":\"CLOSED\",\"closing_date\":\"2026-06-22\",\"description\":\"Test Lot 1 Description\",\"created_by\":\"2\",\"created_at\":\"2026-06-22 13:15:53\",\"warehouse_id\":\"4\"}', '127.0.0.1', 'CLI-Test-Suite', '69u55o4ds1igov0rlqc3vbbe10', NULL, '2026-06-22 11:15:53'),
(480, 'Super Admin', 'CREATE', 'lots', 'LOT-TST-178212-20260622-002', 'Created lot: LOT-TST-178212-20260622-002', NULL, '{\"id\":5,\"lot_number\":\"LOT-TST-178212-20260622-002\",\"product_id\":8,\"status\":\"OPEN\",\"quantity_received\":45.5,\"remaining_quantity\":45.5}', '127.0.0.1', 'CLI-Test-Suite', '2pjta99g72b0ikkt799d7hbhua', NULL, '2026-06-22 11:15:53'),
(481, 'Super Admin', 'UPDATE', 'lots', 'LOT-TST-178212-20260622-002', 'Closed lot: LOT-TST-178212-20260622-002', '{\"id\":\"5\",\"lot_number\":\"LOT-TST-178212-20260622-002\",\"product_id\":\"8\",\"supplier_id\":null,\"received_date\":\"2026-06-22\",\"quantity_received\":\"45.500\",\"remaining_quantity\":\"0.000\",\"unit_cost\":null,\"currency_id\":null,\"exchange_rate\":null,\"status\":\"OPEN\",\"closing_date\":null,\"description\":\"\",\"created_by\":\"2\",\"created_at\":\"2026-06-22 13:15:53\",\"warehouse_id\":\"4\"}', '{\"id\":\"5\",\"lot_number\":\"LOT-TST-178212-20260622-002\",\"product_id\":\"8\",\"supplier_id\":null,\"received_date\":\"2026-06-22\",\"quantity_received\":\"45.500\",\"remaining_quantity\":\"0.000\",\"unit_cost\":null,\"currency_id\":null,\"exchange_rate\":null,\"status\":\"CLOSED\",\"closing_date\":\"2026-06-22\",\"description\":\"\",\"created_by\":\"2\",\"created_at\":\"2026-06-22 13:15:53\",\"warehouse_id\":\"4\"}', '127.0.0.1', 'CLI-Test-Suite', 'bhrdfgpi1ak27hv1csif8m8enh', NULL, '2026-06-22 11:15:54'),
(482, 'Super Admin', 'CREATE', 'lots', 'LOT-TST-178212-20260622-003', 'Created lot: LOT-TST-178212-20260622-003', NULL, '{\"id\":6,\"lot_number\":\"LOT-TST-178212-20260622-003\",\"product_id\":8,\"status\":\"OPEN\",\"quantity_received\":250,\"remaining_quantity\":250}', '127.0.0.1', 'CLI-Test-Suite', '0gm2assgo6f4ra4c1lqq2v8e8q', NULL, '2026-06-22 11:15:54'),
(483, 'Super Admin', 'DELETE', 'lots', 'LOT-TST-178212-20260622-003', 'Deleted lot: LOT-TST-178212-20260622-003', '{\"id\":\"6\",\"lot_number\":\"LOT-TST-178212-20260622-003\",\"product_id\":\"8\",\"supplier_id\":null,\"received_date\":\"2026-06-22\",\"quantity_received\":\"250.000\",\"remaining_quantity\":\"250.000\",\"unit_cost\":null,\"currency_id\":null,\"exchange_rate\":null,\"status\":\"OPEN\",\"closing_date\":null,\"description\":\"\",\"created_by\":\"2\",\"created_at\":\"2026-06-22 13:15:54\",\"warehouse_id\":\"4\"}', NULL, '127.0.0.1', 'CLI-Test-Suite', 'gue43f2ijd06kpvlvs7oopi10g', NULL, '2026-06-22 11:15:54'),
(484, 'Super Admin', 'CREATE', 'product_categories', '001', 'Created product category: Mineral', NULL, '{\"id\":4,\"category_code\":\"001\",\"category_name\":\"Mineral\",\"description\":\"to viewing all details only\",\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '1t97ijoloph0svs874a3pitahh', NULL, '2026-06-22 12:27:01'),
(485, 'Super Admin', 'VIEW', 'product_categories', 'Product Categories List', 'User viewed the product categories list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '1t97ijoloph0svs874a3pitahh', NULL, '2026-06-22 12:27:01'),
(486, 'Super Admin', 'CREATE', 'products', 'SN', 'Created product: Tin', NULL, '{\"id\":9,\"code\":\"SN\",\"name\":\"Tin\",\"full_name\":\"Sn02\",\"unit_of_measure\":\"kg\",\"description\":\"to viewing all details only\",\"inventory_account_id\":14,\"sales_account_id\":10,\"cogs_account_id\":17,\"category_id\":4,\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '1t97ijoloph0svs874a3pitahh', NULL, '2026-06-22 12:27:34'),
(487, 'Super Admin', 'VIEW', 'products', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '1t97ijoloph0svs874a3pitahh', NULL, '2026-06-22 12:27:34'),
(488, 'Super Admin', 'CREATE', 'suppliers', 'Eugene ndayishimiye', 'Created supplier: Eugene ndayishimiye (cooperative)', NULL, '{\"id\":5,\"supplier_type\":\"cooperative\",\"name\":\"Eugene ndayishimiye\",\"nif\":\"09987\",\"vat_reg_no\":\"VAT334\",\"phone\":\"+250785750117\",\"email\":\"nendayishimye@gmail.com\",\"address\":\"Unnamed Road\",\"payables_account_id\":2,\"currency_id\":2,\"region\":\"Rutsiro\",\"is_active\":1,\"notes\":\"done\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '1t97ijoloph0svs874a3pitahh', NULL, '2026-06-22 12:27:56'),
(489, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '1t97ijoloph0svs874a3pitahh', NULL, '2026-06-22 12:27:56'),
(490, 'Super Admin', 'CREATE', 'lots', 'LOT-SN-20260622-001', 'Created lot: LOT-SN-20260622-001', NULL, '{\"id\":7,\"lot_number\":\"LOT-SN-20260622-001\",\"product_id\":9,\"status\":\"OPEN\",\"quantity_received\":50,\"remaining_quantity\":50}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '1t97ijoloph0svs874a3pitahh', NULL, '2026-06-22 12:42:31'),
(491, 'Super Admin', 'VIEW', 'lots', 'Lots List', 'User viewed the lots list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '1t97ijoloph0svs874a3pitahh', NULL, '2026-06-22 12:42:31'),
(492, 'Super Admin', 'UPDATE', 'currencies', 'USD', 'Updated currency: Us Dorall (USD)', '{\"id\":\"2\",\"code\":\"USD\",\"name\":\"Us Dorall\",\"symbol\":\"$\",\"is_base_currency\":\"0\",\"is_active\":\"1\",\"created_at\":\"2026-06-17 00:21:06\",\"created_by\":\"2\",\"updated_at\":\"2026-06-17 00:21:17\",\"updated_by\":null}', '{\"id\":2,\"code\":\"USD\",\"name\":\"Us Dorall\",\"symbol\":\"$\",\"is_base_currency\":1,\"is_active\":1,\"updated_by\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '1t97ijoloph0svs874a3pitahh', NULL, '2026-06-22 12:43:55'),
(493, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '1t97ijoloph0svs874a3pitahh', NULL, '2026-06-22 12:43:55'),
(494, 'Super Admin', 'DELETE', 'lots', 'LOT-SN-20260622-001', 'Deleted lot: LOT-SN-20260622-001', '{\"id\":\"7\",\"lot_number\":\"LOT-SN-20260622-001\",\"product_id\":\"9\",\"supplier_id\":\"5\",\"received_date\":\"2026-06-22\",\"quantity_received\":\"50.000\",\"remaining_quantity\":\"50.000\",\"unit_cost\":\"1000.00\",\"currency_id\":\"1\",\"exchange_rate\":\"1.000000\",\"status\":\"OPEN\",\"closing_date\":null,\"description\":\"\",\"created_by\":\"2\",\"created_at\":\"2026-06-22 14:42:31\",\"warehouse_id\":\"2\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '1t97ijoloph0svs874a3pitahh', NULL, '2026-06-22 12:44:41'),
(495, 'Super Admin', 'VIEW', 'lots', 'Lots List', 'User viewed the lots list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '1t97ijoloph0svs874a3pitahh', NULL, '2026-06-22 12:44:41'),
(496, 'Super Admin', 'CREATE', 'account_types', '1100', 'Created new account type: prepeyment (1100)', NULL, '{\"id\":7,\"code\":\"1100\",\"name\":\"prepeyment\",\"parent_id\":1,\"is_editable\":1,\"is_deletable\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'g44jusja791go6jfmbhcup443s', NULL, '2026-06-22 20:39:42'),
(497, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'g44jusja791go6jfmbhcup443s', NULL, '2026-06-22 20:39:42'),
(498, 'Super Admin', 'VIEW', 'lots', 'Lots List', 'User viewed the lots list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'fa959svbhu66t27qovn01lmkkh', NULL, '2026-06-23 19:30:02'),
(499, 'Super Admin', 'VIEW', 'lots', 'Lots List', 'User viewed the lots list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ousb4uahrnd11chgmss58qquao', NULL, '2026-06-24 00:30:19'),
(500, 'Super Admin', 'VIEW', 'lots', 'Lots List', 'User viewed the lots list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ousb4uahrnd11chgmss58qquao', NULL, '2026-06-24 00:30:20'),
(501, 'Super Admin', 'VIEW', 'lots', 'Lots List', 'User viewed the lots list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'ousb4uahrnd11chgmss58qquao', NULL, '2026-06-24 00:30:21'),
(502, 'Super Admin', 'CREATE', 'account_types', '2001', 'Created new account type: Accounts Payable (2001)', NULL, '{\"id\":3,\"code\":\"2001\",\"name\":\"Accounts Payable\",\"parent_id\":-2,\"is_editable\":1,\"is_deletable\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'dmfipm2f226sr8rn94q3lscdir', NULL, '2026-06-24 02:53:18'),
(503, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'dmfipm2f226sr8rn94q3lscdir', NULL, '2026-06-24 02:53:18'),
(504, 'Super Admin', 'CREATE', 'account_types', '2002', 'Created new account type: Accounts Payable (2002)', NULL, '{\"id\":4,\"code\":\"2002\",\"name\":\"Accounts Payable\",\"parent_id\":-2,\"is_editable\":1,\"is_deletable\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'dmfipm2f226sr8rn94q3lscdir', NULL, '2026-06-24 02:53:35'),
(505, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'dmfipm2f226sr8rn94q3lscdir', NULL, '2026-06-24 02:53:35'),
(506, 'Super Admin', 'DELETE', 'account_types', '2002', 'Deleted account type: Accounts Payable (2002)', '{\"id\":\"4\",\"code\":\"2002\",\"name\":\"Accounts Payable\",\"parent_id\":\"-2\",\"is_editable\":\"1\",\"is_deletable\":\"1\",\"created_at\":\"2026-06-24 04:53:35\",\"updated_at\":\"2026-06-24 04:53:35\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'dmfipm2f226sr8rn94q3lscdir', NULL, '2026-06-24 02:56:09'),
(507, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'dmfipm2f226sr8rn94q3lscdir', NULL, '2026-06-24 02:56:09'),
(508, 'Super Admin', 'CREATE', 'account_types', '2002', 'Created new account type: Accounts Payables (2002)', NULL, '{\"id\":5,\"code\":\"2002\",\"name\":\"Accounts Payables\",\"parent_id\":-2,\"is_editable\":1,\"is_deletable\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'dmfipm2f226sr8rn94q3lscdir', NULL, '2026-06-24 02:56:27'),
(509, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'dmfipm2f226sr8rn94q3lscdir', NULL, '2026-06-24 02:56:27'),
(510, 'Super Admin', 'DELETE', 'account_types', '2002', 'Deleted account type: Accounts Payables (2002)', '{\"id\":\"5\",\"code\":\"2002\",\"name\":\"Accounts Payables\",\"parent_id\":\"-2\",\"is_editable\":\"1\",\"is_deletable\":\"1\",\"created_at\":\"2026-06-24 04:56:27\",\"updated_at\":\"2026-06-24 04:56:27\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'dmfipm2f226sr8rn94q3lscdir', NULL, '2026-06-24 02:56:32'),
(511, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'dmfipm2f226sr8rn94q3lscdir', NULL, '2026-06-24 02:56:32');

-- --------------------------------------------------------

--
-- Table structure for table `currencies`
--

CREATE TABLE `currencies` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `code` varchar(5) NOT NULL,
  `name` varchar(50) NOT NULL,
  `symbol` varchar(5) DEFAULT NULL,
  `is_base_currency` tinyint(1) DEFAULT 0 COMMENT 'If 1, this is USD (base reporting currency)',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `currencies`
--

INSERT INTO `currencies` (`id`, `code`, `name`, `symbol`, `is_base_currency`, `is_active`, `created_at`, `created_by`, `updated_at`, `updated_by`) VALUES
(1, 'RWF', 'RWANDAN FRANGS', 'Rwf', 0, 1, '2026-06-16 22:01:30', 2, '2026-06-22 12:43:55', 2),
(2, 'USD', 'Us Dorall', '$', 1, 1, '2026-06-16 22:21:06', 2, '2026-06-22 12:43:55', 2);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` bigint(20) NOT NULL,
  `customer_code` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `adress` text NOT NULL,
  `currency_id` bigint(20) DEFAULT NULL,
  `receivable_account_id` bigint(20) DEFAULT NULL,
  `credit_limit` decimal(18,2) DEFAULT 0.00 COMMENT 'nideni ntarengwa umukiriya yemerewe kubamo company iyo rirenze system yanga kumuhereza ideni keretse abanje kwishyura',
  `payment_term_days` int(11) DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_payments`
--

CREATE TABLE `customer_payments` (
  `id` bigint(20) NOT NULL,
  `payment_number` varchar(100) NOT NULL,
  `customer_id` bigint(20) NOT NULL,
  `account_id` bigint(20) NOT NULL,
  `currency_id` bigint(20) NOT NULL,
  `exchange_rate` decimal(18,6) NOT NULL DEFAULT 1.000000,
  `amount_currency` decimal(18,2) NOT NULL,
  `amount_base` decimal(18,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('CASH','BANK_TRANSFER','CHEQUE','MOBILE_MONEY') NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('DRAFT','POSTED','CANCELLED') DEFAULT 'DRAFT',
  `journal_entry_id` bigint(20) DEFAULT NULL,
  `created_by` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_payment_allocations`
--

CREATE TABLE `customer_payment_allocations` (
  `id` bigint(20) NOT NULL,
  `customer_payment_id` bigint(20) NOT NULL,
  `sale_id` bigint(20) NOT NULL,
  `amount_allocated` decimal(18,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exchange_rates`
--

CREATE TABLE `exchange_rates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `from_currency_id` tinyint(3) UNSIGNED NOT NULL,
  `to_currency_id` tinyint(3) UNSIGNED NOT NULL,
  `rate` decimal(20,8) NOT NULL COMMENT 'Units of from_currency per 1 unit of to_currency',
  `rate_date` date NOT NULL,
  `source` varchar(100) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exchange_rates`
--

INSERT INTO `exchange_rates` (`id`, `from_currency_id`, `to_currency_id`, `rate`, `rate_date`, `source`, `created_by`, `created_at`, `updated_by`, `updated_at`) VALUES
(2, 1, 2, 1400.00000000, '2026-06-17', 'bnr', 2, '2026-06-17 11:49:17', 2, '2026-06-17 11:49:48');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_counts`
--

CREATE TABLE `inventory_counts` (
  `id` bigint(20) NOT NULL,
  `count_number` varchar(100) DEFAULT NULL,
  `warehouse_id` bigint(20) DEFAULT NULL,
  `count_date` date DEFAULT NULL,
  `status` enum('DRAFT','POSTED') DEFAULT 'DRAFT'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_count_items`
--

CREATE TABLE `inventory_count_items` (
  `id` bigint(20) NOT NULL,
  `inventory_count_id` bigint(20) DEFAULT NULL,
  `product_id` bigint(20) DEFAULT NULL,
  `lot_id` bigint(20) DEFAULT NULL,
  `system_quantity` decimal(18,3) DEFAULT NULL,
  `physical_quantity` decimal(18,3) DEFAULT NULL,
  `variance_quantity` decimal(18,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journal_entries`
--

CREATE TABLE `journal_entries` (
  `id` bigint(20) NOT NULL,
  `journal_no` varchar(100) DEFAULT NULL,
  `entry_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `statuss` enum('DRAFT','POSTED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  `created_by` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journal_entry_lines`
--

CREATE TABLE `journal_entry_lines` (
  `id` bigint(20) NOT NULL,
  `journal_entry_id` bigint(20) NOT NULL,
  `account_id` bigint(20) NOT NULL,
  `debit` decimal(18,2) DEFAULT 0.00,
  `credit` decimal(18,2) DEFAULT 0.00,
  `currency_id` bigint(20) NOT NULL,
  `exchange_rate` decimal(18,6) DEFAULT 1.000000,
  `amount_currency` decimal(18,2) NOT NULL,
  `amount_base` decimal(18,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `status` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`id`, `user_id`, `email`, `login_time`, `ip_address`, `status`) VALUES
(1, NULL, 'admin@inezamining.rw', '2026-06-14 20:02:19', '127.0.0.1', 'invalid_email'),
(4, 2, 'admin@gmail.com', '2026-06-14 20:19:11', '::1', 'invalid_password'),
(5, 2, 'admin@gmail.com', '2026-06-14 20:19:18', '::1', 'invalid_password'),
(7, 2, 'admin@gmail.com', '2026-06-14 20:21:41', '::1', 'success'),
(8, 2, 'admin@gmail.com', '2026-06-14 20:50:23', '::1', 'invalid_password'),
(9, 2, 'admin@gmail.com', '2026-06-14 20:51:24', '::1', 'success'),
(10, 2, 'admin@gmail.com', '2026-06-14 20:53:58', '::1', 'success'),
(11, 2, 'admin@gmail.com', '2026-06-14 21:04:37', '::1', 'success'),
(12, 2, 'admin@gmail.com', '2026-06-14 21:04:54', '::1', 'success'),
(13, 2, 'admin@gmail.com', '2026-06-14 21:10:38', '::1', 'success'),
(14, 2, 'admin@gmail.com', '2026-06-14 21:10:50', '::1', 'success'),
(15, 2, 'admin@gmail.com', '2026-06-14 21:11:01', '::1', 'success'),
(16, 2, 'admin@gmail.com', '2026-06-14 21:13:58', '::1', 'success'),
(17, 2, 'admin@gmail.com', '2026-06-14 21:14:37', '::1', 'success'),
(18, 2, 'admin@gmail.com', '2026-06-14 21:15:00', '::1', 'success'),
(19, 2, 'admin@gmail.com', '2026-06-15 17:41:58', '::1', 'success'),
(20, 2, 'admin@gmail.com', '2026-06-15 18:04:56', '::1', 'success'),
(21, 2, 'admin@gmail.com', '2026-06-15 18:07:19', '::1', 'success'),
(22, NULL, 'manzi@gmail.com', '2026-06-16 20:37:31', '::1', 'invalid_email'),
(23, 2, 'admin@gmail.com', '2026-06-16 20:37:40', '::1', 'success'),
(24, 2, 'admin@gmail.com', '2026-06-17 08:12:27', '::1', 'success'),
(25, 2, 'admin@gmail.com', '2026-06-17 08:32:22', '::1', 'success'),
(26, 2, 'admin@gmail.com', '2026-06-17 08:53:55', '::1', 'invalid_password'),
(27, 2, 'admin@gmail.com', '2026-06-17 08:54:01', '::1', 'invalid_password'),
(28, 2, 'admin@gmail.com', '2026-06-17 08:54:24', '::1', 'success'),
(29, 2, 'admin@gmail.com', '2026-06-17 09:01:21', '::1', 'success'),
(30, 2, 'admin@gmail.com', '2026-06-17 09:08:47', '::1', 'success'),
(31, 2, 'admin@gmail.com', '2026-06-19 08:57:12', '::1', 'success'),
(32, 2, 'admin@gmail.com', '2026-06-20 14:59:06', '::1', 'success'),
(33, 2, 'admin@gmail.com', '2026-06-20 15:23:30', '::1', 'success'),
(34, 5, 'eugene@gmail.com', '2026-06-20 15:26:58', '::1', 'success'),
(35, 2, 'admin@gmail.com', '2026-06-20 15:27:54', '::1', 'success'),
(36, NULL, 'manzi@gmail.com', '2026-06-20 15:28:25', '::1', 'invalid_email'),
(37, 5, 'eugene@gmail.com', '2026-06-20 15:28:38', '::1', 'success'),
(38, 2, 'admin@gmail.com', '2026-06-20 15:30:53', '::1', 'success'),
(39, 5, 'eugene@gmail.com', '2026-06-20 16:01:33', '::1', 'success'),
(40, 2, 'admin@gmail.com', '2026-06-20 16:04:06', '::1', 'success'),
(41, 5, 'eugene@gmail.com', '2026-06-20 16:05:05', '::1', 'success'),
(42, 2, 'admin@gmail.com', '2026-06-20 16:05:30', '::1', 'success'),
(43, 5, 'eugene@gmail.com', '2026-06-20 16:17:17', '::1', 'success'),
(44, 2, 'admin@gmail.com', '2026-06-20 16:17:50', '::1', 'success'),
(45, 2, 'admin@gmail.com', '2026-06-21 13:34:43', '::1', 'success'),
(46, 2, 'admin@gmail.com', '2026-06-21 22:40:04', '::1', 'success'),
(47, 5, 'eugene@gmail.com', '2026-06-21 23:01:26', '::1', 'success'),
(48, 2, 'admin@gmail.com', '2026-06-21 23:01:42', '::1', 'success'),
(49, 2, 'admin@gmail.com', '2026-06-21 23:16:33', '::1', 'success'),
(50, 2, 'admin@gmail.com', '2026-06-21 23:26:50', '::1', 'success'),
(51, 2, 'admin@gmail.com', '2026-06-21 23:33:53', '::1', 'success'),
(52, 2, 'admin@gmail.com', '2026-06-21 23:44:34', '::1', 'success'),
(53, 2, 'admin@gmail.com', '2026-06-22 00:05:49', '::1', 'success'),
(54, 2, 'admin@gmail.com', '2026-06-22 00:12:11', '::1', 'success'),
(55, 2, 'admin@gmail.com', '2026-06-22 08:26:55', '::1', 'success'),
(56, 5, 'eugene@gmail.com', '2026-06-22 08:59:14', '::1', 'success'),
(57, 5, 'eugene@gmail.com', '2026-06-22 08:59:53', '::1', 'success'),
(58, 2, 'admin@gmail.com', '2026-06-22 09:00:03', '::1', 'success'),
(59, 2, 'admin@gmail.com', '2026-06-22 13:03:42', '::1', 'success'),
(60, 2, 'admin@gmail.com', '2026-06-22 20:33:57', '::1', 'success'),
(61, 2, 'admin@gmail.com', '2026-06-23 13:51:23', '::1', 'success'),
(62, 2, 'admin@gmail.com', '2026-06-23 13:55:33', '::1', 'success'),
(63, 5, 'eugene@gmail.com', '2026-06-23 21:03:20', '::1', 'success'),
(64, 2, 'admin@gmail.com', '2026-06-24 00:17:30', '::1', 'success'),
(65, 2, 'admin@gmail.com', '2026-06-24 00:28:07', '::1', 'success'),
(66, 2, 'admin@gmail.com', '2026-06-24 00:34:38', '::1', 'success'),
(67, 2, 'admin@gmail.com', '2026-06-24 02:08:22', '::1', 'success'),
(68, 2, 'admin@gmail.com', '2026-06-24 02:35:12', '::1', 'success');

-- --------------------------------------------------------

--
-- Table structure for table `lots`
--

CREATE TABLE `lots` (
  `id` bigint(20) NOT NULL,
  `lot_number` varchar(100) DEFAULT NULL,
  `product_id` bigint(20) NOT NULL,
  `supplier_id` bigint(20) DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `quantity_received` decimal(18,3) DEFAULT NULL,
  `remaining_quantity` decimal(18,3) DEFAULT NULL,
  `unit_cost` decimal(18,2) DEFAULT NULL,
  `currency_id` bigint(20) DEFAULT NULL,
  `exchange_rate` decimal(18,6) DEFAULT NULL,
  `status` enum('OPEN','CLOSED') DEFAULT 'OPEN',
  `closing_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `warehouse_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `permition_name` varchar(100) NOT NULL,
  `permition_code` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `permition_name`, `permition_code`, `created_at`) VALUES
(5, 'View Users', 'view_users', '2026-06-16 22:46:11'),
(6, 'Create User', 'create_user', '2026-06-16 22:46:11'),
(7, 'Edit User', 'edit_user', '2026-06-16 22:46:11'),
(8, 'Delete User', 'delete_user', '2026-06-16 22:46:11'),
(9, 'View Roles', 'view_roles', '2026-06-16 23:03:56'),
(10, 'Create Role', 'create_role', '2026-06-16 23:03:56'),
(11, 'Edit Role', 'edit_role', '2026-06-16 23:03:56'),
(12, 'Delete Role', 'delete_role', '2026-06-16 23:03:56'),
(13, 'View Permissions', 'view_permissions', '2026-06-16 23:17:10'),
(14, 'Create Permission', 'create_permission', '2026-06-16 23:17:10'),
(15, 'Edit Permission', 'edit_permission', '2026-06-16 23:17:10'),
(16, 'Delete Permission', 'delete_permission', '2026-06-16 23:17:10'),
(18, 'View Audit Logs', 'view_audit_logs', '2026-06-16 23:28:47'),
(19, 'View Products', 'view_products', '2026-06-16 23:37:27'),
(20, 'Create Product', 'create_product', '2026-06-16 23:37:27'),
(21, 'Edit Product', 'edit_product', '2026-06-16 23:37:27'),
(22, 'Delete Product', 'delete_product', '2026-06-16 23:37:27'),
(23, 'View Product Elements', 'view_product_elements', '2026-06-16 23:49:13'),
(24, 'Create Product Element', 'create_product_element', '2026-06-16 23:49:13'),
(25, 'Edit Product Element', 'edit_product_element', '2026-06-16 23:49:13'),
(26, 'Delete Product Element', 'delete_product_element', '2026-06-16 23:49:13'),
(27, 'View Suppliers', 'view_suppliers', '2026-06-16 23:56:33'),
(28, 'Create Supplier', 'create_supplier', '2026-06-16 23:56:33'),
(29, 'Edit Supplier', 'edit_supplier', '2026-06-16 23:56:33'),
(30, 'Delete Supplier', 'delete_supplier', '2026-06-16 23:56:33'),
(31, 'View Account Types', 'view_account_types', '2026-06-19 14:00:21'),
(32, 'Create Account Type', 'create_account_type', '2026-06-19 14:00:21'),
(33, 'Edit Account Type', 'edit_account_type', '2026-06-19 14:00:21'),
(34, 'Delete Account Type', 'delete_account_type', '2026-06-19 14:00:21'),
(35, 'View Accounts', 'view_accounts', '2026-06-19 14:00:21'),
(36, 'Create Account', 'create_account', '2026-06-19 14:00:21'),
(37, 'Edit Account', 'edit_account', '2026-06-19 14:00:21'),
(38, 'Delete Account', 'delete_account', '2026-06-19 14:00:21'),
(40, 'View Product Categories', 'view_product_categories', '2026-06-21 22:51:32'),
(41, 'Create Product Category', 'create_product_category', '2026-06-21 22:51:32'),
(42, 'Edit Product Category', 'edit_product_category', '2026-06-21 22:51:32'),
(43, 'Delete Product Category', 'delete_product_category', '2026-06-21 22:51:32'),
(44, 'View Warehouses', 'view_warehouses', '2026-06-21 23:39:27'),
(45, 'Create Warehouse', 'create_warehouse', '2026-06-21 23:39:27'),
(46, 'Edit Warehouse', 'edit_warehouse', '2026-06-21 23:39:27'),
(47, 'Delete Warehouse', 'delete_warehouse', '2026-06-21 23:39:27'),
(48, 'View Lots', 'view_lots', '2026-06-22 09:58:11'),
(49, 'Create Lot', 'create_lot', '2026-06-22 09:58:11'),
(50, 'Edit Lot', 'edit_lot', '2026-06-22 09:58:11'),
(51, 'Delete Lot', 'delete_lot', '2026-06-22 09:58:11'),
(52, 'Open Lot', 'open_lot', '2026-06-22 09:58:11'),
(53, 'Close Lot', 'close_lot', '2026-06-22 09:58:11');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `code` varchar(10) NOT NULL COMMENT 'e.g. Sn, Ta, Nb',
  `name` varchar(100) NOT NULL COMMENT 'e.g. Tin, Tantalum, Niobium',
  `full_name` varchar(150) DEFAULT NULL,
  `unit_of_measure` varchar(20) DEFAULT 'kg' COMMENT 'kg, tonnes, etc.',
  `description` text DEFAULT NULL,
  `inventory_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sales_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cogs_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `category_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `code`, `name`, `full_name`, `unit_of_measure`, `description`, `inventory_account_id`, `sales_account_id`, `cogs_account_id`, `is_active`, `created_at`, `created_by`, `updated_at`, `updated_by`, `category_id`) VALUES
(9, 'SN', 'Tin', 'Sn02', 'kg', 'to viewing all details only', 14, 10, 17, 1, '2026-06-22 12:27:34', 2, '2026-06-22 12:27:34', NULL, 4);

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` bigint(20) NOT NULL,
  `category_code` varchar(50) DEFAULT NULL,
  `category_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `category_code`, `category_name`, `description`, `is_active`, `created_at`) VALUES
(4, '001', 'Mineral', 'to viewing all details only', 1, '2026-06-22 12:27:01');

-- --------------------------------------------------------

--
-- Table structure for table `product_elements`
--

CREATE TABLE `product_elements` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `product_id` smallint(5) UNSIGNED NOT NULL,
  `element_code` varchar(20) NOT NULL COMMENT 'e.g. Sn%, Fe%, Ta205%, Ta%, Nb205%',
  `element_name` varchar(100) NOT NULL,
  `unit` varchar(20) DEFAULT '%' COMMENT 'Percent grade, ppm, etc.',
  `display_order` tinyint(3) UNSIGNED DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Grade elements per product (e.g. Tin has Sn%, Fe%; Tantalum has Ta205%, Nb205%, Fe%)';

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` bigint(20) NOT NULL,
  `purchase_number` varchar(100) DEFAULT NULL,
  `supplier_id` bigint(20) NOT NULL,
  `currency_id` bigint(20) DEFAULT NULL,
  `exchange_rate` decimal(18,6) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `subtotal` decimal(18,2) DEFAULT NULL,
  `discount` decimal(18,2) DEFAULT NULL,
  `tax` decimal(18,2) DEFAULT NULL,
  `total` decimal(18,2) DEFAULT NULL,
  `journal_entry_id` bigint(20) NOT NULL,
  `status` enum('DRAFT','POSTED') DEFAULT 'DRAFT',
  `warehouse_id` bigint(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` bigint(20) NOT NULL,
  `purchase_id` bigint(20) NOT NULL,
  `lot_id` bigint(20) DEFAULT NULL,
  `product_id` bigint(20) DEFAULT NULL,
  `quantity` decimal(18,3) DEFAULT NULL,
  `unit_price` decimal(18,2) DEFAULT NULL,
  `amount` decimal(18,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'admin', 'admin management', '2026-06-14 19:51:59'),
(2, 'secretary', 'all add', '2026-06-16 23:10:23'),
(3, 'view only', 'to viewing all details only', '2026-06-20 15:25:53');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 48),
(1, 49),
(1, 50),
(1, 51),
(1, 52),
(1, 53),
(2, 7),
(2, 10),
(2, 11),
(3, 19),
(3, 27),
(3, 31),
(3, 35),
(3, 40);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` bigint(20) NOT NULL,
  `sale_number` varchar(100) DEFAULT NULL,
  `customer_id` bigint(20) DEFAULT NULL,
  `currency_id` bigint(20) DEFAULT NULL,
  `exchange_rate` decimal(18,6) DEFAULT NULL,
  `sale_date` date DEFAULT NULL,
  `subtotal` decimal(18,2) DEFAULT NULL,
  `discount` decimal(18,2) DEFAULT NULL,
  `tax` decimal(18,2) DEFAULT NULL,
  `total` decimal(18,2) DEFAULT NULL,
  `journal_entry_id` bigint(20) NOT NULL,
  `status` enum('DRAFT','POSTED') DEFAULT 'DRAFT',
  `warehouse_id` bigint(20) NOT NULL,
  `created_by` bigint(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` bigint(20) NOT NULL,
  `sale_id` bigint(20) DEFAULT NULL,
  `lot_id` bigint(20) DEFAULT NULL,
  `product_id` bigint(20) DEFAULT NULL,
  `quantity` decimal(18,3) DEFAULT NULL,
  `unit_price` decimal(18,2) DEFAULT NULL,
  `amount` decimal(18,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustments`
--

CREATE TABLE `stock_adjustments` (
  `id` bigint(20) NOT NULL,
  `adjustment_number` varchar(100) DEFAULT NULL,
  `warehouse_id` bigint(20) DEFAULT NULL,
  `adjustment_date` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('DRAFT','POSTED') DEFAULT 'DRAFT',
  `created_by` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustment_items`
--

CREATE TABLE `stock_adjustment_items` (
  `id` bigint(20) NOT NULL,
  `stock_adjustment_id` bigint(20) DEFAULT NULL,
  `lot_id` bigint(20) DEFAULT NULL,
  `product_id` bigint(20) DEFAULT NULL,
  `quantity` decimal(18,3) DEFAULT NULL,
  `adjustment_type` enum('INCREASE','DECREASE') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` bigint(20) NOT NULL,
  `product_id` bigint(20) DEFAULT NULL,
  `lot_id` bigint(20) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'igaragaza icyateye movement ese habayeho purchase, sells, ADJUSTMENT, return, transfer ',
  `reference_id` bigint(20) DEFAULT NULL,
  `quantity_in` decimal(18,3) DEFAULT 0.000,
  `quantity_out` decimal(18,3) DEFAULT 0.000,
  `movement_date` date DEFAULT NULL,
  `warehouse_id` bigint(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `supplier_type` enum('individual','cooperative','company') DEFAULT 'individual',
  `name` varchar(200) NOT NULL,
  `nif` varchar(50) DEFAULT NULL COMMENT 'Tax Identification Number',
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `supplier_type`, `name`, `nif`, `phone`, `email`, `address`, `is_active`, `notes`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(5, 'cooperative', 'Eugene ndayishimiye', '09987', '+250785750117', 'nendayishimye@gmail.com', 'Unnamed Road', 1, 'done', '2026-06-22 12:27:56', '2026-06-22 12:27:56', 2, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `supplier_advances`
--

CREATE TABLE `supplier_advances` (
  `id` bigint(20) NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `currency_id` tinyint(3) UNSIGNED NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `exchange_rate` decimal(18,6) DEFAULT 1.000000,
  `advance_date` date NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `status` enum('PAID','RECONCILED','CANCELLED') DEFAULT 'PAID',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_payments`
--

CREATE TABLE `supplier_payments` (
  `id` bigint(20) NOT NULL,
  `supplier_id` bigint(20) DEFAULT NULL,
  `account_id` bigint(20) DEFAULT NULL,
  `currency_id` bigint(20) DEFAULT NULL,
  `exchange_rate` decimal(18,6) DEFAULT NULL,
  `amount_currency` decimal(18,2) DEFAULT NULL,
  `amount_base` decimal(18,6) NOT NULL,
  `journal_entry_id` bigint(20) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `payment_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_payment_allocations`
--

CREATE TABLE `supplier_payment_allocations` (
  `id` bigint(20) NOT NULL,
  `supplier_payment_id` bigint(20) NOT NULL,
  `purchase_id` bigint(20) NOT NULL,
  `amount_allocated` decimal(18,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `img` text DEFAULT NULL,
  `phone_number` varchar(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `img`, `phone_number`, `email`, `password_hash`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'Super', 'Admin', NULL, NULL, 'admin@gmail.com', '$2y$10$9fKyxiZ.bv9j0vl119rIRu1L9n61aT5gbc47jOMU2uC7.vyXhtbLy', 1, '2026-06-14 19:59:52', '2026-06-14 20:51:21'),
(5, 'eugene', 'ndayishimiye', NULL, '0785750117', 'eugene@gmail.com', '$2y$10$Kt7JJRJpdUO/spSlVO3o.O6DMa21eKF/iauzsmJnCayrh5Qeeyjnm', 1, '2026-06-20 15:26:41', '2026-06-20 15:26:41');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
(2, 1),
(5, 3);

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

CREATE TABLE `warehouses` (
  `id` bigint(20) NOT NULL,
  `warehouse_code` varchar(50) DEFAULT NULL,
  `warehouse_name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`id`, `warehouse_code`, `warehouse_name`, `address`, `is_active`, `created_at`, `created_by`) VALUES
(2, 'KIGALI', 'Kicukiro', 'KK05', 1, '2026-06-22 10:58:30', 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_code` (`account_code`),
  ADD KEY `fk_accounts_account_type` (`account_type_id`);

--
-- Indexes for table `account_types`
--
ALTER TABLE `account_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `fk_account_type_parent` (`parent_id`);

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_al_user_name` (`user_full_name`),
  ADD KEY `idx_al_action` (`action`),
  ADD KEY `idx_al_target_table` (`target_table`),
  ADD KEY `idx_al_performed` (`performed_at`);

--
-- Indexes for table `currencies`
--
ALTER TABLE `currencies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_code` (`customer_code`);

--
-- Indexes for table `customer_payments`
--
ALTER TABLE `customer_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_number` (`payment_number`);

--
-- Indexes for table `customer_payment_allocations`
--
ALTER TABLE `customer_payment_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_payment_id` (`customer_payment_id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `exchange_rates`
--
ALTER TABLE `exchange_rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_er_from` (`from_currency_id`),
  ADD KEY `fk_er_to` (`to_currency_id`),
  ADD KEY `fk_er_user` (`created_by`);

--
-- Indexes for table `inventory_counts`
--
ALTER TABLE `inventory_counts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `count_number` (`count_number`),
  ADD KEY `warehouse_id` (`warehouse_id`);

--
-- Indexes for table `inventory_count_items`
--
ALTER TABLE `inventory_count_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `journal_no` (`journal_no`);

--
-- Indexes for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_login_user` (`user_id`);

--
-- Indexes for table `lots`
--
ALTER TABLE `lots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lot_number` (`lot_number`),
  ADD KEY `warehouse_id` (`warehouse_id`),
  ADD KEY `fk_lots_user` (`created_by`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permition_name` (`permition_name`),
  ADD UNIQUE KEY `permition_code` (`permition_code`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_products_inventory_account` (`inventory_account_id`),
  ADD KEY `fk_products_sales_account` (`sales_account_id`),
  ADD KEY `fk_products_cogs_account` (`cogs_account_id`),
  ADD KEY `fk_products_category` (`category_id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_code` (`category_code`);

--
-- Indexes for table `product_elements`
--
ALTER TABLE `product_elements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pe_product` (`product_id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_number` (`purchase_number`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sale_number` (`sale_number`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `adjustment_number` (`adjustment_number`);

--
-- Indexes for table `stock_adjustment_items`
--
ALTER TABLE `stock_adjustment_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier_advances`
--
ALTER TABLE `supplier_advances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `currency_id` (`currency_id`);

--
-- Indexes for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier_payment_allocations`
--
ALTER TABLE `supplier_payment_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_payment_id` (`supplier_payment_id`),
  ADD KEY `purchase_id` (`purchase_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `warehouse_code` (`warehouse_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `account_types`
--
ALTER TABLE `account_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=512;

--
-- AUTO_INCREMENT for table `currencies`
--
ALTER TABLE `currencies`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_payments`
--
ALTER TABLE `customer_payments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_payment_allocations`
--
ALTER TABLE `customer_payment_allocations`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exchange_rates`
--
ALTER TABLE `exchange_rates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory_counts`
--
ALTER TABLE `inventory_counts`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_count_items`
--
ALTER TABLE `inventory_count_items`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `journal_entries`
--
ALTER TABLE `journal_entries`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `lots`
--
ALTER TABLE `lots`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product_elements`
--
ALTER TABLE `product_elements`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_adjustment_items`
--
ALTER TABLE `stock_adjustment_items`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `supplier_advances`
--
ALTER TABLE `supplier_advances`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_payment_allocations`
--
ALTER TABLE `supplier_payment_allocations`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `fk_accounts_account_type` FOREIGN KEY (`account_type_id`) REFERENCES `account_types` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `account_types`
--
ALTER TABLE `account_types`
  ADD CONSTRAINT `fk_account_type_parent` FOREIGN KEY (`parent_id`) REFERENCES `account_types` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `customer_payment_allocations`
--
ALTER TABLE `customer_payment_allocations`
  ADD CONSTRAINT `customer_payment_allocations_ibfk_1` FOREIGN KEY (`customer_payment_id`) REFERENCES `customer_payments` (`id`),
  ADD CONSTRAINT `customer_payment_allocations_ibfk_2` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`);

--
-- Constraints for table `exchange_rates`
--
ALTER TABLE `exchange_rates`
  ADD CONSTRAINT `fk_er_from` FOREIGN KEY (`from_currency_id`) REFERENCES `currencies` (`id`),
  ADD CONSTRAINT `fk_er_to` FOREIGN KEY (`to_currency_id`) REFERENCES `currencies` (`id`),
  ADD CONSTRAINT `fk_er_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_counts`
--
ALTER TABLE `inventory_counts`
  ADD CONSTRAINT `inventory_counts_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`);

--
-- Constraints for table `login`
--
ALTER TABLE `login`
  ADD CONSTRAINT `fk_login_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lots`
--
ALTER TABLE `lots`
  ADD CONSTRAINT `fk_lots_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lots_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_products_cogs_account` FOREIGN KEY (`cogs_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_products_inventory_account` FOREIGN KEY (`inventory_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_products_sales_account` FOREIGN KEY (`sales_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `product_elements`
--
ALTER TABLE `product_elements`
  ADD CONSTRAINT `fk_pe_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_advances`
--
ALTER TABLE `supplier_advances`
  ADD CONSTRAINT `fk_sa_currency` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`),
  ADD CONSTRAINT `fk_sa_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_payment_allocations`
--
ALTER TABLE `supplier_payment_allocations`
  ADD CONSTRAINT `supplier_payment_allocations_ibfk_1` FOREIGN KEY (`supplier_payment_id`) REFERENCES `supplier_payments` (`id`),
  ADD CONSTRAINT `supplier_payment_allocations_ibfk_2` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`);

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
