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
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Build date filter condition
$date_filter_leases = "";
$date_filter_collections = "";
if (!empty($start_date) && !empty($end_date)) {
    $date_filter_leases = " AND l.start_date BETWEEN ? AND ?";
    $date_filter_collections = " AND rc.collection_date BETWEEN ? AND ?";
}

// Fetch branch performance data
if ($selected_branch == 'ALL') {
    // Get total leases and lease value by branch
    $leases_stmt = $conn->prepare("
        SELECT 
            o.outlet_name,
            COUNT(l.id) as total_leases,
            SUM(l.total_amount) as total_lease_value
        FROM outlets o
        LEFT JOIN leases l ON o.outlet_id = l.outlet_id
        WHERE l.start_date BETWEEN ? AND ?
        GROUP BY o.id, o.outlet_name
        ORDER BY total_lease_value DESC
    ");
    $leases_stmt->bind_param("ss", $start_date, $end_date);
    $leases_stmt->execute();
    $leases_result = $leases_stmt->get_result();
    
    // Get total collections by branch
    $collections_stmt = $conn->prepare("
        SELECT 
            o.outlet_name,
            SUM(rc.amount) as total_collections
        FROM outlets o
        LEFT JOIN recovery_collections rc ON o.outlet_id = rc.outlet_id
        WHERE rc.collection_date BETWEEN ? AND ?
        GROUP BY o.id, o.outlet_name
        ORDER BY total_collections DESC
    ");
    $collections_stmt->bind_param("ss", $start_date, $end_date);
    $collections_stmt->execute();
    $collections_result = $collections_stmt->get_result();
    
    // Get overdue amounts by branch
    $overdue_stmt = $conn->prepare("
        SELECT 
            o.outlet_name,
            COUNT(l.id) as overdue_count,
            SUM(l.outstanding_amount) as total_overdue
        FROM outlets o
        LEFT JOIN leases l ON o.outlet_id = l.outlet_id
        WHERE l.status = 'Overdue'
        GROUP BY o.id, o.outlet_name
        ORDER BY total_overdue DESC
    ");
    $overdue_stmt->execute();
    $overdue_result = $overdue_stmt->get_result();
} else {
    // Get total leases and lease value for selected branch
    $leases_stmt = $conn->prepare("
        SELECT 
            o.outlet_name,
            COUNT(l.id) as total_leases,
            SUM(l.total_amount) as total_lease_value
        FROM outlets o
        LEFT JOIN leases l ON o.outlet_id = l.outlet_id
        WHERE o.outlet_id = ?
        AND l.start_date BETWEEN ? AND ?
        GROUP BY o.id, o.outlet_name
    ");
    $leases_stmt->bind_param("sss", $selected_branch, $start_date, $end_date);
    $leases_stmt->execute();
    $leases_result = $leases_stmt->get_result();
    
    // Get total collections for selected branch
    $collections_stmt = $conn->prepare("
        SELECT 
            o.outlet_name,
            SUM(rc.amount) as total_collections
        FROM outlets o
        LEFT JOIN recovery_collections rc ON o.outlet_id = rc.outlet_id
        WHERE o.outlet_id = ?
        AND rc.collection_date BETWEEN ? AND ?
        GROUP BY o.id, o.outlet_name
    ");
    $collections_stmt->bind_param("sss", $selected_branch, $start_date, $end_date);
    $collections_stmt->execute();
    $collections_result = $collections_stmt->get_result();
    
    // Get overdue amounts for selected branch
    $overdue_stmt = $conn->prepare("
        SELECT 
            o.outlet_name,
            COUNT(l.id) as overdue_count,
            SUM(l.outstanding_amount) as total_overdue
        FROM outlets o
        LEFT JOIN leases l ON o.outlet_id = l.outlet_id
        WHERE o.outlet_id = ?
        AND l.status = 'Overdue'
        GROUP BY o.id, o.outlet_name
    ");
    $overdue_stmt->bind_param("s", $selected_branch);
    $overdue_stmt->execute();
    $overdue_result = $overdue_stmt->get_result();
}

// Prepare data for charts
$branch_names = [];
$lease_values = [];
$collection_values = [];
$overdue_values = [];

// Collect lease data
while ($row = $leases_result->fetch_assoc()) {
    $branch_names[] = $row['outlet_name'];
    $lease_values[] = (float)$row['total_lease_value'];
}

// Collect collection data
$collections_data = [];
while ($row = $collections_result->fetch_assoc()) {
    $collections_data[$row['outlet_name']] = (float)$row['total_collections'];
}

// Collect overdue data
$overdue_data = [];
while ($row = $overdue_result->fetch_assoc()) {
    $overdue_data[$row['outlet_name']] = (float)$row['total_overdue'];
}

// Combine all data
$chart_data = [];
foreach ($branch_names as $index => $branch_name) {
    $chart_data[] = [
        'branch' => $branch_name,
        'leases' => $lease_values[$index],
        'collections' => $collections_data[$branch_name] ?? 0,
        'overdue' => $overdue_data[$branch_name] ?? 0
    ];
}

// Don't close the connection here since it's needed by the branch_filter include
// $conn->close();  // Commented out this line
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Analysis - Lease Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Lease Management System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="outlets.php">Outlets</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="clients.php">Clients</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="leases.php">Leases</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="collections.php">Collections</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="branch_analysis.php">Branch Analysis</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>Branch Performance Analysis</h2>
                
                <!-- Branch Filter -->
                <form method="GET" id="analysisFilterForm">
                    <?php include 'includes/branch_filter.php'; ?>
                </form>
                
                <!-- Summary Cards -->
                <div class="row mt-4">
                    <div class="col-md-4 mb-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Leases</h5>
                                <p class="card-text display-6">
                                    <?php 
                                    $total_leases = 0;
                                    foreach ($chart_data as $data) {
                                        $total_leases += $data['leases'];
                                    }
                                    echo number_format($total_leases, 0);
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Collections</h5>
                                <p class="card-text display-6">
                                    $<?php 
                                    $total_collections = 0;
                                    foreach ($chart_data as $data) {
                                        $total_collections += $data['collections'];
                                    }
                                    echo number_format($total_collections, 2);
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Overdue</h5>
                                <p class="card-text display-6">
                                    $<?php 
                                    $total_overdue = 0;
                                    foreach ($chart_data as $data) {
                                        $total_overdue += $data['overdue'];
                                    }
                                    echo number_format($total_overdue, 2);
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Performance Charts -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Branch Performance Comparison</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="performanceChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Detailed Data Table -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Detailed Branch Performance</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Branch</th>
                                                <th>Total Leases</th>
                                                <th>Lease Value</th>
                                                <th>Collections</th>
                                                <th>Overdue Amount</th>
                                                <th>Performance Ratio</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($chart_data as $data): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($data['branch']); ?></td>
                                                    <td><?php echo number_format($data['leases'], 0); ?></td>
                                                    <td>$<?php echo number_format($data['leases'], 2); ?></td>
                                                    <td>$<?php echo number_format($data['collections'], 2); ?></td>
                                                    <td>$<?php echo number_format($data['overdue'], 2); ?></td>
                                                    <td>
                                                        <?php 
                                                        if ($data['leases'] > 0) {
                                                            $ratio = ($data['collections'] / $data['leases']) * 100;
                                                            echo number_format($ratio, 2) . '%';
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($chart_data)): ?>
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
        // Initialize Select2 for branch filter
        document.addEventListener('DOMContentLoaded', function() {
            $('#branchFilter').select2({
                placeholder: "Select a branch",
                allowClear: true
            });
            
            // Apply filter on button click
            document.getElementById('applyFilter').addEventListener('click', function() {
                document.getElementById('analysisFilterForm').submit();
            });
            
            // Apply filter on branch change
            document.getElementById('branchFilter').addEventListener('change', function() {
                document.getElementById('analysisFilterForm').submit();
            });
            
            // Initialize performance chart
            var ctx = document.getElementById('performanceChart').getContext('2d');
            var chartData = <?php echo json_encode($chart_data); ?>;
            
            var branchNames = chartData.map(item => item.branch);
            var leaseValues = chartData.map(item => item.leases);
            var collectionValues = chartData.map(item => item.collections);
            var overdueValues = chartData.map(item => item.overdue);
            
            var performanceChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: branchNames,
                    datasets: [
                        {
                            label: 'Lease Value',
                            data: leaseValues,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Collections',
                            data: collectionValues,
                            backgroundColor: 'rgba(75, 192, 192, 0.6)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Overdue Amount',
                            data: overdueValues,
                            backgroundColor: 'rgba(255, 99, 132, 0.6)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>