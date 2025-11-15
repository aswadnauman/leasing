<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Get export type (Excel or PDF)
$export_type = isset($_GET['type']) ? $_GET['type'] : 'excel';

// Fetch all clients data
$clients_result = $conn->query("SELECT * FROM clients ORDER BY created_at DESC");

if ($export_type == 'excel') {
    // Export to Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="clients_export_' . date('Y-m-d') . '.xls"');
    
    echo "Client ID\tFull Name\tFather/Husband Name\tCNIC\tMobile Primary\tMobile Secondary\tCurrent Address\tPermanent Address\tArea\tRoad\tCity\tProfession\tReference No\tStatus\tRemarks\tOutlet ID\tCreated At\n";
    
    while ($client = $clients_result->fetch_assoc()) {
        echo $client['client_id'] . "\t" . 
             $client['full_name'] . "\t" . 
             $client['father_husband_name'] . "\t" . 
             $client['cnic'] . "\t" . 
             $client['mobile_primary'] . "\t" . 
             $client['mobile_secondary'] . "\t" . 
             $client['address_current'] . "\t" . 
             $client['address_permanent'] . "\t" . 
             $client['area'] . "\t" . 
             $client['road'] . "\t" . 
             $client['city'] . "\t" . 
             $client['profession'] . "\t" . 
             $client['manual_reference_no'] . "\t" . 
             $client['status'] . "\t" . 
             $client['remarks'] . "\t" . 
             $client['outlet_id'] . "\t" . 
             $client['created_at'] . "\n";
    }
} else if ($export_type == 'pdf') {
    // Export to PDF (simplified text version)
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="clients_export_' . date('Y-m-d') . '.pdf"');
    
    echo "Client Export Report\n";
    echo "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    
    echo str_pad("Client ID", 15) . str_pad("Full Name", 25) . str_pad("CNIC", 20) . str_pad("Mobile", 15) . str_pad("Status", 10) . "\n";
    echo str_repeat("-", 85) . "\n";
    
    while ($client = $clients_result->fetch_assoc()) {
        echo str_pad($client['client_id'], 15) . 
             str_pad(substr($client['full_name'], 0, 24), 25) . 
             str_pad($client['cnic'], 20) . 
             str_pad($client['mobile_primary'], 15) . 
             str_pad($client['status'], 10) . "\n";
    }
}

$conn->close();
?>