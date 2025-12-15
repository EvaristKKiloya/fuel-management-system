<?php
/**
 * Setup User Profiles Table
 */

require_once __DIR__ . '/config.php';

try {
    // Create user_profiles table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `user_profiles` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `user_id` INT UNSIGNED NOT NULL UNIQUE,
          `full_name` VARCHAR(255) NOT NULL,
          `email` VARCHAR(255) NOT NULL,
          `phone` VARCHAR(50) DEFAULT NULL,
          `address` VARCHAR(500) DEFAULT NULL,
          `bio` TEXT DEFAULT NULL,
          `job_title` VARCHAR(150) DEFAULT NULL,
          `department` VARCHAR(100) DEFAULT NULL,
          `profile_image` VARCHAR(255) DEFAULT NULL,
          `password_hash` VARCHAR(255) DEFAULT NULL,
          `created_at` DATETIME NOT NULL,
          `updated_at` DATETIME NOT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_user_id` (`user_id`),
          KEY `idx_email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    echo "✓ Created user_profiles table successfully\n";
    
    // Create uploads directory structure
    $uploadDirs = [
        __DIR__ . '/uploads',
        __DIR__ . '/uploads/profiles'
    ];
    
    foreach ($uploadDirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "✓ Created directory: " . basename($dir) . "\n";
        }
    }
    
    // Create .htaccess for uploads directory security
    $htaccess = __DIR__ . '/uploads/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "# Allow access to images\n<FilesMatch \"\\.(jpg|jpeg|png|gif|webp)$\">\n    Require all granted\n</FilesMatch>\n\n# Deny access to PHP files\n<FilesMatch \"\\.php$\">\n    Require all denied\n</FilesMatch>");
        echo "✓ Created .htaccess for uploads security\n";
    }
    
    // Insert sample user profiles
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO user_profiles 
        (user_id, full_name, email, phone, address, bio, job_title, department, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $sampleUsers = [
        [1, 'John Smith', 'john.smith@example.com', '+1-555-0101', '123 Main St, City, State', 'Experienced manager with 10+ years in the industry', 'General Manager', 'Management'],
        [2, 'Sarah Johnson', 'sarah.j@example.com', '+1-555-0102', '456 Oak Ave, City, State', 'Dedicated sales professional focused on customer satisfaction', 'Sales Manager', 'Sales'],
        [3, 'Mike Wilson', 'mike.w@example.com', '+1-555-0103', '789 Pine Rd, City, State', 'Operations specialist with technical expertise', 'Operations Lead', 'Operations'],
        [4, 'Emily Davis', 'emily.d@example.com', '+1-555-0104', '321 Elm St, City, State', 'Finance expert managing company resources', 'Finance Officer', 'Finance'],
        [5, 'David Brown', 'david.b@example.com', '+1-555-0105', '654 Maple Dr, City, State', 'IT specialist maintaining system infrastructure', 'IT Administrator', 'IT'],
    ];
    
    $inserted = 0;
    foreach ($sampleUsers as $user) {
        try {
            $stmt->execute($user);
            if ($stmt->rowCount() > 0) {
                $inserted++;
            }
        } catch (PDOException $e) {
            // Ignore duplicates
        }
    }
    
    echo "✓ Added {$inserted} sample user profiles\n\n";
    echo "Setup complete! The profile.php page is now ready to use.\n";
    echo "\nFeatures available:\n";
    echo "  ✓ Update personal information (name, email, phone, address)\n";
    echo "  ✓ Update job details (title, department)\n";
    echo "  ✓ Add/edit bio\n";
    echo "  ✓ Upload profile image (JPG, PNG, GIF, WebP - max 5MB)\n";
    echo "  ✓ Image preview before upload\n";
    echo "  ✓ Change password\n";
    echo "  ✓ View account information\n";
    echo "  ✓ All actions logged in audit system\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
