-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 19, 2026 at 03:50 PM
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
  `opening_balance` decimal(18,2) DEFAULT 0.00,
  `current_balance` decimal(18,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `account_type_id`, `account_code`, `account_name`, `opening_balance`, `current_balance`, `is_active`, `description`, `created_at`, `updated_at`) VALUES
(1, 2, '1110', 'Cash on Hand', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(2, 2, '1120', 'Bank USD', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(3, 2, '1130', 'Bank EUR', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(4, 2, '1140', 'Bank RWF', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(5, 2, '1150', 'Accounts Receivable', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(6, 2, '1160', 'Inventory', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(7, 3, '1210', 'Machinery and Equipment', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(8, 3, '1220', 'Vehicles', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(9, 3, '1230', 'Computers and IT Equipment', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(10, 5, '2110', 'Accounts Payable', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(11, 5, '2120', 'Taxes Payable', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(12, 6, '2210', 'Long-Term Loan', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(13, 7, '3110', 'Owner Capital', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(14, 7, '3120', 'Retained Earnings', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(15, 9, '4110', 'Mineral Sales Revenue', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(16, 10, '4210', 'Foreign Exchange Gain', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(17, 11, '5110', 'Cost of Goods Sold', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(18, 13, '6110', 'Salaries Expense', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(19, 13, '6120', 'Fuel Expense', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(20, 13, '6130', 'Transport Expense', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(21, 13, '6140', 'Utilities Expense', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(22, 14, '6210', 'Office Supplies Expense', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(23, 15, '6310', 'Marketing Expense', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(24, 16, '6410', 'Bank Charges', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05'),
(25, 16, '6420', 'Foreign Exchange Loss', 0.00, 0.00, 1, NULL, '2026-06-19 12:39:05', '2026-06-19 12:39:05');

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
(1, '1000', 'Assets', NULL, 0, 0, '2026-06-19 12:37:42', '2026-06-19 12:37:42'),
(2, '1100', 'Current Assets', 1, 0, 0, '2026-06-19 12:37:42', '2026-06-19 12:37:42'),
(3, '1200', 'Fixed Assets', 1, 0, 0, '2026-06-19 12:37:42', '2026-06-19 12:37:42'),
(4, '2000', 'Liabilities', NULL, 0, 0, '2026-06-19 12:37:42', '2026-06-19 12:37:42'),
(5, '2100', 'Current Liabilities', 4, 0, 0, '2026-06-19 12:37:42', '2026-06-19 12:37:42'),
(6, '2200', 'Long-Term Liabilities', 4, 0, 0, '2026-06-19 12:37:42', '2026-06-19 12:37:42'),
(7, '3000', 'Equity', NULL, 0, 0, '2026-06-19 12:37:42', '2026-06-19 12:37:42'),
(8, '4000', 'Revenue', NULL, 0, 0, '2026-06-19 12:37:42', '2026-06-19 12:37:42'),
(9, '4100', 'Operating Revenue', 8, 0, 0, '2026-06-19 12:37:42', '2026-06-19 12:37:42'),
(10, '4200', 'Other Revenue', 8, 0, 0, '2026-06-19 12:37:42', '2026-06-19 12:37:42'),
(11, '5000', 'Cost of Sales', NULL, 0, 0, '2026-06-19 12:37:42', '2026-06-19 12:37:42'),
(12, '6000', 'Expenses', NULL, 0, 0, '2026-06-19 12:37:42', '2026-06-19 12:37:42'),
(13, '6100', 'Operating Expenses', 12, 0, 0, '2026-06-19 12:37:42', '2026-06-19 12:37:42'),
(14, '6200', 'Administrative Expenses', 12, 0, 0, '2026-06-19 12:37:42', '2026-06-19 12:37:42'),
(15, '6300', 'Selling Expenses', 12, 0, 0, '2026-06-19 12:37:42', '2026-06-19 12:37:42'),
(16, '6400', 'Financial Expenses', 12, 0, 0, '2026-06-19 12:37:42', '2026-06-19 12:37:42');

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
(202, 'Super Admin', 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'mkdve1tlfo9o0khjmp13dbt9bn', NULL, '2026-06-19 13:43:43');

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
(1, 'RWF', 'RWANDAN FRANGS', 'Rwf', 1, 1, '2026-06-16 22:01:30', 2, '2026-06-16 22:21:17', 2),
(2, 'USD', 'Us Dorall', '$', 0, 1, '2026-06-16 22:21:06', 2, '2026-06-16 22:21:17', NULL);

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
(31, 2, 'admin@gmail.com', '2026-06-19 08:57:12', '::1', 'success');

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
(30, 'Delete Supplier', 'delete_supplier', '2026-06-16 23:56:33');

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
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `code`, `name`, `full_name`, `unit_of_measure`, `description`, `is_active`, `created_at`, `created_by`, `updated_at`, `updated_by`) VALUES
(1, 'SN', 'Tin', 'Cass', 'kg', '', 1, '2026-06-16 23:43:32', 2, '2026-06-16 23:43:32', NULL);

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
(2, 'secretary', 'all add', '2026-06-16 23:10:23');

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
(2, 7),
(2, 10),
(2, 11);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `supplier_type` enum('individual','cooperative','company') DEFAULT 'individual',
  `name` varchar(200) NOT NULL,
  `nif` varchar(50) DEFAULT NULL COMMENT 'Tax Identification Number',
  `vat_reg_no` varchar(50) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL COMMENT 'e.g. Rubaya, Ngororero',
  `is_active` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_advances`
--

CREATE TABLE `supplier_advances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `currency_id` tinyint(3) UNSIGNED NOT NULL,
  `advance_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('outstanding','partially_repaid','repaid','written_off') DEFAULT 'outstanding',
  `repaid_amount` decimal(18,4) DEFAULT 0.0000,
  `lot_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Linked lot if advance for specific batch',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
(2, 'Super', 'Admin', NULL, NULL, 'admin@gmail.com', '$2y$10$9fKyxiZ.bv9j0vl119rIRu1L9n61aT5gbc47jOMU2uC7.vyXhtbLy', 1, '2026-06-14 19:59:52', '2026-06-14 20:51:21');

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
(2, 1);

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
-- Indexes for table `exchange_rates`
--
ALTER TABLE `exchange_rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_er_from` (`from_currency_id`),
  ADD KEY `fk_er_to` (`to_currency_id`),
  ADD KEY `fk_er_user` (`created_by`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_login_user` (`user_id`);

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
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_elements`
--
ALTER TABLE `product_elements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pe_product` (`product_id`);

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
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier_advances`
--
ALTER TABLE `supplier_advances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sa_supplier` (`supplier_id`),
  ADD KEY `idx_sa_currency` (`currency_id`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `account_types`
--
ALTER TABLE `account_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=203;

--
-- AUTO_INCREMENT for table `currencies`
--
ALTER TABLE `currencies`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `exchange_rates`
--
ALTER TABLE `exchange_rates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `product_elements`
--
ALTER TABLE `product_elements`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `supplier_advances`
--
ALTER TABLE `supplier_advances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- Constraints for table `exchange_rates`
--
ALTER TABLE `exchange_rates`
  ADD CONSTRAINT `fk_er_from` FOREIGN KEY (`from_currency_id`) REFERENCES `currencies` (`id`),
  ADD CONSTRAINT `fk_er_to` FOREIGN KEY (`to_currency_id`) REFERENCES `currencies` (`id`),
  ADD CONSTRAINT `fk_er_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `login`
--
ALTER TABLE `login`
  ADD CONSTRAINT `fk_login_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_sa_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

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
