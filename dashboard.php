<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Get user info with proper checks for session variables
$user_id = $_SESSION['user_id'] ?? '';
$username = $_SESSION['username'] ?? 'Unknown User';
$role = $_SESSION['role'] ?? 'User';
$outlet_id = $_SESSION['outlet_id'] ?? '';

// Get outlet name
$outlet_name = "Unknown Outlet";
if (!empty($outlet_id)) {
    $outlet_stmt = $conn->prepare("SELECT outlet_name FROM outlets WHERE outlet_id = ?");
    $outlet_stmt->bind_param("s", $outlet_id);
    $outlet_stmt->execute();
    $outlet_result = $outlet_stmt->get_result();
    if ($outlet = $outlet_result->fetch_assoc()) {
        $outlet_name = $outlet['outlet_name'];
    }
    $outlet_stmt->close();
}

// Fetch dashboard statistics based on user role
if ($role == 'Admin') {
    // Admin stats (all outlets)
    $stats_stmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM leases WHERE status = 'Active') as active_leases,
            (SELECT COUNT(*) FROM recovery_collections WHERE approval_status = 'Pending') as pending_collections,
            (SELECT COUNT(*) FROM outlets) as total_outlets,
            (SELECT COUNT(*) FROM users WHERE is_active = 1) as active_users,
            (SELECT COUNT(*) FROM clients WHERE status = 'Active') as active_clients,
            (SELECT COUNT(*) FROM products WHERE status = 'Available') as available_products
    ");
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    $stats_stmt->close();
} else {
    // Outlet-specific stats
    if (!empty($outlet_id)) {
        $stats_stmt = $conn->prepare("
            SELECT 
                (SELECT COUNT(*) FROM leases WHERE outlet_id = ? AND status = 'Active') as active_leases,
                (SELECT COUNT(*) FROM recovery_collections WHERE outlet_id = ? AND approval_status = 'Pending') as pending_collections,
                (SELECT COUNT(*) FROM clients WHERE outlet_id = ? AND status = 'Active') as active_clients,
                (SELECT COUNT(*) FROM users WHERE outlet_id = ? AND is_active = 1) as active_users,
                (SELECT COUNT(*) FROM products WHERE outlet_id = ? AND status = 'Available') as available_products,
                (SELECT SUM(outstanding_amount) FROM leases WHERE outlet_id = ? AND status = 'Active') as total_outstanding,
                (SELECT COUNT(*) FROM products WHERE outlet_id = ?) as total_products,
                (SELECT COUNT(*) FROM products WHERE outlet_id = ? AND status = 'Leased') as leased_products
        ");
        $stats_stmt->bind_param("ssssssss", $outlet_id, $outlet_id, $outlet_id, $outlet_id, $outlet_id, $outlet_id, $outlet_id, $outlet_id);
        $stats_stmt->execute();
        $stats_result = $stats_stmt->get_result();
        $stats = $stats_result->fetch_assoc();
        $stats_stmt->close();
    } else {
        // Default stats if no outlet_id
        $stats = [
            'active_leases' => 0,
            'pending_collections' => 0,
            'active_clients' => 0,
            'active_users' => 0,
            'available_products' => 0,
            'total_outstanding' => 0
        ];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Lease Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <!-- Permanent Sidebar Navigation -->
    <?php include 'includes/permanent_sidebar.php'; ?>

    <div class="main-content" style="margin-left: 250px; padding: 20px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
                <p class="mb-0 text-muted">Welcome, <?php echo htmlspecialchars($username); ?>! You are logged in as <?php echo htmlspecialchars($role); ?> at <?php echo htmlspecialchars($outlet_name); ?>.</p>
            </div>
            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($username); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
                <!-- Page Header -->
                <div class="page-header">
                    <h1><i class="bi bi-speedometer2 me-3"></i>Dashboard</h1>
                    <p class="mb-0">Welcome, <?php echo htmlspecialchars($username); ?>! You are logged in as <?php echo htmlspecialchars($role); ?> at <?php echo htmlspecialchars($outlet_name); ?>.</p>
                </div>

                <!-- Dashboard Statistics -->
                <div class="row">
                    <?php if ($role == 'Admin'): ?>
                    <!-- Admin Statistics -->
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title"><?php echo $stats['active_leases'] ?? 0; ?></h5>
                                        <p class="card-text">Active Leases</p>
                                    </div>
                                    <i class="bi bi-file-earmark-text" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card bg-warning text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title"><?php echo $stats['pending_collections'] ?? 0; ?></h5>
                                        <p class="card-text">Pending Collections</p>
                                    </div>
                                    <i class="bi bi-cash-stack" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title"><?php echo $stats['total_outlets'] ?? 0; ?></h5>
                                        <p class="card-text">Total Outlets</p>
                                    </div>
                                    <i class="bi bi-shop" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card bg-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title"><?php echo $stats['active_users'] ?? 0; ?></h5>
                                        <p class="card-text">Active Users</p>
                                    </div>
                                    <i class="bi bi-people" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Non-Admin Statistics -->
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title"><?php echo $stats['active_leases'] ?? 0; ?></h5>
                                        <p class="card-text">Active Leases</p>
                                    </div>
                                    <i class="bi bi-file-earmark-text" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card bg-warning text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title"><?php echo $stats['pending_collections'] ?? 0; ?></h5>
                                        <p class="card-text">Pending Collections</p>
                                    </div>
                                    <i class="bi bi-cash-stack" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title"><?php echo $stats['active_clients'] ?? 0; ?></h5>
                                        <p class="card-text">Active Clients</p>
                                    </div>
                                    <i class="bi bi-person" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card bg-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title"><?php echo $stats['available_products'] ?? 0; ?></h5>
                                        <p class="card-text">Available Products</p>
                                    </div>
                                    <i class="bi bi-box-seam" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card bg-secondary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title"><?php echo $stats['leased_products'] ?? 0; ?></h5>
                                        <p class="card-text">Leased Products</p>
                                    </div>
                                    <i class="bi bi-boxes" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card bg-dark text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title"><?php echo $stats['total_products'] ?? 0; ?></h5>
                                        <p class="card-text">Total Products</p>
                                    </div>
                                    <i class="bi bi-box2" style="font-size: 2.5rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Actions Section -->
                <div class="quick-actions">
                    <div class="card-header">
                        <h5><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if ($role == 'Admin' || $role == 'BranchManager' || $role == 'Sales'): ?>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="clients.php?action=add" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-person-plus me-2"></i>Add New Client
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="leases.php?action=add" class="btn btn-outline-success w-100">
                                    <i class="bi bi-file-earmark-plus me-2"></i>Create New Lease
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($role == 'Admin' || $role == 'BranchManager' || $role == 'RecoveryOfficer' || $role == 'AccountsOfficer'): ?>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="collections.php?action=add" class="btn btn-outline-warning w-100">
                                    <i class="bi bi-cash me-2"></i>Add Collection
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($role == 'Admin' || $role == 'BranchManager' || $role == 'Sales'): ?>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="products.php?action=add" class="btn btn-outline-info w-100">
                                    <i class="bi bi-box-seam me-2"></i>Add Product
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <div class="col-md-3 col-6 mb-3">
                                <a href="reports.php" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-bar-chart me-2"></i>View Reports
                                </a>
                            </div>
                            
                            <?php if ($role == 'Admin'): ?>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="users.php" class="btn btn-outline-dark w-100">
                                    <i class="bi bi-people me-2"></i>Manage Users
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="outlets.php" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-shop me-2"></i>Manage Outlets
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($role == 'BranchManager'): ?>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="master_settings.php" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-gear me-2"></i>Master Settings
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Module Overview Section -->
                <div class="module-cards">
                    <div class="card-header">
                        <h5><i class="bi bi-grid me-2"></i>Module Access</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if ($role == 'Admin' || $role == 'BranchManager' || $role == 'Sales'): ?>
                            <div class="col-md-3 col-6 mb-4">
                                <div class="card module-card h-100">
                                    <div class="card-body">
                                        <i class="bi bi-people text-primary"></i>
                                        <h6>Client Management</h6>
                                        <p class="text-muted small">Manage clients and guarantors</p>
                                        <a href="clients.php" class="btn btn-sm btn-outline-primary">Access</a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($role == 'Admin' || $role == 'BranchManager' || $role == 'Sales'): ?>
                            <div class="col-md-3 col-6 mb-4">
                                <div class="card module-card h-100">
                                    <div class="card-body">
                                        <i class="bi bi-file-earmark-text text-success"></i>
                                        <h6>Lease Management</h6>
                                        <p class="text-muted small">Create and manage leases</p>
                                        <a href="leases.php" class="btn btn-sm btn-outline-success">Access</a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($role == 'Admin' || $role == 'BranchManager' || $role == 'RecoveryOfficer' || $role == 'AccountsOfficer'): ?>
                            <div class="col-md-3 col-6 mb-4">
                                <div class="card module-card h-100">
                                    <div class="card-body">
                                        <i class="bi bi-cash-stack text-warning"></i>
                                        <h6>Collections</h6>
                                        <p class="text-muted small">Track payments and collections</p>
                                        <a href="collections.php" class="btn btn-sm btn-outline-warning">Access</a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($role == 'Admin' || $role == 'BranchManager' || $role == 'Sales'): ?>
                            <div class="col-md-3 col-6 mb-4">
                                <div class="card module-card h-100">
                                    <div class="card-body">
                                        <i class="bi bi-box-seam text-info"></i>
                                        <h6>Inventory</h6>
                                        <p class="text-muted small">Manage products and stock</p>
                                        <a href="inventory_module.php" class="btn btn-sm btn-outline-info">Access</a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="col-md-3 col-6 mb-4">
                                <div class="card module-card h-100">
                                    <div class="card-body">
                                        <i class="bi bi-bar-chart text-secondary"></i>
                                        <h6>Reports</h6>
                                        <p class="text-muted small">Generate detailed reports</p>
                                        <a href="reports.php" class="btn btn-sm btn-outline-secondary">Access</a>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($role == 'Admin'): ?>
                            <div class="col-md-3 col-6 mb-4">
                                <div class="card module-card h-100">
                                    <div class="card-body">
                                        <i class="bi bi-shield-lock text-dark"></i>
                                        <h6>Administration</h6>
                                        <p class="text-muted small">System settings and users</p>
                                        <a href="users.php" class="btn btn-sm btn-outline-dark">Access</a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($role == 'Admin' || $role == 'BranchManager' || $role == 'Sales'): ?>
                            <div class="col-md-3 col-6 mb-4">
                                <div class="card module-card h-100">
                                    <div class="card-body">
                                        <i class="bi bi-gear text-primary"></i>
                                        <h6>Master Settings</h6>
                                        <p class="text-muted small">Reference data management</p>
                                        <a href="master_settings.php" class="btn btn-sm btn-outline-primary">Access</a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($role == 'Admin'): ?>
                            <div class="col-md-3 col-6 mb-4">
                                <div class="card module-card h-100">
                                    <div class="card-body">
                                        <i class="bi bi-shop text-success"></i>
                                        <h6>Outlet Management</h6>
                                        <p class="text-muted small">Branch configuration</p>
                                        <a href="outlets.php" class="btn btn-sm btn-outline-success">Access</a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activities Section -->
                <div class="recent-activities">
                    <div class="card-header">
                        <h5><i class="bi bi-activity me-2"></i>Recent Activities</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Activity</th>
                                        <th>Module</th>
                                        <th>User</th>
                                        <th>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><i class="bi bi-file-earmark-plus text-success me-2"></i>New lease created</td>
                                        <td>Lease Management</td>
                                        <td><?php echo htmlspecialchars($username); ?></td>
                                        <td>Just now</td>
                                    </tr>
                                    <tr>
                                        <td><i class="bi bi-person-plus text-primary me-2"></i>Client added</td>
                                        <td>Client Management</td>
                                        <td><?php echo htmlspecialchars($username); ?></td>
                                        <td>2 hours ago</td>
                                    </tr>
                                    <tr>
                                        <td><i class="bi bi-cash-stack text-warning me-2"></i>Collection recorded</td>
                                        <td>Collections</td>
                                        <td><?php echo htmlspecialchars($username); ?></td>
                                        <td>Yesterday</td>
                                    </tr>
                                    <tr>
                                        <td><i class="bi bi-box-seam text-info me-2"></i>Product updated</td>
                                        <td>Inventory</td>
                                        <td><?php echo htmlspecialchars($username); ?></td>
                                        <td>2 days ago</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
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
</body>
</html>