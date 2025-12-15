<?php
require_once __DIR__ . '/config.php';

// Create fuel prices table if it doesn't exist
if ($pdo->query("SHOW TABLES LIKE 'fuel_prices'")->rowCount() == 0) {
    $pdo->exec("CREATE TABLE `fuel_prices` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `fuel_type` ENUM('petrol', 'diesel', 'kerosene') NOT NULL,
      `price` DECIMAL(10, 2) NOT NULL,
      `previous_price` DECIMAL(10, 2) DEFAULT NULL,
      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      INDEX idx_fuel_type (`fuel_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Initialize with default prices
    $pdo->exec("INSERT INTO fuel_prices (fuel_type, price, previous_price) VALUES 
        ('petrol', 3000, NULL),
        ('diesel', 2800, NULL),
        ('kerosene', 2000, NULL)");
}

$message = '';
$messageType = '';

// Handle price update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fuel_type = $_POST['fuel_type'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $userName = $_SESSION['username'] ?? 'Guest';
    
    if (empty($fuel_type) || $price <= 0) {
        $message = "Invalid fuel type or price!";
        $messageType = "danger";
        
        // Log failed attempt
        log_audit($pdo, $userName, AUDIT_ACTION_UPDATE, 'Fuel Prices', 'Failed', 
            'Validation error: Invalid fuel type or price');
    } else {
        try {
            // Get the previous price
            $stmt = $pdo->prepare("SELECT price FROM fuel_prices WHERE fuel_type = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$fuel_type]);
            $prev = $stmt->fetch();
            $prevPrice = $prev ? $prev['price'] : null;
            
            // Insert new price entry (allow multiple entries per fuel type for history)
            $stmt = $pdo->prepare("INSERT INTO fuel_prices (fuel_type, price, previous_price) VALUES (?, ?, ?)");
            $stmt->execute([$fuel_type, $price, $prevPrice]);
            
            $message = "Price updated successfully!";
            $messageType = "success";
            
            // Log price update
            log_audit($pdo, $userName, AUDIT_ACTION_UPDATE, 'Fuel Prices', 'Success', 
                "Updated $fuel_type price from " . ($prevPrice ?? 'N/A') . " to $price");
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "danger";
            
            // Log error
            log_audit($pdo, $userName, AUDIT_ACTION_UPDATE, 'Fuel Prices', 'Failed', 
                'Error: ' . $e->getMessage());
        }
    }
}

// Get current prices
$currentPrices = [];
foreach (['petrol', 'diesel', 'kerosene'] as $fuel) {
    $stmt = $pdo->prepare("SELECT price FROM fuel_prices WHERE fuel_type = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$fuel]);
    $result = $stmt->fetch();
    $currentPrices[$fuel] = $result ? $result['price'] : 0;
}

// Get price history
$history = $pdo->query("SELECT * FROM fuel_prices ORDER BY created_at DESC LIMIT 20")->fetchAll();

require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Fuel Pricing</h2>
  
  <?php if ($message): ?>
  <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= esc($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  
  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card p-4" style="background: #28a745; color: #fff;">
        <h5>⛽ Petrol</h5>
        <h2>TSh <?= number_format($currentPrices['petrol']) ?></h2>
        <small>Per Liter</small>
        <form method="POST" class="mt-3">
          <input type="hidden" name="fuel_type" value="petrol">
          <div class="input-group mb-2">
            <input type="number" name="price" class="form-control" step="0.01" placeholder="New price" required>
            <button type="submit" class="btn btn-light">Update</button>
          </div>
        </form>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card p-4" style="background: #007bff; color: #fff;">
        <h5>🛢️ Diesel</h5>
        <h2>TSh <?= number_format($currentPrices['diesel']) ?></h2>
        <small>Per Liter</small>
        <form method="POST" class="mt-3">
          <input type="hidden" name="fuel_type" value="diesel">
          <div class="input-group mb-2">
            <input type="number" name="price" class="form-control" step="0.01" placeholder="New price" required>
            <button type="submit" class="btn btn-light">Update</button>
          </div>
        </form>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card p-4" style="background: #ffa502; color: #fff;">
        <h5>🔥 Kerosene</h5>
        <h2>TSh <?= number_format($currentPrices['kerosene']) ?></h2>
        <small>Per Liter</small>
        <form method="POST" class="mt-3">
          <input type="hidden" name="fuel_type" value="kerosene">
          <div class="input-group mb-2">
            <input type="number" name="price" class="form-control" step="0.01" placeholder="New price" required>
            <button type="submit" class="btn btn-light">Update</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <div class="card p-4">
    <h5>Price History</h5>
    <table class="table table-striped mt-3">
      <thead>
        <tr>
          <th>Date</th>
          <th>Fuel Type</th>
          <th>Previous Price</th>
          <th>New Price</th>
          <th>Change</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($history)): ?>
        <tr>
          <td colspan="5" class="text-center">No price history found.</td>
        </tr>
        <?php else: ?>
        <?php foreach($history as $h): ?>
        <tr>
          <td><?= esc($h['created_at']) ?></td>
          <td><?= ucfirst($h['fuel_type']) ?></td>
          <td>TSh <?= ($h['previous_price'] ?? null) ? number_format($h['previous_price']) : 'N/A' ?></td>
          <td>TSh <?= number_format($h['price']) ?></td>
          <td>
            <?php 
            $change = ($h['previous_price'] ?? null) ? $h['price'] - $h['previous_price'] : 0;
            $changePercent = ($h['previous_price'] ?? null) ? round(($change / $h['previous_price']) * 100, 2) : 0;
            ?>
            <span class="badge bg-<?= $change >= 0 ? 'success' : 'danger' ?>">
              <?= $change >= 0 ? '+' : '' ?><?= number_format($change, 2) ?> (<?= $changePercent >= 0 ? '+' : '' ?><?= $changePercent ?>%)
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
