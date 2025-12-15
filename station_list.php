<?php
require_once __DIR__ . '/config.php';

// Handle delete request BEFORE including header
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
  $stationId = $_POST['id'] ?? 0;
  if ($stationId) {
    try {
      $deleteStmt = $pdo->prepare('DELETE FROM stations WHERE id = ?');
      $deleteStmt->execute([$stationId]);
      header('Location: station_list.php?deleted=1');
      exit;
    } catch (Exception $e) {
      $error = 'Failed to delete station: ' . $e->getMessage();
    }
  }
}

require_once __DIR__ . '/inc/header.php';

$regionId = isset($_GET['region']) ? (int)$_GET['region'] : null;
if ($regionId) {
    $regionRow = $pdo->prepare('SELECT id, name FROM regions WHERE id = ?');
    $regionRow->execute([$regionId]);
    $region = $regionRow->fetch();
} else {
    $region = null;
}

// Build basic query
$q = 'SELECT s.id, s.name, fs.petrol_l, fs.diesel_l, fs.kerosene_l, IFNULL(sales.today_sales,0) AS sales_today FROM stations s LEFT JOIN fuel_stocks fs ON fs.station_id = s.id LEFT JOIN (SELECT station_id, SUM(amount) AS today_sales FROM sales WHERE DATE(created_at)=DATE(NOW()) GROUP BY station_id) sales ON sales.station_id = s.id';
if ($regionId) $q .= ' WHERE s.region_id = ' . $regionId;
$q .= ' ORDER BY s.name LIMIT 100';

try{
    $stations = $pdo->query($q)->fetchAll();
}catch(Exception $e){
    $stations = [];
}

// Calculate statistics
$totalFuel = 0;
$totalSales = 0;
$criticalStations = 0;
foreach($stations as $s) {
    $totalFuel += ($s['petrol_l'] ?? 0) + ($s['diesel_l'] ?? 0) + ($s['kerosene_l'] ?? 0);
    $totalSales += $s['sales_today'] ?? 0;
    $stockSum = ($s['petrol_l'] ?? 0) + ($s['diesel_l'] ?? 0) + ($s['kerosene_l'] ?? 0);
    if ($stockSum < 2000) $criticalStations++;
}

?>
<div class="container-fluid py-4">
  <!-- Success Message -->
  <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle"></i> Station deleted successfully!
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <?php if (isset($error)): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle"></i> <?= esc($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  
  <!-- Header Section -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
          <a href="/dashboard.php" class="btn btn-outline-secondary btn-sm mb-2">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
          </a>
          <h2 class="mb-0" style="color: #0d6efd; font-weight: 700;">
            <i class="bi bi-fuel-pump"></i> Fuel Stations
            <span class="badge bg-info ms-2"><?= count($stations) ?> Stations</span>
          </h2>
          <?php if ($region): ?>
            <p class="text-muted mb-0 mt-2">
              <i class="bi bi-geo-alt"></i> Region: <strong><?= esc($region['name']) ?></strong>
            </p>
          <?php endif; ?>
        </div>
        <div style="min-width: 250px;">
          <div class="input-group">
            <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control border-0 bg-light" placeholder="Search station name..." id="searchInput">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistics Cards -->
  <div class="row mb-4">
    <div class="col-md-3 mb-3">
      <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #0d6efd;">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="text-muted mb-1" style="font-size: 0.9rem;">Total Stations</p>
              <h3 class="mb-0" style="color: #0d6efd; font-weight: 700;"><?= count($stations) ?></h3>
            </div>
            <i class="bi bi-building" style="font-size: 2rem; color: #0d6efd; opacity: 0.3;"></i>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-md-3 mb-3">
      <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #28a745;">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="text-muted mb-1" style="font-size: 0.9rem;">Total Fuel Stock</p>
              <h3 class="mb-0" style="color: #28a745; font-weight: 700;"><?= number_format($totalFuel) ?> L</h3>
            </div>
            <i class="bi bi-droplet" style="font-size: 2rem; color: #28a745; opacity: 0.3;"></i>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-md-3 mb-3">
      <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #ffc107;">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="text-muted mb-1" style="font-size: 0.9rem;">Today's Sales</p>
              <h3 class="mb-0" style="color: #ffc107; font-weight: 700;">TSh <?= number_format($totalSales, 0) ?></h3>
            </div>
            <i class="bi bi-cash-coin" style="font-size: 2rem; color: #ffc107; opacity: 0.3;"></i>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-md-3 mb-3">
      <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #dc3545;">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="text-muted mb-1" style="font-size: 0.9rem;">Critical Stock</p>
              <h3 class="mb-0" style="color: #dc3545; font-weight: 700;"><?= $criticalStations ?></h3>
            </div>
            <i class="bi bi-exclamation-triangle" style="font-size: 2rem; color: #dc3545; opacity: 0.3;"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Stations Table -->
  <div class="row">
    <div class="col-12">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
          <h5 class="mb-0"><i class="bi bi-list"></i> Stations Overview</h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th class="ps-4" style="font-weight: 600; color: #0d6efd;">ID</th>
                  <th style="font-weight: 600; color: #0d6efd;">Station Name</th>
                  <th style="font-weight: 600; color: #0d6efd;">
                    <i class="bi bi-droplet" style="color: #0066cc;"></i> Petrol
                  </th>
                  <th style="font-weight: 600; color: #0d6efd;">
                    <i class="bi bi-droplet" style="color: #ff9900;"></i> Diesel
                  </th>
                  <th style="font-weight: 600; color: #0d6efd;">
                    <i class="bi bi-droplet" style="color: #cc0000;"></i> Kerosene
                  </th>
                  <th style="font-weight: 600; color: #0d6efd;">Sales Today</th>
                  <th style="font-weight: 600; color: #0d6efd;">Status</th>
                  <th class="pe-4" style="font-weight: 600; color: #0d6efd;">Action</th>
                </tr>
              </thead>
              <tbody id="stationsTable">
                <?php if (empty($stations)): ?>
                  <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                      <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                      <p class="mt-2">No stations found</p>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach($stations as $s): ?>
                    <tr class="station-row">
                      <td class="ps-4">
                        <span class="badge bg-light text-dark">#<?= esc($s['id']) ?></span>
                      </td>
                      <td>
                        <div class="fw-600" style="color: #333;">
                          <i class="bi bi-fuel-pump"></i> <?= esc($s['name']) ?>
                        </div>
                      </td>
                      <td>
                        <div style="color: #0066cc; font-weight: 500;">
                          <?= number_format($s['petrol_l'] ?? 0) ?> L
                        </div>
                      </td>
                      <td>
                        <div style="color: #ff9900; font-weight: 500;">
                          <?= number_format($s['diesel_l'] ?? 0) ?> L
                        </div>
                      </td>
                      <td>
                        <div style="color: #cc0000; font-weight: 500;">
                          <?= number_format($s['kerosene_l'] ?? 0) ?> L
                        </div>
                      </td>
                      <td>
                        <div style="font-weight: 600; color: #28a745;">
                          TSh <?= number_format($s['sales_today'], 0) ?>
                        </div>
                      </td>
                      <td>
                        <?php
                        $totalStock = ($s['petrol_l'] ?? 0) + ($s['diesel_l'] ?? 0) + ($s['kerosene_l'] ?? 0);
                        if ($totalStock > 5000) {
                          echo '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Normal</span>';
                        } elseif ($totalStock > 2000) {
                          echo '<span class="badge bg-warning"><i class="bi bi-exclamation-circle"></i> Low Stock</span>';
                        } else {
                          echo '<span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i> Critical</span>';
                        }
                        ?>
                      </td>
                      <td class="pe-4">
                        <div style="display: flex; gap: 8px; align-items: center;">
                          <a class="btn btn-primary" href="station_details.php?id=<?= $s['id'] ?>" style="background-color: #0d6efd; border: none;">
                            <i class="bi bi-eye"></i> View
                          </a>
                          <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this station?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">
                              <i class="bi bi-trash"></i> Delete
                            </button>
                          </form>
                        </div>
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
  </div>
</div>

<style>
  * {
    font-family: 'Segoe UI', 'Roboto', -apple-system, BlinkMacSystemFont, sans-serif;
  }
  
  body {
    background-color: #f5f6f8;
    color: #2c3e50;
  }
  
  h2 {
    color: #1a3a52 !important;
    letter-spacing: 0.5px;
  }
  
  h5 {
    color: #2c3e50 !important;
    font-weight: 600;
  }
  
  .text-muted {
    color: #6b7280 !important;
  }
  
  .card {
    background-color: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    transition: all 0.3s ease;
  }
  
  .card:hover {
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08) !important;
    border-color: #d1d5db;
  }
  
  .station-row {
    transition: all 0.3s ease;
    border-bottom: 1px solid #e5e7eb;
    background-color: #ffffff;
  }
  
  .station-row:hover {
    background-color: #f9fafb;
    transform: translateX(3px);
  }
  
  table {
    background-color: #ffffff;
  }
  
  table thead {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    border-bottom: 2px solid #d1d5db;
  }
  
  table th {
    color: #374151 !important;
    font-weight: 600;
    padding: 14px 12px !important;
    letter-spacing: 0.4px;
  }
  
  table td {
    color: #4b5563 !important;
    padding: 13px 12px !important;
    vertical-align: middle;
  }
  
  #searchInput {
    border-radius: 25px !important;
    padding: 11px 18px !important;
    font-size: 0.95rem;
    border: 1px solid #d1d5db !important;
    background-color: #f9fafb !important;
    color: #2c3e50 !important;
    transition: all 0.3s ease;
  }
  
  #searchInput::placeholder {
    color: #9ca3af !important;
  }
  
  #searchInput:focus {
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1) !important;
    border-color: #3b82f6 !important;
    background-color: #ffffff !important;
    outline: none;
  }
  
  .badge {
    padding: 7px 12px;
    font-weight: 500;
    border-radius: 20px;
    font-size: 0.85rem;
    letter-spacing: 0.3px;
  }
  
  .bg-success {
    background-color: #10b981 !important;
  }
  
  .bg-warning {
    background-color: #f59e0b !important;
  }
  
  .bg-danger {
    background-color: #ef4444 !important;
  }
  
  .bg-info {
    background-color: #3b82f6 !important;
  }
  
  .bg-light {
    background-color: #f3f4f6 !important;
    color: #374151 !important;
  }
  
  .btn-primary {
    background-color: #3b82f6;
    border: 1px solid #3b82f6;
    color: #ffffff;
    transition: all 0.3s ease;
    font-weight: 500;
    letter-spacing: 0.4px;
  }
  
  .btn-primary:hover {
    background-color: #2563eb;
    border-color: #2563eb;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
  }

  .btn-danger {
    background-color: #ef4444;
    border: 1px solid #ef4444;
    color: #ffffff;
    transition: all 0.3s ease;
    font-weight: 500;
    letter-spacing: 0.4px;
  }

  .btn-danger:hover {
    background-color: #dc2626;
    border-color: #dc2626;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
  }

  .btn-group-sm .btn {
    padding: 5px 10px;
    font-size: 0.85rem;
  }
  
  .btn-outline-secondary {
    color: #6b7280;
    border-color: #d1d5db;
    transition: all 0.3s ease;
  }
  
  .btn-outline-secondary:hover {
    background-color: #f3f4f6;
    border-color: #9ca3af;
    color: #374151;
  }
  
  .border-bottom {
    border-bottom-color: #e5e7eb !important;
  }
  
  /* Stats cards styling */
  .card[style*="border-left"] {
    border-bottom: 1px solid #e5e7eb !important;
    border-right: 1px solid #e5e7eb !important;
    border-top: 1px solid #e5e7eb !important;
  }
  
  .card-body {
    color: #2c3e50;
  }
  
  /* Icon colors */
  .bi-fuel-pump, .bi-building, .bi-droplet, .bi-cash-coin, .bi-exclamation-triangle {
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.05));
  }
  
  /* Scrollbar styling */
  ::-webkit-scrollbar {
    width: 8px;
    height: 8px;
  }
  
  ::-webkit-scrollbar-track {
    background: #f5f6f8;
  }
  
  ::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
  }
  
  ::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
  }
</style>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
  const searchTerm = this.value.toLowerCase();
  const rows = document.querySelectorAll('#stationsTable .station-row');
  
  rows.forEach(row => {
    const stationName = row.cells[1].textContent.toLowerCase();
    if (stationName.includes(searchTerm)) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
});
</script>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
