<?php
require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Fuel Stock Levels</h2>
  
  <div class="row g-3">
    <?php for($i=1; $i<=8; $i++): 
      $petrol = rand(5000, 50000);
      $diesel = rand(8000, 60000);
      $kerosene = rand(3000, 30000);
    ?>
    <div class="col-md-6 col-lg-3">
      <div class="card p-3">
        <h5>Station <?= $i ?></h5>
        <hr>
        <div class="mb-2">
          <strong>⛽ Petrol:</strong> <?= number_format($petrol) ?> L
          <?php if($petrol < 10000): ?>
            <span class="badge bg-danger ms-2">Low</span>
          <?php endif; ?>
        </div>
        <div class="mb-2">
          <strong>🛢️ Diesel:</strong> <?= number_format($diesel) ?> L
          <?php if($diesel < 15000): ?>
            <span class="badge bg-warning ms-2">Medium</span>
          <?php endif; ?>
        </div>
        <div class="mb-2">
          <strong>🔥 Kerosene:</strong> <?= number_format($kerosene) ?> L
        </div>
        <a href="station_details.php?id=<?= $i ?>" class="btn btn-sm btn-outline-primary mt-2">View Details</a>
      </div>
    </div>
    <?php endfor; ?>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
