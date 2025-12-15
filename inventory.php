<?php
require_once __DIR__ . '/config.php';

// Create inventory table if it doesn't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS `inventory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_code` varchar(20) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `quantity` int DEFAULT '0',
  `unit` varchar(20) DEFAULT 'Units',
  `location` varchar(100) DEFAULT NULL,
  `min_stock_level` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_code` (`item_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$message = '';
$messageType = '';
$editMode = false;
$editData = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userName = $_SESSION['username'] ?? 'Guest';
    
    if ($action === 'add' || $action === 'edit') {
        $item_name = trim($_POST['item_name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 0);
        $unit = trim($_POST['unit'] ?? 'Units');
        $location = trim($_POST['location'] ?? '');
        $min_stock_level = intval($_POST['min_stock_level'] ?? 0);
        
        if (empty($item_name)) {
            $message = "Item name is required!";
            $messageType = "danger";
            
            // Log failed attempt
            log_audit($pdo, $userName, AUDIT_ACTION_CREATE, 'Inventory', 'Failed', 'Validation error: Item name required');
        } else {
            try {
                if ($action === 'edit') {
                    $id = $_POST['id'] ?? 0;
                    $stmt = $pdo->prepare("UPDATE inventory SET item_name = ?, category = ?, quantity = ?, unit = ?, location = ?, min_stock_level = ? WHERE id = ?");
                    $stmt->execute([$item_name, $category, $quantity, $unit, $location, $min_stock_level, $id]);
                    $message = "Inventory item updated successfully!";
                    
                    // Log update
                    log_audit($pdo, $userName, AUDIT_ACTION_UPDATE, 'Inventory', 'Success', 
                        "Updated item ID: $id - Name: $item_name, Quantity: $quantity");
                } else {
                    // Generate automatic item code
                    $lastId = $pdo->query("SELECT MAX(id) as max_id FROM inventory")->fetch()['max_id'] ?? 0;
                    $item_code = 'INV-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
                    
                    $stmt = $pdo->prepare("INSERT INTO inventory (item_code, item_name, category, quantity, unit, location, min_stock_level) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$item_code, $item_name, $category, $quantity, $unit, $location, $min_stock_level]);
                    $message = "Inventory item added successfully! (Code: $item_code)";
                    
                    // Log creation
                    log_audit($pdo, $userName, AUDIT_ACTION_CREATE, 'Inventory', 'Success', 
                        "Created item - Code: $item_code, Name: $item_name, Quantity: $quantity");
                }
                $messageType = "success";
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage();
                $messageType = "danger";
                
                // Log error
                log_audit($pdo, $userName, $action === 'edit' ? AUDIT_ACTION_UPDATE : AUDIT_ACTION_CREATE, 
                    'Inventory', 'Failed', 'Error: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        try {
            // Get item info before deleting
            $stmt = $pdo->prepare("SELECT item_code, item_name, quantity FROM inventory WHERE id = ?");
            $stmt->execute([$id]);
            $itemInfo = $stmt->fetch();
            
            $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Inventory item deleted successfully!";
            $messageType = "success";
            
            // Log deletion
            if ($itemInfo) {
                log_audit($pdo, $userName, AUDIT_ACTION_DELETE, 'Inventory', 'Success', 
                    "Deleted item ID: $id - Code: {$itemInfo['item_code']}, Name: {$itemInfo['item_name']}");
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "danger";
            
            // Log error
            log_audit($pdo, $userName, AUDIT_ACTION_DELETE, 'Inventory', 'Failed', 'Error: ' . $e->getMessage());
        }
    }
}

// Handle edit mode
if (isset($_GET['edit'])) {
    $editMode = true;
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editData = $stmt->fetch();
}

// Get all inventory items
$items = $pdo->query("SELECT * FROM inventory ORDER BY id DESC")->fetchAll();

require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Inventory Management</h2>
  
  <?php if ($message): ?>
  <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= esc($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  
  <div class="row mb-4">
    <div class="col-md-12">
      <div class="card p-4 mb-4">
        <h5><?= $editMode ? 'Edit Inventory Item' : 'Add New Inventory Item' ?></h5>
        <form method="POST" class="mt-3">
          <input type="hidden" name="action" value="<?= $editMode ? 'edit' : 'add' ?>">
          <?php if ($editMode): ?>
          <input type="hidden" name="id" value="<?= $editData['id'] ?>">
          <?php endif; ?>
          
          <div class="row">
            <div class="col-md-3">
              <label class="form-label">Item Name *</label>
              <input type="text" name="item_name" class="form-control" value="<?= $editMode ? esc($editData['item_name']) : '' ?>" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Category</label>
              <select name="category" class="form-control">
                <option value="">Select...</option>
                <option value="Equipment" <?= ($editMode && $editData['category'] === 'Equipment') ? 'selected' : '' ?>>Equipment</option>
                <option value="Supplies" <?= ($editMode && $editData['category'] === 'Supplies') ? 'selected' : '' ?>>Supplies</option>
                <option value="Safety" <?= ($editMode && $editData['category'] === 'Safety') ? 'selected' : '' ?>>Safety</option>
                <option value="Maintenance" <?= ($editMode && $editData['category'] === 'Maintenance') ? 'selected' : '' ?>>Maintenance</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Quantity</label>
              <input type="number" name="quantity" class="form-control" value="<?= $editMode ? $editData['quantity'] : '0' ?>" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Unit</label>
              <input type="text" name="unit" class="form-control" value="<?= $editMode ? esc($editData['unit']) : 'Units' ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label">Location</label>
              <input type="text" name="location" class="form-control" value="<?= $editMode ? esc($editData['location'] ?? '') : '' ?>">
            </div>
            <div class="col-md-1">
              <label class="form-label">Min Stock</label>
              <input type="number" name="min_stock_level" class="form-control" value="<?= $editMode ? $editData['min_stock_level'] : '0' ?>">
            </div>
          </div>
          <button type="submit" class="btn btn-success mt-3"><?= $editMode ? 'Update Item' : 'Add Item' ?></button>
          <?php if ($editMode): ?>
          <a href="inventory.php" class="btn btn-secondary mt-3">Cancel Edit</a>
          <?php endif; ?>
        </form>
      </div>
      
      <div class="card p-4">
        <h5>Current Inventory</h5>
        <p class="text-muted">Track equipment, supplies, and spare parts</p>
        
        <table class="table table-striped mt-3">
          <thead>
            <tr>
              <th>Item Code</th>
              <th>Item Name</th>
              <th>Category</th>
              <th>Quantity</th>
              <th>Unit</th>
              <th>Location</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($items)): ?>
            <tr>
              <td colspan="8" class="text-center">No inventory items found. Add your first item above.</td>
            </tr>
            <?php else: ?>
            <?php foreach($items as $item): ?>
            <tr>
              <td><?= esc($item['item_code']) ?></td>
              <td><?= esc($item['item_name']) ?></td>
              <td><?= esc($item['category']) ?></td>
              <td><?= $item['quantity'] ?></td>
              <td><?= esc($item['unit']) ?></td>
              <td><?= esc($item['location'] ?? '') ?></td>
              <td>
                <?php if($item['quantity'] < $item['min_stock_level']): ?>
                  <span class="badge bg-danger">Low Stock</span>
                <?php else: ?>
                  <span class="badge bg-success">In Stock</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="?edit=<?= $item['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $item['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
