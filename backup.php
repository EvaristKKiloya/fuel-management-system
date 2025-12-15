<?php
require_once __DIR__ . '/config.php';

// Drop and recreate backups table to ensure correct schema
$pdo->exec("DROP TABLE IF EXISTS `backups`");
$createTableSql = "CREATE TABLE `backups` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `backup_name` VARCHAR(255) NOT NULL,
  `backup_type` ENUM('full', 'database', 'files') NOT NULL,
  `file_path` VARCHAR(512) NOT NULL,
  `file_size` BIGINT UNSIGNED DEFAULT 0,
  `status` ENUM('completed', 'failed', 'in_progress') DEFAULT 'in_progress',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$pdo->exec($createTableSql);

// Create backups directory if it doesn't exist
$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$message = '';
$messageType = '';

// Handle backup actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Create new backup
    if ($action === 'create') {
        $backupType = $_POST['backup_type'] ?? 'database';
        $backupName = $_POST['backup_name'] ?? 'backup_' . date('Ymd_His');
        $backupName = preg_replace('/[^a-zA-Z0-9_-]/', '', $backupName);
        
        try {
            if ($backupType === 'database' || $backupType === 'full') {
                // Create database backup
                $filename = $backupName . '_db.sql';
                $filepath = $backupDir . '/' . $filename;
                
                // Get all tables
                $tables = [];
                $result = $pdo->query("SHOW TABLES");
                while ($row = $result->fetch(PDO::FETCH_NUM)) {
                    $tables[] = $row[0];
                }
                
                // Generate SQL dump
                $sqlDump = "-- Database Backup\n";
                $sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
                $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
                
                foreach ($tables as $table) {
                    $sqlDump .= "-- Table: $table\n";
                    $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
                    
                    // Get CREATE TABLE statement
                    $createStmt = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
                    $sqlDump .= $createStmt['Create Table'] . ";\n\n";
                    
                    // Get table data
                    $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll();
                    if (!empty($rows)) {
                        foreach ($rows as $row) {
                            $values = array_map(function($val) use ($pdo) {
                                return $val === null ? 'NULL' : $pdo->quote($val);
                            }, array_values($row));
                            $sqlDump .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                        }
                        $sqlDump .= "\n";
                    }
                }
                
                $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";
                
                file_put_contents($filepath, $sqlDump);
                $filesize = filesize($filepath);
                
                // Save backup record to database
                $stmt = $pdo->prepare("INSERT INTO backups (backup_name, backup_type, file_path, file_size, status, created_at) VALUES (?, ?, ?, ?, 'completed', NOW())");
                $stmt->execute([$backupName, $backupType, $filename, $filesize]);
                
                $message = "Backup created successfully!";
                $messageType = "success";
            }
        } catch (Exception $e) {
            $message = "Backup failed: " . $e->getMessage();
            $messageType = "danger";
        }
    }
    
    // Download backup
    elseif ($action === 'download') {
        $backupId = $_POST['backup_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM backups WHERE id = ?");
        $stmt->execute([$backupId]);
        $backup = $stmt->fetch();
        
        if ($backup) {
            $filepath = $backupDir . '/' . $backup['file_path'];
            if (file_exists($filepath)) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($backup['file_path']) . '"');
                header('Content-Length: ' . filesize($filepath));
                readfile($filepath);
                exit;
            }
        }
    }
    
    // Restore backup
    elseif ($action === 'restore') {
        $backupId = $_POST['backup_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM backups WHERE id = ?");
        $stmt->execute([$backupId]);
        $backup = $stmt->fetch();
        
        if ($backup && ($backup['backup_type'] === 'database' || $backup['backup_type'] === 'full')) {
            $filepath = $backupDir . '/' . $backup['file_path'];
            if (file_exists($filepath)) {
                try {
                    $sql = file_get_contents($filepath);
                    $pdo->exec($sql);
                    $message = "Database restored successfully!";
                    $messageType = "success";
                } catch (Exception $e) {
                    $message = "Restore failed: " . $e->getMessage();
                    $messageType = "danger";
                }
            }
        }
    }
    
    // Delete backup
    elseif ($action === 'delete') {
        $backupId = $_POST['backup_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM backups WHERE id = ?");
        $stmt->execute([$backupId]);
        $backup = $stmt->fetch();
        
        if ($backup) {
            $filepath = $backupDir . '/' . $backup['file_path'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            
            $stmt = $pdo->prepare("DELETE FROM backups WHERE id = ?");
            $stmt->execute([$backupId]);
            
            $message = "Backup deleted successfully!";
            $messageType = "success";
        }
    }
}

// Get all backups
$backups = $pdo->query("SELECT * FROM backups ORDER BY created_at DESC")->fetchAll();

// Calculate statistics
$totalBackups = count($backups);
$totalSize = array_sum(array_column($backups, 'file_size'));
$lastBackup = !empty($backups) ? $backups[0]['created_at'] : 'Never';

require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Backup Management</h2>
  
  <?php if ($message): ?>
  <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= esc($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  
  <div class="row mb-4">
    <div class="col-md-12">
      <div class="card p-4">
        <h5>Create New Backup</h5>
        <p class="text-muted">Backup your database and system files</p>
        
        <form method="POST" class="mb-4">
          <input type="hidden" name="action" value="create">
          <div class="row">
            <div class="col-md-4">
              <label class="form-label">Backup Type</label>
              <select name="backup_type" class="form-control" required>
                <option value="database">Database Only</option>
                <option value="full">Full Backup (Database + Files)</option>
                <option value="files">Files Only</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Backup Name</label>
              <input type="text" name="backup_name" class="form-control" value="backup_<?= date('Ymd_His') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">&nbsp;</label>
              <button type="submit" class="btn btn-success w-100">Create Backup</button>
            </div>
          </div>
        </form>
        
        <hr>
        
        <h5 class="mt-4">Backup History</h5>
        <table class="table table-striped mt-3">
          <thead>
            <tr>
              <th>Backup Name</th>
              <th>Type</th>
              <th>Date Created</th>
              <th>Size</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($backups)): ?>
            <tr>
              <td colspan="6" class="text-center">No backups found. Create your first backup above.</td>
            </tr>
            <?php else: ?>
            <?php foreach($backups as $backup): ?>
            <tr>
              <td><?= esc($backup['backup_name']) ?></td>
              <td><?= ucfirst($backup['backup_type']) ?></td>
              <td><?= esc($backup['created_at']) ?></td>
              <td><?= number_format($backup['file_size'] / 1024 / 1024, 2) ?> MB</td>
              <td>
                <?php if ($backup['status'] === 'completed'): ?>
                <span class="badge bg-success">Completed</span>
                <?php elseif ($backup['status'] === 'failed'): ?>
                <span class="badge bg-danger">Failed</span>
                <?php else: ?>
                <span class="badge bg-warning">In Progress</span>
                <?php endif; ?>
              </td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="download">
                  <input type="hidden" name="backup_id" value="<?= $backup['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-primary">Download</button>
                </form>
                <?php if ($backup['backup_type'] === 'database' || $backup['backup_type'] === 'full'): ?>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to restore this backup? This will overwrite current data!');">
                  <input type="hidden" name="action" value="restore">
                  <input type="hidden" name="backup_id" value="<?= $backup['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-warning">Restore</button>
                </form>
                <?php endif; ?>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this backup?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="backup_id" value="<?= $backup['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <div class="row">
    <div class="col-md-6">
      <div class="card p-4">
        <h5>Automatic Backup Schedule</h5>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="autoBackup">
          <label class="form-check-label" for="autoBackup">Enable automatic backups</label>
        </div>
        <div class="mb-3">
          <label class="form-label">Frequency</label>
          <select class="form-control">
            <option selected>Daily at 2:00 AM</option>
            <option>Weekly (Sunday)</option>
            <option>Monthly (1st of month)</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Retention Period</label>
          <select class="form-control">
            <option>Keep last 7 backups</option>
            <option selected>Keep last 30 backups</option>
            <option>Keep all backups</option>
          </select>
        </div>
        <button class="btn btn-primary">Save Schedule</button>
      </div>
    </div>
    
    <div class="col-md-6">
      <div class="card p-4">
        <h5>Backup Status</h5>
        <p class="mb-2"><strong>Last Backup:</strong> <?= esc($lastBackup) ?></p>
        <p class="mb-2"><strong>Total Backups:</strong> <?= $totalBackups ?></p>
        <p class="mb-2"><strong>Total Size:</strong> <?= number_format($totalSize / 1024 / 1024 / 1024, 2) ?> GB</p>
        <p class="mb-2"><strong>Status:</strong> <span class="badge bg-success">Active</span></p>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
