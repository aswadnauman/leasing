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
    $date_filter = " AND DATE(rc.collection_date) BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
} elseif (!empty($start_date)) {
    $date_filter = " AND DATE(rc.collection_date) >= ?";
    $params[] = $start_date;
    $types .= "s";
} elseif (!empty($end_date)) {
    $date_filter = " AND DATE(rc.collection_date) <= ?";
    $params[] = $end_date;
    $types .= "s";
}

// Fetch expense data grouped by category
if ($selected_branch == 'ALL') {
    $sql = "
        SELECT 
            rc.expense_category,
            COUNT(rc.id) as transaction_count,
            SUM(rc.amount) as total_amount
        FROM recovery_collections rc
        WHERE 1=1
        " . $date_filter . "
        GROUP BY rc.expense_category
        ORDER BY total_amount DESC
    ";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
} else {
    $sql = "
        SELECT 
            rc.expense_category,
            COUNT(rc.id) as transaction_count,
            SUM(rc.amount) as total_amount
        FROM recovery_collections rc
        WHERE rc.outlet_id = ?
        " . $date_filter . "
        GROUP BY rc.expense_category
        ORDER BY total_amount DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $params = array_merge([$selected_branch], $params);
    $types = "s" . $types;
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$expense_result = $stmt->get_result();

// Fetch expense data grouped by branch and category
if ($selected_branch == 'ALL') {
    $branch_sql = "
        SELECT 
            o.outlet_name,
            rc.expense_category,
            COUNT(rc.id) as transaction_count,
            SUM(rc.amount) as total_amount
        FROM recovery_collections rc
        JOIN outlets o ON rc.outlet_id = o.outlet_id
        WHERE 1=1
        " . $date_filter . "
        GROUP BY o.outlet_id, o.outlet_name, rc.expense_category
        ORDER BY o.outlet_name, total_amount DESC
    ";
    
    $branch_stmt = $conn->prepare($branch_sql);
    if (!empty($params)) {
        // Remove the first parameter (branch) since we're not filtering by branch in this query
        $branch_params = array_slice($params, 1);
        $branch_types = substr($types, 1);
        if (!empty($branch_params)) {
            $branch_stmt->bind_param($branch_types, ...$branch_params);
        }
    }
} else {
    $branch_sql = "
        SELECT 
            o.outlet_name,
            rc.expense_category,
            COUNT(rc.id) as transaction_count,
            SUM(rc.amount) as total_amount
        FROM recovery_collections rc
        JOIN outlets o ON rc.outlet_id = o.outlet_id
        WHERE rc.outlet_id = ?
        " . $date_filter . "
        GROUP BY o.outlet_id, o.outlet_name, rc.expense_category
        ORDER BY total_amount DESC
    ";
    
    $branch_stmt = $conn->prepare($branch_sql);
    $branch_stmt->bind_param($types, ...$params);
}

$branch_stmt->execute();
$branch_expense_result = $branch_stmt->get_result();

// Calculate total expenses
$total_expenses = 0;
while ($row = $expense_result->fetch_assoc()) {
    $total_expenses += $row['total_amount'];
}

// Don't close the connection here since it's needed by the branch_filter include
// $conn->close();  // Commented out this line
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Report - Lease Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
                        <a class="nav-link active" href="expense_report.php">Expense Report</a>
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
                <h2>Expense Report</h2>
                
                <!-- Branch Filter -->
                <form method="GET" id="expenseFilterForm">
                    <?php include 'includes/branch_filter.php'; ?>
                </form>
                
                <!-- Summary Card -->
                <div class="row mt-4">
                    <div class="col-md-12 mb-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Expenses</h5>
                                <p class="card-text display-6">
                                    $<?php echo number_format($total_expenses, 2); ?>
                                </p>
                                <?php if ($selected_branch != 'ALL'): ?>
                                    <p class="card-text">
                                        <?php
                                        // Get outlet name for selected branch
                                        $conn = getDBConnection();
                                        $outlet_stmt = $conn->prepare("SELECT outlet_name FROM outlets WHERE outlet_id = ?");
                                        $outlet_stmt->bind_param("s", $selected_branch);
                                        $outlet_stmt->execute();
                                        $outlet_result = $outlet_stmt->get_result();
                                        if ($outlet = $outlet_result->fetch_assoc()) {
                                            echo "for " . htmlspecialchars($outlet['outlet_name']);
                                        }
                                        $conn->close();
                                        ?>
                                    </p>
                                <?php else: ?>
                                    <p class="card-text">Across all branches</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Expense by Category -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Expenses by Category</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Transaction Count</th>
                                                <th>Total Amount</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            mysqli_data_seek($expense_result, 0); // Reset result pointer
                                            while ($row = $expense_result->fetch_assoc()): 
                                                $percentage = ($total_expenses > 0) ? ($row['total_amount'] / $total_expenses) * 100 : 0;
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['expense_category']); ?></td>
                                                    <td><?php echo $row['transaction_count']; ?></td>
                                                    <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                                                    <td><?php echo number_format($percentage, 2); ?>%</td>
                                                </tr>
                                            <?php endwhile; ?>
                                            <?php if ($expense_result->num_rows == 0): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No expense data available.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Expense by Branch and Category -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Expenses by Branch and Category</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Branch</th>
                                                <th>Category</th>
                                                <th>Transaction Count</th>
                                                <th>Total Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $branch_expense_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['outlet_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['expense_category']); ?></td>
                                                    <td><?php echo $row['transaction_count']; ?></td>
                                                    <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                            <?php if ($branch_expense_result->num_rows == 0): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No expense data available.</td>
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
                document.getElementById('expenseFilterForm').submit();
            });
            
            // Apply filter on branch change
            document.getElementById('branchFilter').addEventListener('change', function() {
                document.getElementById('expenseFilterForm').submit();
            });
        });
    </script>
</body>
</html>