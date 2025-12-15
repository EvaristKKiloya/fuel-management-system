<?php
require_once __DIR__ . '/inc/header.php';

$message = '';
$messageType = '';

// Create deliveries table if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS deliveries (
        id int NOT NULL AUTO_INCREMENT,
        station_id int DEFAULT NULL,
        fuel_type varchar(20) DEFAULT NULL,
        liters decimal(10,2) DEFAULT NULL,
        amount decimal(10,2) DEFAULT NULL,
        delivery_date date DEFAULT NULL,
        supplier varchar(100) DEFAULT NULL,
        status enum('Scheduled','In Transit','Delivered','Cancelled') DEFAULT 'Scheduled',
        created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY station_id (station_id),
        CONSTRAINT deliveries_ibfk_1 FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // Table might already exist
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userName = $_SESSION['username'] ?? 'Guest';
    
    if ($action === 'create') {
        try {
            $stmt = $pdo->prepare("INSERT INTO deliveries (station_id, fuel_type, liters, amount, delivery_date, supplier, status) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['station_id'],
                $_POST['fuel_type'],
                $_POST['liters'],
                $_POST['amount'],
                $_POST['delivery_date'],
                $_POST['supplier'] ?? '',
                $_POST['status'] ?? 'Scheduled'
            ]);
            $message = 'Delivery scheduled successfully!';
            $messageType = 'success';
            
            // Log creation
            log_audit($pdo, $userName, AUDIT_ACTION_CREATE, 'Deliveries', 'Success', 
                "Created delivery - Fuel: {$_POST['fuel_type']}, Liters: {$_POST['liters']}, Date: {$_POST['delivery_date']}");
        } catch (Exception $e) {
            $message = 'Error creating delivery: ' . $e->getMessage();
            $messageType = 'danger';
            
            // Log error
            log_audit($pdo, $userName, AUDIT_ACTION_CREATE, 'Deliveries', 'Failed', 'Error: ' . $e->getMessage());
        }
    }
    
    if ($action === 'update') {
        try {
            $stmt = $pdo->prepare("UPDATE deliveries SET station_id=?, fuel_type=?, liters=?, amount=?, delivery_date=?, supplier=?, status=? 
                                   WHERE id=?");
            $stmt->execute([
                $_POST['station_id'],
                $_POST['fuel_type'],
                $_POST['liters'],
                $_POST['amount'],
                $_POST['delivery_date'],
                $_POST['supplier'] ?? '',
                $_POST['status'],
                $_POST['delivery_id']
            ]);
            $message = 'Delivery updated successfully!';
            $messageType = 'success';
            
            // Log update
            log_audit($pdo, $userName, AUDIT_ACTION_UPDATE, 'Deliveries', 'Success', 
                "Updated delivery ID: {$_POST['delivery_id']} - Status: {$_POST['status']}");
        } catch (Exception $e) {
            $message = 'Error updating delivery: ' . $e->getMessage();
            $messageType = 'danger';
            
            // Log error
            log_audit($pdo, $userName, AUDIT_ACTION_UPDATE, 'Deliveries', 'Failed', 'Error: ' . $e->getMessage());
        }
    }
    
    if ($action === 'delete') {
        try {
            // Get delivery info before deleting
            $stmt = $pdo->prepare("SELECT fuel_type, liters, delivery_date FROM deliveries WHERE id=?");
            $stmt->execute([$_POST['delivery_id']]);
            $deliveryInfo = $stmt->fetch();
            
            $stmt = $pdo->prepare("DELETE FROM deliveries WHERE id=?");
            $stmt->execute([$_POST['delivery_id']]);
            $message = 'Delivery deleted successfully!';
            $messageType = 'success';
            
            // Log deletion
            if ($deliveryInfo) {
                log_audit($pdo, $userName, AUDIT_ACTION_DELETE, 'Deliveries', 'Success', 
                    "Deleted delivery ID: {$_POST['delivery_id']} - Fuel: {$deliveryInfo['fuel_type']}, Liters: {$deliveryInfo['liters']}");
            }
        } catch (Exception $e) {
            $message = 'Error deleting delivery: ' . $e->getMessage();
            $messageType = 'danger';
            
            // Log error
            log_audit($pdo, $userName, AUDIT_ACTION_DELETE, 'Deliveries', 'Failed', 'Error: ' . $e->getMessage());
        }
    }
}

// Get filter parameters
$filter_status = $_GET['status'] ?? '';
$filter_fuel_type = $_GET['fuel_type'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

// Build query with filters
$query = "SELECT d.*, s.name as station_name, d.liters as quantity
          FROM deliveries d 
          LEFT JOIN stations s ON d.station_id = s.id 
          WHERE 1=1";
$params = [];

if ($filter_status) {
    $query .= " AND d.status = ?";
    $params[] = $filter_status;
}
if ($filter_fuel_type) {
    $query .= " AND d.fuel_type = ?";
    $params[] = $filter_fuel_type;
}
if ($filter_date_from) {
    $query .= " AND d.delivery_date >= ?";
    $params[] = $filter_date_from;
}
if ($filter_date_to) {
    $query .= " AND d.delivery_date <= ?";
    $params[] = $filter_date_to;
}

$query .= " ORDER BY d.delivery_date DESC, d.id DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $deliveries = $stmt->fetchAll();
} catch (Exception $e) {
    $deliveries = [];
}

// Get stations for dropdown
try {
    $stations = $pdo->query("SELECT id, name FROM stations ORDER BY name")->fetchAll();
} catch (Exception $e) {
    $stations = [];
}

// Get trucks/vehicles for dropdown
try {
    $trucks = $pdo->query("SELECT id, plate_number FROM vehicles WHERE type='Truck' ORDER BY plate_number")->fetchAll();
} catch (Exception $e) {
    $trucks = [];
}

?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Deliveries & Trucks</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDeliveryModal">
      <i class="bi bi-plus-circle"></i> Schedule New Delivery
    </button>
  </div>
  
  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= esc($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <!-- Filters -->
  <div class="card p-3 mb-4">
    <form method="GET" class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <option value="">All Statuses</option>
          <option value="Scheduled" <?= $filter_status === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
          <option value="In Transit" <?= $filter_status === 'In Transit' ? 'selected' : '' ?>>In Transit</option>
          <option value="Completed" <?= $filter_status === 'Completed' ? 'selected' : '' ?>>Completed</option>
          <option value="Cancelled" <?= $filter_status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Fuel Type</label>
        <select name="fuel_type" class="form-control">
          <option value="">All Types</option>
          <option value="Petrol" <?= $filter_fuel_type === 'Petrol' ? 'selected' : '' ?>>Petrol</option>
          <option value="Diesel" <?= $filter_fuel_type === 'Diesel' ? 'selected' : '' ?>>Diesel</option>
          <option value="Gasoline" <?= $filter_fuel_type === 'Gasoline' ? 'selected' : '' ?>>Gasoline</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">From Date</label>
        <input type="date" name="date_from" class="form-control" value="<?= esc($filter_date_from) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">To Date</label>
        <input type="date" name="date_to" class="form-control" value="<?= esc($filter_date_to) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">&nbsp;</label>
        <div>
          <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
      </div>
    </form>
  </div>
  
  <!-- Deliveries Table -->
  <div class="card p-4">
    <h5>Deliveries List (<?= count($deliveries) ?>)</h5>
    
    <div class="table-responsive">
      <table class="table table-striped table-hover mt-3">
        <thead>
          <tr>
            <th>ID</th>
            <th>Station</th>
            <th>Truck</th>
            <th>Fuel Type</th>
            <th>Quantity (L)</th>
            <th>Delivery Date</th>
            <th>Status</th>
            <th>Notes</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($deliveries)): ?>
            <tr>
              <td colspan="9" class="text-center text-muted py-4">
                No deliveries found. Click "Schedule New Delivery" to add one.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach($deliveries as $delivery): ?>
            <tr>
              <td>#<?= $delivery['id'] ?></td>
              <td><?= esc($delivery['station_name'] ?? 'N/A') ?></td>
              <td><?= esc($delivery['truck_plate'] ?? 'N/A') ?></td>
              <td><span class="badge bg-info"><?= esc($delivery['fuel_type']) ?></span></td>
              <td><?= number_format($delivery['quantity'], 2) ?></td>
              <td><?= date('Y-m-d', strtotime($delivery['delivery_date'])) ?></td>
              <td>
                <?php
                $badge_class = 'secondary';
                if ($delivery['status'] === 'Completed') $badge_class = 'success';
                elseif ($delivery['status'] === 'In Transit') $badge_class = 'warning';
                elseif ($delivery['status'] === 'Scheduled') $badge_class = 'primary';
                elseif ($delivery['status'] === 'Cancelled') $badge_class = 'danger';
                ?>
                <span class="badge bg-<?= $badge_class ?>"><?= esc($delivery['status']) ?></span>
              </td>
              <td><?= esc(substr($delivery['notes'] ?? '', 0, 30)) ?><?= strlen($delivery['notes'] ?? '') > 30 ? '...' : '' ?></td>
              <td>
                <button class="btn btn-sm btn-outline-primary" onclick="editDelivery(<?= htmlspecialchars(json_encode($delivery), ENT_QUOTES) ?>)">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteDelivery(<?= $delivery['id'] ?>)">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add Delivery Modal -->
<div class="modal fade" id="addDeliveryModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Schedule New Delivery</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="create">
          
          <div class="mb-3">
            <label class="form-label">Station <span class="text-danger">*</span></label>
            <select name="station_id" class="form-control" required>
              <option value="">Select Station</option>
              <?php foreach($stations as $station): ?>
                <option value="<?= $station['id'] ?>"><?= esc($station['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Truck <span class="text-danger">*</span></label>
            <select name="truck_id" class="form-control" required>
              <option value="">Select Truck</option>
              <?php foreach($trucks as $truck): ?>
                <option value="<?= $truck['id'] ?>"><?= esc($truck['plate_number']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Fuel Type <span class="text-danger">*</span></label>
            <select name="fuel_type" class="form-control" required>
              <option value="">Select Type</option>
              <option value="Petrol">Petrol</option>
              <option value="Diesel">Diesel</option>
              <option value="Gasoline">Gasoline</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Quantity (Liters) <span class="text-danger">*</span></label>
            <input type="number" name="quantity" class="form-control" step="0.01" min="0" required>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Delivery Date <span class="text-danger">*</span></label>
            <input type="date" name="delivery_date" class="form-control" required>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <select name="status" class="form-control" required>
              <option value="Scheduled">Scheduled</option>
              <option value="In Transit">In Transit</option>
              <option value="Completed">Completed</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Schedule Delivery</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Delivery Modal -->
<div class="modal fade" id="editDeliveryModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Delivery</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" id="editDeliveryForm">
        <div class="modal-body">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="delivery_id" id="edit_delivery_id">
          
          <div class="mb-3">
            <label class="form-label">Station <span class="text-danger">*</span></label>
            <select name="station_id" id="edit_station_id" class="form-control" required>
              <option value="">Select Station</option>
              <?php foreach($stations as $station): ?>
                <option value="<?= $station['id'] ?>"><?= esc($station['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Truck <span class="text-danger">*</span></label>
            <select name="truck_id" id="edit_truck_id" class="form-control" required>
              <option value="">Select Truck</option>
              <?php foreach($trucks as $truck): ?>
                <option value="<?= $truck['id'] ?>"><?= esc($truck['plate_number']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Fuel Type <span class="text-danger">*</span></label>
            <select name="fuel_type" id="edit_fuel_type" class="form-control" required>
              <option value="">Select Type</option>
              <option value="Petrol">Petrol</option>
              <option value="Diesel">Diesel</option>
              <option value="Gasoline">Gasoline</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Quantity (Liters) <span class="text-danger">*</span></label>
            <input type="number" name="quantity" id="edit_quantity" class="form-control" step="0.01" min="0" required>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Delivery Date <span class="text-danger">*</span></label>
            <input type="date" name="delivery_date" id="edit_delivery_date" class="form-control" required>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <select name="status" id="edit_status" class="form-control" required>
              <option value="Scheduled">Scheduled</option>
              <option value="In Transit">In Transit</option>
              <option value="Completed">Completed</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" id="edit_notes" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Delivery</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteDeliveryModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="delivery_id" id="delete_delivery_id">
          <p>Are you sure you want to delete this delivery?</p>
          <p class="text-danger"><strong>This action cannot be undone.</strong></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function editDelivery(delivery) {
  document.getElementById('edit_delivery_id').value = delivery.id;
  document.getElementById('edit_station_id').value = delivery.station_id;
  document.getElementById('edit_truck_id').value = delivery.truck_id;
  document.getElementById('edit_fuel_type').value = delivery.fuel_type;
  document.getElementById('edit_quantity').value = delivery.quantity;
  document.getElementById('edit_delivery_date').value = delivery.delivery_date;
  document.getElementById('edit_status').value = delivery.status;
  document.getElementById('edit_notes').value = delivery.notes || '';
  
  var editModal = new bootstrap.Modal(document.getElementById('editDeliveryModal'));
  editModal.show();
}

function deleteDelivery(id) {
  document.getElementById('delete_delivery_id').value = id;
  var deleteModal = new bootstrap.Modal(document.getElementById('deleteDeliveryModal'));
  deleteModal.show();
}

// Auto-dismiss alerts
document.addEventListener('DOMContentLoaded', function() {
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
