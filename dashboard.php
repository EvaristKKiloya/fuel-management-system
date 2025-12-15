<?php
require_once __DIR__ . '/inc/header.php';

// Fetch all stations from database
try {
  $stationsData = $pdo->query('SELECT id, name FROM stations ORDER BY id')->fetchAll();
} catch (Exception $e) {
  $stationsData = [];
}

// Fetch total stock from database
try {
  $stockStmt = $pdo->query('SELECT SUM(petrol_l + diesel_l + kerosene_l) as total FROM fuel_stocks');
  $totalStock = $stockStmt->fetch()['total'] ?? 0;
} catch (Exception $e) {
  $totalStock = 0;
}

// Fetch today's sales
try {
  $salesStmt = $pdo->prepare('SELECT SUM(amount) as total FROM sales WHERE DATE(created_at) = CURDATE()');
  $salesStmt->execute();
  $todaySales = $salesStmt->fetch()['total'] ?? 0;
} catch (Exception $e) {
  $todaySales = 0;
}

// Fetch low fuel alerts count
try {
  $alertStmt = $pdo->query('SELECT COUNT(*) as count FROM fuel_stocks WHERE (petrol_l < 500 OR diesel_l < 500 OR kerosene_l < 500)');
  $alertCount = $alertStmt->fetch()['count'] ?? 0;
} catch (Exception $e) {
  $alertCount = 0;
}

// Fetch pending deliveries
try {
  $deliveryStmt = $pdo->query('SELECT COUNT(*) as count FROM deliveries WHERE status = "pending"');
  $pendingDeliveries = $deliveryStmt->fetch()['count'] ?? 0;
} catch (Exception $e) {
  $pendingDeliveries = 0;
}

// Fetch highest selling station today
try {
  $topStationStmt = $pdo->prepare('SELECT station_id, SUM(amount) as total FROM sales WHERE DATE(created_at) = CURDATE() GROUP BY station_id ORDER BY total DESC LIMIT 1');
  $topStationStmt->execute();
  $topStation = $topStationStmt->fetch();
  $topStationId = $topStation['station_id'] ?? 0;
  $topStationName = 'N/A';
  if ($topStationId) {
    $nameStmt = $pdo->prepare('SELECT name FROM stations WHERE id = ?');
    $nameStmt->execute([$topStationId]);
    $stationName = $nameStmt->fetch()['name'] ?? 'Station #' . $topStationId;
    $topStationName = $stationName;
  }
} catch (Exception $e) {
  $topStationName = 'N/A';
  $topStationId = 0;
}
?>
<div class="container-fluid">
  <h2 class="mb-4">Dashboard</h2>
  <div class="row g-3">
    <?php
    // Show stations from database
    $products = [
      ['name' => 'Petrol', 'icon' => '⛽', 'color' => 'text-success'],
      ['name' => 'Diesel', 'icon' => '🛢️', 'color' => 'text-primary'],
      ['name' => 'Kerosene', 'icon' => '🔥', 'color' => 'text-warning']
    ];
    
    // Define solid colors for stations
    $stationColors = [
      'background: #28a745;',  // Green
      'background: #2c3e50;',  // Black
      'background: #007bff;',  // Blue
    ];
    
    $colorIndex = 0;
    foreach ($stationsData as $station) {
      $stationColor = $stationColors[$colorIndex % 3];
      $colorIndex++;
      
      // Fetch fuel stock for this station
      try {
        $stockStmt = $pdo->prepare('SELECT petrol_l, diesel_l, kerosene_l FROM fuel_stocks WHERE station_id = ?');
        $stockStmt->execute([$station['id']]);
        $stock = $stockStmt->fetch();
        $petrol = $stock['petrol_l'] ?? 0;
        $diesel = $stock['diesel_l'] ?? 0;
        $kerosene = $stock['kerosene_l'] ?? 0;
        $stocks = [$petrol, $diesel, $kerosene];
      } catch (Exception $e) {
        $stocks = [0, 0, 0];
      }
      
      // Fetch sales for this station today
      try {
        $saleStmt = $pdo->prepare('SELECT SUM(amount) as total FROM sales WHERE station_id = ? AND DATE(created_at) = CURDATE()');
        $saleStmt->execute([$station['id']]);
        $stationSale = $saleStmt->fetch()['total'] ?? 0;
      } catch (Exception $e) {
        $stationSale = 0;
      }
      ?>
      <div class="col-12 col-md-4">
        <div class="card card-tile p-3 h-100" style="<?= $stationColor ?> color: #fff; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.3)'" onmouseout="this.style.transform=''; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.2)'">
          <div class="d-flex align-items-center mb-2">
            <span class="me-2 fs-4">🏭</span>
            <h5 class="mb-0" style="color: #fff;"><?= esc($station['name']) ?></h5>
          </div>
          <div class="mb-2">
            <?php
            foreach ($products as $idx => $prod):
              $liters = $stocks[$idx] ?? 0;
            ?>
              <div class="d-flex align-items-center mb-1" style="color: #fff;">
                <span class="me-1" style="font-size: 1.2rem;"><?= $prod['icon'] ?></span>
                <span class="me-1" style="color: #fff;"><strong><?= $prod['name'] ?>:</strong></span>
                <span style="color: #fff;"><?= number_format($liters, 0) ?> L</span>
              </div>
            <?php endforeach; ?>
            <div class="station-total d-flex align-items-center justify-content-between mt-2" style="background: rgba(255,255,255,0.15); padding: 8px 12px; border-radius: 6px;">
              <div class="fw-bold" style="color: #fff;">Today Sales</div>
              <div class="fw-semibold" style="background: rgba(255,255,255,0.25); color: #fff; padding: 4px 12px; border-radius: 10px; font-weight: 700;">TSh <?= number_format($stationSale) ?></div>
            </div>
          </div>
          <a class="stretched-link" href="station_details.php?id=<?= $station['id'] ?>"></a>
        </div>
      </div>
      <?php
    }
    ?>
  </div>

  <div class="row mt-4">
    <div class="col-md-8">
      <div class="card p-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none; box-shadow: 0 6px 20px rgba(0,0,0,0.15);">
        <div class="d-flex align-items-center mb-3">
          <i class="bi bi-graph-up-arrow me-2" style="font-size: 2rem;"></i>
          <h5 class="mb-0" style="color: #fff;">Total Network Overview</h5>
        </div>
        
        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <a href="station_list.php" class="text-decoration-none" style="color: inherit;">
              <div class="p-3" style="background: rgba(255,255,255,0.15); border-radius: 10px; border-left: 4px solid #ffa502; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                <div class="d-flex align-items-center">
                  <i class="bi bi-building me-2" style="font-size: 1.8rem; color: #ffa502;"></i>
                  <div>
                    <small style="opacity: 0.9;">Total Stations</small>
                    <h4 class="mb-0" style="color: #fff; font-weight: 700;"><?= count($stationsData) ?></h4>
                  </div>
                </div>
              </div>
            </a>
          </div>
          
          <div class="col-md-4">
            <a href="fuel-stock.php" class="text-decoration-none" style="color: inherit;">
              <div class="p-3" style="background: rgba(255,255,255,0.15); border-radius: 10px; border-left: 4px solid #28a745; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                <div class="d-flex align-items-center">
                  <i class="bi bi-droplet-fill me-2" style="font-size: 1.8rem; color: #28a745;"></i>
                  <div>
                    <small style="opacity: 0.9;">Fuel in Stock</small>
                    <h4 class="mb-0" style="color: #fff; font-weight: 700;"><?= number_format($totalStock, 0) ?> L</h4>
                  </div>
                </div>
              </div>
            </a>
          </div>
          
          <div class="col-md-4">
            <a href="sales.php" class="text-decoration-none" style="color: inherit;">
              <div class="p-3" style="background: rgba(255,255,255,0.15); border-radius: 10px; border-left: 4px solid #00d4ff; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                <div class="d-flex align-items-center">
                  <i class="bi bi-cash-coin me-2" style="font-size: 1.8rem; color: #00d4ff;"></i>
                  <div>
                    <small style="opacity: 0.9;">Sales Today</small>
                    <h4 class="mb-0" style="color: #fff; font-weight: 700;">TSh <?= number_format($todaySales) ?></h4>
                  </div>
                </div>
              </div>
            </a>
          </div>
        </div>
        
        <div class="row g-2">
          <div class="col-md-4">
            <a href="alerts.php" class="text-decoration-none" style="color: inherit;">
              <div class="p-2" style="background: rgba(255,255,255,0.1); border-radius: 8px; text-align: center; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.5rem; color: #ff6b6b;"></i>
                <div class="mt-1"><strong style="font-size: 1.3rem;"><?= $alertCount ?></strong></div>
                <small style="opacity: 0.9;">Low Fuel Alerts</small>
              </div>
            </a>
          </div>
          <div class="col-md-4">
            <a href="deliveries.php" class="text-decoration-none" style="color: inherit;">
              <div class="p-2" style="background: rgba(255,255,255,0.1); border-radius: 8px; text-align: center; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                <i class="bi bi-truck" style="font-size: 1.5rem; color: #ffd93d;"></i>
                <div class="mt-1"><strong style="font-size: 1.3rem;"><?= $pendingDeliveries ?></strong></div>
                <small style="opacity: 0.9;">Pending Deliveries</small>
              </div>
            </a>
          </div>
          <div class="col-md-4">
            <a href="reports.php" class="text-decoration-none" style="color: inherit;">
              <div class="p-2" style="background: rgba(255,255,255,0.1); border-radius: 8px; text-align: center; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                <i class="bi bi-trophy-fill" style="font-size: 1.5rem; color: #ffd700;"></i>
                <div class="mt-1"><strong style="font-size: 1.3rem;"><?= esc($topStationName) ?></strong></div>
                <small style="opacity: 0.9;">Highest Selling Today</small>
              </div>
            </a>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card p-3">
        <h6>Quick Actions</h6>
        <a class="btn btn-primary mb-2" href="reports.php">Generate Report</a>
        <a class="btn btn-secondary mb-2" href="alerts.php">View Alerts</a>
        <a class="btn btn-info mb-2" href="inventory.php">Manage Inventory</a>
        <a class="btn btn-warning" href="expenses.php">View Expenses</a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
