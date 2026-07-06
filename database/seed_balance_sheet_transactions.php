<?php
require_once __DIR__ . '/../config/db.php';

// Define standard accounts to check/create
$requiredAccounts = [
    // Assets (type -1)
    ['code' => '1500', 'name' => 'Property, Plant and Equipment', 'type_id' => -1, 'desc' => 'Long term physical assets'],
    ['code' => '1300', 'name' => 'Merchandise Inventory', 'type_id' => -1, 'desc' => 'Inventory held for sale'],
    ['code' => '1100', 'name' => 'Trade Receivables', 'type_id' => 6, 'desc' => 'Accounts receivable from customers'],
    ['code' => '1010', 'name' => 'Cash and Bank Balances', 'type_id' => -1, 'desc' => 'Cash on hand and in bank accounts'],
    
    // Equity (type -3)
    ['code' => '3000', 'name' => 'Share Capital', 'type_id' => -3, 'desc' => 'Owner contributed capital'],
    ['code' => '3200', 'name' => 'Retained Earnings', 'type_id' => -3, 'desc' => 'Accumulated historical earnings'],
    
    // Liabilities (type -2)
    ['code' => '2200', 'name' => 'Long Term Loans', 'type_id' => -2, 'desc' => 'Long term liabilities and bank loans'],
    ['code' => '2001', 'name' => 'Trade Payables', 'type_id' => 3, 'desc' => 'Accounts payable to suppliers'],
    ['code' => '2100', 'name' => 'Other Current Liabilities', 'type_id' => -2, 'desc' => 'Accrued current liabilities'],
    ['code' => '2400', 'name' => 'Current Tax Payable', 'type_id' => -2, 'desc' => 'Tax liabilities payable to RRA']
];

$accountIds = [];

echo "=== Seeding Balance Sheet Accounts ===\n";
foreach ($requiredAccounts as $acc) {
    $code = mysqli_real_escape_string($conn, $acc['code']);
    $name = mysqli_real_escape_string($conn, $acc['name']);
    $typeId = (int)$acc['type_id'];
    $desc = mysqli_real_escape_string($conn, $acc['desc']);
    
    $check = mysqli_query($conn, "SELECT id FROM accounts WHERE account_code = '$code' LIMIT 1");
    if ($check && mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $accountIds[$code] = (int)$row['id'];
        echo "Account '$code' ($name) already exists.\n";
    } else {
        $insert = mysqli_query($conn, "INSERT INTO accounts (account_type_id, account_code, account_name, description, is_active) 
                                      VALUES ($typeId, '$code', '$name', '$desc', 1)");
        if ($insert) {
            $id = mysqli_insert_id($conn);
            $accountIds[$code] = $id;
            echo "Created Account '$code' ($name) successfully.\n";
        } else {
            echo "Failed to create Account '$code': " . mysqli_error($conn) . "\n";
        }
    }
}

// Check if we have currency 1 (RWF)
$currCheck = mysqli_query($conn, "SELECT id FROM currencies WHERE id = 1 LIMIT 1");
if (!$currCheck || mysqli_num_rows($currCheck) === 0) {
    mysqli_query($conn, "INSERT INTO currencies (id, code, name, symbol, is_base_currency, is_active, created_by) 
                         VALUES (1, 'RWF', 'RWANDAN FRANGS', 'Rwf', 1, 1, 2)");
}

// Helper to post a journal entry
function seedJournalEntry($conn, $date, $journalNo, $description, $lines, $accountIds) {
    // Check if journal no already seeded
    $check = mysqli_query($conn, "SELECT id FROM journal_entries WHERE journal_no = '" . mysqli_real_escape_string($conn, $journalNo) . "' LIMIT 1");
    if ($check && mysqli_num_rows($check) > 0) {
        echo "Journal entry '$journalNo' already seeded.\n";
        return;
    }
    
    mysqli_begin_transaction($conn);
    try {
        $insertHeader = "INSERT INTO journal_entries (journal_no, entry_date, description, statuss, created_by, created_at)
                         VALUES ('" . mysqli_real_escape_string($conn, $journalNo) . "', '$date', '" . mysqli_real_escape_string($conn, $description) . "', 'POSTED', 2, NOW())";
        if (!mysqli_query($conn, $insertHeader)) {
            throw new Exception("Failed to insert header: " . mysqli_error($conn));
        }
        
        $jeId = mysqli_insert_id($conn);
        
        foreach ($lines as $line) {
            $accCode = $line['account_code'];
            if (!isset($accountIds[$accCode])) {
                throw new Exception("Account code '$accCode' not resolved in database.");
            }
            $accId = $accountIds[$accCode];
            $debit = (float)$line['debit'];
            $credit = (float)$line['credit'];
            $amt = $debit > 0 ? $debit : $credit;
            
            $insertLine = "INSERT INTO journal_entry_lines (
                                journal_entry_id, account_id, debit, credit, currency_id, exchange_rate, amount_currency, amount_base, description
                           ) VALUES (
                                $jeId, $accId, $debit, $credit, 1, 1.000000, $amt, $amt, '" . mysqli_real_escape_string($conn, $line['desc']) . "'
                           )";
            if (!mysqli_query($conn, $insertLine)) {
                throw new Exception("Failed to insert line for '$accCode': " . mysqli_error($conn));
            }
        }
        
        mysqli_commit($conn);
        echo "Seeded journal entry '$journalNo' successfully.\n";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "Failed seeding journal entry '$journalNo': " . $e->getMessage() . "\n";
    }
}

// 1. Seed 2020 Opening Balances
$lines2020 = [
    ['account_code' => '1500', 'debit' => 51175000, 'credit' => 0, 'desc' => 'PPE opening balance 2020'],
    ['account_code' => '1300', 'debit' => 464009712, 'credit' => 0, 'desc' => 'Inventory opening balance 2020'],
    ['account_code' => '1100', 'debit' => 185174448, 'credit' => 0, 'desc' => 'Accounts receivable opening 2020'],
    ['account_code' => '1010', 'debit' => 44507100, 'credit' => 0, 'desc' => 'Cash and cash equivalents opening 2020'],
    ['account_code' => '3000', 'debit' => 0, 'credit' => 15000000, 'desc' => 'Share capital opening 2020'],
    ['account_code' => '3200', 'debit' => 0, 'credit' => 381297812, 'desc' => 'Retained earnings opening 2020'],
    ['account_code' => '2001', 'debit' => 0, 'credit' => 149525000, 'desc' => 'Accounts payable opening 2020'],
    ['account_code' => '2100', 'debit' => 0, 'credit' => 35630100, 'desc' => 'Other current liabilities opening 2020'],
    ['account_code' => '2400', 'debit' => 0, 'credit' => 163413348, 'desc' => 'Current tax payable opening 2020']
];
seedJournalEntry($conn, '2020-12-31', 'JE-20201231-0001', 'Opening Statement of Financial Position 2020', $lines2020, $accountIds);

// 2. Seed 2021 Movements
$lines2021 = [
    ['account_code' => '1500', 'debit' => 379480062, 'credit' => 0, 'desc' => 'PPE additions movement 2021'],
    ['account_code' => '1300', 'debit' => 309189158, 'credit' => 0, 'desc' => 'Inventory change movement 2021'],
    ['account_code' => '1100', 'debit' => 185390724, 'credit' => 0, 'desc' => 'Accounts receivable change 2021'],
    ['account_code' => '1010', 'debit' => 51479230, 'credit' => 0, 'desc' => 'Cash movement 2021'],
    ['account_code' => '3200', 'debit' => 0, 'credit' => 491979478, 'desc' => 'Retained earnings change 2021'],
    ['account_code' => '2200', 'debit' => 0, 'credit' => 247055474, 'desc' => 'Long term loan addition 2021'],
    ['account_code' => '2001', 'debit' => 0, 'credit' => 107669092, 'desc' => 'Accounts payable change 2021'],
    ['account_code' => '2100', 'debit' => 0, 'credit' => 31400130, 'desc' => 'Other current liabilities change 2021'],
    ['account_code' => '2400', 'debit' => 0, 'credit' => 47435000, 'desc' => 'Current tax payable change 2021']
];
seedJournalEntry($conn, '2021-12-31', 'JE-20211231-0001', 'Statement of Financial Position Movements 2021', $lines2021, $accountIds);

// 3. Seed 2022 Movements
$lines2022 = [
    ['account_code' => '1500', 'debit' => 271496138, 'credit' => 0, 'desc' => 'PPE additions movement 2022'],
    ['account_code' => '1300', 'debit' => 18425051, 'credit' => 0, 'desc' => 'Inventory change movement 2022'],
    ['account_code' => '1100', 'debit' => 71434513, 'credit' => 0, 'desc' => 'Accounts receivable change 2022'],
    ['account_code' => '2200', 'debit' => 43716697, 'credit' => 0, 'desc' => 'Long term loan principal repayment 2022'],
    ['account_code' => '2001', 'debit' => 144445786, 'credit' => 0, 'desc' => 'Accounts payable change 2022'],
    ['account_code' => '2100', 'debit' => 17030230, 'credit' => 0, 'desc' => 'Other current liabilities change 2022'],
    
    ['account_code' => '1010', 'debit' => 0, 'credit' => 20180230, 'desc' => 'Cash reduction movement 2022'],
    ['account_code' => '3200', 'debit' => 0, 'credit' => 530051923, 'desc' => 'Retained earnings change 2022'],
    ['account_code' => '2400', 'debit' => 0, 'credit' => 16316762, 'desc' => 'Current tax payable change 2022']
];
seedJournalEntry($conn, '2022-12-31', 'JE-20221231-0001', 'Statement of Financial Position Movements 2022', $lines2022, $accountIds);

mysqli_close($conn);
echo "=== Seeding Transactions Complete ===\n";
?>
