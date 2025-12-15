<?php
/**
 * Setup Audit Logs Table - Drop and recreate with correct structure
 */

require_once __DIR__ . '/config.php';

try {
    // Drop existing table if it exists
    $pdo->exec("DROP TABLE IF EXISTS `audit_logs`");
    echo "✓ Dropped existing audit_logs table\n";
    
    // Create audit_logs table with correct structure
    $pdo->exec("
        CREATE TABLE `audit_logs` (
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
    
    echo "✓ Created audit_logs table with correct structure\n";
    
    // Insert sample data
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (user_name, action, module, ip_address, status, details, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
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
    
    echo "✓ Added {$inserted} sample audit log entries\n\n";
    echo "Setup complete! The audit.php page is now ready to use.\n";
    echo "\nFeatures available:\n";
    echo "  ✓ View all audit logs\n";
    echo "  ✓ Filter logs by date range\n";
    echo "  ✓ Filter logs by user\n";
    echo "  ✓ Export logs to CSV file\n";
    echo "  ✓ Clear logs older than 30 days\n";
    echo "  ✓ Clear all logs\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
