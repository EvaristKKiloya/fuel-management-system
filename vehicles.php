<?php
require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Fleet Management</h2>
  
  <div class="row mb-4">
    <div class="col-md-12">
      <div class="card p-4">
        <h5>Fleet Overview</h5>
        <p class="text-muted">Manage fuel delivery trucks and vehicles</p>
        
        <table class="table table-striped mt-3">
          <thead>
            <tr>
              <th>Truck ID</th>
              <th>Registration</th>
              <th>Type</th>
              <th>Capacity (L)</th>
              <th>Status</th>
              <th>Current Location</th>
              <th>Next Service</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php for($i=1; $i<=8; $i++): ?>
            <tr>
              <td>TRK-<?= str_pad($i, 3, '0', STR_PAD_LEFT) ?></td>
              <td>T <?= rand(100,999) ?> ABC</td>
              <td>Tanker</td>
              <td><?= number_format(rand(10000, 40000)) ?></td>
              <td>
                <?php 
                $statuses = [
                  ['Active', 'success'],
                  ['In Transit', 'info'],
                  ['Maintenance', 'warning']
                ];
                $status = $statuses[array_rand($statuses)];
                ?>
                <span class="badge bg-<?= $status[1] ?>"><?= $status[0] ?></span>
              </td>
              <td>Station <?= rand(1,8) ?></td>
              <td><?= date('Y-m-d', strtotime('+'.rand(5,30).' days')) ?></td>
              <td>
                <button class="btn btn-sm btn-primary">Details</button>
              </td>
            </tr>
            <?php endfor; ?>
          </tbody>
        </table>
        
        <button class="btn btn-primary mt-3">Add New Vehicle</button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
