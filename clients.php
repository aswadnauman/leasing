<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Function to compress image
function compressImage($source, $destination, $quality) {
    // Check if GD library is available
    if (!extension_loaded('gd')) {
        // Simply copy the file without compression if GD is not available
        return copy($source, $destination);
    }
    
    // Get image info
    $info = getimagesize($source);
    
    if ($info === false) {
        return false;
    }
    
    // Create image resource based on mime type
    switch ($info['mime']) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    // Save compressed image
    $result = imagejpeg($image, $destination, $quality);
    // In PHP 8.0+, imagedestroy is deprecated as objects are automatically destroyed
    // Rely on PHP's automatic garbage collection for GD image resources
    // imagedestroy($image); // Deprecated in PHP 8.0+
    
    return $result;
}

// Handle AJAX search requests for clients
if (isset($_GET['action']) && $_GET['action'] == 'search' && isset($_GET['q'])) {
    $search = $_GET['q'];
    $searchTerm = "%$search%";
    
    $stmt = $conn->prepare("SELECT client_id, full_name, send_sms, send_whatsapp FROM clients WHERE full_name LIKE ? OR cnic LIKE ? OR manual_reference_no LIKE ? ORDER BY full_name LIMIT 20");
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $clients = array();
    while ($row = $result->fetch_assoc()) {
        // Format for Select2
        $clients[] = array(
            'id' => $row['client_id'],
            'text' => $row['full_name']
        );
    }
    
    // Format the response correctly for Select2
    $response = array(
        "results" => $clients
    );
    
    header('Content-Type: application/json');
    echo json_encode($response);
    $stmt->close();
    exit();
}

// Handle AJAX search requests for master data
if (isset($_GET['action']) && $_GET['action'] == 'search_master' && isset($_GET['type']) && isset($_GET['q'])) {
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
        default:
            // Format the response correctly for Select2
            $response = array(
                "results" => array()
            );
            
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
    }
    
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = array();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    // Format the response correctly for Select2
    $response = array(
        "results" => $data
    );
    
    header('Content-Type: application/json');
    echo json_encode($response);
    $stmt->close();
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_client'])) {
        // Add new client
        $client_id = $_POST['client_id'];
        $full_name = $_POST['full_name'];
        $father_husband_name = $_POST['father_husband_name'];
        $cnic = $_POST['cnic'];
        $mobile_primary = $_POST['mobile_primary'];
        $mobile_secondary = $_POST['mobile_secondary'];
        $address_current = $_POST['address_current'];
        $address_permanent = $_POST['address_permanent'];
        $area = isset($_POST['area']) ? $_POST['area'] : '';
        $road = isset($_POST['road']) ? $_POST['road'] : '';
        $city = isset($_POST['city']) ? $_POST['city'] : '';
        $profession = $_POST['profession'];
        $manual_reference_no = $_POST['manual_reference_no'];
        $status = $_POST['status'];
        $remarks = $_POST['remarks'];
        $outlet_id = $_POST['outlet_id'];
        
        // Handle photo upload
        $photo_path = null;
        if (isset($_POST['photo_data']) && !empty($_POST['photo_data'])) {
            $photo_data = $_POST['photo_data'];
            // Remove data URL prefix
            $photo_data = substr($photo_data, strpos($photo_data, ',') + 1);
            $photo_data = base64_decode($photo_data);
            
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/clients/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $filename = 'client_' . $client_id . '_photo.jpg';
            $temp_path = $upload_dir . 'temp_' . $filename;
            $photo_path = $upload_dir . $filename;
            
            // Save temporary photo
            file_put_contents($temp_path, $photo_data);
            
            // Try to compress image to <= 20KB
            $quality = 70; // Start with 70% quality
            $compression_success = false;
            while (filesize($temp_path) > 20480 && $quality > 10) { // 20KB = 20480 bytes
                $compression_success = compressImage($temp_path, $photo_path, $quality);
                if (!$compression_success) break; // Stop if compression fails
                $quality -= 10;
            }
            
            // If compression was successful and file is still too large, use the compressed version
            if ($compression_success && filesize($photo_path) > 20480) {
                // Simply keep the compressed version without resizing
                // This is a simplified approach when GD is not available
            } else if (!$compression_success) {
                // If compression failed, just copy the original file
                copy($temp_path, $photo_path);
            }
            
            // Clean up temp file
            unlink($temp_path);
        }
        
        $stmt = $conn->prepare("INSERT INTO clients (client_id, full_name, father_husband_name, cnic, mobile_primary, mobile_secondary, address_current, address_permanent, area, road, city, profession, manual_reference_no, status, remarks, outlet_id, photo_path, send_sms, send_whatsapp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $send_sms = isset($_POST['send_sms']) ? 1 : 0;
        $send_whatsapp = isset($_POST['send_whatsapp']) ? 1 : 0;
        $stmt->bind_param("sssssssssssssssssii", $client_id, $full_name, $father_husband_name, $cnic, $mobile_primary, $mobile_secondary, $address_current, $address_permanent, $area, $road, $city, $profession, $manual_reference_no, $status, $remarks, $outlet_id, $photo_path, $send_sms, $send_whatsapp);
        
        if ($stmt->execute()) {
            $success = "Client added successfully!";
        } else {
            // Check if it's a duplicate CNIC error
            if ($conn->errno == 1062) {
                $error = "A client with this CNIC already exists!";
            } else {
                $error = "Error adding client: " . $conn->error;
            }
        }
        $stmt->close();
    } elseif (isset($_POST['update_client'])) {
        // Update client
        $id = $_POST['id'];
        $client_id = $_POST['client_id'];
        $full_name = $_POST['full_name'];
        $father_husband_name = $_POST['father_husband_name'];
        $cnic = $_POST['cnic'];
        $mobile_primary = $_POST['mobile_primary'];
        $mobile_secondary = $_POST['mobile_secondary'];
        $address_current = $_POST['address_current'];
        $address_permanent = $_POST['address_permanent'];
        $area = isset($_POST['area']) ? $_POST['area'] : '';
        $road = isset($_POST['road']) ? $_POST['road'] : '';
        $city = isset($_POST['city']) ? $_POST['city'] : '';
        $profession = $_POST['profession'];
        $manual_reference_no = $_POST['manual_reference_no'];
        $status = $_POST['status'];
        $remarks = $_POST['remarks'];
        $outlet_id = $_POST['outlet_id'];
        
        // Handle photo upload
        $photo_path = $_POST['existing_photo_path']; // Keep existing photo by default
        if (isset($_POST['photo_data']) && !empty($_POST['photo_data'])) {
            $photo_data = $_POST['photo_data'];
            // Remove data URL prefix
            $photo_data = substr($photo_data, strpos($photo_data, ',') + 1);
            $photo_data = base64_decode($photo_data);
            
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/clients/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $filename = 'client_' . $client_id . '_photo.jpg';
            $temp_path = $upload_dir . 'temp_' . $filename;
            $photo_path = $upload_dir . $filename;
            
            // Save temporary photo
            file_put_contents($temp_path, $photo_data);
            
            // Try to compress image to <= 20KB
            $quality = 70; // Start with 70% quality
            $compression_success = false;
            while (filesize($temp_path) > 20480 && $quality > 10) { // 20KB = 20480 bytes
                $compression_success = compressImage($temp_path, $photo_path, $quality);
                if (!$compression_success) break; // Stop if compression fails
                $quality -= 10;
            }
            
            // If compression was successful and file is still too large, use the compressed version
            if ($compression_success && filesize($photo_path) > 20480) {
                // Simply keep the compressed version without resizing
                // This is a simplified approach when GD is not available
            } else if (!$compression_success) {
                // If compression failed, just copy the original file
                copy($temp_path, $photo_path);
            }
            
            // Clean up temp file
            unlink($temp_path);
        }
        
        // Check if CNIC is being changed and if it already exists for another client
        $check_stmt = $conn->prepare("SELECT id FROM clients WHERE cnic = ? AND id != ?");
        $check_stmt->bind_param("si", $cnic, $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "A client with this CNIC already exists!";
        } else {
            $stmt = $conn->prepare("UPDATE clients SET client_id=?, full_name=?, father_husband_name=?, cnic=?, mobile_primary=?, mobile_secondary=?, address_current=?, address_permanent=?, area=?, road=?, city=?, profession=?, manual_reference_no=?, status=?, remarks=?, outlet_id=?, photo_path=?, send_sms=?, send_whatsapp=? WHERE id=?");
            $send_sms = isset($_POST['send_sms']) ? 1 : 0;
            $send_whatsapp = isset($_POST['send_whatsapp']) ? 1 : 0;
            $stmt->bind_param("ssssssssssssssssiiii", $client_id, $full_name, $father_husband_name, $cnic, $mobile_primary, $mobile_secondary, $address_current, $address_permanent, $area, $road, $city, $profession, $manual_reference_no, $status, $remarks, $outlet_id, $photo_path, $send_sms, $send_whatsapp, $id);
            
            if ($stmt->execute()) {
                $success = "Client updated successfully!";
            } else {
                $error = "Error updating client: " . $conn->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    } elseif (isset($_POST['delete_client'])) {
        // Delete client
        $id = $_POST['id'];
        
        // Get photo path before deleting
        $stmt = $conn->prepare("SELECT photo_path FROM clients WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $client = $result->fetch_assoc();
        $stmt->close();
        
        // Delete the client
        $stmt = $conn->prepare("DELETE FROM clients WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // Delete photo file if exists
            if ($client && !empty($client['photo_path']) && file_exists($client['photo_path'])) {
                unlink($client['photo_path']);
            }
            $success = "Client deleted successfully!";
        } else {
            $error = "Error deleting client: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch master data for dropdowns
$professions_result = $conn->query("SELECT * FROM master_profession ORDER BY profession");
$areas_result = $conn->query("SELECT * FROM master_area ORDER BY area");
$roads_result = $conn->query("SELECT * FROM master_road ORDER BY road");
$cities_result = $conn->query("SELECT * FROM master_city ORDER BY city");

// Search functionality
$search_params = array();
$search_conditions = array();

// Check if any search parameters are provided
$has_search = false;

// Collect search parameters
if (isset($_GET['search'])) {
    $simple_search = $_GET['search'];
    if (!empty($simple_search)) {
        $has_search = true;
        $search_term = "%$simple_search%";
        $search_conditions[] = "(full_name LIKE ? OR cnic LIKE ? OR manual_reference_no LIKE ? OR father_husband_name LIKE ? OR mobile_primary LIKE ? OR mobile_secondary LIKE ? OR profession LIKE ? OR area LIKE ? OR city LIKE ? OR road LIKE ?)";
        $search_params = array_merge($search_params, array($search_term, $search_term, $search_term, $search_term, $search_term, $search_term, $search_term, $search_term, $search_term, $search_term));
    }
}

// Advanced search parameters
if (isset($_GET['profession']) && !empty($_GET['profession'])) {
    $has_search = true;
    $search_conditions[] = "profession = ?";
    $search_params[] = $_GET['profession'];
}

if (isset($_GET['area']) && !empty($_GET['area'])) {
    $has_search = true;
    $search_conditions[] = "area = ?";
    $search_params[] = $_GET['area'];
}

if (isset($_GET['city']) && !empty($_GET['city'])) {
    $has_search = true;
    $search_conditions[] = "city = ?";
    $search_params[] = $_GET['city'];
}

if (isset($_GET['road']) && !empty($_GET['road'])) {
    $has_search = true;
    $search_conditions[] = "road = ?";
    $search_params[] = $_GET['road'];
}

if (isset($_GET['cnic']) && !empty($_GET['cnic'])) {
    $has_search = true;
    $search_conditions[] = "cnic LIKE ?";
    $search_params[] = "%" . $_GET['cnic'] . "%";
}

if (isset($_GET['mobile']) && !empty($_GET['mobile'])) {
    $has_search = true;
    $search_conditions[] = "(mobile_primary LIKE ? OR mobile_secondary LIKE ?)";
    $search_params = array_merge($search_params, array("%" . $_GET['mobile'] . "%", "%" . $_GET['mobile'] . "%"));
}

if (isset($_GET['father_name']) && !empty($_GET['father_name'])) {
    $has_search = true;
    $search_conditions[] = "father_husband_name LIKE ?";
    $search_params[] = "%" . $_GET['father_name'] . "%";
}

// Build the query
if ($has_search) {
    $sql = "SELECT * FROM clients WHERE " . implode(' AND ', $search_conditions) . " ORDER BY created_at DESC";
    $clients_result = $conn->prepare($sql);
    
    // Bind parameters if any
    if (!empty($search_params)) {
        // Create types string (all strings for now)
        $types = str_repeat('s', count($search_params));
        $clients_result->bind_param($types, ...$search_params);
    }
    
    $clients_result->execute();
    $clients_result = $clients_result->get_result();
} else {
    // Fetch all clients
    $clients_result = $conn->query("SELECT * FROM clients ORDER BY created_at DESC");
}

// Store search values for form population
$search_values = array(
    'search' => isset($_GET['search']) ? $_GET['search'] : '',
    'profession' => isset($_GET['profession']) ? $_GET['profession'] : '',
    'area' => isset($_GET['area']) ? $_GET['area'] : '',
    'city' => isset($_GET['city']) ? $_GET['city'] : '',
    'road' => isset($_GET['road']) ? $_GET['road'] : '',
    'cnic' => isset($_GET['cnic']) ? $_GET['cnic'] : '',
    'mobile' => isset($_GET['mobile']) ? $_GET['mobile'] : '',
    'father_name' => isset($_GET['father_name']) ? $_GET['father_name'] : ''
);

// Fetch outlets for dropdown
$outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients - Lease Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="assets/css/styles.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script src="assets/js/select2_dropdown_initializer.js"></script>
    <script src="assets/js/image_handler.js"></script>
</head>
<body>
    <!-- Permanent Sidebar Navigation -->
    <?php include 'includes/permanent_sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-12">
                    <h2 class="text-primary">Client Management</h2>
                    <p class="text-muted">Manage client information and details</p>
                </div>
            </div>

            <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Client Records</h5>
                            <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addClientModal">
                                <i class="bi bi-plus-circle"></i> Add New Client
                            </button>
                        </div>
                        <div class="card-body">
                            <!-- Advanced Search Form -->
                            <div class="mb-4">
                                <button class="btn btn-outline-primary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#advancedSearch" aria-expanded="false" aria-controls="advancedSearch">
                                    <i class="bi bi-search me-1"></i> Advanced Search
                                </button>
                                
                                <div class="collapse" id="advancedSearch">
                                    <form method="GET" class="bg-light p-3 rounded">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="search" class="form-label">General Search</label>
                                                <input type="text" name="search" class="form-control" placeholder="Search by name, CNIC, reference no, etc." value="<?php echo htmlspecialchars($search_values['search']); ?>">
                                                <div class="form-text">Search in all fields (name, CNIC, reference number, etc.)</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="cnic" class="form-label">CNIC Number</label>
                                                <input type="text" name="cnic" class="form-control" placeholder="Enter CNIC" value="<?php echo htmlspecialchars($search_values['cnic']); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="father_name" class="form-label">Father/Husband Name</label>
                                                <input type="text" name="father_name" class="form-control" placeholder="Enter father/husband name" value="<?php echo htmlspecialchars($search_values['father_name']); ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="mobile" class="form-label">Phone Number</label>
                                                <input type="text" name="mobile" class="form-control" placeholder="Enter phone number" value="<?php echo htmlspecialchars($search_values['mobile']); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label for="profession" class="form-label">Profession</label>
                                                <select name="profession" class="form-control">
                                                    <option value="">All Professions</option>
                                                    <?php 
                                                    // Reset the result set
                                                    $professions_result_copy = $conn->query("SELECT * FROM master_profession ORDER BY profession");
                                                    while ($profession = $professions_result_copy->fetch_assoc()): ?>
                                                        <option value="<?php echo htmlspecialchars($profession['profession']); ?>" <?php echo ($search_values['profession'] == $profession['profession']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($profession['profession']); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="area" class="form-label">Area</label>
                                                <select name="area" class="form-control">
                                                    <option value="">All Areas</option>
                                                    <?php 
                                                    // Reset the result set
                                                    $areas_result_copy = $conn->query("SELECT * FROM master_area ORDER BY area");
                                                    while ($area = $areas_result_copy->fetch_assoc()): ?>
                                                        <option value="<?php echo htmlspecialchars($area['area']); ?>" <?php echo ($search_values['area'] == $area['area']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($area['area']); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="road" class="form-label">Road</label>
                                                <select name="road" class="form-control">
                                                    <option value="">All Roads</option>
                                                    <?php 
                                                    // Reset the result set
                                                    $roads_result_copy = $conn->query("SELECT * FROM master_road ORDER BY road");
                                                    while ($road = $roads_result_copy->fetch_assoc()): ?>
                                                        <option value="<?php echo htmlspecialchars($road['road']); ?>" <?php echo ($search_values['road'] == $road['road']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($road['road']); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="city" class="form-label">City</label>
                                                <select name="city" class="form-control">
                                                    <option value="">All Cities</option>
                                                    <?php 
                                                    // Reset the result set
                                                    $cities_result_copy = $conn->query("SELECT * FROM master_city ORDER BY city");
                                                    while ($city = $cities_result_copy->fetch_assoc()): ?>
                                                        <option value="<?php echo htmlspecialchars($city['city']); ?>" <?php echo ($search_values['city'] == $city['city']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($city['city']); ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary me-2">
                                                <i class="bi bi-search me-1"></i> Search
                                            </button>
                                            <a href="clients.php" class="btn btn-outline-secondary">
                                                <i class="bi bi-x-circle me-1"></i> Clear
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>CNIC</th>
                                            <th>Mobile</th>
                                            <th>Profession</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($client = $clients_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($client['client_id']); ?></td>
                                            <td><?php echo htmlspecialchars($client['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($client['cnic']); ?></td>
                                            <td><?php echo htmlspecialchars($client['mobile_primary']); ?></td>
                                            <td><?php echo htmlspecialchars($client['profession']); ?></td>
                                            <td>
                                                <?php if ($client['status'] == 'Active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Blocked</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary edit-client" 
                                                        data-client='<?php echo htmlspecialchars(json_encode($client), ENT_QUOTES, 'UTF-8'); ?>'>
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-outline-info view-client" 
                                                        data-client='<?php echo htmlspecialchars(json_encode($client), ENT_QUOTES, 'UTF-8'); ?>'>
                                                    <i class="bi bi-eye"></i> View
                                                </button>
                                                <button class="btn btn-sm btn-outline-secondary print-client" 
                                                        data-client='<?php echo htmlspecialchars(json_encode($client), ENT_QUOTES, 'UTF-8'); ?>'>
                                                    <i class="bi bi-printer"></i> Print
                                                </button>
                                                <a href="client_details_page.php?client_id=<?php echo urlencode($client['client_id']); ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-person-badge"></i> Details
                                                </a>
                                                <button class="btn btn-sm btn-outline-success qr-scan-client" 
                                                        data-client-id="<?php echo htmlspecialchars($client['client_id']); ?>"
                                                        data-client-name="<?php echo htmlspecialchars($client['full_name']); ?>">
                                                    <i class="bi bi-qr-code"></i> QR
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger delete-client" 
                                                        data-id="<?php echo $client['id']; ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Client Modal -->
    <div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" id="addClientForm">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="addClientModalLabel">Add New Client</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="add_client" value="1">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="client_id" class="form-label">Client ID *</label>
                                <input type="text" class="form-control" id="client_id" name="client_id" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="manual_reference_no" class="form-label">Manual Reference No</label>
                                <input type="text" class="form-control" id="manual_reference_no" name="manual_reference_no">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="father_husband_name" class="form-label">Father/Husband Name *</label>
                                <input type="text" class="form-control" id="father_husband_name" name="father_husband_name" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cnic" class="form-label">CNIC *</label>
                                <input type="text" class="form-control" id="cnic" name="cnic" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="profession" class="form-label">Profession *</label>
                                <select class="form-control select2-master" id="profession" name="profession" data-type="profession" required>
                                    <option value="">Select Profession</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="mobile_primary" class="form-label">Primary Mobile *</label>
                                <input type="text" class="form-control" id="mobile_primary" name="mobile_primary" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="mobile_secondary" class="form-label">Secondary Mobile</label>
                                <input type="text" class="form-control" id="mobile_secondary" name="mobile_secondary">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address_current" class="form-label">Current Address *</label>
                            <textarea class="form-control" id="address_current" name="address_current" rows="2" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address_permanent" class="form-label">Permanent Address *</label>
                            <textarea class="form-control" id="address_permanent" name="address_permanent" rows="2" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="area" class="form-label">Area</label>
                                <select class="form-control select2-master" id="area" name="area" data-type="area">
                                    <option value="">Select Area</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="road" class="form-label">Road</label>
                                <select class="form-control select2-master" id="road" name="road" data-type="road">
                                    <option value="">Select Road</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="city" class="form-label">City</label>
                                <select class="form-control select2-master" id="city" name="city" data-type="city">
                                    <option value="">Select City</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="outlet_id" class="form-label">Outlet *</label>
                                <select class="form-control" id="outlet_id" name="outlet_id" required>
                                    <option value="">Select Outlet</option>
                                    <?php 
                                    // Get a fresh connection for the outlets dropdown
                                    $fresh_conn = getDBConnection();
                                    // Reset the result set
                                    $outlets_result = $fresh_conn->query("SELECT outlet_id, outlet_name FROM outlets");
                                    while ($outlet = $outlets_result->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($outlet['outlet_id']); ?>">
                                            <?php echo htmlspecialchars($outlet['outlet_name']); ?>
                                        </option>
                                    <?php endwhile; 
                                    $fresh_conn->close();
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="Active">Active</option>
                                    <option value="Blocked">Blocked</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="2"></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="send_sms" name="send_sms" checked>
                                    <label class="form-check-label" for="send_sms">
                                        Send SMS Notifications
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="send_whatsapp" name="send_whatsapp" checked>
                                    <label class="form-check-label" for="send_whatsapp">
                                        Send WhatsApp Notifications
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="photo_data" class="form-label">Client Photo</label>
                            <input type="file" class="form-control" id="photo_upload" accept="image/*">
                            <input type="hidden" id="photo_data" name="photo_data">
                            <div id="photo_preview" class="mt-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Add Client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Client Modal -->
    <div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" id="editClientForm">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="editClientModalLabel">Edit Client</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="update_client" value="1">
                        <input type="hidden" id="edit_id" name="id">
                        <input type="hidden" id="edit_client_id" name="client_id">
                        <input type="hidden" id="existing_photo_path" name="existing_photo_path">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_client_id_display" class="form-label">Client ID</label>
                                <input type="text" class="form-control" id="edit_client_id_display" disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_manual_reference_no" class="form-label">Manual Reference No</label>
                                <input type="text" class="form-control" id="edit_manual_reference_no" name="manual_reference_no">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_father_husband_name" class="form-label">Father/Husband Name *</label>
                                <input type="text" class="form-control" id="edit_father_husband_name" name="father_husband_name" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_cnic" class="form-label">CNIC *</label>
                                <input type="text" class="form-control" id="edit_cnic" name="cnic" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_profession" class="form-label">Profession *</label>
                                <select class="form-control select2-master" id="edit_profession" name="profession" data-type="profession" required>
                                    <option value="">Select Profession</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_mobile_primary" class="form-label">Primary Mobile *</label>
                                <input type="text" class="form-control" id="edit_mobile_primary" name="mobile_primary" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_mobile_secondary" class="form-label">Secondary Mobile</label>
                                <input type="text" class="form-control" id="edit_mobile_secondary" name="mobile_secondary">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_address_current" class="form-label">Current Address *</label>
                            <textarea class="form-control" id="edit_address_current" name="address_current" rows="2" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_address_permanent" class="form-label">Permanent Address *</label>
                            <textarea class="form-control" id="edit_address_permanent" name="address_permanent" rows="2" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="edit_area" class="form-label">Area</label>
                                <select class="form-control select2-master" id="edit_area" name="area" data-type="area">
                                    <option value="">Select Area</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_road" class="form-label">Road</label>
                                <select class="form-control select2-master" id="edit_road" name="road" data-type="road">
                                    <option value="">Select Road</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_city" class="form-label">City</label>
                                <select class="form-control select2-master" id="edit_city" name="city" data-type="city">
                                    <option value="">Select City</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_outlet_id" class="form-label">Outlet *</label>
                                <select class="form-control" id="edit_outlet_id" name="outlet_id" required>
                                    <option value="">Select Outlet</option>
                                    <?php 
                                    // Reset the result set
                                    $outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets");
                                    while ($outlet = $outlets_result->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($outlet['outlet_id']); ?>">
                                            <?php echo htmlspecialchars($outlet['outlet_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_status" class="form-label">Status *</label>
                                <select class="form-control" id="edit_status" name="status" required>
                                    <option value="Active">Active</option>
                                    <option value="Blocked">Blocked</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="edit_remarks" name="remarks" rows="2"></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_send_sms" name="send_sms" checked>
                                    <label class="form-check-label" for="edit_send_sms">
                                        Send SMS Notifications
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_send_whatsapp" name="send_whatsapp" checked>
                                    <label class="form-check-label" for="edit_send_whatsapp">
                                        Send WhatsApp Notifications
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_photo_upload" class="form-label">Client Photo</label>
                            <input type="file" class="form-control" id="edit_photo_upload" accept="image/*">
                            <input type="hidden" id="edit_photo_data" name="photo_data">
                            <div id="edit_photo_preview" class="mt-2"></div>
                            <div id="current_photo" class="mt-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Client Modal -->
    <div class="modal fade" id="viewClientModal" tabindex="-1" aria-labelledby="viewClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="viewClientModalLabel">View Client Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-4">
                            <div class="mb-3">
                                <h6 class="text-muted mb-2">Client Photo</h6>
                                <div id="view_photo"></div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card border-0">
                                <div class="card-body p-0">
                                    <h6 class="text-primary mb-3 border-bottom pb-2">Personal Information</h6>
                                    <div class="row mb-2">
                                        <div class="col-sm-5"><strong>Client ID:</strong></div>
                                        <div class="col-sm-7" id="view_client_id"></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-5"><strong>Full Name:</strong></div>
                                        <div class="col-sm-7" id="view_full_name"></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-5"><strong>Father/Husband Name:</strong></div>
                                        <div class="col-sm-7" id="view_father_husband_name"></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-5"><strong>CNIC:</strong></div>
                                        <div class="col-sm-7" id="view_cnic"></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-5"><strong>Profession:</strong></div>
                                        <div class="col-sm-7" id="view_profession"></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-5"><strong>Status:</strong></div>
                                        <div class="col-sm-7" id="view_status"></div>
                                    </div>
                                    
                                    <h6 class="text-primary mt-4 mb-3 border-bottom pb-2">Contact Information</h6>
                                    <div class="row mb-2">
                                        <div class="col-sm-5"><strong>Primary Mobile:</strong></div>
                                        <div class="col-sm-7" id="view_mobile_primary"></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-5"><strong>Secondary Mobile:</strong></div>
                                        <div class="col-sm-7" id="view_mobile_secondary"></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-5"><strong>Current Address:</strong></div>
                                        <div class="col-sm-7" id="view_address_current"></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-5"><strong>Permanent Address:</strong></div>
                                        <div class="col-sm-7" id="view_address_permanent"></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-5"><strong>Area:</strong></div>
                                        <div class="col-sm-7" id="view_area"></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-5"><strong>Road:</strong></div>
                                        <div class="col-sm-7" id="view_road"></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-5"><strong>City:</strong></div>
                                        <div class="col-sm-7" id="view_city"></div>
                                    </div>
                                    
                                    <h6 class="text-primary mt-4 mb-3 border-bottom pb-2">Additional Information</h6>
                                    <div class="row mb-2">
                                        <div class="col-sm-5"><strong>Manual Reference No:</strong></div>
                                        <div class="col-sm-7" id="view_manual_reference_no"></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-5"><strong>Outlet:</strong></div>
                                        <div class="col-sm-7" id="view_outlet_id"></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-5"><strong>Remarks:</strong></div>
                                        <div class="col-sm-7" id="view_remarks"></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-5"><strong>SMS Notifications:</strong></div>
                                        <div class="col-sm-7" id="view_send_sms"></div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-sm-5"><strong>WhatsApp Notifications:</strong></div>
                                        <div class="col-sm-7" id="view_send_whatsapp"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteClientModal" tabindex="-1" aria-labelledby="deleteClientModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteClientModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this client? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="delete_client" value="1">
                        <input type="hidden" id="delete_client_id" name="id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Client</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script>
        // Debug flag - set to true to see console logs
        const DEBUG_ACTIONS = true;
        
        // Initialize file upload handlers for client photos
        $(document).ready(function() {
            console.log('[DEBUG] Document ready - initializing action handlers');
            
            if (typeof handleFileUpload === 'function') {
                console.log('[DEBUG] Image handler loaded successfully');
                handleFileUpload('photo_upload', 'photo_preview', 'photo_data');
                handleFileUpload('edit_photo_upload', 'edit_photo_preview', 'edit_photo_data');
            } else {
                console.warn('[WARNING] Image handler not loaded');
            }
            
            // Setup modal dropdown lifecycle for Add Client modal
            if (typeof Select2DropdownInitializer !== 'undefined') {
                console.log('[DEBUG] Select2DropdownInitializer loaded');
                $('#addClientModal').on('show.bs.modal', function() {
                    var $modal = $(this);
                    if (!$modal.data('dropdowns-initialized')) {
                        Select2DropdownInitializer.initializeOutletDropdowns($modal);
                        $modal.data('dropdowns-initialized', true);
                    }
                });
                
                // Also initialize dropdowns for Edit Client modal when shown
                $('#editClientModal').on('show.bs.modal', function() {
                    var $modal = $(this);
                    if (!$modal.data('dropdowns-initialized')) {
                        Select2DropdownInitializer.initializeOutletDropdowns($modal);
                        $modal.data('dropdowns-initialized', true);
                    }
                });
            } else {
                console.warn('[WARNING] Select2DropdownInitializer not loaded');
            }
        });
        
        // Ensure all event listeners are attached after DOM is fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[DEBUG] DOM fully loaded - attaching event listeners for action buttons');
            
            // Handle edit client button click
            const editButtons = document.querySelectorAll('.edit-client');
            console.log(`[DEBUG] Found ${editButtons.length} edit buttons`);
            
            editButtons.forEach((button, index) => {
                button.addEventListener('click', function() {
                    if (DEBUG_ACTIONS) console.log(`[DEBUG] Edit button ${index} clicked`);
                    const clientJson = this.getAttribute('data-client');
                    try {
                        if (!clientJson) {
                            throw new Error('No data-client attribute found on edit button');
                        }
                        const client = JSON.parse(clientJson);
                        const modal = document.getElementById('editClientModal');
                        const $modal = $(modal);
                        
                        // Populate form fields
                        document.getElementById('edit_id').value = client.id || '';
                        document.getElementById('edit_client_id').value = client.client_id || '';
                        document.getElementById('edit_client_id_display').value = client.client_id || '';
                        document.getElementById('edit_full_name').value = client.full_name || '';
                        document.getElementById('edit_father_husband_name').value = client.father_husband_name || '';
                        document.getElementById('edit_cnic').value = client.cnic || '';
                        document.getElementById('edit_mobile_primary').value = client.mobile_primary || '';
                        document.getElementById('edit_mobile_secondary').value = client.mobile_secondary || '';
                        document.getElementById('edit_address_current').value = client.address_current || '';
                        document.getElementById('edit_address_permanent').value = client.address_permanent || '';
                        document.getElementById('edit_manual_reference_no').value = client.manual_reference_no || '';
                        document.getElementById('edit_remarks').value = client.remarks || '';
                        document.getElementById('existing_photo_path').value = client.photo_path || '';
                        document.getElementById('edit_outlet_id').value = client.outlet_id || '';
                        document.getElementById('edit_status').value = client.status || '';
                        
                        // Populate messaging preferences
                        document.getElementById('edit_send_sms').checked = client.send_sms == 1;
                        document.getElementById('edit_send_whatsapp').checked = client.send_whatsapp == 1;
                        
                        // Handle dropdowns using the initializer helper
                        if (typeof Select2DropdownInitializer !== 'undefined') {
                            const dropdownIds = ['edit_profession', 'edit_area', 'edit_road', 'edit_city'];
                            
                            // Destroy existing instances
                            dropdownIds.forEach(id => {
                                const $dropdown = $('#' + id);
                                if ($dropdown.hasClass('select2-hidden-accessible')) {
                                    $dropdown.select2('destroy');
                                }
                            });
                            
                            // Clear and set selected values
                            const dropdownValues = {
                                'edit_profession': client.profession,
                                'edit_area': client.area,
                                'edit_road': client.road,
                                'edit_city': client.city
                            };
                            
                            Object.keys(dropdownValues).forEach(id => {
                                const value = dropdownValues[id];
                                const $dropdown = $('#' + id);
                                $dropdown.empty(); // Clear existing options
                                if (value && value.trim()) {
                                    $dropdown.append(new Option(value, value, true, true));
                                } else {
                                    $dropdown.append(new Option('Select...', '', false, false));
                                }
                            });
                            
                            // Reinitialize all dropdowns with proper configuration
                            dropdownIds.forEach(id => {
                                const entityType = $('#' + id).data('type') || 'outlet';
                                $('#' + id).select2({
                                    placeholder: "Click to select or search...",
                                    allowClear: true,
                                    dropdownParent: $modal,
                                    ajax: {
                                        url: window.location.href.split('?')[0],
                                        dataType: 'json',
                                        delay: 250,
                                        data: function (params) {
                                            return {
                                                action: 'search_master',
                                                type: entityType,
                                                q: params.term || ''
                                            };
                                        },
                                        processResults: function (data) {
                                            if (!data) return { results: [] };
                                            var results = data.results || data;
                                            if (!Array.isArray(results)) results = [];
                                            return { results: results };
                                        },
                                        cache: true
                                    },
                                    minimumInputLength: 0
                                });
                            });
                        }
                        
                        // Show current photo if exists
                        if (client.photo_path) {
                            document.getElementById('current_photo').innerHTML = 
                                '<p>Current Photo:</p><img src="' + escapeHtml(client.photo_path) + '" class="img-thumbnail" style="max-width: 150px;">';
                        } else {
                            document.getElementById('current_photo').innerHTML = '<p>No current photo</p>';
                        }
                        
                        // Show the modal
                        var editModal = new bootstrap.Modal(modal);
                        editModal.show();
                    } catch (e) {
                        console.error('[ERROR] Edit handler error:', e);
                        alert('Error loading client data for editing: ' + e.message);
                    }
                });
            });
            
            // Cleanup Edit modal when closing - destroy Select2 instances
            $('#editClientModal').on('hide.bs.modal', function() {
                if (typeof Select2DropdownInitializer !== 'undefined') {
                    Select2DropdownInitializer.destroyAllDropdowns($(this));
                }
            });
            
            // Handle view client button click
            const viewButtons = document.querySelectorAll('.view-client');
            console.log(`[DEBUG] Found ${viewButtons.length} view buttons`);
            
            viewButtons.forEach((button, index) => {
                button.addEventListener('click', function() {
                    if (DEBUG_ACTIONS) console.log(`[DEBUG] View button ${index} clicked`);
                    const clientJson = this.getAttribute('data-client');
                    try {
                        if (!clientJson) {
                            throw new Error('No data-client attribute found on view button');
                        }
                        const client = JSON.parse(clientJson);
                        if (DEBUG_ACTIONS) console.log('[DEBUG] Client data parsed for view:', client);
                        
                        // Populate view fields
                        document.getElementById('view_client_id').textContent = client.client_id;
                        document.getElementById('view_full_name').textContent = client.full_name;
                        document.getElementById('view_father_husband_name').textContent = client.father_husband_name;
                        document.getElementById('view_cnic').textContent = client.cnic;
                        document.getElementById('view_mobile_primary').textContent = client.mobile_primary;
                        document.getElementById('view_mobile_secondary').textContent = client.mobile_secondary || 'N/A';
                        document.getElementById('view_address_current').textContent = client.address_current;
                        document.getElementById('view_address_permanent').textContent = client.address_permanent;
                        document.getElementById('view_area').textContent = client.area || 'N/A';
                        document.getElementById('view_road').textContent = client.road || 'N/A';
                        document.getElementById('view_city').textContent = client.city || 'N/A';
                        document.getElementById('view_profession').textContent = client.profession;
                        document.getElementById('view_manual_reference_no').textContent = client.manual_reference_no || 'N/A';
                        document.getElementById('view_outlet_id').textContent = client.outlet_id;
                        document.getElementById('view_remarks').textContent = client.remarks || 'N/A';
                        
                        // Status badge
                        if (client.status == 'Active') {
                            document.getElementById('view_status').innerHTML = '<span class="badge bg-success">Active</span>';
                        } else {
                            document.getElementById('view_status').innerHTML = '<span class="badge bg-danger">Blocked</span>';
                        }
                        
                        // Messaging preferences
                        document.getElementById('view_send_sms').textContent = client.send_sms == 1 ? 'Yes' : 'No';
                        document.getElementById('view_send_whatsapp').textContent = client.send_whatsapp == 1 ? 'Yes' : 'No';
                        
                        // Show photo if exists with better styling
                        if (client.photo_path && client.photo_path.trim() !== '') {
                            document.getElementById('view_photo').innerHTML = 
                                '<img src="' + client.photo_path + '" class="img-fluid rounded" alt="Client Photo" style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; padding: 5px;" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'block\';">' +
                                '<div class="text-muted mt-2" style="display:none;">Photo not available</div>';
                        } else {
                            document.getElementById('view_photo').innerHTML = 
                                '<div class="bg-light p-5 rounded text-center" style="max-width: 200px; height: 200px; display: flex; align-items: center; justify-content: center;">' +
                                '    <span class="text-muted">No photo available</span>' +
                                '</div>';
                        }
                        
                        // Show the modal
                        var viewModal = new bootstrap.Modal(document.getElementById('viewClientModal'));
                        viewModal.show();
                        if (DEBUG_ACTIONS) console.log('[DEBUG] View modal displayed');
                    } catch (e) {
                        console.error('[ERROR] View handler error:', e);
                        alert('Error loading client data for viewing: ' + e.message);
                    }
                });
            });
            
            // Handle print client button click
            const printButtons = document.querySelectorAll('.print-client');
            console.log(`[DEBUG] Found ${printButtons.length} print buttons`);
            
            printButtons.forEach((button, index) => {
                button.addEventListener('click', function() {
                    if (DEBUG_ACTIONS) console.log(`[DEBUG] Print button ${index} clicked`);
                    const clientJson = this.getAttribute('data-client');
                    try {
                        if (!clientJson) {
                            throw new Error('No data-client attribute found on print button');
                        }
                        const client = JSON.parse(clientJson);
                        if (DEBUG_ACTIONS) console.log('[DEBUG] Client data parsed for print:', client);

                        
                        // Create a printable window
                        var printWindow = window.open('', '_blank', 'width=800,height=600');
                        printWindow.document.write(`
                            <!DOCTYPE html>
                            <html>
                                <head>
                                    <title>Client Details - ${client.full_name}</title>
                                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                                    <style>
                                        body { 
                                            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                                            margin: 0; 
                                            padding: 20px; 
                                            background-color: #f8f9fa;
                                        }
                                        .print-container {
                                            max-width: 800px;
                                            margin: 0 auto;
                                            background: white;
                                            padding: 30px;
                                            border-radius: 8px;
                                            box-shadow: 0 0 20px rgba(0,0,0,0.1);
                                        }
                                        .header {
                                            text-align: center;
                                            margin-bottom: 30px;
                                            padding-bottom: 20px;
                                            border-bottom: 2px solid #007bff;
                                        }
                                        .header h1 {
                                            color: #007bff;
                                            margin-bottom: 5px;
                                        }
                                        .header h2 {
                                            color: #333;
                                            margin-top: 5px;
                                            font-size: 1.5em;
                                        }
                                        .client-details {
                                            display: grid;
                                            grid-template-columns: 1fr 1fr;
                                            gap: 20px;
                                            margin-bottom: 30px;
                                        }
                                        .detail-group {
                                            margin-bottom: 15px;
                                        }
                                        .detail-label {
                                            font-weight: bold;
                                            color: #007bff;
                                            display: block;
                                            margin-bottom: 5px;
                                        }
                                        .detail-value {
                                            padding: 8px 12px;
                                            background-color: #f1f3f5;
                                            border-radius: 4px;
                                            border-left: 3px solid #007bff;
                                        }
                                        .photo-section {
                                            text-align: center;
                                            margin: 25px 0;
                                            padding: 20px;
                                            background-color: #f8f9fa;
                                            border-radius: 8px;
                                        }
                                        .photo-placeholder {
                                            width: 200px;
                                            height: 200px;
                                            margin: 0 auto;
                                            background-color: #e9ecef;
                                            border-radius: 8px;
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            color: #6c757d;
                                            font-style: italic;
                                        }
                                        .client-photo {
                                            max-width: 200px;
                                            max-height: 200px;
                                            border-radius: 8px;
                                            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                                        }
                                        .section-title {
                                            color: #007bff;
                                            border-bottom: 1px solid #dee2e6;
                                            padding-bottom: 10px;
                                            margin: 25px 0 15px 0;
                                        }
                                        .full-width {
                                            grid-column: 1 / -1;
                                        }
                                        .footer {
                                            text-align: center;
                                            margin-top: 30px;
                                            padding-top: 20px;
                                            border-top: 1px solid #dee2e6;
                                            color: #6c757d;
                                            font-size: 0.9em;
                                        }
                                        @media print {
                                            body {
                                                background-color: white;
                                            }
                                            .print-container {
                                                box-shadow: none;
                                                padding: 0;
                                            }
                                        }
                                    </style>
                                </head>
                                <body>
                                    <div class="print-container">
                                        <div class="header">
                                            <h1>Client Details</h1>
                                            <h2>${client.full_name}</h2>
                                            <p>Generated on: ${new Date().toLocaleDateString()}</p>
                                        </div>
                                        
                                        <div class="photo-section">
                                            ${client.photo_path ? 
                                                `<img src="${client.photo_path}" alt="Client Photo" class="client-photo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                 <div class="photo-placeholder" style="display:none;">Photo not available</div>` : 
                                                `<div class="photo-placeholder">No photo available</div>`}
                                        </div>
                                        
                                        <h3 class="section-title">Personal Information</h3>
                                        <div class="client-details">
                                            <div class="detail-group">
                                                <span class="detail-label">Client ID:</span>
                                                <div class="detail-value">${client.client_id}</div>
                                            </div>
                                            <div class="detail-group">
                                                <span class="detail-label">Full Name:</span>
                                                <div class="detail-value">${client.full_name}</div>
                                            </div>
                                            <div class="detail-group">
                                                <span class="detail-label">Father/Husband Name:</span>
                                                <div class="detail-value">${client.father_husband_name}</div>
                                            </div>
                                            <div class="detail-group">
                                                <span class="detail-label">CNIC:</span>
                                                <div class="detail-value">${client.cnic}</div>
                                            </div>
                                            <div class="detail-group">
                                                <span class="detail-label">Profession:</span>
                                                <div class="detail-value">${client.profession}</div>
                                            </div>
                                            <div class="detail-group">
                                                <span class="detail-label">Status:</span>
                                                <div class="detail-value">${client.status}</div>
                                            </div>
                                        </div>
                                        
                                        <h3 class="section-title">Contact Information</h3>
                                        <div class="client-details">
                                            <div class="detail-group">
                                                <span class="detail-label">Primary Mobile:</span>
                                                <div class="detail-value">${client.mobile_primary}</div>
                                            </div>
                                            <div class="detail-group">
                                                <span class="detail-label">Secondary Mobile:</span>
                                                <div class="detail-value">${client.mobile_secondary || 'N/A'}</div>
                                            </div>
                                            <div class="detail-group full-width">
                                                <span class="detail-label">Current Address:</span>
                                                <div class="detail-value">${client.address_current}</div>
                                            </div>
                                            <div class="detail-group full-width">
                                                <span class="detail-label">Permanent Address:</span>
                                                <div class="detail-value">${client.address_permanent}</div>
                                            </div>
                                            <div class="detail-group">
                                                <span class="detail-label">Area:</span>
                                                <div class="detail-value">${client.area || 'N/A'}</div>
                                            </div>
                                            <div class="detail-group">
                                                <span class="detail-label">Road:</span>
                                                <div class="detail-value">${client.road || 'N/A'}</div>
                                            </div>
                                            <div class="detail-group">
                                                <span class="detail-label">City:</span>
                                                <div class="detail-value">${client.city || 'N/A'}</div>
                                            </div>
                                        </div>
                                        
                                        <h3 class="section-title">Additional Information</h3>
                                        <div class="client-details">
                                            <div class="detail-group">
                                                <span class="detail-label">Manual Reference No:</span>
                                                <div class="detail-value">${client.manual_reference_no || 'N/A'}</div>
                                            </div>
                                            <div class="detail-group">
                                                <span class="detail-label">Outlet:</span>
                                                <div class="detail-value">${client.outlet_id}</div>
                                            </div>
                                            <div class="detail-group full-width">
                                                <span class="detail-label">Remarks:</span>
                                                <div class="detail-value">${client.remarks || 'N/A'}</div>
                                            </div>
                                            <div class="detail-group">
                                                <span class="detail-label">SMS Notifications:</span>
                                                <div class="detail-value">${client.send_sms == 1 ? 'Yes' : 'No'}</div>
                                            </div>
                                            <div class="detail-group">
                                                <span class="detail-label">WhatsApp Notifications:</span>
                                                <div class="detail-value">${client.send_whatsapp == 1 ? 'Yes' : 'No'}</div>
                                            </div>
                                        </div>
                                        
                                        <div class="footer">
                                            <p>Document generated by Lease Management System</p>
                                        </div>
                                    </div>
                                    
                                    <script>
                                        window.onload = function() {
                                            window.print();
                                        };
                                    <\/script>
                                </body>
                            </html>
                        `);
                        printWindow.document.close();
                        if (DEBUG_ACTIONS) console.log('[DEBUG] Print window created and ready');
                    } catch (e) {
                        console.error('[ERROR] Print handler error:', e);
                        alert('Error loading client data for printing: ' + e.message);
                    }
                });
            });
            
            // Handle delete client button click
            const deleteButtons = document.querySelectorAll('.delete-client');
            console.log(`[DEBUG] Found ${deleteButtons.length} delete buttons`);
            
            deleteButtons.forEach((button, index) => {
                button.addEventListener('click', function() {
                    if (DEBUG_ACTIONS) console.log(`[DEBUG] Delete button ${index} clicked`);
                    try {
                        // Get the client ID from data-id attribute
                        const clientId = this.getAttribute('data-id');
                        if (!clientId) {
                            throw new Error('No data-id attribute found on delete button');
                        }
                        if (DEBUG_ACTIONS) console.log(`[DEBUG] Delete client ID: ${clientId}`);
                        
                        document.getElementById('delete_client_id').value = clientId;
                        
                        // Show the modal
                        var deleteModal = new bootstrap.Modal(document.getElementById('deleteClientModal'));
                        deleteModal.show();
                        if (DEBUG_ACTIONS) console.log('[DEBUG] Delete confirmation modal displayed');
                    } catch (e) {
                        console.error('[ERROR] Delete handler error:', e);
                        alert('Error preparing delete action: ' + e.message);
                    }
                });
            });
            
            // Handle QR code generation
            const qrButtons = document.querySelectorAll('.qr-scan-client');
            console.log(`[DEBUG] Found ${qrButtons.length} QR buttons`);
            
            qrButtons.forEach((button, index) => {
                button.addEventListener('click', function() {
                    if (DEBUG_ACTIONS) console.log(`[DEBUG] QR button ${index} clicked`);
                    try {
                        const clientId = this.getAttribute('data-client-id');
                        const clientName = this.getAttribute('data-client-name');
                        
                        if (!clientId || !clientName) {
                            throw new Error('Missing client ID or name for QR code');
                        }
                        if (DEBUG_ACTIONS) console.log(`[DEBUG] Generating QR for client: ${clientName} (${clientId})`);
                        
                        // Generate QR code data in the required format
                        const qrData = `CLIENT_ID:${clientId}|NAME:${clientName}`;
                        
                        // Create a modal to display the QR code
                        const qrModalHtml = `
                            <div class="modal fade" id="qrCodeModal" tabindex="-1" aria-labelledby="qrCodeModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title" id="qrCodeModalLabel">QR Code for Client: ${clientName}</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body text-center">
                                            <div id="qrCodeContainer" class="d-flex justify-content-center my-4"></div>
                                            <div class="alert alert-info">
                                                <strong>QR Data:</strong> ${qrData}
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Remove existing modal if present
                        const existingModal = document.getElementById('qrCodeModal');
                        if (existingModal) {
                            existingModal.remove();
                        }
                        
                        // Add modal to body
                        document.body.insertAdjacentHTML('beforeend', qrModalHtml);
                        
                        // Show the modal
                        const qrModal = new bootstrap.Modal(document.getElementById('qrCodeModal'));
                        qrModal.show();
                        if (DEBUG_ACTIONS) console.log('[DEBUG] QR code modal displayed');
                        
                        // Generate QR code after modal is shown
                        const qrModalElement = document.getElementById('qrCodeModal');
                        qrModalElement.addEventListener('shown.bs.modal', function generateQRCode() {
                            const qrContainer = document.getElementById('qrCodeContainer');
                            if (qrContainer) {
                                // Clear previous QR code
                                qrContainer.innerHTML = '';
                                
                                // Check if QRCode library is loaded
                                if (typeof QRCode === 'undefined') {
                                    console.error('[ERROR] QRCode library not loaded');
                                    qrContainer.innerHTML = '<div class="alert alert-danger">QR Code library not loaded</div>';
                                    return;
                                }
                                
                                // Generate QR code
                                QRCode.toCanvas(qrContainer, qrData, {
                                    width: 200,
                                    height: 200,
                                    margin: 2,
                                    color: {
                                        dark: '#000000',
                                        light: '#ffffff'
                                    }
                                }, function (error) {
                                    if (error) {
                                        console.error('[ERROR] QR code generation error:', error);
                                        qrContainer.innerHTML = '<div class="alert alert-danger">Error generating QR code: ' + error.message + '</div>';
                                    } else {
                                        if (DEBUG_ACTIONS) console.log('[DEBUG] QR code generated successfully');
                                    }
                                });
                            }
                        }, { once: true }); // Use { once: true } to remove listener after first call
                        
                        // Clean up modal when closed
                        qrModalElement.addEventListener('hidden.bs.modal', function() {
                            qrModalElement.remove();
                        }, { once: true });
                    } catch (e) {
                        console.error('[ERROR] QR handler error:', e);
                        alert('Error generating QR code: ' + e.message);
                    }
                });
            });
            
            // Auto-expand advanced search if there are search parameters
            const hasSearchParams = <?php echo json_encode($has_search); ?>;
            if (hasSearchParams) {
                const advancedSearch = document.getElementById('advancedSearch');
                if (advancedSearch) {
                    const bsCollapse = new bootstrap.Collapse(advancedSearch, {
                        toggle: false
                    });
                    bsCollapse.show();
                }
            }
        });
    </script>
</body>
</html>
<?php
// Close the database connection at the very end
$conn->close();
?>