<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/audit_helper.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';
$success = '';

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
        
        // Log failed login attempt
        log_audit(
            $pdo,
            $username ?: 'Unknown',
            AUDIT_ACTION_LOGIN_FAILED,
            'Authentication',
            'Failed',
            'Empty credentials - IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown')
        );
    } else {
        try {
            // Check admin_users table
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['login_time'] = time();
                
                // Log successful login
                log_audit(
                    $pdo,
                    $username,
                    AUDIT_ACTION_LOGIN,
                    'Authentication',
                    'Success',
                    'User logged in successfully from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown')
                );
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password';
                
                // Log failed login attempt
                log_audit(
                    $pdo,
                    $username,
                    AUDIT_ACTION_LOGIN_FAILED,
                    'Authentication',
                    'Failed',
                    'Invalid credentials - IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown')
                );
            }
        } catch (Exception $e) {
            $error = 'Login error: ' . $e->getMessage();
            
            // Log error
            log_audit(
                $pdo,
                $username,
                AUDIT_ACTION_LOGIN_FAILED,
                'Authentication',
                'Failed',
                'Error: ' . $e->getMessage() . ' - IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown')
            );
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Fuel Management System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .login-card {
      max-width: 450px;
      width: 100%;
    }
  </style>
</head>
<body>

<div class="container">
  <div class="login-card">
    <div class="card shadow-lg border-0">
      <div class="card-body p-5">
        <div class="text-center mb-4">
          <img src="https://cdn-icons-png.flaticon.com/512/3448/3448636.png" alt="Logo" width="80" height="80" class="mb-3">
          <h3 class="fw-bold">Fuel Management System</h3>
          <p class="text-muted">Sign in to your account</p>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <form method="POST" action="">
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input type="text" class="form-control" id="username" name="username" required autofocus>
            </div>
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" class="form-control" id="password" name="password" required>
            </div>
          </div>

          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="remember">
            <label class="form-check-label" for="remember">Remember me</label>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
          </button>
        </form>

        <div class="text-center mt-4">
          <small class="text-muted">
            Default: username: <strong>admin</strong> | password: <strong>admin</strong>
          </small>
        </div>
      </div>
    </div>

    <div class="text-center mt-3 text-white">
      <small>&copy; 2025 Fuel Management System. All rights reserved.</small>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
