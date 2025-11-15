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

// Build filter conditions
$where_conditions = [];
$params = [];
$types = "";

if ($selected_branch != 'ALL') {
    $where_conditions[] = "l.outlet_id = ?";
    $params[] = $selected_branch;
    $types .= "s";
}

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

// Fetch lease income data grouped by month
$sql = "
    SELECT 
        DATE_FORMAT(l.start_date, '%Y-%m') as month,
        o.outlet_name,
        COUNT(l.id) as total_leases,
        SUM(l.total_amount) as total_lease_value,
        SUM(l.outstanding_amount) as total_outstanding,
        SUM(l.late_fee) as total_late_fees,
        SUM(l.discount) as total_discounts,
        AVG(l.total_amount) as average_lease_value
    FROM leases l
    JOIN outlets o ON l.outlet_id = o.outlet_id
    $where_clause
    GROUP BY DATE_FORMAT(l.start_date, '%Y-%m'), o.outlet_id, o.outlet_name
    ORDER BY month DESC, total_lease_value DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $income_result = $stmt->get_result();
} else {
    $income_result = $conn->query($sql);
}

// Fetch outlets for filter dropdown
$outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets ORDER BY outlet_name");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lease Income Report - Lease Management System</title>
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
                            <h1><i class="bi bi-currency-dollar me-3"></i>Lease Income Report</h1>
                            <p class="mb-0">Monthly income analysis by outlet</p>
                        </div>
                    </div>
                </div>

                <!-- Breadcrumb Navigation -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="reports.php">Reports</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Lease Income</li>
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
                                <div class="col-md-4">
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
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
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

                <!-- Lease Income Table -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-table me-2"></i>Lease Income Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="leaseIncomeTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Month</th>
                                        <th>Outlet</th>
                                        <th>Total Leases</th>
                                        <th>Total Lease Value</th>
                                        <th>Total Outstanding</th>
                                        <th>Total Late Fees</th>
                                        <th>Total Discounts</th>
                                        <th>Average Lease Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($income_result && $income_result->num_rows > 0): ?>
                                        <?php while ($income = $income_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($income['month']); ?></td>
                                                <td><?php echo htmlspecialchars($income['outlet_name']); ?></td>
                                                <td><?php echo $income['total_leases']; ?></td>
                                                <td>$<?php echo number_format($income['total_lease_value'], 2); ?></td>
                                                <td>$<?php echo number_format($income['total_outstanding'], 2); ?></td>
                                                <td>$<?php echo number_format($income['total_late_fees'], 2); ?></td>
                                                <td>$<?php echo number_format($income['total_discounts'], 2); ?></td>
                                                <td>$<?php echo number_format($income['average_lease_value'], 2); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No lease income records found.</td>
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
            const table = document.getElementById('leaseIncomeTable');
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
            link.setAttribute("download", "lease_income_report_<?php echo date('Y-m-d'); ?>.csv");
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>