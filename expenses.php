<?php
require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Expenses</h2>
  
  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card p-3">
        <h6>Total Expenses This Month</h6>
        <h3 class="text-danger">TSh <?= number_format(rand(5000000, 15000000)) ?></h3>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card p-3">
        <h6>Average Daily Expense</h6>
        <h3 class="text-warning">TSh <?= number_format(rand(200000, 600000)) ?></h3>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card p-3">
        <h6>Pending Payments</h6>
        <h3 class="text-info">TSh <?= number_format(rand(500000, 2000000)) ?></h3>
      </div>
    </div>
  </div>
  
  <div class="card p-4">
    <h5>Recent Expenses</h5>
    <table class="table table-striped mt-3">
      <thead>
        <tr>
          <th>Date</th>
          <th>Category</th>
          <th>Description</th>
          <th>Amount</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $categories = ['Maintenance', 'Fuel Purchase', 'Salaries', 'Utilities', 'Supplies'];
        for($i=0; $i<10; $i++): 
        ?>
        <tr>
          <td><?= date('Y-m-d', strtotime('-'.rand(0,30).' days')) ?></td>
          <td><?= $categories[array_rand($categories)] ?></td>
          <td>Expense item #<?= rand(100,999) ?></td>
          <td>TSh <?= number_format(rand(50000, 500000)) ?></td>
          <td><span class="badge bg-success">Paid</span></td>
        </tr>
        <?php endfor; ?>
      </tbody>
    </table>
    
    <button class="btn btn-primary mt-3">Add New Expense</button>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
