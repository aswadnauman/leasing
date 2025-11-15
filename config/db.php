<?php
// Database configuration and utility functions

// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'lease_management');

// Function to get database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Function to update product status based on stock levels
function updateProductStatus($conn, $product_id) {
    // This function would contain logic to update product status
    // For now, we'll keep it simple
    return true;
}

// Function to generate installment payments for a lease
function generateInstallmentPayments($conn, $lease_id, $start_date, $end_date, $number_of_installments, $installment_amount) {
    // Check if lease exists
    $lease_check = $conn->prepare("SELECT id FROM leases WHERE lease_id = ?");
    $lease_check->bind_param("s", $lease_id);
    $lease_check->execute();
    $lease_result = $lease_check->get_result();
    
    if ($lease_result->num_rows == 0) {
        error_log("Lease $lease_id does not exist, cannot generate installment payments");
        $lease_check->close();
        return false;
    }
    $lease_check->close();
    
    // Delete existing installment schedule entries for this lease (if any)
    $delete_stmt = $conn->prepare("DELETE FROM installment_schedule WHERE lease_id = ?");
    $delete_stmt->bind_param("s", $lease_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    // Generate installment schedule
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $intervalDays = ceil(($end->getTimestamp() - $start->getTimestamp()) / (60 * 60 * 24) / $number_of_installments);
    
    $currentDate = clone $start;
    
    for ($i = 1; $i <= $number_of_installments; $i++) {
        $dueDate = clone $currentDate;
        $dueDate->modify("+$intervalDays days");
        
        // Insert installment schedule record
        $insert_stmt = $conn->prepare("INSERT INTO installment_schedule (lease_id, installment_number, due_date, amount, paid_amount, status) VALUES (?, ?, ?, ?, 0, 'Pending')");
        $insert_stmt->bind_param("sisd", $lease_id, $i, $dueDate->format('Y-m-d'), $installment_amount);
        
        if (!$insert_stmt->execute()) {
            error_log("Error inserting installment payment: " . $insert_stmt->error);
        }
        
        $insert_stmt->close();
        $currentDate = $dueDate;
    }
    
    return true;
}

// Function to update installment status
function updateInstallmentStatus($conn, $lease_id, $installment_number, $status, $payment_date = null, $payment_method = null, $transaction_id = null) {
    $stmt = $conn->prepare("UPDATE installment_schedule SET status = ?, payment_date = ?, remarks = ? WHERE lease_id = ? AND installment_number = ?");
    
    // Fix for "Only variables should be passed by reference" notice
    if ($payment_date === null) {
        $payment_date = null;
    }
    
    if ($payment_method === null) {
        $payment_method = null;
    }
    
    if ($transaction_id === null) {
        $transaction_id = null;
    }
    
    $remarks = json_encode(["payment_method" => $payment_method, "transaction_id" => $transaction_id]);
    $stmt->bind_param("ssssi", $status, $payment_date, $remarks, $lease_id, $installment_number);
    
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        $stmt->close();
        return false;
    }
}

// Function to get overdue installments
function getOverdueInstallments($conn) {
    $stmt = $conn->prepare("
        SELECT isch.*, l.client_id, c.full_name as client_name
        FROM installment_schedule isch
        JOIN leases l ON isch.lease_id = l.lease_id
        JOIN clients c ON l.client_id = c.client_id
        WHERE isch.status = 'Pending' AND isch.due_date < CURDATE()
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $overdue = [];
    while ($row = $result->fetch_assoc()) {
        $overdue[] = $row;
    }
    
    $stmt->close();
    return $overdue;
}
?>