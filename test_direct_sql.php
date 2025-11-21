<?php
include 'config/db.php';

$conn = getDBConnection();

// Test direct SQL insert
$photo_path = 'uploads/recovery_persons/rp_RP001_photo.jpg';
$sql = "INSERT INTO recovery_persons (recovery_person_id, full_name, cnic, mobile_number, address, city_id, area_id, email, outlet_id, photo_path, status) VALUES ('RP003', 'Test Person', '1234567890124', '03001234568', 'Test Address', 1, 1, 'test2@example.com', 'OUT002', '$photo_path', 'Active')";

if ($conn->query($sql)) {
    echo "Direct SQL insert successful\n";
    
    // Check what was actually inserted
    $result = $conn->query("SELECT photo_path FROM recovery_persons WHERE recovery_person_id = 'RP003'");
    if ($row = $result->fetch_assoc()) {
        echo "Actual photo_path stored in DB: " . ($row['photo_path'] ?? 'null') . "\n";
        echo "Type: " . gettype($row['photo_path']) . "\n";
    }
} else {
    echo "Direct SQL insert failed: " . $conn->error . "\n";
}

$conn->close();
?>