<?php
require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Clients & Partners</h2>
  
  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card p-3 text-center" style="background: #28a745; color: #fff;">
        <h6>Total Clients</h6>
        <h3><?= rand(100, 500) ?></h3>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card p-3 text-center" style="background: #007bff; color: #fff;">
        <h6>Active Contracts</h6>
        <h3><?= rand(50, 200) ?></h3>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card p-3 text-center" style="background: #ffa502; color: #fff;">
        <h6>New This Month</h6>
        <h3><?= rand(5, 20) ?></h3>
      </div>
    </div>
  </div>
  
  <div class="card p-4">
    <h5>Client Directory</h5>
    <table class="table table-striped mt-3">
      <thead>
        <tr>
          <th>ID</th>
          <th>Company Name</th>
          <th>Contact Person</th>
          <th>Phone</th>
          <th>Email</th>
          <th>Type</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $types = ['Corporate', 'Individual', 'Government', 'Transport'];
        for($i=1; $i<=10; $i++): 
        ?>
        <tr>
          <td>CLT<?= str_pad($i, 4, '0', STR_PAD_LEFT) ?></td>
          <td>Company <?= $i ?></td>
          <td>Contact <?= $i ?></td>
          <td>+255 <?= rand(700000000, 799999999) ?></td>
          <td>contact<?= $i ?>@company.com</td>
          <td><?= $types[array_rand($types)] ?></td>
          <td><span class="badge bg-success">Active</span></td>
          <td>
            <button class="btn btn-sm btn-primary">View</button>
          </td>
        </tr>
        <?php endfor; ?>
      </tbody>
    </table>
    
    <button class="btn btn-primary mt-3">Add New Client</button>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
