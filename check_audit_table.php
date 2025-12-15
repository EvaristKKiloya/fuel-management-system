<?php
require_once __DIR__ . '/config.php';

try {
    // Check if table exists and show structure
    $stmt = $pdo->query("DESCRIBE audit_logs");
    echo "Table structure:\n";
    while ($row = $stmt->fetch()) {
        echo "  {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\nTable exists and is ready to use!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
