<?php
require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Suppliers</h2>
  
  <div class="row mb-4">
    <div class="col-md-12">
      <div class="card p-4">
        <h5>Fuel Suppliers</h5>
        <p class="text-muted">Manage fuel suppliers and purchase orders</p>
        
        <table class="table table-striped mt-3">
          <thead>
            <tr>
              <th>Supplier ID</th>
              <th>Company Name</th>
              <th>Contact Person</th>
              <th>Phone</th>
              <th>Email</th>
              <th>Fuel Types</th>
              <th>Rating</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $suppliers = [
              ['Total Tanzania', 'Petrol, Diesel'],
              ['Puma Energy', 'Diesel, Kerosene'],
              ['Shell Tanzania', 'Petrol, Diesel, Kerosene'],
              ['Oryx Energies', 'Diesel'],
              ['Hass Petroleum', 'Petrol, Diesel']
            ];
            foreach($suppliers as $idx => $sup): 
            ?>
            <tr>
              <td>SUP<?= str_pad($idx+1, 3, '0', STR_PAD_LEFT) ?></td>
              <td><?= $sup[0] ?></td>
              <td>Manager <?= $idx+1 ?></td>
              <td>+255 <?= rand(700000000, 799999999) ?></td>
              <td>contact@<?= strtolower(str_replace(' ', '', $sup[0])) ?>.co.tz</td>
              <td><?= $sup[1] ?></td>
              <td>
                <?php for($s=0; $s<rand(3,5); $s++): ?>⭐<?php endfor; ?>
              </td>
              <td><span class="badge bg-success">Active</span></td>
              <td>
                <button class="btn btn-sm btn-primary">Details</button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        
        <button class="btn btn-primary mt-3">Add New Supplier</button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
