<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/audit_helper.php';

// Log page view for authenticated users
if (!empty($_SESSION['username']) && !isset($_GET['no_audit'])) {
    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
    $excludePages = ['logout']; // Don't log logout page view, it's logged separately
    
    if (!in_array($currentPage, $excludePages)) {
        log_audit(
            $pdo,
            $_SESSION['username'],
            AUDIT_ACTION_PAGE_VIEW,
            ucfirst(str_replace('_', ' ', $currentPage)),
            'Success',
            'User accessed ' . $currentPage . ' page'
        );
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Fuel Station Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/assets/style.css" rel="stylesheet">
</head>

<body>

<!-- ======= MODERN NAVBAR ======= -->
<nav class="navbar navbar-expand-lg bg-white shadow-sm py-2">
  <div class="container-fluid">

    <!-- LOGO + BRAND -->
    <a class="navbar-brand d-flex align-items-center fw-bold" href="/dashboard.php">
      <img src="https://cdn-icons-png.flaticon.com/512/3448/3448636.png" alt="Fuel Station Logo" width="38" height="38" class="me-2" onerror="this.src='https://cdn-icons-png.flaticon.com/512/1598/1598196.png'">
      Fuel Management System
    </a>

    <!-- SEARCH BAR -->
    <form class="d-none d-md-flex ms-3 flex-grow-1">
      <div class="input-group">
        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control" placeholder="Search stations, staff, trucks...">
      </div>
    </form>

    <div class="d-flex align-items-center ms-auto">

      <!-- NOTIFICATIONS -->
      <div class="me-3 position-relative">
        <i class="bi bi-bell fs-4"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge bg-danger rounded-pill">
          3
        </span>
      </div>

      <!-- LIVE CLOCK -->
      <div class="me-4 fw-semibold text-primary" id="clock">--:--</div>

      <!-- USER DROPDOWN -->
      <div class="dropdown">
        <a class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" href="#" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle fs-4 me-2"></i>
          <?= esc($_SESSION['username'] ?? 'Guest') ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
          <?php if (!empty($_SESSION['user_id'])): ?>
            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>
            <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear"></i> Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
          <?php else: ?>
            <li><a class="dropdown-item" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a></li>
          <?php endif; ?>
        </ul>
      </div>

    </div>
  </div>
</nav>


<!-- ======= MODERN SIDEBAR ======= -->
<div class="d-flex">
  <aside class="sidebar bg-dark text-white p-3" style="width: var(--sidebar-width);">

    <h6 class="text-uppercase text-white-50 mb-3" style="margin-right: 100px;">Main Menu</h6>
    <ul class="nav flex-column mb-4">
      <li class="nav-item">
        <a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active bg-primary' : '' ?>" href="dashboard.php">
          <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'station_list.php' ? 'active bg-primary' : '' ?>" href="station_list.php">
          <i class="bi bi-fuel-pump-fill me-2"></i> Fuel Stations
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'station_details.php' ? 'active bg-primary' : '' ?>" href="station_details.php">
          <i class="bi bi-info-circle me-2"></i> Station Details
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active bg-primary' : '' ?>" href="reports.php">
          <i class="bi bi-file-earmark-text me-2"></i> Reports
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'alerts.php' ? 'active bg-primary' : '' ?>" href="alerts.php">
          <i class="bi bi-exclamation-triangle me-2"></i> Alerts
        </a>
      </li>

      <h6 class="text-uppercase text-white-50 mt-4 mb-2" style="margin-right: 100px;">Operations</h6>
      <li class="nav-item">
        <a class="nav-link text-white" href="deliveries.php">
          <i class="bi bi-truck-front me-2"></i> Deliveries & Trucks
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-white" href="fuel-stock.php">
          <i class="bi bi-droplet-half me-2"></i> Fuel Stock Levels
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-white" href="sales.php">
          <i class="bi bi-bar-chart-line me-2"></i> Sales Analytics
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-white" href="expenses.php">
          <i class="bi bi-cash-coin me-2"></i> Expenses
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-white" href="fuel-price.php">
          <i class="bi bi-currency-dollar me-2"></i> Fuel Pricing
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-white" href="inventory.php">
          <i class="bi bi-box-seam me-2"></i> Inventory
        </a>
      </li>

      <h6 class="text-uppercase text-white-50 mt-4 mb-2" style="margin-right: 100px;">Management</h6>
      <li class="nav-item">
        <a class="nav-link text-white" href="staff.php">
          <i class="bi bi-people me-2"></i> Staff Management
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-white" href="vehicles.php">
          <i class="bi bi-truck me-2"></i> Fleet Management
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-white" href="clients.php">
          <i class="bi bi-building me-2"></i> Clients & Partners
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-white" href="suppliers.php">
          <i class="bi bi-shop me-2"></i> Suppliers
        </a>
      </li>

      <h6 class="text-uppercase text-white-50 mt-4 mb-2" style="margin-right: 100px;">System</h6>
      <li class="nav-item">
        <a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'security.php' ? 'active bg-primary' : '' ?>" href="security.php">
          <i class="bi bi-shield-lock me-2"></i> Security
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-white" href="settings.php">
          <i class="bi bi-gear-wide-connected me-2"></i> Settings
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-white" href="audit.php">
          <i class="bi bi-clipboard-data me-2"></i> Audit & Logs
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-white" href="backup.php">
          <i class="bi bi-cloud-arrow-up me-2"></i> Backup
        </a>
      </li>
    </ul>

    <!-- Logout button -->
    <div class="mt-auto pt-3">
      <a class="btn btn-outline-light w-100" href="logout.php">
        <i class="bi bi-box-arrow-right me-2"></i> Logout
      </a>
    </div>

  </aside>

  <main class="flex-fill p-4">
