<?php
require_once __DIR__ . '/config.php';

// Create suppliers table if it doesn't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(20) DEFAULT NULL,
  `company_name` varchar(200) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `fuel_types` varchar(100) DEFAULT NULL,
  `rating` int DEFAULT '5',
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_code` (`supplier_code`)
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
        $company_name = trim($_POST['company_name'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $fuel_types = trim($_POST['fuel_types'] ?? '');
        $rating = intval($_POST['rating'] ?? 5);
        $status = $_POST['status'] ?? 'Active';
        
        if (empty($company_name)) {
            $message = "Company name is required!";
            $messageType = "danger";
            
            // Log failed attempt
            log_audit($pdo, $userName, AUDIT_ACTION_CREATE, 'Suppliers', 'Failed', 'Validation error: Company name required');
        } else {
            try {
                if ($action === 'edit') {
                    $id = $_POST['id'] ?? 0;
                    $stmt = $pdo->prepare("UPDATE suppliers SET company_name = ?, contact_person = ?, phone = ?, email = ?, fuel_types = ?, rating = ?, status = ? WHERE id = ?");
                    $stmt->execute([$company_name, $contact_person, $phone, $email, $fuel_types, $rating, $status, $id]);
                    $message = "Supplier updated successfully!";
                    
                    // Log update
                    log_audit($pdo, $userName, AUDIT_ACTION_UPDATE, 'Suppliers', 'Success', 
                        "Updated supplier ID: $id - Name: $company_name, Rating: $rating");
                } else {
                    // Generate automatic supplier code
                    $lastId = $pdo->query("SELECT MAX(id) as max_id FROM suppliers")->fetch()['max_id'] ?? 0;
                    $supplier_code = 'SUP-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
                    
                    $stmt = $pdo->prepare("INSERT INTO suppliers (supplier_code, company_name, contact_person, phone, email, fuel_types, rating, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$supplier_code, $company_name, $contact_person, $phone, $email, $fuel_types, $rating, $status]);
                    $message = "Supplier added successfully! (Code: $supplier_code)";
                    
                    // Log creation
                    log_audit($pdo, $userName, AUDIT_ACTION_CREATE, 'Suppliers', 'Success', 
                        "Created supplier - Code: $supplier_code, Name: $company_name");
                }
                $messageType = "success";
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage();
                $messageType = "danger";
                
                // Log error
                log_audit($pdo, $userName, $action === 'edit' ? AUDIT_ACTION_UPDATE : AUDIT_ACTION_CREATE, 
                    'Suppliers', 'Failed', 'Error: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        try {
            // Get supplier info before deleting
            $stmt = $pdo->prepare("SELECT supplier_code, company_name FROM suppliers WHERE id = ?");
            $stmt->execute([$id]);
            $supplierInfo = $stmt->fetch();
            
            $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Supplier deleted successfully!";
            $messageType = "success";
            
            // Log deletion
            if ($supplierInfo) {
                log_audit($pdo, $userName, AUDIT_ACTION_DELETE, 'Suppliers', 'Success', 
                    "Deleted supplier ID: $id - Code: {$supplierInfo['supplier_code']}, Name: {$supplierInfo['company_name']}");
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "danger";
            
            // Log error
            log_audit($pdo, $userName, AUDIT_ACTION_DELETE, 'Suppliers', 'Failed', 'Error: ' . $e->getMessage());
        }
    }
}

// Handle edit mode
if (isset($_GET['edit'])) {
    $editMode = true;
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editData = $stmt->fetch();
}

// Get all suppliers
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY id DESC")->fetchAll();

require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Suppliers</h2>
  
  <?php if ($message): ?>
  <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= esc($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  
  <div class="row mb-4">
    <div class="col-md-12">
      <div class="card p-4 mb-4">
        <h5><?= $editMode ? 'Edit Supplier' : 'Add New Supplier' ?></h5>
        <form method="POST" class="mt-3">
          <input type="hidden" name="action" value="<?= $editMode ? 'edit' : 'add' ?>">
          <?php if ($editMode): ?>
          <input type="hidden" name="id" value="<?= $editData['id'] ?>">
          <?php endif; ?>
          
          <div class="row">
            <div class="col-md-3">
              <label class="form-label">Company Name *</label>
              <input type="text" name="company_name" class="form-control" value="<?= $editMode ? esc($editData['company_name']) : '' ?>" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Contact Person</label>
              <input type="text" name="contact_person" class="form-control" value="<?= $editMode ? esc($editData['contact_person']) : '' ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" value="<?= $editMode ? esc($editData['phone']) : '' ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="<?= $editMode ? esc($editData['email']) : '' ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label">Fuel Types</label>
              <input type="text" name="fuel_types" class="form-control" placeholder="Petrol, Diesel" value="<?= $editMode ? esc($editData['fuel_types']) : '' ?>">
            </div>
            <div class="col-md-1">
              <label class="form-label">Rating</label>
              <select name="rating" class="form-control">
                <?php for($r=1; $r<=5; $r++): ?>
                <option value="<?= $r ?>" <?= ($editMode && $editData['rating'] == $r) ? 'selected' : '' ?>><?= $r ?>⭐</option>
                <?php endfor; ?>
              </select>
            </div>
          </div>
          <button type="submit" class="btn btn-success mt-3"><?= $editMode ? 'Update Supplier' : 'Add Supplier' ?></button>
          <?php if ($editMode): ?>
          <a href="suppliers.php" class="btn btn-secondary mt-3">Cancel Edit</a>
          <?php endif; ?>
        </form>
      </div>
      
      <div class="card p-4">
        <h5>Fuel Suppliers</h5>
        <p class="text-muted">Manage fuel suppliers and purchase orders</p>
        
        <table class="table table-striped mt-3">
          <thead>
            <tr>
              <th>Supplier ID</th>
              <th>Company Name</th>
              <th>Contact Person</th>
              <th>Phone</th>
              <th>Email</th>
              <th>Fuel Types</th>
              <th>Rating</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($suppliers)): ?>
            <tr>
              <td colspan="9" class="text-center">No suppliers found. Add your first supplier above.</td>
            </tr>
            <?php else: ?>
            <?php foreach($suppliers as $s): ?>
            <tr>
              <td>SUP<?= str_pad($s['id'], 3, '0', STR_PAD_LEFT) ?></td>
              <td><?= esc($s['company_name']) ?></td>
              <td><?= esc($s['contact_person']) ?></td>
              <td><?= esc($s['phone']) ?></td>
              <td><?= esc($s['email']) ?></td>
              <td><?= esc($s['fuel_types']) ?></td>
              <td>
                <?php for($i=0; $i<$s['rating']; $i++): ?>⭐<?php endfor; ?>
              </td>
              <td>
                <?php if ($s['status'] === 'Active'): ?>
                <span class="badge bg-success">Active</span>
                <?php else: ?>
                <span class="badge bg-secondary">Inactive</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="?edit=<?= $s['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this supplier?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $s['id'] ?>">
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
