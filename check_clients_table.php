<?php
include 'config/db.php';

$conn = getDBConnection();

// Check if clients table exists
$result = $conn->query("SHOW TABLES LIKE 'clients'");
if ($result->num_rows == 0) {
    echo "Clients table does not exist\n";
    $conn->close();
    exit();
}

// Show table structure
echo "Clients table structure:\n";
$result = $conn->query("DESCRIBE clients");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}

$conn->close();
?>