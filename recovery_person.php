<?php
session_start();
require_once 'config/db.php';
require_once 'includes/dynamic_dropdowns.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user has admin privileges
if (($_SESSION['role'] ?? '') != 'Admin') {
    header("Location: dashboard.php");
    exit();
}

// Handle AJAX search requests
handleMasterDataAjax(getDBConnection());

$conn = getDBConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_recovery_person'])) {
        $recovery_person_id = $_POST['recovery_person_id'];
        $full_name = $_POST['full_name'];
        $cnic = $_POST['cnic'];
        $mobile_number = $_POST['mobile_number'];
        $address = $_POST['address'];
        $city_id = $_POST['city_id'];
        $area_id = $_POST['area_id'];
        $email = $_POST['email'];
        $outlet_id = $_POST['outlet_id'];
        $status = $_POST['status'];
        
        // Handle photo upload
        $photo_path = null;
        
        if (isset($_POST['photo_data']) && !empty($_POST['photo_data'])) {
            $photo_data = $_POST['photo_data'];
            
            // Remove data URL prefix if present
            if (strpos($photo_data, 'data:image') === 0) {
                $comma_pos = strpos($photo_data, ',');
                if ($comma_pos !== false) {
                    $photo_data = substr($photo_data, $comma_pos + 1);
                }
            }
            
            // Decode base64 data
            $decoded_data = base64_decode($photo_data, true);
            
            // Validate that we have data
            if ($decoded_data !== false && strlen($decoded_data) > 0) {
                // Create uploads directory if it doesn't exist
                $upload_dir = 'uploads/recovery_persons/';
                
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename using the recovery_person_id from POST
                $filename = 'rp_' . $_POST['recovery_person_id'] . '_photo.jpg';
                $photo_path = $upload_dir . $filename;
                
                // Save photo and check if it was successful
                $bytes_written = file_put_contents($photo_path, $decoded_data);
                
                if ($bytes_written === false || $bytes_written === 0) {
                    // Handle error if needed
                    $photo_path = null;
                }
            }
        }
        
        $stmt = $conn->prepare("INSERT INTO recovery_persons (recovery_person_id, full_name, cnic, mobile_number, address, city_id, area_id, email, outlet_id, photo_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        // Fix: photo_path should be bound as string (s), not integer (i)
        $stmt->bind_param("sssssiissss", $recovery_person_id, $full_name, $cnic, $mobile_number, $address, $city_id, $area_id, $email, $outlet_id, $photo_path, $status);
        
        if ($stmt->execute()) {
            $success = "Recovery person added successfully!";
            header("Location: recovery_person.php?success=" . urlencode($success));
            exit();
        } else {
            error_log("Add new person - Error executing statement: " . $stmt->error);
            // Check if it's a duplicate CNIC error
            if ($conn->errno == 1062) {
                $error = "A recovery person with this CNIC already exists!";
            } else {
                $error = "Error adding recovery person: " . $conn->error;
            }
        }
        $stmt->close();
    } elseif (isset($_POST['update_recovery_person'])) {
        // Update recovery person
        $id = $_POST['id'];
        $full_name = $_POST['full_name'];
        $cnic = $_POST['cnic'];
        $mobile_number = $_POST['mobile_number'];
        $address = $_POST['address'];
        $city_id = $_POST['city_id'];
        $area_id = $_POST['area_id'];
        $email = $_POST['email'];
        $outlet_id = $_POST['outlet_id'];
        $status = $_POST['status'];
        
        // Handle photo upload
        $photo_path = $_POST['existing_photo_path']; // Keep existing photo by default
        error_log("Existing photo path: " . ($photo_path ?? 'null'));
        error_log("Photo data present: " . (isset($_POST['photo_data']) && !empty($_POST['photo_data']) ? 'yes' : 'no'));
        
        if (isset($_POST['photo_data']) && !empty($_POST['photo_data'])) {
            $photo_data = $_POST['photo_data'];
            error_log("Photo data length: " . strlen($photo_data));
            
            // Remove data URL prefix if present
            if (strpos($photo_data, 'data:image') === 0) {
                $comma_pos = strpos($photo_data, ',');
                if ($comma_pos !== false) {
                    $photo_data = substr($photo_data, $comma_pos + 1);
                }
            }
            
            // Decode base64 data
            $decoded_data = base64_decode($photo_data, true);
            error_log("Decoded data length: " . strlen($decoded_data));
            
            // Validate that we have data
            if ($decoded_data !== false && strlen($decoded_data) > 0) {
                // Create uploads directory if it doesn't exist
                $upload_dir = 'uploads/recovery_persons/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename using the recovery_person_id from POST
                $filename = 'rp_' . $_POST['recovery_person_id'] . '_photo.jpg';
                $new_photo_path = $upload_dir . $filename;
                error_log("New photo path: " . $new_photo_path);
                
                // Save photo and check if it was successful
                $bytes_written = file_put_contents($new_photo_path, $decoded_data);
                error_log("Bytes written: " . ($bytes_written !== false ? $bytes_written : 'false'));
                
                if ($bytes_written !== false && $bytes_written > 0) {
                    // Successfully saved new photo
                    $photo_path = $new_photo_path;
                    error_log("Photo saved successfully");
                    
                    // If there was an existing photo and it's different from the new one, delete the old one
                    if (!empty($_POST['existing_photo_path']) && $_POST['existing_photo_path'] !== $new_photo_path) {
                        error_log("Deleting old photo: " . $_POST['existing_photo_path']);
                        if (file_exists($_POST['existing_photo_path'])) {
                            unlink($_POST['existing_photo_path']);
                        }
                    }
                } else {
                    // Handle error if needed
                    // Keep existing photo if new photo couldn't be saved
                    error_log("Failed to save photo, keeping existing");
                    $photo_path = $_POST['existing_photo_path'];
                }
            } else {
                error_log("Invalid decoded data");
            }
        } elseif (empty($_POST['existing_photo_path'])) {
            // If no photo data and no existing photo, set to null
            $photo_path = null;
            error_log("No photo data and no existing photo, setting to null");
        }
        
        error_log("Final photo path: " . ($photo_path ?? 'null'));
        
        // Check if CNIC is being changed and if it already exists for another recovery person
        $check_stmt = $conn->prepare("SELECT id FROM recovery_persons WHERE cnic = ? AND id != ?");
        $check_stmt->bind_param("si", $cnic, $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "A recovery person with this CNIC already exists!";
        } else {
            $stmt = $conn->prepare("UPDATE recovery_persons SET full_name=?, cnic=?, mobile_number=?, address=?, city_id=?, area_id=?, email=?, outlet_id=?, photo_path=?, status=? WHERE id=?");
            // Fix: photo_path should be bound as string (s), not integer (i)
            $stmt->bind_param("ssssiisssii", $full_name, $cnic, $mobile_number, $address, $city_id, $area_id, $email, $outlet_id, $photo_path, $status, $id);
            
            if ($stmt->execute()) {
                $success = "Recovery person updated successfully!";
                header("Location: recovery_person.php?success=" . urlencode($success));
                exit();
            } else {
                $error = "Error updating recovery person: " . $conn->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    } elseif (isset($_POST['delete_recovery_person'])) {
        // Delete recovery person
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM recovery_persons WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = "Recovery person deleted successfully!";
            header("Location: recovery_person.php?success=" . urlencode($success));
            exit();
        } else {
            $error = "Error deleting recovery person: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchTerm = "%$search%";

// Fetch data for dropdowns
$areas_dropdown = $conn->query("SELECT id, area FROM master_area ORDER BY area");
$cities_dropdown = $conn->query("SELECT id, city FROM master_city ORDER BY city");
$outlets_dropdown = $conn->query("SELECT outlet_id, outlet_name FROM outlets");

// Fetch recovery persons
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT rp.*, c.city, a.area, o.outlet_name FROM recovery_persons rp LEFT JOIN master_city c ON rp.city_id = c.id LEFT JOIN master_area a ON rp.area_id = a.id LEFT JOIN outlets o ON rp.outlet_id = o.outlet_id WHERE rp.full_name LIKE ? OR rp.cnic LIKE ? OR rp.recovery_person_id LIKE ? ORDER BY rp.full_name");
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
} else {
    $stmt = $conn->prepare("SELECT rp.*, c.city, a.area, o.outlet_name FROM recovery_persons rp LEFT JOIN master_city c ON rp.city_id = c.id LEFT JOIN master_area a ON rp.area_id = a.id LEFT JOIN outlets o ON rp.outlet_id = o.outlet_id ORDER BY rp.full_name");
}
$stmt->execute();
$recovery_persons_result = $stmt->get_result();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recovery Person Management - Lease Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <!-- Permanent Sidebar Navigation -->
    <?php include 'includes/permanent_sidebar.php'; ?>

    <div class="main-content" style="margin-left: 250px; padding: 20px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1"><i class="bi bi-person-badge me-2"></i>Recovery Person Management</h1>
                <p class="mb-0 text-muted">Manage system recovery person data</p>
            </div>
            <div class="d-flex">
                <button class="btn btn-secondary ms-2" onclick="window.location.href='dashboard.php'">
                    <i class="bi bi-arrow-left-circle me-1"></i>Back to Dashboard
                </button>
                <div class="dropdown ms-2">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Manage Recovery Persons</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success) || isset($_GET['success'])): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success ?? $_GET['success']); ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <!-- Search Form -->
                        <form method="GET" class="mb-3">
                            <div class="row">
                                <div class="col-md-8">
                                    <input type="text" class="form-control" name="search" placeholder="Search recovery persons..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-4">
                                    <div class="btn-group" role="group">
                                        <button type="submit" class="btn btn-primary">Search</button>
                                        <a href="recovery_person.php" class="btn btn-outline-secondary">Clear</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Add Recovery Person Form -->
                        <form method="POST" id="recovery-person-form" onsubmit="console.log('Form submitted'); console.log('Photo data length:', document.getElementById('rp-photo-data').value.length); return true;">
                            <input type="hidden" id="recovery-person-id" name="id">
                            <input type="hidden" id="rp-existing-photo-path" name="existing_photo_path">
                            <input type="hidden" id="rp-photo-data" name="photo_data">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="recovery_person_id" class="form-label">Recovery Person ID *</label>
                                    <input type="text" class="form-control" name="recovery_person_id" id="recovery_person_id" placeholder="Enter recovery person ID" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="full_name" id="full_name" placeholder="Enter full name" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="cnic" class="form-label">CNIC *</label>
                                    <input type="text" class="form-control" name="cnic" id="cnic" placeholder="Enter CNIC" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="mobile_number" class="form-label">Mobile Number</label>
                                    <input type="text" class="form-control" name="mobile_number" id="mobile_number" placeholder="Enter mobile number">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" id="email" placeholder="Enter email">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Status *</label>
                                    <select class="form-select" name="status" id="status" required>
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="city_id" class="form-label">City *</label>
                                    <select class="form-select" name="city_id" id="city_id" required>
                                        <option value="">Select City</option>
                                        <?php if ($cities_dropdown && $cities_dropdown->num_rows > 0): ?>
                                            <?php while ($city = $cities_dropdown->fetch_assoc()): ?>
                                                <option value="<?php echo $city['id']; ?>"><?php echo htmlspecialchars($city['city']); ?></option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="area_id" class="form-label">Area *</label>
                                    <select class="form-select" name="area_id" id="area_id" required>
                                        <option value="">Select Area</option>
                                        <?php if ($areas_dropdown && $areas_dropdown->num_rows > 0): ?>
                                            <?php while ($area = $areas_dropdown->fetch_assoc()): ?>
                                                <option value="<?php echo $area['id']; ?>"><?php echo htmlspecialchars($area['area']); ?></option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="outlet_id" class="form-label">Outlet *</label>
                                    <select class="form-select" name="outlet_id" id="outlet_id" required>
                                        <option value="">Select Outlet</option>
                                        <?php if ($outlets_dropdown && $outlets_dropdown->num_rows > 0): ?>
                                            <?php while ($outlet = $outlets_dropdown->fetch_assoc()): ?>
                                                <option value="<?php echo $outlet['outlet_id']; ?>"><?php echo htmlspecialchars($outlet['outlet_name']); ?></option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="rp-photo-upload" class="form-label">Photo</label>
                                    <input type="file" class="form-control" id="rp-photo-upload" accept="image/*" onchange="console.log('Inline onchange triggered', this.files)">
                                    <div id="rp-photo-preview" class="mt-2 border p-2" style="min-height: 150px; background-color: #f8f9fa;">
                                        <span class="text-muted">No image selected</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" name="address" id="address" rows="3" placeholder="Enter address"></textarea>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <button type="submit" name="add_recovery_person" class="btn btn-primary" id="recovery-person-submit-btn">Add Recovery Person</button>
                                    <button type="submit" name="update_recovery_person" class="btn btn-success" id="recovery-person-update-btn" style="display: none;">Update Recovery Person</button>
                                    <button type="button" class="btn btn-secondary" id="recovery-person-cancel-btn" style="display: none;">Cancel</button>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Recovery Persons List -->
                        <div class="table-responsive mt-4">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>CNIC</th>
                                        <th>Mobile</th>
                                        <th>City</th>
                                        <th>Area</th>
                                        <th>Outlet</th>
                                        <th>Status</th>
                                        <th>Photo</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recovery_persons_result && $recovery_persons_result->num_rows > 0): ?>
                                        <?php while ($person = $recovery_persons_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($person['recovery_person_id']); ?></td>
                                                <td><?php echo htmlspecialchars($person['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($person['cnic']); ?></td>
                                                <td><?php echo htmlspecialchars($person['mobile_number']); ?></td>
                                                <td><?php echo htmlspecialchars($person['city'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($person['area'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($person['outlet_name'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php if ($person['status'] == 'Active'): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    // Simplified photo display logic
                                                    if (!empty($person['photo_path'])):
                                                        // Check if file exists, if not show a placeholder
                                                        if (file_exists($person['photo_path'])): ?>
                                                            <img src="<?php echo htmlspecialchars($person['photo_path']); ?>" alt="Photo" style="width: 50px; height: 50px; object-fit: cover;" class="img-thumbnail">
                                                        <?php else: ?>
                                                            <span class="text-muted">File missing</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">No photo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary edit-recovery-person-btn" 
                                                            data-id="<?php echo $person['id']; ?>"
                                                            data-recovery_person_id="<?php echo htmlspecialchars($person['recovery_person_id']); ?>"
                                                            data-full_name="<?php echo htmlspecialchars($person['full_name']); ?>"
                                                            data-cnic="<?php echo htmlspecialchars($person['cnic']); ?>"
                                                            data-mobile_number="<?php echo htmlspecialchars($person['mobile_number']); ?>"
                                                            data-address="<?php echo htmlspecialchars($person['address']); ?>"
                                                            data-city_id="<?php echo $person['city_id'] ?? ''; ?>"
                                                            data-area_id="<?php echo $person['area_id'] ?? ''; ?>"
                                                            data-email="<?php echo htmlspecialchars($person['email']); ?>"
                                                            data-outlet_id="<?php echo htmlspecialchars($person['outlet_id']); ?>"
                                                            data-status="<?php echo $person['status'] == 'Active' ? 1 : 0; ?>"
                                                            data-photo_path="<?php echo htmlspecialchars($person['photo_path'] ?? ''); ?>">
                                                        Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger delete-recovery-person-btn" 
                                                            data-id="<?php echo $person['id']; ?>"
                                                            data-full_name="<?php echo htmlspecialchars($person['full_name']); ?>">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="text-center">No recovery persons found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteRecoveryPersonModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the recovery person "<span id="delete-recovery-person-name"></span>"?</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" id="delete-recovery-person-id" name="id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_recovery_person" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">&copy; 2025 Lease Management System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="assets/js/image_handler.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2 on all select elements
            $('select').select2({
                placeholder: "Select an option",
                allowClear: true,
                width: '100%'
            });
            
            // Handle file upload for recovery person
            // Initialize file upload handler
            function initializeFileUpload() {
                console.log('Attempting to initialize file upload handler');
                
                // Check if the file input element exists
                const fileInput = document.getElementById('rp-photo-upload');
                if (!fileInput) {
                    console.error('File input element not found');
                    return;
                }
                
                console.log('File input element found:', fileInput);
                console.log('File input element type:', fileInput.type);
                console.log('File input element id:', fileInput.id);
                
                // Check if the preview element exists
                const previewElement = document.getElementById('rp-photo-preview');
                if (!previewElement) {
                    console.error('Preview element not found');
                    return;
                }
                
                console.log('Preview element found:', previewElement);
                console.log('Preview element id:', previewElement.id);
                
                if (typeof handleFileUpload === 'function') {
                    console.log('Initializing file upload handler');
                    handleFileUpload('rp-photo-upload', 'rp-photo-preview', 'rp-photo-data');
                } else {
                    console.error('handleFileUpload function not found');
                }
            }
            
            // Initialize immediately
            console.log('Initializing file upload handler');
            initializeFileUpload();
            
            // Add a small delay to ensure elements are fully rendered
            setTimeout(function() {
                console.log('Re-initializing file upload handler after delay');
                initializeFileUpload();
            }, 1000);
            
            // Edit Recovery Person Button
            document.querySelectorAll('.edit-recovery-person-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const recovery_person_id = this.getAttribute('data-recovery_person_id');
                    const full_name = this.getAttribute('data-full_name');
                    const cnic = this.getAttribute('data-cnic');
                    const mobile_number = this.getAttribute('data-mobile_number');
                    const address = this.getAttribute('data-address');
                    const city_id = this.getAttribute('data-city_id');
                    const area_id = this.getAttribute('data-area_id');
                    const email = this.getAttribute('data-email');
                    const outlet_id = this.getAttribute('data-outlet_id');
                    const status = this.getAttribute('data-status');
                    const photo_path = this.getAttribute('data-photo_path');
                    
                    console.log('Edit button clicked, photo_path:', photo_path);
                    
                    // Populate form fields
                    document.getElementById('recovery-person-id').value = id;
                    document.getElementById('recovery_person_id').value = recovery_person_id;
                    document.getElementById('full_name').value = full_name;
                    document.getElementById('cnic').value = cnic;
                    document.getElementById('mobile_number').value = mobile_number;
                    document.getElementById('address').value = address;
                    document.getElementById('city_id').value = city_id;
                    $('#city_id').trigger('change'); // Trigger Select2 to update display
                    document.getElementById('area_id').value = area_id;
                    $('#area_id').trigger('change'); // Trigger Select2 to update display
                    document.getElementById('email').value = email;
                    document.getElementById('outlet_id').value = outlet_id;
                    $('#outlet_id').trigger('change'); // Trigger Select2 to update display
                    document.getElementById('status').value = status == 1 ? 'Active' : 'Inactive';
                    $('#status').trigger('change'); // Trigger Select2 to update display
                    document.getElementById('rp-existing-photo-path').value = photo_path;
                    
                    console.log('Existing photo path set in hidden field:', photo_path);
                    
                    // Show existing photo preview if available
                    if (typeof displayExistingPhoto === 'function') {
                        console.log('Displaying existing photo:', photo_path);
                        // Add a small delay to ensure the preview element is ready
                        setTimeout(function() {
                            displayExistingPhoto('rp-photo-preview', photo_path);
                        }, 100);
                    } else {
                        console.error('displayExistingPhoto function not found');
                    }
                    
                    // Show update buttons and hide add button
                    document.getElementById('recovery-person-submit-btn').style.display = 'none';
                    document.getElementById('recovery-person-update-btn').style.display = 'inline-block';
                    document.getElementById('recovery-person-cancel-btn').style.display = 'inline-block';
                    
                    // Scroll to the form
                    document.getElementById('recovery-person-form').scrollIntoView({ behavior: 'smooth' });
                });
            });
            
            // Cancel Recovery Person Edit
            document.getElementById('recovery-person-cancel-btn').addEventListener('click', function() {
                console.log('Cancel button clicked');
                // Reset form
                document.getElementById('recovery-person-form').reset();
                console.log('Form reset completed');
                document.getElementById('recovery-person-id').value = '';
                document.getElementById('rp-existing-photo-path').value = '';
                // Clear preview and show "No image selected" text
                const previewElement = document.getElementById('rp-photo-preview');
                if (previewElement) {
                    previewElement.innerHTML = '<span class="text-muted">No image selected</span>';
                    console.log('Preview cleared');
                } else {
                    console.error('Preview element not found when clearing');
                }
                
                // Reset Select2 dropdowns
                $('#city_id').val(null).trigger('change');
                $('#area_id').val(null).trigger('change');
                $('#outlet_id').val(null).trigger('change');
                $('#status').val('Active').trigger('change');
                
                // Show add button and hide update buttons
                document.getElementById('recovery-person-submit-btn').style.display = 'inline-block';
                document.getElementById('recovery-person-update-btn').style.display = 'none';
                this.style.display = 'none';
            });
            
            // Delete Recovery Person Button
            document.querySelectorAll('.delete-recovery-person-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-full_name');
                    
                    document.getElementById('delete-recovery-person-id').value = id;
                    document.getElementById('delete-recovery-person-name').textContent = name;
                    
                    var deleteModal = new bootstrap.Modal(document.getElementById('deleteRecoveryPersonModal'));
                    deleteModal.show();
                });
            });
        });
    </script>
</body>
</html>