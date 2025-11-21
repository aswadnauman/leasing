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

// Handle AJAX search requests for recovery persons
if (isset($_GET['action']) && $_GET['action'] == 'search' && isset($_GET['q'])) {
    $conn = getDBConnection();
    $search = $_GET['q'];
    $searchTerm = "%$search%";
    
    $stmt = $conn->prepare("SELECT user_id, username FROM users WHERE role='RecoveryOfficer' AND (username LIKE ? OR email LIKE ?) AND is_active=1 ORDER BY username LIMIT 20");
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $recovery_persons = array();
    while ($row = $result->fetch_assoc()) {
        $recovery_persons[] = [
            'id' => $row['user_id'],
            'text' => $row['username']
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($recovery_persons);
    $conn->close();
    exit();
}

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
                $filename = 'rp_' . $recovery_person_id . '_photo.jpg';
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
        $stmt->bind_param("sssssiissis", $recovery_person_id, $full_name, $cnic, $mobile_number, $address, $city_id, $area_id, $email, $outlet_id, $photo_path, $status);
        
        if ($stmt->execute()) {
            $success = "Recovery person added successfully!";
        } else {
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
                $filename = 'rp_' . ($_POST['recovery_person_id'] ?? 'unknown') . '_photo.jpg';
                $photo_path = $upload_dir . $filename;
                
                // Save photo and check if it was successful
                $bytes_written = file_put_contents($photo_path, $decoded_data);
                if ($bytes_written === false || $bytes_written === 0) {
                    // Handle error if needed
                    $photo_path = $_POST['existing_photo_path']; // Revert to existing photo on error
                }
            }
        } elseif (empty($_POST['existing_photo_path'])) {
            // If no photo data and no existing photo, set to null
            $photo_path = null;
        }
        
        // Check if CNIC is being changed and if it already exists for another recovery person
        $check_stmt = $conn->prepare("SELECT id FROM recovery_persons WHERE cnic = ? AND id != ?");
        $check_stmt->bind_param("si", $cnic, $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "A recovery person with this CNIC already exists!";
        } else {
            $stmt = $conn->prepare("UPDATE recovery_persons SET full_name=?, cnic=?, mobile_number=?, address=?, city_id=?, area_id=?, email=?, outlet_id=?, photo_path=?, status=? WHERE id=?");
            $stmt->bind_param("ssssiissisi", $full_name, $cnic, $mobile_number, $address, $city_id, $area_id, $email, $outlet_id, $photo_path, $status, $id);
            
            if ($stmt->execute()) {
                $success = "Recovery person updated successfully!";
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
        } else {
            $error = "Error deleting recovery person: " . $conn->error;
        }
        $stmt->close();
    }
}

// Search functionality
$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $search_term = "%$search%";
    $recovery_persons_result = $conn->prepare("SELECT rp.*, c.city as city_name, a.area as area_name, o.outlet_name FROM recovery_persons rp LEFT JOIN master_city c ON rp.city_id = c.id LEFT JOIN master_area a ON rp.area_id = a.id LEFT JOIN outlets o ON rp.outlet_id = o.outlet_id WHERE rp.full_name LIKE ? OR rp.cnic LIKE ? OR rp.mobile_number LIKE ? ORDER BY rp.created_at DESC");
    $recovery_persons_result->bind_param("sss", $search_term, $search_term, $search_term);
    $recovery_persons_result->execute();
    $recovery_persons_result = $recovery_persons_result->get_result();
} else {
    // Fetch all recovery persons
    $recovery_persons_result = $conn->query("SELECT rp.*, c.city as city_name, a.area as area_name, o.outlet_name FROM recovery_persons rp LEFT JOIN master_city c ON rp.city_id = c.id LEFT JOIN master_area a ON rp.area_id = a.id LEFT JOIN outlets o ON rp.outlet_id = o.outlet_id ORDER BY rp.created_at DESC");
}

// Fetch master data for dropdowns
$cities_result = $conn->query("SELECT id, city FROM master_city ORDER BY city");
$areas_result = $conn->query("SELECT id, area FROM master_area ORDER BY area");

// Fetch outlets for dropdown
$outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recovery Persons - Lease Management System</title>
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
                <h1 class="h3 mb-1"><i class="bi bi-person-badge me-2"></i>Recovery Persons Management</h1>
                <p class="mb-0 text-muted">Manage recovery persons in the system</p>
            </div>
            <div class="dropdown">
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
        <div class="row">
            <div class="col-md-12">
                <h2>Recovery Persons Management</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5>Add/Edit Recovery Person</h5>
                    </div>
                    <div class="card-body">
                        <!-- Add Recovery Person Form -->
                        <form method="POST" id="recovery-person-form">
                            <input type="hidden" id="rp-id" name="id">
                            <input type="hidden" id="rp-existing-photo-path" name="existing_photo_path">
                            <input type="hidden" id="rp-photo-data" name="photo_data">
                                
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="recovery_person_id" class="form-label">Recovery Person ID *</label>
                                    <input type="text" class="form-control" id="recovery_person_id" name="recovery_person_id" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                                </div>
                            </div>
                                
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="cnic" class="form-label">CNIC *</label>
                                    <input type="text" class="form-control" id="cnic" name="cnic" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="mobile_number" class="form-label">Mobile Number</label>
                                    <input type="text" class="form-control" id="mobile_number" name="mobile_number">
                                </div>
                            </div>
                                
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                            </div>
                                
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="city_id" class="form-label">City</label>
                                    <select class="form-select select2-city" id="city_id" name="city_id">
                                        <option value="">Select City</option>
                                        <?php if ($cities_result && $cities_result->num_rows > 0): ?>
                                            <?php while ($city = $cities_result->fetch_assoc()): ?>
                                                <option value="<?php echo $city['id']; ?>"><?php echo htmlspecialchars($city['city']); ?></option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="area_id" class="form-label">Area</label>
                                    <select class="form-select select2-area" id="area_id" name="area_id">
                                        <option value="">Select Area</option>
                                        <?php if ($areas_result && $areas_result->num_rows > 0): ?>
                                            <?php while ($area = $areas_result->fetch_assoc()): ?>
                                                <option value="<?php echo $area['id']; ?>"><?php echo htmlspecialchars($area['area']); ?></option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                                
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="outlet_id" class="form-label">Assigned Outlet *</label>
                                    <select class="form-select select2-outlet" id="outlet_id" name="outlet_id" required>
                                        <option value="">Select Outlet</option>
                                        <?php if ($outlets_result && $outlets_result->num_rows > 0): ?>
                                            <?php while ($outlet = $outlets_result->fetch_assoc()): ?>
                                                <option value="<?php echo $outlet['outlet_id']; ?>"><?php echo htmlspecialchars($outlet['outlet_name']); ?></option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                                
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <!-- Photo Upload Section -->
                                    <div class="card">
                                        <div class="card-header">
                                            <h6>Recovery Person Photo</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="mb-3">
                                                        <label for="rp-photo-upload" class="form-label">Upload Photo</label>
                                                        <input type="file" class="form-control" id="rp-photo-upload" accept="image/*">
                                                        <div class="form-text">JPG or PNG format, automatically optimized to ≤ 20KB</div>
                                                        <img id="rp-photo-preview" src="" alt="Photo Preview" style="display: none; max-width: 100%; height: 150px; margin-top: 10px; border: 1px solid #ddd; border-radius: 4px;">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                                
                            <button type="submit" name="add_recovery_person" class="btn btn-primary">Add Recovery Person</button>
                            <button type="button" class="btn btn-success" id="rp-update-btn" style="display: none;">Update Recovery Person</button>
                            <button type="button" class="btn btn-secondary" id="rp-cancel-btn" style="display:none;">Cancel</button>
                        </form>
                    </div>
                </div>
                
                <!-- Recovery Persons List -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Recovery Persons List</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>CNIC</th>
                                        <th>Mobile</th>
                                        <th>City</th>
                                        <th>Area</th>
                                        <th>Outlet</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recovery_persons_result && $recovery_persons_result->num_rows > 0): ?>
                                        <?php while ($person = $recovery_persons_result->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($person['photo_path']) && file_exists($person['photo_path']) && filesize($person['photo_path']) > 0): ?>
                                                        <img src="<?php echo htmlspecialchars($person['photo_path']); ?>" alt="Photo" width="50" height="50" class="rounded" onerror="this.onerror=null;this.src='assets/images/no-photo.png';">
                                                    <?php else: ?>
                                                        <div class="bg-light border rounded" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                                            <span class="text-muted">No Photo</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($person['recovery_person_id']); ?></td>
                                                <td><?php echo htmlspecialchars($person['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($person['cnic']); ?></td>
                                                <td><?php echo htmlspecialchars($person['mobile_number']); ?></td>
                                                <td><?php echo htmlspecialchars($person['city_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($person['area_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($person['outlet_name'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $person['status'] == 1 ? 'success' : 'secondary'; ?>">
                                                        <?php echo $person['status'] == 1 ? 'Active' : 'Inactive'; ?>
                                                    </span>
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
                                                            data-status="<?php echo $person['status']; ?>"
                                                            data-photo_path="<?php echo htmlspecialchars($person['photo_path']); ?>">
                                                        Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger delete-recovery-person-btn" 
                                                            data-id="<?php echo $person['id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($person['full_name']); ?>">
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
        // Initialize Select2 on all select elements with specific classes
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2 on all select elements with search functionality
            $('.select2-city').select2({
                placeholder: "Select a city",
                allowClear: true,
                width: '100%'
            });
            
            $('.select2-area').select2({
                placeholder: "Select an area",
                allowClear: true,
                width: '100%'
            });
            
            $('.select2-outlet').select2({
                placeholder: "Select an outlet",
                allowClear: true,
                width: '100%'
            });
            
            // Handle file upload for recovery person
            handleFileUpload('rp-photo-upload', 'rp-photo-preview');
        });
        
        // Handle form submission for recovery person
        document.getElementById('recovery-person-form').addEventListener('submit', function(e) {
            // Get optimized image data
            const imageData = getOptimizedImageData('rp-photo-preview');
            if (imageData) {
                document.getElementById('rp-photo-data').value = imageData;
                console.log("Image data captured for submission");
            } else {
                console.log("No valid image data to submit");
            }
        });
        
        // Update button functionality
        document.getElementById('rp-update-btn').addEventListener('click', function() {
            // Get optimized image data
            const imageData = getOptimizedImageData('rp-photo-preview');
            if (imageData) {
                document.getElementById('rp-photo-data').value = imageData;
            }
            
            // Change the form submission to use update
            const form = document.getElementById('recovery-person-form');
            const updateInput = document.createElement('input');
            updateInput.type = 'hidden';
            updateInput.name = 'update_recovery_person';
            updateInput.value = '1';
            form.appendChild(updateInput);
            form.submit();
        });
        
        // Cancel button functionality for recovery person
        document.getElementById('rp-cancel-btn').addEventListener('click', function() {
            // Reset form
            document.getElementById('recovery-person-form').reset();
            document.getElementById('rp-id').value = '';
            document.getElementById('recovery_person_id').value = '';
            document.getElementById('recovery_person_id').readOnly = false;
            document.getElementById('rp-existing-photo-path').value = '';
            document.getElementById('rp-photo-data').value = '';
            document.getElementById('rp-photo-preview').style.display = 'none';
            
            // Reset Select2 dropdowns
            $('#city_id').val('').trigger('change');
            $('#area_id').val('').trigger('change');
            $('#outlet_id').val('').trigger('change');
            
            // Reset form title and submit button
            document.querySelector('[name="add_recovery_person"]').style.display = 'inline-block';
            document.getElementById('rp-update-btn').style.display = 'none';
            this.style.display = 'none';
        });
        
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
                
                // Fill form fields
                document.getElementById('rp-id').value = id;
                document.getElementById('recovery_person_id').value = recovery_person_id;
                document.getElementById('recovery_person_id').readOnly = true;
                document.getElementById('full_name').value = full_name;
                document.getElementById('cnic').value = cnic;
                document.getElementById('mobile_number').value = mobile_number;
                document.getElementById('address').value = address;
                document.getElementById('email').value = email;
                document.getElementById('status').value = status;
                document.getElementById('rp-existing-photo-path').value = photo_path || '';
                
                // Set dropdown values and trigger Select2 update
                const citySelect = document.getElementById('city_id');
                const areaSelect = document.getElementById('area_id');
                const outletSelect = document.getElementById('outlet_id');
                
                citySelect.value = city_id || '';
                areaSelect.value = area_id || '';
                outletSelect.value = outlet_id || '';
                
                // Trigger Select2 to update the display
                $('#city_id').trigger('change');
                $('#area_id').trigger('change');
                $('#outlet_id').trigger('change');
                
                // Show existing photo if available
                const photoPreview = document.getElementById('rp-photo-preview');
                if (photo_path && photo_path !== '' && photo_path !== 'null') {
                    photoPreview.src = photo_path;
                    photoPreview.style.display = 'block';
                } else {
                    photoPreview.style.display = 'none';
                }
                
                // Change form title and submit button
                document.querySelector('[name="add_recovery_person"]').style.display = 'none';
                document.getElementById('rp-update-btn').style.display = 'inline-block';
                document.getElementById('rp-cancel-btn').style.display = 'inline-block';
                
                // Scroll to the form
                document.getElementById('recovery-person-form').scrollIntoView({ behavior: 'smooth' });
            });
        });
        
        // Delete Recovery Person Button
        document.querySelectorAll('.delete-recovery-person-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                
                document.getElementById('delete-recovery-person-id').value = id;
                document.getElementById('delete-recovery-person-name').textContent = name;
                
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteRecoveryPersonModal'));
                deleteModal.show();
            });
        });
    </script>
</body>
</html>