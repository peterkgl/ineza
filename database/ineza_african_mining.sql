-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 30, 2026 at 11:44 PM
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

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `account_type_id`, `account_code`, `account_name`, `is_active`, `description`, `created_at`, `updated_at`) VALUES
(2, 3, '2002', 'Eugene ndayishimiye - Accounts Payable', 1, 'Auto-created account for supplier: Eugene ndayishimiye', '2026-06-24 04:30:13', '2026-06-24 04:30:13'),
(3, 3, '2003', 'Igiraneza Fablice - Accounts Payable', 1, 'Auto-created account for supplier: Igiraneza Fablice', '2026-06-24 04:31:43', '2026-06-24 04:31:43'),
(4, 3, '2013', 'bk', 1, '', '2026-06-24 04:48:12', '2026-06-24 04:48:12'),
(8, 6, '1002', 'eugene - Accounts Receivable', 1, 'Auto-created account for customer: eugene', '2026-06-27 22:32:35', '2026-06-27 22:32:35'),
(9, 3, '2004', 'Alliance - Accounts Payable', 1, 'Auto-created account for supplier: Alliance', '2026-06-29 14:00:59', '2026-06-29 14:00:59');

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
(-6, '6000', 'Expenses', NULL, 0, 0, '2026-06-24 02:50:00', '2026-06-30 19:30:03'),
(-5, '5000', 'Cost of Sales', NULL, 0, 0, '2026-06-24 02:50:00', '2026-06-24 02:50:00'),
(-4, '4000', 'Revenue', NULL, 0, 0, '2026-06-24 02:50:00', '2026-06-24 02:50:00'),
(-3, '3000', 'Equity', NULL, 0, 0, '2026-06-24 02:50:00', '2026-06-24 02:50:00'),
(-2, '2000', 'Liabilities', NULL, 0, 0, '2026-06-24 02:50:00', '2026-06-24 02:50:00'),
(-1, '1000', 'Assets', NULL, 0, 0, '2026-06-24 02:50:00', '2026-06-24 02:50:00'),
(3, '2001', 'Accounts Payable', -2, 1, 1, '2026-06-24 02:53:18', '2026-06-24 02:53:18'),
(6, '1001', 'Accounts Receivable', -1, 1, 1, '2026-06-27 21:39:14', '2026-06-27 21:39:14');

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
(1, 'Super Admin', 'CREATE', 'purchasing', 'TEST-PUR-1782507687', 'Recorded mining purchase: TEST-PUR-1782507687', NULL, '{\"id\":1,\"purchase_no\":\"TEST-PUR-1782507687\",\"product_id\":1,\"quantity_kg\":100,\"purchase_value_usd\":500}', '', '', 'mf00ov006vt7i95jf61k2cb49s', NULL, '2026-06-26 21:01:27'),
(2, 'Super Admin', 'UPDATE', 'purchasing', 'TEST-PUR-1782507687', 'Status changed from pending to received', '{\"status\":\"pending\"}', '{\"status\":\"received\"}', '', '', 'aq2k3beeafa4621up2sd7p9e70', NULL, '2026-06-26 21:01:27'),
(3, 'Super Admin', 'UPDATE', 'purchasing', 'TEST-PUR-1782507687', 'Updated purchase: TEST-PUR-1782507687', '{\"id\":\"1\",\"purchase_no\":\"TEST-PUR-1782507687\",\"delivery_no\":null,\"inventory_code\":null,\"delivery_date\":\"2026-06-26\",\"purchase_date\":\"2026-06-26\",\"lot_id\":\"2\",\"product_id\":\"1\",\"supplier_id\":\"7\",\"warehouse_id\":\"2\",\"quantity_kg\":\"100.0000\",\"uom_id\":\"1\",\"price_per_kg_rwf\":null,\"purchase_value_rwf\":\"600000.00\",\"exchange_rate\":null,\"purchase_value_usd\":\"500.0000\",\"net_paid_supplier_usd\":null,\"charges_per_kg\":null,\"price_per_ta_unit\":null,\"price_per_kg_usd\":\"5.0000\",\"lme_price\":null,\"tc_charges\":null,\"tax_rra\":null,\"tax_rma\":null,\"tax_inkomane\":null,\"production_charges\":null,\"status\":\"received\",\"notes\":null,\"created_by\":\"2\",\"created_at\":\"2026-06-26 23:01:27\",\"updated_at\":\"2026-06-26 23:01:27\"}', '{\"id\":1,\"purchase_no\":\"TEST-PUR-1782507687\",\"product_id\":1,\"quantity_kg\":150,\"purchase_value_usd\":750}', '', '', '07r879durdnbo5ocgp8tq3foms', NULL, '2026-06-26 21:01:27'),
(4, 'Super Admin', 'UPDATE', 'purchasing', 'TEST-PUR-1782507687', 'Status changed from received to pending', '{\"status\":\"received\"}', '{\"status\":\"pending\"}', '', '', '8cumpkdgj8k3bq3v0vt7de47rk', NULL, '2026-06-26 21:01:28'),
(5, 'Super Admin', 'UPDATE', 'purchasing', 'TEST-PUR-1782507687', 'Status changed from pending to received', '{\"status\":\"pending\"}', '{\"status\":\"received\"}', '', '', 'qnnva6jjt72oc31l68fo4l0oaj', NULL, '2026-06-26 21:01:28'),
(6, 'Super Admin', 'CREATE', 'purchasing', 'PUR-20260627-01C2', 'Recorded mining purchase: PUR-20260627-01C2', NULL, '{\"id\":1,\"purchase_no\":\"PUR-20260627-01C2\",\"product_id\":1,\"quantity_kg\":60,\"purchase_value_usd\":300000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'rsqpai1ohfm2kvuu3lj1b0uu0s', NULL, '2026-06-26 22:31:41'),
(7, 'Super Admin', 'UPDATE', 'purchasing', 'PUR-20260627-01C2', 'Status changed from pending to confirmed', '{\"status\":\"pending\"}', '{\"status\":\"confirmed\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'rsqpai1ohfm2kvuu3lj1b0uu0s', NULL, '2026-06-26 22:33:53'),
(8, 'Super Admin', 'UPDATE', 'purchasing', 'PUR-20260627-01C2', 'Status changed from confirmed to cancelled', '{\"status\":\"confirmed\"}', '{\"status\":\"cancelled\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'rsqpai1ohfm2kvuu3lj1b0uu0s', NULL, '2026-06-26 22:34:10'),
(9, 'Super Admin', 'UPDATE', 'purchasing', 'PUR-20260627-01C2', 'Status changed from cancelled to received', '{\"status\":\"cancelled\"}', '{\"status\":\"received\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'rsqpai1ohfm2kvuu3lj1b0uu0s', NULL, '2026-06-26 22:34:21'),
(10, 'Super Admin', 'UPDATE', 'lots', 'Lot 1-Ta', 'Closed lot: Lot 1-Ta', '{\"id\":\"2\",\"lots_code\":\"Lot 1-Ta\",\"opening_date\":\"2026-06-26\",\"closing_date\":null}', '{\"id\":\"2\",\"lots_code\":\"Lot 1-Ta\",\"opening_date\":\"2026-06-26\",\"closing_date\":\"2026-06-27\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'rsqpai1ohfm2kvuu3lj1b0uu0s', NULL, '2026-06-27 00:07:03'),
(11, 'Super Admin', 'VIEW', 'lots', 'Lots List', 'User viewed the lots list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'rsqpai1ohfm2kvuu3lj1b0uu0s', NULL, '2026-06-27 00:07:03'),
(12, 'Super Admin', 'CREATE', 'lots', 'Lot 2-Ta', 'Created lot: Lot 2-Ta', NULL, '{\"id\":4,\"lots_code\":\"Lot 2-Ta\",\"opening_date\":\"2026-06-27\",\"closing_date\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'rsqpai1ohfm2kvuu3lj1b0uu0s', NULL, '2026-06-27 00:07:44'),
(13, 'Super Admin', 'VIEW', 'lots', 'Lots List', 'User viewed the lots list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'rsqpai1ohfm2kvuu3lj1b0uu0s', NULL, '2026-06-27 00:07:44'),
(14, 'Super Admin', 'CREATE', 'account_types', '1001', 'Created new account type: Accounts Receivable (1001)', NULL, '{\"id\":6,\"code\":\"1001\",\"name\":\"Accounts Receivable\",\"parent_id\":-1,\"is_editable\":1,\"is_deletable\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'eb3qakrqdmajfuci13o5mv0h6q', NULL, '2026-06-27 21:39:14'),
(15, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'eb3qakrqdmajfuci13o5mv0h6q', NULL, '2026-06-27 21:39:15'),
(16, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'eb3qakrqdmajfuci13o5mv0h6q', NULL, '2026-06-27 22:20:17'),
(17, 'Super Admin', 'CREATE', 'sells', 'SALE-20260628-A1D0', 'Recorded sales order: SALE-20260628-A1D0', NULL, '{\"id\":2,\"sale_no\":\"SALE-20260628-A1D0\",\"customer_id\":3,\"total_qty_kg\":30,\"total_value_usd\":30000,\"amount_paid\":1600}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'eb3qakrqdmajfuci13o5mv0h6q', NULL, '2026-06-27 22:32:35'),
(18, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'eb3qakrqdmajfuci13o5mv0h6q', NULL, '2026-06-27 22:32:37'),
(19, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'h8po9ufo8d6h4mbfjilsbrkdta', NULL, '2026-06-29 13:54:58'),
(20, 'Super Admin', 'CREATE', 'product', 'TA', 'Created product: Tantalum', NULL, '{\"id\":2,\"product_code\":\"TA\",\"product_name\":\"Tantalum\",\"category\":\"Wolfarm\",\"uom_id\":1,\"description\":\"\",\"is_active\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'h8po9ufo8d6h4mbfjilsbrkdta', NULL, '2026-06-29 13:59:13'),
(21, 'Super Admin', 'VIEW', 'product', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'h8po9ufo8d6h4mbfjilsbrkdta', NULL, '2026-06-29 13:59:14'),
(22, 'Super Admin', 'UPDATE', 'product', 'TA', 'Updated product: Tantalum', '{\"id\":\"2\",\"product_code\":\"TA\",\"product_name\":\"Tantalum\",\"category\":\"Wolfarm\",\"uom_id\":\"1\",\"description\":\"\",\"is_active\":\"0\",\"created_at\":\"2026-06-29 15:59:13\",\"updated_at\":\"2026-06-29 15:59:13\"}', '{\"id\":2,\"product_code\":\"TA\",\"product_name\":\"Tantalum\",\"category\":\"Wolfarm\",\"uom_id\":1,\"description\":\"\",\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'h8po9ufo8d6h4mbfjilsbrkdta', NULL, '2026-06-29 13:59:21'),
(23, 'Super Admin', 'VIEW', 'product', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'h8po9ufo8d6h4mbfjilsbrkdta', NULL, '2026-06-29 13:59:21'),
(24, 'Super Admin', 'CREATE', 'suppliers', 'Alliance', 'Created supplier: Alliance (individual)', NULL, '{\"id\":10,\"supplier_type\":\"individual\",\"name\":\"Alliance\",\"nif\":\"1778\",\"phone\":\"+250 785 750 116\",\"email\":\"alliance@gmail.com\",\"address\":\"KN 4 Rd\",\"payables_account_id\":9,\"is_active\":1,\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'h8po9ufo8d6h4mbfjilsbrkdta', NULL, '2026-06-29 14:00:59'),
(25, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'h8po9ufo8d6h4mbfjilsbrkdta', NULL, '2026-06-29 14:00:59'),
(26, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'h8po9ufo8d6h4mbfjilsbrkdta', NULL, '2026-06-29 14:48:21'),
(27, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'h8po9ufo8d6h4mbfjilsbrkdta', NULL, '2026-06-29 14:48:26'),
(28, 'Super Admin', 'CREATE', 'purchasing', 'PUR-20260630-E44D', 'Recorded mining purchase: PUR-20260630-E44D', NULL, '{\"id\":2,\"purchase_no\":\"PUR-20260630-E44D\",\"product_id\":1,\"quantity_kg\":450,\"purchase_value_usd\":1311.174}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'i8lc8i6anv0uuoqam0cqcl10ml', NULL, '2026-06-29 23:44:12'),
(29, 'Super Admin', 'UPDATE', 'purchasing', 'PUR-20260630-E44D', 'Status changed from pending to received', '{\"status\":\"pending\"}', '{\"status\":\"received\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'i8lc8i6anv0uuoqam0cqcl10ml', NULL, '2026-06-29 23:44:36'),
(30, 'Super Admin', 'CREATE', 'purchasing', 'PUR-20260630-5C62', 'Recorded mining purchase: PUR-20260630-5C62', NULL, '{\"id\":3,\"purchase_no\":\"PUR-20260630-5C62\",\"product_id\":2,\"quantity_kg\":1343.5,\"purchase_value_usd\":20071.89}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nh9g2024jck42fqq4eeai84qoe', NULL, '2026-06-30 10:09:36'),
(31, 'Super Admin', 'UPDATE', 'lots', 'Lot 2-Ta', 'Closed lot: Lot 2-Ta', '{\"id\":\"4\",\"lots_code\":\"Lot 2-Ta\",\"opening_date\":\"2026-06-27\",\"closing_date\":null}', '{\"id\":\"4\",\"lots_code\":\"Lot 2-Ta\",\"opening_date\":\"2026-06-27\",\"closing_date\":\"2026-06-30\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nh9g2024jck42fqq4eeai84qoe', NULL, '2026-06-30 10:10:59'),
(32, 'Super Admin', 'VIEW', 'lots', 'Lots List', 'User viewed the lots list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nh9g2024jck42fqq4eeai84qoe', NULL, '2026-06-30 10:10:59'),
(33, 'Super Admin', 'CREATE', 'lots', 'Lot 3-Ta', 'Created lot: Lot 3-Ta', NULL, '{\"id\":5,\"lots_code\":\"Lot 3-Ta\",\"opening_date\":\"2026-06-30\",\"closing_date\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nh9g2024jck42fqq4eeai84qoe', NULL, '2026-06-30 10:11:11'),
(34, 'Super Admin', 'VIEW', 'lots', 'Lots List', 'User viewed the lots list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'nh9g2024jck42fqq4eeai84qoe', NULL, '2026-06-30 10:11:11'),
(35, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'p3cb8acj3dspbvcubqnok7ue1e', NULL, '2026-06-30 10:20:38'),
(36, 'Super Admin', 'CREATE', 'purchasing', 'PUR-20260630-823E', 'Recorded mining purchase: PUR-20260630-823E', NULL, '{\"id\":4,\"purchase_no\":\"PUR-20260630-823E\",\"product_id\":1,\"quantity_kg\":365,\"purchase_value_usd\":2.2484}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '1tbslmljhb4chi4ok5mt3cbi9k', NULL, '2026-06-30 10:24:00'),
(37, 'Super Admin', 'VIEW', 'purchasing', 'Purchases List', 'User viewed the purchases list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '96kp727h458f1emgo1fi1ov9h5', NULL, '2026-06-30 19:28:29'),
(38, 'Super Admin', 'CREATE', 'product', 'TIN-008', 'Created product: cassiterite', NULL, '{\"id\":4,\"product_code\":\"TIN-008\",\"product_name\":\"cassiterite\",\"uom_id\":3,\"inventory_account_id\":6,\"sales_account_id\":3,\"cogs_account_id\":6,\"description\":\"\",\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '565b04f3fepjrhf29ahd2ps048', NULL, '2026-06-30 21:20:22'),
(39, 'Super Admin', 'VIEW', 'product', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '565b04f3fepjrhf29ahd2ps048', NULL, '2026-06-30 21:20:22'),
(40, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '565b04f3fepjrhf29ahd2ps048', NULL, '2026-06-30 21:27:26');

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
(1, 'RWF', 'RWANDAN FRANGS', 'Rwf', 1, 1, '2026-06-16 22:01:30', 2, '2026-06-26 10:43:52', 2),
(2, 'USD', 'Us Dorall', '$', 0, 1, '2026-06-16 22:21:06', 2, '2026-06-26 10:43:52', 2);

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_code` varchar(30) DEFAULT NULL,
  `customer_type` enum('company','individual','government') DEFAULT 'company',
  `name` varchar(200) NOT NULL,
  `nif` varchar(50) DEFAULT NULL,
  `vat_reg_no` varchar(100) DEFAULT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `credit_limit` decimal(18,2) DEFAULT 0.00,
  `currency` char(3) DEFAULT 'USD' COMMENT 'Default billing currency',
  `receivable_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Buyers / export customers who purchase mineral products from Ineza';

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
  `sale_id` bigint(20) UNSIGNED NOT NULL,
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
-- Table structure for table `government_tax_log`
--

CREATE TABLE `government_tax_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchasing_id` bigint(20) UNSIGNED NOT NULL,
  `tax_authority` enum('RRA','RMA','INKOMANE','OTHER') NOT NULL,
  `tax_amount` decimal(18,4) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'RWF',
  `tax_rate_pct` decimal(8,4) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Government taxes withheld per purchase (RRA, RMA, INKOMANE)';

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
(68, 2, 'admin@gmail.com', '2026-06-24 02:35:12', '::1', 'success'),
(69, 2, 'admin@gmail.com', '2026-06-24 03:27:22', '::1', 'success'),
(70, 2, 'admin@gmail.com', '2026-06-24 04:12:06', '::1', 'success'),
(71, 2, 'admin@gmail.com', '2026-06-24 04:36:54', '::1', 'success'),
(72, 2, 'admin@gmail.com', '2026-06-24 20:31:19', '::1', 'success'),
(73, 2, 'admin@gmail.com', '2026-06-25 20:11:49', '::1', 'success'),
(74, 2, 'admin@gmail.com', '2026-06-25 20:45:06', '::1', 'success'),
(75, 2, 'admin@gmail.com', '2026-06-25 21:03:26', '::1', 'success'),
(76, 2, 'admin@gmail.com', '2026-06-25 21:39:31', '::1', 'success'),
(77, 2, 'admin@gmail.com', '2026-06-25 21:49:47', '::1', 'success'),
(78, 2, 'admin@gmail.com', '2026-06-25 22:14:48', '::1', 'success'),
(79, 2, 'admin@gmail.com', '2026-06-25 22:21:00', '::1', 'success'),
(80, 5, 'eugene@gmail.com', '2026-06-25 22:25:22', '::1', 'success'),
(81, 2, 'admin@gmail.com', '2026-06-25 22:25:49', '::1', 'success'),
(82, 5, 'eugene@gmail.com', '2026-06-25 22:28:15', '::1', 'success'),
(83, 2, 'admin@gmail.com', '2026-06-25 22:28:35', '::1', 'success'),
(84, NULL, 'admin@pt.com', '2026-06-26 07:21:40', '::1', 'invalid_email'),
(85, 2, 'admin@gmail.com', '2026-06-26 07:23:46', '::1', 'success'),
(86, 2, 'admin@gmail.com', '2026-06-26 08:59:36', '::1', 'success'),
(87, 2, 'admin@gmail.com', '2026-06-26 09:34:24', '::1', 'success'),
(88, 2, 'admin@gmail.com', '2026-06-26 09:59:29', '::1', 'success'),
(89, 2, 'admin@gmail.com', '2026-06-26 09:59:55', '::1', 'success'),
(90, 2, 'admin@gmail.com', '2026-06-26 10:27:22', '::1', 'success'),
(91, 2, 'admin@gmail.com', '2026-06-26 12:41:58', '::1', 'success'),
(92, 2, 'admin@gmail.com', '2026-06-26 14:08:04', '::1', 'success'),
(93, 2, 'admin@gmail.com', '2026-06-26 17:19:18', '::1', 'success'),
(94, 2, 'admin@gmail.com', '2026-06-26 18:46:36', '::1', 'success'),
(95, 2, 'admin@gmail.com', '2026-06-26 18:55:06', '::1', 'success'),
(96, 2, 'admin@gmail.com', '2026-06-26 19:43:57', '::1', 'success'),
(97, 2, 'admin@gmail.com', '2026-06-26 19:56:30', '::1', 'success'),
(98, 2, 'admin@gmail.com', '2026-06-26 20:17:19', '::1', 'success'),
(99, 2, 'admin@gmail.com', '2026-06-26 21:01:59', '::1', 'success'),
(100, 2, 'admin@gmail.com', '2026-06-27 19:02:40', '::1', 'success'),
(101, 2, 'admin@gmail.com', '2026-06-29 13:54:53', '::1', 'success'),
(102, 2, 'admin@gmail.com', '2026-06-29 22:41:54', '::1', 'success'),
(103, 2, 'admin@gmail.com', '2026-06-29 22:42:24', '::1', 'success'),
(104, 2, 'admin@gmail.com', '2026-06-29 22:45:03', '::1', 'success'),
(105, 2, 'admin@gmail.com', '2026-06-29 23:11:57', '::1', 'success'),
(106, 2, 'admin@gmail.com', '2026-06-29 23:36:02', '::1', 'success'),
(107, 2, 'admin@gmail.com', '2026-06-29 23:36:56', '::1', 'success'),
(108, 2, 'admin@gmail.com', '2026-06-30 08:58:01', '::1', 'success'),
(109, 2, 'admin@gmail.com', '2026-06-30 09:19:20', '::1', 'success'),
(110, 2, 'admin@gmail.com', '2026-06-30 09:25:57', '::1', 'success'),
(111, 2, 'admin@gmail.com', '2026-06-30 10:20:31', '::1', 'success'),
(112, 2, 'admin@gmail.com', '2026-06-30 10:22:21', '::1', 'success'),
(113, 2, 'admin@gmail.com', '2026-06-30 19:11:02', '127.0.0.1', 'success'),
(114, 2, 'admin@gmail.com', '2026-06-30 19:30:26', '::1', 'success'),
(115, 2, 'admin@gmail.com', '2026-06-30 21:18:57', '::1', 'success');

-- --------------------------------------------------------

--
-- Table structure for table `lots`
--

CREATE TABLE `lots` (
  `id` int(10) UNSIGNED NOT NULL,
  `lots_code` varchar(50) NOT NULL COMMENT 'e.g. Lot 1-Ta, Lot 01-Tin',
  `product_id` int(10) UNSIGNED NOT NULL,
  `opening_date` date NOT NULL,
  `closing_date` date DEFAULT NULL COMMENT 'NULL means lot is still open',
  `statuss` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Batch/lot system. Each lot groups multiple purchase deliveries together.';

--
-- Dumping data for table `lots`
--

INSERT INTO `lots` (`id`, `lots_code`, `product_id`, `opening_date`, `closing_date`, `statuss`) VALUES
(2, 'Lot 1-Ta', 0, '2026-06-26', '2026-06-27', 1),
(4, 'Lot 2-Ta', 0, '2026-06-27', '2026-06-30', 1),
(5, 'Lot 3-Ta', 0, '2026-06-30', NULL, 1);

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
(53, 'Close Lot', 'close_lot', '2026-06-22 09:58:11'),
(54, 'View Units of Measure', 'view_unit_of_measure', '2026-06-25 22:14:01'),
(55, 'Create Unit of Measure', 'create_unit_of_measure', '2026-06-25 22:14:01'),
(56, 'Edit Unit of Measure', 'edit_unit_of_measure', '2026-06-25 22:14:01'),
(57, 'Delete Unit of Measure', 'delete_unit_of_measure', '2026-06-25 22:14:01'),
(58, 'View Purchases', 'view_purchas', '2026-06-26 08:45:11'),
(59, 'Create Purchases', 'create_purchas', '2026-06-26 08:45:11'),
(60, 'Edit Purchases', 'edit_purchas', '2026-06-26 08:45:11'),
(61, 'Delete Purchases', 'delete_purchas', '2026-06-26 08:45:11'),
(62, 'View Stock', 'view_stock', '2026-06-26 19:32:58'),
(63, 'View Stock Movement', 'view_stock_movement', '2026-06-26 19:32:58'),
(64, 'View Sales', 'view_sales', '2026-06-27 22:14:14'),
(65, 'Create Sale', 'create_sale', '2026-06-27 22:14:14'),
(66, 'Edit Sale', 'edit_sale', '2026-06-27 22:14:14'),
(67, 'Delete Sale', 'delete_sale', '2026-06-27 22:14:14');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_code` varchar(50) NOT NULL COMMENT 'e.g. COLTAN-TA, TIN-SN',
  `product_name` varchar(200) NOT NULL COMMENT 'e.g. Coltan (Tantalite), Cassiterite (Tin)',
  `uom_id` int(10) UNSIGNED NOT NULL COMMENT 'Default unit of measure',
  `inventory_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sales_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cogs_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Products traded by Ineza African Mining (Coltan, Tin, etc.)';

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`id`, `product_code`, `product_name`, `uom_id`, `inventory_account_id`, `sales_account_id`, `cogs_account_id`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'TIN', 'Coltan', 1, NULL, NULL, NULL, '', 1, '2026-06-25 20:45:34', '2026-06-25 20:45:34'),
(2, 'TA', 'Tantalum', 1, NULL, NULL, NULL, '', 1, '2026-06-29 13:59:13', '2026-06-29 13:59:21'),
(4, 'TIN-008', 'cassiterite', 3, 6, 3, 6, '', 1, '2026-06-30 21:20:22', '2026-06-30 21:20:22');

-- --------------------------------------------------------

--
-- Table structure for table `product_element`
--

CREATE TABLE `product_element` (
  `id` int(10) UNSIGNED NOT NULL,
  `element_code` varchar(30) NOT NULL COMMENT 'e.g. Ta205, Nb205, Sn, Fe, Bal',
  `element_name` varchar(150) NOT NULL COMMENT 'e.g. Tantalum Pentoxide, Tin, Iron',
  `symbol` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Mineral/chemical elements tracked per purchase (Ta205, Nb205, Sn, Fe, etc.)';

--
-- Dumping data for table `product_element`
--

INSERT INTO `product_element` (`id`, `element_code`, `element_name`, `symbol`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Ta205', 'Tantalum Pentoxide', 'Ta₂O₅', NULL, 1, '2026-06-25 13:03:32', '2026-06-25 13:03:32'),
(2, 'Ta', 'Tantalum', 'Ta', NULL, 1, '2026-06-25 13:03:32', '2026-06-25 13:03:32'),
(3, 'Nb205', 'Niobium Pentoxide', 'Nb₂O₅', NULL, 1, '2026-06-25 13:03:32', '2026-06-25 13:03:32'),
(4, 'Fe', 'Iron', 'Fe', NULL, 1, '2026-06-25 13:03:32', '2026-06-25 13:03:32'),
(5, 'Sn', 'Tin', 'Sn', NULL, 1, '2026-06-25 13:03:32', '2026-06-25 13:03:32'),
(6, 'Bal', 'Balance / Other', '$', '', 1, '2026-06-25 13:03:32', '2026-06-25 21:39:57');

-- --------------------------------------------------------

--
-- Table structure for table `product_element_composition`
--

CREATE TABLE `product_element_composition` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `product_element_id` int(10) UNSIGNED NOT NULL,
  `is_primary_grade` tinyint(1) DEFAULT 0 COMMENT '1 = the main commercial grade element',
  `display_order` tinyint(4) DEFAULT 0,
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Which elements are tracked per product (e.g. Coltan tracks Ta205, Nb205, Fe, Bal)';

--
-- Dumping data for table `product_element_composition`
--

INSERT INTO `product_element_composition` (`id`, `product_id`, `product_element_id`, `is_primary_grade`, `display_order`, `notes`) VALUES
(5, 2, 5, 1, 0, NULL),
(6, 1, 5, 1, 0, NULL);

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
-- Table structure for table `purchasing`
--

CREATE TABLE `purchasing` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_no` varchar(50) NOT NULL COMMENT 'Internal reference e.g. PUR-2025-0001',
  `delivery_no` varchar(30) DEFAULT NULL COMMENT 'D/Down reference e.g. DD 1',
  `inventory_code` varchar(50) DEFAULT NULL COMMENT 'Inventory/stock code per delivery',
  `account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `purchase_date` date NOT NULL DEFAULT curdate(),
  `lot_id` int(10) UNSIGNED NOT NULL COMMENT 'Which lot/batch this delivery belongs to',
  `product_id` int(10) UNSIGNED NOT NULL COMMENT 'Which mineral product was purchased',
  `supplier_id` bigint(20) UNSIGNED NOT NULL COMMENT 'The négociant/supplier',
  `negociant` varchar(200) DEFAULT NULL,
  `warehouse_id` int(10) UNSIGNED NOT NULL COMMENT 'Receiving warehouse',
  `quantity_kg` decimal(14,4) NOT NULL COMMENT 'Delivered quantity in Kg',
  `uom_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Unit of measure (default KG)',
  `price_per_kg_rwf` decimal(18,4) DEFAULT NULL COMMENT 'Price per Kg in Rwf',
  `purchase_value_rwf` decimal(18,2) DEFAULT NULL COMMENT 'Total purchase value in Rwf',
  `exchange_rate` decimal(14,4) DEFAULT NULL COMMENT 'RWF/USD rate on purchase date',
  `purchase_value_usd` decimal(18,4) DEFAULT NULL COMMENT 'Total purchase value in USD',
  `net_paid_supplier_usd` decimal(18,4) DEFAULT NULL COMMENT 'Net amount paid to supplier USD',
  `charges_per_kg` decimal(12,4) DEFAULT NULL COMMENT 'Processing charge per Kg',
  `production_charges_per_kg` decimal(12,4) DEFAULT NULL,
  `price_per_ta_unit` decimal(12,4) DEFAULT NULL COMMENT 'Price per Ta unit (coltan)',
  `price_per_kg_usd` decimal(12,4) DEFAULT NULL COMMENT '$ price per Kg',
  `lme_price` decimal(14,4) DEFAULT NULL COMMENT 'LME reference price (Tin)',
  `tc_charges` decimal(12,4) DEFAULT NULL COMMENT 'Treatment/refining charges',
  `tax_rra` decimal(14,4) DEFAULT NULL COMMENT 'RRA tax withheld',
  `tax_rma` decimal(14,4) DEFAULT NULL COMMENT 'RMA tax withheld',
  `tax_inkomane` decimal(14,4) DEFAULT NULL COMMENT 'INKOMANE levy withheld',
  `production_charges` decimal(14,4) DEFAULT NULL COMMENT 'Total production charges',
  `status` enum('pending','confirmed','received','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='One row per mineral purchase/delivery. Links lot, product, supplier, warehouse.';

-- --------------------------------------------------------

--
-- Table structure for table `purchasing_element_grade`
--

CREATE TABLE `purchasing_element_grade` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchasing_id` bigint(20) UNSIGNED NOT NULL,
  `product_element_id` int(10) UNSIGNED NOT NULL,
  `grade_pct` decimal(10,6) DEFAULT NULL COMMENT 'Element grade as a fraction e.g. 0.418582',
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Assay / chemical grade readings per element per purchase line';

--
-- Dumping data for table `purchasing_element_grade`
--

INSERT INTO `purchasing_element_grade` (`id`, `purchasing_id`, `product_element_id`, `grade_pct`, `notes`) VALUES
(1, 1, 3, 87.000000, ''),
(2, 1, 5, 67.000000, ''),
(3, 2, 3, 35.000000, ''),
(4, 2, 5, 76.000000, ''),
(5, 3, 5, 54.690000, ''),
(6, 4, 5, 88.000000, '');

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
(1, 54),
(1, 55),
(1, 56),
(1, 57),
(1, 58),
(1, 59),
(1, 60),
(1, 61),
(1, 62),
(1, 63),
(1, 64),
(1, 65),
(1, 66),
(1, 67),
(2, 7),
(2, 10),
(2, 11),
(3, 19),
(3, 27),
(3, 31),
(3, 35),
(3, 40),
(3, 54);

-- --------------------------------------------------------

--
-- Table structure for table `sells`
--

CREATE TABLE `sells` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sale_no` varchar(50) NOT NULL COMMENT 'e.g. SALE-2025-0001',
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `sale_date` date NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `warehouse_id` int(10) UNSIGNED NOT NULL COMMENT 'Warehouse from which goods are shipped',
  `total_qty_kg` decimal(14,4) DEFAULT 0.0000,
  `total_value_rwf` decimal(20,2) DEFAULT NULL,
  `total_value_usd` decimal(20,4) DEFAULT NULL,
  `exchange_rate` decimal(14,4) DEFAULT NULL,
  `currency` char(3) DEFAULT 'USD',
  `payment_terms` varchar(100) DEFAULT NULL COMMENT 'e.g. Net 30, CIA, T/T',
  `incoterms` varchar(50) DEFAULT NULL COMMENT 'e.g. FOB Mombasa, CIF',
  `export_permit_no` varchar(100) DEFAULT NULL,
  `destination_country` varchar(100) DEFAULT NULL,
  `status` enum('draft','confirmed','shipped','invoiced','paid','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Sales order header — one row per export/sale transaction';

-- --------------------------------------------------------

--
-- Table structure for table `sells_item`
--

CREATE TABLE `sells_item` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sells_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `lot_id` int(10) UNSIGNED NOT NULL COMMENT 'Traces which lot the sold goods came from',
  `warehouse_id` int(10) UNSIGNED NOT NULL COMMENT 'Source warehouse (may differ from sells header for multi-wh)',
  `uom_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `quantity_kg` decimal(14,4) NOT NULL,
  `price_per_kg_usd` decimal(18,4) DEFAULT NULL,
  `price_per_kg_rwf` decimal(18,4) DEFAULT NULL,
  `line_value_usd` decimal(20,4) DEFAULT NULL,
  `line_value_rwf` decimal(20,2) DEFAULT NULL,
  `grade_pct_primary` decimal(10,6) DEFAULT NULL COMMENT 'Primary element grade e.g. Ta205%, Sn%',
  `primary_element_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK to product_element',
  `cogs_per_kg_usd` decimal(18,4) DEFAULT NULL,
  `cogs_total_usd` decimal(20,4) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Sales line items — links each sale to lot for full traceability of origin';

-- --------------------------------------------------------

--
-- Table structure for table `stock`
--

CREATE TABLE `stock` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `warehouse_id` int(10) UNSIGNED NOT NULL COMMENT 'Which warehouse holds this stock',
  `product_id` int(10) UNSIGNED NOT NULL COMMENT 'Which product',
  `lot_id` int(10) UNSIGNED NOT NULL COMMENT 'Which lot/batch',
  `uom_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `qty_purchased` decimal(14,4) NOT NULL DEFAULT 0.0000 COMMENT 'Total Kg received from purchasing',
  `qty_sold` decimal(14,4) NOT NULL DEFAULT 0.0000 COMMENT 'Total Kg shipped out via sales',
  `qty_adjusted` decimal(14,4) NOT NULL DEFAULT 0.0000 COMMENT 'Manual adjustments (+/-)',
  `qty_on_hand` decimal(14,4) GENERATED ALWAYS AS (`qty_purchased` - `qty_sold` + `qty_adjusted`) STORED COMMENT 'Computed remaining stock: purchased − sold + adjustments',
  `avg_cost_per_kg_rwf` decimal(18,4) DEFAULT NULL COMMENT 'Weighted average cost in RWF',
  `avg_cost_per_kg_usd` decimal(18,4) DEFAULT NULL COMMENT 'Weighted average cost in USD',
  `total_value_rwf` decimal(20,2) DEFAULT NULL,
  `total_value_usd` decimal(20,4) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL,
  `opening` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `closing` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `last_rolled_over_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Running stock balance per warehouse, product, and lot. qty_on_hand is auto-computed.';

-- --------------------------------------------------------

--
-- Table structure for table `stock_movement`
--

CREATE TABLE `stock_movement` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `movement_type` enum('PURCHASE_IN','SALE_OUT','TRANSFER_IN','TRANSFER_OUT','ADJUSTMENT_IN','ADJUSTMENT_OUT','RETURN_IN','OPENING_STOCK') NOT NULL,
  `warehouse_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `lot_id` int(10) UNSIGNED DEFAULT NULL,
  `uom_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `qty_kg` decimal(14,4) NOT NULL COMMENT 'Positive number always; direction from movement_type',
  `unit_cost_rwf` decimal(18,4) DEFAULT NULL,
  `unit_cost_usd` decimal(18,4) DEFAULT NULL,
  `total_value_rwf` decimal(20,2) DEFAULT NULL,
  `total_value_usd` decimal(20,4) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'purchasing, sells, transfer, adjustment',
  `reference_id` bigint(20) DEFAULT NULL COMMENT 'FK to the source record (purchasing.id or sells.id)',
  `movement_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `opening` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `closing` decimal(14,4) NOT NULL DEFAULT 0.0000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Full audit trail of every stock movement (in, out, transfer, adjustment)';

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `supplier_type` enum('individual','cooperative','company') DEFAULT 'individual',
  `name` varchar(200) NOT NULL,
  `nif` varchar(50) DEFAULT NULL COMMENT 'Tax Identification Number',
  `vat_reg_no` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `payables_account_id` bigint(20) UNSIGNED DEFAULT NULL,
  `currency_id` int(11) DEFAULT NULL,
  `region` varchar(200) DEFAULT NULL,
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

INSERT INTO `suppliers` (`id`, `supplier_type`, `name`, `nif`, `vat_reg_no`, `phone`, `email`, `address`, `payables_account_id`, `currency_id`, `region`, `is_active`, `notes`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(7, 'cooperative', 'Eugene ndayishimiye', '09987', NULL, '+250785750117', 'nendayishimye@gmail.com', 'Unnamed Road', 2, NULL, NULL, 1, 'done', '2026-06-24 04:30:13', '2026-06-24 04:30:13', 2, NULL),
(8, 'individual', 'Igiraneza Fablice', '0448', NULL, '+250785750109', 'igiraneza@gmail.com', 'kigali', 3, NULL, NULL, 1, 'done', '2026-06-24 04:31:43', '2026-06-24 04:31:43', 2, NULL),
(10, 'individual', 'Alliance', '1778', NULL, '+250 785 750 116', 'alliance@gmail.com', 'KN 4 Rd', 9, NULL, NULL, 1, '', '2026-06-29 14:00:59', '2026-06-29 14:00:59', 2, NULL);

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
-- Table structure for table `unit_of_measure`
--

CREATE TABLE `unit_of_measure` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL COMMENT 'e.g. KG, MT, G, LB',
  `name` varchar(100) NOT NULL COMMENT 'e.g. Kilogram, Metric Ton',
  `symbol` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Units of measure used across products, purchases, and sales';

--
-- Dumping data for table `unit_of_measure`
--

INSERT INTO `unit_of_measure` (`id`, `code`, `name`, `symbol`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'KG', 'Kilogram', 'kg', 1, '2026-06-25 13:01:42', '2026-06-25 13:01:42'),
(2, 'MT', 'Metric Ton', 'MT', 1, '2026-06-25 13:01:42', '2026-06-25 13:01:42'),
(3, 'G', 'Gram', 'g', 1, '2026-06-25 13:01:42', '2026-06-25 13:01:42'),
(4, 'LB', 'Pound', 'lb', 1, '2026-06-25 13:01:42', '2026-06-25 13:01:42'),
(5, 'L', 'Litre', 'L', 1, '2026-06-25 13:01:42', '2026-06-25 13:01:42'),
(6, 'PCS', 'Pieces', 'pcs', 1, '2026-06-25 13:01:42', '2026-06-25 13:01:42');

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

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_transfer`
--

CREATE TABLE `warehouse_transfer` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transfer_no` varchar(50) NOT NULL,
  `from_warehouse_id` int(10) UNSIGNED NOT NULL,
  `to_warehouse_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `lot_id` int(10) UNSIGNED NOT NULL,
  `uom_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `quantity_kg` decimal(14,4) NOT NULL,
  `transfer_date` date NOT NULL,
  `status` enum('pending','in_transit','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Inter-warehouse stock transfers';

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
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_customer_code` (`customer_code`),
  ADD KEY `fk_customer_receivable_account` (`receivable_account_id`);

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
  ADD KEY `customer_payment_allocations_ibfk_2` (`sale_id`);

--
-- Indexes for table `exchange_rates`
--
ALTER TABLE `exchange_rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_er_from` (`from_currency_id`),
  ADD KEY `fk_er_to` (`to_currency_id`),
  ADD KEY `fk_er_user` (`created_by`);

--
-- Indexes for table `government_tax_log`
--
ALTER TABLE `government_tax_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gtl_purchasing` (`purchasing_id`),
  ADD KEY `idx_gtl_authority` (`tax_authority`);

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
  ADD UNIQUE KEY `uq_lots_code` (`lots_code`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permition_name` (`permition_name`),
  ADD UNIQUE KEY `permition_code` (`permition_code`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_product_code` (`product_code`),
  ADD KEY `fk_product_uom` (`uom_id`);

--
-- Indexes for table `product_element`
--
ALTER TABLE `product_element`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_element_code` (`element_code`);

--
-- Indexes for table `product_element_composition`
--
ALTER TABLE `product_element_composition`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_product_element` (`product_id`,`product_element_id`),
  ADD KEY `fk_pec_element` (`product_element_id`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchasing`
--
ALTER TABLE `purchasing`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_purchase_no` (`purchase_no`),
  ADD KEY `fk_purchasing_account` (`account_id`);

--
-- Indexes for table `purchasing_element_grade`
--
ALTER TABLE `purchasing_element_grade`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_peg_purchase_element` (`purchasing_id`,`product_element_id`),
  ADD KEY `fk_peg_element` (`product_element_id`);

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
-- Indexes for table `sells`
--
ALTER TABLE `sells`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sale_no` (`sale_no`);

--
-- Indexes for table `sells_item`
--
ALTER TABLE `sells_item`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_stock_wh_prod_lot` (`warehouse_id`,`product_id`,`lot_id`),
  ADD KEY `idx_stock_rolled_over` (`last_rolled_over_at`);

--
-- Indexes for table `stock_movement`
--
ALTER TABLE `stock_movement`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_supplier_payables_account` (`payables_account_id`);

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
-- Indexes for table `unit_of_measure`
--
ALTER TABLE `unit_of_measure`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_uom_code` (`code`);

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
-- Indexes for table `warehouse_transfer`
--
ALTER TABLE `warehouse_transfer`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_transfer_no` (`transfer_no`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `account_types`
--
ALTER TABLE `account_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `currencies`
--
ALTER TABLE `currencies`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `government_tax_log`
--
ALTER TABLE `government_tax_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `lots`
--
ALTER TABLE `lots`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product_element`
--
ALTER TABLE `product_element`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `product_element_composition`
--
ALTER TABLE `product_element_composition`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchasing`
--
ALTER TABLE `purchasing`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchasing_element_grade`
--
ALTER TABLE `purchasing_element_grade`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sells`
--
ALTER TABLE `sells`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sells_item`
--
ALTER TABLE `sells_item`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock`
--
ALTER TABLE `stock`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_movement`
--
ALTER TABLE `stock_movement`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
-- AUTO_INCREMENT for table `unit_of_measure`
--
ALTER TABLE `unit_of_measure`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- AUTO_INCREMENT for table `warehouse_transfer`
--
ALTER TABLE `warehouse_transfer`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `customer`
--
ALTER TABLE `customer`
  ADD CONSTRAINT `fk_customer_receivable_account` FOREIGN KEY (`receivable_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customer_payment_allocations`
--
ALTER TABLE `customer_payment_allocations`
  ADD CONSTRAINT `customer_payment_allocations_ibfk_1` FOREIGN KEY (`customer_payment_id`) REFERENCES `customer_payments` (`id`),
  ADD CONSTRAINT `customer_payment_allocations_ibfk_2` FOREIGN KEY (`sale_id`) REFERENCES `sells` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exchange_rates`
--
ALTER TABLE `exchange_rates`
  ADD CONSTRAINT `fk_er_from` FOREIGN KEY (`from_currency_id`) REFERENCES `currencies` (`id`),
  ADD CONSTRAINT `fk_er_to` FOREIGN KEY (`to_currency_id`) REFERENCES `currencies` (`id`),
  ADD CONSTRAINT `fk_er_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `government_tax_log`
--
ALTER TABLE `government_tax_log`
  ADD CONSTRAINT `fk_gtl_purchasing` FOREIGN KEY (`purchasing_id`) REFERENCES `purchasing` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `fk_product_uom` FOREIGN KEY (`uom_id`) REFERENCES `unit_of_measure` (`id`);

--
-- Constraints for table `product_element_composition`
--
ALTER TABLE `product_element_composition`
  ADD CONSTRAINT `fk_pec_element` FOREIGN KEY (`product_element_id`) REFERENCES `product_element` (`id`),
  ADD CONSTRAINT `fk_pec_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`);

--
-- Constraints for table `purchasing`
--
ALTER TABLE `purchasing`
  ADD CONSTRAINT `fk_purchasing_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchasing_element_grade`
--
ALTER TABLE `purchasing_element_grade`
  ADD CONSTRAINT `fk_peg_element` FOREIGN KEY (`product_element_id`) REFERENCES `product_element` (`id`),
  ADD CONSTRAINT `fk_peg_purchase` FOREIGN KEY (`purchasing_id`) REFERENCES `purchasing` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `fk_supplier_payables_account` FOREIGN KEY (`payables_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL;

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
