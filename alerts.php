<?php
require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2>Alerts Center</h2>
  <div class="card p-3">
    <table class="table">
      <thead><tr><th>Station</th><th>Fuel type</th><th>Current liters</th><th>Threshold</th><th>Level</th><th>Time</th></tr></thead>
      <tbody>
        <tr><td>Station 12</td><td>Petrol</td><td>120</td><td>200</td><td><span class="badge bg-warning">Low</span></td><td>2025-12-03 10:12</td></tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
