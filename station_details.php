<?php
require_once __DIR__ . '/inc/header.php';

// Helper function to escape HTML
function esc($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

$stationId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$message = '';
$messageType = '';

// Create regions table if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS regions (
        id int NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // Table might already exist
}

// Create stations table if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS stations (
        id int NOT NULL AUTO_INCREMENT,
        region_id int DEFAULT NULL,
        name varchar(100) NOT NULL,
        location varchar(255) DEFAULT NULL,
        manager varchar(100) DEFAULT NULL,
        phone varchar(15) DEFAULT NULL,
        status enum('Active','Inactive','Maintenance') DEFAULT 'Active',
        pumps int DEFAULT '4',
        nozzles int DEFAULT '12',
        capacity int DEFAULT '50000',
        operating_hours VARCHAR(50) DEFAULT '24/7',
        staff_count INT DEFAULT 8,
        latitude decimal(10,8) DEFAULT NULL,
        longitude decimal(11,8) DEFAULT NULL,
        created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY region_id (region_id),
        CONSTRAINT stations_ibfk_1 FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Add missing columns if they don't exist
    try {
        $pdo->exec("ALTER TABLE stations ADD COLUMN operating_hours VARCHAR(50) DEFAULT '24/7'");
    } catch (Exception $e) {
        // Column already exists
    }
    
    try {
        $pdo->exec("ALTER TABLE stations ADD COLUMN staff_count INT DEFAULT 8");
    } catch (Exception $e) {
        // Column already exists
    }
    
    try {
        $pdo->exec("ALTER TABLE stations ADD COLUMN region_id int DEFAULT NULL");
    } catch (Exception $e) {
        // Column already exists
    }
    
    try {
        $pdo->exec("ALTER TABLE stations ADD COLUMN status enum('Active','Inactive','Maintenance') DEFAULT 'Active'");
    } catch (Exception $e) {
        // Column already exists
    }
} catch (Exception $e) {
    // Table might already exist
}

// Fetch station from database
$station = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ?");
    $stmt->execute([$stationId]);
    $station = $stmt->fetch();
} catch (Exception $e) {
    $station = null;
}

// If no database record, use defaults
if (!$station) {
    $station = [
        'id' => $stationId,
        'name' => 'Station ' . $stationId,
        'location' => 'Location Address ' . $stationId . ', Dar es Salaam',
        'manager' => 'Manager ' . $stationId,
        'phone' => '+255 700 000 000',
        'pumps' => 4,
        'nozzles' => 12,
        'capacity' => 50000,
        'operating_hours' => '24/7',
        'staff_count' => 8,
        'latitude' => null,
        'longitude' => null
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userName = $_SESSION['username'] ?? 'Guest';
    
    if ($action === 'update_station') {
        try {
            $stmt = $pdo->prepare("INSERT INTO stations (id, name, location, manager, phone, pumps, nozzles, capacity, operating_hours, staff_count, latitude, longitude) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE 
                                   name=VALUES(name), location=VALUES(location), manager=VALUES(manager), phone=VALUES(phone),
                                   pumps=VALUES(pumps), nozzles=VALUES(nozzles), capacity=VALUES(capacity), 
                                   operating_hours=VALUES(operating_hours), staff_count=VALUES(staff_count),
                                   latitude=VALUES(latitude), longitude=VALUES(longitude)");
            $stmt->execute([
                $stationId,
                $_POST['name'],
                $_POST['location'],
                $_POST['manager'],
                $_POST['phone'],
                $_POST['pumps'],
                $_POST['nozzles'],
                $_POST['capacity'],
                $_POST['operating_hours'],
                $_POST['staff_count'],
                $_POST['latitude'] ?? null,
                $_POST['longitude'] ?? null
            ]);
            $message = 'Station updated successfully!';
            $messageType = 'success';
            
            // Log station update
            log_audit($pdo, $userName, AUDIT_ACTION_UPDATE, 'Stations', 'Success', 
                "Updated station ID: $stationId - Name: {$_POST['name']}, Location: {$_POST['location']}");
            
            // Reload station data
            $stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ?");
            $stmt->execute([$stationId]);
            $station = $stmt->fetch();
        } catch (Exception $e) {
            $message = 'Error updating station: ' . $e->getMessage();
            $messageType = 'danger';
            
            // Log error
            log_audit($pdo, $userName, AUDIT_ACTION_UPDATE, 'Stations', 'Failed', 
                'Error: ' . $e->getMessage());
        }
    }
}

// Fetch all employees assigned to this station
$employees = [];
try {
    $stmt = $pdo->prepare("SELECT id, name, position, contact, status FROM staff WHERE station_id = ? ORDER BY position DESC, name ASC");
    $stmt->execute([$stationId]);
    $employees = $stmt->fetchAll();
} catch (Exception $e) {
    $employees = [];
}

// Fetch manager details if assigned
$managerDetails = null;
if (!empty($employees)) {
    foreach ($employees as $emp) {
        if (strtolower($emp['position']) === 'manager') {
            $managerDetails = $emp;
            break;
        }
    }
}
for($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dailySales[] = [
        'date' => date('M d', strtotime("-$i days")),
        'petrol' => rand(50000, 200000),
        'diesel' => rand(80000, 300000),
        'kerosene' => rand(30000, 100000)
    ];
}

// Current stock levels
$stock = [
    'petrol' => rand(10000, 50000),
    'diesel' => rand(15000, 60000),
    'kerosene' => rand(5000, 30000)
];

// Pump information
$pumps = [];
for($i = 1; $i <= ($station['pumps'] ?? 4); $i++) {
    $pumps[] = [
        'id' => 'PUMP-' . str_pad($i, 2, '0', STR_PAD_LEFT),
        'type' => ($i % 3 == 0) ? 'Kerosene' : (($i % 2 == 0) ? 'Diesel' : 'Petrol'),
        'nozzles' => rand(2, 4),
        'status' => rand(0, 10) > 1 ? 'Active' : 'Maintenance',
        'today_sales' => rand(20000, 80000)
    ];
}

?>

<div class="container-fluid">
  <!-- Header Section -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <a href="station_list.php" class="btn btn-outline-secondary mb-2"><i class="bi bi-arrow-left"></i> Back</a>
      <h2 class="mb-0" style="color: #1a3a52;"><?= esc($station['name']) ?></h2>
      <p class="text-muted"><i class="bi bi-geo-alt"></i> <?= esc($station['location']) ?></p>
    </div>
    <div>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editStationModal">
        <i class="bi bi-pencil-square"></i> Edit Station
      </button>
    </div>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Summary Cards -->
  <div class="row mb-4">
    <div class="col-md-3 mb-3">
      <div class="card p-3 border-0" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: #fff;">
        <div class="d-flex align-items-center">
          <i class="bi bi-fuel-pump-fill" style="font-size: 2.5rem; opacity: 0.8;"></i>
          <div class="ms-3">
            <h6 class="mb-0">Total Pumps</h6>
            <h3 class="mb-0"><?= $station['pumps'] ?? 4 ?></h3>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card p-3 border-0" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: #fff;">
        <div class="d-flex align-items-center">
          <i class="bi bi-inlet" style="font-size: 2.5rem; opacity: 0.8;"></i>
          <div class="ms-3">
            <h6 class="mb-0">Total Nozzles</h6>
            <h3 class="mb-0"><?= $station['nozzles'] ?? 12 ?></h3>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card p-3 border-0" style="background: linear-gradient(135deg, #ffa502 0%, #ff8c00 100%); color: #fff;">
        <div class="d-flex align-items-center">
          <i class="bi bi-droplet-fill" style="font-size: 2.5rem; opacity: 0.8;"></i>
          <div class="ms-3">
            <h6 class="mb-0">Tank Capacity</h6>
            <h3 class="mb-0"><?= number_format($station['capacity'] ?? 50000) ?> L</h3>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card p-3 border-0" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: #fff;">
        <div class="d-flex align-items-center">
          <i class="bi bi-cash-coin" style="font-size: 2.5rem; opacity: 0.8;"></i>
          <div class="ms-3">
            <h6 class="mb-0">Today's Sales</h6>
            <h3 class="mb-0">TSh <?= number_format(rand(300000, 800000)) ?></h3>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Charts Row -->
  <div class="row mb-4">
    <!-- Daily Sales Chart -->
    <div class="col-md-8">
      <div class="card p-4 border-0">
        <h5 style="color: #2c3e50;"><i class="bi bi-graph-up me-2"></i>Daily Sales (Last 7 Days)</h5>
        <canvas id="salesChart" height="80"></canvas>
      </div>
    </div>
    
    <!-- Stock Levels -->
    <div class="col-md-4">
      <div class="card p-4 border-0">
        <h5 style="color: #2c3e50;"><i class="bi bi-droplet me-2"></i>Current Stock Levels</h5>
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <span><i class="bi bi-fuel-pump text-success"></i> Petrol</span>
            <strong><?= number_format($stock['petrol']) ?> L</strong>
          </div>
          <div class="progress" style="height: 20px;">
            <div class="progress-bar bg-success" style="width: <?= ($stock['petrol']/($station['capacity'] ?? 50000)*100) ?>%"></div>
          </div>
          <small class="text-muted"><?= round($stock['petrol']/($station['capacity'] ?? 50000)*100, 1) ?>% of capacity</small>
        </div>
        
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <span><i class="bi bi-fuel-pump text-primary"></i> Diesel</span>
            <strong><?= number_format($stock['diesel']) ?> L</strong>
          </div>
          <div class="progress" style="height: 20px;">
            <div class="progress-bar bg-primary" style="width: <?= ($stock['diesel']/($station['capacity'] ?? 50000)*100) ?>%"></div>
          </div>
          <small class="text-muted"><?= round($stock['diesel']/($station['capacity'] ?? 50000)*100, 1) ?>% of capacity</small>
        </div>
        
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <span><i class="bi bi-fuel-pump text-warning"></i> Kerosene</span>
            <strong><?= number_format($stock['kerosene']) ?> L</strong>
          </div>
          <div class="progress" style="height: 20px;">
            <div class="progress-bar bg-warning" style="width: <?= ($stock['kerosene']/($station['capacity'] ?? 50000)*100) ?>%"></div>
          </div>
          <small class="text-muted"><?= round($stock['kerosene']/($station['capacity'] ?? 50000)*100, 1) ?>% of capacity</small>
        </div>
        
        <button class="btn btn-success btn-sm w-100 mt-2"><i class="bi bi-plus-circle"></i> Request Refill</button>
      </div>
    </div>
  </div>

  <!-- Pumps and Nozzles Information -->
  <div class="row mb-4">
    <div class="col-md-12">
      <div class="card p-4 border-0">
        <h5 style="color: #2c3e50;"><i class="bi bi-fuel-pump-fill me-2"></i>Pump & Nozzle Information</h5>
        <div class="row mt-3">
          <?php foreach($pumps as $pump): ?>
          <div class="col-md-3 mb-3">
            <div class="card p-3 border-0" style="background: <?= $pump['status'] == 'Active' ? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' : '#6c757d' ?>; color: #fff;">
              <div class="text-center mb-2">
                <i class="bi bi-fuel-pump" style="font-size: 2.5rem;"></i>
              </div>
              <h6 class="text-center"><?= $pump['id'] ?></h6>
              <hr style="border-color: rgba(255,255,255,0.3);">
              <p class="mb-1"><strong>Type:</strong> <?= $pump['type'] ?></p>
              <p class="mb-1"><strong>Nozzles:</strong> <?= $pump['nozzles'] ?> <i class="bi bi-inlet"></i></p>
              <p class="mb-1"><strong>Status:</strong> 
                <span class="badge bg-<?= $pump['status'] == 'Active' ? 'success' : 'warning' ?>"><?= $pump['status'] ?></span>
              </p>
              <p class="mb-0"><strong>Today:</strong> TSh <?= number_format($pump['today_sales']) ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Station Information -->
  <div class="row">
    <div class="col-md-6 mb-4">
      <div class="card p-4 border-0">
        <h5 style="color: #2c3e50;"><i class="bi bi-info-circle me-2"></i>Station Information</h5>
        <table class="table table-borderless">
          <tr>
            <td><strong style="color: #374151;">Station ID:</strong></td>
            <td><?= $station['id'] ?></td>
          </tr>
          <tr>
            <td><strong style="color: #374151;">Manager:</strong></td>
            <td>
              <?php if ($managerDetails): ?>
                <a href="staff.php?edit=<?= $managerDetails['id'] ?>" class="text-decoration-none">
                  <?= esc($managerDetails['name']) ?>
                </a>
                <small class="text-muted d-block"><?= esc($managerDetails['contact'] ?? '—') ?></small>
              <?php else: ?>
                <?= esc($station['manager']) ?>
              <?php endif; ?>
            </td>
          </tr>
          <tr>
            <td><strong style="color: #374151;">Phone:</strong></td>
            <td><?= esc($station['phone']) ?></td>
          </tr>
          <tr>
            <td><strong style="color: #374151;">Location:</strong></td>
            <td><?= esc($station['location']) ?></td>
          </tr>
          <tr>
            <td><strong style="color: #374151;">Operating Hours:</strong></td>
            <td><?= esc($station['operating_hours'] ?? '24/7') ?></td>
          </tr>
          <tr>
            <td><strong style="color: #374151;">Staff Count:</strong></td>
            <td><?= count($employees) ?> employees</td>
          </tr>
        </table>
      </div>
    </div>
    
    <div class="col-md-6 mb-4">
      <div class="card p-4 border-0">
        <h5 style="color: #2c3e50;"><i class="bi bi-activity me-2"></i>Recent Activity</h5>
        <div class="list-group list-group-flush">
          <?php for($i = 0; $i < 5; $i++): ?>
          <div class="list-group-item px-0">
            <div class="d-flex justify-content-between">
              <span><?= ['Fuel delivery received', 'Pump maintenance completed', 'Price update applied', 'Stock alert sent', 'Shift change'][rand(0,4)] ?></span>
              <small class="text-muted"><?= rand(1,60) ?> min ago</small>
            </div>
          </div>
          <?php endfor; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Assigned Employees Section -->
  <div class="row mt-4">
    <div class="col-md-12">
      <div class="card p-4 border-0">
        <h5 style="color: #2c3e50;"><i class="bi bi-people me-2"></i>Assigned Employees (<?= count($employees) ?>)</h5>
        
        <?php if (empty($employees)): ?>
          <div class="alert alert-info mt-3 mb-0">
            <i class="bi bi-info-circle"></i> No employees assigned to this station yet.
            <a href="staff.php" class="alert-link">Assign staff members</a>
          </div>
        <?php else: ?>
          <div class="table-responsive mt-3">
            <table class="table table-hover">
              <thead class="table-light">
                <tr>
                  <th>Name</th>
                  <th>Position</th>
                  <th>Contact</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($employees as $emp): ?>
                <tr>
                  <td>
                    <strong><?= esc($emp['name']) ?></strong>
                    <?php if (strtolower($emp['position']) === 'manager'): ?>
                      <span class="badge bg-warning ms-2">Station Manager</span>
                    <?php endif; ?>
                  </td>
                  <td><span class="badge bg-primary"><?= esc($emp['position']) ?></span></td>
                  <td><?= esc($emp['contact'] ?? '—') ?></td>
                  <td>
                    <?php if ($emp['status'] === 'active'): ?>
                      <span class="badge bg-success"><i class="bi bi-check-circle"></i> Active</span>
                    <?php elseif ($emp['status'] === 'on_leave'): ?>
                      <span class="badge bg-warning"><i class="bi bi-exclamation-circle"></i> On Leave</span>
                    <?php else: ?>
                      <span class="badge bg-secondary"><i class="bi bi-x-circle"></i> Inactive</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a href="staff.php?edit=<?= $emp['id'] ?>" class="btn btn-sm btn-outline-primary">
                      <i class="bi bi-pencil"></i> Edit
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          
          <div class="mt-3 text-end">
            <a href="staff.php" class="btn btn-sm btn-outline-success">
              <i class="bi bi-plus-circle"></i> Add More Staff
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Edit Station Modal -->
<div class="modal fade" id="editStationModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0">
      <div class="modal-header bg-primary text-white border-0">
        <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Station Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="action" value="update_station">
          
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label"><strong>Station Name</strong> <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="<?= esc($station['name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label"><strong>Manager Name</strong> <span class="text-danger">*</span></label>
              <input type="text" name="manager" class="form-control" value="<?= esc($station['manager']) ?>" required>
            </div>
          </div>
          
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label"><strong>Phone Number</strong> <span class="text-danger">*</span></label>
              <input type="tel" name="phone" class="form-control" value="<?= esc($station['phone']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label"><strong>Operating Hours</strong></label>
              <input type="text" name="operating_hours" class="form-control" value="<?= esc($station['operating_hours'] ?? '24/7') ?>" placeholder="e.g., 24/7 or 6am-10pm">
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label"><strong>Location/Address</strong> <span class="text-danger">*</span></label>
            <textarea name="location" class="form-control" rows="2" required><?= esc($station['location']) ?></textarea>
          </div>
          
          <hr>
          <h6 style="color: #1a3a52;"><i class="bi bi-fuel-pump"></i> Equipment Details</h6>
          
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label"><strong>Number of Pumps</strong> <span class="text-danger">*</span></label>
              <input type="number" name="pumps" class="form-control" value="<?= $station['pumps'] ?>" min="1" required>
            </div>
            <div class="col-md-4">
              <label class="form-label"><strong>Total Nozzles</strong> <span class="text-danger">*</span></label>
              <input type="number" name="nozzles" class="form-control" value="<?= $station['nozzles'] ?>" min="1" required>
            </div>
            <div class="col-md-4">
              <label class="form-label"><strong>Tank Capacity (Liters)</strong> <span class="text-danger">*</span></label>
              <input type="number" name="capacity" class="form-control" value="<?= $station['capacity'] ?>" min="1000" required>
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label"><strong>Staff Count</strong> (Read-only - Auto-calculated from assigned staff)</label>
            <input type="number" name="staff_count" class="form-control" value="<?= count($employees) ?>" min="1" readonly>
          </div>
          
          <hr>
          <h6 style="color: #1a3a52;"><i class="bi bi-geo-alt"></i> Location Coordinates</h6>
          
          <div class="row">
            <div class="col-md-6">
              <label class="form-label"><strong>Latitude</strong></label>
              <input type="number" name="latitude" class="form-control" step="0.000001" value="<?= $station['latitude'] ?? '' ?>" placeholder="-6.7924">
            </div>
            <div class="col-md-6">
              <label class="form-label"><strong>Longitude</strong></label>
              <input type="number" name="longitude" class="form-control" step="0.000001" value="<?= $station['longitude'] ?? '' ?>" placeholder="39.2083">
            </div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Daily Sales Chart
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
  type: 'line',
  data: {
    labels: <?= json_encode(array_column($dailySales, 'date')) ?>,
    datasets: [
      {
        label: 'Petrol Sales (TSh)',
        data: <?= json_encode(array_column($dailySales, 'petrol')) ?>,
        borderColor: '#28a745',
        backgroundColor: 'rgba(40, 167, 69, 0.1)',
        tension: 0.4,
        fill: true
      },
      {
        label: 'Diesel Sales (TSh)',
        data: <?= json_encode(array_column($dailySales, 'diesel')) ?>,
        borderColor: '#007bff',
        backgroundColor: 'rgba(0, 123, 255, 0.1)',
        tension: 0.4,
        fill: true
      },
      {
        label: 'Kerosene Sales (TSh)',
        data: <?= json_encode(array_column($dailySales, 'kerosene')) ?>,
        borderColor: '#ffa502',
        backgroundColor: 'rgba(255, 165, 2, 0.1)',
        tension: 0.4,
        fill: true
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: {
        position: 'bottom'
      },
      tooltip: {
        callbacks: {
          label: function(context) {
            return context.dataset.label + ': TSh ' + context.parsed.y.toLocaleString();
          }
        }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: function(value) {
            return 'TSh ' + value.toLocaleString();
          }
        }
      }
    }
  }
});

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

