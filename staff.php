<?php
require_once __DIR__ . '/config.php';

// Create staff table matching the SQL schema
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `staff` (
      `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `staff_code` VARCHAR(50) NOT NULL,
      `name` VARCHAR(150) NOT NULL,
      `position` VARCHAR(100) NOT NULL,
      `phone` VARCHAR(50) DEFAULT NULL,
      `status` ENUM('Active','On Leave','Off Duty') NOT NULL DEFAULT 'Active',
      `station_id` INT(10) UNSIGNED DEFAULT NULL,
      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `staff_code` (`staff_code`),
      KEY `station_id` (`station_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Create trigger for auto-generating staff codes
    try {
        $pdo->exec("DROP TRIGGER IF EXISTS trg_staff_before_insert");
        $pdo->exec("CREATE TRIGGER `trg_staff_before_insert` BEFORE INSERT ON `staff` FOR EACH ROW 
        BEGIN
          DECLARE next_id INT UNSIGNED;
          IF NEW.staff_code IS NULL OR NEW.staff_code = '' THEN
            SET next_id = NEW.id;
            IF next_id IS NULL OR next_id = 0 THEN
              SELECT `AUTO_INCREMENT` INTO next_id
              FROM `information_schema`.`TABLES`
              WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'staff'
              LIMIT 1;
            END IF;
            SET NEW.staff_code = CONCAT('EMP', LPAD(next_id, 3, '0'));
          END IF;
        END");
    } catch (Exception $e) {
        // Trigger might already exist
    }
} catch (Exception $e) {
    // Table might already exist
}

$message = '';
$messageType = '';
$editMode = false;
$editData = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userName = $_SESSION['username'] ?? 'Guest';
    
    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $station_id = !empty($_POST['station_id']) ? (int)$_POST['station_id'] : NULL;
        $phone = trim($_POST['phone'] ?? '');
        $status = $_POST['status'] ?? 'Active';
        
        if (empty($name) || empty($position)) {
            $message = "Name and position are required!";
            $messageType = "danger";
            
            // Log failed attempt
            log_audit($pdo, $userName, AUDIT_ACTION_CREATE, 'Staff', 'Failed', 'Validation error: Missing required fields');
        } else {
            try {
                if ($action === 'edit') {
                    $id = $_POST['id'] ?? 0;
                    $stmt = $pdo->prepare("UPDATE staff SET name = ?, position = ?, station_id = ?, phone = ?, status = ? WHERE id = ?");
                    $stmt->execute([$name, $position, $station_id, $phone, $status, $id]);
                    $message = "Staff updated successfully!";
                    
                    // Log update
                    log_audit($pdo, $userName, AUDIT_ACTION_UPDATE, 'Staff', 'Success', 
                        "Updated staff ID: $id - Name: $name, Position: $position");
                } else {
                    $stmt = $pdo->prepare("INSERT INTO staff (staff_code, name, position, station_id, phone, status) VALUES ('', ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $position, $station_id, $phone, $status]);
                    $message = "Staff added successfully!";
                    
                    // Log creation
                    log_audit($pdo, $userName, AUDIT_ACTION_CREATE, 'Staff', 'Success', 
                        "Created new staff - Name: $name, Position: $position");
                }
                $messageType = "success";
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage();
                $messageType = "danger";
                
                // Log error
                log_audit($pdo, $userName, $action === 'edit' ? AUDIT_ACTION_UPDATE : AUDIT_ACTION_CREATE, 
                    'Staff', 'Failed', 'Error: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        try {
            // Get staff info before deleting
            $stmt = $pdo->prepare("SELECT name, position FROM staff WHERE id = ?");
            $stmt->execute([$id]);
            $staffInfo = $stmt->fetch();
            
            $stmt = $pdo->prepare("DELETE FROM staff WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Staff deleted successfully!";
            $messageType = "success";
            
            // Log deletion
            if ($staffInfo) {
                log_audit($pdo, $userName, AUDIT_ACTION_DELETE, 'Staff', 'Success', 
                    "Deleted staff ID: $id - Name: {$staffInfo['name']}, Position: {$staffInfo['position']}");
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "danger";
            
            // Log error
            log_audit($pdo, $userName, AUDIT_ACTION_DELETE, 'Staff', 'Failed', 'Error: ' . $e->getMessage());
        }
    }
}

// Handle edit mode
if (isset($_GET['edit'])) {
    $editMode = true;
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editData = $stmt->fetch();
}

// Get all staff with station details
$staff = $pdo->query("SELECT s.*, st.name as station_name, st.phone as station_phone, st.manager as station_manager 
                      FROM staff s 
                      LEFT JOIN stations st ON s.station_id = st.id 
                      ORDER BY s.id DESC")->fetchAll();

// Get all stations for dropdown
$stations = $pdo->query("SELECT id, name, phone, manager FROM stations ORDER BY name")->fetchAll();

// Statistics
$totalStaff = count($staff);
$onDuty = count(array_filter($staff, fn($s) => $s['status'] === 'Active'));
$onLeave = count(array_filter($staff, fn($s) => $s['status'] === 'On Leave'));
$newThisMonth = count(array_filter($staff, fn($s) => strtotime($s['created_at']) >= strtotime('first day of this month')));

require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4"><i class="bi bi-people"></i> Staff Management</h2>
  
  <?php if ($message): ?>
  <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= esc($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  
  <div class="row mb-4">
    <div class="col-md-3 mb-3">
      <div class="card p-3 border-0" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: #fff;">
        <h6>Total Staff</h6>
        <h3><?= $totalStaff ?></h3>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card p-3 border-0" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: #fff;">
        <h6>On Duty</h6>
        <h3><?= $onDuty ?></h3>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card p-3 border-0" style="background: linear-gradient(135deg, #ffa502 0%, #ff8c00 100%); color: #fff;">
        <h6>On Leave</h6>
        <h3><?= $onLeave ?></h3>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card p-3 border-0" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: #fff;">
        <h6>New This Month</h6>
        <h3><?= $newThisMonth ?></h3>
      </div>
    </div>
  </div>
  
  <div class="card p-4 mb-4 border-0">
    <h5><?= $editMode ? 'Edit Staff' : 'Add New Staff' ?></h5>
    <form method="POST" class="mt-3">
      <input type="hidden" name="action" value="<?= $editMode ? 'edit' : 'add' ?>">
      <?php if ($editMode): ?>
      <input type="hidden" name="id" value="<?= $editData['id'] ?>">
      <?php endif; ?>
      
      <div class="row">
        <div class="col-md-3 mb-3">
          <label class="form-label"><strong>Name</strong> <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" value="<?= $editMode ? esc($editData['name']) : '' ?>" required>
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label"><strong>Position</strong> <span class="text-danger">*</span></label>
          <select name="position" class="form-control" required>
            <option value="">Select...</option>
            <?php 
            $positions = ['Manager', 'Attendant', 'Supervisor', 'Cashier', 'Mechanic', 'Dispatcher'];
            foreach ($positions as $pos):
            ?>
            <option value="<?= $pos ?>" <?= ($editMode && $editData['position'] === $pos) ? 'selected' : '' ?>><?= $pos ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 mb-3">
          <label class="form-label"><strong>Assign to Station</strong></label>
          <select name="station_id" class="form-control" id="stationSelect" onchange="updateStationContact()">
            <option value="">-- Select Station --</option>
            <?php foreach ($stations as $station): ?>
            <option value="<?= $station['id'] ?>" 
                    data-phone="<?= esc($station['phone']) ?>" 
                    data-manager="<?= esc($station['manager']) ?>"
                    <?= ($editMode && $editData['station_id'] == $station['id']) ? 'selected' : '' ?>>
              <?= esc($station['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label"><strong>Contact</strong></label>
          <input type="text" name="phone" class="form-control" id="contactInput" value="<?= $editMode ? esc($editData['phone'] ?? '') : '' ?>" placeholder="Enter contact number">
        </div>
        <div class="col-md-2 mb-3">
          <label class="form-label"><strong>Status</strong></label>
          <select name="status" class="form-control">
            <option value="Active" <?= ($editMode && $editData['status'] === 'Active') ? 'selected' : '' ?>>Active</option>
            <option value="On Leave" <?= ($editMode && $editData['status'] === 'On Leave') ? 'selected' : '' ?>>On Leave</option>
            <option value="Off Duty" <?= ($editMode && $editData['status'] === 'Off Duty') ? 'selected' : '' ?>>Off Duty</option>
          </select>
        </div>
      </div>

      <!-- Station Details Display -->
      <div class="alert alert-info mt-3 mb-3" id="stationDetails" style="display: none;">
        <strong>Station Details:</strong><br>
        <span id="stationManager">Manager: <em>Select a station</em></span><br>
        <span id="stationPhone">Phone: <em>Select a station</em></span>
      </div>

      <div class="mt-3">
        <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> <?= $editMode ? 'Update' : 'Add' ?> Staff</button>
        <?php if ($editMode): ?>
        <a href="staff.php" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Cancel</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
  
  <div class="card p-4 border-0">
    <h5><i class="bi bi-list"></i> Staff Directory</h5>
    <div class="table-responsive">
      <table class="table table-striped table-hover mt-3">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Position</th>
            <th>Assigned Station</th>
            <th>Station Manager</th>
            <th>Contact</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($staff)): ?>
          <tr>
            <td colspan="8" class="text-center text-muted py-4">
              <i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.3;"></i>
              <p class="mt-2">No staff members found. Add your first staff member above.</p>
            </td>
          </tr>
          <?php else: ?>
          <?php foreach($staff as $s): ?>
          <tr>
            <td><span class="badge bg-light text-dark">EMP<?= str_pad($s['id'], 3, '0', STR_PAD_LEFT) ?></span></td>
            <td><strong><?= esc($s['name']) ?></strong></td>
            <td><span class="badge bg-primary"><?= esc($s['position']) ?></span></td>
            <td>
              <?php if ($s['station_id']): ?>
                <a href="station_details.php?id=<?= $s['station_id'] ?>" class="text-decoration-none">
                  <?= esc($s['station_name']) ?>
                </a>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td><?= esc($s['station_manager'] ?? '—') ?></td>
            <td><?= esc($s['phone'] ?? '—') ?></td>
            <td>
              <?php if ($s['status'] === 'Active'): ?>
              <span class="badge bg-success"><i class="bi bi-check-circle"></i> Active</span>
              <?php elseif ($s['status'] === 'On Leave'): ?>
              <span class="badge bg-warning"><i class="bi bi-exclamation-circle"></i> On Leave</span>
              <?php else: ?>
              <span class="badge bg-secondary"><i class="bi bi-x-circle"></i> Off Duty</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="?edit=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this staff member?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
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

<script>
function updateStationContact() {
  const stationSelect = document.getElementById('stationSelect');
  const stationDetails = document.getElementById('stationDetails');
  const stationManager = document.getElementById('stationManager');
  const stationPhone = document.getElementById('stationPhone');
  
  const selectedOption = stationSelect.options[stationSelect.selectedIndex];
  
  if (selectedOption.value) {
    const phone = selectedOption.getAttribute('data-phone');
    const manager = selectedOption.getAttribute('data-manager');
    
    // Display station details
    stationDetails.style.display = 'block';
    stationManager.innerHTML = 'Manager: <strong>' + manager + '</strong>';
    stationPhone.innerHTML = 'Phone: <strong>' + phone + '</strong>';
  } else {
    stationDetails.style.display = 'none';
  }
}

// Initialize on page load if station is pre-selected
document.addEventListener('DOMContentLoaded', function() {
  updateStationContact();
  
  // Auto-dismiss alerts
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(alert => {
    setTimeout(() => {
      const bsAlert = new bootstrap.Alert(alert);
      bsAlert.close();
    }, 5000);
  });
});
</script>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

