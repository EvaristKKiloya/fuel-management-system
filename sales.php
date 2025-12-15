<?php
require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Sales Analytics</h2>
  
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card p-3 text-center" style="background: #28a745; color: #fff;">
        <h6>Today's Sales</h6>
        <h3>TSh <?= number_format(rand(500000, 2000000)) ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 text-center" style="background: #007bff; color: #fff;">
        <h6>This Week</h6>
        <h3>TSh <?= number_format(rand(3000000, 8000000)) ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 text-center" style="background: #ffa502; color: #fff;">
        <h6>This Month</h6>
        <h3>TSh <?= number_format(rand(15000000, 30000000)) ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 text-center" style="background: #2c3e50; color: #fff;">
        <h6>Total Transactions</h6>
        <h3><?= number_format(rand(500, 2000)) ?></h3>
      </div>
    </div>
  </div>
  
  <div class="row">
    <div class="col-md-12">
      <div class="card p-4">
        <h5>Sales by Station (This Month)</h5>
        <table class="table table-striped mt-3">
          <thead>
            <tr>
              <th>Station</th>
              <th>Petrol Sales</th>
              <th>Diesel Sales</th>
              <th>Kerosene Sales</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <?php for($i=1; $i<=8; $i++): 
              $p = rand(500000, 2000000);
              $d = rand(800000, 3000000);
              $k = rand(200000, 800000);
            ?>
            <tr>
              <td>Station <?= $i ?></td>
              <td>TSh <?= number_format($p) ?></td>
              <td>TSh <?= number_format($d) ?></td>
              <td>TSh <?= number_format($k) ?></td>
              <td><strong>TSh <?= number_format($p+$d+$k) ?></strong></td>
            </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
