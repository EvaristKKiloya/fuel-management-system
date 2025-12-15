<?php
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
$q = 'SELECT s.id, s.name, fs.petrol_l, fs.diesel_l, fs.gasoline_l, IFNULL(sales.today_sales,0) AS sales_today FROM stations s LEFT JOIN fuel_stocks fs ON fs.station_id = s.id LEFT JOIN (SELECT station_id, SUM(amount) AS today_sales FROM sales WHERE DATE(created_at)=DATE(NOW()) GROUP BY station_id) sales ON sales.station_id = s.id';
if ($regionId) $q .= ' WHERE s.region_id = ' . $regionId;
$q .= ' ORDER BY s.name LIMIT 100';

try{
    $stations = $pdo->query($q)->fetchAll();
}catch(Exception $e){
    $stations = [];
}

?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <a href="/dashboard.php" class="btn btn-sm btn-outline-secondary">&larr; Back</a>
      <h3 class="d-inline ms-2"><?= esc($region ? ($region['name'] . ' – ' . count($stations) . ' Fuel Stations') : 'Stations') ?></h3>
    </div>
    <div class="d-flex gap-2">
      <input class="form-control" placeholder="Search station" id="searchInput">
    </div>
  </div>

  <div class="row">
    <div class="col-md-8">
      <table class="table table-striped">
        <thead><tr><th>ID</th><th>Name</th><th>Petrol (L)</th><th>Diesel (L)</th><th>Gasoline (L)</th><th>Sales Today</th><th>Status</th><th></th></tr></thead>
        <tbody id="stationsTable">
          <?php foreach($stations as $s): ?>
            <tr>
              <td><?= esc($s['id']) ?></td>
              <td><?= esc($s['name']) ?></td>
              <td><?= esc($s['petrol_l'] ?? '—') ?></td>
              <td><?= esc($s['diesel_l'] ?? '—') ?></td>
              <td><?= esc($s['gasoline_l'] ?? '—') ?></td>
              <td>$<?= number_format($s['sales_today']) ?></td>
              <td><span class="badge bg-success">Normal</span></td>
              <td><a class="btn btn-sm btn-outline-primary" href="station_details.php?id=<?= $s['id'] ?>">View</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="col-md-4">
      <div class="card p-3 mb-3">
        <h6>Fuel Distribution</h6>
        <canvas id="fuelPie" height="180"></canvas>
      </div>
      <div class="card p-3 mb-3">
        <h6>Daily Sales Trend</h6>
        <canvas id="salesLine" height="180"></canvas>
      </div>
      <div class="card p-3">
        <h6>Top 5 Stations</h6>
        <canvas id="topBars" height="180"></canvas>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
