<?php
include 'config/db.php';

$conn = getDBConnection();

echo "Testing CRUD operations for clients...\n\n";

// Test 1: Insert a new client
echo "1. Testing INSERT operation...\n";
$stmt = $conn->prepare("INSERT INTO clients (client_id, full_name, father_husband_name, cnic, mobile_primary, mobile_secondary, address_current, address_permanent, area, road, city, profession, manual_reference_no, status, remarks, outlet_id, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$client_id = 'TEST' . time(); // Unique client ID
$full_name = 'Test Client';
$father_husband_name = 'Test Father';
$cnic = '99999-' . substr(time(), -6) . '-9'; // Unique CNIC
$mobile_primary = '0300-1234567';
$mobile_secondary = '0300-7654321';
$address_current = 'Test Current Address';
$address_permanent = 'Test Permanent Address';
$area = 'Test Area';
$road = 'Test Road';
$city = 'Test City';
$profession = 'Test Profession';
$manual_reference_no = 'TESTREF' . time();
$status = 'Active';
$remarks = 'Test remarks';
$outlet_id = 'OUT001';
$photo_path = null;

$stmt->bind_param("sssssssssssssssss", $client_id, $full_name, $father_husband_name, $cnic, $mobile_primary, $mobile_secondary, $address_current, $address_permanent, $area, $road, $city, $profession, $manual_reference_no, $status, $remarks, $outlet_id, $photo_path);

if ($stmt->execute()) {
    echo "✓ Client inserted successfully with ID: $client_id\n";
    $inserted_id = $stmt->insert_id;
} else {
    echo "✗ Error inserting client: " . $stmt->error . "\n";
    $conn->close();
    exit();
}
$stmt->close();

// Test 2: Select the inserted client
echo "\n2. Testing SELECT operation...\n";
$stmt = $conn->prepare("SELECT * FROM clients WHERE client_id = ?");
$stmt->bind_param("s", $client_id);
$stmt->execute();
$result = $stmt->get_result();

if ($client = $result->fetch_assoc()) {
    echo "✓ Client retrieved successfully\n";
    echo "  Client ID: " . $client['client_id'] . "\n";
    echo "  Full Name: " . $client['full_name'] . "\n";
    echo "  CNIC: " . $client['cnic'] . "\n";
    echo "  Status: " . $client['status'] . "\n";
} else {
    echo "✗ Error retrieving client\n";
    $conn->close();
    exit();
}
$stmt->close();

// Test 3: Update the client
echo "\n3. Testing UPDATE operation...\n";
$stmt = $conn->prepare("UPDATE clients SET full_name = ?, status = ? WHERE client_id = ?");
$new_full_name = 'Updated Test Client';
$new_status = 'Blocked';
$stmt->bind_param("sss", $new_full_name, $new_status, $client_id);

if ($stmt->execute()) {
    echo "✓ Client updated successfully\n";
} else {
    echo "✗ Error updating client: " . $stmt->error . "\n";
    $conn->close();
    exit();
}
$stmt->close();

// Verify update
$stmt = $conn->prepare("SELECT full_name, status FROM clients WHERE client_id = ?");
$stmt->bind_param("s", $client_id);
$stmt->execute();
$result = $stmt->get_result();
if ($updated_client = $result->fetch_assoc()) {
    if ($updated_client['full_name'] == $new_full_name && $updated_client['status'] == $new_status) {
        echo "✓ Update verification successful\n";
    } else {
        echo "✗ Update verification failed\n";
    }
}
$stmt->close();

// Test 4: Delete the client
echo "\n4. Testing DELETE operation...\n";
$stmt = $conn->prepare("DELETE FROM clients WHERE client_id = ?");
$stmt->bind_param("s", $client_id);

if ($stmt->execute()) {
    echo "✓ Client deleted successfully\n";
} else {
    echo "✗ Error deleting client: " . $stmt->error . "\n";
    $conn->close();
    exit();
}
$stmt->close();

echo "\n✓ All CRUD operations completed successfully!\n";

$conn->close();
?>