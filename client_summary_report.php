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
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'ALL';
$profession_filter = isset($_GET['profession_filter']) ? $_GET['profession_filter'] : '';

// Build filter conditions
$where_conditions = [];
$params = [];
$types = "";

if ($selected_branch != 'ALL') {
    $where_conditions[] = "c.outlet_id = ?";
    $params[] = $selected_branch;
    $types .= "s";
}

if ($status_filter != 'ALL') {
    $where_conditions[] = "c.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($profession_filter)) {
    $where_conditions[] = "c.profession = ?";
    $params[] = $profession_filter;
    $types .= "s";
}

// Build WHERE clause
$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Fetch client summary data
$sql = "
    SELECT 
        c.client_id,
        c.full_name,
        c.cnic,
        c.mobile_primary,
        c.area,
        c.road,
        c.city,
        c.profession,
        c.status,
        c.outlet_id,
        o.outlet_name,
        COUNT(l.id) as total_leases,
        SUM(CASE WHEN l.status = 'Active' THEN 1 ELSE 0 END) as active_leases,
        SUM(CASE WHEN l.status = 'Overdue' THEN 1 ELSE 0 END) as overdue_leases,
        SUM(CASE WHEN l.status = 'Closed' THEN 1 ELSE 0 END) as closed_leases,
        COALESCE(SUM(l.total_amount), 0) as total_lease_value,
        COALESCE(SUM(l.outstanding_amount), 0) as total_outstanding
    FROM clients c
    LEFT JOIN leases l ON c.client_id = l.client_id
    JOIN outlets o ON c.outlet_id = o.outlet_id
    $where_clause
    GROUP BY c.id, c.client_id, c.full_name, c.cnic, c.mobile_primary, c.area, c.road, c.city, c.profession, c.status, c.outlet_id, o.outlet_name
    ORDER BY c.created_at DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $clients_result = $stmt->get_result();
} else {
    $clients_result = $conn->query($sql);
}

// Fetch outlets for filter dropdown
$outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets ORDER BY outlet_name");

// Fetch professions for filter dropdown
$professions_result = $conn->query("SELECT DISTINCT profession FROM clients WHERE profession IS NOT NULL ORDER BY profession");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Summary Report - Lease Management System</title>
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
                            <h1><i class="bi bi-people me-3"></i>Client Summary Report</h1>
                            <p class="mb-0">Summary of clients and their lease activities</p>
                        </div>
                    </div>
                </div>

                <!-- Breadcrumb Navigation -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="reports.php">Reports</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Client Summary</li>
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
                                        <label for="status_filter" class="form-label">Status</label>
                                        <select class="form-select" id="status_filter" name="status_filter">
                                            <option value="ALL" <?php echo ($status_filter == 'ALL') ? 'selected' : ''; ?>>All Statuses</option>
                                            <option value="Active" <?php echo ($status_filter == 'Active') ? 'selected' : ''; ?>>Active</option>
                                            <option value="Blocked" <?php echo ($status_filter == 'Blocked') ? 'selected' : ''; ?>>Blocked</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="profession_filter" class="form-label">Profession</label>
                                        <select class="form-select" id="profession_filter" name="profession_filter">
                                            <option value="">All Professions</option>
                                            <?php if ($professions_result && $professions_result->num_rows > 0): ?>
                                                <?php while ($profession = $professions_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $profession['profession']; ?>" <?php echo ($profession_filter == $profession['profession']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($profession['profession']); ?>
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

                <!-- Client Summary Table -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-table me-2"></i>Client Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="clientSummaryTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Client ID</th>
                                        <th>Client Name</th>
                                        <th>CNIC</th>
                                        <th>Mobile</th>
                                        <th>Area</th>
                                        <th>Road</th>
                                        <th>City</th>
                                        <th>Profession</th>
                                        <th>Status</th>
                                        <th>Outlet</th>
                                        <th>Total Leases</th>
                                        <th>Active Leases</th>
                                        <th>Overdue Leases</th>
                                        <th>Closed Leases</th>
                                        <th>Total Lease Value</th>
                                        <th>Total Outstanding</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($clients_result && $clients_result->num_rows > 0): ?>
                                        <?php while ($client = $clients_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($client['client_id']); ?></td>
                                                <td><?php echo htmlspecialchars($client['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($client['cnic']); ?></td>
                                                <td><?php echo htmlspecialchars($client['mobile_primary']); ?></td>
                                                <td><?php echo htmlspecialchars($client['area']); ?></td>
                                                <td><?php echo htmlspecialchars($client['road']); ?></td>
                                                <td><?php echo htmlspecialchars($client['city']); ?></td>
                                                <td><?php echo htmlspecialchars($client['profession']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $client['status'] == 'Active' ? 'success' : 'danger'; 
                                                    ?>">
                                                        <?php echo htmlspecialchars($client['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($client['outlet_name']); ?></td>
                                                <td><?php echo $client['total_leases']; ?></td>
                                                <td><?php echo $client['active_leases']; ?></td>
                                                <td><?php echo $client['overdue_leases']; ?></td>
                                                <td><?php echo $client['closed_leases']; ?></td>
                                                <td>$<?php echo number_format($client['total_lease_value'], 2); ?></td>
                                                <td>$<?php echo number_format($client['total_outstanding'], 2); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="16" class="text-center">No client records found.</td>
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
            const table = document.getElementById('clientSummaryTable');
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
            link.setAttribute("download", "client_summary_report_<?php echo date('Y-m-d'); ?>.csv");
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>