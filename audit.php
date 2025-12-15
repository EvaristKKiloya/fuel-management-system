<?php
require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Audit & Logs</h2>
  
  <div class="row mb-4">
    <div class="col-md-12">
      <div class="card p-4">
        <h5>System Activity Logs</h5>
        <p class="text-muted">Track all system activities and changes</p>
        
        <div class="mb-3">
          <label class="form-label">Filter by Date Range</label>
          <div class="row">
            <div class="col-md-3">
              <input type="date" class="form-control" value="<?= date('Y-m-d', strtotime('-7 days')) ?>">
            </div>
            <div class="col-md-3">
              <input type="date" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-3">
              <select class="form-control">
                <option>All Users</option>
                <option>Admin</option>
                <option>Manager</option>
                <option>Staff</option>
              </select>
            </div>
            <div class="col-md-3">
              <button class="btn btn-primary w-100">Filter</button>
            </div>
          </div>
        </div>
        
        <table class="table table-striped mt-3">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>User</th>
              <th>Action</th>
              <th>Module</th>
              <th>IP Address</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $actions = ['Login', 'Update Record', 'Delete Record', 'Add Record', 'Export Data', 'Change Settings'];
            $modules = ['Dashboard', 'Stations', 'Sales', 'Staff', 'Settings', 'Reports'];
            for($i=0; $i<20; $i++): 
            ?>
            <tr>
              <td><?= date('Y-m-d H:i:s', strtotime('-'.rand(0,48).' hours')) ?></td>
              <td>User<?= rand(1,10) ?></td>
              <td><?= $actions[array_rand($actions)] ?></td>
              <td><?= $modules[array_rand($modules)] ?></td>
              <td>192.168.1.<?= rand(1,255) ?></td>
              <td><span class="badge bg-success">Success</span></td>
            </tr>
            <?php endfor; ?>
          </tbody>
        </table>
        
        <div class="mt-3">
          <button class="btn btn-primary">Export Logs</button>
          <button class="btn btn-secondary">Clear Old Logs</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
