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
$area_filter = isset($_GET['area_filter']) ? $_GET['area_filter'] : '';
$road_filter = isset($_GET['road_filter']) ? $_GET['road_filter'] : '';
$recovery_person_filter = isset($_GET['recovery_person_filter']) ? $_GET['recovery_person_filter'] : 'ALL';

// Build filter conditions
$where_conditions = [];
$params = [];
$types = "";

if ($selected_branch != 'ALL') {
    $where_conditions[] = "l.outlet_id = ?";
    $params[] = $selected_branch;
    $types .= "s";
}

if (!empty($area_filter)) {
    $where_conditions[] = "c.area = ?";
    $params[] = $area_filter;
    $types .= "s";
}

if (!empty($road_filter)) {
    $where_conditions[] = "c.road = ?";
    $params[] = $road_filter;
    $types .= "s";
}

if ($recovery_person_filter != 'ALL') {
    $where_conditions[] = "l.recovery_person_id = ?";
    $params[] = $recovery_person_filter;
    $types .= "s";
}

// Add condition for overdue leases
$where_conditions[] = "l.status = 'Overdue'";
$params[] = 'Overdue';
$types .= "s";

// Build WHERE clause
$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Fetch outstanding and overdue leases data
$sql = "
    SELECT 
        l.lease_id,
        l.start_date,
        l.end_date,
        l.total_amount,
        l.outstanding_amount,
        l.late_fee,
        DATEDIFF(CURDATE(), l.end_date) as overdue_days,
        c.full_name as client_name,
        c.cnic as client_cnic,
        c.area,
        c.road,
        c.mobile_primary,
        p.product_name,
        u.username as recovery_person,
        o.outlet_name
    FROM leases l
    JOIN clients c ON l.client_id = c.client_id
    JOIN products p ON l.product_id = p.product_id
    JOIN users u ON l.recovery_person_id = u.user_id
    JOIN outlets o ON l.outlet_id = o.outlet_id
    $where_clause
    ORDER BY l.end_date ASC
";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $leases_result = $stmt->get_result();
} else {
    $leases_result = $conn->query($sql);
}

// Fetch outlets for filter dropdown
$outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets ORDER BY outlet_name");

// Fetch areas for filter dropdown
$areas_result = $conn->query("SELECT DISTINCT area FROM clients WHERE area IS NOT NULL ORDER BY area");

// Fetch roads for filter dropdown
$roads_result = $conn->query("SELECT DISTINCT road FROM clients WHERE road IS NOT NULL ORDER BY road");

// Fetch recovery persons for filter dropdown
$recovery_persons_result = $conn->query("SELECT user_id, username FROM users WHERE role='RecoveryOfficer' AND is_active=1 ORDER BY username");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outstanding & Overdue Report - Lease Management System</title>
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
                            <h1><i class="bi bi-exclamation-triangle me-3"></i>Outstanding & Overdue Report</h1>
                            <p class="mb-0">List of overdue lease agreements requiring immediate attention</p>
                        </div>
                    </div>
                </div>

                <!-- Breadcrumb Navigation -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="reports.php">Reports</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Outstanding & Overdue</li>
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
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="branch_filter" class="form-label">Outlet</label>
                                        <select class="form-select" id="branch_filter" name="branch_filter">
                                            <option value="ALL" <?php echo ($selected_branch == 'ALL') ? 'selected' : ''; ?>>All Outlets</option>
                                            <?php if ($outlets_result && $outlets_result->num_rows > 0): ?>
                                                <?php while ($outlet = $outlets_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $outlet['outlet_id']; ?>" <?php echo ($selected_branch == $outlet['outlet_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($outlet['outlet_name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="area_filter" class="form-label">Area</label>
                                        <select class="form-select" id="area_filter" name="area_filter">
                                            <option value="">All Areas</option>
                                            <?php if ($areas_result && $areas_result->num_rows > 0): ?>
                                                <?php while ($area = $areas_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $area['area']; ?>" <?php echo ($area_filter == $area['area']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($area['area']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="road_filter" class="form-label">Road</label>
                                        <select class="form-select" id="road_filter" name="road_filter">
                                            <option value="">All Roads</option>
                                            <?php if ($roads_result && $roads_result->num_rows > 0): ?>
                                                <?php while ($road = $roads_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $road['road']; ?>" <?php echo ($road_filter == $road['road']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($road['road']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="recovery_person_filter" class="form-label">Recovery Person</label>
                                        <select class="form-select" id="recovery_person_filter" name="recovery_person_filter">
                                            <option value="ALL" <?php echo ($recovery_person_filter == 'ALL') ? 'selected' : ''; ?>>All Recovery Persons</option>
                                            <?php if ($recovery_persons_result && $recovery_persons_result->num_rows > 0): ?>
                                                <?php while ($person = $recovery_persons_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $person['user_id']; ?>" <?php echo ($recovery_person_filter == $person['user_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($person['username']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
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

                <!-- Outstanding & Overdue Table -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-table me-2"></i>Outstanding & Overdue Leases</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="overdueTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Lease ID</th>
                                        <th>Client</th>
                                        <th>Client CNIC</th>
                                        <th>Product</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Total Amount</th>
                                        <th>Outstanding</th>
                                        <th>Late Fee</th>
                                        <th>Overdue Days</th>
                                        <th>Area</th>
                                        <th>Road</th>
                                        <th>Mobile</th>
                                        <th>Recovery Person</th>
                                        <th>Outlet</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($leases_result && $leases_result->num_rows > 0): ?>
                                        <?php while ($lease = $leases_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($lease['lease_id']); ?></td>
                                                <td><?php echo htmlspecialchars($lease['client_name']); ?></td>
                                                <td><?php echo htmlspecialchars($lease['client_cnic']); ?></td>
                                                <td><?php echo htmlspecialchars($lease['product_name']); ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($lease['start_date'])); ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($lease['end_date'])); ?></td>
                                                <td>$<?php echo number_format($lease['total_amount'], 2); ?></td>
                                                <td>$<?php echo number_format($lease['outstanding_amount'], 2); ?></td>
                                                <td>$<?php echo number_format($lease['late_fee'], 2); ?></td>
                                                <td><?php echo $lease['overdue_days']; ?> days</td>
                                                <td><?php echo htmlspecialchars($lease['area']); ?></td>
                                                <td><?php echo htmlspecialchars($lease['road']); ?></td>
                                                <td><?php echo htmlspecialchars($lease['mobile_primary']); ?></td>
                                                <td><?php echo htmlspecialchars($lease['recovery_person']); ?></td>
                                                <td><?php echo htmlspecialchars($lease['outlet_name']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="15" class="text-center">No overdue lease records found.</td>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        function exportToExcel() {
            // Simple export to Excel functionality
            const table = document.getElementById('overdueTable');
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
            link.setAttribute("download", "outstanding_overdue_report_<?php echo date('Y-m-d'); ?>.csv");
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>