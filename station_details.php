<?php
require_once __DIR__ . '/inc/header.php';

$stationId = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// Demo station data
$station = [
  'id' => $stationId,
  'name' => 'Station ' . $stationId,
  'location' => 'Location Address ' . $stationId . ', Dar es Salaam',
  'manager' => 'Manager ' . $stationId,
  'phone' => '+255 ' . rand(700000000, 799999999),
  'pumps' => rand(4, 8),
  'nozzles' => rand(12, 24),
  'capacity' => rand(50000, 150000)
];

// Generate daily sales data for the last 7 days
$dailySales = [];
for($i = 6; $i >= 0; $i--) {
  $date = date('Y-m-d', strtotime("-$i days"));
  $dailySales[] = [
    'date' => date('M d', strtotime("-$i days")),
    'petrol' => rand(50000, 200000),
    'diesel' => rand(80000, 300000),
    'kerosene' => rand(30000, 100000)
  ];
}

// Pump information
$pumps = [];
for($i = 1; $i <= $station['pumps']; $i++) {
  $pumps[] = [
    'id' => 'PUMP-' . str_pad($i, 2, '0', STR_PAD_LEFT),
    'type' => ($i % 3 == 0) ? 'Kerosene' : (($i % 2 == 0) ? 'Diesel' : 'Petrol'),
    'nozzles' => rand(2, 4),
    'status' => rand(0, 10) > 1 ? 'Active' : 'Maintenance',
    'today_sales' => rand(20000, 80000)
  ];
}

// Current stock levels
$stock = [
  'petrol' => rand(10000, 50000),
  'diesel' => rand(15000, 60000),
  'kerosene' => rand(5000, 30000)
];
?>

<div class="container-fluid">
  <!-- Header Section -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <a href="dashboard.php" class="btn btn-outline-secondary mb-2">&larr; Back to Dashboard</a>
      <h2 class="mb-0"><?= esc($station['name']) ?></h2>
      <p class="text-muted"><?= esc($station['location']) ?></p>
    </div>
    <div>
      <button class="btn btn-primary">Edit Station</button>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card p-3" style="background: #28a745; color: #fff;">
        <div class="d-flex align-items-center">
          <i class="bi bi-fuel-pump-fill" style="font-size: 2.5rem; opacity: 0.8;"></i>
          <div class="ms-3">
            <h6 class="mb-0">Total Pumps</h6>
            <h3 class="mb-0"><?= $station['pumps'] ?></h3>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3" style="background: #007bff; color: #fff;">
        <div class="d-flex align-items-center">
          <i class="bi bi-inlet" style="font-size: 2.5rem; opacity: 0.8;"></i>
          <div class="ms-3">
            <h6 class="mb-0">Total Nozzles</h6>
            <h3 class="mb-0"><?= $station['nozzles'] ?></h3>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3" style="background: #ffa502; color: #fff;">
        <div class="d-flex align-items-center">
          <i class="bi bi-droplet-fill" style="font-size: 2.5rem; opacity: 0.8;"></i>
          <div class="ms-3">
            <h6 class="mb-0">Tank Capacity</h6>
            <h3 class="mb-0"><?= number_format($station['capacity']) ?> L</h3>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3" style="background: #2c3e50; color: #fff;">
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
      <div class="card p-4">
        <h5>Daily Sales (Last 7 Days)</h5>
        <canvas id="salesChart" height="80"></canvas>
      </div>
    </div>
    
    <!-- Stock Levels -->
    <div class="col-md-4">
      <div class="card p-4">
        <h5>Current Stock Levels</h5>
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <span><i class="bi bi-fuel-pump text-success"></i> Petrol</span>
            <strong><?= number_format($stock['petrol']) ?> L</strong>
          </div>
          <div class="progress" style="height: 20px;">
            <div class="progress-bar bg-success" style="width: <?= ($stock['petrol']/$station['capacity']*100) ?>%"></div>
          </div>
          <small class="text-muted"><?= round($stock['petrol']/$station['capacity']*100, 1) ?>% of capacity</small>
        </div>
        
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <span><i class="bi bi-fuel-pump text-primary"></i> Diesel</span>
            <strong><?= number_format($stock['diesel']) ?> L</strong>
          </div>
          <div class="progress" style="height: 20px;">
            <div class="progress-bar bg-primary" style="width: <?= ($stock['diesel']/$station['capacity']*100) ?>%"></div>
          </div>
          <small class="text-muted"><?= round($stock['diesel']/$station['capacity']*100, 1) ?>% of capacity</small>
        </div>
        
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <span><i class="bi bi-fuel-pump text-warning"></i> Kerosene</span>
            <strong><?= number_format($stock['kerosene']) ?> L</strong>
          </div>
          <div class="progress" style="height: 20px;">
            <div class="progress-bar bg-warning" style="width: <?= ($stock['kerosene']/$station['capacity']*100) ?>%"></div>
          </div>
          <small class="text-muted"><?= round($stock['kerosene']/$station['capacity']*100, 1) ?>% of capacity</small>
        </div>
        
        <button class="btn btn-success btn-sm w-100 mt-2">Request Refill</button>
      </div>
    </div>
  </div>

  <!-- Pumps and Nozzles Information -->
  <div class="row mb-4">
    <div class="col-md-12">
      <div class="card p-4">
        <h5><i class="bi bi-fuel-pump-fill me-2"></i>Pump & Nozzle Information</h5>
        <div class="row mt-3">
          <?php foreach($pumps as $pump): ?>
          <div class="col-md-3 mb-3">
            <div class="card p-3" style="background: <?= $pump['status'] == 'Active' ? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' : '#6c757d' ?>; color: #fff;">
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
    <div class="col-md-6">
      <div class="card p-4">
        <h5><i class="bi bi-info-circle me-2"></i>Station Information</h5>
        <table class="table table-borderless">
          <tr>
            <td><strong>Station ID:</strong></td>
            <td><?= $station['id'] ?></td>
          </tr>
          <tr>
            <td><strong>Manager:</strong></td>
            <td><?= esc($station['manager']) ?></td>
          </tr>
          <tr>
            <td><strong>Phone:</strong></td>
            <td><?= esc($station['phone']) ?></td>
          </tr>
          <tr>
            <td><strong>Location:</strong></td>
            <td><?= esc($station['location']) ?></td>
          </tr>
          <tr>
            <td><strong>Operating Hours:</strong></td>
            <td>24/7</td>
          </tr>
          <tr>
            <td><strong>Staff Count:</strong></td>
            <td><?= rand(8, 20) ?> employees</td>
          </tr>
        </table>
      </div>
    </div>
    
    <div class="col-md-6">
      <div class="card p-4">
        <h5><i class="bi bi-activity me-2"></i>Recent Activity</h5>
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
        tension: 0.4
      },
      {
        label: 'Diesel Sales (TSh)',
        data: <?= json_encode(array_column($dailySales, 'diesel')) ?>,
        borderColor: '#007bff',
        backgroundColor: 'rgba(0, 123, 255, 0.1)',
        tension: 0.4
      },
      {
        label: 'Kerosene Sales (TSh)',
        data: <?= json_encode(array_column($dailySales, 'kerosene')) ?>,
        borderColor: '#ffa502',
        backgroundColor: 'rgba(255, 165, 2, 0.1)',
        tension: 0.4
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
</script>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
