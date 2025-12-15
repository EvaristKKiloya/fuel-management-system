<?php
/**
 * Audit Helper - Log system activities
 */

if (!function_exists('log_audit')) {
    /**
     * Log an audit entry to the database
     * 
     * @param PDO $pdo Database connection
     * @param string $userName User performing the action
     * @param string $action Action performed
     * @param string $module Module/page where action occurred
     * @param string $status Status of the action (Success/Failed)
     * @param string $details Additional details
     * @return bool Success status
     */
    function log_audit($pdo, $userName, $action, $module, $status = 'Success', $details = '') {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO audit_logs (user_name, action, module, ip_address, status, details, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            
            return $stmt->execute([
                $userName,
                $action,
                $module,
                $ipAddress,
                $status,
                $details
            ]);
        } catch (PDOException $e) {
            error_log("Audit log error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Auto-log audit for POST requests
 */
function auto_log_audit($pdo) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['skip_audit'])) {
        $action = $_POST['action'] ?? 'Unknown';
        $module = basename($_SERVER['PHP_SELF'], '.php');
        $userName = $_SESSION['username'] ?? 'Guest';
        
        $details = json_encode([
            'action' => $action,
            'data' => array_diff_key($_POST, array_flip(['password', 'password_hash', 'skip_audit']))
        ]);
        
        log_audit($pdo, $userName, ucfirst($action), ucfirst($module), 'Success', $details);
    }
}

/**
 * Common audit actions
 */
define('AUDIT_ACTION_LOGIN', 'Login');
define('AUDIT_ACTION_LOGOUT', 'Logout');
define('AUDIT_ACTION_LOGIN_FAILED', 'Login Failed');
define('AUDIT_ACTION_CREATE', 'Create Record');
define('AUDIT_ACTION_UPDATE', 'Update Record');
define('AUDIT_ACTION_DELETE', 'Delete Record');
define('AUDIT_ACTION_EXPORT', 'Export Data');
define('AUDIT_ACTION_IMPORT', 'Import Data');
define('AUDIT_ACTION_VIEW', 'View Data');
define('AUDIT_ACTION_SETTINGS', 'Change Settings');
define('AUDIT_ACTION_BACKUP', 'Create Backup');
define('AUDIT_ACTION_RESTORE', 'Restore Backup');
define('AUDIT_ACTION_PAGE_VIEW', 'Page View');
