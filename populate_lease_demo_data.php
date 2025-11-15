<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Function to generate lease demo data
function generateLeaseDemoData($conn) {
    // Get outlet ID
    $result = $conn->query("SELECT outlet_id FROM outlets LIMIT 1");
    $outlet = $result->fetch_assoc();
    $outlet_id = $outlet['outlet_id'];
    
    // Get client IDs
    $clients_result = $conn->query("SELECT client_id FROM clients LIMIT 5");
    $client_ids = [];
    while ($row = $clients_result->fetch_assoc()) {
        $client_ids[] = $row['client_id'];
    }
    
    // Get product IDs
    $products_result = $conn->query("SELECT product_id FROM products LIMIT 10");
    $product_ids = [];
    while ($row = $products_result->fetch_assoc()) {
        $product_ids[] = $row['product_id'];
    }
    
    // Get recovery person IDs
    $recovery_result = $conn->query("SELECT recovery_person_id FROM recovery_persons LIMIT 3");
    $recovery_ids = [];
    while ($row = $recovery_result->fetch_assoc()) {
        $recovery_ids[] = $row['recovery_person_id'];
    }
    
    // Generate lease IDs
    $lease_ids = [];
    for ($i = 1; $i <= 15; $i++) {
        $lease_ids[] = sprintf("LSE%03d", $i);
    }
    
    // Sample lease data
    $leases = [
        // Active leases
        [
            'lease_id' => $lease_ids[0],
            'client_id' => $client_ids[0],
            'guarantor_id' => $client_ids[1],
            'recovery_person_id' => $recovery_ids[0],
            'product_id' => $product_ids[0],
            'outlet_id' => $outlet_id,
            'start_date' => date('Y-m-d', strtotime('-3 months')),
            'end_date' => date('Y-m-d', strtotime('+9 months')),
            'total_amount' => 1500.00,
            'installment_amount' => 125.00,
            'number_of_installments' => 12,
            'paid_installments' => 3,
            'outstanding_amount' => 1125.00,
            'status' => 'Active',
            'late_fee' => 0.00,
            'discount' => 0.00
        ],
        [
            'lease_id' => $lease_ids[1],
            'client_id' => $client_ids[1],
            'guarantor_id' => $client_ids[2],
            'recovery_person_id' => $recovery_ids[1],
            'product_id' => $product_ids[1],
            'outlet_id' => $outlet_id,
            'start_date' => date('Y-m-d', strtotime('-2 months')),
            'end_date' => date('Y-m-d', strtotime('+10 months')),
            'total_amount' => 2500.00,
            'installment_amount' => 208.33,
            'number_of_installments' => 12,
            'paid_installments' => 2,
            'outstanding_amount' => 2083.34,
            'status' => 'Active',
            'late_fee' => 0.00,
            'discount' => 0.00
        ],
        [
            'lease_id' => $lease_ids[2],
            'client_id' => $client_ids[2],
            'guarantor_id' => $client_ids[3],
            'recovery_person_id' => $recovery_ids[2],
            'product_id' => $product_ids[2],
            'outlet_id' => $outlet_id,
            'start_date' => date('Y-m-d', strtotime('-1 month')),
            'end_date' => date('Y-m-d', strtotime('+11 months')),
            'total_amount' => 1800.00,
            'installment_amount' => 150.00,
            'number_of_installments' => 12,
            'paid_installments' => 1,
            'outstanding_amount' => 1650.00,
            'status' => 'Active',
            'late_fee' => 0.00,
            'discount' => 0.00
        ],
        // Overdue leases
        [
            'lease_id' => $lease_ids[3],
            'client_id' => $client_ids[3],
            'guarantor_id' => $client_ids[4],
            'recovery_person_id' => $recovery_ids[0],
            'product_id' => $product_ids[3],
            'outlet_id' => $outlet_id,
            'start_date' => date('Y-m-d', strtotime('-4 months')),
            'end_date' => date('Y-m-d', strtotime('+8 months')),
            'total_amount' => 2200.00,
            'installment_amount' => 183.33,
            'number_of_installments' => 12,
            'paid_installments' => 2,
            'outstanding_amount' => 1833.34,
            'status' => 'Overdue',
            'late_fee' => 50.00,
            'discount' => 0.00
        ],
        [
            'lease_id' => $lease_ids[4],
            'client_id' => $client_ids[4],
            'guarantor_id' => $client_ids[0],
            'recovery_person_id' => $recovery_ids[1],
            'product_id' => $product_ids[4],
            'outlet_id' => $outlet_id,
            'start_date' => date('Y-m-d', strtotime('-5 months')),
            'end_date' => date('Y-m-d', strtotime('+7 months')),
            'total_amount' => 3000.00,
            'installment_amount' => 250.00,
            'number_of_installments' => 12,
            'paid_installments' => 3,
            'outstanding_amount' => 2250.00,
            'status' => 'Overdue',
            'late_fee' => 75.00,
            'discount' => 0.00
        ],
        // Closed leases
        [
            'lease_id' => $lease_ids[5],
            'client_id' => $client_ids[0],
            'guarantor_id' => $client_ids[1],
            'recovery_person_id' => $recovery_ids[2],
            'product_id' => $product_ids[5],
            'outlet_id' => $outlet_id,
            'start_date' => date('Y-m-d', strtotime('-12 months')),
            'end_date' => date('Y-m-d', strtotime('now')),
            'total_amount' => 1200.00,
            'installment_amount' => 100.00,
            'number_of_installments' => 12,
            'paid_installments' => 12,
            'outstanding_amount' => 0.00,
            'status' => 'Closed',
            'late_fee' => 0.00,
            'discount' => 0.00
        ],
        [
            'lease_id' => $lease_ids[6],
            'client_id' => $client_ids[1],
            'guarantor_id' => $client_ids[2],
            'recovery_person_id' => $recovery_ids[0],
            'product_id' => $product_ids[6],
            'outlet_id' => $outlet_id,
            'start_date' => date('Y-m-d', strtotime('-15 months')),
            'end_date' => date('Y-m-d', strtotime('-3 months')),
            'total_amount' => 2000.00,
            'installment_amount' => 166.67,
            'number_of_installments' => 12,
            'paid_installments' => 12,
            'outstanding_amount' => 0.00,
            'status' => 'Closed',
            'late_fee' => 0.00,
            'discount' => 0.00
        ],
        // More active leases
        [
            'lease_id' => $lease_ids[7],
            'client_id' => $client_ids[2],
            'guarantor_id' => $client_ids[3],
            'recovery_person_id' => $recovery_ids[1],
            'product_id' => $product_ids[7],
            'outlet_id' => $outlet_id,
            'start_date' => date('Y-m-d', strtotime('-1 week')),
            'end_date' => date('Y-m-d', strtotime('+11 months')),
            'total_amount' => 2800.00,
            'installment_amount' => 233.33,
            'number_of_installments' => 12,
            'paid_installments' => 0,
            'outstanding_amount' => 2800.00,
            'status' => 'Active',
            'late_fee' => 0.00,
            'discount' => 0.00
        ],
        [
            'lease_id' => $lease_ids[8],
            'client_id' => $client_ids[3],
            'guarantor_id' => $client_ids[4],
            'recovery_person_id' => $recovery_ids[2],
            'product_id' => $product_ids[8],
            'outlet_id' => $outlet_id,
            'start_date' => date('Y-m-d', strtotime('-2 weeks')),
            'end_date' => date('Y-m-d', strtotime('+10 months')),
            'total_amount' => 1600.00,
            'installment_amount' => 133.33,
            'number_of_installments' => 12,
            'paid_installments' => 0,
            'outstanding_amount' => 1600.00,
            'status' => 'Active',
            'late_fee' => 0.00,
            'discount' => 0.00
        ],
        // More overdue leases
        [
            'lease_id' => $lease_ids[9],
            'client_id' => $client_ids[4],
            'guarantor_id' => $client_ids[0],
            'recovery_person_id' => $recovery_ids[0],
            'product_id' => $product_ids[9],
            'outlet_id' => $outlet_id,
            'start_date' => date('Y-m-d', strtotime('-6 months')),
            'end_date' => date('Y-m-d', strtotime('+6 months')),
            'total_amount' => 2400.00,
            'installment_amount' => 200.00,
            'number_of_installments' => 12,
            'paid_installments' => 4,
            'outstanding_amount' => 1600.00,
            'status' => 'Overdue',
            'late_fee' => 100.00,
            'discount' => 0.00
        ],
        // Additional leases with different statuses
        [
            'lease_id' => $lease_ids[10],
            'client_id' => $client_ids[0],
            'guarantor_id' => $client_ids[1],
            'recovery_person_id' => $recovery_ids[1],
            'product_id' => $product_ids[0],
            'outlet_id' => $outlet_id,
            'start_date' => date('Y-m-d', strtotime('-8 months')),
            'end_date' => date('Y-m-d', strtotime('+4 months')),
            'total_amount' => 3200.00,
            'installment_amount' => 266.67,
            'number_of_installments' => 12,
            'paid_installments' => 6,
            'outstanding_amount' => 1600.00,
            'status' => 'Active',
            'late_fee' => 0.00,
            'discount' => 50.00
        ],
        [
            'lease_id' => $lease_ids[11],
            'client_id' => $client_ids[1],
            'guarantor_id' => $client_ids[2],
            'recovery_person_id' => $recovery_ids[2],
            'product_id' => $product_ids[1],
            'outlet_id' => $outlet_id,
            'start_date' => date('Y-m-d', strtotime('-9 months')),
            'end_date' => date('Y-m-d', strtotime('+3 months')),
            'total_amount' => 1900.00,
            'installment_amount' => 158.33,
            'number_of_installments' => 12,
            'paid_installments' => 7,
            'outstanding_amount' => 791.67,
            'status' => 'Overdue',
            'late_fee' => 30.00,
            'discount' => 0.00
        ],
        [
            'lease_id' => $lease_ids[12],
            'client_id' => $client_ids[2],
            'guarantor_id' => $client_ids[3],
            'recovery_person_id' => $recovery_ids[0],
            'product_id' => $product_ids[2],
            'outlet_id' => $outlet_id,
            'start_date' => date('Y-m-d', strtotime('-18 months')),
            'end_date' => date('Y-m-d', strtotime('-6 months')),
            'total_amount' => 2600.00,
            'installment_amount' => 216.67,
            'number_of_installments' => 12,
            'paid_installments' => 12,
            'outstanding_amount' => 0.00,
            'status' => 'Closed',
            'late_fee' => 0.00,
            'discount' => 0.00
        ],
        [
            'lease_id' => $lease_ids[13],
            'client_id' => $client_ids[3],
            'guarantor_id' => $client_ids[4],
            'recovery_person_id' => $recovery_ids[1],
            'product_id' => $product_ids[3],
            'outlet_id' => $outlet_id,
            'start_date' => date('Y-m-d', strtotime('-3 weeks')),
            'end_date' => date('Y-m-d', strtotime('+9 months')),
            'total_amount' => 2100.00,
            'installment_amount' => 175.00,
            'number_of_installments' => 12,
            'paid_installments' => 0,
            'outstanding_amount' => 2100.00,
            'status' => 'Active',
            'late_fee' => 0.00,
            'discount' => 0.00
        ],
        [
            'lease_id' => $lease_ids[14],
            'client_id' => $client_ids[4],
            'guarantor_id' => $client_ids[0],
            'recovery_person_id' => $recovery_ids[2],
            'product_id' => $product_ids[4],
            'outlet_id' => $outlet_id,
            'start_date' => date('Y-m-d', strtotime('-7 months')),
            'end_date' => date('Y-m-d', strtotime('+5 months')),
            'total_amount' => 2700.00,
            'installment_amount' => 225.00,
            'number_of_installments' => 12,
            'paid_installments' => 5,
            'outstanding_amount' => 1575.00,
            'status' => 'Overdue',
            'late_fee' => 60.00,
            'discount' => 25.00
        ]
    ];
    
    // Insert leases
    $success_count = 0;
    $error_count = 0;
    
    foreach ($leases as $lease) {
        $stmt = $conn->prepare("INSERT IGNORE INTO leases (lease_id, client_id, guarantor_id, recovery_person_id, product_id, outlet_id, start_date, end_date, total_amount, installment_amount, number_of_installments, paid_installments, outstanding_amount, status, late_fee, discount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssdddiisdd", 
            $lease['lease_id'],
            $lease['client_id'],
            $lease['guarantor_id'],
            $lease['recovery_person_id'],
            $lease['product_id'],
            $lease['outlet_id'],
            $lease['start_date'],
            $lease['end_date'],
            $lease['total_amount'],
            $lease['installment_amount'],
            $lease['number_of_installments'],
            $lease['paid_installments'],
            $lease['outstanding_amount'],
            $lease['status'],
            $lease['late_fee'],
            $lease['discount']
        );
        
        if ($stmt->execute()) {
            $success_count++;
        } else {
            $error_count++;
            echo "Error inserting lease " . $lease['lease_id'] . ": " . $conn->error . "\n";
        }
        $stmt->close();
    }
    
    echo "Lease demo data population completed!\n";
    echo "Successfully inserted: $success_count leases\n";
    echo "Errors: $error_count\n";
}

// Call the function to generate lease demo data
generateLeaseDemoData($conn);

$conn->close();
?>