<?php
require_once __DIR__ . '/config.php';

// Create vehicles table if it doesn't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `registration` varchar(20) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `capacity` int DEFAULT NULL,
  `status` enum('Available','In Transit','Maintenance','Inactive') DEFAULT 'Available',
  `location` varchar(100) DEFAULT NULL,
  `next_service` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `registration` (`registration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$message = '';
$messageType = '';
$editMode = false;
$editData = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userName = $_SESSION['username'] ?? 'Guest';
    
    if ($action === 'add' || $action === 'edit') {
        $registration = strtoupper(trim($_POST['registration'] ?? ''));
        $type = trim($_POST['type'] ?? 'Tanker');
        $capacity = intval($_POST['capacity'] ?? 0);
        $status = $_POST['status'] ?? 'Available';
        $location = trim($_POST['location'] ?? '');
        $next_service = $_POST['next_service'] ?? null;
        
        if (empty($registration)) {
            $message = "Registration number is required!";
            $messageType = "danger";
            
            // Log failed attempt
            log_audit($pdo, $userName, AUDIT_ACTION_CREATE, 'Vehicles', 'Failed', 'Validation error: Registration number required');
        } else {
            try {
                if ($action === 'edit') {
                    $id = $_POST['id'] ?? 0;
                    $stmt = $pdo->prepare("UPDATE vehicles SET registration = ?, type = ?, capacity = ?, status = ?, location = ?, next_service = ? WHERE id = ?");
                    $stmt->execute([$registration, $type, $capacity, $status, $location, $next_service, $id]);
                    $message = "Vehicle updated successfully!";
                    
                    // Log update
                    log_audit($pdo, $userName, AUDIT_ACTION_UPDATE, 'Vehicles', 'Success', 
                        "Updated vehicle ID: $id - Registration: $registration, Status: $status");
                } else {
                    $stmt = $pdo->prepare("INSERT INTO vehicles (registration, type, capacity, status, location, next_service) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$registration, $type, $capacity, $status, $location, $next_service]);
                    $message = "Vehicle added successfully!";
                    
                    // Log creation
                    log_audit($pdo, $userName, AUDIT_ACTION_CREATE, 'Vehicles', 'Success', 
                        "Created vehicle - Registration: $registration, Type: $type");
                }
                $messageType = "success";
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage();
                $messageType = "danger";
                
                // Log error
                log_audit($pdo, $userName, $action === 'edit' ? AUDIT_ACTION_UPDATE : AUDIT_ACTION_CREATE, 
                    'Vehicles', 'Failed', 'Error: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        try {
            // Get vehicle info before deleting
            $stmt = $pdo->prepare("SELECT registration, type FROM vehicles WHERE id = ?");
            $stmt->execute([$id]);
            $vehicleInfo = $stmt->fetch();
            
            $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Vehicle deleted successfully!";
            $messageType = "success";
            
            // Log deletion
            if ($vehicleInfo) {
                log_audit($pdo, $userName, AUDIT_ACTION_DELETE, 'Vehicles', 'Success', 
                    "Deleted vehicle ID: $id - Registration: {$vehicleInfo['registration']}");
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "danger";
            
            // Log error
            log_audit($pdo, $userName, AUDIT_ACTION_DELETE, 'Vehicles', 'Failed', 'Error: ' . $e->getMessage());
        }
    }
}

// Handle edit mode
if (isset($_GET['edit'])) {
    $editMode = true;
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editData = $stmt->fetch();
}

// Get all vehicles
$vehicles = $pdo->query("SELECT * FROM vehicles ORDER BY id DESC")->fetchAll();

require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Fleet Management</h2>
  
  <?php if ($message): ?>
  <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= esc($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  
  <div class="row mb-4">
    <div class="col-md-12">
      <div class="card p-4 mb-4">
        <h5><?= $editMode ? 'Edit Vehicle' : 'Add New Vehicle' ?></h5>
        <form method="POST" class="mt-3">
          <input type="hidden" name="action" value="<?= $editMode ? 'edit' : 'add' ?>">
          <?php if ($editMode): ?>
          <input type="hidden" name="id" value="<?= $editData['id'] ?>">
          <?php endif; ?>
          
          <div class="row">
            <div class="col-md-2">
              <label class="form-label">Registration *</label>
              <input type="text" name="registration" class="form-control" value="<?= $editMode ? esc($editData['registration']) : '' ?>" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Type</label>
              <select name="type" class="form-control">
                <option value="Tanker" <?= ($editMode && $editData['type'] === 'Tanker') ? 'selected' : '' ?>>Tanker</option>
                <option value="Truck" <?= ($editMode && $editData['type'] === 'Truck') ? 'selected' : '' ?>>Truck</option>
                <option value="Van" <?= ($editMode && $editData['type'] === 'Van') ? 'selected' : '' ?>>Van</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Capacity (L)</label>
              <input type="number" name="capacity" class="form-control" value="<?= $editMode ? $editData['capacity'] : '' ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label">Status</label>
              <select name="status" class="form-control">
                <option value="Available" <?= ($editMode && $editData['status'] === 'Available') ? 'selected' : '' ?>>Available</option>
                <option value="In Transit" <?= ($editMode && $editData['status'] === 'In Transit') ? 'selected' : '' ?>>In Transit</option>
                <option value="Maintenance" <?= ($editMode && $editData['status'] === 'Maintenance') ? 'selected' : '' ?>>Maintenance</option>
                <option value="Inactive" <?= ($editMode && $editData['status'] === 'Inactive') ? 'selected' : '' ?>>Inactive</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Location</label>
              <input type="text" name="location" class="form-control" value="<?= $editMode ? esc($editData['location']) : '' ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label">Next Service</label>
              <input type="date" name="next_service" class="form-control" value="<?= $editMode ? $editData['next_service'] : '' ?>">
            </div>
          </div>
          <button type="submit" class="btn btn-success mt-3"><?= $editMode ? 'Update Vehicle' : 'Add Vehicle' ?></button>
          <?php if ($editMode): ?>
          <a href="vehicles.php" class="btn btn-secondary mt-3">Cancel Edit</a>
          <?php endif; ?>
        </form>
      </div>
      
      <div class="card p-4">
        <h5>Fleet Overview</h5>
        <p class="text-muted">Manage fuel delivery trucks and vehicles</p>
        
        <table class="table table-striped mt-3">
          <thead>
            <tr>
              <th>Truck ID</th>
              <th>Registration</th>
              <th>Type</th>
              <th>Capacity (L)</th>
              <th>Status</th>
              <th>Current Location</th>
              <th>Next Service</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($vehicles)): ?>
            <tr>
              <td colspan="8" class="text-center">No vehicles found. Add your first vehicle above.</td>
            </tr>
            <?php else: ?>
            <?php foreach($vehicles as $v): ?>
            <tr>
              <td>TRK-<?= str_pad($v['id'], 3, '0', STR_PAD_LEFT) ?></td>
              <td><?= esc($v['registration']) ?></td>
              <td><?= esc($v['type']) ?></td>
              <td><?= number_format($v['capacity']) ?></td>
              <td>
                <?php 
                $statusColors = [
                  'Available' => 'success',
                  'In Transit' => 'info',
                  'Maintenance' => 'warning',
                  'Inactive' => 'secondary'
                ];
                $color = $statusColors[$v['status']] ?? 'secondary';
                ?>
                <span class="badge bg-<?= $color ?>"><?= esc($v['status']) ?></span>
              </td>
              <td><?= esc($v['location']) ?></td>
              <td><?= $v['next_service'] ? date('Y-m-d', strtotime($v['next_service'])) : 'Not set' ?></td>
              <td>
                <a href="?edit=<?= $v['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this vehicle?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $v['id'] ?>">
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
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
