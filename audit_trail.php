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

// Get filter parameters
$user_filter = isset($_GET['user_filter']) ? $_GET['user_filter'] : '';
$action_filter = isset($_GET['action_filter']) ? $_GET['action_filter'] : '';
$table_filter = isset($_GET['table_filter']) ? $_GET['table_filter'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build WHERE clause
$where_conditions = [];
$params = [];
$types = "";

if (!empty($user_filter)) {
    $where_conditions[] = "at.user_id = ?";
    $params[] = $user_filter;
    $types .= "s";
}

if (!empty($action_filter)) {
    $where_conditions[] = "at.action_type = ?";
    $params[] = $action_filter;
    $types .= "s";
}

if (!empty($table_filter)) {
    $where_conditions[] = "at.table_name = ?";
    $params[] = $table_filter;
    $types .= "s";
}

if (!empty($start_date)) {
    $where_conditions[] = "DATE(at.created_at) >= ?";
    $params[] = $start_date;
    $types .= "s";
}

if (!empty($end_date)) {
    $where_conditions[] = "DATE(at.created_at) <= ?";
    $params[] = $end_date;
    $types .= "s";
}

$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Fetch audit trail data
$sql = "
    SELECT 
        at.*,
        u.username
    FROM audit_trail at
    JOIN users u ON at.user_id = u.user_id
    $where_clause
    ORDER BY at.created_at DESC
    LIMIT 1000
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Fetch users for filter dropdown
$users_result = $conn->query("SELECT user_id, username FROM users ORDER BY username");

// Fetch tables for filter dropdown
$tables_result = $conn->query("
    SELECT DISTINCT table_name 
    FROM audit_trail 
    WHERE table_name IS NOT NULL 
    ORDER BY table_name
");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail - Lease Management System</title>
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
                            <h1><i class="bi bi-activity me-3"></i>Audit Trail</h1>
                            <p class="mb-0">View system activity logs</p>
                        </div>
                    </div>
                </div>

                <!-- Breadcrumb Navigation -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="users.php">Administration</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Audit Trail</li>
                    </ol>
                </nav>

                <!-- Filter Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-funnel me-2"></i>Filter Audit Trail</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="user_filter" class="form-label">User</label>
                                <select class="form-select" id="user_filter" name="user_filter">
                                    <option value="">All Users</option>
                                    <?php if ($users_result && $users_result->num_rows > 0): ?>
                                        <?php while ($user = $users_result->fetch_assoc()): ?>
                                            <option value="<?php echo $user['user_id']; ?>" <?php echo ($user_filter == $user['user_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="action_filter" class="form-label">Action</label>
                                <select class="form-select" id="action_filter" name="action_filter">
                                    <option value="">All Actions</option>
                                    <option value="CREATE" <?php echo ($action_filter == 'CREATE') ? 'selected' : ''; ?>>Create</option>
                                    <option value="UPDATE" <?php echo ($action_filter == 'UPDATE') ? 'selected' : ''; ?>>Update</option>
                                    <option value="DELETE" <?php echo ($action_filter == 'DELETE') ? 'selected' : ''; ?>>Delete</option>
                                    <option value="LOGIN" <?php echo ($action_filter == 'LOGIN') ? 'selected' : ''; ?>>Login</option>
                                    <option value="LOGOUT" <?php echo ($action_filter == 'LOGOUT') ? 'selected' : ''; ?>>Logout</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="table_filter" class="form-label">Table</label>
                                <select class="form-select" id="table_filter" name="table_filter">
                                    <option value="">All Tables</option>
                                    <?php if ($tables_result && $tables_result->num_rows > 0): ?>
                                        <?php while ($table = $tables_result->fetch_assoc()): ?>
                                            <option value="<?php echo $table['table_name']; ?>" <?php echo ($table_filter == $table['table_name']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($table['table_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            
                            <div class="col-md-2">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            
                            <div class="col-md-1 d-flex align-items-end">
                                <div>
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Filter</button>
                                    <a href="audit_trail.php" class="btn btn-secondary"><i class="bi bi-x-circle me-1"></i>Clear</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Audit Trail Table -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-list me-2"></i>Audit Trail Records</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Table</th>
                                        <th>Record ID</th>
                                        <th>Timestamp</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        switch ($row['action_type']) {
                                                            case 'CREATE': echo 'success'; break;
                                                            case 'UPDATE': echo 'warning'; break;
                                                            case 'DELETE': echo 'danger'; break;
                                                            case 'LOGIN': echo 'info'; break;
                                                            case 'LOGOUT': echo 'secondary'; break;
                                                            default: echo 'primary';
                                                        }
                                                    ?>">
                                                        <?php echo htmlspecialchars($row['action_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['table_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($row['record_id'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('M j, Y H:i:s', strtotime($row['created_at'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $row['id']; ?>">
                                                        <i class="bi bi-eye me-1"></i>View
                                                    </button>
                                                    
                                                    <!-- Details Modal -->
                                                    <div class="modal fade" id="detailsModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="detailsModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="detailsModalLabel<?php echo $row['id']; ?>">Audit Details</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <h6>Old Values</h6>
                                                                            <pre><?php echo htmlspecialchars($row['old_values'] ?? 'N/A'); ?></pre>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <h6>New Values</h6>
                                                                            <pre><?php echo htmlspecialchars($row['new_values'] ?? 'N/A'); ?></pre>
                                                                        </div>
                                                                    </div>
                                                                    <div class="mt-3">
                                                                        <h6>IP Address</h6>
                                                                        <p><?php echo htmlspecialchars($row['ip_address'] ?? 'N/A'); ?></p>
                                                                    </div>
                                                                    <div class="mt-3">
                                                                        <h6>User Agent</h6>
                                                                        <p><?php echo htmlspecialchars($row['user_agent'] ?? 'N/A'); ?></p>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No audit trail records found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($result && $result->num_rows > 0): ?>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    Showing <?php echo $result->num_rows; ?> of 1000 records (limited for performance)
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                        <i class="bi bi-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
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