<?php
require_once __DIR__ . '/inc/header.php';

$message = '';
$messageType = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? sanitize($_POST['action']) : '';
    $userName = $_SESSION['username'] ?? 'Guest';
    
    switch ($action) {
        case 'save':
            if (saveSettings($pdo, $_POST)) {
                $message = 'Settings saved successfully!';
                $messageType = 'success';
                
                // Log settings update
                log_audit($pdo, $userName, AUDIT_ACTION_UPDATE, 'Settings', 'Success', 
                    'Updated system settings');
            } else {
                $message = 'Failed to save settings.';
                $messageType = 'danger';
                
                // Log failure
                log_audit($pdo, $userName, AUDIT_ACTION_UPDATE, 'Settings', 'Failed', 
                    'Failed to save settings');
            }
            break;
            
        case 'clear_cache':
            if (clearSystemCache()) {
                $message = 'Cache cleared successfully!';
                $messageType = 'success';
                
                // Log cache clear
                log_audit($pdo, $userName, 'Clear Cache', 'Settings', 'Success', 
                    'Cleared system cache');
            } else {
                $message = 'Failed to clear cache.';
                $messageType = 'danger';
                
                // Log failure
                log_audit($pdo, $userName, 'Clear Cache', 'Settings', 'Failed', 
                    'Failed to clear cache');
            }
            break;
            
        case 'run_diagnostics':
            $diagnostics = runDiagnostics($pdo);
            $message = 'Diagnostics completed.';
            $messageType = 'info';
            
            // Log diagnostics
            log_audit($pdo, $userName, 'Run Diagnostics', 'Settings', 'Success', 
                'Ran system diagnostics');
            break;
            
        case 'reset':
            if (resetSystem($pdo)) {
                $message = 'System reset completed!';
                $messageType = 'success';
                
                // Log system reset
                log_audit($pdo, $userName, 'System Reset', 'Settings', 'Success', 
                    'System reset completed');
            } else {
                $message = 'Failed to reset system.';
                $messageType = 'danger';
                
                // Log failure
                log_audit($pdo, $userName, 'System Reset', 'Settings', 'Failed', 
                    'Failed to reset system');
            }
            break;
    }
}

// Helper Functions
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function saveSettings($pdo, $data) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE,
            setting_value LONGTEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        $settingsToSave = array(
            'company_name' => sanitize($data['company_name'] ?? ''),
            'company_email' => sanitize($data['company_email'] ?? ''),
            'company_phone' => sanitize($data['company_phone'] ?? ''),
            'currency' => sanitize($data['currency'] ?? ''),
            'timezone' => sanitize($data['timezone'] ?? ''),
        );
        
        foreach ($settingsToSave as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) 
                                   VALUES (:key, :value) 
                                   ON DUPLICATE KEY UPDATE setting_value = :value");
            $stmt->execute([':key' => $key, ':value' => $value]);
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function clearSystemCache() {
    try {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function runDiagnostics($pdo) {
    $diagnostics = array();
    
    try {
        $stmt = $pdo->query("SELECT 1");
        $diagnostics['database'] = array('status' => 'OK', 'message' => 'Database OK');
    } catch (Exception $e) {
        $diagnostics['database'] = array('status' => 'ERROR', 'message' => 'Database Error');
    }
    
    $diagnostics['php_version'] = array('status' => 'INFO', 'message' => 'PHP ' . PHP_VERSION);
    
    $required_extensions = array('pdo', 'pdo_mysql');
    foreach ($required_extensions as $ext) {
        if (extension_loaded($ext)) {
            $diagnostics['extension_' . $ext] = array('status' => 'OK', 'message' => ucfirst($ext) . ' OK');
        } else {
            $diagnostics['extension_' . $ext] = array('status' => 'WARNING', 'message' => ucfirst($ext) . ' Missing');
        }
    }
    
    return $diagnostics;
}

function resetSystem($pdo) {
    try {
        $pdo->exec("TRUNCATE TABLE settings");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

?>

<div class="container-fluid">
  <h2 class="mb-4">System Settings</h2>
  
  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <div class="row">
    <div class="col-md-8">
      <div class="card p-4 mb-4">
        <h5>General Settings</h5>
        <form method="POST">
          <input type="hidden" name="action" value="save">
          
          <div class="mb-3">
            <label class="form-label">Company Name</label>
            <input type="text" class="form-control" name="company_name" value="Fuel Management System">
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="company_email" value="admin@fuelmanagement.co.tz">
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" class="form-control" name="company_phone" value="+255 700 000 000">
          </div>
          <div class="mb-3">
            <label class="form-label">Currency</label>
            <select class="form-control" name="currency">
              <option selected>TSh - Tanzanian Shilling</option>
              <option>USD - US Dollar</option>
              <option>EUR - Euro</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Timezone</label>
            <select class="form-control" name="timezone">
              <option selected>Africa/Dar_es_Salaam</option>
              <option>UTC</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
      </div>
      
      <div class="card p-4">
        <h5>Notification Settings</h5>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" checked>
          <label class="form-check-label">Email notifications for low fuel alerts</label>
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" checked>
          <label class="form-check-label">SMS notifications for deliveries</label>
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox">
          <label class="form-check-label">Daily sales report</label>
        </div>
        <button class="btn btn-primary">Update Notifications</button>
      </div>
    </div>
    
    <div class="col-md-4">
      <div class="card p-4 mb-3">
        <h6>System Info</h6>
        <p class="mb-1"><strong>Version:</strong> 1.0.0</p>
        <p class="mb-1"><strong>Last Update:</strong> <?= date('Y-m-d') ?></p>
        <p class="mb-1"><strong>Server:</strong> XAMPP</p>
      </div>
      
      <div class="card p-4">
        <h6>Quick Actions</h6>
        <form method="POST" class="mb-2">
          <input type="hidden" name="action" value="clear_cache">
          <button type="submit" class="btn btn-secondary btn-sm w-100 mb-2">Clear Cache</button>
        </form>
        <form method="POST" class="mb-2">
          <input type="hidden" name="action" value="run_diagnostics">
          <button type="submit" class="btn btn-secondary btn-sm w-100 mb-2">Run Diagnostics</button>
        </form>
        <form method="POST">
          <input type="hidden" name="action" value="reset">
          <button type="submit" class="btn btn-danger btn-sm w-100" onclick="return confirm('Are you sure?')">Reset System</button>
        </form>
      </div>
    </div>
  </div>
  
  <?php if (!empty($diagnostics)): ?>
  <div class="card p-4 mt-4">
    <h5>Diagnostics Results</h5>
    <table class="table table-sm">
      <thead>
        <tr>
          <th>Check</th>
          <th>Status</th>
          <th>Message</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($diagnostics as $key => $diag): ?>
        <tr>
          <td><?= ucwords(str_replace('_', ' ', $key)) ?></td>
          <td>
            <span class="badge bg-<?= $diag['status'] === 'OK' ? 'success' : ($diag['status'] === 'ERROR' ? 'danger' : 'warning') ?>">
              <?= $diag['status'] ?>
            </span>
          </td>
          <td><?= esc($diag['message']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
