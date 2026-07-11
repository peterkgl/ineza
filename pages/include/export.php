<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';

$sheet = $_GET['sheet'] ?? '';
if (empty($sheet)) {
    die("Sheet parameter missing.");
}

// Filter parameters
$filter_year = $_GET['filter_year'] ?? '';
$filter_month = $_GET['filter_month'] ?? '';
$filter_date = $_GET['filter_date'] ?? '';
$filter_start = $_GET['filter_start'] ?? '';
$filter_end = $_GET['filter_end'] ?? '';

// Map slugs back to original sheet names (only the 11 kept reports)
$sheet_map = [
    'bank_recon_usd' => 'Bank Recon_$ EQUITY INEZA',
    'bank_recon_rwf' => 'RWF EQUITY - INEZA',
    'monthly_transactions' => 'Monthly Transactions',
    'cash_count_hq' => 'INEZA Cash Count HQ ',
    'petty_cash_rub' => 'PC INEZA RUB',
    'cash_count_rub' => 'INEZA CASH COUNT RUB',
    'purchase_logs_ta' => 'Purchase Logs_Ta',
    'accounts_payable' => 'Account Payable',
    'tin_summary' => 'Tin Summary',
    'ta_summary' => 'Ta Summary',
    'equity_report' => 'Bank Recon_EQUITY RWF - INEZA'
];

if (!isset($sheet_map[$sheet])) {
    die("Invalid sheet name.");
}

$original_sheet_name = $sheet_map[$sheet];

// ────────────────────────────────────────────────────────────────────────────
// Fetch and Prepare Data from Database
// ────────────────────────────────────────────────────────────────────────────

// Build date condition for SQL queries
$date_col = "purchase_date";
if ($sheet === 'petty_cash_rub' || $sheet === 'monthly_transactions') {
    $date_col = "entry_date";
} elseif ($sheet === 'bank_recon_usd' || $sheet === 'bank_recon_rwf') {
    $date_col = "entry_date";
} elseif ($sheet === 'cash_count_hq' || $sheet === 'cash_count_rub') {
    $date_col = "count_date";
}

$date_cond = "";
if (!empty($filter_date)) {
    $fd = mysqli_real_escape_string($conn, $filter_date);
    $date_cond = " AND DATE({$date_col}) = '{$fd}' ";
} elseif (!empty($filter_start) && !empty($filter_end)) {
    $fs = mysqli_real_escape_string($conn, $filter_start);
    $fe = mysqli_real_escape_string($conn, $filter_end);
    $date_cond = " AND {$date_col} BETWEEN '{$fs}' AND '{$fe}' ";
} elseif (!empty($filter_year) && !empty($filter_month)) {
    $fy = mysqli_real_escape_string($conn, $filter_year);
    $fm = mysqli_real_escape_string($conn, $filter_month);
    $date_cond = " AND YEAR({$date_col}) = '{$fy}' AND MONTH({$date_col}) = '{$fm}' ";
} elseif (!empty($filter_year)) {
    $fy = mysqli_real_escape_string($conn, $filter_year);
    $date_cond = " AND YEAR({$date_col}) = '{$fy}' ";
} elseif (!empty($filter_month)) {
    $fm = mysqli_real_escape_string($conn, $filter_month);
    $date_cond = " AND MONTH({$date_col}) = '{$fm}' ";
}

$rows_data = [];
$gl_balance = 0.0;
$stmt_balance = 0.0;
$recon_items = [];
$cash_counts = [];

// Equity Report variables
$opening_capital = 0.0;
$opening_retained = 0.0;
$closing_capital = 0.0;
$closing_retained = 0.0;
$profit = 0.0;
$capital_movement = 0.0;
$startDate = '';
$endDate = '';
$periodLabel = '';

if ($sheet === 'purchase_logs_ta') {
    $query = "SELECT p.*, s.name as supplier_name, l.lots_code, peg.grade_pct 
              FROM purchasing p
              JOIN suppliers s ON p.supplier_id = s.id
              JOIN lots l ON p.lot_id = l.id
              LEFT JOIN purchasing_element_grade peg ON peg.purchasing_id = p.id AND peg.product_element_id = 1
              WHERE p.product_id = 2 {$date_cond}
              ORDER BY p.purchase_date ASC, p.id ASC";
    $db_res = mysqli_query($conn, $query);
    $idx = 0;
    $total_qty = 0.0;
    if ($db_res) {
        while ($db_row = mysqli_fetch_assoc($db_res)) {
            $idx++;
            $qty = (float)$db_row['quantity_kg'];
            $total_qty += $qty;
            $grade = (float)($db_row['grade_pct'] ?? 0);
            $val_usd = (float)$db_row['purchase_value_usd'];
            
            $row_vals = array_fill(0, 26, '');
            $row_vals[0] = $idx;
            $row_vals[1] = $idx;
            $row_vals[2] = $db_row['purchase_date'];
            $row_vals[3] = $db_row['purchase_date'];
            $row_vals[4] = $db_row['delivery_no'] ?? '';
            $row_vals[5] = 'Batch 1';
            $row_vals[6] = $db_row['lots_code'];
            $row_vals[7] = $db_row['supplier_name'];
            $row_vals[8] = 'Province';
            $row_vals[9] = 'District';
            $row_vals[10] = $qty > 0 ? $qty : '';
            $row_vals[11] = $grade > 0 ? $grade : '';
            $row_vals[12] = (float)$db_row['price_per_kg_rwf'] > 0 ? (float)$db_row['price_per_kg_rwf'] : '';
            $row_vals[13] = (float)$db_row['purchase_value_rwf'] > 0 ? (float)$db_row['purchase_value_rwf'] : '';
            $row_vals[14] = (float)$db_row['exchange_rate'] > 0 ? (float)$db_row['exchange_rate'] : '';
            $row_vals[15] = $val_usd > 0 ? $val_usd : '';
            $row_vals[16] = (float)$db_row['net_paid_supplier_usd'] > 0 ? (float)$db_row['net_paid_supplier_usd'] : '';
            $row_vals[17] = (float)$db_row['charges_per_kg'] > 0 ? (float)$db_row['charges_per_kg'] : '';
            $row_vals[18] = $grade > 0 ? round($val_usd / ($qty * $grade), 2) : '';
            $row_vals[19] = $qty > 0 ? round($val_usd / $qty, 2) : '';
            $row_vals[20] = $grade > 0 ? $grade : '';
            $row_vals[21] = $grade > 0 ? $grade * 0.819 : '';
            $row_vals[22] = $grade > 0 ? $grade * 0.204 : '';
            $row_vals[23] = $grade > 0 ? $grade * 0.054 : '';
            $row_vals[24] = $grade > 0 ? $grade * 0.458 : '';
            $row_vals[25] = $total_qty;

            $rows_data[] = $row_vals;
        }
    }
} elseif ($sheet === 'petty_cash_rub') {
    $query = "SELECT je.entry_date as tx_date, je.description as je_desc,
                jel.debit as deposit, jel.credit as withdrawal, jel.description as details,
                offset_a.account_name as offsetting_account_name
              FROM journal_entry_lines jel
              JOIN journal_entries je ON jel.journal_entry_id = je.id
              JOIN accounts a ON jel.account_id = a.id
              LEFT JOIN journal_entry_lines offset_jel ON offset_jel.journal_entry_id = je.id AND offset_jel.id != jel.id
              LEFT JOIN accounts offset_a ON offset_jel.account_id = offset_a.id
              WHERE a.account_code = '1010-04' AND je.statuss = 'POSTED' {$date_cond}
              ORDER BY je.entry_date ASC, je.id ASC";
    $db_res = mysqli_query($conn, $query);
    $balance = 0.0;
    if ($db_res) {
        while ($db_row = mysqli_fetch_assoc($db_res)) {
            $deposit = (float)$db_row['deposit'];
            $withdrawal = (float)$db_row['withdrawal'];
            $cash_val = $deposit > 0 ? $deposit : -$withdrawal;
            $balance = round($balance + $cash_val, 2);
            $who = $db_row['je_desc'];
            if (preg_match('/To\/From:\s*(.*?)\s*\|\s*Details:/i', $db_row['je_desc'], $matches)) {
                $who = $matches[1];
            }
            
            $row_vals = array_fill(0, 21, '');
            $row_vals[0] = $db_row['tx_date'];
            $row_vals[1] = $db_row['tx_date'];
            $row_vals[2] = date('F', strtotime($db_row['tx_date']));
            $row_vals[3] = $withdrawal > 0 ? $withdrawal : '';
            $row_vals[4] = $deposit > 0 ? $deposit : '';
            $row_vals[5] = $who;
            $row_vals[6] = 'Ref';
            $row_vals[7] = 'Type';
            $row_vals[8] = 'Category';
            $row_vals[9] = 'Sub-Cat';
            $row_vals[10] = $db_row['details'];
            $row_vals[11] = 'Notes';
            $row_vals[12] = 'Project';
            $row_vals[13] = 'Location';
            $row_vals[14] = 'Approval';
            $row_vals[15] = $db_row['offsetting_account_name'];
            $row_vals[16] = 'Posted';
            $row_vals[17] = $balance;
            $row_vals[18] = 'Yes';
            $row_vals[19] = date('Y-m', strtotime($db_row['tx_date']));
            $row_vals[20] = 'OK';

            $rows_data[] = $row_vals;
        }
    }
} elseif ($sheet === 'accounts_payable') {
    $query = "SELECT s.name as supplier_name, l.lots_code,
                SUM(p.quantity_kg) as total_weight, SUM(p.purchase_value_usd) as total_amount,
                p.purchase_date as tx_date,
                (SELECT COALESCE(SUM(sa.amount), 0) FROM supplier_advances sa WHERE sa.supplier_id = s.id AND sa.advance_date = p.purchase_date) as total_advances
              FROM purchasing p 
              JOIN suppliers s ON p.supplier_id = s.id 
              JOIN lots l ON p.lot_id = l.id
              WHERE 1=1 {$date_cond} 
              GROUP BY s.id, l.id, p.purchase_date 
              ORDER BY p.purchase_date ASC, s.name ASC";
    $db_res = mysqli_query($conn, $query);
    $idx = 0;
    if ($db_res) {
        while ($db_row = mysqli_fetch_assoc($db_res)) {
            $idx++;
            $amount = (float)$db_row['total_amount'];
            $advances = (float)$db_row['total_advances'];
            
            $row_vals = array_fill(0, 11, '');
            $row_vals[0] = $idx;
            $row_vals[1] = $db_row['tx_date'];
            $row_vals[2] = $db_row['supplier_name'];
            $row_vals[3] = $db_row['lots_code'];
            $row_vals[4] = 'Mineral Lot';
            $row_vals[5] = (float)$db_row['total_weight'] > 0 ? (float)$db_row['total_weight'] : '';
            $row_vals[6] = $amount > 0 ? $amount : '';
            $row_vals[7] = $advances > 0 ? $advances : '';
            $row_vals[8] = ($amount - $advances) > 0 ? ($amount - $advances) : '';
            $row_vals[9] = 'Pending';
            $row_vals[10] = 'Notes';

            $rows_data[] = $row_vals;
        }
    }
} elseif ($sheet === 'tin_summary') {
    if (!function_exists('formatGradeExcel')) {
        // Excel percentage formats are stored as fraction (e.g. 0.5469 for 54.69%)
        function formatGradeExcel($val) {
            if (is_null($val) || $val === '') return '';
            $val = (float)$val;
            if ($val > 1.0) {
                $val = $val / 100;
            }
            return $val;
        }
    }

    // Query product elements dynamically based on graded elements in the filtered period
    $elements_query = "SELECT DISTINCT pe.id, pe.element_code, pe.element_name, pe.symbol, COALESCE(pec.display_order, 999) as display_order
                       FROM product_element pe
                       JOIN purchasing_element_grade peg ON pe.id = peg.product_element_id
                       JOIN purchasing p ON peg.purchasing_id = p.id
                       LEFT JOIN product_element_composition pec ON pe.id = pec.product_element_id AND pec.product_id = 1
                       WHERE p.product_id = 1 {$date_cond}
                       ORDER BY display_order ASC, pe.element_code ASC";
    $elements_res = mysqli_query($conn, $elements_query);
    if (!$elements_res || mysqli_num_rows($elements_res) === 0) {
        $elements_query = "SELECT DISTINCT pe.id, pe.element_code, pe.element_name, pe.symbol, COALESCE(pec.display_order, 999) as display_order
                           FROM product_element pe
                           LEFT JOIN product_element_composition pec ON pe.id = pec.product_element_id AND pec.product_id = 1
                           WHERE pec.product_id = 1
                           ORDER BY display_order ASC, pe.element_code ASC";
        $elements_res = mysqli_query($conn, $elements_query);
    }
    $elements = [];
    while ($elem = mysqli_fetch_assoc($elements_res)) {
        $elements[] = $elem;
    }

    // Fetch grades for mapping
    $grades_map = [];
    $all_grades_query = "SELECT peg.purchasing_id, peg.product_element_id, peg.grade_pct
                         FROM purchasing_element_grade peg
                         JOIN purchasing p ON peg.purchasing_id = p.id
                         WHERE p.product_id = 1 {$date_cond}";
    $all_grades_res = mysqli_query($conn, $all_grades_query);
    if ($all_grades_res) {
        while ($g_row = mysqli_fetch_assoc($all_grades_res)) {
            $grades_map[$g_row['purchasing_id']][$g_row['product_element_id']] = $g_row['grade_pct'];
        }
    }

    // Fetch average grades for total row
    $avg_grades_query = "SELECT pe.id, pe.element_name, pe.element_code, AVG(peg.grade_pct) as avg_pct
                         FROM purchasing_element_grade peg
                         JOIN product_element pe ON peg.product_element_id = pe.id
                         JOIN purchasing p ON peg.purchasing_id = p.id
                         WHERE p.product_id = 1 {$date_cond}
                         GROUP BY pe.id, pe.element_name, pe.element_code";
    $avg_grades_res = mysqli_query($conn, $avg_grades_query);
    $avg_grades = [];
    if ($avg_grades_res) {
        while ($ag = mysqli_fetch_assoc($avg_grades_res)) {
            $avg_grades[(int)$ag['id']] = $ag;
        }
    }

    $query = "SELECT p.id, p.purchase_date as tx_date, p.negociant, s.name as supplier_name, p.purchase_no,
                p.quantity_kg as total_weight,
                p.lme_price as avg_lme
              FROM purchasing p 
              JOIN suppliers s ON p.supplier_id = s.id
              WHERE p.product_id = 1 {$date_cond}
              ORDER BY p.purchase_date ASC, s.name ASC";
    $db_res = mysqli_query($conn, $query);
    $idx = 0;
    $total_w = 0.0;
    if ($db_res) {
        while ($db_row = mysqli_fetch_assoc($db_res)) {
            $idx++;
            $weight = (float)$db_row['total_weight'];
            $total_w += $weight;
            
            $row_vals = [];
            $row_vals[0] = '';
            $row_vals[1] = $db_row['tx_date'];
            $row_vals[2] = $db_row['negociant'] ?? '';
            $row_vals[3] = $db_row['purchase_no'];
            $row_vals[4] = $weight > 0 ? $weight : '';
            
            $col_idx = 5;
            foreach ($elements as $elem) {
                $elem_id = (int)$elem['id'];
                $grade_val = isset($grades_map[$db_row['id']][$elem_id]) ? $grades_map[$db_row['id']][$elem_id] : null;
                $row_vals[$col_idx] = formatGradeExcel($grade_val);
                $col_idx++;
            }
            
            $row_vals[$col_idx] = (float)$db_row['avg_lme'] > 0 ? (float)$db_row['avg_lme'] : '';
            $col_idx++;

            while (count($row_vals) < 10) {
                $row_vals[] = '';
            }

            $rows_data[] = $row_vals;
        }
    }

    if ($idx > 0) {
        $total_row = [];
        $total_row[0] = '';
        $total_row[1] = '';
        $total_row[2] = 'Under processing:';
        $total_row[3] = '';
        $total_row[4] = $total_w > 0 ? $total_w : '';
        
        $col_idx = 5;
        foreach ($elements as $elem) {
            $elem_id = (int)$elem['id'];
            $avg_val = isset($avg_grades[$elem_id]) ? $avg_grades[$elem_id]['avg_pct'] : null;
            $total_row[$col_idx] = formatGradeExcel($avg_val);
            $col_idx++;
        }
        $total_row[$col_idx] = '';
        $col_idx++;
        
        while (count($total_row) < 10) {
            $total_row[] = '';
        }
        $rows_data[] = $total_row;
    }
} elseif ($sheet === 'ta_summary') {
    if (!function_exists('formatGradeExcel')) {
        // Excel percentage formats are stored as fraction (e.g. 0.5469 for 54.69%)
        function formatGradeExcel($val) {
            if (is_null($val) || $val === '') return '';
            $val = (float)$val;
            if ($val > 1.0) {
                $val = $val / 100;
            }
            return $val;
        }
    }

    // Query active/used product elements dynamically for Tantalum (product_id = 2)
    $elements_query = "SELECT DISTINCT pe.id, pe.element_code, pe.element_name, pe.symbol, COALESCE(pec.display_order, 999) as display_order
                       FROM product_element pe
                       JOIN purchasing_element_grade peg ON pe.id = peg.product_element_id
                       JOIN purchasing p ON peg.purchasing_id = p.id
                       LEFT JOIN product_element_composition pec ON pe.id = pec.product_element_id AND pec.product_id = 2
                       WHERE p.product_id = 2 {$date_cond}
                       ORDER BY display_order ASC, pe.element_code ASC";
    $elements_res = mysqli_query($conn, $elements_query);
    if (!$elements_res || mysqli_num_rows($elements_res) === 0) {
        $elements_query = "SELECT DISTINCT pe.id, pe.element_code, pe.element_name, pe.symbol, COALESCE(pec.display_order, 999) as display_order
                           FROM product_element pe
                           LEFT JOIN product_element_composition pec ON pe.id = pec.product_element_id AND pec.product_id = 2
                           WHERE pec.product_id = 2
                           ORDER BY display_order ASC, pe.element_code ASC";
        $elements_res = mysqli_query($conn, $elements_query);
    }
    $elements = [];
    while ($elem = mysqli_fetch_assoc($elements_res)) {
        $elements[] = $elem;
    }

    // Fetch grades for mapping
    $grades_map = [];
    $all_grades_query = "SELECT peg.purchasing_id, peg.product_element_id, peg.grade_pct
                         FROM purchasing_element_grade peg
                         JOIN purchasing p ON peg.purchasing_id = p.id
                         WHERE p.product_id = 2 {$date_cond}";
    $all_grades_res = mysqli_query($conn, $all_grades_query);
    if ($all_grades_res) {
        while ($g_row = mysqli_fetch_assoc($all_grades_res)) {
            $grades_map[$g_row['purchasing_id']][$g_row['product_element_id']] = $g_row['grade_pct'];
        }
    }

    // Fetch average grades for total row
    $avg_grades_query = "SELECT pe.id, pe.element_name, pe.element_code, AVG(peg.grade_pct) as avg_pct
                         FROM purchasing_element_grade peg
                         JOIN product_element pe ON peg.product_element_id = pe.id
                         JOIN purchasing p ON peg.purchasing_id = p.id
                         WHERE p.product_id = 2 {$date_cond}
                         GROUP BY pe.id, pe.element_name, pe.element_code";
    $avg_grades_res = mysqli_query($conn, $avg_grades_query);
    $avg_grades = [];
    if ($avg_grades_res) {
        while ($ag = mysqli_fetch_assoc($avg_grades_res)) {
            $avg_grades[(int)$ag['id']] = $ag;
        }
    }

    $query = "SELECT p.id, p.purchase_date as tx_date, p.negociant, s.name as supplier_name, p.purchase_no,
                p.quantity_kg as total_weight,
                p.lme_price as avg_lme
              FROM purchasing p 
              JOIN suppliers s ON p.supplier_id = s.id
              WHERE p.product_id = 2 {$date_cond}
              ORDER BY p.purchase_date ASC, s.name ASC";
    $db_res = mysqli_query($conn, $query);
    $idx = 0;
    $total_w = 0.0;
    if ($db_res) {
        while ($db_row = mysqli_fetch_assoc($db_res)) {
            $idx++;
            $weight = (float)$db_row['total_weight'];
            $total_w += $weight;
            
            $row_vals = [];
            $row_vals[0] = '';
            $row_vals[1] = $db_row['tx_date'];
            $row_vals[2] = $db_row['negociant'] ?? '';
            $row_vals[3] = $db_row['purchase_no'];
            $row_vals[4] = $weight > 0 ? $weight : '';
            
            $col_idx = 5;
            foreach ($elements as $elem) {
                $elem_id = (int)$elem['id'];
                $grade_val = isset($grades_map[$db_row['id']][$elem_id]) ? $grades_map[$db_row['id']][$elem_id] : null;
                $row_vals[$col_idx] = formatGradeExcel($grade_val);
                $col_idx++;
            }
            
            $row_vals[$col_idx] = (float)$db_row['avg_lme'] > 0 ? (float)$db_row['avg_lme'] : '';
            $col_idx++;

            while (count($row_vals) < 10) {
                $row_vals[] = '';
            }

            $rows_data[] = $row_vals;
        }
    }

    if ($idx > 0) {
        $total_row = [];
        $total_row[0] = '';
        $total_row[1] = '';
        $total_row[2] = 'Under processing:';
        $total_row[3] = '';
        $total_row[4] = $total_w > 0 ? $total_w : '';
        
        $col_idx = 5;
        foreach ($elements as $elem) {
            $elem_id = (int)$elem['id'];
            $avg_val = isset($avg_grades[$elem_id]) ? $avg_grades[$elem_id]['avg_pct'] : null;
            $total_row[$col_idx] = formatGradeExcel($avg_val);
            $col_idx++;
        }
        $total_row[$col_idx] = '';
        $col_idx++;
        
        while (count($total_row) < 10) {
            $total_row[] = '';
        }
        $rows_data[] = $total_row;
    }
} elseif ($sheet === 'monthly_transactions') {
    $excel_accounts = [
        4 => ['active' => 'N', 'name' => 'EQUITY - INEZA AFRICAN MINING USD'],
        5 => ['active' => 'N', 'name' => 'EQUITY - INEZA AFRICAN MINING RWF'],
        6 => ['active' => 'N', 'name' => 'EQUITY - INEZA AFRICAN MINING EURO'],
        7 => ['active' => 'N', 'name' => 'Accounts Receivables'],
        8 => ['active' => 'N', 'name' => 'Accounts Receivables - '],
        9 => ['active' => 'N', 'name' => 'Accounts Receivables - '],
        10 => ['active' => 'N', 'name' => 'Accounts Receivable - Others'],
        11 => ['active' => 'N', 'name' => 'Advances - Employees'],
        12 => ['active' => 'N', 'name' => 'Advances to Star Metal'],
        13 => ['active' => 'Y', 'name' => 'Advances to O/E - Pierre GATAMA'],
        14 => ['active' => 'N', 'name' => 'Advances to O/E - MUVUNYI DIEDONNE'],
        15 => ['active' => 'N', 'name' => 'Advances to O/E - Andrew KAYITARE'],
        16 => ['active' => 'N', 'name' => 'Advances to O/E - Daniel Makasi'],
        17 => ['active' => 'Y', 'name' => 'Advances to O/E - Charles MUNYANEZA'],
        18 => ['active' => 'N', 'name' => 'Advances to O/E - Olivier '],
        19 => ['active' => 'N', 'name' => 'Advances to O/E - Yury Ilin'],
        20 => ['active' => 'N', 'name' => 'Advances to O/E - Nsana Jean'],
        21 => ['active' => 'N', 'name' => 'Advances to O/E - MUSABYEYEZU Justine'],
        22 => ['active' => 'Y', 'name' => 'Advances to O/E - GEDEON'],
        24 => ['active' => 'N', 'name' => 'Advances - Suppliers'],
        25 => ['active' => 'N', 'name' => 'Advances to O/E - Murindwa Andre'],
        26 => ['active' => 'N', 'name' => 'Advances to O/E - Darius BIMENYIMANA'],
        27 => ['active' => 'N', 'name' => 'Advances to O/E - Murego Paulin'],
        28 => ['active' => 'N', 'name' => 'Advances to O/E - Jean Bosco YAMFASHIJE'],
        29 => ['active' => 'N', 'name' => 'Advances to O/E - Fidele BIZIMANA'],
        30 => ['active' => 'N', 'name' => 'Advances to O/E - Jeanne MUKAMUDENGE'],
        31 => ['active' => 'N', 'name' => 'Advances to O/E - Fanny MUKAMUGEMA'],
        32 => ['active' => 'N', 'name' => 'Advances to O/E - Richard AKAYEZU'],
        33 => ['active' => 'N', 'name' => 'Advances to O/E - Marc NSHIMYUMUREMYI'],
        34 => ['active' => 'N', 'name' => 'Advances to O/E - AHADI Furaha'],
        35 => ['active' => 'N', 'name' => 'Advances to O/E - Athanase MBARUBUKEYE'],
        36 => ['active' => 'N', 'name' => 'Advances to O/E - Michel NSHIMIYIMANA'],
        37 => ['active' => 'N', 'name' => 'Advances to O/E - Maman Innocent'],
        38 => ['active' => 'N', 'name' => 'Advances to O/E - Maestro HABUMUGISHA'],
        39 => ['active' => 'N', 'name' => 'Advances to O/E - Bertin RUTABINGWA'],
        40 => ['active' => 'N', 'name' => 'Advances - Cooperatives'],
        41 => ['active' => 'N', 'name' => 'Advances - Others'],
        42 => ['active' => 'N', 'name' => 'Prepayments'],
        43 => ['active' => 'N', 'name' => 'Prepaid Rent'],
        44 => ['active' => 'Y', 'name' => 'Rental Deposits'],
        45 => ['active' => 'Y', 'name' => 'Stocks - Tin'],
        46 => ['active' => 'N', 'name' => 'Stocks - Coltan'],
        47 => ['active' => 'N', 'name' => 'Stocks - Others'],
        48 => ['active' => 'N', 'name' => 'Asset Under Construction - '],
        50 => ['active' => 'N', 'name' => 'Due from EQUITY - INEZA AFRICAN MINING USD'],
        51 => ['active' => 'Y', 'name' => 'Due from EQUITY - INEZA AFRICAN MINING RWF'],
        52 => ['active' => 'N', 'name' => 'Due from EQUITY - INEZA AFRICAN MINING EURO'],
        53 => ['active' => 'N', 'name' => 'Land & Buildings - @ Cost'],
        54 => ['active' => 'N', 'name' => 'Land & Buildings - Accum Depre'],
        55 => ['active' => 'N', 'name' => 'Motor Vehicles - @ Cost'],
        56 => ['active' => 'N', 'name' => 'Motor Vehicles - Accum Depre'],
        57 => ['active' => 'N', 'name' => 'Computer Equipment - @ Cost'],
        58 => ['active' => 'N', 'name' => 'Computer Equipment - Accum Depre'],
        59 => ['active' => 'N', 'name' => 'Office Equipment - @ Cost'],
        60 => ['active' => 'N', 'name' => 'Office Equipment - Accum Depre'],
        61 => ['active' => 'N', 'name' => 'Furniture & Fittings - @ Cost'],
        62 => ['active' => 'N', 'name' => 'Furniture & Fittings - Accum Depre'],
        63 => ['active' => 'N', 'name' => 'Other Fixed Assets - @ Cost'],
        64 => ['active' => 'N', 'name' => 'Other Fixed Assets - Accum Depre'],
        65 => ['active' => 'N', 'name' => 'Leasehold Improvements'],
        66 => ['active' => 'N', 'name' => 'Leasehold Improvements - Amortization'],
        67 => ['active' => 'Y', 'name' => 'Mineral Processing Equipment - @ Cost'],
        68 => ['active' => 'N', 'name' => 'Mineral Processing Equipment - Accum Depre'],
        69 => ['active' => 'N', 'name' => 'Goodwill / Intangible Assets'],
        70 => ['active' => 'Y', 'name' => 'Investments'],
        72 => ['active' => 'N', 'name' => 'Accounts Payable'],
        73 => ['active' => 'N', 'name' => 'Accounts Payable - Others'],
        74 => ['active' => 'N', 'name' => 'Accrued Liabilities'],
        75 => ['active' => 'N', 'name' => 'Advances from METALEKSPO SIA'],
        76 => ['active' => 'Y', 'name' => 'Advances from Star Metal Company'],
        77 => ['active' => 'N', 'name' => 'Advances from MUREGO AND GATAMA'],
        78 => ['active' => 'N', 'name' => 'Advances from Partner'],
        79 => ['active' => 'N', 'name' => 'Salaries Payable'],
        80 => ['active' => 'N', 'name' => 'Consultancy Fee Payable'],
        81 => ['active' => 'N', 'name' => 'Rent Payable'],
        82 => ['active' => 'N', 'name' => 'Commission Payable'],
        83 => ['active' => 'N', 'name' => 'Severance Payable'],
        84 => ['active' => 'N', 'name' => 'Payroll Tax Payable'],
        85 => ['active' => 'N', 'name' => 'InCome Tax Payable'],
        86 => ['active' => 'N', 'name' => 'Long Term Liabilities'],
        87 => ['active' => 'N', 'name' => 'Investment from Star Metal'],
        88 => ['active' => 'N', 'name' => 'Investment from XY'],
        89 => ['active' => 'N', 'name' => 'Common shares'],
        90 => ['active' => 'N', 'name' => 'Loans Payable - '],
        91 => ['active' => 'Y', 'name' => 'Due to EQUITY - INEZA AFRICAN MINING USD'],
        92 => ['active' => 'N', 'name' => 'Due to EQUITY - INEZA AFRICAN MINING RWF'],
        93 => ['active' => 'N', 'name' => 'Due to EQUITY - INEZA AFRICAN MINING EURO'],
        94 => ['active' => 'N', 'name' => 'Member\'s Equity'],
        95 => ['active' => 'N', 'name' => 'Retained Income / (Accumulated Loss)'],
        96 => ['active' => 'N', 'name' => 'Prior Period Adjustment'],
        98 => ['active' => 'N', 'name' => 'Sales'],
        100 => ['active' => 'N', 'name' => 'Export Costs'],
        101 => ['active' => 'N', 'name' => 'Export Packaging'],
        102 => ['active' => 'N', 'name' => 'Export Taxes'],
        103 => ['active' => 'N', 'name' => 'Cost of Sales - Minerals Transport, Taxes & Tags'],
        104 => ['active' => 'N', 'name' => 'Cost of Sales - Travel & Transport'],
        105 => ['active' => 'N', 'name' => 'Cost of Sales - Salaries & Wages'],
        106 => ['active' => 'N', 'name' => 'Cost of Sales - Others'],
        107 => ['active' => 'N', 'name' => 'Sample Costs'],
        108 => ['active' => 'N', 'name' => 'Cooperative Fees'],
        110 => ['active' => 'N', 'name' => 'Advertising & Promotions'],
        111 => ['active' => 'N', 'name' => 'Amortization'],
        112 => ['active' => 'N', 'name' => 'Bad Debts'],
        113 => ['active' => 'Y', 'name' => 'Bank Charges'],
        114 => ['active' => 'N', 'name' => 'Cleaning & Hygiene'],
        115 => ['active' => 'N', 'name' => 'Computer Supplies & Others'],
        116 => ['active' => 'N', 'name' => 'Consulting Fees'],
        117 => ['active' => 'N', 'name' => 'Courier & Postage'],
        118 => ['active' => 'N', 'name' => 'Commission fees'],
        119 => ['active' => 'N', 'name' => 'Custom Duties & Taxes'],
        120 => ['active' => 'N', 'name' => 'Depreciation'],
        121 => ['active' => 'N', 'name' => 'Donations'],
        122 => ['active' => 'Y', 'name' => 'Electricity & Water'],
        123 => ['active' => 'N', 'name' => 'Insurance Expense'],
        124 => ['active' => 'N', 'name' => 'Legal Fees'],
        125 => ['active' => 'N', 'name' => 'Machinery Hire'],
        126 => ['active' => 'N', 'name' => 'Exploration and Evaluation Cost'],
        127 => ['active' => 'N', 'name' => 'Medical Costs'],
        128 => ['active' => 'N', 'name' => 'Membership Fees & Dues'],
        129 => ['active' => 'Y', 'name' => 'Motor Vehicle Expenses'],
        130 => ['active' => 'N', 'name' => 'Office Supplies'],
        131 => ['active' => 'N', 'name' => 'Other Consumables'],
        132 => ['active' => 'N', 'name' => 'Pft/Loss on Foreign Exchange'],
        133 => ['active' => 'N', 'name' => 'Professional Fees'],
        134 => ['active' => 'Y', 'name' => 'Rent Expense'],
        135 => ['active' => 'N', 'name' => 'Rent Site'],
        136 => ['active' => 'Y', 'name' => 'Repairs & Maintenance'],
        137 => ['active' => 'N', 'name' => 'Representation & Entertainment'],
        138 => ['active' => 'N', 'name' => 'Safety'],
        139 => ['active' => 'N', 'name' => 'Salaries'],
        140 => ['active' => 'Y', 'name' => 'Salary - Others'],
        141 => ['active' => 'N', 'name' => 'Software License'],
        142 => ['active' => 'N', 'name' => 'Management Fee'],
        143 => ['active' => 'N', 'name' => 'Security'],
        144 => ['active' => 'N', 'name' => 'Staff Training'],
        145 => ['active' => 'Y', 'name' => 'Staff Welfare'],
        146 => ['active' => 'N', 'name' => 'Staff Welfare Site'],
        147 => ['active' => 'N', 'name' => 'Taxes, Permits & Licenses'],
        148 => ['active' => 'N', 'name' => 'Concession License Fee'],
        149 => ['active' => 'N', 'name' => 'Telecommunications'],
        150 => ['active' => 'Y', 'name' => 'Transport'],
        151 => ['active' => 'Y', 'name' => 'Travel & Accommodation'],
        152 => ['active' => 'N', 'name' => 'Visa Fees'],
        153 => ['active' => 'N', 'name' => 'Sundry Account'],
        154 => ['active' => 'Y', 'name' => 'Miscellaneous Expense'],
        156 => ['active' => 'N', 'name' => 'Interest Income'],
        157 => ['active' => 'N', 'name' => 'Interest Expense'],
        158 => ['active' => 'N', 'name' => 'Bad Debts Recovered'],
        159 => ['active' => 'N', 'name' => 'Pft/Loss on Sale of Non Current Assets'],
        160 => ['active' => 'N', 'name' => 'Other Income'],
        161 => ['active' => 'N', 'name' => 'Other Loss'],
        162 => ['active' => 'N', 'name' => 'Provision for Income Tax'],
        164 => ['active' => 'Y', 'name' => 'Total Cash-Out Net of Cash-In'],
        168 => ['active' => 'Y', 'name' => 'Petty Cash Fund - INEZA'],
        169 => ['active' => 'N', 'name' => 'Funds to Sites - Rubaya'],
        171 => ['active' => '', 'name' => 'Equity $ Withdrawals'],
        172 => ['active' => '', 'name' => 'Petty Cash Payments'],
        174 => ['active' => '', 'name' => 'Monthly Exp -2025'],
        175 => ['active' => '', 'name' => 'Monthly Exp -2026'],
        176 => ['active' => '', 'name' => 'Petty Cash Fund - 2025'],
        177 => ['active' => '', 'name' => 'Petty Cash Fund - 2026'],
        178 => ['active' => '', 'name' => 'Equity $ Deposits'],
    ];

    $translation = [
        'Stocks - Tin' => 'Stocks - Tin',
        'Stocks - Coltan' => 'Stocks - Coltan',
        'Stocks - Tantalum' => 'Stocks - Tin',
        'Sales - Tin' => 'Sales',
        'Sales - Coltan' => 'Sales',
        'Sales - Tantalum' => 'Sales',
        'Trade Receivables' => 'Accounts Receivables',
        'Long Term Loans' => 'Long Term Liabilities',
        'Eugene ndayishimiye - Accounts Payable' => 'Accounts Payable',
        'EQUITY US$ ACCOUNT' => 'EQUITY - INEZA AFRICAN MINING USD',
        'Investments' => 'Investments',
        'Bank Charges' => 'Bank Charges',
        'Petty Cash Fund - INEZA' => 'Petty Cash Fund - INEZA',
        'Due from EQUITY - INEZA AFRICAN MINING RWF' => 'Due from EQUITY - INEZA AFRICAN MINING RWF',
        'Advances from Star Metal Company' => 'Advances from Star Metal Company',
        'Funds to Sites - Rubaya' => 'Funds to Sites - Rubaya',
        'Advances to O/E - Charles MUNYANEZA' => 'Advances to O/E - Charles MUNYANEZA',
        'Travel & Accommodation' => 'Travel & Accommodation',
        'Staff Welfare' => 'Staff Welfare',
        'Transport' => 'Transport',
        'Miscellaneous Expense' => 'Miscellaneous Expense',
        'Advances to O/E - GEDEON' => 'Advances to O/E - GEDEON'
    ];

    $month_cols = ['2025-05', '2025-06', '2025-07', '2025-08', '2025-09', '2025-10', '2025-11', '2025-12'];

    $matrix = [];
    foreach ($excel_accounts as $rowId => $acc) {
        $matrix[$rowId] = [
            'active' => $acc['active'],
            'name' => $acc['name'],
            'months' => array_fill_keys($month_cols, 0.0),
            'total' => 0.0
        ];
    }

    $name_to_row_ids = [];
    foreach ($excel_accounts as $rowId => $acc) {
        $name_to_row_ids[strtolower(trim($acc['name']))][] = $rowId;
    }

    // Query details
    $q = "SELECT DATE_FORMAT(je.entry_date, '%Y-%m') as tx_month,
                 a.account_name,
                 SUM(jel.debit) as total_debit, SUM(jel.credit) as total_credit
          FROM journal_entry_lines jel
          JOIN journal_entries je ON jel.journal_entry_id = je.id
          JOIN accounts a ON jel.account_id = a.id
          WHERE je.statuss = 'POSTED' {$date_cond}
          GROUP BY tx_month, a.id";
    $db_res = mysqli_query($conn, $q);
    if ($db_res) {
        while ($db_row = mysqli_fetch_assoc($db_res)) {
            $db_name = $db_row['account_name'];
            $m = $db_row['tx_month'];
            $debit = (float)$db_row['total_debit'];
            $credit = (float)$db_row['total_credit'];
            $net = $debit - $credit;
            
            $target_name = $translation[$db_name] ?? $db_name;
            $key = strtolower(trim($target_name));
            
            if (isset($name_to_row_ids[$key])) {
                foreach ($name_to_row_ids[$key] as $rowId) {
                    if (isset($matrix[$rowId]['months'][$m])) {
                        $matrix[$rowId]['months'][$m] += $net;
                    }
                }
            }
        }
    }

    foreach ($matrix as $rowId => $row_data) {
        $row_total = 0.0;
        foreach ($row_data['months'] as $m => $val) {
            $row_total += $val;
        }
        
        $row_vals = array_fill(0, 16, '');
        $row_vals[0] = $row_data['active'];
        $row_vals[1] = $row_data['name'];
        $row_vals[2] = '';
        foreach ($month_cols as $idx => $m) {
            $row_vals[3 + $idx] = $row_data['months'][$m] != 0 ? $row_data['months'][$m] : 0.0;
        }
        $row_vals[11] = $row_total;
        $row_vals[12] = '';
        $row_vals[13] = $row_total; // Grand total
        
        $rows_data[] = $row_vals;
    }
} elseif ($sheet === 'bank_recon_usd' || $sheet === 'bank_recon_rwf') {
    $report_code = ($sheet === 'bank_recon_usd') ? 'bank_recon_usd' : 'bank_recon_rwf';
    $account_code = ($sheet === 'bank_recon_usd') ? '1010-01' : '1010-03';

    // Get reconciliation target date
    $target_date = $filter_date;
    if (empty($target_date)) {
        $latest_res = mysqli_query($conn, "SELECT MAX(as_of_date) as max_date FROM bank_statement_balances WHERE report_slug = '{$report_code}'");
        if ($latest_res && $latest_row = mysqli_fetch_assoc($latest_res)) {
            $target_date = $latest_row['max_date'] ?? date('Y-m-d');
        } else {
            $target_date = date('Y-m-d');
        }
    }

    // Get the bank balance from GL
    $query = "SELECT 
                COALESCE(SUM(jel.debit), 0) - COALESCE(SUM(jel.credit), 0) as gl_balance
              FROM journal_entry_lines jel
              JOIN journal_entries je ON jel.journal_entry_id = je.id
              JOIN accounts a ON jel.account_id = a.id
              WHERE a.account_code = '{$account_code}' AND je.statuss = 'POSTED' AND je.entry_date <= '{$target_date}'";
    $gl_res = mysqli_query($conn, $query);
    $gl_row = mysqli_fetch_assoc($gl_res);
    $gl_balance = (float)($gl_row['gl_balance'] ?? 0);

    // Get statement balance
    $stmt_query = "SELECT balance FROM bank_statement_balances WHERE report_slug = '{$report_code}' AND as_of_date = '{$target_date}' LIMIT 1";
    $stmt_res = mysqli_query($conn, $stmt_query);
    $stmt_row = $stmt_res ? mysqli_fetch_assoc($stmt_res) : null;
    $stmt_balance = $stmt_row ? (float)$stmt_row['balance'] : 0;

    // Get reconciling items
    $recon_query = "SELECT item_type, description, amount FROM bank_recon_items WHERE report_slug = '{$report_code}' AND as_of_date = '{$target_date}' ORDER BY id ASC";
    $recon_res = mysqli_query($conn, $recon_query);
    if ($recon_res) {
        while ($ri = mysqli_fetch_assoc($recon_res)) {
            $recon_items[] = $ri;
        }
    }
} elseif ($sheet === 'cash_count_hq' || $sheet === 'cash_count_rub') {
    $report_code = ($sheet === 'cash_count_hq') ? 'cash_count_hq' : 'cash_count_rub';
    $report_code_esc = mysqli_real_escape_string($conn, $report_code);

    // Resolve target date
    $cc_target_date = '';
    if (!empty($filter_date)) {
        $cc_target_date = mysqli_real_escape_string($conn, $filter_date);
    } elseif (!empty($filter_start)) {
        $cc_target_date = mysqli_real_escape_string($conn, $filter_start);
    } else {
        // Use the latest date recorded for this report
        $latest_res = mysqli_query($conn, "SELECT MAX(count_date) as max_date FROM cash_counts WHERE report_slug = '{$report_code_esc}'");
        if ($latest_res && $latest_row = mysqli_fetch_assoc($latest_res)) {
            $cc_target_date = $latest_row['max_date'] ?? date('Y-m-d');
        } else {
            $cc_target_date = date('Y-m-d');
        }
    }

    // Fetch petty counts for this site only
    $petty_counts = [];
    $cc_res = mysqli_query($conn, "SELECT denomination, currency, quantity FROM cash_counts WHERE report_slug = '{$report_code_esc}' AND count_date = '{$cc_target_date}'");
    if ($cc_res) {
        while ($cc_row = mysqli_fetch_assoc($cc_res)) {
            $petty_counts[] = [
                'currency'     => $cc_row['currency'],
                'denomination' => (float)$cc_row['denomination'],
                'quantity'     => (int)$cc_row['quantity'],
                'total'        => (float)$cc_row['denomination'] * (int)$cc_row['quantity']
            ];
        }
    }

    // Fetch consolidated counts across ALL sites for the same date
    $con_raw = [];
    $cc_res2 = mysqli_query($conn, "SELECT denomination, currency, SUM(quantity) as total_qty FROM cash_counts WHERE count_date = '{$cc_target_date}' GROUP BY denomination, currency");
    if ($cc_res2) {
        while ($cc_row2 = mysqli_fetch_assoc($cc_res2)) {
            $denom = (float)$cc_row2['denomination'];
            $qty   = (int)$cc_row2['total_qty'];
            $con_raw[] = [
                'currency'     => $cc_row2['currency'],
                'denomination' => $denom,
                'quantity'     => $qty,
                'total'        => $denom * $qty
            ];
        }
    }

    // Keep backward-compat variable used in handleCashCountReport call
    $cash_counts = $petty_counts;
} elseif ($sheet === 'equity_report') {
    // Determine active period
    // Load available years
    $yearsQuery = "SELECT DISTINCT YEAR(entry_date) as yr FROM journal_entries WHERE statuss = 'POSTED' ORDER BY yr DESC";
    $yearsRes = mysqli_query($conn, $yearsQuery);
    $availableYears = [];
    if ($yearsRes) {
        while ($row = mysqli_fetch_assoc($yearsRes)) {
            $availableYears[] = (int)$row['yr'];
        }
    }
    $defaultYear = !empty($availableYears) ? $availableYears[0] : 2022;

    if (!empty($filter_start) && !empty($filter_end)) {
        $startDate = $filter_start;
        $endDate = $filter_end;
        $periodLabel = "For the period from " . date('j F Y', strtotime($startDate)) . " to " . date('j F Y', strtotime($endDate));
    } elseif (!empty($filter_date)) {
        $startDate = $filter_date;
        $endDate = $filter_date;
        $periodLabel = "For the day " . date('j F Y', strtotime($startDate));
    } elseif (!empty($filter_year) && !empty($filter_month)) {
        $startDate = "{$filter_year}-" . sprintf('%02d', $filter_month) . "-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        $periodLabel = "For the month ended " . date('F Y', strtotime($startDate));
    } elseif (!empty($filter_year)) {
        $startDate = "{$filter_year}-01-01";
        $endDate = "{$filter_year}-12-31";
        $periodLabel = "For the year ended 31 December {$filter_year}";
    } else {
        $startDate = "{$defaultYear}-01-01";
        $endDate = "{$defaultYear}-12-31";
        $periodLabel = "For the year ended 31 December {$defaultYear}";
    }
}

// Custom labels and date replacements are disabled
$labels = [];
$dates = [];

// ────────────────────────────────────────────────────────────────────────────
// Generate Output Excel using ZipArchive and DOMDocument
// ────────────────────────────────────────────────────────────────────────────

$temp_dir = sys_get_temp_dir();
$filename = "Report_" . preg_replace('/[^A-Za-z0-9_\-]/', '_', $sheet) . "_" . date('Ymd_His') . ".xlsx";
$output_path = $temp_dir . DIRECTORY_SEPARATOR . $filename;

// Copy template workbook to the output path
$template_path = __DIR__ . '/../../ineza_mining.xlsx';
if (!file_exists($template_path)) {
    die("Template file not found at " . $template_path);
}
if (!copy($template_path, $output_path)) {
    die("Failed to copy template file.");
}

// Open the copied zip file
$zip = new ZipArchive();
if ($zip->open($output_path) !== TRUE) {
    die("Failed to open copied Excel file as ZIP.");
}

// 1. Load relationships to map sheet IDs to worksheet XML files dynamically
$rels_xml = $zip->getFromName('xl/_rels/workbook.xml.rels');
if (!$rels_xml) {
    die("Failed to load workbook relationships.");
}
$rels_dom = new DOMDocument();
@$rels_dom->loadXML($rels_xml);
$rel_map = [];
foreach ($rels_dom->getElementsByTagName('Relationship') as $rel) {
    $rId = $rel->getAttribute('Id');
    $target = $rel->getAttribute('Target');
    $rel_map[$rId] = $target;
}

// 2. Load workbook to map sheet names to sheet IDs dynamically
$wb_xml = $zip->getFromName('xl/workbook.xml');
if (!$wb_xml) {
    die("Failed to load workbook structure.");
}
$wb_dom = new DOMDocument();
@$wb_dom->loadXML($wb_xml);
$all_sheets_discovered = [];
foreach ($wb_dom->getElementsByTagName('sheet') as $sheet_node) {
    $s_name = $sheet_node->getAttribute('name');
    $rId = $sheet_node->getAttribute('r:id');
    if (empty($rId)) {
        $rId = $sheet_node->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
    }
    if (empty($rId)) {
        foreach ($sheet_node->attributes as $attr) {
            if ($attr->localName === 'id' && strpos($attr->namespaceURI, '/relationships') !== false) {
                $rId = $attr->value;
                break;
            }
        }
    }
    if ($rId && isset($rel_map[$rId])) {
        $all_sheets_discovered[$rId] = [
            'name' => $s_name,
            'file' => $rel_map[$rId]
        ];
    }
}

// Find the target sheet's ID and filename
$target_rId = null;
$target_sheet_file = null;
foreach ($all_sheets_discovered as $rId => $info) {
    if (strcasecmp(trim($info['name']), trim($original_sheet_name)) === 0) {
        $target_rId = $rId;
        $target_sheet_file = $info['file'];
        break;
    }
}

if (!$target_rId || !$target_sheet_file) {
    die("Selected sheet '{$original_sheet_name}' not found in Excel template.");
}

// 3. Load shared strings to resolve text values in cells
$shared_strings = [];
if ($zip->locateName('xl/sharedStrings.xml') !== false) {
    $ss_xml = $zip->getFromName('xl/sharedStrings.xml');
    $ss_dom = new DOMDocument();
    @$ss_dom->loadXML($ss_xml);
    foreach ($ss_dom->getElementsByTagName('si') as $si) {
        $shared_strings[] = $si->nodeValue;
    }
}

// Helper functions for XML cell manipulations
function getColLetter($colIdx) {
    $letter = '';
    while ($colIdx > 0) {
        $temp = ($colIdx - 1) % 26;
        $letter = chr($temp + 65) . $letter;
        $colIdx = intval(($colIdx - $temp - 1) / 26);
    }
    return $letter;
}

function colLetterToIdx($letter) {
    $idx = 0;
    $len = strlen($letter);
    for ($i = 0; $i < $len; $i++) {
        $idx = $idx * 26 + (ord($letter[$i]) - 64);
    }
    return $idx;
}

function getCellByRef($sheetData, $ref) {
    preg_match('/^([A-Z]+)([0-9]+)$/', $ref, $matches);
    if (!$matches) return null;
    $rowIdx = $matches[2];
    
    $rows = $sheetData->getElementsByTagName('row');
    foreach ($rows as $r) {
        if ($r->getAttribute('r') == $rowIdx) {
            $cells = $r->getElementsByTagName('c');
            foreach ($cells as $c) {
                if ($c->getAttribute('r') === $ref) {
                    return $c;
                }
            }
            break;
        }
    }
    return null;
}

function getCellValueResolved($cellNode, $shared_strings) {
    if (!$cellNode) return null;
    $v_nodes = $cellNode->getElementsByTagName('v');
    if ($v_nodes->length === 0) {
        $is_nodes = $cellNode->getElementsByTagName('is');
        if ($is_nodes->length > 0) {
            return $is_nodes->item(0)->nodeValue;
        }
        return null;
    }
    $val = $v_nodes->item(0)->nodeValue;
    if ($cellNode->getAttribute('t') === 's') {
        $idx = intval($val);
        return isset($shared_strings[$idx]) ? $shared_strings[$idx] : null;
    }
    return $val;
}

function getOrCreateCell($dom, $sheetData, $rowIdx, $colIdx) {
    $rowNode = null;
    $rows = $sheetData->getElementsByTagName('row');
    foreach ($rows as $r) {
        if ($r->getAttribute('r') == $rowIdx) {
            $rowNode = $r;
            break;
        }
    }
    
    if (!$rowNode) {
        $rowNode = $dom->createElement('row');
        $rowNode->setAttribute('r', $rowIdx);
        
        $inserted = false;
        foreach ($rows as $r) {
            if (intval($r->getAttribute('r')) > $rowIdx) {
                $sheetData->insertBefore($rowNode, $r);
                $inserted = true;
                break;
            }
        }
        if (!$inserted) {
            $sheetData->appendChild($rowNode);
        }
    }
    
    $cellRef = getColLetter($colIdx) . $rowIdx;
    $cells = $rowNode->getElementsByTagName('c');
    foreach ($cells as $c) {
        if ($c->getAttribute('r') === $cellRef) {
            return $c;
        }
    }
    
    $cellNode = $dom->createElement('c');
    $cellNode->setAttribute('r', $cellRef);
    
    $inserted = false;
    foreach ($cells as $c) {
        $cRef = $c->getAttribute('r');
        preg_match('/^([A-Z]+)/', $cRef, $m);
        $cColIdx = colLetterToIdx($m[1]);
        if ($cColIdx > $colIdx) {
            $rowNode->insertBefore($cellNode, $c);
            $inserted = true;
            break;
        }
    }
    if (!$inserted) {
        $rowNode->appendChild($cellNode);
    }
    
    return $cellNode;
}

function setCellValue($dom, $cellNode, $val) {
    while ($cellNode->hasChildNodes()) {
        $cellNode->removeChild($cellNode->firstChild);
    }
    
    if ($val === '' || $val === null) {
        $cellNode->removeAttribute('t');
        return;
    }
    
    if (is_numeric($val) && strpos(strval($val), '-') === false) {
        // Safe check to avoid converting dates like 2026-01-05 to numbers
        $cellNode->removeAttribute('t');
        $v = $dom->createElement('v', htmlspecialchars($val));
        $cellNode->appendChild($v);
    } else {
        $cellNode->setAttribute('t', 'inlineStr');
        $is = $dom->createElement('is');
        $t = $dom->createElement('t', htmlspecialchars($val));
        $is->appendChild($t);
        $cellNode->appendChild($is);
    }
}

function setCellFormulaAndValue($dom, $cellNode, $formula, $val) {
    while ($cellNode->hasChildNodes()) {
        $cellNode->removeChild($cellNode->firstChild);
    }
    $cellNode->removeAttribute('t');
    $fNode = $dom->createElement('f', $formula);
    $cellNode->appendChild($fNode);
    if ($val !== null) {
        $vNode = $dom->createElement('v', htmlspecialchars($val));
        $cellNode->appendChild($vNode);
    }
}

// Dynamic Equity calculations for Excel
function getBalanceForCategory($conn, $category, $asOfDate) {
    $asOfDateEsc = mysqli_real_escape_string($conn, $asOfDate);
    $whereClause = "";
    if ($category === 'share_capital') {
        $whereClause = "(a.account_code LIKE '30%' OR LOWER(a.account_name) LIKE '%share%' OR LOWER(a.account_name) LIKE '%capital%') AND t.parent_id = -3";
    } elseif ($category === 'retained_earnings') {
        $whereClause = "(a.account_code LIKE '32%' OR LOWER(a.account_name) LIKE '%retained%' OR LOWER(a.account_name) LIKE '%earnings%') AND t.parent_id = -3";
    } else {
        return 0.0;
    }

    $sql = "
        SELECT COALESCE(SUM(jel.credit - jel.debit), 0.00) as net_balance
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.id
        JOIN accounts a ON jel.account_id = a.id
        JOIN account_types t ON a.account_type_id = t.id
        WHERE je.statuss = 'POSTED' AND je.entry_date <= '$asOfDateEsc' AND $whereClause";
        
    $res = mysqli_query($conn, $sql);
    if ($res && $row = mysqli_fetch_assoc($res)) {
        return (float)$row['net_balance'];
    }
    return 0.0;
}

function getNetIncomeUpToDate($conn, $asOfDate) {
    $asOfDateEsc = mysqli_real_escape_string($conn, $asOfDate);
    
    $revenueSql = "
        SELECT COALESCE(SUM(jel.credit - jel.debit), 0.00) as net_revenue
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.id
        JOIN accounts a ON jel.account_id = a.id
        JOIN account_types t ON a.account_type_id = t.id
        WHERE je.statuss = 'POSTED' AND je.entry_date <= '$asOfDateEsc' AND t.parent_id = -4";
    $revRes = mysqli_query($conn, $revenueSql);
    $revRow = mysqli_fetch_assoc($revRes);
    $netRevenue = (float)($revRow['net_revenue'] ?? 0.0);

    $cogsSql = "
        SELECT COALESCE(SUM(jel.debit - jel.credit), 0.00) as net_cogs
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.id
        JOIN accounts a ON jel.account_id = a.id
        JOIN account_types t ON a.account_type_id = t.id
        WHERE je.statuss = 'POSTED' AND je.entry_date <= '$asOfDateEsc' AND t.parent_id = -5";
    $cogsRes = mysqli_query($conn, $cogsSql);
    $cogsRow = mysqli_fetch_assoc($cogsRes);
    $netCogs = (float)($cogsRow['net_cogs'] ?? 0.0);

    $expSql = "
        SELECT COALESCE(SUM(jel.debit - jel.credit), 0.00) as net_exp
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.id
        JOIN accounts a ON jel.account_id = a.id
        JOIN account_types t ON a.account_type_id = t.id
        WHERE je.statuss = 'POSTED' AND je.entry_date <= '$asOfDateEsc' AND t.parent_id = -6";
    $expRes = mysqli_query($conn, $expSql);
    $expRow = mysqli_fetch_assoc($expRes);
    $netExp = (float)($expRow['net_exp'] ?? 0.0);

    return $netRevenue - $netCogs - $netExp;
}

function getRetainedEarningsWithNetIncome($conn, $asOfDate) {
    $retainedRaw = getBalanceForCategory($conn, 'retained_earnings', $asOfDate);
    $netIncome = getNetIncomeUpToDate($conn, $asOfDate);
    return $retainedRaw + $netIncome;
}

function getNetIncomeForDateRange($conn, $startDate, $endDate) {
    $startDateEsc = mysqli_real_escape_string($conn, $startDate);
    $endDateEsc = mysqli_real_escape_string($conn, $endDate);
    
    $revenueSql = "
        SELECT COALESCE(SUM(jel.credit - jel.debit), 0.00) as net_revenue
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.id
        JOIN accounts a ON jel.account_id = a.id
        JOIN account_types t ON a.account_type_id = t.id
        WHERE je.statuss = 'POSTED' AND je.entry_date BETWEEN '$startDateEsc' AND '$endDateEsc' AND t.parent_id = -4";
    $revRes = mysqli_query($conn, $revenueSql);
    $revRow = mysqli_fetch_assoc($revRes);
    $netRevenue = (float)($revRow['net_revenue'] ?? 0.0);

    $cogsSql = "
        SELECT COALESCE(SUM(jel.debit - jel.credit), 0.00) as net_cogs
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.id
        JOIN accounts a ON jel.account_id = a.id
        JOIN account_types t ON a.account_type_id = t.id
        WHERE je.statuss = 'POSTED' AND je.entry_date BETWEEN '$startDateEsc' AND '$endDateEsc' AND t.parent_id = -5";
    $cogsRes = mysqli_query($conn, $cogsSql);
    $cogsRow = mysqli_fetch_assoc($cogsRes);
    $netCogs = (float)($cogsRow['net_cogs'] ?? 0.0);

    $expSql = "
        SELECT COALESCE(SUM(jel.debit - jel.credit), 0.00) as net_exp
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.id
        JOIN accounts a ON jel.account_id = a.id
        JOIN account_types t ON a.account_type_id = t.id
        WHERE je.statuss = 'POSTED' AND je.entry_date BETWEEN '$startDateEsc' AND '$endDateEsc' AND t.parent_id = -6";
    $expRes = mysqli_query($conn, $expSql);
    $expRow = mysqli_fetch_assoc($expRes);
    $netExp = (float)($expRow['net_exp'] ?? 0.0);

    return $netRevenue - $netCogs - $netExp;
}

function getDataStartRow($slug) {
    $row_map = [
        'purchase_logs_ta' => 13,
        'petty_cash_rub' => 7,
        'accounts_payable' => 5,
        'tin_summary' => 6,
        'ta_summary' => 5,
        'monthly_transactions' => 4,
    ];
    return $row_map[$slug] ?? 7;
}

function injectTabularRows($dom, $sheetData, $rows_data, $start_row) {
    if (empty($rows_data)) {
        return;
    }
    
    $num_cols = 0;
    foreach ($rows_data as $r) {
        $num_cols = max($num_cols, count($r));
    }
    
    // Save styles from the template row (start_row)
    $template_styles = [];
    $template_row_node = null;
    
    $row_nodes = $sheetData->getElementsByTagName('row');
    foreach ($row_nodes as $r_node) {
        if ($r_node->getAttribute('r') == $start_row) {
            $template_row_node = $r_node;
            break;
        }
    }
    
    for ($c = 1; $c <= $num_cols; $c++) {
        $cellRef = getColLetter($c) . $start_row;
        $style = null;
        if ($template_row_node) {
            foreach ($template_row_node->getElementsByTagName('c') as $cell_node) {
                if ($cell_node->getAttribute('r') === $cellRef) {
                    if ($cell_node->hasAttribute('s')) {
                        $style = $cell_node->getAttribute('s');
                    }
                    break;
                }
            }
        }
        $template_styles[$c] = $style;
    }
    
    $row_ht = null;
    $row_customFormat = null;
    $row_customHeight = null;
    $row_s = null;
    if ($template_row_node) {
        if ($template_row_node->hasAttribute('ht')) $row_ht = $template_row_node->getAttribute('ht');
        if ($template_row_node->hasAttribute('customFormat')) $row_customFormat = $template_row_node->getAttribute('customFormat');
        if ($template_row_node->hasAttribute('customHeight')) $row_customHeight = $template_row_node->getAttribute('customHeight');
        if ($template_row_node->hasAttribute('s')) $row_s = $template_row_node->getAttribute('s');
    }
    
    // Clear existing rows from start_row onwards
    $to_remove = [];
    foreach ($row_nodes as $r_node) {
        $r_val = intval($r_node->getAttribute('r'));
        if ($r_val >= $start_row) {
            $to_remove[] = $r_node;
        }
    }
    foreach ($to_remove as $r_node) {
        $sheetData->removeChild($r_node);
    }
    
    // Write new rows
    foreach ($rows_data as $r_idx => $row_vals) {
        $current_row = $start_row + $r_idx;
        
        $row_node = $dom->createElement('row');
        $row_node->setAttribute('r', $current_row);
        if ($row_ht !== null) $row_node->setAttribute('ht', $row_ht);
        if ($row_customFormat !== null) $row_node->setAttribute('customFormat', $row_customFormat);
        if ($row_customHeight !== null) $row_node->setAttribute('customHeight', $row_customHeight);
        if ($row_s !== null) $row_node->setAttribute('s', $row_s);
        $row_node->setAttribute('spans', "1:" . $num_cols);
        
        foreach ($row_vals as $c_idx => $val) {
            $col_idx = $c_idx + 1;
            $cellRef = getColLetter($col_idx) . $current_row;
            
            $cell_node = $dom->createElement('c');
            $cell_node->setAttribute('r', $cellRef);
            
            $style = isset($template_styles[$col_idx]) ? $template_styles[$col_idx] : null;
            global $sheet, $elements;
            if ($sheet === 'tin_summary' || $sheet === 'ta_summary') {
                $lme_col_idx = 6 + count($elements);
                if ($col_idx >= 6 && $col_idx < $lme_col_idx) {
                    $style = $template_styles[6] ?? null;
                } elseif ($col_idx === $lme_col_idx) {
                    $style = $template_styles[5] ?? null;
                }
            }
            if ($style !== null) {
                $cell_node->setAttribute('s', $style);
            }
            
            setCellValue($dom, $cell_node, $val);
            $row_node->appendChild($cell_node);
        }
        
        $sheetData->appendChild($row_node);
    }
    
    // Update dimension ref
    $dimension_nodes = $dom->getElementsByTagName('dimension');
    if ($dimension_nodes->length > 0) {
        $dim_node = $dimension_nodes->item(0);
        $ref = $dim_node->getAttribute('ref');
        if (preg_match('/^([A-Z]+[0-9]+):([A-Z]+)([0-9]+)$/', $ref, $matches)) {
            $start = $matches[1];
            $end_col = getColLetter($num_cols);
            $end_row = $start_row + count($rows_data) - 1;
            if ($end_row < $start_row) $end_row = $start_row;
            $dim_node->setAttribute('ref', $start . ":" . $end_col . $end_row);
        }
    }
}

function handleReconReport($dom, $sheetData, $gl_balance, $stmt_balance, $recon_items) {
    $cell_h8 = getOrCreateCell($dom, $sheetData, 8, 8);
    setCellValue($dom, $cell_h8, $stmt_balance);
    
    $cell_h77 = getOrCreateCell($dom, $sheetData, 77, 8);
    setCellValue($dom, $cell_h77, $gl_balance);
    
    $outstanding = [];
    $transit = [];
    $unrecorded = [];
    foreach ($recon_items as $item) {
        if ($item['item_type'] === 'outstanding_check') {
            $outstanding[] = $item;
        } elseif ($item['item_type'] === 'deposit_in_transit') {
            $transit[] = $item;
        } elseif ($item['item_type'] === 'unrecorded_payment') {
            $unrecorded[] = $item;
        }
    }
    
    for ($i = 0; $i < 6; $i++) {
        $row_num = 10 + $i;
        $cell_desc = getOrCreateCell($dom, $sheetData, $row_num, 3);
        $cell_amt = getOrCreateCell($dom, $sheetData, $row_num, 7);
        if ($i < count($outstanding)) {
            setCellValue($dom, $cell_desc, $outstanding[$i]['description']);
            setCellValue($dom, $cell_amt, $outstanding[$i]['amount']);
        } else {
            setCellValue($dom, $cell_desc, 0);
            setCellValue($dom, $cell_amt, 0);
        }
    }
    
    for ($i = 0; $i < 44; $i++) {
        $row_num = 18 + $i;
        $cell_desc = getOrCreateCell($dom, $sheetData, $row_num, 3);
        $cell_amt = getOrCreateCell($dom, $sheetData, $row_num, 7);
        if ($i < count($transit)) {
            setCellValue($dom, $cell_desc, $transit[$i]['description']);
            setCellValue($dom, $cell_amt, $transit[$i]['amount']);
        } else {
            setCellValue($dom, $cell_desc, 0);
            setCellValue($dom, $cell_amt, 0);
        }
    }
    
    for ($i = 0; $i < 11; $i++) {
        $row_num = 65 + $i;
        $cell_desc = getOrCreateCell($dom, $sheetData, $row_num, 3);
        $cell_amt = getOrCreateCell($dom, $sheetData, $row_num, 7);
        if ($i < count($unrecorded)) {
            setCellValue($dom, $cell_desc, $unrecorded[$i]['description']);
            setCellValue($dom, $cell_amt, $unrecorded[$i]['amount']);
        } else {
            setCellValue($dom, $cell_desc, 0);
            setCellValue($dom, $cell_amt, 0);
        }
    }
}

function handleCashCountReport($dom, $sheetData, $cash_counts, $shared_strings, $con_raw = []) {
    // Write petty cash (Left Side) — col B holds denomination, write to C (qty) and D (amount)
    foreach ($cash_counts as $item) {
        $denom    = floatval($item['denomination']);
        $qty      = intval($item['quantity']);
        $total    = floatval($item['total']);
        $currency = $item['currency'];

        for ($row = 1; $row <= 100; $row++) {
            if ($currency === 'RWF') {
                $cell_b = getCellByRef($sheetData, 'B' . $row);
                $val_b  = getCellValueResolved($cell_b, $shared_strings);
                if ($val_b !== null && floatval($val_b) == $denom) {
                    $cell_c = getOrCreateCell($dom, $sheetData, $row, 3); // C = qty
                    setCellValue($dom, $cell_c, $qty);
                    $cell_d = getOrCreateCell($dom, $sheetData, $row, 4); // D = amount
                    setCellValue($dom, $cell_d, $total);
                    break;
                }
            } elseif ($currency === 'USD') {
                // USD left side uses col B as well (same column layout)
                $cell_b = getCellByRef($sheetData, 'B' . $row);
                $val_b  = getCellValueResolved($cell_b, $shared_strings);
                if ($val_b !== null && floatval($val_b) == $denom) {
                    $cell_c = getOrCreateCell($dom, $sheetData, $row, 3); // C = qty
                    setCellValue($dom, $cell_c, $qty);
                    $cell_d = getOrCreateCell($dom, $sheetData, $row, 4); // D = amount
                    setCellValue($dom, $cell_d, $total);
                    break;
                }
            }
        }
    }

    // Write consolidated (Right Side) — col J holds denomination, write to K (qty) and L (amount)
    foreach ($con_raw as $item) {
        $denom    = floatval($item['denomination']);
        $qty      = intval($item['quantity']);
        $total    = floatval($item['total']);
        $currency = $item['currency'];

        for ($row = 1; $row <= 100; $row++) {
            $cell_j = getCellByRef($sheetData, 'J' . $row);
            $val_j  = getCellValueResolved($cell_j, $shared_strings);
            if ($val_j !== null && floatval($val_j) == $denom) {
                $cell_k = getOrCreateCell($dom, $sheetData, $row, 11); // K = qty
                setCellValue($dom, $cell_k, $qty);
                $cell_l = getOrCreateCell($dom, $sheetData, $row, 12); // L = amount
                setCellValue($dom, $cell_l, $total);
                break;
            }
        }
    }
}

function handleEquityReport($dom, $sheetData, $opening_capital, $opening_retained, $capital_movement, $profit, $closing_capital, $closing_retained, $startDate, $endDate, $periodLabel) {
    // 1. Write period label to Row 3, Col 2 (B)
    $cell_b3 = getOrCreateCell($dom, $sheetData, 3, 2);
    setCellValue($dom, $cell_b3, strtoupper($periodLabel));
    
    // 2. Row 6 (Opening balance)
    $cell_b6 = getOrCreateCell($dom, $sheetData, 6, 2);
    setCellValue($dom, $cell_b6, "Opening balance (" . date('j F Y', strtotime($startDate)) . ")");
    
    $cell_c6 = getOrCreateCell($dom, $sheetData, 6, 3);
    setCellValue($dom, $cell_c6, $opening_capital);
    
    $cell_d6 = getOrCreateCell($dom, $sheetData, 6, 4);
    setCellValue($dom, $cell_d6, $opening_retained);
    
    $cell_e6 = getOrCreateCell($dom, $sheetData, 6, 5);
    setCellFormulaAndValue($dom, $cell_e6, "SUM(C6:D6)", $opening_capital + $opening_retained);
    
    // 3. Row 7 (Profit/Loss)
    $cell_b7 = getOrCreateCell($dom, $sheetData, 7, 2);
    setCellValue($dom, $cell_b7, "Profit/(Loss) for the period");
    
    $cell_c7 = getOrCreateCell($dom, $sheetData, 7, 3);
    setCellValue($dom, $cell_c7, $capital_movement);
    
    $cell_d7 = getOrCreateCell($dom, $sheetData, 7, 4);
    setCellValue($dom, $cell_d7, $profit);
    
    $cell_e7 = getOrCreateCell($dom, $sheetData, 7, 5);
    setCellFormulaAndValue($dom, $cell_e7, "SUM(C7:D7)", $capital_movement + $profit);
    
    // 4. Row 8 (Closing balance)
    $cell_b8 = getOrCreateCell($dom, $sheetData, 8, 2);
    setCellValue($dom, $cell_b8, "Closing balance (" . date('j F Y', strtotime($endDate)) . ")");
    
    $cell_c8 = getOrCreateCell($dom, $sheetData, 8, 3);
    setCellFormulaAndValue($dom, $cell_c8, "SUM(C6:C7)", $closing_capital);
    
    $cell_d8 = getOrCreateCell($dom, $sheetData, 8, 4);
    setCellFormulaAndValue($dom, $cell_d8, "SUM(D6:D7)", $closing_retained);
    
    $cell_e8 = getOrCreateCell($dom, $sheetData, 8, 5);
    setCellFormulaAndValue($dom, $cell_e8, "SUM(E6:E7)", $closing_capital + $closing_retained);
}

// Calculate equity parameters if report selected
if ($sheet === 'equity_report') {
    $dayBeforeOpening = date('Y-m-d', strtotime($startDate . ' -1 day'));
    $opening_capital = getBalanceForCategory($conn, 'share_capital', $dayBeforeOpening);
    $opening_retained = getRetainedEarningsWithNetIncome($conn, $dayBeforeOpening);
    
    $closing_capital = getBalanceForCategory($conn, 'share_capital', $endDate);
    $closing_retained = getRetainedEarningsWithNetIncome($conn, $endDate);
    
    $p_l_profit = getNetIncomeForDateRange($conn, $startDate, $endDate);
    $retained_change = getBalanceForCategory($conn, 'retained_earnings', $endDate) - getBalanceForCategory($conn, 'retained_earnings', $dayBeforeOpening);
    $profit = $p_l_profit + $retained_change;
    $capital_movement = $closing_capital - $opening_capital;
}

// 4. Load target worksheet and execute modifications
$sheet_xml = $zip->getFromName('xl/' . $target_sheet_file);
if (!$sheet_xml) {
    die("Failed to read sheet XML for target sheet.");
}
$sheet_dom = new DOMDocument();
@$sheet_dom->loadXML($sheet_xml);
$sheetData = $sheet_dom->getElementsByTagName('sheetData')->item(0);
if (!$sheetData) {
    die("sheetData node not found in target sheet.");
}

// Apply custom labels (disabled - no report_labels table)
// Apply date replacements (disabled - no report_dates table)

// Inject sheet specific database data
if ($sheet === 'bank_recon_usd' || $sheet === 'bank_recon_rwf') {
    handleReconReport($sheet_dom, $sheetData, $gl_balance, $stmt_balance, $recon_items);
} elseif ($sheet === 'cash_count_hq' || $sheet === 'cash_count_rub') {
    handleCashCountReport($sheet_dom, $sheetData, $cash_counts, $shared_strings, $con_raw);
} elseif ($sheet === 'equity_report') {
    handleEquityReport($sheet_dom, $sheetData, $opening_capital, $opening_retained, $capital_movement, $profit, $closing_capital, $closing_retained, $startDate, $endDate, $periodLabel);
} else {
    if ($sheet === 'tin_summary' || $sheet === 'ta_summary') {
        $product_id = ($sheet === 'tin_summary') ? 1 : 2;
        // Fetch product UOM
        $uom_code = 'Kgs';
        $uom_query = "SELECT uom.code 
                      FROM product p
                      LEFT JOIN unit_of_measure uom ON p.uom_id = uom.id
                      WHERE p.id = $product_id";
        $uom_res = mysqli_query($conn, $uom_query);
        if ($uom_res && $uom_row = mysqli_fetch_assoc($uom_res)) {
            $uom_code = $uom_row['code'] ?: 'Kgs';
        }
        $kgs_cell = getOrCreateCell($sheet_dom, $sheetData, 4, 5);
        setCellValue($sheet_dom, $kgs_cell, $uom_code);

        // Query product elements dynamically based on graded elements in the filtered period
        $elements_query = "SELECT DISTINCT pe.id, pe.element_code, pe.element_name, pe.symbol, COALESCE(pec.display_order, 999) as display_order
                           FROM product_element pe
                           JOIN purchasing_element_grade peg ON pe.id = peg.product_element_id
                           JOIN purchasing p ON peg.purchasing_id = p.id
                           LEFT JOIN product_element_composition pec ON pe.id = pec.product_element_id AND pec.product_id = $product_id
                           WHERE p.product_id = $product_id {$date_cond}
                           ORDER BY display_order ASC, pe.element_code ASC";
        $elements_res = mysqli_query($conn, $elements_query);
        if (!$elements_res || mysqli_num_rows($elements_res) === 0) {
            $elements_query = "SELECT DISTINCT pe.id, pe.element_code, pe.element_name, pe.symbol, COALESCE(pec.display_order, 999) as display_order
                               FROM product_element pe
                               LEFT JOIN product_element_composition pec ON pe.id = pec.product_element_id AND pec.product_id = $product_id
                               WHERE pec.product_id = $product_id
                               ORDER BY display_order ASC, pe.element_code ASC";
            $elements_res = mysqli_query($conn, $elements_query);
        }
        $elements = [];
        while ($elem = mysqli_fetch_assoc($elements_res)) {
            $elements[] = $elem;
        }

        // Overwrite headers in row 4 (Column F onwards, index 6 onwards)
        $col_idx = 6;
        foreach ($elements as $elem) {
            $header_cell = getOrCreateCell($sheet_dom, $sheetData, 4, $col_idx);
            setCellValue($sheet_dom, $header_cell, $elem['element_code']);
            $col_idx++;
        }
        $lme_cell = getOrCreateCell($sheet_dom, $sheetData, 4, $col_idx);
        setCellValue($sheet_dom, $lme_cell, 'LME');
        // Clear any remaining template columns (original was F, G, H, I: indexes 6, 7, 8, 9)
        $col_idx++;
        while ($col_idx <= 9) {
            $extra_cell = getCellByRef($sheetData, getColLetter($col_idx) . '4');
            if ($extra_cell) {
                setCellValue($sheet_dom, $extra_cell, '');
            }
            $col_idx++;
        }
    }
    $start_row = getDataStartRow($sheet);
    injectTabularRows($sheet_dom, $sheetData, $rows_data, $start_row);
}

// Write the modified sheet XML back to the archive
$zip->addFromString('xl/' . $target_sheet_file, $sheet_dom->saveXML());

// 5. Clean workbook structure to keep ONLY the selected sheet
// workbook.xml - keep only the single <sheet>
$wb_xml = $zip->getFromName('xl/workbook.xml');
$wb_dom = new DOMDocument();
@$wb_dom->loadXML($wb_xml);
$sheets_list = $wb_dom->getElementsByTagName('sheet');
$to_remove_sheets = [];
foreach ($sheets_list as $sheet_node) {
    if ($sheet_node->getAttribute('name') !== $original_sheet_name) {
        $to_remove_sheets[] = $sheet_node;
    }
}
foreach ($to_remove_sheets as $sheet_node) {
    $sheet_node->parentNode->removeChild($sheet_node);
}
$zip->addFromString('xl/workbook.xml', $wb_dom->saveXML());

// workbook.xml.rels - keep only the target sheet Relationship
$rels_xml = $zip->getFromName('xl/_rels/workbook.xml.rels');
$rels_dom = new DOMDocument();
@$rels_dom->loadXML($rels_xml);
$rels_list = $rels_dom->getElementsByTagName('Relationship');
$to_remove_rels = [];
foreach ($rels_list as $rel_node) {
    $type = $rel_node->getAttribute('Type');
    $rId = $rel_node->getAttribute('Id');
    if (strpos($type, '/worksheet') !== false) {
        if ($rId !== $target_rId) {
            $to_remove_rels[] = $rel_node;
        }
    }
}
foreach ($to_remove_rels as $rel_node) {
    $rel_node->parentNode->removeChild($rel_node);
}
$zip->addFromString('xl/_rels/workbook.xml.rels', $rels_dom->saveXML());

// [Content_Types].xml - keep only the target worksheet Override
$ct_xml = $zip->getFromName('[Content_Types].xml');
$ct_dom = new DOMDocument();
@$ct_dom->loadXML($ct_xml);
$overrides = $ct_dom->getElementsByTagName('Override');
$to_remove_ct = [];
foreach ($overrides as $override) {
    $partName = $override->getAttribute('PartName');
    if (preg_match('/^\/xl\/worksheets\/sheet\d+\.xml$/i', $partName)) {
        if (strcasecmp($partName, '/xl/' . $target_sheet_file) !== 0) {
            $to_remove_ct[] = $override;
        }
    }
}
foreach ($to_remove_ct as $override) {
    $override->parentNode->removeChild($override);
}
$zip->addFromString('[Content_Types].xml', $ct_dom->saveXML());

// 6. Delete all other sheet files from ZIP archive to keep size small
foreach ($all_sheets_discovered as $rId => $info) {
    if ($rId !== $target_rId) {
        $zip->deleteName('xl/' . $info['file']);
        $zip->deleteName('xl/worksheets/_rels/' . basename($info['file']) . '.rels');
    }
}

// Save all changes
$zip->close();

// Stream the resulting file to the browser
if (file_exists($output_path)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($output_path));
    readfile($output_path);
    unlink($output_path);
    exit();
} else {
    echo "Failed to generate Excel file.";
}
?>
