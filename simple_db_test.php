<?php
// Simple database test
require_once 'config/db.php';

try {
    $conn = getDBConnection();
    
    // Test a simple query
    $result = $conn->query("SELECT COUNT(*) as count FROM clients");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Database connection successful. Found " . $row['count'] . " clients in the database.";
    } else {
        echo "Query failed: " . $conn->error;
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage();
}
?>