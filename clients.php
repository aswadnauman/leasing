<?php
session_start();
require_once 'config/db.php';
require_once 'includes/dynamic_dropdowns.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle AJAX search requests for clients
if (isset($_GET['action']) && $_GET['action'] == 'search' && isset($_GET['q'])) {
    $conn = getDBConnection();
    $search = $_GET['q'];
    $searchTerm = "%$search%";
    
    $stmt = $conn->prepare("SELECT client_id, full_name FROM clients WHERE full_name LIKE ? OR cnic LIKE ? OR manual_reference_no LIKE ? ORDER BY full_name LIMIT 20");
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $clients = array();
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($clients);
    $conn->close();
    exit();
}

// Handle AJAX search requests for master data
if (isset($_GET['action']) && $_GET['action'] == 'search_master' && isset($_GET['type']) && isset($_GET['q'])) {
    $conn = getDBConnection();
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
            echo json_encode([]);
            $conn->close();
            exit();
    }
    
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = array();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($data);
    $stmt->close();
    $conn->close();
    exit();
}

$conn = getDBConnection();

// Function to compress image (simplified version without GD)
function compressImage($source, $destination, $quality) {
    // Simply copy the file without compression if GD is not available
    return copy($source, $destination);
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
        
        $stmt = $conn->prepare("INSERT INTO clients (client_id, full_name, father_husband_name, cnic, mobile_primary, mobile_secondary, address_current, address_permanent, area, road, city, profession, manual_reference_no, status, remarks, outlet_id, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssssssss", $client_id, $full_name, $father_husband_name, $cnic, $mobile_primary, $mobile_secondary, $address_current, $address_permanent, $area, $road, $city, $profession, $manual_reference_no, $status, $remarks, $outlet_id, $photo_path);
        
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
            $filename = 'client_' . $_POST['client_id'] . '_photo.jpg';
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
            $stmt = $conn->prepare("UPDATE clients SET full_name=?, father_husband_name=?, cnic=?, mobile_primary=?, mobile_secondary=?, address_current=?, address_permanent=?, area=?, road=?, city=?, profession=?, manual_reference_no=?, status=?, remarks=?, outlet_id=?, photo_path=? WHERE id=?");
            $stmt->bind_param("ssssssssssssssssi", $full_name, $father_husband_name, $cnic, $mobile_primary, $mobile_secondary, $address_current, $address_permanent, $area, $road, $city, $profession, $manual_reference_no, $status, $remarks, $outlet_id, $photo_path, $id);
            
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
        $stmt = $conn->prepare("DELETE FROM clients WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = "Client deleted successfully!";
        } else {
            $error = "Error deleting client: " . $conn->error;
        }
        $stmt->close();
    }
}

// Search functionality
$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $search_term = "%$search%";
    $clients_result = $conn->prepare("SELECT * FROM clients WHERE full_name LIKE ? OR cnic LIKE ? OR manual_reference_no LIKE ? ORDER BY created_at DESC");
    $clients_result->bind_param("sss", $search_term, $search_term, $search_term);
    $clients_result->execute();
    $clients_result = $clients_result->get_result();
} else {
    // Fetch all clients
    $clients_result = $conn->query("SELECT * FROM clients ORDER BY created_at DESC");
}

// Fetch master data for dropdowns
$professions_result = $conn->query("SELECT * FROM master_profession ORDER BY profession");
$areas_result = $conn->query("SELECT * FROM master_area ORDER BY area");
$roads_result = $conn->query("SELECT * FROM master_road ORDER BY road");
$cities_result = $conn->query("SELECT * FROM master_city ORDER BY city");

// Fetch outlets for dropdown
$outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets");

$conn->close();
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
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Client List</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <form method="GET" class="d-flex">
                                        <input type="text" name="search" class="form-control me-2" placeholder="Search clients..." value="<?php echo htmlspecialchars($search); ?>">
                                        <button class="btn btn-outline-primary" type="submit">Search</button>
                                    </form>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addClientModal">
                                        <i class="bi bi-plus-circle"></i> Add New Client
                                    </button>
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
                                                    <span class="badge bg-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary edit-client" 
                                                        data-client='<?php echo json_encode($client); ?>'>
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger delete-client" 
                                                        data-id="<?php echo $client['id']; ?>">
                                                    <i class="bi bi-trash"></i>
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
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">Quick Stats</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6 text-center">
                                    <h3><?php echo $clients_result->num_rows; ?></h3>
                                    <p>Total Clients</p>
                                </div>
                                <div class="col-6 text-center">
                                    <?php 
                                    $conn = getDBConnection();
                                    $active_result = $conn->query("SELECT COUNT(*) as count FROM clients WHERE status='Active'");
                                    $active_count = $active_result->fetch_assoc()['count'];
                                    $conn->close();
                                    ?>
                                    <h3><?php echo $active_count; ?></h3>
                                    <p>Active Clients</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header bg-warning text-white">
                            <h5 class="card-title mb-0">Search Tips</h5>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>Search by name, CNIC, or reference number</li>
                                <li>Use partial matches for better results</li>
                                <li>Sort results by clicking column headers</li>
                            </ul>
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
                                    <?php while ($profession = $professions_result->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($profession['profession']); ?>">
                                            <?php echo htmlspecialchars($profession['profession']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                    <?php 
                                    // Reset the result set for future use
                                    $professions_result = $conn->query("SELECT * FROM master_profession ORDER BY profession");
                                    ?>
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
                                    <?php while ($area = $areas_result->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($area['area']); ?>">
                                            <?php echo htmlspecialchars($area['area']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                    <?php 
                                    // Reset the result set for future use
                                    $areas_result = $conn->query("SELECT * FROM master_area ORDER BY area");
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="road" class="form-label">Road</label>
                                <select class="form-control select2-master" id="road" name="road" data-type="road">
                                    <option value="">Select Road</option>
                                    <?php while ($road = $roads_result->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($road['road']); ?>">
                                            <?php echo htmlspecialchars($road['road']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                    <?php 
                                    // Reset the result set for future use
                                    $roads_result = $conn->query("SELECT * FROM master_road ORDER BY road");
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="city" class="form-label">City</label>
                                <select class="form-control select2-master" id="city" name="city" data-type="city">
                                    <option value="">Select City</option>
                                    <?php while ($city = $cities_result->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($city['city']); ?>">
                                            <?php echo htmlspecialchars($city['city']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                    <?php 
                                    // Reset the result set for future use
                                    $cities_result = $conn->query("SELECT * FROM master_city ORDER BY city");
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="outlet_id" class="form-label">Outlet *</label>
                                <select class="form-control" id="outlet_id" name="outlet_id" required>
                                    <option value="">Select Outlet</option>
                                    <?php while ($outlet = $outlets_result->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($outlet['outlet_id']); ?>">
                                            <?php echo htmlspecialchars($outlet['outlet_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                    <?php 
                                    // Reset the result set for future use
                                    $outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets");
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="2"></textarea>
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
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="edit_remarks" name="remarks" rows="2"></textarea>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="assets/js/image_handler.js"></script>
    <script src="assets/js/master_data_dropdowns.js"></script>
    <script>
        // Handle edit client button click
        document.querySelectorAll('.edit-client').forEach(button => {
            button.addEventListener('click', function() {
                const client = JSON.parse(this.getAttribute('data-client'));
                
                // Populate form fields
                document.getElementById('edit_id').value = client.id;
                document.getElementById('edit_client_id').value = client.client_id;
                document.getElementById('edit_client_id_display').value = client.client_id;
                document.getElementById('edit_full_name').value = client.full_name;
                document.getElementById('edit_father_husband_name').value = client.father_husband_name;
                document.getElementById('edit_cnic').value = client.cnic;
                document.getElementById('edit_mobile_primary').value = client.mobile_primary;
                document.getElementById('edit_mobile_secondary').value = client.mobile_secondary;
                document.getElementById('edit_address_current').value = client.address_current;
                document.getElementById('edit_address_permanent').value = client.address_permanent;
                document.getElementById('edit_manual_reference_no').value = client.manual_reference_no;
                document.getElementById('edit_remarks').value = client.remarks;
                document.getElementById('existing_photo_path').value = client.photo_path;
                document.getElementById('edit_outlet_id').value = client.outlet_id;
                document.getElementById('edit_status').value = client.status;
                
                // Set selected values for dropdowns
                if (client.profession) {
                    $('#edit_profession').append(new Option(client.profession, client.profession, true, true));
                }
                
                if (client.area) {
                    $('#edit_area').append(new Option(client.area, client.area, true, true));
                }
                
                if (client.road) {
                    $('#edit_road').append(new Option(client.road, client.road, true, true));
                }
                
                if (client.city) {
                    $('#edit_city').append(new Option(client.city, client.city, true, true));
                }
                
                // Show current photo if exists
                if (client.photo_path) {
                    document.getElementById('current_photo').innerHTML = 
                        '<p>Current Photo:</p><img src="' + client.photo_path + '" class="img-thumbnail" style="max-width: 150px;">';
                } else {
                    document.getElementById('current_photo').innerHTML = '<p>No current photo</p>';
                }
                
                // Show the modal
                var editModal = new bootstrap.Modal(document.getElementById('editClientModal'));
                editModal.show();
            });
        });
        
        // Handle delete client button click
        document.querySelectorAll('.delete-client').forEach(button => {
            button.addEventListener('click', function() {
                const clientId = this.getAttribute('data-id');
                document.getElementById('delete_client_id').value = clientId;
                
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteClientModal'));
                deleteModal.show();
            });
        });
    </script>
</body>
</html>