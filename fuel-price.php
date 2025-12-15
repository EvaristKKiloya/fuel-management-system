<?php
require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Fuel Pricing</h2>
  
  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card p-4" style="background: #28a745; color: #fff;">
        <h5>⛽ Petrol</h5>
        <h2>TSh <?= number_format(rand(2500, 3500)) ?></h2>
        <small>Per Liter</small>
        <button class="btn btn-light btn-sm mt-3">Update Price</button>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card p-4" style="background: #007bff; color: #fff;">
        <h5>🛢️ Diesel</h5>
        <h2>TSh <?= number_format(rand(2200, 3200)) ?></h2>
        <small>Per Liter</small>
        <button class="btn btn-light btn-sm mt-3">Update Price</button>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card p-4" style="background: #ffa502; color: #fff;">
        <h5>🔥 Kerosene</h5>
        <h2>TSh <?= number_format(rand(1800, 2800)) ?></h2>
        <small>Per Liter</small>
        <button class="btn btn-light btn-sm mt-3">Update Price</button>
      </div>
    </div>
  </div>
  
  <div class="card p-4">
    <h5>Price History</h5>
    <table class="table table-striped mt-3">
      <thead>
        <tr>
          <th>Date</th>
          <th>Fuel Type</th>
          <th>Previous Price</th>
          <th>New Price</th>
          <th>Change</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $fuels = ['Petrol', 'Diesel', 'Kerosene'];
        for($i=0; $i<8; $i++): 
          $old = rand(2000, 3000);
          $new = rand(2000, 3000);
          $change = $new - $old;
        ?>
        <tr>
          <td><?= date('Y-m-d', strtotime('-'.rand(0,60).' days')) ?></td>
          <td><?= $fuels[array_rand($fuels)] ?></td>
          <td>TSh <?= number_format($old) ?></td>
          <td>TSh <?= number_format($new) ?></td>
          <td>
            <span class="badge bg-<?= $change >= 0 ? 'success' : 'danger' ?>">
              <?= $change >= 0 ? '+' : '' ?><?= $change ?>
            </span>
          </td>
        </tr>
        <?php endfor; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
