<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'manage_settings')) {
    header("Location: ../dashboard");
    exit();
}

$success_message = '';
$error_message = '';

// Helper to log audit trail
function logSettingsAudit($conn, $user_id, $action, $description, $old_vals = '', $new_vals = '') {
    $user_id = (int)$user_id;
    
    // Fetch user full name
    $uQuery = mysqli_query($conn, "SELECT first_name, last_name FROM users WHERE id = $user_id LIMIT 1");
    $fullName = "System User";
    if ($uQuery && mysqli_num_rows($uQuery) > 0) {
        $u = mysqli_fetch_assoc($uQuery);
        $fullName = trim($u['first_name'] . ' ' . $u['last_name']);
    }
    
    $fullNameEsc = mysqli_real_escape_string($conn, $fullName);
    $actionEsc = mysqli_real_escape_string($conn, $action);
    $descEsc = mysqli_real_escape_string($conn, $description);
    $oldEsc = mysqli_real_escape_string($conn, $old_vals);
    $newEsc = mysqli_real_escape_string($conn, $new_vals);
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    mysqli_query($conn, "INSERT INTO audit_log (
        user_full_name, action, target_table, target_name, target_description,
        old_values, new_values, ip_address, user_agent
    ) VALUES (
        '$fullNameEsc', '$actionEsc', 'settings', 'System Configuration', '$descEsc',
        '$oldEsc', '$newEsc', '$ip', '$ua'
    )");
}

// Process POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['token'] ?? '';
    if (empty($csrf_token) || $csrf_token !== ($_SESSION['settings_token'] ?? '')) {
        $error_message = "Invalid transaction token. Please try again.";
    } else {
        $keys_to_update = [
            'tax_rate_rra',
            'tax_rate_rma_tin',
            'tax_rate_rma_coltan',
            'tax_rate_rma_wolframite',
            'tax_rate_inkomane_tin',
            'tax_rate_inkomane_coltan',
            'tax_rate_inkomane_wolframite'
        ];
        
        $old_settings = [];
        $new_settings = [];
        
        mysqli_begin_transaction($conn);
        
        try {
            foreach ($keys_to_update as $key) {
                if (isset($_POST[$key])) {
                    $val = trim($_POST[$key]);
                    if ($val === '') {
                        throw new Exception("Setting keys cannot be left empty.");
                    }
                    if (!is_numeric($val) || (float)$val < 0) {
                        throw new Exception("Setting values must be valid non-negative numbers.");
                    }
                    
                    // Fetch old value
                    $old_val = '';
                    $oldQuery = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = '$key' LIMIT 1");
                    if ($oldQuery && mysqli_num_rows($oldQuery) > 0) {
                        $old_val = mysqli_fetch_assoc($oldQuery)['setting_value'];
                    }
                    
                    $old_settings[$key] = $old_val;
                    $new_settings[$key] = $val;
                    
                    $valEsc = mysqli_real_escape_string($conn, $val);
                    $updateQuery = "UPDATE settings SET setting_value = '$valEsc' WHERE setting_key = '$key'";
                    if (!mysqli_query($conn, $updateQuery)) {
                        throw new Exception("Failed to update setting key: " . mysqli_error($conn));
                    }
                }
            }
            
            mysqli_commit($conn);
            $success_message = "Configuration settings updated successfully.";
            
            // Log audit trail
            logSettingsAudit(
                $conn, 
                $userId, 
                'UPDATE', 
                'Updated purchase module tax and levy configuration rates',
                json_encode($old_settings),
                json_encode($new_settings)
            );
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_message = $e->getMessage();
        }
    }
}

// Generate token
$_SESSION['settings_token'] = bin2hex(random_bytes(32));

// Fetch settings
$settings = [];
$res = mysqli_query($conn, "SELECT setting_key, setting_value, description FROM settings");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $settings[$row['setting_key']] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — System Settings</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/settings.css">
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

  <?php $page_title = "System Settings"; include '../include/navbar.php'; ?>

  <div class="content">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          Purchase Settings
        </h1>
        <div class="page-sub">Configure RRA withholding tax rates, RMA levies, and INKOMANE local community taxes</div>
      </div>
    </div>

    <form method="POST" action="index.php" style="margin-top: 14px;">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['settings_token']); ?>">
      
      <div class="settings-grid-layout">
        
        <?php if (!empty($success_message)): ?>
          <div class="alert-item green" style="border: 1px solid var(--green);">
            <svg class="alert-icon" viewBox="0 0 24 24" style="stroke: var(--green);"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <div class="alert-content">
              <div class="alert-title"><?php echo htmlspecialchars($success_message); ?></div>
            </div>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
          <div class="alert-item red" style="border: 1px solid var(--red);">
            <svg class="alert-icon" viewBox="0 0 24 24" style="stroke: var(--red);"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div class="alert-content">
              <div class="alert-title"><?php echo htmlspecialchars($error_message); ?></div>
            </div>
          </div>
        <?php endif; ?>

        <!-- Government Taxes Card -->
        <div class="card">
          <div class="settings-card-header">
            <h2 class="settings-card-title">
              <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              Rwanda Revenue Authority (RRA) Tax
            </h2>
          </div>
          
          <div class="form-group" style="max-width: 320px; margin-bottom: 0;">
            <label for="tax_rate_rra">Withholding Tax Rate</label>
            <div class="input-addon-group">
              <input type="number" id="tax_rate_rra" name="tax_rate_rra" class="form-control" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($settings['tax_rate_rra']['setting_value'] ?? '3.00'); ?>" required>
              <span class="addon-suffix">%</span>
            </div>
            <div class="description">Governs standard withholding tax deductions applied to supplier purchase values.</div>
          </div>
        </div>

        <!-- RMA Levies Card -->
        <div class="card">
          <div class="settings-card-header">
            <h2 class="settings-card-title">
              <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="3" y1="9" x2="21" y2="9"/></svg>
              RMA Levy Rates
            </h2>
          </div>
          
          <div class="settings-row">
            <div class="form-group">
              <label for="tax_rate_rma_tin">Tin (Sn)</label>
              <div class="input-addon-group">
                <input type="number" id="tax_rate_rma_tin" name="tax_rate_rma_tin" class="form-control" step="0.1" min="0" value="<?php echo htmlspecialchars($settings['tax_rate_rma_tin']['setting_value'] ?? '50.0'); ?>" required>
                <span class="addon-suffix">RWF</span>
              </div>
              <div class="description">Tin (Sn) RMA levy rate applied per kilogram of delivered material.</div>
            </div>

            <div class="form-group">
              <label for="tax_rate_rma_coltan">Tantalum (Ta) / Coltan</label>
              <div class="input-addon-group">
                <input type="number" id="tax_rate_rma_coltan" name="tax_rate_rma_coltan" class="form-control" step="0.1" min="0" value="<?php echo htmlspecialchars($settings['tax_rate_rma_coltan']['setting_value'] ?? '125.0'); ?>" required>
                <span class="addon-suffix">RWF</span>
              </div>
              <div class="description">Tantalum (Ta) RMA levy rate applied per kilogram of delivered material.</div>
            </div>

            <div class="form-group">
              <label for="tax_rate_rma_wolframite">Wolframite (W03)</label>
              <div class="input-addon-group">
                <input type="number" id="tax_rate_rma_wolframite" name="tax_rate_rma_wolframite" class="form-control" step="0.1" min="0" value="<?php echo htmlspecialchars($settings['tax_rate_rma_wolframite']['setting_value'] ?? '50.0'); ?>" required>
                <span class="addon-suffix">RWF</span>
              </div>
              <div class="description">Wolframite (W03) RMA levy rate applied per kilogram of delivered material.</div>
            </div>
          </div>
        </div>

        <!-- INKOMANE local tax Card -->
        <div class="card">
          <div class="settings-card-header">
            <h2 class="settings-card-title">
              <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              INKOMANE Tax Rates
            </h2>
          </div>
          
          <div class="settings-row">
            <div class="form-group">
              <label for="tax_rate_inkomane_tin">Tin (Sn)</label>
              <div class="input-addon-group">
                <input type="number" id="tax_rate_inkomane_tin" name="tax_rate_inkomane_tin" class="form-control" step="0.1" min="0" value="<?php echo htmlspecialchars($settings['tax_rate_inkomane_tin']['setting_value'] ?? '20.0'); ?>" required>
                <span class="addon-suffix">RWF</span>
              </div>
              <div class="description">Tin (Sn) local community INKOMANE tax applied per kilogram.</div>
            </div>

            <div class="form-group">
              <label for="tax_rate_inkomane_coltan">Tantalum (Ta) / Coltan</label>
              <div class="input-addon-group">
                <input type="number" id="tax_rate_inkomane_coltan" name="tax_rate_inkomane_coltan" class="form-control" step="0.1" min="0" value="<?php echo htmlspecialchars($settings['tax_rate_inkomane_coltan']['setting_value'] ?? '40.0'); ?>" required>
                <span class="addon-suffix">RWF</span>
              </div>
              <div class="description">Tantalum (Ta) local community INKOMANE tax applied per kilogram.</div>
            </div>

            <div class="form-group">
              <label for="tax_rate_inkomane_wolframite">Wolframite (W03)</label>
              <div class="input-addon-group">
                <input type="number" id="tax_rate_inkomane_wolframite" name="tax_rate_inkomane_wolframite" class="form-control" step="0.1" min="0" value="<?php echo htmlspecialchars($settings['tax_rate_inkomane_wolframite']['setting_value'] ?? '20.0'); ?>" required>
                <span class="addon-suffix">RWF</span>
              </div>
              <div class="description">Wolframite (W03) local community INKOMANE tax applied per kilogram.</div>
            </div>
          </div>
        </div>

        <!-- Footer Action -->
        <div class="settings-footer-actions">
          <button type="submit" class="btn-sm btn-primary" style="padding: 6px 16px;">
            <svg class="btn-icon btn-icon-white" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Configuration
          </button>
        </div>
        
      </div>
    </form>
    
    <div class="bottom-spacer"></div>
  </div>
</div>

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
</body>
</html>
