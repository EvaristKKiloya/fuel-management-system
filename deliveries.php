<?php
require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Deliveries & Trucks</h2>
  
  <div class="row mb-4">
    <div class="col-md-12">
      <div class="card p-4">
        <h5>Recent Deliveries</h5>
        <p class="text-muted">Track fuel deliveries and truck assignments</p>
        
        <table class="table table-striped mt-3">
          <thead>
            <tr>
              <th>ID</th>
              <th>Station</th>
              <th>Truck</th>
              <th>Fuel Type</th>
              <th>Quantity (L)</th>
              <th>Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php for($i=1; $i<=5; $i++): ?>
            <tr>
              <td>#DEL<?= str_pad($i, 3, '0', STR_PAD_LEFT) ?></td>
              <td>Station <?= rand(1,8) ?></td>
              <td>Truck-<?= rand(100,199) ?></td>
              <td>Diesel</td>
              <td><?= number_format(rand(5000,15000)) ?></td>
              <td><?= date('Y-m-d', strtotime('-'.rand(0,30).' days')) ?></td>
              <td><span class="badge bg-success">Completed</span></td>
            </tr>
            <?php endfor; ?>
          </tbody>
        </table>
        
        <a href="#" class="btn btn-primary mt-3">Schedule New Delivery</a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
