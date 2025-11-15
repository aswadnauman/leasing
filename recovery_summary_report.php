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
$recovery_person_filter = isset($_GET['recovery_person_filter']) ? $_GET['recovery_person_filter'] : 'ALL';

// Build filter conditions for collections
$where_conditions = [];
$params = [];
$types = "";

if ($selected_branch != 'ALL') {
    $where_conditions[] = "rc.outlet_id = ?";
    $params[] = $selected_branch;
    $types .= "s";
}

if (!empty($start_date)) {
    $where_conditions[] = "DATE(rc.collection_date) >= ?";
    $params[] = $start_date;
    $types .= "s";
}

if (!empty($end_date)) {
    $where_conditions[] = "DATE(rc.collection_date) <= ?";
    $params[] = $end_date;
    $types .= "s";
}

if ($recovery_person_filter != 'ALL') {
    $where_conditions[] = "rc.recovery_person_id = ?";
    $params[] = $recovery_person_filter;
    $types .= "s";
}

// Build WHERE clause
$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Fetch recovery summary data
$sql = "
    SELECT 
        u.username as recovery_person,
        u.user_id,
        o.outlet_name,
        COUNT(rc.id) as total_collections,
        SUM(CASE WHEN rc.collection_type = 'Cash' THEN 1 ELSE 0 END) as cash_collections,
        SUM(CASE WHEN rc.collection_type = 'BankTransfer' THEN 1 ELSE 0 END) as bank_collections,
        SUM(CASE WHEN rc.collection_type = 'Online' THEN 1 ELSE 0 END) as online_collections,
        SUM(CASE WHEN rc.collection_type = 'Cheque' THEN 1 ELSE 0 END) as cheque_collections,
        SUM(CASE WHEN rc.collection_type = 'Cash' THEN rc.amount ELSE 0 END) as cash_amount,
        SUM(CASE WHEN rc.collection_type = 'BankTransfer' THEN rc.amount ELSE 0 END) as bank_amount,
        SUM(CASE WHEN rc.collection_type = 'Online' THEN rc.amount ELSE 0 END) as online_amount,
        SUM(CASE WHEN rc.collection_type = 'Cheque' THEN rc.amount ELSE 0 END) as cheque_amount,
        SUM(rc.amount) as total_amount,
        AVG(rc.amount) as average_amount
    FROM recovery_collections rc
    JOIN users u ON rc.recovery_person_id = u.user_id
    JOIN outlets o ON rc.outlet_id = o.outlet_id
    $where_clause
    GROUP BY u.user_id, u.username, o.outlet_name
    ORDER BY total_amount DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $collections_result = $stmt->get_result();
} else {
    $collections_result = $conn->query($sql);
}

// Fetch outlets for filter dropdown
$outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets ORDER BY outlet_name");

// Fetch recovery persons for filter dropdown
$recovery_persons_result = $conn->query("SELECT user_id, username FROM users WHERE role='RecoveryOfficer' AND is_active=1 ORDER BY username");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recovery Summary Report - Lease Management System</title>
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
                            <h1><i class="bi bi-person-lines-fill me-3"></i>Recovery Summary Report</h1>
                            <p class="mb-0">Summary of collections by recovery personnel</p>
                        </div>
                    </div>
                </div>

                <!-- Breadcrumb Navigation -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="reports.php">Reports</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Recovery Summary</li>
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
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
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

                <!-- Recovery Summary Table -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-table me-2"></i>Recovery Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="recoverySummaryTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Recovery Person</th>
                                        <th>Outlet</th>
                                        <th>Total Collections</th>
                                        <th>Cash Collections</th>
                                        <th>Bank Collections</th>
                                        <th>Online Collections</th>
                                        <th>Cheque Collections</th>
                                        <th>Cash Amount</th>
                                        <th>Bank Amount</th>
                                        <th>Online Amount</th>
                                        <th>Cheque Amount</th>
                                        <th>Total Amount</th>
                                        <th>Average Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($collections_result && $collections_result->num_rows > 0): ?>
                                        <?php while ($collection = $collections_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($collection['recovery_person']); ?></td>
                                                <td><?php echo htmlspecialchars($collection['outlet_name']); ?></td>
                                                <td><?php echo $collection['total_collections']; ?></td>
                                                <td><?php echo $collection['cash_collections']; ?></td>
                                                <td><?php echo $collection['bank_collections']; ?></td>
                                                <td><?php echo $collection['online_collections']; ?></td>
                                                <td><?php echo $collection['cheque_collections']; ?></td>
                                                <td>$<?php echo number_format($collection['cash_amount'], 2); ?></td>
                                                <td>$<?php echo number_format($collection['bank_amount'], 2); ?></td>
                                                <td>$<?php echo number_format($collection['online_amount'], 2); ?></td>
                                                <td>$<?php echo number_format($collection['cheque_amount'], 2); ?></td>
                                                <td>$<?php echo number_format($collection['total_amount'], 2); ?></td>
                                                <td>$<?php echo number_format($collection['average_amount'], 2); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="13" class="text-center">No recovery collection records found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot class="table-dark">
                                    <?php
                                    // Calculate totals
                                    $total_collections = 0;
                                    $cash_collections = 0;
                                    $bank_collections = 0;
                                    $online_collections = 0;
                                    $cheque_collections = 0;
                                    $cash_amount = 0;
                                    $bank_amount = 0;
                                    $online_amount = 0;
                                    $cheque_amount = 0;
                                    $total_amount = 0;
                                    
                                    // Reset result pointer to calculate totals
                                    if ($collections_result && $collections_result->num_rows > 0) {
                                        mysqli_data_seek($collections_result, 0);
                                        while ($collection = $collections_result->fetch_assoc()) {
                                            $total_collections += $collection['total_collections'];
                                            $cash_collections += $collection['cash_collections'];
                                            $bank_collections += $collection['bank_collections'];
                                            $online_collections += $collection['online_collections'];
                                            $cheque_collections += $collection['cheque_collections'];
                                            $cash_amount += $collection['cash_amount'];
                                            $bank_amount += $collection['bank_amount'];
                                            $online_amount += $collection['online_amount'];
                                            $cheque_amount += $collection['cheque_amount'];
                                            $total_amount += $collection['total_amount'];
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <th colspan="2">Totals</th>
                                        <th><?php echo $total_collections; ?></th>
                                        <th><?php echo $cash_collections; ?></th>
                                        <th><?php echo $bank_collections; ?></th>
                                        <th><?php echo $online_collections; ?></th>
                                        <th><?php echo $cheque_collections; ?></th>
                                        <th>$<?php echo number_format($cash_amount, 2); ?></th>
                                        <th>$<?php echo number_format($bank_amount, 2); ?></th>
                                        <th>$<?php echo number_format($online_amount, 2); ?></th>
                                        <th>$<?php echo number_format($cheque_amount, 2); ?></th>
                                        <th>$<?php echo number_format($total_amount, 2); ?></th>
                                        <th>$<?php echo $total_collections > 0 ? number_format($total_amount / $total_collections, 2) : '0.00'; ?></th>
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
            const table = document.getElementById('recoverySummaryTable');
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
            link.setAttribute("download", "recovery_summary_report_<?php echo date('Y-m-d'); ?>.csv");
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>