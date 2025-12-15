<?php
require_once __DIR__ . '/config.php';

// Get fuel stocks data from database
$query = "SELECT 
    s.id, 
    s.name,
    COALESCE(fs.petrol_l, 0) as petrol_l,
    COALESCE(fs.diesel_l, 0) as diesel_l,
    COALESCE(fs.kerosene_l, 0) as kerosene_l
FROM stations s
LEFT JOIN fuel_stocks fs ON s.id = fs.station_id
ORDER BY s.id";

$stations = $pdo->query($query)->fetchAll();

require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Fuel Stock Levels</h2>
  
  <div class="row g-3">
    <?php if (empty($stations)): ?>
    <div class="col-md-12">
      <div class="alert alert-info">
        No stations found in the database. Please add stations first.
      </div>
    </div>
    <?php else: ?>
    <?php foreach($stations as $station): 
      $petrol = $station['petrol_l'];
      $diesel = $station['diesel_l'];
      $kerosene = $station['kerosene_l'];
    ?>
    <div class="col-md-6 col-lg-3">
      <div class="card p-3">
        <h5><?= esc($station['name']) ?></h5>
        <hr>
        <div class="mb-2">
          <strong>⛽ Petrol:</strong> <?= number_format($petrol) ?> L
          <?php if($petrol < 10000): ?>
            <span class="badge bg-danger ms-2">Low</span>
          <?php elseif($petrol < 20000): ?>
            <span class="badge bg-warning ms-2">Medium</span>
          <?php else: ?>
            <span class="badge bg-success ms-2">Good</span>
          <?php endif; ?>
        </div>
        <div class="mb-2">
          <strong>🛢️ Diesel:</strong> <?= number_format($diesel) ?> L
          <?php if($diesel < 15000): ?>
            <span class="badge bg-danger ms-2">Low</span>
          <?php elseif($diesel < 30000): ?>
            <span class="badge bg-warning ms-2">Medium</span>
          <?php else: ?>
            <span class="badge bg-success ms-2">Good</span>
          <?php endif; ?>
        </div>
        <div class="mb-2">
          <strong>🔥 Kerosene:</strong> <?= number_format($kerosene) ?> L
          <?php if($kerosene < 5000): ?>
            <span class="badge bg-danger ms-2">Low</span>
          <?php elseif($kerosene < 10000): ?>
            <span class="badge bg-warning ms-2">Medium</span>
          <?php else: ?>
            <span class="badge bg-success ms-2">Good</span>
          <?php endif; ?>
        </div>
        <div class="mt-3">
          <small class="text-muted">Total: <?= number_format($petrol + $diesel + $kerosene) ?> L</small>
        </div>
        <a href="station_details.php?id=<?= $station['id'] ?>" class="btn btn-sm btn-outline-primary mt-2">View Details</a>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
