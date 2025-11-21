<?php
include 'config/db.php';

$conn = getDBConnection();

// Test inserting a client
$stmt = $conn->prepare("INSERT INTO clients (client_id, full_name, father_husband_name, cnic, mobile_primary, mobile_secondary, address_current, address_permanent, area, road, city, profession, manual_reference_no, status, remarks, outlet_id, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$client_id = 'TEST001';
$full_name = 'Test Client';
$father_husband_name = 'Test Father';
$cnic = '1234567890123';
$mobile_primary = '03001234567';
$mobile_secondary = '03001234568';
$address_current = 'Test Current Address';
$address_permanent = 'Test Permanent Address';
$area = 'Test Area';
$road = 'Test Road';
$city = 'Test City';
$profession = 'Test Profession';
$manual_reference_no = 'TESTREF001';
$status = 'Active';
$remarks = 'Test remarks';
$outlet_id = 'OUT001';
$photo_path = null;

$stmt->bind_param("sssssssssssssssss", $client_id, $full_name, $father_husband_name, $cnic, $mobile_primary, $mobile_secondary, $address_current, $address_permanent, $area, $road, $city, $profession, $manual_reference_no, $status, $remarks, $outlet_id, $photo_path);

if ($stmt->execute()) {
    echo "Test client inserted successfully\n";
    
    // Now test updating the client
    $stmt2 = $conn->prepare("UPDATE clients SET full_name=?, status=? WHERE client_id=?");
    $new_full_name = 'Updated Test Client';
    $new_status = 'Blocked';
    $stmt2->bind_param("sss", $new_full_name, $new_status, $client_id);
    
    if ($stmt2->execute()) {
        echo "Test client updated successfully\n";
        
        // Now test deleting the client
        $stmt3 = $conn->prepare("DELETE FROM clients WHERE client_id=?");
        $stmt3->bind_param("s", $client_id);
        
        if ($stmt3->execute()) {
            echo "Test client deleted successfully\n";
        } else {
            echo "Error deleting test client: " . $stmt3->error . "\n";
        }
        $stmt3->close();
    } else {
        echo "Error updating test client: " . $stmt2->error . "\n";
    }
    $stmt2->close();
} else {
    echo "Error inserting test client: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
?>