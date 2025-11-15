<?php
require_once 'config/db.php';

$conn = getDBConnection();

// Add photo_path column to products table
$sql = "ALTER TABLE products ADD COLUMN photo_path VARCHAR(255)";

if ($conn->query($sql) === TRUE) {
    echo "Column 'photo_path' added to products table successfully\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}

$conn->close();
?>