<?php
require_once __DIR__ . '/inc/header.php';

// Fetch regions and aggregate stats
try {
  $regions = $pdo->query('SELECT id, name FROM regions ORDER BY name')->fetchAll();
} catch (Exception $e) {
  $regions = [];
}

// helper to compute aggregates for demo if DB empty
function demo_number($min, $max){ return rand($min,$max); }
?>
<div class="container-fluid">
  <h2 class="mb-4">Dashboard</h2>
  <div class="row g-3">
    <?php
    // Show 8 stations (Station 1 to 8) with 3 products each
    $products = [
      ['name' => 'Petrol', 'icon' => '⛽', 'color' => 'text-success'],
      ['name' => 'Diesel', 'icon' => '🛢️', 'color' => 'text-primary'],
      ['name' => 'Kerosene', 'icon' => '🔥', 'color' => 'text-warning']
    ];
    
    // Define solid colors for each station (Only 3 colors: Green, Black, Blue)
    $stationColors = [
      1 => 'background: #28a745;',  // Green - Row 1
      2 => 'background: #28a745;',  // Green - Row 1
      3 => 'background: #28a745;',  // Green - Row 1
      4 => 'background: #2c3e50;',  // Black - Row 2
      5 => 'background: #2c3e50;',  // Black - Row 2
      6 => 'background: #2c3e50;',  // Black - Row 2
      7 => 'background: #007bff;',  // Blue - Row 3
      8 => 'background: #007bff;'   // Blue - Row 3
    ];
    
    for ($i = 1; $i <= 8; $i++) {
      ?>
      <div class="col-12 col-md-4">
        <div class="card card-tile p-3 h-100" style="<?= $stationColors[$i] ?> color: #fff; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.3)'" onmouseout="this.style.transform=''; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.2)'">
          <div class="d-flex align-items-center mb-2">
            <span class="me-2 fs-4">🏭</span>
            <h5 class="mb-0" style="color: #fff;">Station <?= $i ?></h5>
          </div>
          <div class="mb-2">
            <?php
            $stationTotal = 0;
            foreach ($products as $prod):
              $liters = demo_number(500, 20000);
              $salesAmt = demo_number(10000, 200000); // amount in TSh
              $stationTotal += $salesAmt;
            ?>
              <div class="d-flex align-items-center mb-1" style="color: #fff;">
                <span class="me-1" style="font-size: 1.2rem;"><?= $prod['icon'] ?></span>
                <span class="me-1" style="color: #fff;"><strong><?= $prod['name'] ?>:</strong></span>
                <span style="color: #fff;"><?= $liters ?> L</span>
                <span class="ms-auto small" style="color: rgba(255,255,255,0.9);">Sales: <span class="tsh-badge" style="background: rgba(255,255,255,0.2); color: #fff; padding: 2px 8px; border-radius: 10px; font-weight: 600;">TSh <?= number_format($salesAmt) ?></span></span>
              </div>
            <?php endforeach; ?>
            <div class="station-total d-flex align-items-center justify-content-between mt-2" style="background: rgba(255,255,255,0.15); padding: 8px 12px; border-radius: 6px;">
              <div class="fw-bold" style="color: #fff;">Total</div>
              <div class="fw-semibold" style="background: rgba(255,255,255,0.25); color: #fff; padding: 4px 12px; border-radius: 10px; font-weight: 700;">TSh <?= number_format($stationTotal) ?></div>
            </div>
          </div>
          <a class="stretched-link" href="station_details.php?id=<?= $i ?>"></a>
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
                    <h4 class="mb-0" style="color: #fff; font-weight: 700;"><?= demo_number(50,300) ?></h4>
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
                    <h4 class="mb-0" style="color: #fff; font-weight: 700;"><?= number_format(demo_number(50000,1000000)) ?> L</h4>
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
                    <h4 class="mb-0" style="color: #fff; font-weight: 700;">TSh <?= number_format(demo_number(50000,200000)) ?></h4>
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
                <div class="mt-1"><strong style="font-size: 1.3rem;"><?= demo_number(0,15) ?></strong></div>
                <small style="opacity: 0.9;">Low Fuel Alerts</small>
              </div>
            </a>
          </div>
          <div class="col-md-4">
            <a href="deliveries.php" class="text-decoration-none" style="color: inherit;">
              <div class="p-2" style="background: rgba(255,255,255,0.1); border-radius: 8px; text-align: center; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                <i class="bi bi-truck" style="font-size: 1.5rem; color: #ffd93d;"></i>
                <div class="mt-1"><strong style="font-size: 1.3rem;"><?= demo_number(0,10) ?></strong></div>
                <small style="opacity: 0.9;">Pending Deliveries</small>
              </div>
            </a>
          </div>
          <div class="col-md-4">
            <a href="reports.php" class="text-decoration-none" style="color: inherit;">
              <div class="p-2" style="background: rgba(255,255,255,0.1); border-radius: 8px; text-align: center; cursor: pointer; transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                <i class="bi bi-trophy-fill" style="font-size: 1.5rem; color: #ffd700;"></i>
                <div class="mt-1"><strong style="font-size: 1.3rem;">Station #<?= demo_number(1,200) ?></strong></div>
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
        <a class="btn btn-secondary" href="alerts.php">View Alerts</a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
