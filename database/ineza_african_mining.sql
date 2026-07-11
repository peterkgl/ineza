-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 11, 2026 at 01:28 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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
(1, 4, '1024', 'Stock - Tin', 1, '', '2026-07-10 09:10:49', '2026-07-10 09:10:49'),
(2, 4, '1034', 'Stock - Tantalum', 1, '', '2026-07-10 09:11:07', '2026-07-10 09:11:07'),
(3, 37, '4011', 'sales - Tin', 1, '', '2026-07-10 09:13:12', '2026-07-10 09:13:12'),
(4, 37, '4021', 'sales - Tantalum', 1, '', '2026-07-10 09:13:38', '2026-07-10 09:13:38'),
(5, 41, '5011', 'Mineral Cost', 1, '', '2026-07-10 09:14:35', '2026-07-10 09:14:35'),
(6, 15, '2014', 'Eugene ndayishimiye - Accounts Payable', 1, 'Auto-created account for supplier: Eugene ndayishimiye', '2026-07-10 09:15:31', '2026-07-10 09:15:31'),
(7, 15, '2015', 'Mr Peter Umupapa mwiza - Accounts Payable', 1, 'Auto-created account for supplier: Mr Peter Umupapa mwiza', '2026-07-10 09:16:12', '2026-07-10 09:16:12'),
(9, 15, '2016', 'Jean Nepo - Accounts Payable', 1, 'Auto-created account for supplier: Jean Nepo', '2026-07-10 16:27:48', '2026-07-10 16:27:48'),
(10, 1, '1021', 'IAM Petty Cash Fund HQ - NSANA Jean', 1, '', '2026-07-11 07:54:52', '2026-07-11 07:54:52'),
(11, 1, '1031', 'EQUITY RWF Account - INEZA AFRICAN MINING', 1, '', '2026-07-11 07:55:07', '2026-07-11 07:55:07'),
(12, 1, '1041', 'EQUITY $ Account - INEZA AFRICAN MINING', 1, '', '2026-07-11 07:55:23', '2026-07-11 07:55:23'),
(13, 1, '1051', 'EQUITY € Account - INEZA AFRICAN MINING', 1, '', '2026-07-11 07:55:36', '2026-07-11 07:55:36'),
(14, 1, '1061', 'IAM Petty Cash on Site RUB - Gedeon HARERIMANA', 1, '', '2026-07-11 07:55:51', '2026-07-11 07:55:51');

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
(1, '1001', 'Cash & Cash Equivalents', -1, 1, 1, '2026-07-02 08:49:04', '2026-07-11 07:52:48'),
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

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_full_name`, `action`, `target_table`, `target_name`, `target_description`, `old_values`, `new_values`, `ip_address`, `user_agent`, `session_id`, `notes`, `performed_at`) VALUES
(1, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:03:05'),
(2, 'Super Admin', 'CREATE', 'accounts', '1024', 'Created new account: Stock - Tin (1024)', NULL, '{\"id\":1,\"account_type_id\":4,\"account_code\":\"1024\",\"account_name\":\"Stock - Tin\",\"is_active\":1,\"description\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:10:49'),
(3, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:10:49'),
(4, 'Super Admin', 'CREATE', 'accounts', '1034', 'Created new account: Stock - Tantalum (1034)', NULL, '{\"id\":2,\"account_type_id\":4,\"account_code\":\"1034\",\"account_name\":\"Stock - Tantalum\",\"is_active\":1,\"description\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:11:07'),
(5, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:11:07'),
(6, 'Super Admin', 'CREATE', 'accounts', '4011', 'Created new account: sales - Tin (4011)', NULL, '{\"id\":3,\"account_type_id\":37,\"account_code\":\"4011\",\"account_name\":\"sales - Tin\",\"is_active\":1,\"description\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:13:12'),
(7, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:13:12'),
(8, 'Super Admin', 'CREATE', 'accounts', '4021', 'Created new account: sales - Tantalum (4021)', NULL, '{\"id\":4,\"account_type_id\":37,\"account_code\":\"4021\",\"account_name\":\"sales - Tantalum\",\"is_active\":1,\"description\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:13:38'),
(9, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:13:38'),
(10, 'Super Admin', 'CREATE', 'accounts', '5011', 'Created new account: Mineral Cost (5011)', NULL, '{\"id\":5,\"account_type_id\":41,\"account_code\":\"5011\",\"account_name\":\"Mineral Cost\",\"is_active\":1,\"description\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:14:35'),
(11, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:14:35'),
(12, 'Super Admin', 'CREATE', 'suppliers', 'Eugene ndayishimiye', 'Created supplier: Eugene ndayishimiye (individual)', NULL, '{\"id\":1,\"supplier_type\":\"individual\",\"name\":\"Eugene ndayishimiye\",\"nif\":\"\",\"phone\":\"+250785750117\",\"email\":\"nendayishimye@gmail.com\",\"address\":\"Unnamed Road\",\"payables_account_id\":6,\"is_active\":1,\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:15:31'),
(13, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:15:31'),
(14, 'Super Admin', 'CREATE', 'suppliers', 'Mr Peter Umupapa mwiza', 'Created supplier: Mr Peter Umupapa mwiza (individual)', NULL, '{\"id\":2,\"supplier_type\":\"individual\",\"name\":\"Mr Peter Umupapa mwiza\",\"nif\":\"\",\"phone\":\"+250789887655\",\"email\":\"mrpeter@gmail.com\",\"address\":\"musanze faraja\",\"payables_account_id\":7,\"is_active\":1,\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:16:12'),
(15, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:16:12'),
(16, 'Super Admin', 'CREATE', 'product', 'SN', 'Created product: Tin', NULL, '{\"id\":1,\"product_code\":\"SN\",\"product_name\":\"Tin\",\"uom_id\":1,\"inventory_account_id\":1024,\"sales_account_id\":4011,\"cogs_account_id\":5011,\"description\":\"\",\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:17:28'),
(17, 'Super Admin', 'VIEW', 'product', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:17:28'),
(18, 'Super Admin', 'CREATE', 'lots', 'Lot 1-Ta', 'Created lot: Lot 1-Ta', NULL, '{\"id\":1,\"lots_code\":\"Lot 1-Ta\",\"product_id\":1,\"opening_date\":\"2026-07-10\",\"closing_date\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:18:22'),
(19, 'Super Admin', 'VIEW', 'lots', 'Lots List', 'User viewed the lots list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:18:22'),
(20, 'Super Admin', 'CREATE', 'product', 'TA', 'Created product: Tantalum', NULL, '{\"id\":2,\"product_code\":\"TA\",\"product_name\":\"Tantalum\",\"uom_id\":1,\"inventory_account_id\":1034,\"sales_account_id\":4021,\"cogs_account_id\":5011,\"description\":\"\",\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:19:18'),
(21, 'Super Admin', 'VIEW', 'product', 'Products List', 'User viewed the products list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:19:18'),
(22, 'Super Admin', 'UPDATE', 'lots', 'Lot 1-Ta', 'Updated lot: Lot 1-Ta', '{\"id\":\"1\",\"lots_code\":\"Lot 1-Ta\",\"product_id\":\"1\",\"opening_date\":\"2026-07-10\",\"closing_date\":null,\"statuss\":\"1\"}', '{\"id\":1,\"lots_code\":\"Lot 1-SN\",\"product_id\":1,\"opening_date\":\"2026-07-10\",\"closing_date\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:20:12'),
(23, 'Super Admin', 'VIEW', 'lots', 'Lots List', 'User viewed the lots list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:20:12'),
(24, 'Super Admin', 'CREATE', 'lots', 'Lot 1-Ta', 'Created lot: Lot 1-Ta', NULL, '{\"id\":2,\"lots_code\":\"Lot 1-Ta\",\"product_id\":2,\"opening_date\":\"2026-07-10\",\"closing_date\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:20:34'),
(25, 'Super Admin', 'VIEW', 'lots', 'Lots List', 'User viewed the lots list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:20:34'),
(26, 'Super Admin', 'CREATE', 'purchasing', 'PUR-20260710-9FDB', 'Recorded mining purchase: PUR-20260710-9FDB', NULL, '{\"id\":1,\"purchase_no\":\"PUR-20260710-9FDB\",\"product_id\":1,\"quantity_kg\":1343.5,\"purchase_value_usd\":20069.63292}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:34:29'),
(27, 'Super Admin', 'UPDATE', 'purchasing', 'PUR-20260710-9FDB', 'Status changed from pending to received', '{\"status\":\"pending\"}', '{\"status\":\"received\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:37:35'),
(28, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '46dhdu729ucs6hs5sdmebo679i', NULL, '2026-07-10 09:43:29'),
(29, 'Super Admin', 'CREATE', 'purchasing', 'PUR-20260710-CEF4', 'Recorded mining purchase: PUR-20260710-CEF4', NULL, '{\"id\":2,\"purchase_no\":\"PUR-20260710-CEF4\",\"product_id\":1,\"quantity_kg\":71.9,\"purchase_value_usd\":1109.204176}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'jdv6femo33h5m3k4gvt5khas25', NULL, '2026-07-10 10:36:23'),
(30, 'Super Admin', 'CREATE', 'purchasing', 'PUR-20260710-F36C', 'Recorded mining purchase: PUR-20260710-F36C', NULL, '{\"id\":3,\"purchase_no\":\"PUR-20260710-F36C\",\"product_id\":1,\"quantity_kg\":71.9,\"purchase_value_usd\":1109.204176}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'jdv6femo33h5m3k4gvt5khas25', NULL, '2026-07-10 10:41:58'),
(31, 'Super Admin', 'UPDATE', 'purchasing', 'PUR-20260710-F36C', 'Status changed from pending to received', '{\"status\":\"pending\"}', '{\"status\":\"received\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'jdv6femo33h5m3k4gvt5khas25', NULL, '2026-07-10 10:42:09'),
(32, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'jdv6femo33h5m3k4gvt5khas25', NULL, '2026-07-10 11:00:24'),
(33, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'jdv6femo33h5m3k4gvt5khas25', NULL, '2026-07-10 11:00:28'),
(34, 'Super Admin', 'CREATE', 'purchasing', 'PUR-20260710-91AC', 'Recorded mining purchase: PUR-20260710-91AC', NULL, '{\"id\":1,\"purchase_no\":\"PUR-20260710-91AC\",\"product_id\":1,\"quantity_kg\":1343.5,\"purchase_value_usd\":20069.63292}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'jdv6femo33h5m3k4gvt5khas25', NULL, '2026-07-10 11:02:45'),
(35, 'Super Admin', 'UPDATE', 'purchasing', 'PUR-20260710-91AC', 'Status changed from pending to received', '{\"status\":\"pending\"}', '{\"status\":\"received\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'jdv6femo33h5m3k4gvt5khas25', NULL, '2026-07-10 11:03:27'),
(36, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'jdv6femo33h5m3k4gvt5khas25', NULL, '2026-07-10 11:06:21'),
(37, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'jdv6femo33h5m3k4gvt5khas25', NULL, '2026-07-10 11:07:09'),
(38, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'jdv6femo33h5m3k4gvt5khas25', NULL, '2026-07-10 11:07:26'),
(39, 'Super Admin', 'CREATE', 'sells', 'SALE-20260710-C5D9', 'Recorded sales order: SALE-20260710-C5D9', NULL, '{\"id\":1,\"sale_no\":\"SALE-20260710-C5D9\",\"customer_id\":1,\"total_qty_kg\":200,\"total_value_usd\":500000,\"amount_paid\":3000}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'jdv6femo33h5m3k4gvt5khas25', NULL, '2026-07-10 11:26:01'),
(40, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'jdv6femo33h5m3k4gvt5khas25', NULL, '2026-07-10 11:26:04'),
(41, 'Super Admin', 'CREATE', 'journal_entries', 'JE-20260710-0005', 'Created manual journal entry: JE-20260710-0005', NULL, '{\"id\":5,\"journal_no\":\"JE-20260710-0005\",\"entry_date\":\"2026-07-10\",\"total_debit\":45000,\"description\":\"to viewing all details only\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'jdv6femo33h5m3k4gvt5khas25', NULL, '2026-07-10 11:38:05'),
(42, 'Super Admin', 'CREATE', 'suppliers', 'Jean Nepo', 'Created supplier: Jean Nepo (individual)', NULL, '{\"id\":3,\"supplier_type\":\"individual\",\"name\":\"Jean Nepo\",\"nif\":\"12345678\",\"phone\":\"+250789028963\",\"email\":\"ndahayoptr@gmail.com\",\"address\":\"KN 1 Rd, Kigali\",\"payables_account_id\":9,\"is_active\":1,\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'rfsfqole0em439g056edsn7ojb', NULL, '2026-07-10 16:27:48'),
(43, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'rfsfqole0em439g056edsn7ojb', NULL, '2026-07-10 16:27:48'),
(44, 'Super Admin', 'UPDATE', 'suppliers', 'Jean Nepo Supplier', 'Updated supplier: Jean Nepo Supplier (individual)', '{\"id\":\"3\",\"supplier_type\":\"individual\",\"name\":\"Jean Nepo\",\"nif\":\"12345678\",\"vat_reg_no\":null,\"phone\":\"+250789028963\",\"email\":\"ndahayoptr@gmail.com\",\"address\":\"KN 1 Rd, Kigali\",\"payables_account_id\":\"2016\",\"currency_id\":null,\"region\":null,\"is_active\":\"1\",\"notes\":\"\",\"created_at\":\"2026-07-10 18:27:48\",\"updated_at\":\"2026-07-10 18:27:48\",\"created_by\":\"2\",\"updated_by\":null}', '{\"id\":3,\"supplier_type\":\"individual\",\"name\":\"Jean Nepo Supplier\",\"nif\":\"12345678\",\"phone\":\"+250789028963\",\"email\":\"ndahayoptr@gmail.com\",\"address\":\"KN 1 Rd, Kigali\",\"is_active\":1,\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'rfsfqole0em439g056edsn7ojb', NULL, '2026-07-10 16:29:03'),
(45, 'Super Admin', 'VIEW', 'suppliers', 'Suppliers List', 'User viewed the suppliers list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'rfsfqole0em439g056edsn7ojb', NULL, '2026-07-10 16:29:03'),
(46, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'rfsfqole0em439g056edsn7ojb', NULL, '2026-07-10 17:27:20'),
(76, 'Super Admin', 'CREATE', 'purchasing', 'PUR-20260710-0524', 'Recorded mining purchase: PUR-20260710-0524', NULL, '{\"id\":34,\"purchase_no\":\"PUR-20260710-0524\",\"product_id\":1,\"quantity_kg\":1343.5,\"purchase_value_usd\":20069.63292}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '08o7drta8645qskbidanp7cp7c', NULL, '2026-07-10 21:51:46'),
(77, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'rfsfqole0em439g056edsn7ojb', NULL, '2026-07-10 21:53:50'),
(78, 'Super Admin', 'UPDATE', 'purchasing', 'PUR-20260710-0524', 'Status changed from pending to received', '{\"status\":\"pending\"}', '{\"status\":\"received\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'rfsfqole0em439g056edsn7ojb', NULL, '2026-07-10 21:58:05'),
(79, 'Super Admin', 'UPDATE', 'stock', 'Adjustment: Loss', 'Adjusted stock id 1 by 2 kg (Loss)', '{\"id\":\"1\",\"warehouse_id\":\"1\",\"product_id\":\"1\",\"lot_id\":\"1\",\"uom_id\":\"1\",\"qty_purchased\":\"1343.5000\",\"qty_sold\":\"0.0000\",\"qty_adjusted\":\"0.0000\",\"qty_on_hand\":\"1343.5000\",\"avg_cost_per_kg_rwf\":\"21588.8601\",\"avg_cost_per_kg_usd\":\"14.9383\",\"purchase_currency_id\":\"2\",\"purchase_amount_in_currency\":\"20069.6329\",\"exchange_rate\":\"1445.2000\",\"converted_amount\":\"29004633.4960\",\"total_value_rwf\":\"29004633.50\",\"total_value_usd\":\"20069.6329\",\"last_updated\":\"2026-07-11 00:01:47\",\"notes\":null,\"opening\":\"1343.5000\",\"closing\":\"1343.5000\",\"last_rolled_over_at\":\"2026-07-11\"}', '{\"id\":1,\"qty_adjusted\":-2,\"closing\":1341.5}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'rfsfqole0em439g056edsn7ojb', NULL, '2026-07-10 22:22:26'),
(80, 'Super Admin', 'UPDATE', 'stock', 'Adjustment: Gain', 'Adjusted stock id 1 by 2 kg (Gain)', '{\"id\":\"1\",\"warehouse_id\":\"1\",\"product_id\":\"1\",\"lot_id\":\"1\",\"uom_id\":\"1\",\"qty_purchased\":\"1343.5000\",\"qty_sold\":\"0.0000\",\"qty_adjusted\":\"-2.0000\",\"qty_on_hand\":\"1341.5000\",\"avg_cost_per_kg_rwf\":\"21588.8601\",\"avg_cost_per_kg_usd\":\"14.9383\",\"purchase_currency_id\":\"2\",\"purchase_amount_in_currency\":\"20069.6329\",\"exchange_rate\":\"1445.2000\",\"converted_amount\":\"29004633.4960\",\"total_value_rwf\":\"29004633.50\",\"total_value_usd\":\"20069.6329\",\"last_updated\":\"2026-07-11 00:22:26\",\"notes\":null,\"opening\":\"1343.5000\",\"closing\":\"1341.5000\",\"last_rolled_over_at\":\"2026-07-11\"}', '{\"id\":1,\"qty_adjusted\":0,\"closing\":1343.5}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'rfsfqole0em439g056edsn7ojb', NULL, '2026-07-10 22:28:47'),
(81, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'rfsfqole0em439g056edsn7ojb', NULL, '2026-07-10 22:30:13'),
(82, 'Super Admin', 'UPDATE', 'stock', 'Adjustment: Loss', 'Adjusted stock id 1 by 3 kg (Loss)', '{\"id\":\"1\",\"warehouse_id\":\"1\",\"product_id\":\"1\",\"lot_id\":\"1\",\"uom_id\":\"1\",\"qty_purchased\":\"1343.5000\",\"qty_sold\":\"0.0000\",\"qty_adjusted\":\"0.0000\",\"qty_on_hand\":\"1343.5000\",\"avg_cost_per_kg_rwf\":\"21588.8601\",\"avg_cost_per_kg_usd\":\"14.9383\",\"purchase_currency_id\":\"2\",\"purchase_amount_in_currency\":\"20069.6329\",\"exchange_rate\":\"1445.2000\",\"converted_amount\":\"29004633.4960\",\"total_value_rwf\":\"29004633.50\",\"total_value_usd\":\"20069.6329\",\"last_updated\":\"2026-07-11 00:28:47\",\"notes\":null,\"opening\":\"1343.5000\",\"closing\":\"1343.5000\",\"last_rolled_over_at\":\"2026-07-11\"}', '{\"id\":1,\"qty_adjusted\":-3,\"closing\":1340.5}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'rfsfqole0em439g056edsn7ojb', NULL, '2026-07-10 22:35:15'),
(83, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'grnvmrfsu458cs56l9d553h3rg', NULL, '2026-07-11 07:48:13'),
(84, 'Super Admin', 'UPDATE', 'account_types', '1001', 'Updated account type: Cash & Cash Equivalents (1001)', '{\"id\":\"1\",\"code\":\"1001\",\"name\":\"Cash & Cash Equivalenta\",\"parent_id\":\"-1\",\"is_editable\":\"1\",\"is_deletable\":\"1\",\"created_at\":\"2026-07-02 10:49:04\",\"updated_at\":\"2026-07-02 10:49:04\"}', '{\"id\":1,\"code\":\"1001\",\"name\":\"Cash & Cash Equivalents\",\"parent_id\":-1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'grnvmrfsu458cs56l9d553h3rg', NULL, '2026-07-11 07:52:48'),
(85, 'Super Admin', 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'grnvmrfsu458cs56l9d553h3rg', NULL, '2026-07-11 07:52:48'),
(86, 'Super Admin', 'CREATE', 'accounts', '1021', 'Created new account: IAM Petty Cash Fund HQ - NSANA Jean (1021)', NULL, '{\"id\":10,\"account_type_id\":1,\"account_code\":\"1021\",\"account_name\":\"IAM Petty Cash Fund HQ - NSANA Jean\",\"is_active\":1,\"description\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'grnvmrfsu458cs56l9d553h3rg', NULL, '2026-07-11 07:54:52'),
(87, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'grnvmrfsu458cs56l9d553h3rg', NULL, '2026-07-11 07:54:52'),
(88, 'Super Admin', 'CREATE', 'accounts', '1031', 'Created new account: EQUITY RWF Account - INEZA AFRICAN MINING (1031)', NULL, '{\"id\":11,\"account_type_id\":1,\"account_code\":\"1031\",\"account_name\":\"EQUITY RWF Account - INEZA AFRICAN MINING\",\"is_active\":1,\"description\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'grnvmrfsu458cs56l9d553h3rg', NULL, '2026-07-11 07:55:07'),
(89, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'grnvmrfsu458cs56l9d553h3rg', NULL, '2026-07-11 07:55:07'),
(90, 'Super Admin', 'CREATE', 'accounts', '1041', 'Created new account: EQUITY $ Account - INEZA AFRICAN MINING (1041)', NULL, '{\"id\":12,\"account_type_id\":1,\"account_code\":\"1041\",\"account_name\":\"EQUITY $ Account - INEZA AFRICAN MINING\",\"is_active\":1,\"description\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'grnvmrfsu458cs56l9d553h3rg', NULL, '2026-07-11 07:55:23'),
(91, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'grnvmrfsu458cs56l9d553h3rg', NULL, '2026-07-11 07:55:23'),
(92, 'Super Admin', 'CREATE', 'accounts', '1051', 'Created new account: EQUITY € Account - INEZA AFRICAN MINING (1051)', NULL, '{\"id\":13,\"account_type_id\":1,\"account_code\":\"1051\",\"account_name\":\"EQUITY € Account - INEZA AFRICAN MINING\",\"is_active\":1,\"description\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'grnvmrfsu458cs56l9d553h3rg', NULL, '2026-07-11 07:55:36'),
(93, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'grnvmrfsu458cs56l9d553h3rg', NULL, '2026-07-11 07:55:36'),
(94, 'Super Admin', 'CREATE', 'accounts', '1061', 'Created new account: IAM Petty Cash on Site RUB - Gedeon HARERIMANA (1061)', NULL, '{\"id\":14,\"account_type_id\":1,\"account_code\":\"1061\",\"account_name\":\"IAM Petty Cash on Site RUB - Gedeon HARERIMANA\",\"is_active\":1,\"description\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'grnvmrfsu458cs56l9d553h3rg', NULL, '2026-07-11 07:55:51'),
(95, 'Super Admin', 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'grnvmrfsu458cs56l9d553h3rg', NULL, '2026-07-11 07:55:51'),
(96, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'grnvmrfsu458cs56l9d553h3rg', NULL, '2026-07-11 08:21:30'),
(97, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'grnvmrfsu458cs56l9d553h3rg', NULL, '2026-07-11 09:40:18'),
(98, 'Super Admin', 'VIEW', 'sells', 'Sales List', 'User viewed the sales list', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '95us1fnq7tkcmtnkmtnk2deom0', NULL, '2026-07-11 09:42:55');

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
(2, 1, 2, 1445.20000000, '2026-06-17', 'bnr', 2, '2026-06-17 11:49:17', 2, '2026-07-10 16:38:38');

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
(18, 'JE-20260710-0001', '2026-07-10', 'Purchase recorded: PUR-20260710-0524', '', 2, '2026-07-10 21:51:46'),
(23, 'JE-20260711-0001', '2026-07-11', 'Stock adjustment loss for product #1', '', 2, '2026-07-10 22:22:26'),
(24, 'JE-20260711-0002', '2026-07-11', 'Stock adjustment gain for product #1', '', 2, '2026-07-10 22:28:47'),
(25, 'JE-20260711-0003', '2026-07-11', 'Stock adjustment loss for product #1', '', 2, '2026-07-10 22:35:15');

-- --------------------------------------------------------

--
-- Table structure for table `journal_entry_lines`
--

CREATE TABLE `journal_entry_lines` (
  `id` bigint(20) NOT NULL,
  `journal_entry_id` bigint(20) NOT NULL,
  `parent_account_id` bigint(20) NOT NULL,
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

INSERT INTO `journal_entry_lines` (`id`, `journal_entry_id`, `parent_account_id`, `account_id`, `debit`, `credit`, `currency_id`, `exchange_rate`, `amount_currency`, `amount_base`, `description`) VALUES
(2, 18, 4, 1024, 14611.55, 0.00, 2, 1445.200000, 14611.55, 21116610.35, 'Purchase recorded: PUR-20260710-0524'),
(3, 18, 15, 2016, 0.00, 14611.55, 2, 1445.200000, 14611.55, 21116610.35, 'Purchase recorded: PUR-20260710-0524'),
(4, 23, 4, 5011, 2.00, 0.00, 2, 1.000000, 2.00, 2.00, 'Stock adjustment loss for product #1'),
(5, 23, 4, 1024, 0.00, 2.00, 2, 1.000000, 2.00, 2.00, 'Stock adjustment loss for product #1'),
(6, 24, 4, 1024, 29.88, 0.00, 2, 1445.200000, 29.88, 43177.66, 'Stock adjustment gain for product #1'),
(7, 24, 4, 5011, 0.00, 29.88, 2, 1445.200000, 29.88, 43177.66, 'Stock adjustment gain for product #1'),
(8, 25, 41, 5, 44.81, 0.00, 2, 1445.200000, 44.81, 64766.49, 'Stock adjustment loss for product #1'),
(9, 25, 4, 1, 0.00, 44.81, 2, 1445.200000, 44.81, 64766.49, 'Stock adjustment loss for product #1');

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
(1, 2, 'admin@gmail.com', '2026-07-10 10:23:24', '::1', 'success'),
(2, 2, 'admin@gmail.com', '2026-07-10 10:28:02', '::1', 'success'),
(3, 2, 'admin@gmail.com', '2026-07-10 21:44:01', '::1', 'success'),
(4, 2, 'admin@gmail.com', '2026-07-11 07:48:07', '::1', 'success'),
(5, 2, 'admin@gmail.com', '2026-07-11 09:42:43', '::1', 'success');

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
(1, 'Lot 1-SN', 1, '2026-07-10', NULL, 1),
(2, 'Lot 1-Ta', 2, '2026-07-10', NULL, 1);

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
(1, 'SN', 'Tin', 1, 1024, 4011, 5011, '', 1, '2026-07-10 09:17:28', '2026-07-10 09:17:28'),
(2, 'TA', 'Tantalum', 1, 1034, 4021, 5011, '', 1, '2026-07-10 09:19:18', '2026-07-10 09:19:18');

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
(1, 1, 5, 1, 0, NULL);

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

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `purchase_id`, `lot_id`, `product_id`, `quantity`, `unit_price`, `amount`) VALUES
(30, 34, 1, 1, 1343.500, 14.94, 20069.63);

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
(34, 'PUR-20260710-0524', 'DN-001', 'INV-001', 2016, NULL, '2026-07-10', 1, 1, 3, 'Furaha', 1, 1343.5000, 1, 21588.8601, 29004633.50, 1445.2000, 2, 20069.6329, 29004633.4960, 20069.6329, 14611.5488, NULL, 3.5000, NULL, 14.9383, 'lme', 32800.0000, 3000.0000, 690.7600, 46.4815, 18.5926, 4702.2500, 'received', '', 2, '2026-07-10 21:51:46', '2026-07-10 21:58:05');

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
(30, 34, 5, 54.690000, '');

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

--
-- Dumping data for table `stock`
--

INSERT INTO `stock` (`id`, `warehouse_id`, `product_id`, `lot_id`, `uom_id`, `qty_purchased`, `qty_sold`, `qty_adjusted`, `avg_cost_per_kg_rwf`, `avg_cost_per_kg_usd`, `purchase_currency_id`, `purchase_amount_in_currency`, `exchange_rate`, `converted_amount`, `total_value_rwf`, `total_value_usd`, `last_updated`, `notes`, `opening`, `closing`, `last_rolled_over_at`) VALUES
(1, 1, 1, 1, 1, 1343.5000, 0.0000, -3.0000, 21588.8601, 14.9383, 2, 20069.6329, 1445.2000, 29004633.4960, 29004633.50, 20069.6329, '2026-07-10 22:35:15', NULL, 1343.5000, 1340.5000, '2026-07-11');

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

--
-- Dumping data for table `stock_movement`
--

INSERT INTO `stock_movement` (`id`, `movement_type`, `warehouse_id`, `product_id`, `lot_id`, `uom_id`, `qty_kg`, `unit_cost_rwf`, `unit_cost_usd`, `purchase_currency_id`, `purchase_amount_in_currency`, `exchange_rate`, `converted_amount`, `total_value_rwf`, `total_value_usd`, `reference_type`, `reference_id`, `movement_date`, `notes`, `created_by`, `created_at`, `opening`, `closing`) VALUES
(1, 'PURCHASE_IN', 1, 1, 1, 1, 1343.5000, 21588.8601, 14.9383, 2, 20069.6329, 1445.2000, 29004633.4960, 29004633.50, 20069.6329, 'purchasing', 34, '2026-07-10', '', 2, '2026-07-10 21:58:05', 0.0000, 1343.5000),
(6, 'ADJUSTMENT_OUT', 1, 1, 1, 1, 2.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-11', 'counting error', 2, '2026-07-10 22:22:26', 1343.5000, 1341.5000),
(7, 'ADJUSTMENT_IN', 1, 1, 1, 1, 2.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-11', 'counting error', 2, '2026-07-10 22:28:47', 1343.5000, 1343.5000),
(8, 'ADJUSTMENT_OUT', 1, 1, 1, 1, 3.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-11', 'counting error', 2, '2026-07-10 22:35:15', 1343.5000, 1340.5000);

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
(1, 'individual', 'Eugene ndayishimiye', '', NULL, '+250785750117', 'nendayishimye@gmail.com', 'Unnamed Road', 2014, NULL, NULL, 1, '', '2026-07-10 09:15:31', '2026-07-10 17:25:07', 2, NULL),
(2, 'individual', 'Mr Peter Umupapa mwiza', '', NULL, '+250789887655', 'mrpeter@gmail.com', 'musanze faraja', 2015, NULL, NULL, 1, '', '2026-07-10 09:16:12', '2026-07-10 17:25:02', 2, NULL),
(3, 'individual', 'Jean Nepo Supplier', '12345678', NULL, '+250789028963', 'ndahayoptr@gmail.com', 'KN 1 Rd, Kigali', 2016, NULL, NULL, 1, '', '2026-07-10 16:27:48', '2026-07-10 16:29:03', 2, 2);

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
  ADD UNIQUE KEY `uq_purchase_no` (`purchase_no`);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `bank_recon_items`
--
ALTER TABLE `bank_recon_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_statement_balances`
--
ALTER TABLE `bank_statement_balances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_counts`
--
ALTER TABLE `cash_counts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `currencies`
--
ALTER TABLE `currencies`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lots`
--
ALTER TABLE `lots`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `purchasing`
--
ALTER TABLE `purchasing`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `purchasing_element_grade`
--
ALTER TABLE `purchasing_element_grade`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stock_movement`
--
ALTER TABLE `stock_movement`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
