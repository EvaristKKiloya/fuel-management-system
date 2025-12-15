<?php
require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Staff Management</h2>
  
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card p-3 text-center" style="background: #28a745; color: #fff;">
        <h6>Total Staff</h6>
        <h3><?= rand(50, 150) ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 text-center" style="background: #007bff; color: #fff;">
        <h6>On Duty</h6>
        <h3><?= rand(30, 80) ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 text-center" style="background: #ffa502; color: #fff;">
        <h6>On Leave</h6>
        <h3><?= rand(5, 15) ?></h3>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 text-center" style="background: #2c3e50; color: #fff;">
        <h6>New This Month</h6>
        <h3><?= rand(2, 8) ?></h3>
      </div>
    </div>
  </div>
  
  <div class="card p-4">
    <h5>Staff Directory</h5>
    <table class="table table-striped mt-3">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Position</th>
          <th>Station</th>
          <th>Contact</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $positions = ['Manager', 'Attendant', 'Supervisor', 'Cashier', 'Mechanic'];
        for($i=1; $i<=10; $i++): 
        ?>
        <tr>
          <td>EMP<?= str_pad($i, 3, '0', STR_PAD_LEFT) ?></td>
          <td>Employee <?= $i ?></td>
          <td><?= $positions[array_rand($positions)] ?></td>
          <td>Station <?= rand(1,8) ?></td>
          <td>+255 <?= rand(700000000, 799999999) ?></td>
          <td><span class="badge bg-success">Active</span></td>
          <td>
            <button class="btn btn-sm btn-primary">View</button>
          </td>
        </tr>
        <?php endfor; ?>
      </tbody>
    </table>
    
    <button class="btn btn-primary mt-3">Add New Staff</button>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
