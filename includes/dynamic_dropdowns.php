<?php
/**
 * Dynamic Dropdown Functions for Master Data
 * Provides standardized AJAX search functionality for all master data entities
 */

// Handle AJAX requests for master data
function handleMasterDataAjax($conn) {
    if (!isset($_GET['action']) || $_GET['action'] != 'search_master') {
        return;
    }
    
    if (!isset($_GET['type']) || !isset($_GET['q'])) {
        header('Content-Type: application/json');
        echo json_encode([]);
        exit();
    }
    
    $type = $_GET['type'];
    $search = $_GET['q'];
    $searchTerm = "%$search%";
    
    switch($type) {
        case 'profession':
            $stmt = $conn->prepare("SELECT profession as id, profession as text FROM master_profession WHERE profession LIKE ? ORDER BY profession LIMIT 20");
            break;
        case 'area':
            $stmt = $conn->prepare("SELECT area as id, area as text FROM master_area WHERE area LIKE ? ORDER BY area LIMIT 20");
            break;
        case 'road':
            $stmt = $conn->prepare("SELECT road as id, road as text FROM master_road WHERE road LIKE ? ORDER BY road LIMIT 20");
            break;
        case 'city':
            $stmt = $conn->prepare("SELECT city as id, city as text FROM master_city WHERE city LIKE ? ORDER BY city LIMIT 20");
            break;
        case 'recovery_person':
            $stmt = $conn->prepare("SELECT user_id as id, username as text FROM users WHERE role='RecoveryOfficer' AND (username LIKE ? OR email LIKE ?) AND is_active=1 ORDER BY username LIMIT 20");
            $stmt->bind_param("ss", $searchTerm, $searchTerm);
            break;
        case 'client':
            $stmt = $conn->prepare("SELECT client_id as id, full_name as text FROM clients WHERE full_name LIKE ? OR cnic LIKE ? OR manual_reference_no LIKE ? ORDER BY full_name LIMIT 20");
            $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
            break;
        case 'product':
            $stmt = $conn->prepare("SELECT product_id as id, product_name as text FROM products WHERE status='Available' AND (product_name LIKE ? OR product_id LIKE ? OR serial_number LIKE ?) ORDER BY product_name LIMIT 20");
            $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
            break;
        case 'lease':
            $stmt = $conn->prepare("SELECT lease_id as id, lease_id as text FROM leases WHERE lease_id LIKE ? ORDER BY lease_id LIMIT 20");
            $stmt->bind_param("s", $searchTerm);
            break;
        default:
            header('Content-Type: application/json');
            echo json_encode([]);
            exit();
    }
    
    // Execute statement if not already bound
    if (!isset($stmt->param_count) || $stmt->param_count == 0) {
        $stmt->bind_param("s", $searchTerm);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = array();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode(['results' => $data]);
    $stmt->close();
    exit();
}

// Function to generate Select2 dropdown for master data
function generateMasterDataDropdown($conn, $type, $selectedValue = null, $placeholder = "Select...") {
    $options = "";
    
    // If there's a selected value, we need to make sure it appears in the dropdown
    if ($selectedValue) {
        switch($type) {
            case 'profession':
                $stmt = $conn->prepare("SELECT profession FROM master_profession WHERE profession = ?");
                break;
            case 'area':
                $stmt = $conn->prepare("SELECT area FROM master_area WHERE area = ?");
                break;
            case 'road':
                $stmt = $conn->prepare("SELECT road FROM master_road WHERE road = ?");
                break;
            case 'city':
                $stmt = $conn->prepare("SELECT city FROM master_city WHERE city = ?");
                break;
            case 'recovery_person':
                $stmt = $conn->prepare("SELECT user_id, username FROM users WHERE user_id = ?");
                break;
            case 'client':
                $stmt = $conn->prepare("SELECT client_id, full_name FROM clients WHERE client_id = ?");
                break;
            case 'product':
                $stmt = $conn->prepare("SELECT product_id, product_name FROM products WHERE product_id = ?");
                break;
            default:
                $stmt = null;
        }
        
        if ($stmt) {
            $stmt->bind_param("s", $selectedValue);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $displayText = isset($row['text']) ? $row['text'] : 
                              (isset($row['username']) ? $row['username'] : 
                              (isset($row['full_name']) ? $row['full_name'] : 
                              (isset($row['product_name']) ? $row['product_name'] : $row[$type])));
                $options .= "<option value='" . htmlspecialchars($selectedValue) . "' selected>" . htmlspecialchars($displayText) . "</option>";
            }
            $stmt->close();
        }
    }
    
    return $options;
}

?>