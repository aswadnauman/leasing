<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user has admin privileges
if ($_SESSION['role'] != 'Admin') {
    header("Location: dashboard.php");
    exit();
}

$conn = getDBConnection();

// Handle form submission for adding/updating users
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        // Add new user
        $user_id = $_POST['user_id'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $outlet_id = $_POST['outlet_id'];
        $assigned_areas = $_POST['assigned_areas'] ?? '';
        
        $stmt = $conn->prepare("INSERT INTO users (user_id, username, email, password, role, outlet_id, assigned_areas) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $user_id, $username, $email, $password, $role, $outlet_id, $assigned_areas);
        
        if ($stmt->execute()) {
            $success = "User added successfully!";
        } else {
            $error = "Error adding user: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_user'])) {
        // Update user
        $id = $_POST['id'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        $outlet_id = $_POST['outlet_id'];
        $assigned_areas = $_POST['assigned_areas'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=?, outlet_id=?, assigned_areas=?, is_active=? WHERE id=?");
        $stmt->bind_param("ssssssi", $username, $email, $role, $outlet_id, $assigned_areas, $is_active, $id);
        
        if ($stmt->execute()) {
            $success = "User updated successfully!";
        } else {
            $error = "Error updating user: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_user'])) {
        // Delete user
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = "User deleted successfully!";
        } else {
            $error = "Error deleting user: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['reset_password'])) {
        // Reset user password
        $id = $_POST['id'];
        $new_password = password_hash('password123', PASSWORD_DEFAULT); // Default password
        
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $new_password, $id);
        
        if ($stmt->execute()) {
            $success = "Password reset successfully! New password: password123";
        } else {
            $error = "Error resetting password: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all users with related data
$users_result = $conn->query("
    SELECT u.*, o.outlet_name 
    FROM users u
    JOIN outlets o ON u.outlet_id = o.outlet_id
    ORDER BY u.created_at DESC
");

// Fetch outlets for dropdown
$outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Lease Management System</title>
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
                <h1 class="h3 mb-1"><i class="bi bi-people me-2"></i>User Management</h1>
                <p class="mb-0 text-muted">Manage system users and permissions</p>
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
                <h2>User Management</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Add User Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Add New User</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="user_id" class="form-label">User ID *</label>
                                        <input type="text" class="form-control" id="user_id" name="user_id" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username *</label>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password *</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="role" class="form-label">Role *</label>
                                        <select class="form-select" id="role" name="role" required>
                                            <option value="Admin">Admin</option>
                                            <option value="BranchManager">Branch Manager</option>
                                            <option value="RecoveryOfficer">Recovery Officer</option>
                                            <option value="AccountsOfficer">Accounts Officer</option>
                                            <option value="DataEntry">Data Entry</option>
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
                            
                            <div class="mb-3">
                                <label for="assigned_areas" class="form-label">Assigned Areas (for Recovery Officers)</label>
                                <input type="text" class="form-control" id="assigned_areas" name="assigned_areas" placeholder="e.g., Area A, Area B">
                            </div>
                            
                            <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                        </form>
                    </div>
                </div>
                
                <!-- Users List -->
                <div class="card">
                    <div class="card-header">
                        <h5>All Users</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>User ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Outlet</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($users_result && $users_result->num_rows > 0): ?>
                                        <?php while ($user = $users_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                                <td><?php echo htmlspecialchars($user['outlet_name']); ?></td>
                                                <td>
                                                    <?php if ($user['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary edit-btn" 
                                                            data-id="<?php echo $user['id']; ?>"
                                                            data-user_id="<?php echo htmlspecialchars($user['user_id']); ?>"
                                                            data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                            data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                            data-role="<?php echo htmlspecialchars($user['role']); ?>"
                                                            data-outlet_id="<?php echo htmlspecialchars($user['outlet_id']); ?>"
                                                            data-assigned_areas="<?php echo htmlspecialchars($user['assigned_areas']); ?>"
                                                            data-is_active="<?php echo $user['is_active']; ?>">
                                                        Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-warning reset-btn" 
                                                            data-id="<?php echo $user['id']; ?>"
                                                            data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                        Reset Pass
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger delete-btn" 
                                                            data-id="<?php echo $user['id']; ?>"
                                                            data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No users found.</td>
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

    <!-- Edit User Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit-id" name="id">
                        <div class="mb-3">
                            <label for="edit-user_id" class="form-label">User ID</label>
                            <input type="text" class="form-control" id="edit-user_id" name="user_id" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="edit-username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="edit-username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="edit-email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-role" class="form-label">Role *</label>
                            <select class="form-select" id="edit-role" name="role" required>
                                <option value="Admin">Admin</option>
                                <option value="BranchManager">Branch Manager</option>
                                <option value="RecoveryOfficer">Recovery Officer</option>
                                <option value="AccountsOfficer">Accounts Officer</option>
                                <option value="DataEntry">Data Entry</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit-outlet_id" class="form-label">Outlet *</label>
                            <select class="form-select" id="edit-outlet_id" name="outlet_id" required>
                                <?php if ($outlets_result && $outlets_result->num_rows > 0): ?>
                                    <?php while ($outlet = $outlets_result->fetch_assoc()): ?>
                                        <option value="<?php echo $outlet['outlet_id']; ?>"><?php echo htmlspecialchars($outlet['outlet_name']); ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit-assigned_areas" class="form-label">Assigned Areas</label>
                            <input type="text" class="form-control" id="edit-assigned_areas" name="assigned_areas">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="edit-is_active" name="is_active" value="1">
                            <label class="form-check-label" for="edit-is_active">
                                Active
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reset Password Confirmation Modal -->
    <div class="modal fade" id="resetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reset the password for user "<span id="reset-username"></span>"?</p>
                    <p>The new password will be: <strong>password123</strong></p>
                </div>
                <div class="modal-footer">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" id="reset-user-id" name="id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="reset_password" class="btn btn-warning">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the user "<span id="delete-username"></span>"?</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" id="delete-user-id" name="id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_user" class="btn btn-danger">Delete</button>
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
            
            // Edit button functionality
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const user_id = this.getAttribute('data-user_id');
                    const username = this.getAttribute('data-username');
                    const email = this.getAttribute('data-email');
                    const role = this.getAttribute('data-role');
                    const outlet_id = this.getAttribute('data-outlet_id');
                    const assigned_areas = this.getAttribute('data-assigned_areas');
                    const is_active = this.getAttribute('data-is_active');
                    
                    // Fill modal fields
                    document.getElementById('edit-id').value = id;
                    document.getElementById('edit-user_id').value = user_id;
                    document.getElementById('edit-username').value = username;
                    document.getElementById('edit-email').value = email;
                    document.getElementById('edit-role').value = role;
                    document.getElementById('edit-outlet_id').value = outlet_id;
                    document.getElementById('edit-assigned_areas').value = assigned_areas;
                    document.getElementById('edit-is_active').checked = is_active == 1;
                    
                    // Set modal title
                    document.querySelector('.modal-title').textContent = 'Edit User - ' + username;
                    
                    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
                    editModal.show();
                });
            });
            
            // Reset password button functionality
            document.querySelectorAll('.reset-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const username = this.getAttribute('data-username');
                    
                    document.getElementById('reset-user-id').value = id;
                    document.getElementById('reset-username').textContent = username;
                    
                    var resetModal = new bootstrap.Modal(document.getElementById('resetModal'));
                    resetModal.show();
                });
            });
            
            // Delete button functionality
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const username = this.getAttribute('data-username');
                    
                    document.getElementById('delete-user-id').value = id;
                    document.getElementById('delete-username').textContent = username;
                    
                    var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                    deleteModal.show();
                });
            });
        });
        
    </script>
</body>
</html>