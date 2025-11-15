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
$params = [];
$types = "";

if (!empty($start_date) && !empty($end_date)) {
    $date_filter = " AND l.start_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
} elseif (!empty($start_date)) {
    $date_filter = " AND l.start_date >= ?";
    $params[] = $start_date;
    $types .= "s";
} elseif (!empty($end_date)) {
    $date_filter = " AND l.start_date <= ?";
    $params[] = $end_date;
    $types .= "s";
}

// Fetch sales data by branch
if ($selected_branch == 'ALL') {
    $branch_sql = "
        SELECT 
            o.outlet_name,
            COUNT(l.id) as total_leases,
            SUM(l.total_amount) as total_sales,
            AVG(l.total_amount) as avg_sale
        FROM outlets o
        LEFT JOIN leases l ON o.outlet_id = l.outlet_id
        WHERE 1=1
        " . $date_filter . "
        GROUP BY o.id, o.outlet_name
        ORDER BY total_sales DESC
    ";
    
    $branch_stmt = $conn->prepare($branch_sql);
    if (!empty($params)) {
        $branch_stmt->bind_param($types, ...$params);
    }
} else {
    $branch_sql = "
        SELECT 
            o.outlet_name,
            COUNT(l.id) as total_leases,
            SUM(l.total_amount) as total_sales,
            AVG(l.total_amount) as avg_sale
        FROM outlets o
        LEFT JOIN leases l ON o.outlet_id = l.outlet_id
        WHERE o.outlet_id = ?
        " . $date_filter . "
        GROUP BY o.id, o.outlet_name
    ";
    
    $branch_stmt = $conn->prepare($branch_sql);
    $branch_params = array_merge([$selected_branch], $params);
    $branch_types = "s" . $types;
    $branch_stmt->bind_param($branch_types, ...$branch_params);
}

$branch_stmt->execute();
$branch_result = $branch_stmt->get_result();

// Fetch sales data by product category
if ($selected_branch == 'ALL') {
    $category_sql = "
        SELECT 
            p.category,
            COUNT(l.id) as total_leases,
            SUM(l.total_amount) as total_sales,
            AVG(l.total_amount) as avg_sale
        FROM products p
        LEFT JOIN leases l ON p.product_id = l.product_id
        WHERE 1=1
        " . $date_filter . "
        GROUP BY p.category
        ORDER BY total_sales DESC
    ";
    
    $category_stmt = $conn->prepare($category_sql);
    if (!empty($params)) {
        $category_stmt->bind_param($types, ...$params);
    }
} else {
    $category_sql = "
        SELECT 
            p.category,
            COUNT(l.id) as total_leases,
            SUM(l.total_amount) as total_sales,
            AVG(l.total_amount) as avg_sale
        FROM products p
        LEFT JOIN leases l ON p.product_id = l.product_id
        WHERE l.outlet_id = ?
        " . $date_filter . "
        GROUP BY p.category
        ORDER BY total_sales DESC
    ";
    
    $category_stmt = $conn->prepare($category_sql);
    $category_stmt->bind_param($branch_types, ...$branch_params);
}

$category_stmt->execute();
$category_result = $category_stmt->get_result();

// Calculate total sales
$total_sales = 0;
$total_leases = 0;
mysqli_data_seek($branch_result, 0); // Reset result pointer
while ($row = $branch_result->fetch_assoc()) {
    $total_sales += $row['total_sales'];
    $total_leases += $row['total_leases'];
}

// Don't close the connection here since it's needed by the branch_filter include
// $conn->close();  // Commented out this line
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - Lease Management System</title>
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
                        <a class="nav-link" href="branch_analysis.php">Branch Analysis</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="expense_report.php">Expense Report</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="sales_report.php">Sales Report</a>
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
                <h2>Sales Report</h2>
                
                <!-- Branch Filter -->
                <form method="GET" id="salesFilterForm">
                    <?php include 'includes/branch_filter.php'; ?>
                </form>
                
                <!-- Summary Cards -->
                <div class="row mt-4">
                    <div class="col-md-4 mb-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Sales</h5>
                                <p class="card-text display-6">
                                    $<?php echo number_format($total_sales, 2); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Leases</h5>
                                <p class="card-text display-6">
                                    <?php echo number_format($total_leases, 0); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Average Sale</h5>
                                <p class="card-text display-6">
                                    $<?php 
                                    echo ($total_leases > 0) ? number_format($total_sales / $total_leases, 2) : '0.00';
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sales by Branch -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Sales by Branch</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Branch</th>
                                                <th>Total Leases</th>
                                                <th>Total Sales</th>
                                                <th>Average Sale</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            mysqli_data_seek($branch_result, 0); // Reset result pointer
                                            while ($row = $branch_result->fetch_assoc()): 
                                                $percentage = ($total_sales > 0) ? ($row['total_sales'] / $total_sales) * 100 : 0;
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['outlet_name']); ?></td>
                                                    <td><?php echo number_format($row['total_leases'], 0); ?></td>
                                                    <td>$<?php echo number_format($row['total_sales'], 2); ?></td>
                                                    <td>$<?php echo number_format($row['avg_sale'], 2); ?></td>
                                                    <td><?php echo number_format($percentage, 2); ?>%</td>
                                                </tr>
                                            <?php endwhile; ?>
                                            <?php if ($branch_result->num_rows == 0): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No sales data available.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sales by Product Category -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Sales by Product Category</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Product Category</th>
                                                <th>Total Leases</th>
                                                <th>Total Sales</th>
                                                <th>Average Sale</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $category_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                                                    <td><?php echo number_format($row['total_leases'], 0); ?></td>
                                                    <td>$<?php echo number_format($row['total_sales'], 2); ?></td>
                                                    <td>$<?php echo number_format($row['avg_sale'], 2); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                            <?php if ($category_result->num_rows == 0): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No sales data available.</td>
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
                document.getElementById('salesFilterForm').submit();
            });
            
            // Apply filter on branch change
            document.getElementById('branchFilter').addEventListener('change', function() {
                document.getElementById('salesFilterForm').submit();
            });
        });
    </script>
</body>
</html>