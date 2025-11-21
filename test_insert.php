<?php
include 'config/db.php';

$conn = getDBConnection();

// Test direct insert
$photo_path = 'uploads/recovery_persons/rp_RP001_photo.jpg';
$recovery_person_id = 'RP004'; // Changed to avoid duplicate
$full_name = 'Test Person';
$cnic = '1234567890125'; // Changed to avoid duplicate
$mobile_number = '03001234569';
$address = 'Test Address';
$city_id = 1;
$area_id = 1;
$email = 'test4@example.com';
$outlet_id = 'OUT001';
$status = 'Active';

$stmt = $conn->prepare("INSERT INTO recovery_persons (recovery_person_id, full_name, cnic, mobile_number, address, city_id, area_id, email, outlet_id, photo_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssiissss", $recovery_person_id, $full_name, $cnic, $mobile_number, $address, $city_id, $area_id, $email, $outlet_id, $photo_path, $status);

if ($stmt->execute()) {
    echo "Direct insert successful\n";
    
    // Check what was actually inserted
    $check_stmt = $conn->prepare("SELECT photo_path FROM recovery_persons WHERE recovery_person_id = ?");
    $check_stmt->bind_param("s", $recovery_person_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_row = $check_result->fetch_assoc()) {
        echo "Actual photo_path stored in DB: " . ($check_row['photo_path'] ?? 'null') . "\n";
        echo "Type: " . gettype($check_row['photo_path']) . "\n";
    }
    $check_stmt->close();
} else {
    echo "Direct insert failed: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
?>