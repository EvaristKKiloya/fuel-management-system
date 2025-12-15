<?php
require_once __DIR__ . '/config.php';

// Create expenses table if it doesn't exist
if ($pdo->query("SHOW TABLES LIKE 'expenses'")->rowCount() == 0) {
    $pdo->exec("CREATE TABLE `expenses` (
      `id` int NOT NULL AUTO_INCREMENT,
      `station_id` int DEFAULT NULL,
      `expense_date` date DEFAULT NULL,
      `category` varchar(50) DEFAULT NULL,
      `description` varchar(255) DEFAULT NULL,
      `amount` decimal(10,2) DEFAULT NULL,
      `status` enum('Paid','Pending','Cancelled') DEFAULT 'Paid',
      `date` date DEFAULT NULL,
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `station_id` (`station_id`),
      CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`station_id`) REFERENCES `stations` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

$message = '';
$messageType = '';
$editMode = false;
$editData = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userName = $_SESSION['username'] ?? 'Guest';
    
    if ($action === 'add' || $action === 'edit') {
        $station_id = intval($_POST['station_id'] ?? 0) ?: null;
        $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $status = $_POST['status'] ?? 'Paid';
        
        if (empty($category) || $amount <= 0) {
            $message = "Category and valid amount are required!";
            $messageType = "danger";
            
            // Log failed attempt
            log_audit($pdo, $userName, AUDIT_ACTION_CREATE, 'Expenses', 'Failed', 'Validation error: Category and valid amount required');
        } else {
            try {
                if ($action === 'edit') {
                    $id = $_POST['id'] ?? 0;
                    $stmt = $pdo->prepare("UPDATE expenses SET station_id = ?, expense_date = ?, category = ?, description = ?, amount = ?, status = ? WHERE id = ?");
                    $stmt->execute([$station_id, $expense_date, $category, $description, $amount, $status, $id]);
                    $message = "Expense updated successfully!";
                    
                    // Log update
                    log_audit($pdo, $userName, AUDIT_ACTION_UPDATE, 'Expenses', 'Success', 
                        "Updated expense ID: $id - Category: $category, Amount: $amount");
                } else {
                    $stmt = $pdo->prepare("INSERT INTO expenses (station_id, expense_date, category, description, amount, status) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$station_id, $expense_date, $category, $description, $amount, $status]);
                    $message = "Expense added successfully!";
                    
                    // Log creation
                    log_audit($pdo, $userName, AUDIT_ACTION_CREATE, 'Expenses', 'Success', 
                        "Created expense - Category: $category, Amount: $amount, Date: $expense_date");
                }
                $messageType = "success";
            } catch (Exception $e) {
                $message = "Error: " . $e->getMessage();
                $messageType = "danger";
                
                // Log error
                log_audit($pdo, $userName, $action === 'edit' ? AUDIT_ACTION_UPDATE : AUDIT_ACTION_CREATE, 
                    'Expenses', 'Failed', 'Error: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        try {
            // Get expense info before deleting
            $stmt = $pdo->prepare("SELECT category, amount, expense_date FROM expenses WHERE id = ?");
            $stmt->execute([$id]);
            $expenseInfo = $stmt->fetch();
            
            $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Expense deleted successfully!";
            $messageType = "success";
            
            // Log deletion
            if ($expenseInfo) {
                log_audit($pdo, $userName, AUDIT_ACTION_DELETE, 'Expenses', 'Success', 
                    "Deleted expense ID: $id - Category: {$expenseInfo['category']}, Amount: {$expenseInfo['amount']}");
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "danger";
            
            // Log error
            log_audit($pdo, $userName, AUDIT_ACTION_DELETE, 'Expenses', 'Failed', 'Error: ' . $e->getMessage());
        }
    }
}

// Handle edit mode
if (isset($_GET['edit'])) {
    $editMode = true;
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editData = $stmt->fetch();
}

// Filter data
$filterCategory = $_GET['category'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterMonth = $_GET['month'] ?? date('Y-m');

$query = "SELECT * FROM expenses WHERE DATE_FORMAT(COALESCE(expense_date, date), '%Y-%m') = ?";
$params = [$filterMonth];

if (!empty($filterCategory)) {
    $query .= " AND category = ?";
    $params[] = $filterCategory;
}
if (!empty($filterStatus)) {
    $query .= " AND status = ?";
    $params[] = $filterStatus;
}

$query .= " ORDER BY COALESCE(expense_date, date) DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$expenses = $stmt->fetchAll();

// Calculate statistics
$allExpensesStmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE DATE_FORMAT(COALESCE(expense_date, date), '%Y-%m') = ?");
$allExpensesStmt->execute([$filterMonth]);
$totalExpenses = $allExpensesStmt->fetch()['total'] ?? 0;

$pendingStmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE status = 'Pending' AND DATE_FORMAT(COALESCE(expense_date, date), '%Y-%m') = ?");
$pendingStmt->execute([$filterMonth]);
$pendingExpenses = $pendingStmt->fetch()['total'] ?? 0;

$avgStmt = $pdo->prepare("SELECT AVG(amount) as average FROM expenses WHERE DATE_FORMAT(COALESCE(expense_date, date), '%Y-%m') = ?");
$avgStmt->execute([$filterMonth]);
$avgExpense = $avgStmt->fetch()['average'] ?? 0;

require_once __DIR__ . '/inc/header.php';
?>
<div class="container-fluid">
  <h2 class="mb-4">Expenses</h2>
  
  <?php if ($message): ?>
  <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= esc($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  
  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card p-3" style="background: linear-gradient(135deg, #FF6B6B 0%, #FF8E8E 100%); color: white; border: none; border-radius: 15px;">
        <h6>Total Expenses This Month</h6>
        <h3>TSh <?= number_format($totalExpenses, 2) ?></h3>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card p-3" style="background: linear-gradient(135deg, #FFA500 0%, #FFB84D 100%); color: white; border: none; border-radius: 15px;">
        <h6>Average Expense</h6>
        <h3>TSh <?= number_format($avgExpense, 2) ?></h3>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card p-3" style="background: linear-gradient(135deg, #4ECDC4 0%, #44A8A0 100%); color: white; border: none; border-radius: 15px;">
        <h6>Pending Payments</h6>
        <h3>TSh <?= number_format($pendingExpenses, 2) ?></h3>
      </div>
    </div>
  </div>
  
  <div class="card p-4 mb-4" style="border: none; border-radius: 15px; background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%); color: white;">
    <h5><?= $editMode ? 'Edit Expense' : 'Add New Expense' ?></h5>
    <form method="POST" class="mt-3">
      <input type="hidden" name="action" value="<?= $editMode ? 'edit' : 'add' ?>">
      <?php if ($editMode): ?>
      <input type="hidden" name="id" value="<?= $editData['id'] ?>">
      <?php endif; ?>
      
      <div class="row">
        <div class="col-md-2">
          <label class="form-label">Date *</label>
          <input type="date" name="expense_date" class="form-control" value="<?= $editMode ? ($editData['expense_date'] ?? $editData['date'] ?? '') : date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Category *</label>
          <select name="category" class="form-control" required>
            <option value="">Select...</option>
            <option value="Maintenance" <?= ($editMode && $editData['category'] === 'Maintenance') ? 'selected' : '' ?>>Maintenance</option>
            <option value="Fuel Purchase" <?= ($editMode && $editData['category'] === 'Fuel Purchase') ? 'selected' : '' ?>>Fuel Purchase</option>
            <option value="Salaries" <?= ($editMode && $editData['category'] === 'Salaries') ? 'selected' : '' ?>>Salaries</option>
            <option value="Utilities" <?= ($editMode && $editData['category'] === 'Utilities') ? 'selected' : '' ?>>Utilities</option>
            <option value="Supplies" <?= ($editMode && $editData['category'] === 'Supplies') ? 'selected' : '' ?>>Supplies</option>
            <option value="Other" <?= ($editMode && $editData['category'] === 'Other') ? 'selected' : '' ?>>Other</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Description</label>
          <input type="text" name="description" class="form-control" value="<?= $editMode ? esc($editData['description'] ?? '') : '' ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label">Amount *</label>
          <input type="number" name="amount" class="form-control" step="0.01" value="<?= $editMode ? $editData['amount'] : '' ?>" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select name="status" class="form-control">
            <option value="Paid" <?= ($editMode && $editData['status'] === 'Paid') ? 'selected' : '' ?>>Paid</option>
            <option value="Pending" <?= ($editMode && $editData['status'] === 'Pending') ? 'selected' : '' ?>>Pending</option>
            <option value="Cancelled" <?= ($editMode && $editData['status'] === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
          </select>
        </div>
        <div class="col-md-1">
          <label class="form-label">&nbsp;</label>
          <button type="submit" class="btn btn-success w-100"><?= $editMode ? 'Update' : 'Add' ?></button>
        </div>
      </div>
    </form>
    <?php if ($editMode): ?>
    <a href="expenses.php" class="btn btn-secondary mt-2">Cancel Edit</a>
    <?php endif; ?>
  </div>
  
  <div class="card p-4">
    <h5>Filter Expenses</h5>
    <form method="GET" class="row g-3 mt-2">
      <div class="col-md-3">
        <label class="form-label">Month</label>
        <input type="month" name="month" class="form-control" value="<?= $filterMonth ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Category</label>
        <select name="category" class="form-control">
          <option value="">All Categories</option>
          <option value="Maintenance" <?= $filterCategory === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
          <option value="Fuel Purchase" <?= $filterCategory === 'Fuel Purchase' ? 'selected' : '' ?>>Fuel Purchase</option>
          <option value="Salaries" <?= $filterCategory === 'Salaries' ? 'selected' : '' ?>>Salaries</option>
          <option value="Utilities" <?= $filterCategory === 'Utilities' ? 'selected' : '' ?>>Utilities</option>
          <option value="Supplies" <?= $filterCategory === 'Supplies' ? 'selected' : '' ?>>Supplies</option>
          <option value="Other" <?= $filterCategory === 'Other' ? 'selected' : '' ?>>Other</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <option value="">All Status</option>
          <option value="paid" <?= $filterStatus === 'paid' ? 'selected' : '' ?>>Paid</option>
          <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
          <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">&nbsp;</label>
        <button type="submit" class="btn btn-primary w-100">Filter</button>
      </div>
    </form>
  </div>
  
  <div class="card p-4 mt-4">
    <h5>Recent Expenses</h5>
    <table class="table table-striped mt-3">
      <thead>
        <tr>
          <th>Date</th>
          <th>Category</th>
          <th>Description</th>
          <th>Amount</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($expenses)): ?>
        <tr>
          <td colspan="6" class="text-center py-4">No expenses found for the selected filters.</td>
        </tr>
        <?php else: ?>
        <?php foreach($expenses as $e): ?>
        <tr>
          <td><?= esc($e['expense_date'] ?? $e['date'] ?? '') ?></td>
          <td><?= esc($e['category']) ?></td>
          <td><?= esc($e['description'] ?? '') ?></td>
          <td>TSh <?= number_format($e['amount'], 2) ?></td>
          <td>
            <?php if ($e['status'] === 'Paid'): ?>
            <span class="badge bg-success">Paid</span>
            <?php elseif ($e['status'] === 'Pending'): ?>
            <span class="badge bg-warning">Pending</span>
            <?php else: ?>
            <span class="badge bg-secondary">Cancelled</span>
            <?php endif; ?>
          </td>
          <td>
            <a href="?edit=<?= $e['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this expense?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $e['id'] ?>">
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
