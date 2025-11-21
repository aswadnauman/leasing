<?php
require_once 'config/db.php';

try {
    $conn = getDBConnection();
    
    // Test queries for each table
    $tables = [
        'clients' => "SELECT COUNT(*) as count FROM clients WHERE status='Active'",
        'users' => "SELECT COUNT(*) as count FROM users WHERE role='RecoveryOfficer' AND is_active=1",
        'outlets' => "SELECT COUNT(*) as count FROM outlets",
        'products' => "SELECT COUNT(*) as count FROM products WHERE status='Available'"
    ];
    
    $results = [];
    
    foreach ($tables as $table => $query) {
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            $results[$table] = $row['count'];
        } else {
            $results[$table] = "Error: " . $conn->error;
        }
    }
    
    $conn->close();
    
    echo "<h2>Database Connection Test</h2>";
    echo "<p>Connection successful. Table counts:</p>";
    echo "<ul>";
    foreach ($results as $table => $count) {
        echo "<li><strong>$table:</strong> $count records</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "Error connecting to database: " . $e->getMessage();
}
?>