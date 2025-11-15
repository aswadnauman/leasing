<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Handle form submission for adding/updating collections
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_collection'])) {
        // Add new collection
        $recovery_person_id = $_POST['recovery_person_id'];
        $collection_type = $_POST['collection_type'];
        $bank_name = $_POST['bank_name'] ?? '';
        $account_number = $_POST['account_number'] ?? '';
        $reference_no = $_POST['reference_no'] ?? '';
        $transaction_id = $_POST['transaction_id'] ?? '';
        $amount = $_POST['amount'];
        $latitude = $_POST['latitude'] ?? '';
        $longitude = $_POST['longitude'] ?? '';
        $outlet_id = $_POST['outlet_id'];
        $lease_id = $_POST['lease_id'] ?? ''; // Add this line
        $client_id = $_POST['client_id'] ?? ''; // Add this line
        $created_by = $_SESSION['user_id']; // Add this line
        
        // Update the INSERT statement to include new columns
        $stmt = $conn->prepare("INSERT INTO recovery_collections (recovery_person_id, collection_type, bank_name, account_number, reference_no, transaction_id, amount, latitude, longitude, outlet_id, lease_id, client_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssdssssss", $recovery_person_id, $collection_type, $bank_name, $account_number, $reference_no, $transaction_id, $amount, $latitude, $longitude, $outlet_id, $lease_id, $client_id, $created_by);
        
        if ($stmt->execute()) {
            $success = "Collection added successfully!";
        } else {
            $error = "Error adding collection: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_collection'])) {
        // Update collection (approval)
        $id = $_POST['id'];
        $approval_status = $_POST['approval_status'];
        $remarks = $_POST['remarks'] ?? '';
        $role = $_SESSION['role'];
        
        // Determine which field to update based on user role
        if ($role == 'BranchManager') {
            $stmt = $conn->prepare("UPDATE recovery_collections SET approval_status=?, supervisor_remarks=? WHERE id=?");
            $stmt->bind_param("ssi", $approval_status, $remarks, $id);
        } elseif ($role == 'AccountsOfficer') {
            $stmt = $conn->prepare("UPDATE recovery_collections SET approval_status=?, accounts_remarks=? WHERE id=?");
            $stmt->bind_param("ssi", $approval_status, $remarks, $id);
        } else {
            // Admin can update any status
            $stmt = $conn->prepare("UPDATE recovery_collections SET approval_status=?, final_approval_remarks=? WHERE id=?");
            $stmt->bind_param("ssi", $approval_status, $remarks, $id);
        }
        
        if ($stmt->execute()) {
            $success = "Collection updated successfully!";
        } else {
            $error = "Error updating collection: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all collections with related data
$collections_result = $conn->query("
    SELECT rc.*, u.username as recovery_person_name, o.outlet_name, c.full_name as client_name, l.lease_id as lease_identifier, rp.photo_path as recovery_person_photo
    FROM recovery_collections rc
    JOIN users u ON rc.recovery_person_id = u.user_id
    JOIN outlets o ON rc.outlet_id = o.outlet_id
    LEFT JOIN clients c ON rc.client_id = c.client_id
    LEFT JOIN leases l ON rc.lease_id = l.lease_id
    LEFT JOIN recovery_persons rp ON rc.recovery_person_id = rp.recovery_person_id
    ORDER BY rc.collection_date DESC
");

// Fetch users for dropdown
$users_result = $conn->query("SELECT user_id, username FROM users WHERE role='RecoveryOfficer' AND is_active=1");

// Fetch outlets for dropdown
$outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets");

// Fetch leases for dropdown
$leases_result = $conn->query("SELECT lease_id FROM leases WHERE status IN ('Active', 'Overdue') ORDER BY lease_id");

// Fetch clients for dropdown
$clients_result = $conn->query("SELECT client_id, full_name FROM clients WHERE status='Active' ORDER BY full_name");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collections - Lease Management System</title>
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
                <h1 class="h3 mb-1"><i class="bi bi-cash-stack me-2"></i>Recovery Collections</h1>
                <p class="mb-0 text-muted">Manage recovery collections and payments</p>
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
                
                <!-- Add Collection Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-cash-stack me-2"></i>Add New Collection</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="recovery_person_id" class="form-label">Recovery Person *</label>
                                        <select class="form-select" id="recovery_person_id" name="recovery_person_id" required>
                                            <option value="">Select Recovery Person</option>
                                            <?php if ($users_result && $users_result->num_rows > 0): ?>
                                                <?php while ($user = $users_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $user['user_id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="outlet_id" class="form-label">Outlet *</label>
                                        <select class="form-select" id="outlet_id" name="outlet_id" required>
                                            <option value="">Select Outlet</option>
                                            <?php if ($outlets_result && $outlets_result->num_rows > 0): ?>
                                                <?php while ($outlet = $outlets_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $outlet['outlet_id']; ?>"><?php echo htmlspecialchars($outlet['outlet_name']); ?></option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="lease_id" class="form-label">Lease (Optional)</label>
                                        <select class="form-select" id="lease_id" name="lease_id">
                                            <option value="">Select Lease</option>
                                            <?php if ($leases_result && $leases_result->num_rows > 0): ?>
                                                <?php while ($lease = $leases_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $lease['lease_id']; ?>"><?php echo htmlspecialchars($lease['lease_id']); ?></option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="client_id" class="form-label">Client (Optional)</label>
                                        <select class="form-select" id="client_id" name="client_id">
                                            <option value="">Select Client</option>
                                            <?php if ($clients_result && $clients_result->num_rows > 0): ?>
                                                <?php while ($client = $clients_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $client['client_id']; ?>"><?php echo htmlspecialchars($client['full_name']); ?></option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="collection_type" class="form-label">Collection Type *</label>
                                        <select class="form-select" id="collection_type" name="collection_type" required>
                                            <option value="">Select Collection Type</option>
                                            <option value="Cash">Cash</option>
                                            <option value="Bank">Bank</option>
                                            <option value="OnlineTransfer">Online Transfer</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">Amount *</label>
                                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="bank_name" class="form-label">Bank Name</label>
                                        <input type="text" class="form-control" id="bank_name" name="bank_name">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="account_number" class="form-label">Account Number</label>
                                        <input type="text" class="form-control" id="account_number" name="account_number">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="reference_no" class="form-label">Reference No</label>
                                        <input type="text" class="form-control" id="reference_no" name="reference_no">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="transaction_id" class="form-label">Transaction ID</label>
                                        <input type="text" class="form-control" id="transaction_id" name="transaction_id">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="latitude" class="form-label">Latitude</label>
                                        <input type="text" class="form-control" id="latitude" name="latitude">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="longitude" class="form-label">Longitude</label>
                                        <input type="text" class="form-control" id="longitude" name="longitude">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="lease_id" class="form-label">Lease ID</label>
                                        <input type="text" class="form-control" id="lease_id" name="lease_id">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="client_id" class="form-label">Client ID</label>
                                        <input type="text" class="form-control" id="client_id" name="client_id">
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="add_collection" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Add Collection</button>
                        </form>
                    </div>
                </div>
                
                <!-- Collections List -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-list me-2"></i>All Collections</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Recovery Person</th>
                                        <th>Outlet</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($collections_result && $collections_result->num_rows > 0): ?>
                                        <?php while ($collection = $collections_result->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($collection['recovery_person_photo']) && file_exists($collection['recovery_person_photo'])): ?>
                                                        <img src="<?php echo htmlspecialchars($collection['recovery_person_photo']); ?>" alt="Photo" width="50" height="50" class="rounded">
                                                    <?php else: ?>
                                                        <div class="bg-light border rounded" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                                            <span class="text-muted">No Photo</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($collection['recovery_person_name']); ?></td>
                                                <td><?php echo htmlspecialchars($collection['outlet_name']); ?></td>
                                                <td><?php echo htmlspecialchars($collection['collection_type']); ?></td>
                                                <td>$<?php echo number_format($collection['amount'], 2); ?></td>
                                                <td><?php echo date('Y-m-d H:i', strtotime($collection['collection_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $collection['approval_status'] == 'Pending' ? 'warning' : 
                                                            ($collection['approval_status'] == 'Verified' ? 'info' : 
                                                            ($collection['approval_status'] == 'Approved' ? 'success' : 'danger')); 
                                                    ?>">
                                                        <?php echo htmlspecialchars($collection['approval_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($_SESSION['role'] == 'BranchManager' || $_SESSION['role'] == 'AccountsOfficer' || $_SESSION['role'] == 'Admin'): ?>
                                                        <button class="btn btn-sm btn-outline-primary approve-btn" 
                                                                data-id="<?php echo $collection['id']; ?>"
                                                                data-status="<?php echo $collection['approval_status']; ?>">
                                                            <i class="bi bi-check-circle"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No collections found.</td>
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

    <!-- Approval Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveModalLabel">Approve Collection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="approve-id" name="id">
                        <div class="mb-3">
                            <label for="approval_status" class="form-label">Approval Status</label>
                            <select class="form-select" id="approval_status" name="approval_status" required>
                                <option value="Pending">Pending</option>
                                <option value="Verified">Verified</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_collection" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="bg-light text-center py-4">
        <div class="container">
            <p class="mb-0">&copy; 2025 Lease Management System. All rights reserved.</p>
            <p class="mb-0 text-muted small">Version 1.0.0</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Initialize Select2 on all select elements
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2 on all select elements with search functionality
            $('select').select2({
                placeholder: "Select an option",
                allowClear: true,
                width: '100%'
            });
            
            // Approval button functionality
            document.querySelectorAll('.approve-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const status = this.getAttribute('data-status');
                    
                    document.getElementById('approve-id').value = id;
                    document.getElementById('approval_status').value = status;
                    
                    var approveModal = new bootstrap.Modal(document.getElementById('approveModal'));
                    approveModal.show();
                });
            });
        });
        
    </script>
</body>
</html>