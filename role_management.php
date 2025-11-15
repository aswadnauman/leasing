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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_role'])) {
        // Add new role
        $role_name = $_POST['role_name'];
        $description = $_POST['description'];
        
        $stmt = $conn->prepare("INSERT INTO user_roles (role_name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $role_name, $description);
        
        if ($stmt->execute()) {
            $role_id = $conn->insert_id;
            
            // Add permissions if provided
            if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
                foreach ($_POST['permissions'] as $permission) {
                    $perm_stmt = $conn->prepare("INSERT INTO role_permissions (role_id, permission) VALUES (?, ?)");
                    $perm_stmt->bind_param("is", $role_id, $permission);
                    $perm_stmt->execute();
                    $perm_stmt->close();
                }
            }
            
            $success = "Role added successfully!";
        } else {
            $error = "Error adding role: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_role'])) {
        // Update role
        $id = $_POST['id'];
        $role_name = $_POST['role_name'];
        $description = $_POST['description'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE user_roles SET role_name=?, description=?, is_active=? WHERE id=?");
        $stmt->bind_param("ssii", $role_name, $description, $is_active, $id);
        
        if ($stmt->execute()) {
            // Delete existing permissions
            $del_stmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id=?");
            $del_stmt->bind_param("i", $id);
            $del_stmt->execute();
            $del_stmt->close();
            
            // Add new permissions if provided
            if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
                foreach ($_POST['permissions'] as $permission) {
                    $perm_stmt = $conn->prepare("INSERT INTO role_permissions (role_id, permission) VALUES (?, ?)");
                    $perm_stmt->bind_param("is", $id, $permission);
                    $perm_stmt->execute();
                    $perm_stmt->close();
                }
            }
            
            $success = "Role updated successfully!";
        } else {
            $error = "Error updating role: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_role'])) {
        // Delete role
        $id = $_POST['id'];
        
        // Check if role is assigned to any users
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role IN (SELECT role_name FROM user_roles WHERE id=?)");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        $check_stmt->close();
        
        if ($check_row['count'] > 0) {
            $error = "Cannot delete role. It is assigned to " . $check_row['count'] . " user(s).";
        } else {
            // Delete permissions first
            $del_perm_stmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id=?");
            $del_perm_stmt->bind_param("i", $id);
            $del_perm_stmt->execute();
            $del_perm_stmt->close();
            
            // Delete role
            $stmt = $conn->prepare("DELETE FROM user_roles WHERE id=?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success = "Role deleted successfully!";
            } else {
                $error = "Error deleting role: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Fetch all roles with permissions
$roles_result = $conn->query("
    SELECT ur.*, 
           GROUP_CONCAT(rp.permission) as permissions
    FROM user_roles ur
    LEFT JOIN role_permissions rp ON ur.id = rp.role_id
    GROUP BY ur.id
    ORDER BY ur.role_name
");

// Define all possible permissions
$all_permissions = [
    'dashboard_view',
    'client_manage',
    'lease_manage',
    'product_manage',
    'collection_manage',
    'report_view',
    'user_manage',
    'role_manage',
    'audit_view',
    'backup_manage'
];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Management - Lease Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building me-2"></i>Lease Management System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <button class="btn btn-outline-light d-lg-none ms-2" type="button" data-bs-toggle="offcanvas" 
                    data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
                <i class="bi bi-list"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation (Desktop) -->
            <div class="col-lg-2 d-none d-lg-block p-0">
                <?php include 'includes/sidebar_navigation.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10 col-md-12 main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1><i class="bi bi-shield-lock me-3"></i>Role Management</h1>
                            <p class="mb-0">Manage user roles and permissions</p>
                        </div>
                    </div>
                </div>

                <!-- Breadcrumb Navigation -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="users.php">Administration</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Role Management</li>
                    </ol>
                </nav>

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
                
                <!-- Add Role Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-plus-circle me-2"></i>Add New Role</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="role_name" class="form-label">Role Name *</label>
                                        <input type="text" class="form-control" id="role_name" name="role_name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <input type="text" class="form-control" id="description" name="description">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Permissions</label>
                                <div class="row">
                                    <?php foreach ($all_permissions as $permission): ?>
                                        <div class="col-md-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $permission; ?>" id="perm_<?php echo $permission; ?>">
                                                <label class="form-check-label" for="perm_<?php echo $permission; ?>">
                                                    <?php 
                                                    switch ($permission) {
                                                        case 'dashboard_view': echo 'View Dashboard'; break;
                                                        case 'client_manage': echo 'Manage Clients'; break;
                                                        case 'lease_manage': echo 'Manage Leases'; break;
                                                        case 'product_manage': echo 'Manage Products'; break;
                                                        case 'collection_manage': echo 'Manage Collections'; break;
                                                        case 'report_view': echo 'View Reports'; break;
                                                        case 'user_manage': echo 'Manage Users'; break;
                                                        case 'role_manage': echo 'Manage Roles'; break;
                                                        case 'audit_view': echo 'View Audit Trail'; break;
                                                        case 'backup_manage': echo 'Manage Backups'; break;
                                                        default: echo $permission;
                                                    }
                                                    ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <button type="submit" name="add_role" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-1"></i>Add Role
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Roles List -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-list me-2"></i>Existing Roles</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Role Name</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Permissions</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($roles_result && $roles_result->num_rows > 0): ?>
                                        <?php while ($role = $roles_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($role['role_name']); ?></td>
                                                <td><?php echo htmlspecialchars($role['description'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php if ($role['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $permissions = explode(',', $role['permissions']);
                                                    echo count($permissions) > 0 && $permissions[0] !== '' ? count($permissions) : '0';
                                                    ?> permissions
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editRoleModal<?php echo $role['id']; ?>">
                                                        <i class="bi bi-pencil me-1"></i>Edit
                                                    </button>
                                                    
                                                    <!-- Edit Role Modal -->
                                                    <div class="modal fade" id="editRoleModal<?php echo $role['id']; ?>" tabindex="-1" aria-labelledby="editRoleModalLabel<?php echo $role['id']; ?>" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <form method="POST">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="editRoleModalLabel<?php echo $role['id']; ?>">Edit Role</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="id" value="<?php echo $role['id']; ?>">
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="edit_role_name<?php echo $role['id']; ?>" class="form-label">Role Name *</label>
                                                                            <input type="text" class="form-control" id="edit_role_name<?php echo $role['id']; ?>" name="role_name" value="<?php echo htmlspecialchars($role['role_name']); ?>" required>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="edit_description<?php echo $role['id']; ?>" class="form-label">Description</label>
                                                                            <input type="text" class="form-control" id="edit_description<?php echo $role['id']; ?>" name="description" value="<?php echo htmlspecialchars($role['description'] ?? ''); ?>">
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Status</label>
                                                                            <div class="form-check">
                                                                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active<?php echo $role['id']; ?>" <?php echo $role['is_active'] ? 'checked' : ''; ?>>
                                                                                <label class="form-check-label" for="edit_is_active<?php echo $role['id']; ?>">
                                                                                    Active
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Permissions</label>
                                                                            <div class="row">
                                                                                <?php 
                                                                                $role_permissions = explode(',', $role['permissions']);
                                                                                foreach ($all_permissions as $permission): 
                                                                                ?>
                                                                                    <div class="col-md-4">
                                                                                        <div class="form-check">
                                                                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="<?php echo $permission; ?>" id="edit_perm_<?php echo $role['id'] . '_' . $permission; ?>" <?php echo in_array($permission, $role_permissions) ? 'checked' : ''; ?>>
                                                                                            <label class="form-check-label" for="edit_perm_<?php echo $role['id'] . '_' . $permission; ?>">
                                                                                                <?php 
                                                                                                switch ($permission) {
                                                                                                    case 'dashboard_view': echo 'View Dashboard'; break;
                                                                                                    case 'client_manage': echo 'Manage Clients'; break;
                                                                                                    case 'lease_manage': echo 'Manage Leases'; break;
                                                                                                    case 'product_manage': echo 'Manage Products'; break;
                                                                                                    case 'collection_manage': echo 'Manage Collections'; break;
                                                                                                    case 'report_view': echo 'View Reports'; break;
                                                                                                    case 'user_manage': echo 'Manage Users'; break;
                                                                                                    case 'role_manage': echo 'Manage Roles'; break;
                                                                                                    case 'audit_view': echo 'View Audit Trail'; break;
                                                                                                    case 'backup_manage': echo 'Manage Backups'; break;
                                                                                                    default: echo $permission;
                                                                                                }
                                                                                                ?>
                                                                                            </label>
                                                                                        </div>
                                                                                    </div>
                                                                                <?php endforeach; ?>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="update_role" class="btn btn-primary">Save Changes</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Delete Button -->
                                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteRoleModal<?php echo $role['id']; ?>">
                                                        <i class="bi bi-trash me-1"></i>Delete
                                                    </button>
                                                    
                                                    <!-- Delete Role Modal -->
                                                    <div class="modal fade" id="deleteRoleModal<?php echo $role['id']; ?>" tabindex="-1" aria-labelledby="deleteRoleModalLabel<?php echo $role['id']; ?>" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <form method="POST">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="deleteRoleModalLabel<?php echo $role['id']; ?>">Delete Role</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="id" value="<?php echo $role['id']; ?>">
                                                                        <p>Are you sure you want to delete the role "<strong><?php echo htmlspecialchars($role['role_name']); ?></strong>"?</p>
                                                                        <p class="text-danger">This action cannot be undone.</p>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="delete_role" class="btn btn-danger">Delete Role</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No roles found</td>
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

    <footer class="bg-light text-center py-4 mt-4">
        <div class="container">
            <p class="mb-0">&copy; 2025 Lease Management System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>