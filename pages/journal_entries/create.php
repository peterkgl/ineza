<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

// Access permission check
if (!hasPermission($conn, $userId, 'create_journal_entry')) {
    header("Location: ../dashboard");
    exit();
}

if (empty($_SESSION['journal_entries_token'])) {
    $_SESSION['journal_entries_token'] = bin2hex(random_bytes(32));
}

// Fetch Active Accounts
$accQuery = "SELECT id, account_code, account_name FROM accounts WHERE is_active = 1 ORDER BY account_code ASC";
$accResult = mysqli_query($conn, $accQuery);
$accounts = [];
if ($accResult) {
    while ($row = mysqli_fetch_assoc($accResult)) {
        $accounts[] = $row;
    }
}

// Fetch Currencies
$currQuery = "SELECT id, code, name FROM currencies WHERE is_active = 1";
$currResult = mysqli_query($conn, $currQuery);
$currencies = [];
if ($currResult) {
    while ($row = mysqli_fetch_assoc($currResult)) {
        $currencies[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Create Journal Entry</title>
<meta name="description" content="Draft and save manual double entry journal entries.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/journal_entries.css">
<script>
  (function() {
    var savedTheme = localStorage.getItem('theme');
    var currentTheme = savedTheme || 'light';
    document.documentElement.setAttribute('data-theme', currentTheme);
  })();
</script>
</head>
<body>

<?php include '../include/sidebar.php'; ?>

<div class="main">

  <?php $page_title = "Record Manual Journal Entry"; include '../include/navbar.php'; ?>

  <div class="content" id="journalCreateContent">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; margin-right: 8px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          New Manual Journal Entry
        </h1>
        <div class="page-sub">Draft a balanced debit and credit ledger record</div>
      </div>
      <div class="page-actions">
        <a href="./index" class="btn-sm" style="text-decoration: none;">
          Cancel &amp; Return
        </a>
      </div>
    </div>

    <!-- ===== FORM CARD ===== -->
    <div class="journal-container">
      <div class="journal-card">
        <form id="journalForm">
          <input type="hidden" name="token" value="<?php echo $_SESSION['journal_entries_token']; ?>">

          <!-- Header details -->
          <div class="form-grid">
            <div class="form-group">
              <label for="entry_date">Transaction Date *</label>
              <input type="date" id="entry_date" name="entry_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="form-group">
              <label for="journal_no">Journal Entry Number</label>
              <input type="text" id="journal_no" class="form-control" placeholder="Auto-generated serial" disabled>
            </div>

            <div class="form-group">
              <label for="currency_id">Transaction Currency</label>
              <select id="currency_id" name="currency_id" class="form-control">
                <?php foreach ($currencies as $c): ?>
                  <option value="<?php echo $c['id']; ?>" <?php echo $c['code'] === 'RWF' ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c['name'] . ' (' . $c['code'] . ')'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="exchange_rate">Exchange Rate (vs RWF)</label>
              <input type="number" id="exchange_rate" name="exchange_rate" class="form-control" value="1.000000" step="0.000001" min="0.000001">
            </div>
          </div>

          <div class="form-group" style="margin-bottom: 24px;">
            <label for="description">Memo / Header Narration *</label>
            <input type="text" id="description" name="description" class="form-control" placeholder="Provide a summary explanation for this ledger adjustment..." required>
          </div>

          <!-- Entry lines -->
          <div class="journal-card-title" style="margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
            <span>Ledger Allocation Lines</span>
            <button type="button" class="btn-sm btn-primary" id="addLineBtn" style="font-size: 11.5px; padding: 4px 10px;">
              <svg class="btn-icon btn-icon-white" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Add Row
            </button>
          </div>

          <div class="note-table-wrapper">
            <table class="line-table" id="lineTable">
              <thead>
                <tr>
                  <th style="width: 32%;">Account</th>
                  <th style="width: 32%;">Description</th>
                  <th style="width: 15%; text-align: right;">Debit</th>
                  <th style="width: 15%; text-align: right;">Credit</th>
                  <th style="width: 6%; text-align: center;">Action</th>
                </tr>
              </thead>
              <tbody id="lineBody">
                <!-- Lines will be dynamically generated by JS -->
              </tbody>
            </table>
          </div>

          <!-- Balance Validation & Summary -->
          <div class="balance-summary-box">
            <div class="balance-card">
              <div class="balance-row">
                <span>Total Debits:</span>
                <span id="summaryDebit" style="font-family: monospace; font-weight: 500;">0.00</span>
              </div>
              <div class="balance-row">
                <span>Total Credits:</span>
                <span id="summaryCredit" style="font-family: monospace; font-weight: 500;">0.00</span>
              </div>
              <div class="balance-row total">
                <span>Difference:</span>
                <span id="summaryDiff" class="difference balanced" style="font-family: monospace;">0.00</span>
              </div>
            </div>
          </div>

          <!-- Error Alert Panel -->
          <div id="errorAlert" class="error-msg" style="display: none; padding: 12px; background: rgba(232, 26, 26, 0.1); border: 1px solid var(--red); color: var(--red); border-radius: var(--radius); margin-top: 20px; font-size: 12.5px;"></div>

          <!-- Save Actions -->
          <div class="form-actions">
            <button type="submit" class="btn-sm btn-primary" id="saveBtn" style="padding: 10px 18px; font-size: 13px;" disabled>
              Post Journal Entry
            </button>
          </div>

        </form>
      </div>
    </div>

  </div>
</div>

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script>
const accounts = <?php echo json_encode($accounts); ?>;
const currencies = <?php echo json_encode($currencies); ?>;

document.addEventListener('DOMContentLoaded', function() {
    const lineBody = document.getElementById('lineBody');
    const addLineBtn = document.getElementById('addLineBtn');
    const entryDateInput = document.getElementById('entry_date');
    const journalNoInput = document.getElementById('journal_no');
    const currencySelect = document.getElementById('currency_id');
    const exchangeRateInput = document.getElementById('exchange_rate');
    const journalForm = document.getElementById('journalForm');
    const errorAlert = document.getElementById('errorAlert');
    const saveBtn = document.getElementById('saveBtn');

    // Fetch Recommended Journal No on Date change
    function fetchJournalNo() {
        const date = entryDateInput.value;
        if (!date) return;
        fetch(`api.php?action=generate_no&date=${date}`)
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    journalNoInput.value = res.data.journal_no;
                }
            });
    }

    entryDateInput.addEventListener('change', fetchJournalNo);
    fetchJournalNo(); // initial load

    // Exchange Rate automation
    currencySelect.addEventListener('change', function() {
        const val = this.value;
        // If RWF (1), set rate to 1
        if (val === '1') {
            exchangeRateInput.value = '1.000000';
            exchangeRateInput.disabled = true;
        } else {
            exchangeRateInput.disabled = false;
            // set default exchange rate (e.g. 1400.00 for USD)
            if (val === '2') {
                exchangeRateInput.value = '1400.000000';
            }
        }
    });
    // Trigger initial state
    if (currencySelect.value === '1') {
        exchangeRateInput.disabled = true;
    }

    // Add empty row helper
    function addLineRow() {
        const tr = document.createElement('tr');
        tr.className = 'entry-line-row';

        // Select Account dropdown
        let optionsHtml = '<option value="">-- Choose Account --</option>';
        accounts.forEach(acc => {
            optionsHtml += `<option value="${acc.id}">${acc.account_name} (${acc.account_code})</option>`;
        });

        tr.innerHTML = `
            <td>
                <select class="form-control line-account" style="font-size:12.5px;" required>
                    ${optionsHtml}
                </select>
            </td>
            <td>
                <input type="text" class="form-control line-desc" placeholder="Line description..." style="font-size:12.5px;">
            </td>
            <td>
                <input type="number" class="form-control line-debit" value="0.00" step="0.01" min="0" style="text-align: right; font-family: monospace; font-size:12.5px;">
            </td>
            <td>
                <input type="number" class="form-control line-credit" value="0.00" step="0.01" min="0" style="text-align: right; font-family: monospace; font-size:12.5px;">
            </td>
            <td style="text-align: center;">
                <button type="button" class="btn-danger-outline remove-row-btn" style="padding: 4px 8px; font-size:11px;">Remove</button>
            </td>
        `;

        lineBody.appendChild(tr);

        // Bind events
        const debitInput = tr.querySelector('.line-debit');
        const creditInput = tr.querySelector('.line-credit');
        const removeBtn = tr.querySelector('.remove-row-btn');

        // Prevent typing both debit and credit
        debitInput.addEventListener('input', function() {
            if (parseFloat(this.value) > 0) {
                creditInput.value = '0.00';
            }
            recalculateTotals();
        });

        creditInput.addEventListener('input', function() {
            if (parseFloat(this.value) > 0) {
                debitInput.value = '0.00';
            }
            recalculateTotals();
        });

        removeBtn.addEventListener('click', function() {
            tr.remove();
            recalculateTotals();
        });

        recalculateTotals();
    }

    addLineBtn.addEventListener('click', addLineRow);

    // Add first 2 default rows
    addLineRow();
    addLineRow();

    // Recalculate Debits & Credits Totals
    function recalculateTotals() {
        const debits = document.querySelectorAll('.line-debit');
        const credits = document.querySelectorAll('.line-credit');

        let totalDebit = 0;
        let totalCredit = 0;

        debits.forEach(d => {
            const val = parseFloat(d.value);
            if (!isNaN(val)) totalDebit += val;
        });

        credits.forEach(c => {
            const val = parseFloat(c.value);
            if (!isNaN(val)) totalCredit += val;
        });

        document.getElementById('summaryDebit').textContent = totalDebit.toFixed(2);
        document.getElementById('summaryCredit').textContent = totalCredit.toFixed(2);

        const diff = Math.abs(totalDebit - totalCredit);
        const diffSpan = document.getElementById('summaryDiff');
        diffSpan.textContent = diff.toFixed(2);

        const rowCount = document.querySelectorAll('.entry-line-row').length;

        // Balanced and valid check
        if (diff < 0.01 && totalDebit > 0 && rowCount >= 2) {
            diffSpan.className = 'difference balanced';
            diffSpan.textContent = 'Balanced';
            saveBtn.disabled = false;
        } else {
            diffSpan.className = 'difference';
            saveBtn.disabled = true;
        }
    }

    // Handle Form Submit
    journalForm.addEventListener('submit', function(e) {
        e.preventDefault();
        errorAlert.style.display = 'none';

        // Pack lines data
        const rows = document.querySelectorAll('.entry-line-row');
        const linesData = [];
        let isValid = true;

        rows.forEach((row, i) => {
            const accountId = row.querySelector('.line-account').value;
            const description = row.querySelector('.line-desc').value;
            const debit = parseFloat(row.querySelector('.line-debit').value) || 0;
            const credit = parseFloat(row.querySelector('.line-credit').value) || 0;

            if (!accountId) {
                isValid = false;
                row.querySelector('.line-account').style.borderColor = 'var(--red)';
            } else {
                row.querySelector('.line-account').style.borderColor = '';
            }

            linesData.push({
                account_id: accountId,
                description: description,
                debit: debit,
                credit: credit
            });
        });

        if (!isValid) {
            errorAlert.textContent = "Please select a valid ledger account for every line.";
            errorAlert.style.display = 'block';
            return;
        }

        // Send AJAX POST
        saveBtn.disabled = true;
        saveBtn.textContent = "Saving...";

        const formData = new FormData(journalForm);
        formData.append('lines', JSON.stringify(linesData));

        fetch('api.php?action=create', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                window.location.href = `view?id=${res.data.id}`;
            } else {
                errorAlert.textContent = res.message;
                errorAlert.style.display = 'block';
                saveBtn.disabled = false;
                saveBtn.textContent = "Post Journal Entry";
            }
        })
        .catch(err => {
            errorAlert.textContent = "System connection failure. Please try again.";
            errorAlert.style.display = 'block';
            saveBtn.disabled = false;
            saveBtn.textContent = "Post Journal Entry";
        });
    });
});
</script>
</body>
</html>
