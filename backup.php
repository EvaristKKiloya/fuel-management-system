<?php
require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Backup Management</h2>
  
  <div class="row mb-4">
    <div class="col-md-12">
      <div class="card p-4">
        <h5>Create New Backup</h5>
        <p class="text-muted">Backup your database and system files</p>
        
        <form class="mb-4">
          <div class="row">
            <div class="col-md-4">
              <label class="form-label">Backup Type</label>
              <select class="form-control">
                <option>Full Backup (Database + Files)</option>
                <option>Database Only</option>
                <option>Files Only</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Backup Name</label>
              <input type="text" class="form-control" value="backup_<?= date('Ymd_His') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">&nbsp;</label>
              <button type="submit" class="btn btn-success w-100">Create Backup</button>
            </div>
          </div>
        </form>
        
        <hr>
        
        <h5 class="mt-4">Backup History</h5>
        <table class="table table-striped mt-3">
          <thead>
            <tr>
              <th>Backup Name</th>
              <th>Type</th>
              <th>Date Created</th>
              <th>Size</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $types = ['Full Backup', 'Database Only', 'Files Only'];
            for($i=1; $i<=8; $i++): 
            ?>
            <tr>
              <td>backup_<?= date('Ymd_His', strtotime('-'.rand(1,30).' days')) ?></td>
              <td><?= $types[array_rand($types)] ?></td>
              <td><?= date('Y-m-d H:i:s', strtotime('-'.rand(1,30).' days')) ?></td>
              <td><?= rand(50,500) ?> MB</td>
              <td><span class="badge bg-success">Completed</span></td>
              <td>
                <button class="btn btn-sm btn-primary">Download</button>
                <button class="btn btn-sm btn-warning">Restore</button>
                <button class="btn btn-sm btn-danger">Delete</button>
              </td>
            </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <div class="row">
    <div class="col-md-6">
      <div class="card p-4">
        <h5>Automatic Backup Schedule</h5>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" checked>
          <label class="form-check-label">Enable automatic backups</label>
        </div>
        <div class="mb-3">
          <label class="form-label">Frequency</label>
          <select class="form-control">
            <option selected>Daily at 2:00 AM</option>
            <option>Weekly (Sunday)</option>
            <option>Monthly (1st of month)</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Retention Period</label>
          <select class="form-control">
            <option>Keep last 7 backups</option>
            <option selected>Keep last 30 backups</option>
            <option>Keep all backups</option>
          </select>
        </div>
        <button class="btn btn-primary">Save Schedule</button>
      </div>
    </div>
    
    <div class="col-md-6">
      <div class="card p-4">
        <h5>Backup Status</h5>
        <p class="mb-2"><strong>Last Backup:</strong> <?= date('Y-m-d H:i:s', strtotime('-1 day')) ?></p>
        <p class="mb-2"><strong>Next Scheduled:</strong> <?= date('Y-m-d H:i:s', strtotime('+1 day')) ?></p>
        <p class="mb-2"><strong>Total Backups:</strong> 8</p>
        <p class="mb-2"><strong>Total Size:</strong> 2.4 GB</p>
        <p class="mb-2"><strong>Status:</strong> <span class="badge bg-success">Active</span></p>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
