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
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build filter conditions
$where_conditions = [];
$params = [];
$types = "";

if (!empty($start_date)) {
    $where_conditions[] = "l.start_date >= ?";
    $params[] = $start_date;
    $types .= "s";
}

if (!empty($end_date)) {
    $where_conditions[] = "l.start_date <= ?";
    $params[] = $end_date;
    $types .= "s";
}

// Build WHERE clause
$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Fetch consolidated data across all outlets
$sql = "
    SELECT 
        o.outlet_name,
        o.outlet_id,
        COUNT(l.id) as total_leases,
        SUM(CASE WHEN l.status = 'Active' THEN 1 ELSE 0 END) as active_leases,
        SUM(CASE WHEN l.status = 'Overdue' THEN 1 ELSE 0 END) as overdue_leases,
        SUM(CASE WHEN l.status = 'Closed' THEN 1 ELSE 0 END) as closed_leases,
        SUM(l.total_amount) as total_lease_value,
        SUM(l.outstanding_amount) as total_outstanding,
        SUM(l.late_fee) as total_late_fees,
        SUM(l.discount) as total_discounts,
        COUNT(DISTINCT c.client_id) as total_clients,
        COUNT(DISTINCT p.product_id) as total_products
    FROM outlets o
    LEFT JOIN leases l ON o.outlet_id = l.outlet_id
    LEFT JOIN clients c ON o.outlet_id = c.outlet_id
    LEFT JOIN products p ON o.outlet_id = p.outlet_id
    $where_clause
    GROUP BY o.outlet_id, o.outlet_name
    ORDER BY total_lease_value DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $consolidated_result = $stmt->get_result();
} else {
    $consolidated_result = $conn->query($sql);
}

// Fetch overall totals
$totals_sql = "
    SELECT 
        COUNT(l.id) as total_leases,
        SUM(CASE WHEN l.status = 'Active' THEN 1 ELSE 0 END) as active_leases,
        SUM(CASE WHEN l.status = 'Overdue' THEN 1 ELSE 0 END) as overdue_leases,
        SUM(CASE WHEN l.status = 'Closed' THEN 1 ELSE 0 END) as closed_leases,
        SUM(l.total_amount) as total_lease_value,
        SUM(l.outstanding_amount) as total_outstanding,
        SUM(l.late_fee) as total_late_fees,
        SUM(l.discount) as total_discounts,
        COUNT(DISTINCT c.client_id) as total_clients,
        COUNT(DISTINCT p.product_id) as total_products
    FROM leases l
    LEFT JOIN clients c ON l.client_id = c.client_id
    LEFT JOIN products p ON l.product_id = p.product_id
    $where_clause
";

if (!empty($params)) {
    $stmt = $conn->prepare($totals_sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $totals_result = $stmt->get_result();
    $totals = $totals_result->fetch_assoc();
} else {
    $totals_result = $conn->query($totals_sql);
    $totals = $totals_result->fetch_assoc();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multi-Outlet Consolidated Report - Lease Management System</title>
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
                            <h1><i class="bi bi-diagram-3 me-3"></i>Multi-Outlet Consolidated Report</h1>
                            <p class="mb-0">Consolidated performance across all outlets</p>
                        </div>
                    </div>
                </div>

                <!-- Breadcrumb Navigation -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="reports.php">Reports</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Multi-Outlet Consolidated</li>
                    </ol>
                </nav>

                <!-- Filter Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-funnel me-2"></i>Filter Report</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-search me-1"></i>Filter
                                </button>
                                <button type="button" class="btn btn-success" onclick="exportToExcel()">
                                    <i class="bi bi-file-earmark-excel me-1"></i>Export to Excel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Consolidated Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5 class="card-title">Total Leases</h5>
                                <p class="card-text display-6"><?php echo $totals['total_leases'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5 class="card-title">Active Leases</h5>
                                <p class="card-text display-6"><?php echo $totals['active_leases'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <h5 class="card-title">Overdue Leases</h5>
                                <p class="card-text display-6"><?php echo $totals['overdue_leases'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h5 class="card-title">Total Lease Value</h5>
                                <p class="card-text display-6">$<?php echo number_format($totals['total_lease_value'] ?? 0, 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Consolidated Table -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-table me-2"></i>Outlet-wise Consolidated Report</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="consolidatedTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Outlet</th>
                                        <th>Total Leases</th>
                                        <th>Active Leases</th>
                                        <th>Overdue Leases</th>
                                        <th>Closed Leases</th>
                                        <th>Total Lease Value</th>
                                        <th>Total Outstanding</th>
                                        <th>Total Late Fees</th>
                                        <th>Total Discounts</th>
                                        <th>Total Clients</th>
                                        <th>Total Products</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($consolidated_result && $consolidated_result->num_rows > 0): ?>
                                        <?php while ($outlet = $consolidated_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($outlet['outlet_name']); ?></td>
                                                <td><?php echo $outlet['total_leases']; ?></td>
                                                <td><?php echo $outlet['active_leases']; ?></td>
                                                <td><?php echo $outlet['overdue_leases']; ?></td>
                                                <td><?php echo $outlet['closed_leases']; ?></td>
                                                <td>$<?php echo number_format($outlet['total_lease_value'], 2); ?></td>
                                                <td>$<?php echo number_format($outlet['total_outstanding'], 2); ?></td>
                                                <td>$<?php echo number_format($outlet['total_late_fees'], 2); ?></td>
                                                <td>$<?php echo number_format($outlet['total_discounts'], 2); ?></td>
                                                <td><?php echo $outlet['total_clients']; ?></td>
                                                <td><?php echo $outlet['total_products']; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="11" class="text-center">No consolidated data found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot class="table-dark">
                                    <tr>
                                        <th>Totals</th>
                                        <th><?php echo $totals['total_leases'] ?? 0; ?></th>
                                        <th><?php echo $totals['active_leases'] ?? 0; ?></th>
                                        <th><?php echo $totals['overdue_leases'] ?? 0; ?></th>
                                        <th><?php echo $totals['closed_leases'] ?? 0; ?></th>
                                        <th>$<?php echo number_format($totals['total_lease_value'] ?? 0, 2); ?></th>
                                        <th>$<?php echo number_format($totals['total_outstanding'] ?? 0, 2); ?></th>
                                        <th>$<?php echo number_format($totals['total_late_fees'] ?? 0, 2); ?></th>
                                        <th>$<?php echo number_format($totals['total_discounts'] ?? 0, 2); ?></th>
                                        <th><?php echo $totals['total_clients'] ?? 0; ?></th>
                                        <th><?php echo $totals['total_products'] ?? 0; ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function exportToExcel() {
            // Simple export to Excel functionality
            const table = document.getElementById('consolidatedTable');
            const rows = Array.from(table.rows);
            
            // Create CSV content
            let csvContent = "";
            rows.forEach(row => {
                const cols = Array.from(row.cells);
                const rowData = cols.map(cell => `"${cell.textContent.trim()}"`).join(",");
                csvContent += rowData + "\n";
            });
            
            // Create download link
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.setAttribute("href", url);
            link.setAttribute("download", "multi_outlet_consolidated_report_<?php echo date('Y-m-d'); ?>.csv");
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>