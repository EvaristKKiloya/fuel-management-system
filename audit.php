<?php
require_once __DIR__ . '/config.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'log_activity':
                // Log an activity
                $stmt = $pdo->prepare("
                    INSERT INTO audit_logs (user_name, action, module, ip_address, status, details, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $_POST['user_name'] ?? 'System',
                    $_POST['action_type'] ?? '',
                    $_POST['module'] ?? '',
                    $_SERVER['REMOTE_ADDR'],
                    $_POST['status'] ?? 'Success',
                    $_POST['details'] ?? ''
                ]);
                echo json_encode(['success' => true, 'message' => 'Activity logged successfully']);
                exit;
                
            case 'clear_logs':
                // Clear logs older than specified days
                $days = (int)($_POST['days'] ?? 30);
                $stmt = $pdo->prepare("DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
                $stmt->execute([$days]);
                $deleted = $stmt->rowCount();
                echo json_encode(['success' => true, 'message' => "$deleted logs cleared successfully"]);
                exit;
                
            case 'export_logs':
                // Export logs as CSV
                $startDate = $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
                $endDate = $_POST['end_date'] ?? date('Y-m-d');
                $userName = $_POST['user_filter'] ?? '';
                
                $query = "SELECT * FROM audit_logs WHERE DATE(created_at) BETWEEN ? AND ?";
                $params = [$startDate, $endDate];
                
                if ($userName && $userName !== 'All Users') {
                    $query .= " AND user_name = ?";
                    $params[] = $userName;
                }
                
                $query .= " ORDER BY created_at DESC";
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $logs = $stmt->fetchAll();
                
                // Generate CSV
                $filename = 'audit_logs_' . date('Y-m-d_His') . '.csv';
                $filepath = __DIR__ . '/backups/' . $filename;
                
                $fp = fopen($filepath, 'w');
                fputcsv($fp, ['ID', 'Timestamp', 'User', 'Action', 'Module', 'IP Address', 'Status', 'Details']);
                
                foreach ($logs as $log) {
                    fputcsv($fp, [
                        $log['id'],
                        $log['created_at'],
                        $log['user_name'],
                        $log['action'],
                        $log['module'],
                        $log['ip_address'],
                        $log['status'],
                        $log['details']
                    ]);
                }
                fclose($fp);
                
                echo json_encode(['success' => true, 'filename' => $filename, 'message' => 'Logs exported successfully']);
                exit;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Handle GET requests for filtering
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$userFilter = $_GET['user_filter'] ?? '';

$query = "SELECT * FROM audit_logs WHERE DATE(created_at) BETWEEN ? AND ?";
$params = [$startDate, $endDate];

if ($userFilter && $userFilter !== 'All Users') {
    $query .= " AND user_name = ?";
    $params[] = $userFilter;
}

$query .= " ORDER BY created_at DESC LIMIT 100";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get unique users for filter
$usersStmt = $pdo->query("SELECT DISTINCT user_name FROM audit_logs ORDER BY user_name");
$users = $usersStmt->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Audit & Logs</h2>
  
  <div class="row mb-4">
    <div class="col-md-12">
      <div class="card p-4">
        <h5>System Activity Logs</h5>
        <p class="text-muted">Track all system activities and changes</p>
        
        <form method="GET" id="filterForm" class="mb-3">
          <label class="form-label">Filter by Date Range</label>
          <div class="row">
            <div class="col-md-3">
              <input type="date" name="start_date" id="startDate" class="form-control" value="<?= esc($startDate) ?>">
            </div>
            <div class="col-md-3">
              <input type="date" name="end_date" id="endDate" class="form-control" value="<?= esc($endDate) ?>">
            </div>
            <div class="col-md-3">
              <select name="user_filter" id="userFilter" class="form-control">
                <option value="">All Users</option>
                <?php foreach ($users as $user): ?>
                  <option value="<?= esc($user) ?>" <?= $userFilter === $user ? 'selected' : '' ?>>
                    <?= esc($user) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
          </div>
        </form>
        
        <div class="table-responsive">
          <table class="table table-striped mt-3">
            <thead>
              <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Action</th>
                <th>Module</th>
                <th>IP Address</th>
                <th>Status</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody id="logsTable">
              <?php if (empty($logs)): ?>
                <tr>
                  <td colspan="7" class="text-center">No logs found</td>
                </tr>
              <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                  <td><?= esc($log['created_at']) ?></td>
                  <td><?= esc($log['user_name']) ?></td>
                  <td><?= esc($log['action']) ?></td>
                  <td><?= esc($log['module']) ?></td>
                  <td><?= esc($log['ip_address']) ?></td>
                  <td>
                    <span class="badge bg-<?= $log['status'] === 'Success' ? 'success' : 'danger' ?>">
                      <?= esc($log['status']) ?>
                    </span>
                  </td>
                  <td><?= esc($log['details']) ?></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        
        <div class="mt-3">
          <button class="btn btn-primary" id="exportLogsBtn">Export Logs</button>
          <button class="btn btn-warning" id="clearOldLogsBtn">Clear Old Logs (30+ days)</button>
          <button class="btn btn-danger" id="clearAllLogsBtn">Clear All Logs</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Export Logs
    document.getElementById('exportLogsBtn').addEventListener('click', function() {
        const formData = new FormData();
        formData.append('action', 'export_logs');
        formData.append('start_date', document.getElementById('startDate').value);
        formData.append('end_date', document.getElementById('endDate').value);
        formData.append('user_filter', document.getElementById('userFilter').value);
        
        fetch('audit.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message + '\nFile: ' + data.filename);
                // Download the file
                window.location.href = 'backups/' + data.filename;
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error exporting logs: ' + error);
        });
    });
    
    // Clear Old Logs (30+ days)
    document.getElementById('clearOldLogsBtn').addEventListener('click', function() {
        if (confirm('Are you sure you want to clear logs older than 30 days?')) {
            const formData = new FormData();
            formData.append('action', 'clear_logs');
            formData.append('days', '30');
            
            fetch('audit.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error clearing logs: ' + error);
            });
        }
    });
    
    // Clear All Logs
    document.getElementById('clearAllLogsBtn').addEventListener('click', function() {
        if (confirm('Are you sure you want to clear ALL logs? This action cannot be undone!')) {
            const formData = new FormData();
            formData.append('action', 'clear_logs');
            formData.append('days', '0');
            
            fetch('audit.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error clearing logs: ' + error);
            });
        }
    });
});
</script>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
