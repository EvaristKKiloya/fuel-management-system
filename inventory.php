<?php
require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Inventory Management</h2>
  
  <div class="row mb-4">
    <div class="col-md-12">
      <div class="card p-4">
        <h5>Current Inventory</h5>
        <p class="text-muted">Track equipment, supplies, and spare parts</p>
        
        <table class="table table-striped mt-3">
          <thead>
            <tr>
              <th>Item Code</th>
              <th>Item Name</th>
              <th>Category</th>
              <th>Quantity</th>
              <th>Unit</th>
              <th>Location</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $items = [
              ['Pump Parts', 'Equipment'],
              ['Hoses', 'Supplies'],
              ['Nozzles', 'Equipment'],
              ['Safety Gear', 'Safety'],
              ['Filters', 'Maintenance']
            ];
            foreach($items as $idx => $item): 
              $qty = rand(5, 100);
            ?>
            <tr>
              <td>INV-<?= str_pad($idx+1, 4, '0', STR_PAD_LEFT) ?></td>
              <td><?= $item[0] ?></td>
              <td><?= $item[1] ?></td>
              <td><?= $qty ?></td>
              <td>Units</td>
              <td>Warehouse <?= rand(1,3) ?></td>
              <td>
                <?php if($qty < 20): ?>
                  <span class="badge bg-danger">Low Stock</span>
                <?php else: ?>
                  <span class="badge bg-success">In Stock</span>
                <?php endif; ?>
              </td>
              <td>
                <button class="btn btn-sm btn-primary">Update</button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        
        <button class="btn btn-primary mt-3">Add New Item</button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
