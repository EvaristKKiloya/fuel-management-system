<?php
require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2>Reports</h2>
  <div class="card p-3 mb-3">
    <form class="row g-2">
      <div class="col-md-3"><input class="form-control" type="date" name="from"></div>
      <div class="col-md-3"><input class="form-control" type="date" name="to"></div>
      <div class="col-md-3"><select class="form-select" name="region"><option value="">All regions</option></select></div>
      <div class="col-md-3"><button class="btn btn-primary">Apply</button></div>
    </form>
  </div>

  <div class="row">
    <div class="col-md-8">
      <div class="card p-3 mb-3"><h6>Total sales over time</h6><canvas id="salesOverTime" height="160"></canvas></div>
      <div class="card p-3 mb-3"><h6>Fuel consumption trends</h6><canvas id="consumptionTrends" height="160"></canvas></div>
    </div>
    <div class="col-md-4">
      <div class="card p-3"><h6>Station ranking</h6><canvas id="stationRank" height="220"></canvas></div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
