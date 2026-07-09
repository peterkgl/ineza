-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 09, 2026 at 11:08 AM
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
(11, 4, '1024', 'Stocks - Tin', 1, '', '2026-07-02 18:41:13', '2026-07-02 18:41:13'),
(12, 4, '1034', 'Stocks - Coltan', 1, '', '2026-07-02 18:41:32', '2026-07-02 18:41:32'),
(13, 4, '1044', 'Stocks - Tantalum', 1, '', '2026-07-02 18:57:54', '2026-07-02 18:57:54'),
(14, 37, '4011', 'Sales - Tin', 1, '', '2026-07-02 18:59:27', '2026-07-02 18:59:27'),
(15, 37, '4021', 'Sales - Coltan', 1, '', '2026-07-02 18:59:57', '2026-07-02 18:59:57'),
(16, 37, '4031', 'Sales - Tantalum', 1, '', '2026-07-02 19:00:32', '2026-07-02 19:00:32'),
(17, -1, '1500', 'Property, Plant and Equipment', 1, 'Long term physical assets', '2026-07-03 23:50:46', '2026-07-03 23:50:46'),
(18, -1, '1300', 'Merchandise Inventory', 1, 'Inventory held for sale', '2026-07-03 23:50:46', '2026-07-03 23:50:46'),
(19, 6, '1100', 'Trade Receivables', 1, 'Accounts receivable from customers', '2026-07-03 23:50:46', '2026-07-03 23:50:46'),
(20, -1, '1010', 'Cash and Bank Balances', 1, 'Cash on hand and in bank accounts', '2026-07-03 23:50:46', '2026-07-03 23:50:46'),
(21, -3, '3000', 'Share Capital', 1, 'Owner contributed capital', '2026-07-03 23:50:46', '2026-07-03 23:50:46'),
(22, -3, '3200', 'Retained Earnings', 1, 'Accumulated historical earnings', '2026-07-03 23:50:46', '2026-07-03 23:50:46'),
(23, -2, '2200', 'Long Term Loans', 1, 'Long term liabilities and bank loans', '2026-07-03 23:50:46', '2026-07-03 23:50:46'),
(24, 3, '2001', 'Trade Payables', 1, 'Accounts payable to suppliers', '2026-07-03 23:50:46', '2026-07-03 23:50:46'),
(25, -2, '2100', 'Other Current Liabilities', 1, 'Accrued current liabilities', '2026-07-03 23:50:46', '2026-07-03 23:50:46'),
(26, -2, '2400', 'Current Tax Payable', 1, 'Tax liabilities payable to RRA', '2026-07-03 23:50:46', '2026-07-03 23:50:46'),
(27, 15, '2014', 'Eugene ndayishimiye - Accounts Payable', 1, 'Auto-created account for supplier: Eugene ndayishimiye', '2026-07-06 13:45:03', '2026-07-06 13:45:03'),
(28, 1, '1010-01', 'EQUITY US$ ACCOUNT', 1, 'Equity USD bank account', '2026-07-07 12:07:53', '2026-07-07 12:07:53'),
(29, 28, '3000-01', 'Investments', 1, 'Investments account', '2026-07-07 12:07:53', '2026-07-07 12:07:53'),
(30, 60, '6013-01', 'Bank Charges', 1, 'Bank charges expense', '2026-07-07 12:07:53', '2026-07-07 12:07:53'),
(31, 1, '1010-02', 'Petty Cash Fund - INEZA', 1, 'Petty cash asset account', '2026-07-07 12:07:53', '2026-07-07 12:07:53'),
(32, 1, '1010-03', 'Due from EQUITY - INEZA AFRICAN MINING RWF', 1, 'Due from RWF equity account', '2026-07-07 12:07:53', '2026-07-07 12:07:53'),
(33, 19, '2005-01', 'Advances from Star Metal Company', 1, 'Customer advances liability', '2026-07-07 12:07:53', '2026-07-07 12:07:53'),
(34, 1, '1010-04', 'PC INEZA RUB', 1, 'Rubaya petty cash account', '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(35, 60, '6000-RUB-001', 'Funds to Sites - Rubaya', 1, 'Seeded from Rubaya Petty Cash', '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(36, 60, '6000-RUB-002', 'Advances to O/E - Charles MUNYANEZA', 1, 'Seeded from Rubaya Petty Cash', '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(37, 60, '6000-RUB-003', 'Travel & Accommodation', 1, 'Seeded from Rubaya Petty Cash', '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(38, 60, '6000-RUB-004', 'Staff Welfare', 1, 'Seeded from Rubaya Petty Cash', '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(39, 60, '6000-RUB-005', 'Transport', 1, 'Seeded from Rubaya Petty Cash', '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(40, 60, '6000-RUB-008', 'Miscellaneous Expense', 1, 'Seeded from Rubaya Petty Cash', '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(41, 60, '6000-RUB-009', 'Advances to O/E - GEDEON', 1, 'Seeded from Rubaya Petty Cash', '2026-07-07 15:08:13', '2026-07-07 15:08:13');

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
(-6, '6000', 'Expenses', NULL, 0, 0, '2026-06-24 00:50:00', '2026-06-30 06:14:32'),
(-5, '5000', 'Cost of Sales', NULL, 0, 0, '2026-06-24 00:50:00', '2026-06-24 00:50:00'),
(-4, '4000', 'Revenue', NULL, 0, 0, '2026-06-24 00:50:00', '2026-06-24 00:50:00'),
(-3, '3000', 'Equity', NULL, 0, 0, '2026-06-24 00:50:00', '2026-06-24 00:50:00'),
(-2, '2000', 'Liabilities', NULL, 0, 0, '2026-06-24 00:50:00', '2026-06-24 00:50:00'),
(-1, '1000', 'Assets', NULL, 0, 0, '2026-06-24 00:50:00', '2026-06-24 00:50:00'),
(1, '1001', 'Cash & Cash Equivalenta', -1, 1, 1, '2026-07-02 08:49:04', '2026-07-02 08:49:04'),
(2, '1002', 'Accounts Receivable', -1, 1, 1, '2026-07-02 08:49:19', '2026-07-02 08:49:19'),
(3, '1003', 'Other Receivables', -1, 1, 1, '2026-07-02 08:49:38', '2026-07-02 08:49:38'),
(4, '1004', 'Inventory', -1, 1, 1, '2026-07-02 08:50:38', '2026-07-02 08:50:38'),
(5, '1005', 'Property, Plant & Equipment', -1, 1, 1, '2026-07-02 08:51:00', '2026-07-02 08:51:00'),
(6, '1006', 'Prepayments', -1, 1, 1, '2026-07-02 08:52:15', '2026-07-02 08:52:15'),
(7, '1007', 'Employee Advances', -1, 1, 1, '2026-07-02 08:55:58', '2026-07-02 08:55:58'),
(8, '1008', 'Supplier Advances', -1, 1, 1, '2026-07-02 09:08:13', '2026-07-02 09:08:13'),
(9, '1009', 'Customer Advances', -1, 1, 1, '2026-07-02 09:08:49', '2026-07-02 09:08:49'),
(10, '1010', 'Cooperative Advances', -1, 1, 1, '2026-07-02 09:11:17', '2026-07-02 09:11:17'),
(11, '1011', 'Other Advances', -1, 1, 1, '2026-07-02 09:11:42', '2026-07-02 09:11:42'),
(12, '1012', 'Intangible Assets', -1, 1, 1, '2026-07-02 09:14:10', '2026-07-02 09:14:10'),
(13, '1013', 'Deferred Tax Assets', -1, 1, 1, '2026-07-02 09:14:28', '2026-07-02 09:14:28'),
(14, '1014', 'Other Assets', -1, 1, 1, '2026-07-02 09:14:44', '2026-07-02 09:14:44'),
(15, '2001', 'Accounts Payable', -2, 1, 1, '2026-07-02 09:15:07', '2026-07-02 09:15:07'),
(16, '2002', 'Accrued Liabilities', -2, 1, 1, '2026-07-02 09:16:05', '2026-07-02 09:16:05'),
(17, '2003', 'Payroll Liabilities', -2, 1, 1, '2026-07-02 09:16:21', '2026-07-02 09:16:21'),
(18, '2004', 'Tax Liabilities', -2, 1, 1, '2026-07-02 09:16:45', '2026-07-02 09:16:45'),
(19, '2005', 'Customer Deposits / Advances', -2, 1, 1, '2026-07-02 09:17:10', '2026-07-02 09:17:10'),
(20, '2006', 'Deferred Revenue', -2, 1, 1, '2026-07-02 09:17:29', '2026-07-02 09:17:29'),
(21, '2007', 'Short-Term Loans', -2, 1, 1, '2026-07-02 09:17:45', '2026-07-02 09:17:45'),
(22, '2008', 'Long-Term Loans', -2, 1, 1, '2026-07-02 09:18:04', '2026-07-02 09:18:04'),
(23, '2009', 'Lease Liabilities', -2, 1, 1, '2026-07-02 13:11:54', '2026-07-02 13:11:54'),
(24, '2010', 'Provisions', -2, 1, 1, '2026-07-02 13:12:08', '2026-07-02 13:12:08'),
(25, '2011', 'Intercompany Payables', -2, 1, 1, '2026-07-02 13:12:26', '2026-07-02 13:12:26'),
(26, '2012', 'Deferred Tax Liabilities', -2, 1, 1, '2026-07-02 13:12:42', '2026-07-02 13:12:42'),
(27, '2013', 'Other Liabilities', -2, 1, 1, '2026-07-02 13:13:11', '2026-07-02 13:13:11'),
(28, '3001', 'Share Capital', -3, 1, 1, '2026-07-02 13:14:52', '2026-07-02 13:14:52'),
(29, '3002', 'Partner Capital', -3, 1, 1, '2026-07-02 13:30:07', '2026-07-02 13:30:07'),
(30, '3003', 'Member\'s Equity', -3, 1, 1, '2026-07-02 13:30:21', '2026-07-02 13:30:21'),
(31, '3004', 'Owner\'s Drawings', -3, 1, 1, '2026-07-02 13:30:51', '2026-07-02 13:30:51'),
(32, '3005', 'Retained Earnings', -3, 1, 1, '2026-07-02 13:31:03', '2026-07-02 13:31:03'),
(33, '3006', 'Current Year Earnings', -3, 1, 1, '2026-07-02 13:31:18', '2026-07-02 13:31:18'),
(34, '3007', 'Reserves', -3, 1, 1, '2026-07-02 13:31:35', '2026-07-02 13:31:35'),
(35, '3008', 'Other Comprehensive Income', -3, 1, 1, '2026-07-02 13:32:35', '2026-07-02 13:32:35'),
(36, '3009', 'Prior Period Adjustments', -3, 1, 1, '2026-07-02 13:32:54', '2026-07-02 13:32:54'),
(37, '4001', 'Product Sales', -4, 1, 1, '2026-07-02 13:33:22', '2026-07-02 13:33:22'),
(38, '4002', 'Service Revenue', -4, 1, 1, '2026-07-02 13:33:38', '2026-07-02 13:33:38'),
(39, '4003', 'Other Operating Revenue', -4, 1, 1, '2026-07-02 13:34:05', '2026-07-02 13:34:05'),
(40, '4004', 'Other Income', -4, 1, 1, '2026-07-02 13:34:22', '2026-07-02 13:34:22'),
(41, '5001', 'Mineral Production Costs', -5, 1, 1, '2026-07-02 14:04:37', '2026-07-02 14:04:37'),
(42, '5002', 'Export Costs', -5, 1, 1, '2026-07-02 14:04:52', '2026-07-02 14:04:52'),
(43, '5003', 'Transportation & Logistics Costs', -5, 1, 1, '2026-07-02 14:05:11', '2026-07-02 14:05:11'),
(44, '5004', 'Direct Labor Costs', -5, 1, 1, '2026-07-02 14:05:33', '2026-07-02 14:05:33'),
(45, '5005', 'Sampling & Quality Costs', -5, 1, 1, '2026-07-02 14:05:50', '2026-07-02 14:05:50'),
(46, '5006', 'Cooperative & Royalty Costs', -5, 1, 1, '2026-07-02 14:06:07', '2026-07-02 14:06:07'),
(47, '5007', 'Other Direct Costs', -5, 1, 1, '2026-07-02 14:06:26', '2026-07-02 14:06:26'),
(48, '6001', 'Administrative Expenses', -6, 1, 1, '2026-07-02 14:22:21', '2026-07-02 14:22:21'),
(49, '6002', 'Employee Costs', -6, 1, 1, '2026-07-02 14:22:55', '2026-07-02 14:22:55'),
(50, '6003', 'Professional & Consulting Fees', -6, 1, 1, '2026-07-02 14:23:10', '2026-07-02 14:23:10'),
(51, '6004', 'Marketing & Business Development', -6, 1, 1, '2026-07-02 14:23:24', '2026-07-02 14:23:24'),
(52, '6005', 'Occupancy & Utilities', -6, 1, 1, '2026-07-02 14:23:43', '2026-07-02 14:23:43'),
(53, '6006', 'Transport & Travel', -6, 1, 1, '2026-07-02 14:23:55', '2026-07-02 14:23:55'),
(54, '6007', 'Repairs & Maintenance', -6, 1, 1, '2026-07-02 14:24:13', '2026-07-02 14:24:13'),
(55, '6008', 'IT & Software Expenses', -6, 1, 1, '2026-07-02 14:24:31', '2026-07-02 14:24:31'),
(56, '6009', 'Safety & Security', -6, 1, 1, '2026-07-02 14:24:46', '2026-07-02 14:24:46'),
(57, '6010', 'Insurance', -6, 1, 1, '2026-07-02 14:24:56', '2026-07-02 14:24:56'),
(58, '6011', 'Taxes & Regulatory Expenses', -6, 1, 1, '2026-07-02 14:26:07', '2026-07-02 14:26:07'),
(59, '6012', 'Mining & Exploration Expenses', -6, 1, 1, '2026-07-02 14:26:28', '2026-07-02 14:26:28'),
(60, '6013', 'Financial Expenses', -6, 1, 1, '2026-07-02 14:26:41', '2026-07-02 14:26:41'),
(61, '6014', 'Depreciation & Amortization', -6, 1, 1, '2026-07-02 14:29:29', '2026-07-02 14:29:29');

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

-- --------------------------------------------------------

--
-- Table structure for table `bank_recon_items`
--

CREATE TABLE `bank_recon_items` (
  `id` int(11) NOT NULL,
  `report_slug` varchar(50) NOT NULL COMMENT 'bank_recon_usd or bank_recon_rwf',
  `as_of_date` date NOT NULL,
  `item_type` enum('outstanding_check','deposit_in_transit','unrecorded_payment') NOT NULL,
  `item_date` date NOT NULL,
  `reference` varchar(50) DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_statement_balances`
--

CREATE TABLE `bank_statement_balances` (
  `id` int(11) NOT NULL,
  `report_slug` varchar(50) NOT NULL COMMENT 'bank_recon_usd or bank_recon_rwf',
  `as_of_date` date NOT NULL,
  `balance` decimal(18,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank_statement_balances`
--

INSERT INTO `bank_statement_balances` (`id`, `report_slug`, `as_of_date`, `balance`) VALUES
(1, 'bank_recon_usd', '2026-01-05', 36477.40),
(2, 'bank_recon_rwf', '2026-01-05', 2508.74);

-- --------------------------------------------------------

--
-- Table structure for table `cash_counts`
--

CREATE TABLE `cash_counts` (
  `id` int(11) NOT NULL,
  `report_slug` varchar(50) NOT NULL COMMENT 'cash_count_hq or cash_count_rub',
  `count_date` date NOT NULL,
  `denomination` varchar(20) NOT NULL COMMENT '5000, 2000, 100, 50, etc.',
  `currency` varchar(10) NOT NULL COMMENT 'RWF or USD',
  `quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cash_counts`
--

INSERT INTO `cash_counts` (`id`, `report_slug`, `count_date`, `denomination`, `currency`, `quantity`) VALUES
(1, 'cash_count_hq', '2025-12-23', '5000', 'RWF', 110000),
(2, 'cash_count_hq', '2025-12-23', '1', 'RWF', 4434),
(4, 'cash_count_rub', '2026-01-05', '100', 'RWF', 15100),
(5, 'cash_count_rub', '2026-01-05', '1', 'RWF', 14);

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

--
-- Dumping data for table `journal_entries`
--

INSERT INTO `journal_entries` (`id`, `journal_no`, `entry_date`, `description`, `statuss`, `created_by`, `created_at`) VALUES
(1, 'JE-USD-EQ-0001', '2025-05-25', 'To/From: AFRICAN FRESH PRODUCTS LTD | Details: Investment from African Fresh Products', 'POSTED', 2, '2026-07-07 12:07:53'),
(2, 'JE-USD-EQ-0002', '2025-05-25', 'To/From: Equity Bank | Details: SEARCH FEE /4001650050117', 'POSTED', 2, '2026-07-07 12:07:53'),
(3, 'JE-USD-EQ-0003', '2025-05-29', 'To/From: MUVUNYI DIEUDONNE | Details: Cash withdraw for Petty Cash replenishment', 'POSTED', 2, '2026-07-07 12:07:53'),
(4, 'JE-USD-EQ-0004', '2025-05-29', 'To/From: MUVUNYI DIEUDONNE | Details: Cash withdraw for Petty Cash replenishment', 'POSTED', 2, '2026-07-07 12:07:53'),
(5, 'JE-USD-EQ-0005', '2025-05-29', 'To/From: Equity Bank | Details: Cash W/D No Chq Charge', 'POSTED', 2, '2026-07-07 12:07:53'),
(6, 'JE-USD-EQ-0006', '2025-05-29', 'To/From: Equity Bank | Details: Cash Withdrawal Charge', 'POSTED', 2, '2026-07-07 12:07:53'),
(7, 'JE-USD-EQ-0007', '2025-05-29', 'To/From: Equity Bank | Details: SUPREME NON-MEMBER FEE', 'POSTED', 2, '2026-07-07 12:07:53'),
(8, 'JE-USD-EQ-0008', '2025-06-04', 'To/From: MUVUNYI DIEUDONNE | Details: Cash withdraw for Petty Cash replenishment', 'POSTED', 2, '2026-07-07 12:07:53'),
(9, 'JE-USD-EQ-0009', '2025-06-04', 'To/From: Equity Bank | Details: Cash W/D No Chq Charge', 'POSTED', 2, '2026-07-07 12:07:53'),
(10, 'JE-USD-EQ-0010', '2025-06-04', 'To/From: Equity Bank | Details: Inter Sol Cash Wdrawal charge', 'POSTED', 2, '2026-07-07 12:07:53'),
(11, 'JE-USD-EQ-0011', '2025-06-05', 'To/From: STAR METAL COMPANY LTD | Details: TRF FROM STAR METALS COMPANY LTD', 'POSTED', 2, '2026-07-07 12:07:53'),
(12, 'JE-USD-EQ-0012', '2025-06-05', 'To/From: BETTER OFF EQUIPMENTS AND SOLUTIONS | Details: Purchase of plant equipment, magnetic separator and shaking table - TRANSFER TO BETTER OFF EQUIPMENTS AND SOLUTIONS', 'POSTED', 2, '2026-07-07 12:07:53'),
(13, 'JE-RUB-EQ-0001', '2025-06-25', 'To/From: HARERIMANA ZIRUNGUYE Gedeon | Details: Aprovisionnement YURY 3000$ & D.G 5000', 'POSTED', 2, '2026-07-07 15:08:13'),
(14, 'JE-RUB-EQ-0002', '2025-06-25', 'To/From: CHARLES MUNYANEZA | Details: Avance CHARLES MUNYANEZA', 'POSTED', 2, '2026-07-07 15:08:13'),
(15, 'JE-RUB-EQ-0003', '2025-06-25', 'To/From: HARERIMANA ZIRUNGUYE Gedeon | Details: Logement Gdeon & Abel', 'POSTED', 2, '2026-07-07 15:08:13'),
(16, 'JE-RUB-EQ-0004', '2025-06-25', 'To/From: HARERIMANA ZIRUNGUYE Gedeon | Details: Restauration GEDEON & Abel', 'POSTED', 2, '2026-07-07 15:08:13'),
(17, 'JE-RUB-EQ-0005', '2025-06-25', 'To/From: ABEL NYAMUGIRA | Details: Avance Abel', 'POSTED', 2, '2026-07-07 15:08:13'),
(18, 'JE-RUB-EQ-0006', '2025-06-25', 'To/From: HARERIMANA ZIRUNGUYE Gedeon | Details: Transport matelas', 'POSTED', 2, '2026-07-07 15:08:13'),
(19, 'JE-RUB-EQ-0007', '2025-06-25', 'To/From: HARERIMANA ZIRUNGUYE Gedeon | Details: Achat de 160kgs @12$', 'POSTED', 2, '2026-07-07 15:08:13'),
(20, 'JE-RUB-EQ-0008', '2025-06-25', 'To/From: Commissionnaire | Details: Commissionnaire', 'POSTED', 2, '2026-07-07 15:08:13'),
(21, 'JE-RUB-EQ-0009', '2025-06-25', 'To/From: HARERIMANA ZIRUNGUYE Gedeon | Details: Avance GEDEON - Salary', 'POSTED', 2, '2026-07-07 15:08:13'),
(22, 'JE-20201231-0001', '2020-12-31', 'Opening Statement of Financial Position 2020', 'CANCELLED', 2, '2026-07-07 22:20:19'),
(23, 'JE-20211231-0001', '2021-12-31', 'Statement of Financial Position Movements 2021', 'POSTED', 2, '2026-07-07 22:20:19'),
(24, 'JE-20221231-0001', '2022-12-31', 'Statement of Financial Position Movements 2022', 'POSTED', 2, '2026-07-07 22:20:19');

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

--
-- Dumping data for table `journal_entry_lines`
--

INSERT INTO `journal_entry_lines` (`id`, `journal_entry_id`, `account_id`, `debit`, `credit`, `currency_id`, `exchange_rate`, `amount_currency`, `amount_base`, `description`) VALUES
(1, 1, 28, 26449.00, 0.00, 2, 1400.000000, 26449.00, 37028600.00, 'Investment from African Fresh Products'),
(2, 1, 29, 0.00, 26449.00, 2, 1400.000000, 26449.00, 37028600.00, 'Investment from African Fresh Products'),
(3, 2, 28, 0.00, 6.25, 2, 1400.000000, 6.25, 8750.00, 'SEARCH FEE /4001650050117'),
(4, 2, 30, 6.25, 0.00, 2, 1400.000000, 6.25, 8750.00, 'SEARCH FEE /4001650050117'),
(5, 3, 28, 0.00, 450.35, 2, 1400.000000, 450.35, 630490.00, 'Cash withdraw for Petty Cash replenishment'),
(6, 3, 31, 450.35, 0.00, 2, 1400.000000, 450.35, 630490.00, 'Cash withdraw for Petty Cash replenishment'),
(7, 4, 28, 0.00, 8899.65, 2, 1400.000000, 8899.65, 12459510.00, 'Cash withdraw for Petty Cash replenishment'),
(8, 4, 32, 8899.65, 0.00, 2, 1400.000000, 8899.65, 12459510.00, 'Cash withdraw for Petty Cash replenishment'),
(9, 5, 28, 0.00, 2.47, 2, 1400.000000, 2.47, 3458.00, 'Cash W/D No Chq Charge'),
(10, 5, 30, 2.47, 0.00, 2, 1400.000000, 2.47, 3458.00, 'Cash W/D No Chq Charge'),
(11, 6, 28, 0.00, 5.01, 2, 1400.000000, 5.01, 7014.00, 'Cash Withdrawal Charge'),
(12, 6, 30, 5.01, 0.00, 2, 1400.000000, 5.01, 7014.00, 'Cash Withdrawal Charge'),
(13, 7, 28, 0.00, 8.34, 2, 1400.000000, 8.34, 11676.00, 'SUPREME NON-MEMBER FEE'),
(14, 7, 30, 8.34, 0.00, 2, 1400.000000, 8.34, 11676.00, 'SUPREME NON-MEMBER FEE'),
(15, 8, 28, 0.00, 1000.00, 2, 1400.000000, 1000.00, 1400000.00, 'Cash withdraw for Petty Cash replenishment'),
(16, 8, 31, 1000.00, 0.00, 2, 1400.000000, 1000.00, 1400000.00, 'Cash withdraw for Petty Cash replenishment'),
(17, 9, 28, 0.00, 2.47, 2, 1400.000000, 2.47, 3458.00, 'Cash W/D No Chq Charge'),
(18, 9, 30, 2.47, 0.00, 2, 1400.000000, 2.47, 3458.00, 'Cash W/D No Chq Charge'),
(19, 10, 28, 0.00, 5.01, 2, 1400.000000, 5.01, 7014.00, 'Inter Sol Cash Wdrawal charge'),
(20, 10, 30, 5.01, 0.00, 2, 1400.000000, 5.01, 7014.00, 'Inter Sol Cash Wdrawal charge'),
(21, 11, 28, 15000.00, 0.00, 2, 1400.000000, 15000.00, 21000000.00, 'TRF FROM STAR METALS COMPANY LTD'),
(22, 11, 33, 0.00, 15000.00, 2, 1400.000000, 15000.00, 21000000.00, 'TRF FROM STAR METALS COMPANY LTD'),
(23, 12, 28, 0.00, 29000.00, 2, 1400.000000, 29000.00, 40600000.00, 'Purchase of plant equipment, magnetic separator and shaking table - TRANSFER TO BETTER OFF EQUIPMENTS AND SOLUTIONS'),
(24, 12, 31, 29000.00, 0.00, 2, 1400.000000, 29000.00, 40600000.00, 'Purchase of plant equipment, magnetic separator and shaking table - TRANSFER TO BETTER OFF EQUIPMENTS AND SOLUTIONS'),
(25, 13, 34, 35000.00, 0.00, 2, 1.000000, 35000.00, 35000.00, 'Aprovisionnement YURY 3000$ & D.G 5000'),
(26, 13, 35, 0.00, 35000.00, 2, 1.000000, 35000.00, 35000.00, 'Aprovisionnement YURY 3000$ & D.G 5000'),
(27, 14, 34, 0.00, 32500.00, 2, 1.000000, 32500.00, 32500.00, 'Avance CHARLES MUNYANEZA'),
(28, 14, 36, 32500.00, 0.00, 2, 1.000000, 32500.00, 32500.00, 'Avance CHARLES MUNYANEZA'),
(29, 15, 34, 0.00, 120.00, 2, 1.000000, 120.00, 120.00, 'Logement Gdeon & Abel'),
(30, 15, 37, 120.00, 0.00, 2, 1.000000, 120.00, 120.00, 'Logement Gdeon & Abel'),
(31, 16, 34, 0.00, 96.00, 2, 1.000000, 96.00, 96.00, 'Restauration GEDEON & Abel'),
(32, 16, 38, 96.00, 0.00, 2, 1.000000, 96.00, 96.00, 'Restauration GEDEON & Abel'),
(33, 17, 34, 0.00, 60.00, 2, 1.000000, 60.00, 60.00, 'Avance Abel'),
(34, 17, 39, 60.00, 0.00, 2, 1.000000, 60.00, 60.00, 'Avance Abel'),
(35, 18, 34, 0.00, 10.00, 2, 1.000000, 10.00, 10.00, 'Transport matelas'),
(36, 18, 39, 10.00, 0.00, 2, 1.000000, 10.00, 10.00, 'Transport matelas'),
(37, 19, 34, 0.00, 1920.00, 2, 1.000000, 1920.00, 1920.00, 'Achat de 160kgs @12$'),
(38, 19, 11, 1920.00, 0.00, 2, 1.000000, 1920.00, 1920.00, 'Achat de 160kgs @12$'),
(39, 20, 34, 0.00, 20.00, 2, 1.000000, 20.00, 20.00, 'Commissionnaire'),
(40, 20, 40, 20.00, 0.00, 2, 1.000000, 20.00, 20.00, 'Commissionnaire'),
(41, 21, 34, 0.00, 100.00, 2, 1.000000, 100.00, 100.00, 'Avance GEDEON - Salary'),
(42, 21, 41, 100.00, 0.00, 2, 1.000000, 100.00, 100.00, 'Avance GEDEON - Salary'),
(43, 22, 17, 51175000.00, 0.00, 1, 1.000000, 51175000.00, 51175000.00, 'PPE opening balance 2020'),
(44, 22, 18, 464009712.00, 0.00, 1, 1.000000, 464009712.00, 464009712.00, 'Inventory opening balance 2020'),
(45, 22, 19, 185174448.00, 0.00, 1, 1.000000, 185174448.00, 185174448.00, 'Accounts receivable opening 2020'),
(46, 22, 20, 44507100.00, 0.00, 1, 1.000000, 44507100.00, 44507100.00, 'Cash and cash equivalents opening 2020'),
(47, 22, 21, 0.00, 15000000.00, 1, 1.000000, 15000000.00, 15000000.00, 'Share capital opening 2020'),
(48, 22, 22, 0.00, 381297812.00, 1, 1.000000, 381297812.00, 381297812.00, 'Retained earnings opening 2020'),
(49, 22, 24, 0.00, 149525000.00, 1, 1.000000, 149525000.00, 149525000.00, 'Accounts payable opening 2020'),
(50, 22, 25, 0.00, 35630100.00, 1, 1.000000, 35630100.00, 35630100.00, 'Other current liabilities opening 2020'),
(51, 22, 26, 0.00, 163413348.00, 1, 1.000000, 163413348.00, 163413348.00, 'Current tax payable opening 2020'),
(52, 23, 17, 379480062.00, 0.00, 1, 1.000000, 379480062.00, 379480062.00, 'PPE additions movement 2021'),
(53, 23, 18, 309189158.00, 0.00, 1, 1.000000, 309189158.00, 309189158.00, 'Inventory change movement 2021'),
(54, 23, 19, 185390724.00, 0.00, 1, 1.000000, 185390724.00, 185390724.00, 'Accounts receivable change 2021'),
(55, 23, 20, 51479230.00, 0.00, 1, 1.000000, 51479230.00, 51479230.00, 'Cash movement 2021'),
(56, 23, 22, 0.00, 491979478.00, 1, 1.000000, 491979478.00, 491979478.00, 'Retained earnings change 2021'),
(57, 23, 23, 0.00, 247055474.00, 1, 1.000000, 247055474.00, 247055474.00, 'Long term loan addition 2021'),
(58, 23, 24, 0.00, 107669092.00, 1, 1.000000, 107669092.00, 107669092.00, 'Accounts payable change 2021'),
(59, 23, 25, 0.00, 31400130.00, 1, 1.000000, 31400130.00, 31400130.00, 'Other current liabilities change 2021'),
(60, 23, 26, 0.00, 47435000.00, 1, 1.000000, 47435000.00, 47435000.00, 'Current tax payable change 2021'),
(61, 24, 17, 271496138.00, 0.00, 1, 1.000000, 271496138.00, 271496138.00, 'PPE additions movement 2022'),
(62, 24, 18, 18425051.00, 0.00, 1, 1.000000, 18425051.00, 18425051.00, 'Inventory change movement 2022'),
(63, 24, 19, 71434513.00, 0.00, 1, 1.000000, 71434513.00, 71434513.00, 'Accounts receivable change 2022'),
(64, 24, 23, 43716697.00, 0.00, 1, 1.000000, 43716697.00, 43716697.00, 'Long term loan principal repayment 2022'),
(65, 24, 24, 144445786.00, 0.00, 1, 1.000000, 144445786.00, 144445786.00, 'Accounts payable change 2022'),
(66, 24, 25, 17030230.00, 0.00, 1, 1.000000, 17030230.00, 17030230.00, 'Other current liabilities change 2022'),
(67, 24, 20, 0.00, 20180230.00, 1, 1.000000, 20180230.00, 20180230.00, 'Cash reduction movement 2022'),
(68, 24, 22, 0.00, 530051923.00, 1, 1.000000, 530051923.00, 530051923.00, 'Retained earnings change 2022'),
(69, 24, 26, 0.00, 16316762.00, 1, 1.000000, 16316762.00, 16316762.00, 'Current tax payable change 2022');

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
(115, 2, 'admin@gmail.com', '2026-06-30 21:18:57', '::1', 'success'),
(116, 2, 'admin@gmail.com', '2026-06-30 22:08:02', '::1', 'success'),
(117, 2, 'admin@gmail.com', '2026-06-30 22:38:30', '::1', 'success'),
(118, 2, 'admin@gmail.com', '2026-06-30 22:46:47', '::1', 'success'),
(119, 2, 'admin@gmail.com', '2026-06-30 22:59:05', '::1', 'success'),
(120, 2, 'admin@gmail.com', '2026-06-30 23:21:16', '::1', 'success'),
(121, 2, 'admin@gmail.com', '2026-06-30 23:39:53', '::1', 'success'),
(122, 2, 'admin@gmail.com', '2026-07-01 13:03:08', '::1', 'success'),
(123, 2, 'admin@gmail.com', '2026-07-01 13:03:08', '::1', 'success'),
(124, 2, 'admin@gmail.com', '2026-07-01 21:03:09', '::1', 'success'),
(125, 2, 'admin@gmail.com', '2026-07-01 21:25:48', '::1', 'success'),
(126, 2, 'admin@gmail.com', '2026-07-01 23:40:44', '::1', 'success'),
(127, 2, 'admin@gmail.com', '2026-07-02 00:08:49', '::1', 'success'),
(128, 2, 'admin@gmail.com', '2026-07-02 00:20:04', '::1', 'success'),
(129, 2, 'admin@gmail.com', '2026-07-02 00:30:22', '::1', 'success'),
(130, 2, 'admin@gmail.com', '2026-07-02 00:33:56', '::1', 'success'),
(131, 2, 'admin@gmail.com', '2026-07-02 00:40:48', '::1', 'success'),
(132, 2, 'admin@gmail.com', '2026-07-02 01:42:12', '::1', 'success'),
(133, 2, 'admin@gmail.com', '2026-07-02 13:58:31', '::1', 'success'),
(134, 2, 'admin@gmail.com', '2026-07-02 17:02:27', '::1', 'success'),
(135, 2, 'admin@gmail.com', '2026-07-03 11:39:04', '::1', 'success'),
(136, 2, 'admin@gmail.com', '2026-07-03 18:56:47', '::1', 'success'),
(137, 2, 'admin@gmail.com', '2026-07-03 21:04:03', '::1', 'success'),
(138, 2, 'admin@gmail.com', '2026-07-03 21:28:22', '::1', 'success'),
(139, 2, 'admin@gmail.com', '2026-07-03 21:42:12', '::1', 'success'),
(140, 2, 'admin@gmail.com', '2026-07-03 21:43:29', '::1', 'success'),
(141, 2, 'admin@gmail.com', '2026-07-03 22:24:43', '::1', 'success'),
(142, 2, 'admin@gmail.com', '2026-07-03 22:51:54', '::1', 'success'),
(143, 2, 'admin@gmail.com', '2026-07-03 23:52:56', '::1', 'success'),
(144, 2, 'admin@gmail.com', '2026-07-04 00:01:29', '::1', 'success'),
(145, 2, 'admin@gmail.com', '2026-07-06 13:44:18', '::1', 'success'),
(146, 2, 'admin@gmail.com', '2026-07-06 13:59:27', '::1', 'success'),
(147, 2, 'admin@gmail.com', '2026-07-07 07:44:11', '::1', 'success'),
(148, 2, 'admin@gmail.com', '2026-07-07 08:47:17', '::1', 'success'),
(149, 2, 'admin@gmail.com', '2026-07-07 09:40:44', '::1', 'success'),
(150, 2, 'admin@gmail.com', '2026-07-07 10:21:04', '::1', 'success'),
(151, 2, 'admin@gmail.com', '2026-07-07 10:44:10', '::1', 'success'),
(152, 2, 'admin@gmail.com', '2026-07-07 10:47:43', '::1', 'success'),
(153, 2, 'admin@gmail.com', '2026-07-07 16:05:10', '::1', 'success'),
(154, 2, 'admin@gmail.com', '2026-07-07 16:51:09', '::1', 'success'),
(155, 2, 'admin@gmail.com', '2026-07-07 22:04:03', '::1', 'success'),
(156, 2, 'admin@gmail.com', '2026-07-07 22:56:47', '::1', 'success'),
(157, 2, 'admin@gmail.com', '2026-07-08 10:08:40', '::1', 'success'),
(158, 2, 'admin@gmail.com', '2026-07-08 10:51:18', '::1', 'success'),
(159, 2, 'admin@gmail.com', '2026-07-08 11:01:13', '::1', 'success'),
(160, 2, 'admin@gmail.com', '2026-07-08 15:07:19', '::1', 'success'),
(161, 2, 'admin@gmail.com', '2026-07-08 15:19:57', '::1', 'success'),
(162, 2, 'admin@gmail.com', '2026-07-08 18:33:46', '::1', 'success'),
(163, 2, 'admin@gmail.com', '2026-07-09 07:53:17', '::1', 'success'),
(164, 2, 'admin@gmail.com', '2026-07-09 08:10:43', '::1', 'success'),
(165, 2, 'admin@gmail.com', '2026-07-09 08:16:38', '::1', 'success'),
(166, 2, 'admin@gmail.com', '2026-07-09 08:56:12', '::1', 'success');

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
(1, 'Lot 1-Ta', 1, '2026-07-06', NULL, 1),
(2, 'Lot 2-Ta', 2, '2025-01-01', NULL, 1),
(3, 'Lot 01-Tin', 1, '2025-01-01', NULL, 1),
(4, 'Lot 03-Tin', 1, '2025-01-01', NULL, 1),
(5, 'Lot 4-Ta', 2, '2025-01-01', NULL, 1),
(6, 'Lot 05-Tin', 1, '2025-01-01', NULL, 1);

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
(67, 'Delete Sale', 'delete_sale', '2026-06-27 22:14:14'),
(68, 'View Journal Entries', 'view_journal_entries', '2026-07-03 22:47:11'),
(69, 'Create Journal Entry', 'create_journal_entry', '2026-07-03 22:47:11'),
(70, 'Cancel Journal Entry', 'cancel_journal_entry', '2026-07-03 22:47:11'),
(71, 'View General Ledger', 'view_general_ledger', '2026-07-03 22:47:11'),
(72, 'View Trial Balance', 'view_trial_balance', '2026-07-03 22:47:11'),
(73, 'View Account Ledger', 'view_account_ledger', '2026-07-03 22:47:11');

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
(1, 'TIN', 'Tantalum', 1, 1024, 4021, NULL, '', 1, '2026-07-06 13:45:32', '2026-07-06 13:47:14'),
(2, 'NE', 'Coltan', 1, 1034, 4021, NULL, '', 1, '2026-07-06 14:01:20', '2026-07-06 14:01:20');

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
(1, 1, 6, 0, 0, NULL),
(2, 1, 1, 1, 0, NULL);

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
  `purchase_currency_id` tinyint(3) UNSIGNED DEFAULT NULL,
  `purchase_amount_in_currency` decimal(18,4) DEFAULT NULL,
  `converted_amount` decimal(18,4) DEFAULT NULL,
  `purchase_value_usd` decimal(18,4) DEFAULT NULL COMMENT 'Total purchase value in USD',
  `net_paid_supplier_usd` decimal(18,4) DEFAULT NULL COMMENT 'Net amount paid to supplier USD',
  `charges_per_kg` decimal(12,4) DEFAULT NULL COMMENT 'Processing charge per Kg',
  `production_charges_per_kg` decimal(12,4) DEFAULT NULL,
  `price_per_ta_unit` decimal(12,4) DEFAULT NULL COMMENT 'Price per Ta unit (coltan)',
  `price_per_kg_usd` decimal(12,4) DEFAULT NULL COMMENT '$ price per Kg',
  `pricing_method` enum('lme','manual') NOT NULL DEFAULT 'lme',
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

--
-- Dumping data for table `purchasing`
--

INSERT INTO `purchasing` (`id`, `purchase_no`, `delivery_no`, `inventory_code`, `account_id`, `delivery_date`, `purchase_date`, `lot_id`, `product_id`, `supplier_id`, `negociant`, `warehouse_id`, `quantity_kg`, `uom_id`, `price_per_kg_rwf`, `purchase_value_rwf`, `exchange_rate`, `purchase_currency_id`, `purchase_amount_in_currency`, `converted_amount`, `purchase_value_usd`, `net_paid_supplier_usd`, `charges_per_kg`, `production_charges_per_kg`, `price_per_ta_unit`, `price_per_kg_usd`, `pricing_method`, `lme_price`, `tc_charges`, `tax_rra`, `tax_rma`, `tax_inkomane`, `production_charges`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'PUR-TA-0001', 'DD 1', NULL, NULL, '2025-07-15', '2025-07-15', 1, 2, 2, NULL, 1, 0.0000, 1, 0.0000, 0.00, 1400.0000, NULL, NULL, NULL, 0.0000, 0.0000, 0.0000, NULL, NULL, NULL, 'lme', NULL, NULL, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:12', '2026-07-07 15:08:12'),
(2, 'PUR-TA-0003', 'DD 1', NULL, NULL, '2025-07-16', '2025-07-16', 1, 2, 3, NULL, 1, 186.1000, 1, 78333.1610, 14577801.26, 1445.0000, NULL, NULL, NULL, 10088.4438, 9075.9703, 3.7000, NULL, NULL, NULL, 'lme', NULL, NULL, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:12', '2026-07-07 15:08:12'),
(3, 'PUR-TA-0004', 'DD 1', NULL, NULL, '2025-07-16', '2025-07-16', 1, 2, 4, NULL, 1, 116.3000, 1, 33193.0950, 3860356.95, 1445.0000, NULL, NULL, NULL, 6411.6655, 5543.1256, 5.7000, NULL, NULL, NULL, 'lme', NULL, NULL, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:12', '2026-07-07 15:08:12'),
(4, 'PUR-TA-0005', 'DD 1', NULL, NULL, '2025-07-16', '2025-07-16', 1, 2, 5, NULL, 1, 166.1000, 1, 29295.2075, 4865933.97, 1445.0000, NULL, NULL, NULL, 8081.8280, 6873.6368, 5.7000, NULL, NULL, NULL, 'lme', NULL, NULL, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:12', '2026-07-07 15:08:12'),
(5, 'PUR-TA-0006', 'DD 1', NULL, NULL, '2025-07-16', '2025-07-16', 1, 2, 6, NULL, 1, 1009.2000, 1, 31962.6775, 32256734.13, 1445.0000, NULL, NULL, NULL, 54262.0601, 46867.4409, 5.6000, NULL, NULL, NULL, 'lme', NULL, NULL, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:12', '2026-07-07 15:08:12'),
(6, 'PUR-TA-0007', 'DD 1', NULL, NULL, '2025-07-16', '2025-07-16', 1, 2, 7, NULL, 1, 327.0000, 1, 45789.1600, 14973055.32, 1445.0000, NULL, NULL, NULL, 19040.1309, 16567.6879, 5.7000, NULL, NULL, NULL, 'lme', NULL, NULL, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:12', '2026-07-07 15:08:12'),
(7, 'PUR-TA-0008', 'DD 1', NULL, NULL, '2025-07-16', '2025-07-16', 1, 2, 8, NULL, 1, 182.1000, 1, 23434.2875, 4267383.75, 1445.0000, NULL, NULL, NULL, 6678.7907, 5419.6635, 5.7000, NULL, NULL, NULL, 'lme', NULL, NULL, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:12', '2026-07-07 15:08:12'),
(8, 'PUR-TA-0009', 'DD 1', NULL, NULL, '2025-07-16', '2025-07-16', 1, 2, 9, NULL, 1, 171.0000, 1, 38407.6000, 6567699.60, 1450.0000, NULL, NULL, NULL, 8945.6598, 7683.1314, 5.7000, NULL, NULL, NULL, 'lme', NULL, NULL, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:12', '2026-07-07 15:08:12'),
(9, 'PUR-TA-0010', 'DD 1', NULL, NULL, '2025-09-15', '2025-09-15', 2, 2, 10, NULL, 1, 1570.5000, 1, 28850.3648, 45309497.85, 1450.0000, NULL, NULL, NULL, 97866.6498, 85800.0882, 5.7000, NULL, NULL, NULL, 'lme', NULL, NULL, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:12', '2026-07-07 15:08:12'),
(10, 'PUR-SN-0001', NULL, NULL, NULL, '2025-07-15', '2025-07-15', 3, 1, 2, NULL, 1, 0.0000, 1, 0.0000, 0.00, 1400.0000, NULL, NULL, NULL, 0.0000, 0.0000, 0.0000, NULL, NULL, NULL, 'lme', 0.0000, 0.0000, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(11, 'PUR-SN-0003', NULL, NULL, NULL, '2025-07-16', '2025-07-16', 3, 1, 11, NULL, 1, 1343.5000, 1, 21588.8601, 29004633.50, 1445.2000, NULL, NULL, NULL, 20069.6329, 14611.5489, 3.5000, NULL, NULL, NULL, 'lme', 32800.0000, 3000.0000, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(12, 'PUR-SN-0004', NULL, NULL, NULL, '2025-07-16', '2025-07-16', 3, 1, 12, NULL, 1, 71.9000, 1, 22295.1582, 1603021.88, 1445.2000, NULL, NULL, NULL, 1109.2042, 816.0501, 3.5000, NULL, NULL, NULL, 'lme', 32800.0000, 3000.0000, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(13, 'PUR-SN-0005', NULL, NULL, NULL, '2025-07-16', '2025-07-16', 3, 1, 13, NULL, 1, 61.4000, 1, 21778.4703, 1337198.08, 1445.2000, NULL, NULL, NULL, 925.2685, 675.5841, 3.5000, NULL, NULL, NULL, 'lme', 32800.0000, 3000.0000, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(14, 'PUR-SN-0006', NULL, NULL, NULL, '2025-07-16', '2025-07-16', 3, 1, 4, NULL, 1, 312.2000, 1, 28867.2341, 9012350.49, 1445.2000, NULL, NULL, NULL, 6236.0576, 4925.2319, 3.5000, NULL, NULL, NULL, 'lme', 32800.0000, 2500.0000, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(15, 'PUR-AP-0004', NULL, NULL, NULL, '2026-12-31', '2026-12-31', 3, 1, 14, NULL, 1, 0.0000, 1, 0.0000, 0.00, 1400.0000, NULL, NULL, NULL, 206085.3100, 206085.3100, NULL, NULL, NULL, NULL, 'lme', NULL, NULL, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(16, 'PUR-AP-0005', NULL, NULL, NULL, '2026-12-31', '2026-12-31', 4, 1, 14, NULL, 1, 3241.9000, 1, 0.0000, 0.00, 1400.0000, NULL, NULL, NULL, 65052.6330, 65052.6330, NULL, NULL, NULL, NULL, 'lme', NULL, NULL, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(17, 'PUR-AP-0006', NULL, NULL, NULL, '2026-12-31', '2026-12-31', 4, 1, 15, NULL, 1, 1178.4000, 1, 0.0000, 0.00, 1400.0000, NULL, NULL, NULL, 28200.0000, 28200.0000, NULL, NULL, NULL, NULL, 'lme', NULL, NULL, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(18, 'PUR-AP-0007', NULL, NULL, NULL, '2026-12-31', '2026-12-31', 4, 1, 16, NULL, 1, 354.0000, 1, 0.0000, 0.00, 1400.0000, NULL, NULL, NULL, 7557.8512, 7557.8512, NULL, NULL, NULL, NULL, 'lme', NULL, NULL, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(19, 'PUR-AP-0008', NULL, NULL, NULL, '2026-12-31', '2026-12-31', 4, 1, 17, NULL, 1, 1843.0000, 1, 0.0000, 0.00, 1400.0000, NULL, NULL, NULL, 40617.0799, 40617.0799, NULL, NULL, NULL, NULL, 'lme', NULL, NULL, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(20, 'PUR-AP-0009', NULL, NULL, NULL, '2026-12-31', '2026-12-31', 4, 1, 18, NULL, 1, 484.1000, 1, 0.0000, 0.00, 1400.0000, NULL, NULL, NULL, 11593.3767, 11593.3767, NULL, NULL, NULL, NULL, 'lme', NULL, NULL, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(21, 'PUR-AP-0010', NULL, NULL, NULL, '2026-12-31', '2026-12-31', 4, 1, 19, NULL, 1, 2599.8000, 1, 0.0000, 0.00, 1400.0000, NULL, NULL, NULL, 54322.2378, 54322.2378, NULL, NULL, NULL, NULL, 'lme', NULL, NULL, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(22, 'PUR-AP-0011', NULL, NULL, NULL, '2026-12-31', '2026-12-31', 5, 2, 19, NULL, 1, 1087.4000, 1, 0.0000, 0.00, 1400.0000, NULL, NULL, NULL, 64670.0000, 64670.0000, NULL, NULL, NULL, NULL, 'lme', NULL, NULL, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(23, 'PUR-AP-0012', NULL, NULL, NULL, '2026-01-05', '2026-01-05', 6, 1, 20, NULL, 1, 1407.5000, 1, 0.0000, 0.00, 1400.0000, NULL, NULL, NULL, 35402.5269, 35402.5269, NULL, NULL, NULL, NULL, 'lme', NULL, NULL, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13'),
(24, 'PUR-AP-0013', NULL, NULL, NULL, '2026-12-31', '2026-12-31', 4, 1, 20, NULL, 1, 949.4000, 1, 0.0000, 0.00, 1400.0000, NULL, NULL, NULL, 23240.0500, 23240.0500, NULL, NULL, NULL, NULL, 'lme', NULL, NULL, NULL, NULL, NULL, NULL, 'received', NULL, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13');

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
(1, 2, 1, 0.418582, NULL),
(2, 3, 1, 0.431148, NULL),
(3, 4, 1, 0.380518, NULL),
(4, 5, 1, 0.415166, NULL),
(5, 6, 1, 0.483242, NULL),
(6, 7, 1, 0.304390, NULL),
(7, 8, 1, 0.403942, NULL),
(8, 9, 1, 0.362300, NULL),
(9, 11, 4, 0.546900, NULL),
(10, 12, 4, 0.561800, NULL),
(11, 13, 4, 0.550900, NULL),
(12, 14, 4, 0.685200, NULL);

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
(1, 68),
(1, 69),
(1, 70),
(1, 71),
(1, 72),
(1, 73),
(2, 7),
(2, 10),
(2, 11),
(2, 68),
(2, 69),
(2, 70),
(2, 71),
(2, 72),
(2, 73),
(3, 19),
(3, 27),
(3, 31),
(3, 35),
(3, 40),
(3, 54),
(3, 68),
(3, 71),
(3, 72),
(3, 73);

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
  `purchase_currency_id` tinyint(3) UNSIGNED DEFAULT NULL,
  `purchase_amount_in_currency` decimal(18,4) DEFAULT NULL,
  `exchange_rate` decimal(14,4) DEFAULT NULL,
  `converted_amount` decimal(18,4) DEFAULT NULL,
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
  `purchase_currency_id` tinyint(3) UNSIGNED DEFAULT NULL,
  `purchase_amount_in_currency` decimal(18,4) DEFAULT NULL,
  `exchange_rate` decimal(14,4) DEFAULT NULL,
  `converted_amount` decimal(18,4) DEFAULT NULL,
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
(1, 'individual', 'Eugene ndayishimiye', '', NULL, '+250785750117', 'nendayishimye@gmail.com', 'Unnamed Road', 27, NULL, NULL, 1, '', '2026-07-06 13:45:03', '2026-07-06 13:45:03', 2, NULL),
(2, 'individual', 'Opening Stock', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-07-07 15:08:12', '2026-07-07 15:08:12', 2, NULL),
(3, 'individual', 'Furaha', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-07-07 15:08:12', '2026-07-07 15:08:12', 2, NULL),
(4, 'individual', 'Jules', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-07-07 15:08:12', '2026-07-07 15:08:12', 2, NULL),
(5, 'individual', 'Kabumba', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-07-07 15:08:12', '2026-07-07 15:08:12', 2, NULL),
(6, 'individual', 'Mama Kaziba 1&2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-07-07 15:08:12', '2026-07-07 15:08:12', 2, NULL),
(7, 'individual', 'Gasana 1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-07-07 15:08:12', '2026-07-07 15:08:12', 2, NULL),
(8, 'individual', 'Gasana 2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-07-07 15:08:12', '2026-07-07 15:08:12', 2, NULL),
(9, 'individual', 'Charles', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-07-07 15:08:12', '2026-07-07 15:08:12', 2, NULL),
(10, 'individual', 'Eric ZURU', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-07-07 15:08:12', '2026-07-07 15:08:12', 2, NULL),
(11, 'individual', 'Christine 1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13', 2, NULL),
(12, 'individual', 'Christine 2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13', 2, NULL),
(13, 'individual', 'Christine 3', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13', 2, NULL),
(14, 'individual', 'Paulin Murego', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13', 2, NULL),
(15, 'individual', 'Darius BIMENYIMANA', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13', 2, NULL),
(16, 'individual', 'Richard AKAYEZU', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13', 2, NULL),
(17, 'individual', 'Marc NSHIMYUMUREMYI', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13', 2, NULL),
(18, 'individual', 'MURINDWA ANDRE', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13', 2, NULL),
(19, 'individual', 'Eprocomi', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13', 2, NULL),
(20, 'individual', 'Bosco', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13', 2, NULL);

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

--
-- Dumping data for table `supplier_advances`
--

INSERT INTO `supplier_advances` (`id`, `supplier_id`, `currency_id`, `amount`, `exchange_rate`, `advance_date`, `purpose`, `status`, `notes`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 14, 2, 131000.00, 1.000000, '2026-12-31', 'Payables Advance for Tin - Lot 01 & 02', 'PAID', NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13', 2),
(2, 19, 2, 20000.00, 1.000000, '2026-12-31', 'Payables Advance for Tin - Lot 03', 'PAID', NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13', 2),
(3, 19, 2, 44000.00, 1.000000, '2026-12-31', 'Payables Advance for Ta - Lot 04', 'PAID', NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13', 2),
(4, 20, 2, 25000.00, 1.000000, '2026-01-05', 'Payables Advance for Tin - Lot 05', 'PAID', NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13', 2),
(5, 20, 2, 12500.00, 1.000000, '2026-12-31', 'Payables Advance for Tin - Lot 03', 'PAID', NULL, '2026-07-07 15:08:13', '2026-07-07 15:08:13', 2);

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
(1, 'KGL-WH', 'Kigali Warehouse', NULL, 1, '2026-07-07 15:08:12', 2);

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
-- Indexes for table `bank_recon_items`
--
ALTER TABLE `bank_recon_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bank_statement_balances`
--
ALTER TABLE `bank_statement_balances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug_date` (`report_slug`,`as_of_date`);

--
-- Indexes for table `cash_counts`
--
ALTER TABLE `cash_counts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug_date_denom` (`report_slug`,`count_date`,`denomination`,`currency`);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `account_types`
--
ALTER TABLE `account_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_recon_items`
--
ALTER TABLE `bank_recon_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_statement_balances`
--
ALTER TABLE `bank_statement_balances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cash_counts`
--
ALTER TABLE `cash_counts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=167;

--
-- AUTO_INCREMENT for table `lots`
--
ALTER TABLE `lots`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `product_element`
--
ALTER TABLE `product_element`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `product_element_composition`
--
ALTER TABLE `product_element_composition`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchasing`
--
ALTER TABLE `purchasing`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `purchasing_element_grade`
--
ALTER TABLE `purchasing_element_grade`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `supplier_advances`
--
ALTER TABLE `supplier_advances`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- Constraints for table `purchasing`
--
ALTER TABLE `purchasing`
  ADD CONSTRAINT `fk_purchasing_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
