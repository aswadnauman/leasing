<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Get filter parameters
$selected_branch = isset($_GET['branch_filter']) ? $_GET['branch_filter'] : 'ALL';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build date filter condition
$date_filter = "";
if (!empty($start_date) && !empty($end_date)) {
    $date_filter = " AND DATE(rc.collection_date) BETWEEN ? AND ?";
} elseif (!empty($start_date)) {
    $date_filter = " AND DATE(rc.collection_date) >= ?";
} elseif (!empty($end_date)) {
    $date_filter = " AND DATE(rc.collection_date) <= ?";
}

// Fetch report data with branch filtering
// Outlet-wise collection report
if ($selected_branch == 'ALL' && empty($start_date) && empty($end_date)) {
    $outlet_collection_stmt = $conn->prepare("
        SELECT 
            o.outlet_name,
            COUNT(rc.id) as total_collections,
            SUM(CASE WHEN rc.collection_type = 'Cash' THEN rc.amount ELSE 0 END) as cash_amount,
            SUM(CASE WHEN rc.collection_type = 'Bank' THEN rc.amount ELSE 0 END) as bank_amount,
            SUM(CASE WHEN rc.collection_type = 'OnlineTransfer' THEN rc.amount ELSE 0 END) as online_amount,
            SUM(rc.amount) as total_amount
        FROM outlets o
        LEFT JOIN recovery_collections rc ON o.outlet_id = rc.outlet_id
        GROUP BY o.id, o.outlet_name
        ORDER BY total_amount DESC
    ");
    $outlet_collection_stmt->execute();
    $outlet_collection_result = $outlet_collection_stmt->get_result();
} else {
    $outlet_collection_sql = "
        SELECT 
            o.outlet_name,
            COUNT(rc.id) as total_collections,
            SUM(CASE WHEN rc.collection_type = 'Cash' THEN rc.amount ELSE 0 END) as cash_amount,
            SUM(CASE WHEN rc.collection_type = 'Bank' THEN rc.amount ELSE 0 END) as bank_amount,
            SUM(CASE WHEN rc.collection_type = 'OnlineTransfer' THEN rc.amount ELSE 0 END) as online_amount,
            SUM(rc.amount) as total_amount
        FROM outlets o
        LEFT JOIN recovery_collections rc ON o.outlet_id = rc.outlet_id
        WHERE (? = 'ALL' OR o.outlet_id = ?)
        " . $date_filter . "
        GROUP BY o.id, o.outlet_name
        ORDER BY total_amount DESC
    ";
    
    $outlet_collection_stmt = $conn->prepare($outlet_collection_sql);
    
    if (!empty($start_date) && !empty($end_date)) {
        $outlet_collection_stmt->bind_param("ssss", $selected_branch, $selected_branch, $start_date, $end_date);
    } elseif (!empty($start_date)) {
        $outlet_collection_stmt->bind_param("sss", $selected_branch, $selected_branch, $start_date);
    } elseif (!empty($end_date)) {
        $outlet_collection_stmt->bind_param("sss", $selected_branch, $selected_branch, $end_date);
    } else {
        $outlet_collection_stmt->bind_param("ss", $selected_branch, $selected_branch);
    }
    
    $outlet_collection_stmt->execute();
    $outlet_collection_result = $outlet_collection_stmt->get_result();
}

// Recovery person-wise collection report
if ($selected_branch == 'ALL' && empty($start_date) && empty($end_date)) {
    $recovery_collection_stmt = $conn->prepare("
        SELECT 
            u.username as recovery_person,
            COUNT(rc.id) as total_collections,
            SUM(CASE WHEN rc.approval_status = 'Pending' THEN 1 ELSE 0 END) as pending_approvals,
            SUM(CASE WHEN rc.approval_status = 'Approved' THEN 1 ELSE 0 END) as approved_collections,
            SUM(rc.amount) as total_collected
        FROM users u
        LEFT JOIN recovery_collections rc ON u.user_id = rc.recovery_person_id
        WHERE u.role = 'RecoveryOfficer'
        GROUP BY u.id, u.username
        ORDER BY total_collected DESC
    ");
    $recovery_collection_stmt->execute();
    $recovery_collection_result = $recovery_collection_stmt->get_result();
} else {
    $recovery_collection_sql = "
        SELECT 
            u.username as recovery_person,
            COUNT(rc.id) as total_collections,
            SUM(CASE WHEN rc.approval_status = 'Pending' THEN 1 ELSE 0 END) as pending_approvals,
            SUM(CASE WHEN rc.approval_status = 'Approved' THEN 1 ELSE 0 END) as approved_collections,
            SUM(rc.amount) as total_collected
        FROM users u
        LEFT JOIN recovery_collections rc ON u.user_id = rc.recovery_person_id
        WHERE u.role = 'RecoveryOfficer'
        AND (? = 'ALL' OR rc.outlet_id = ?)
        " . $date_filter . "
        GROUP BY u.id, u.username
        ORDER BY total_collected DESC
    ";
    
    $recovery_collection_stmt = $conn->prepare($recovery_collection_sql);
    
    if (!empty($start_date) && !empty($end_date)) {
        $recovery_collection_stmt->bind_param("ssss", $selected_branch, $selected_branch, $start_date, $end_date);
    } elseif (!empty($start_date)) {
        $recovery_collection_stmt->bind_param("sss", $selected_branch, $selected_branch, $start_date);
    } elseif (!empty($end_date)) {
        $recovery_collection_stmt->bind_param("sss", $selected_branch, $selected_branch, $end_date);
    } else {
        $recovery_collection_stmt->bind_param("ss", $selected_branch, $selected_branch);
    }
    
    $recovery_collection_stmt->execute();
    $recovery_collection_result = $recovery_collection_stmt->get_result();
}

// Overdue leases report
if ($selected_branch == 'ALL') {
    $overdue_leases_stmt = $conn->prepare("
        SELECT 
            l.lease_id,
            c.full_name as customer_id,
            p.product_name,
            l.outstanding_amount,
            l.late_fee,
            DATEDIFF(CURDATE(), l.end_date) as overdue_days,
            u.username as recovery_person,
            o.outlet_name
        FROM leases l
        JOIN products p ON l.product_id = p.product_id
        JOIN users u ON l.recovery_person_id = u.user_id
        JOIN outlets o ON l.outlet_id = o.outlet_id
        JOIN clients c ON l.client_id = c.client_id
        WHERE l.status = 'Overdue'
        ORDER BY l.outstanding_amount DESC
    ");
    $overdue_leases_stmt->execute();
    $overdue_leases_result = $overdue_leases_stmt->get_result();
} else {
    $overdue_leases_stmt = $conn->prepare("
        SELECT 
            l.lease_id,
            c.full_name as customer_id,
            p.product_name,
            l.outstanding_amount,
            l.late_fee,
            DATEDIFF(CURDATE(), l.end_date) as overdue_days,
            u.username as recovery_person,
            o.outlet_name
        FROM leases l
        JOIN products p ON l.product_id = p.product_id
        JOIN users u ON l.recovery_person_id = u.user_id
        JOIN outlets o ON l.outlet_id = o.outlet_id
        JOIN clients c ON l.client_id = c.client_id
        WHERE l.status = 'Overdue'
        AND l.outlet_id = ?
        ORDER BY l.outstanding_amount DESC
    ");
    $overdue_leases_stmt->bind_param("s", $selected_branch);
    $overdue_leases_stmt->execute();
    $overdue_leases_result = $overdue_leases_stmt->get_result();
}

// Don't close the connection here because branch_filter.php needs it
// The connection will be closed at the end of the script
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Lease Management System</title>
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
                <h1 class="h3 mb-1"><i class="bi bi-bar-chart me-2"></i>Reports</h1>
                <p class="mb-0 text-muted">Generate and view system reports</p>
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

                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1><i class="bi bi-bar-chart me-3"></i>Reports</h1>
                            <p class="mb-0">Generate and view system reports</p>
                        </div>
                    </div>
                </div>

                <!-- Breadcrumb Navigation -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Reports</li>
                    </ol>
                </nav>

                <!-- Branch Filter -->
                <form method="GET" id="reportFilterForm" class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-funnel me-2"></i>Report Filters</h5>
                    </div>
                    <div class="card-body">
                        <?php include 'includes/branch_filter.php'; ?>
                    </div>
                </form>
                
                <!-- Report Navigation Tabs -->
                <ul class="nav nav-tabs mb-4" id="reportTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="outlet-tab" data-bs-toggle="tab" data-bs-target="#outlet" type="button" role="tab" aria-controls="outlet" aria-selected="true">
                            <i class="bi bi-shop me-1"></i>Outlet-wise Collection
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="recovery-tab" data-bs-toggle="tab" data-bs-target="#recovery" type="button" role="tab" aria-controls="recovery" aria-selected="false">
                            <i class="bi bi-person-badge me-1"></i>Recovery Person-wise
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="overdue-tab" data-bs-toggle="tab" data-bs-target="#overdue" type="button" role="tab" aria-controls="overdue" aria-selected="false">
                            <i class="bi bi-exclamation-triangle me-1"></i>Overdue Leases
                        </button>
                    </li>
                </ul>
                
                <!-- Tab Content -->
                <div class="tab-content" id="reportTabsContent">
                    <!-- Outlet-wise Collection Report -->
                    <div class="tab-pane fade show active" id="outlet" role="tabpanel" aria-labelledby="outlet-tab">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="bi bi-shop me-2"></i>Outlet-wise Collection Report</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Outlet</th>
                                                <th>Total Collections</th>
                                                <th>Cash Amount</th>
                                                <th>Bank Amount</th>
                                                <th>Online Amount</th>
                                                <th>Total Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($outlet_collection_result && $outlet_collection_result->num_rows > 0): ?>
                                                <?php while ($row = $outlet_collection_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($row['outlet_name']); ?></td>
                                                        <td><?php echo $row['total_collections']; ?></td>
                                                        <td>$<?php echo number_format($row['cash_amount'], 2); ?></td>
                                                        <td>$<?php echo number_format($row['bank_amount'], 2); ?></td>
                                                        <td>$<?php echo number_format($row['online_amount'], 2); ?></td>
                                                        <td><strong>$<?php echo number_format($row['total_amount'], 2); ?></strong></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">No data available.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recovery Person-wise Collection Report -->
                    <div class="tab-pane fade" id="recovery" role="tabpanel" aria-labelledby="recovery-tab">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="bi bi-person-badge me-2"></i>Recovery Person-wise Collection Report</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Recovery Person</th>
                                                <th>Total Collections</th>
                                                <th>Pending Approvals</th>
                                                <th>Approved Collections</th>
                                                <th>Total Collected</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($recovery_collection_result && $recovery_collection_result->num_rows > 0): ?>
                                                <?php while ($row = $recovery_collection_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($row['recovery_person']); ?></td>
                                                        <td><?php echo $row['total_collections']; ?></td>
                                                        <td><?php echo $row['pending_approvals']; ?></td>
                                                        <td><?php echo $row['approved_collections']; ?></td>
                                                        <td><strong>$<?php echo number_format($row['total_collected'], 2); ?></strong></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No data available.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Overdue Leases Report -->
                    <div class="tab-pane fade" id="overdue" role="tabpanel" aria-labelledby="overdue-tab">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="bi bi-exclamation-triangle me-2"></i>Overdue Leases Report</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Lease ID</th>
                                                <th>Customer</th>
                                                <th>Product</th>
                                                <th>Outstanding Amount</th>
                                                <th>Late Fee</th>
                                                <th>Overdue Days</th>
                                                <th>Recovery Person</th>
                                                <th>Outlet</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($overdue_leases_result && $overdue_leases_result->num_rows > 0): ?>
                                                <?php while ($row = $overdue_leases_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($row['lease_id']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['customer_id']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                                        <td><strong>$<?php echo number_format($row['outstanding_amount'], 2); ?></strong></td>
                                                        <td>$<?php echo number_format($row['late_fee'], 2); ?></td>
                                                        <td><span class="badge bg-danger"><?php echo $row['overdue_days']; ?> days</span></td>
                                                        <td><?php echo htmlspecialchars($row['recovery_person']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['outlet_name']); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">No overdue leases found.</td>
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
        </div>
    </div>

    <footer class="bg-light text-center py-4">
        <div class="container">
            <p class="mb-0">&copy; 2025 Lease Management System. All rights reserved.</p>
            <p class="mb-0 text-muted small">Version 1.0.0</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Initialize Select2 on the branch filter dropdown
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2 on the branch filter dropdown
            $('#branchFilter').select2({
                placeholder: "Select a branch",
                allowClear: true,
                width: '100%'
            });
            
            // Apply filters when the Apply Filters button is clicked
            $('#applyFilter').click(function() {
                $('#reportFilterForm').submit();
            });
        });
    </script>
    
    <?php
    // Close the database connection at the very end
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
    ?>
</body>
</html>