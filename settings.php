<?php
require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">System Settings</h2>
  
  <div class="row">
    <div class="col-md-8">
      <div class="card p-4 mb-4">
        <h5>General Settings</h5>
        <form>
          <div class="mb-3">
            <label class="form-label">Company Name</label>
            <input type="text" class="form-control" value="Fuel Management System">
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" value="admin@fuelmanagement.co.tz">
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" class="form-control" value="+255 700 000 000">
          </div>
          <div class="mb-3">
            <label class="form-label">Currency</label>
            <select class="form-control">
              <option selected>TSh - Tanzanian Shilling</option>
              <option>USD - US Dollar</option>
              <option>EUR - Euro</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Timezone</label>
            <select class="form-control">
              <option selected>Africa/Dar_es_Salaam</option>
              <option>UTC</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
      </div>
      
      <div class="card p-4">
        <h5>Notification Settings</h5>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" checked>
          <label class="form-check-label">Email notifications for low fuel alerts</label>
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" checked>
          <label class="form-check-label">SMS notifications for deliveries</label>
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox">
          <label class="form-check-label">Daily sales report</label>
        </div>
        <button class="btn btn-primary">Update Notifications</button>
      </div>
    </div>
    
    <div class="col-md-4">
      <div class="card p-4 mb-3">
        <h6>System Info</h6>
        <p class="mb-1"><strong>Version:</strong> 1.0.0</p>
        <p class="mb-1"><strong>Last Update:</strong> <?= date('Y-m-d') ?></p>
        <p class="mb-1"><strong>Server:</strong> XAMPP</p>
      </div>
      
      <div class="card p-4">
        <h6>Quick Actions</h6>
        <button class="btn btn-secondary btn-sm w-100 mb-2">Clear Cache</button>
        <button class="btn btn-secondary btn-sm w-100 mb-2">Run Diagnostics</button>
        <button class="btn btn-danger btn-sm w-100">Reset System</button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
