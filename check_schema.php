<?php
include 'config/db.php';

$conn = getDBConnection();
$result = $conn->query('DESCRIBE recovery_persons');
echo "Schema for recovery_persons table:\n";
while ($row = $result->fetch_assoc()) {
    if ($row['Field'] == 'photo_path') {
        print_r($row);
    }
}
$conn->close();
?>