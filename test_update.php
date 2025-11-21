<?php
include 'config/db.php';

$conn = getDBConnection();

// First, let's insert a test record if it doesn't exist
$recovery_person_id_check = 'RP005';
$check_stmt = $conn->prepare("SELECT id FROM recovery_persons WHERE recovery_person_id = ?");
$check_stmt->bind_param("s", $recovery_person_id_check);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows == 0) {
    // Insert test record
    $photo_path = 'uploads/recovery_persons/rp_RP005_photo.jpg';
    $recovery_person_id = 'RP005';
    $full_name = 'Test Person 5';
    $cnic = '1234567890126';
    $mobile_number = '03001234570';
    $address = 'Test Address 5';
    $city_id = 1;
    $area_id = 1;
    $email = 'test5@example.com';
    $outlet_id = 'OUT001';
    $status = 'Active';

    $stmt = $conn->prepare("INSERT INTO recovery_persons (recovery_person_id, full_name, cnic, mobile_number, address, city_id, area_id, email, outlet_id, photo_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssiissss", $recovery_person_id, $full_name, $cnic, $mobile_number, $address, $city_id, $area_id, $email, $outlet_id, $photo_path, $status);
    $stmt->execute();
    $stmt->close();
}

$check_stmt->close();

// Now test update
$new_photo_path = 'uploads/recovery_persons/rp_RP005_photo_updated.jpg';
$full_name = 'Updated Test Person 5';
$cnic = '1234567890126';
$mobile_number = '03001234570';
$address = 'Updated Test Address 5';
$city_id = 1;
$area_id = 1;
$email = 'test5updated@example.com';
$outlet_id = 'OUT001';
$status = 'Active';
$id = 0;

// Get the ID of the record we want to update
$recovery_person_id_for_update = 'RP005';
$id_stmt = $conn->prepare("SELECT id FROM recovery_persons WHERE recovery_person_id = ?");
$id_stmt->bind_param("s", $recovery_person_id_for_update);
$id_stmt->execute();
$id_result = $id_stmt->get_result();
if ($id_row = $id_result->fetch_assoc()) {
    $id = $id_row['id'];
}
$id_stmt->close();

if ($id > 0) {
    $stmt = $conn->prepare("UPDATE recovery_persons SET full_name=?, cnic=?, mobile_number=?, address=?, city_id=?, area_id=?, email=?, outlet_id=?, photo_path=?, status=? WHERE id=?");
    $stmt->bind_param("ssssiisssii", $full_name, $cnic, $mobile_number, $address, $city_id, $area_id, $email, $outlet_id, $new_photo_path, $status, $id);

    if ($stmt->execute()) {
        echo "Update successful\n";
        
        // Check what was actually updated
        $check_stmt = $conn->prepare("SELECT photo_path FROM recovery_persons WHERE id = ?");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_row = $check_result->fetch_assoc()) {
            echo "Actual photo_path stored in DB: " . ($check_row['photo_path'] ?? 'null') . "\n";
            echo "Type: " . gettype($check_row['photo_path']) . "\n";
        }
        $check_stmt->close();
    } else {
        echo "Update failed: " . $stmt->error . "\n";
    }
    
    $stmt->close();
} else {
    echo "Could not find record to update\n";
}

$conn->close();
?>