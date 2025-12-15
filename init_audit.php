<?php
/**
 * Initialize Audit Logs Table and Add Sample Data
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/audit_helper.php';

try {
    // Create audit_logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `audit_logs` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `user_name` VARCHAR(100) NOT NULL,
          `action` VARCHAR(255) NOT NULL,
          `module` VARCHAR(100) NOT NULL,
          `ip_address` VARCHAR(45) DEFAULT NULL,
          `status` VARCHAR(50) DEFAULT 'Success',
          `details` TEXT DEFAULT NULL,
          `created_at` DATETIME NOT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_created_at` (`created_at`),
          KEY `idx_user_name` (`user_name`),
          KEY `idx_module` (`module`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    echo "✓ Audit logs table created successfully!\n";
    
    // Add sample audit logs
    $sampleLogs = [
        ['Admin', 'Login', 'Authentication', 'Success', 'User logged in successfully'],
        ['Admin', 'View Data', 'Dashboard', 'Success', 'Viewed dashboard'],
        ['Manager', 'Create Record', 'Stations', 'Success', 'Added new station'],
        ['Manager', 'Update Record', 'Sales', 'Success', 'Updated sales record'],
        ['Staff1', 'Export Data', 'Reports', 'Success', 'Exported monthly report'],
        ['Admin', 'Delete Record', 'Staff', 'Success', 'Removed inactive staff member'],
        ['Manager', 'Create Backup', 'Backup', 'Success', 'Created database backup'],
        ['Staff2', 'View Data', 'Inventory', 'Success', 'Checked inventory levels'],
        ['Admin', 'Change Settings', 'Settings', 'Success', 'Updated system settings'],
        ['Manager', 'Update Record', 'Fuel Stock', 'Success', 'Updated fuel stock levels'],
        ['Staff1', 'Create Record', 'Deliveries', 'Success', 'Recorded fuel delivery'],
        ['Admin', 'Login', 'Authentication', 'Failed', 'Invalid password attempt'],
        ['Manager', 'View Data', 'Reports', 'Success', 'Viewed sales report'],
        ['Staff2', 'Update Record', 'Vehicles', 'Success', 'Updated vehicle information'],
        ['Admin', 'Export Data', 'Audit Logs', 'Success', 'Exported audit logs'],
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (user_name, action, module, ip_address, status, details, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $inserted = 0;
    for ($i = 0; $i < 50; $i++) {
        $log = $sampleLogs[array_rand($sampleLogs)];
        $daysAgo = rand(0, 60);
        $hoursAgo = rand(0, 23);
        $minutesAgo = rand(0, 59);
        
        $timestamp = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days -{$hoursAgo} hours -{$minutesAgo} minutes"));
        
        $stmt->execute([
            $log[0],
            $log[1],
            $log[2],
            '192.168.1.' . rand(1, 255),
            $log[3],
            $log[4],
            $timestamp
        ]);
        $inserted++;
    }
    
    echo "✓ Added {$inserted} sample audit log entries!\n";
    echo "\n";
    echo "Setup complete! You can now use the audit.php page.\n";
    echo "Features enabled:\n";
    echo "  - Filter logs by date range and user\n";
    echo "  - Export logs to CSV\n";
    echo "  - Clear old logs (30+ days)\n";
    echo "  - Clear all logs\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
