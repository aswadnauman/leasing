<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();
$success = '';
$error = '';

// Handle AJAX search requests for clients
if (isset($_GET['action']) && $_GET['action'] == 'search' && isset($_GET['q'])) {
    $search = $_GET['q'];
    $searchTerm = "%$search%";
    
    $stmt = $conn->prepare("SELECT client_id, full_name FROM clients WHERE full_name LIKE ? OR cnic LIKE ? ORDER BY full_name LIMIT 20");
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $clients = array();
    while ($row = $result->fetch_assoc()) {
        $clients[] = array('id' => $row['client_id'], 'text' => $row['full_name']);
    }
    
    header('Content-Type: application/json');
    echo json_encode(array('results' => $clients));
    $stmt->close();
    exit();
}

// Handle AJAX search for master data
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
            echo json_encode(array('results' => array()));
            exit();
    }
    
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $results = array();
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode(array('results' => $results));
    $stmt->close();
    exit();
}

// Handle FORM SUBMISSIONS
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ADD CLIENT
    if (isset($_POST['add_client'])) {
        $client_id = $_POST['client_id'] ?? '';
        $full_name = $_POST['full_name'] ?? '';
        $father_husband_name = $_POST['father_husband_name'] ?? '';
        $cnic = $_POST['cnic'] ?? '';
        $mobile_primary = $_POST['mobile_primary'] ?? '';
        $mobile_secondary = $_POST['mobile_secondary'] ?? '';
        $address_current = $_POST['address_current'] ?? '';
        $address_permanent = $_POST['address_permanent'] ?? '';
        $profession = $_POST['profession'] ?? '';
        $area = $_POST['area'] ?? '';
        $road = $_POST['road'] ?? '';
        $city = $_POST['city'] ?? '';
        $manual_reference_no = $_POST['manual_reference_no'] ?? '';
        $status = $_POST['status'] ?? 'Active';
        $remarks = $_POST['remarks'] ?? '';
        $outlet_id = $_POST['outlet_id'] ?? '';
        $send_sms = isset($_POST['send_sms']) ? 1 : 0;
        $send_whatsapp = isset($_POST['send_whatsapp']) ? 1 : 0;
        
        // Handle photo upload
        $photo_path = NULL;
        if (isset($_POST['photo_data']) && !empty($_POST['photo_data'])) {
            $photo_data = $_POST['photo_data'];
            $photo_data = substr($photo_data, strpos($photo_data, ',') + 1);
            $photo_data = base64_decode($photo_data);
            
            $upload_dir = 'uploads/clients/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $filename = 'client_' . uniqid() . '.jpg';
            $photo_path = $upload_dir . $filename;
            file_put_contents($photo_path, $photo_data);
        }
        
        $stmt = $conn->prepare("INSERT INTO clients (client_id, full_name, father_husband_name, cnic, mobile_primary, mobile_secondary, address_current, address_permanent, area, road, city, profession, manual_reference_no, status, remarks, outlet_id, photo_path, send_sms, send_whatsapp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("sssssssssssssssisii", $client_id, $full_name, $father_husband_name, $cnic, $mobile_primary, $mobile_secondary, $address_current, $address_permanent, $area, $road, $city, $profession, $manual_reference_no, $status, $remarks, $outlet_id, $photo_path, $send_sms, $send_whatsapp);
        
        if ($stmt->execute()) {
            $success = "Client added successfully!";
        } else {
            $error = "Error adding client: " . $conn->error;
        }
        $stmt->close();
    }
    
    // UPDATE CLIENT
    elseif (isset($_POST['update_client'])) {
        $id = $_POST['id'] ?? '';
        $client_id = $_POST['client_id'] ?? '';
        $full_name = $_POST['full_name'] ?? '';
        $father_husband_name = $_POST['father_husband_name'] ?? '';
        $cnic = $_POST['cnic'] ?? '';
        $mobile_primary = $_POST['mobile_primary'] ?? '';
        $mobile_secondary = $_POST['mobile_secondary'] ?? '';
        $address_current = $_POST['address_current'] ?? '';
        $address_permanent = $_POST['address_permanent'] ?? '';
        $profession = $_POST['profession'] ?? '';
        $area = $_POST['area'] ?? '';
        $road = $_POST['road'] ?? '';
        $city = $_POST['city'] ?? '';
        $manual_reference_no = $_POST['manual_reference_no'] ?? '';
        $status = $_POST['status'] ?? 'Active';
        $remarks = $_POST['remarks'] ?? '';
        $outlet_id = $_POST['outlet_id'] ?? '';
        $send_sms = isset($_POST['send_sms']) ? 1 : 0;
        $send_whatsapp = isset($_POST['send_whatsapp']) ? 1 : 0;
        
        $photo_path = $_POST['existing_photo_path'] ?? NULL;
        if (isset($_POST['photo_data']) && !empty($_POST['photo_data'])) {
            $photo_data = $_POST['photo_data'];
            $photo_data = substr($photo_data, strpos($photo_data, ',') + 1);
            $photo_data = base64_decode($photo_data);
            
            $upload_dir = 'uploads/clients/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $filename = 'client_' . uniqid() . '.jpg';
            $photo_path = $upload_dir . $filename;
            file_put_contents($photo_path, $photo_data);
        }
        
        $stmt = $conn->prepare("UPDATE clients SET client_id=?, full_name=?, father_husband_name=?, cnic=?, mobile_primary=?, mobile_secondary=?, address_current=?, address_permanent=?, area=?, road=?, city=?, profession=?, manual_reference_no=?, status=?, remarks=?, outlet_id=?, photo_path=?, send_sms=?, send_whatsapp=? WHERE id=?");
        
        $stmt->bind_param("sssssssssssssssisii", $client_id, $full_name, $father_husband_name, $cnic, $mobile_primary, $mobile_secondary, $address_current, $address_permanent, $area, $road, $city, $profession, $manual_reference_no, $status, $remarks, $outlet_id, $photo_path, $send_sms, $send_whatsapp, $id);
        
        if ($stmt->execute()) {
            $success = "Client updated successfully!";
        } else {
            $error = "Error updating client: " . $conn->error;
        }
        $stmt->close();
    }
    
    // DELETE CLIENT
    elseif (isset($_POST['delete_client'])) {
        $id = $_POST['id'] ?? '';
        
        // Get photo path
        $stmt = $conn->prepare("SELECT photo_path FROM clients WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $client = $result->fetch_assoc();
        $stmt->close();
        
        // Delete client
        $stmt = $conn->prepare("DELETE FROM clients WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
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

// Fetch all clients
$clients_result = $conn->query("SELECT * FROM clients ORDER BY created_at DESC");
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
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h1 class="h3">Clients Management</h1>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addClientModal">
                            <i class="bi bi-plus-circle me-2"></i>Add New Client
                        </button>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Clients Table -->
            <div class="card">
                <div class="card-body">
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
                                        <button class="btn btn-sm btn-primary edit-btn" data-id="<?php echo $client['id']; ?>" data-client='<?php echo htmlspecialchars(json_encode($client), ENT_QUOTES, 'UTF-8'); ?>'>
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-info view-btn" data-client='<?php echo htmlspecialchars(json_encode($client), ENT_QUOTES, 'UTF-8'); ?>'>
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                        <button class="btn btn-sm btn-secondary qr-btn" data-id="<?php echo htmlspecialchars($client['client_id']); ?>" data-name="<?php echo htmlspecialchars($client['full_name']); ?>">
                                            <i class="bi bi-qr-code"></i> QR
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $client['id']; ?>">
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

    <!-- ADD CLIENT MODAL -->
    <div class="modal fade" id="addClientModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Add New Client</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="add_client" value="1">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Client ID *</label>
                                <input type="text" class="form-control" name="client_id" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Father/Husband Name *</label>
                                <input type="text" class="form-control" name="father_husband_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">CNIC *</label>
                                <input type="text" class="form-control" name="cnic" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Primary Mobile *</label>
                                <input type="text" class="form-control" name="mobile_primary" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Secondary Mobile</label>
                                <input type="text" class="form-control" name="mobile_secondary">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Current Address *</label>
                            <textarea class="form-control" name="address_current" rows="2" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Permanent Address *</label>
                            <textarea class="form-control" name="address_permanent" rows="2" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Profession *</label>
                                <select class="form-control select2-master" name="profession" data-type="profession" required></select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Area</label>
                                <select class="form-control select2-master" name="area" data-type="area"></select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Road</label>
                                <select class="form-control select2-master" name="road" data-type="road"></select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">City</label>
                                <select class="form-control select2-master" name="city" data-type="city"></select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Manual Reference No</label>
                                <input type="text" class="form-control" name="manual_reference_no">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Outlet *</label>
                                <select class="form-control" name="outlet_id" required>
                                    <option value="">Select Outlet</option>
                                    <?php 
                                    $outlets_result->data_seek(0);
                                    while ($outlet = $outlets_result->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($outlet['outlet_id']); ?>">
                                            <?php echo htmlspecialchars($outlet['outlet_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-control" name="status">
                                    <option value="Active">Active</option>
                                    <option value="Blocked">Blocked</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Remarks</label>
                                <input type="text" class="form-control" name="remarks">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Photo</label>
                            <input type="file" class="form-control" id="add-photo" accept="image/*">
                            <input type="hidden" id="add-photo-data" name="photo_data">
                            <div id="add-photo-preview" class="mt-2"></div>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="add-sms" name="send_sms">
                            <label class="form-check-label" for="add-sms">Send SMS Notifications</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="add-whatsapp" name="send_whatsapp">
                            <label class="form-check-label" for="add-whatsapp">Send WhatsApp Notifications</label>
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

    <!-- EDIT CLIENT MODAL -->
    <div class="modal fade" id="editClientModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Edit Client</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="update_client" value="1">
                        <input type="hidden" id="edit-id" name="id">
                        <input type="hidden" id="edit-existing-photo" name="existing_photo_path">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Client ID *</label>
                                <input type="text" class="form-control" id="edit-client-id" name="client_id" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="edit-full-name" name="full_name" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Father/Husband Name *</label>
                                <input type="text" class="form-control" id="edit-father-name" name="father_husband_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">CNIC *</label>
                                <input type="text" class="form-control" id="edit-cnic" name="cnic" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Primary Mobile *</label>
                                <input type="text" class="form-control" id="edit-mobile-primary" name="mobile_primary" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Secondary Mobile</label>
                                <input type="text" class="form-control" id="edit-mobile-secondary" name="mobile_secondary">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Current Address *</label>
                            <textarea class="form-control" id="edit-address-current" name="address_current" rows="2" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Permanent Address *</label>
                            <textarea class="form-control" id="edit-address-permanent" name="address_permanent" rows="2" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Profession *</label>
                                <select class="form-control select2-master" id="edit-profession" name="profession" data-type="profession" required></select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Area</label>
                                <select class="form-control select2-master" id="edit-area" name="area" data-type="area"></select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Road</label>
                                <select class="form-control select2-master" id="edit-road" name="road" data-type="road"></select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">City</label>
                                <select class="form-control select2-master" id="edit-city" name="city" data-type="city"></select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Manual Reference No</label>
                                <input type="text" class="form-control" id="edit-manual-ref" name="manual_reference_no">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Outlet *</label>
                                <select class="form-control" id="edit-outlet" name="outlet_id" required>
                                    <option value="">Select Outlet</option>
                                    <?php 
                                    $outlets_result->data_seek(0);
                                    while ($outlet = $outlets_result->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($outlet['outlet_id']); ?>">
                                            <?php echo htmlspecialchars($outlet['outlet_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-control" id="edit-status" name="status">
                                    <option value="Active">Active</option>
                                    <option value="Blocked">Blocked</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Remarks</label>
                                <input type="text" class="form-control" id="edit-remarks" name="remarks">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Photo</label>
                            <input type="file" class="form-control" id="edit-photo" accept="image/*">
                            <input type="hidden" id="edit-photo-data" name="photo_data">
                            <div id="edit-photo-preview" class="mt-2"></div>
                            <div id="edit-current-photo" class="mt-2"></div>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="edit-sms" name="send_sms">
                            <label class="form-check-label" for="edit-sms">Send SMS Notifications</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="edit-whatsapp" name="send_whatsapp">
                            <label class="form-check-label" for="edit-whatsapp">Send WhatsApp Notifications</label>
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

    <!-- VIEW CLIENT MODAL -->
    <div class="modal fade" id="viewClientModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">View Client Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Client ID:</strong> <span id="view-client-id"></span></p>
                            <p><strong>Full Name:</strong> <span id="view-full-name"></span></p>
                            <p><strong>Father/Husband:</strong> <span id="view-father-name"></span></p>
                            <p><strong>CNIC:</strong> <span id="view-cnic"></span></p>
                            <p><strong>Mobile Primary:</strong> <span id="view-mobile-primary"></span></p>
                            <p><strong>Mobile Secondary:</strong> <span id="view-mobile-secondary"></span></p>
                            <p><strong>Profession:</strong> <span id="view-profession"></span></p>
                            <p><strong>Status:</strong> <span id="view-status"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Area:</strong> <span id="view-area"></span></p>
                            <p><strong>Road:</strong> <span id="view-road"></span></p>
                            <p><strong>City:</strong> <span id="view-city"></span></p>
                            <p><strong>Outlet:</strong> <span id="view-outlet"></span></p>
                            <p><strong>Reference No:</strong> <span id="view-ref-no"></span></p>
                            <p><strong>Remarks:</strong> <span id="view-remarks"></span></p>
                            <p><strong>SMS Notify:</strong> <span id="view-sms"></span></p>
                            <p><strong>WhatsApp Notify:</strong> <span id="view-whatsapp"></span></p>
                        </div>
                    </div>
                    <div id="view-photo" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- DELETE CLIENT MODAL -->
    <div class="modal fade" id="deleteClientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="delete_client" value="1">
                    <input type="hidden" id="delete-id" name="id">
                    
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Delete Client</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this client? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- QR CODE MODAL -->
    <div class="modal fade" id="qrCodeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">QR Code</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="qr-code-container" class="mb-3"></div>
                    <div id="qr-data-info"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        console.log('[DEBUG] Page loaded');
        
        // Helper function
        function escapeHtml(text) {
            if (!text) return '';
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }
        
        $(document).ready(function() {
            console.log('[DEBUG] jQuery ready');
            
            // Initialize image upload handlers
            if (typeof handleFileUpload === 'function') {
                handleFileUpload('add-photo', 'add-photo-preview', 'add-photo-data');
                handleFileUpload('edit-photo', 'edit-photo-preview', 'edit-photo-data');
            }
            
            // Initialize Select2 for modals
            if (typeof Select2DropdownInitializer !== 'undefined') {
                $('#addClientModal').on('show.bs.modal', function() {
                    Select2DropdownInitializer.initializeOutletDropdowns($(this));
                });
                
                $('#editClientModal').on('show.bs.modal', function() {
                    Select2DropdownInitializer.initializeOutletDropdowns($(this));
                });
            }
        });
        
        // Document click handler for all buttons
        document.addEventListener('click', function(e) {
            // EDIT BUTTON
            if (e.target.closest('.edit-btn')) {
                const btn = e.target.closest('.edit-btn');
                const clientData = JSON.parse(btn.getAttribute('data-client'));
                
                document.getElementById('edit-id').value = clientData.id || '';
                document.getElementById('edit-client-id').value = clientData.client_id || '';
                document.getElementById('edit-full-name').value = clientData.full_name || '';
                document.getElementById('edit-father-name').value = clientData.father_husband_name || '';
                document.getElementById('edit-cnic').value = clientData.cnic || '';
                document.getElementById('edit-mobile-primary').value = clientData.mobile_primary || '';
                document.getElementById('edit-mobile-secondary').value = clientData.mobile_secondary || '';
                document.getElementById('edit-address-current').value = clientData.address_current || '';
                document.getElementById('edit-address-permanent').value = clientData.address_permanent || '';
                document.getElementById('edit-profession').value = clientData.profession || '';
                document.getElementById('edit-area').value = clientData.area || '';
                document.getElementById('edit-road').value = clientData.road || '';
                document.getElementById('edit-city').value = clientData.city || '';
                document.getElementById('edit-manual-ref').value = clientData.manual_reference_no || '';
                document.getElementById('edit-outlet').value = clientData.outlet_id || '';
                document.getElementById('edit-status').value = clientData.status || 'Active';
                document.getElementById('edit-remarks').value = clientData.remarks || '';
                document.getElementById('edit-sms').checked = (clientData.send_sms == 1);
                document.getElementById('edit-whatsapp').checked = (clientData.send_whatsapp == 1);
                document.getElementById('edit-existing-photo').value = clientData.photo_path || '';
                
                if (clientData.photo_path) {
                    document.getElementById('edit-current-photo').innerHTML = '<img src="' + escapeHtml(clientData.photo_path) + '" class="img-thumbnail" style="max-width: 150px;">';
                } else {
                    document.getElementById('edit-current-photo').innerHTML = '<p>No current photo</p>';
                }
                
                new bootstrap.Modal(document.getElementById('editClientModal')).show();
                console.log('[DEBUG] Edit modal opened');
            }
            
            // VIEW BUTTON
            if (e.target.closest('.view-btn')) {
                const btn = e.target.closest('.view-btn');
                const clientData = JSON.parse(btn.getAttribute('data-client'));
                
                document.getElementById('view-client-id').textContent = clientData.client_id || 'N/A';
                document.getElementById('view-full-name').textContent = clientData.full_name || 'N/A';
                document.getElementById('view-father-name').textContent = clientData.father_husband_name || 'N/A';
                document.getElementById('view-cnic').textContent = clientData.cnic || 'N/A';
                document.getElementById('view-mobile-primary').textContent = clientData.mobile_primary || 'N/A';
                document.getElementById('view-mobile-secondary').textContent = clientData.mobile_secondary || 'N/A';
                document.getElementById('view-profession').textContent = clientData.profession || 'N/A';
                document.getElementById('view-area').textContent = clientData.area || 'N/A';
                document.getElementById('view-road').textContent = clientData.road || 'N/A';
                document.getElementById('view-city').textContent = clientData.city || 'N/A';
                document.getElementById('view-outlet').textContent = clientData.outlet_id || 'N/A';
                document.getElementById('view-ref-no').textContent = clientData.manual_reference_no || 'N/A';
                document.getElementById('view-remarks').textContent = clientData.remarks || 'N/A';
                document.getElementById('view-sms').textContent = (clientData.send_sms == 1 ? 'Yes' : 'No');
                document.getElementById('view-whatsapp').textContent = (clientData.send_whatsapp == 1 ? 'Yes' : 'No');
                document.getElementById('view-status').innerHTML = (clientData.status == 'Active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Blocked</span>');
                
                if (clientData.photo_path) {
                    document.getElementById('view-photo').innerHTML = '<img src="' + escapeHtml(clientData.photo_path) + '" class="img-fluid rounded" style="max-width: 300px;">';
                } else {
                    document.getElementById('view-photo').innerHTML = '<p class="text-muted">No photo available</p>';
                }
                
                new bootstrap.Modal(document.getElementById('viewClientModal')).show();
                console.log('[DEBUG] View modal opened');
            }
            
            // DELETE BUTTON
            if (e.target.closest('.delete-btn')) {
                const btn = e.target.closest('.delete-btn');
                document.getElementById('delete-id').value = btn.getAttribute('data-id');
                new bootstrap.Modal(document.getElementById('deleteClientModal')).show();
                console.log('[DEBUG] Delete modal opened');
            }
            
            // QR BUTTON
            if (e.target.closest('.qr-btn')) {
                const btn = e.target.closest('.qr-btn');
                const clientId = btn.getAttribute('data-id');
                const clientName = btn.getAttribute('data-name');
                const qrData = `CLIENT_ID:${clientId}|NAME:${clientName}`;
                
                const modal = new bootstrap.Modal(document.getElementById('qrCodeModal'));
                
                // Clear previous QR
                const container = document.getElementById('qr-code-container');
                container.innerHTML = '';
                
                // Generate QR
                if (typeof QRCode !== 'undefined') {
                    QRCode.toCanvas(container, qrData, {
                        width: 250,
                        height: 250,
                        margin: 2,
                        color: { dark: '#000000', light: '#ffffff' }
                    }, function(err) {
                        if (err) {
                            console.error('[ERROR] QR generation error:', err);
                            container.innerHTML = '<div class="alert alert-danger">Error generating QR code</div>';
                        } else {
                            console.log('[DEBUG] QR code generated');
                        }
                    });
                    
                    document.getElementById('qr-data-info').innerHTML = '<strong>QR Data:</strong><br>' + escapeHtml(qrData);
                } else {
                    container.innerHTML = '<div class="alert alert-danger">QRCode library not loaded</div>';
                }
                
                modal.show();
                console.log('[DEBUG] QR modal opened for:', clientName);
            }
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
