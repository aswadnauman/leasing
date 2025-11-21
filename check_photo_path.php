<?php
include 'config/db.php';

$conn = getDBConnection();
$result = $conn->query('SELECT recovery_person_id, photo_path FROM recovery_persons WHERE recovery_person_id = "RP001"');
if ($row = $result->fetch_assoc()) {
    echo "Photo path in DB: " . ($row['photo_path'] ?? 'null') . "\n";
    echo "File exists: " . (file_exists($row['photo_path']) ? 'yes' : 'no') . "\n";
    echo "File path: " . realpath($row['photo_path']) . "\n";
} else {
    echo "No record found for RP001\n";
}
$conn->close();
?>