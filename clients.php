<?php
require_once __DIR__ . '/config.php';

// Create clients table if it doesn't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS `clients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_code` varchar(20) DEFAULT NULL,
  `company_name` varchar(200) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `type` enum('Corporate','Individual','Government','Transport') DEFAULT 'Individual',
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_code` (`client_code`)
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
        $type = $_POST['type'] ?? 'Individual';
        $status = $_POST['status'] ?? 'Active';
        
        if (empty($company_name)) {
            $message = "Company name is required!";
            $messageType = "danger";
            
            // Log failed attempt
            log_audit($pdo, $userName, AUDIT_ACTION_CREATE, 'Clients', 'Failed', 'Validation error: Company name required');
        } else {
            try {
                if ($action === 'edit') {
                    $id = $_POST['id'] ?? 0;
                    $stmt = $pdo->prepare("UPDATE clients SET company_name = ?, contact_person = ?, phone = ?, email = ?, type = ?, status = ? WHERE id = ?");
                    $stmt->execute([$company_name, $contact_person, $phone, $email, $type, $status, $id]);
                    $message = "Client updated successfully!";
                    
                    // Log update
                    log_audit($pdo, $userName, AUDIT_ACTION_UPDATE, 'Clients', 'Success', 
                        "Updated client ID: $id - Name: $company_name, Type: $type");
                } else {
                    // Generate automatic client code
                    $lastId = $pdo->query("SELECT MAX(id) as max_id FROM clients")->fetch()['max_id'] ?? 0;
                    $client_code = 'CL-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
                    
                    $stmt = $pdo->prepare("INSERT INTO clients (client_code, company_name, contact_person, phone, email, type, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$client_code, $company_name, $contact_person, $phone, $email, $type, $status]);
                    $message = "Client added successfully! (Code: $client_code)";
                    
                    // Log creation
                    log_audit($pdo, $userName, AUDIT_ACTION_CREATE, 'Clients', 'Success', 
                        "Created client - Code: $client_code, Name: $company_name");
                }
                $messageType = "success";
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage();
                $messageType = "danger";
                
                // Log error
                log_audit($pdo, $userName, $action === 'edit' ? AUDIT_ACTION_UPDATE : AUDIT_ACTION_CREATE, 
                    'Clients', 'Failed', 'Error: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        try {
            // Get client info before deleting
            $stmt = $pdo->prepare("SELECT client_code, company_name FROM clients WHERE id = ?");
            $stmt->execute([$id]);
            $clientInfo = $stmt->fetch();
            
            $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Client deleted successfully!";
            $messageType = "success";
            
            // Log deletion
            if ($clientInfo) {
                log_audit($pdo, $userName, AUDIT_ACTION_DELETE, 'Clients', 'Success', 
                    "Deleted client ID: $id - Code: {$clientInfo['client_code']}, Name: {$clientInfo['company_name']}");
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "danger";
            
            // Log error
            log_audit($pdo, $userName, AUDIT_ACTION_DELETE, 'Clients', 'Failed', 'Error: ' . $e->getMessage());
        }
    }
}

// Handle edit mode
if (isset($_GET['edit'])) {
    $editMode = true;
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editData = $stmt->fetch();
}

// Get all clients
$clients = $pdo->query("SELECT * FROM clients ORDER BY id DESC")->fetchAll();

// Statistics
$totalClients = count($clients);
$activeContracts = count(array_filter($clients, fn($c) => $c['status'] === 'Active'));
$newThisMonth = count(array_filter($clients, fn($c) => strtotime($c['created_at']) >= strtotime('first day of this month')));

require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Clients & Partners</h2>
  
  <?php if ($message): ?>
  <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= esc($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  
  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card p-3 text-center" style="background: #28a745; color: #fff;">
        <h6>Total Clients</h6>
        <h3><?= $totalClients ?></h3>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card p-3 text-center" style="background: #007bff; color: #fff;">
        <h6>Active Contracts</h6>
        <h3><?= $activeContracts ?></h3>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card p-3 text-center" style="background: #ffa502; color: #fff;">
        <h6>New This Month</h6>
        <h3><?= $newThisMonth ?></h3>
      </div>
    </div>
  </div>
  
  <div class="card p-4 mb-4">
    <h5><?= $editMode ? 'Edit Client' : 'Add New Client' ?></h5>
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
          <label class="form-label">Type</label>
          <select name="type" class="form-control">
            <option value="Corporate" <?= ($editMode && $editData['type'] === 'Corporate') ? 'selected' : '' ?>>Corporate</option>
            <option value="Individual" <?= ($editMode && $editData['type'] === 'Individual') ? 'selected' : '' ?>>Individual</option>
            <option value="Government" <?= ($editMode && $editData['type'] === 'Government') ? 'selected' : '' ?>>Government</option>
            <option value="Transport" <?= ($editMode && $editData['type'] === 'Transport') ? 'selected' : '' ?>>Transport</option>
          </select>
        </div>
        <div class="col-md-1">
          <label class="form-label">&nbsp;</label>
          <button type="submit" class="btn btn-success w-100"><?= $editMode ? 'Update' : 'Add' ?></button>
        </div>
      </div>
    </form>
    <?php if ($editMode): ?>
    <a href="clients.php" class="btn btn-secondary mt-2">Cancel Edit</a>
    <?php endif; ?>
  </div>
  
  <div class="card p-4">
    <h5>Client Directory</h5>
    <table class="table table-striped mt-3">
      <thead>
        <tr>
          <th>ID</th>
          <th>Company Name</th>
          <th>Contact Person</th>
          <th>Phone</th>
          <th>Email</th>
          <th>Type</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($clients)): ?>
        <tr>
          <td colspan="8" class="text-center">No clients found. Add your first client above.</td>
        </tr>
        <?php else: ?>
        <?php foreach($clients as $c): ?>
        <tr>
          <td>CLT<?= str_pad($c['id'], 4, '0', STR_PAD_LEFT) ?></td>
          <td><?= esc($c['company_name']) ?></td>
          <td><?= esc($c['contact_person']) ?></td>
          <td><?= esc($c['phone']) ?></td>
          <td><?= esc($c['email']) ?></td>
          <td><?= ucfirst($c['type']) ?></td>
          <td>
            <?php if ($c['status'] === 'Active'): ?>
            <span class="badge bg-success">Active</span>
            <?php else: ?>
            <span class="badge bg-secondary">Inactive</span>
            <?php endif; ?>
          </td>
          <td>
            <a href="?edit=<?= $c['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this client?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $c['id'] ?>">
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

<?php require_once __DIR__ . '/inc/footer.php'; ?>
