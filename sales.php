<?php
require_once __DIR__ . '/config.php';

// Get filter parameters
$filterStation = $_GET['station'] ?? '';
$filterMonth = $_GET['month'] ?? date('Y-m');
$filterFuelType = $_GET['fuel_type'] ?? '';

// Get sales data with filters
$query = "SELECT * FROM sales WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";
$params = [$filterMonth];

if (!empty($filterStation)) {
    $query .= " AND station_id = ?";
    $params[] = $filterStation;
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sales = $stmt->fetchAll();

// Calculate statistics for the selected month
$todayStmt = $pdo->prepare("SELECT SUM(amount) as total FROM sales WHERE DATE(created_at) = CURDATE()");
$todayStmt->execute();
$todayTotal = $todayStmt->fetch()['total'] ?? 0;

$weekStmt = $pdo->prepare("SELECT SUM(amount) as total FROM sales WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$weekStmt->execute();
$weekTotal = $weekStmt->fetch()['total'] ?? 0;

$monthStmt = $pdo->prepare("SELECT SUM(amount) as total FROM sales WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$monthStmt->execute([$filterMonth]);
$monthTotal = $monthStmt->fetch()['total'] ?? 0;

$countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM sales WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$countStmt->execute([$filterMonth]);
$transactionCount = $countStmt->fetch()['count'] ?? 0;

// Get stations for filter
$stations = $pdo->query("SELECT DISTINCT station_id FROM sales")->fetchAll(PDO::FETCH_COLUMN);

// Get sales by station for the selected month
$stationSalesQuery = "SELECT station_id, SUM(amount) as total FROM sales WHERE DATE_FORMAT(created_at, '%Y-%m') = ?";
$stationSalesParams = [$filterMonth];

if (!empty($filterStation)) {
    $stationSalesQuery .= " AND station_id = ?";
    $stationSalesParams[] = $filterStation;
}

$stationSalesQuery .= " GROUP BY station_id ORDER BY total DESC";

$stationStmt = $pdo->prepare($stationSalesQuery);
$stationStmt->execute($stationSalesParams);
$stationSales = $stationStmt->fetchAll();

require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Sales Analytics</h2>
  
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card p-3 text-center" style="background: #28a745; color: #fff;">
        <h6>Today's Sales</h6>
        <h3>TSh <?= number_format($todayTotal, 2) ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 text-center" style="background: #007bff; color: #fff;">
        <h6>This Week</h6>
        <h3>TSh <?= number_format($weekTotal, 2) ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 text-center" style="background: #ffa502; color: #fff;">
        <h6>This Month (<?= $filterMonth ?>)</h6>
        <h3>TSh <?= number_format($monthTotal, 2) ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 text-center" style="background: #2c3e50; color: #fff;">
        <h6>Total Transactions</h6>
        <h3><?= number_format($transactionCount) ?></h3>
      </div>
    </div>
  </div>
  
  <div class="card p-4 mb-4">
    <h5>Filter Sales Data</h5>
    <form method="GET" class="row g-3 mt-2">
      <div class="col-md-4">
        <label class="form-label">Month</label>
        <input type="month" name="month" class="form-control" value="<?= $filterMonth ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Station</label>
        <select name="station" class="form-control">
          <option value="">All Stations</option>
          <?php 
          // Get actual station names from database
          $stationNames = $pdo->query("SELECT DISTINCT id, name FROM stations ORDER BY name")->fetchAll();
          foreach($stationNames as $st):
          ?>
          <option value="<?= $st['id'] ?>" <?= $filterStation == $st['id'] ? 'selected' : '' ?>>
            <?= esc($st['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">&nbsp;</label>
        <button type="submit" class="btn btn-primary w-100">Filter</button>
        <a href="sales.php" class="btn btn-secondary w-100 mt-2">Reset</a>
      </div>
    </form>
  </div>
  
  <div class="row">
    <div class="col-md-12">
      <div class="card p-4">
        <h5>Sales by Station (<?= $filterMonth ?>)</h5>
        <?php if (empty($stationSales)): ?>
        <p class="text-center text-muted mt-3">No sales data found for the selected filters.</p>
        <?php else: ?>
        <table class="table table-striped mt-3">
          <thead>
            <tr>
              <th>Station</th>
              <th>Total Sales</th>
              <th>Transaction Count</th>
              <th>Average per Transaction</th>
              <th>% of Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($stationSales as $ss): 
              $stationName = $pdo->query("SELECT name FROM stations WHERE id = " . intval($ss['station_id']))->fetch()['name'] ?? 'Unknown';
              $stationCount = $pdo->query("SELECT COUNT(*) as count FROM sales WHERE station_id = " . intval($ss['station_id']) . " AND DATE_FORMAT(created_at, '%Y-%m') = '$filterMonth'")->fetch()['count'] ?? 0;
              $avgPerTransaction = $stationCount > 0 ? $ss['total'] / $stationCount : 0;
              $percentage = $monthTotal > 0 ? ($ss['total'] / $monthTotal) * 100 : 0;
            ?>
            <tr>
              <td><?= esc($stationName) ?></td>
              <td><strong>TSh <?= number_format($ss['total'], 2) ?></strong></td>
              <td><?= $stationCount ?></td>
              <td>TSh <?= number_format($avgPerTransaction, 2) ?></td>
              <td>
                <div class="progress" style="height: 20px;">
                  <div class="progress-bar" style="width: <?= $percentage ?>%"><?= round($percentage, 1) ?>%</div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <div class="card p-4 mt-4">
    <h5>Recent Sales Transactions</h5>
    <?php if (empty($sales)): ?>
    <p class="text-center text-muted mt-3">No sales data found for the selected filters.</p>
    <?php else: ?>
    <table class="table table-striped mt-3">
      <thead>
        <tr>
          <th>Date</th>
          <th>Station</th>
          <th>Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($sales as $s): 
          $stationName = $pdo->query("SELECT name FROM stations WHERE id = " . intval($s['station_id']))->fetch()['name'] ?? 'Unknown';
        ?>
        <tr>
          <td><?= esc($s['created_at']) ?></td>
          <td><?= esc($stationName) ?></td>
          <td>TSh <?= number_format($s['amount'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
