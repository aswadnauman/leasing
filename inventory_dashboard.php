<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check user role - only Admin, BranchManager, and Sales can access inventory
$user_role = $_SESSION['role'];
if ($user_role != 'Admin' && $user_role != 'BranchManager' && $user_role != 'Sales') {
    header("Location: dashboard.php");
    exit();
}

$conn = getDBConnection();

// Get overall inventory summary
$summary_query = "
    SELECT 
        COUNT(DISTINCT p.product_id) as total_products,
        COUNT(DISTINCT CASE WHEN p.status = 'Available' THEN p.product_id END) as available_products,
        COUNT(DISTINCT CASE WHEN p.status = 'Leased' THEN p.product_id END) as leased_products,
        COUNT(DISTINCT CASE WHEN p.status = 'UnderMaintenance' THEN p.product_id END) as maintenance_products,
        COUNT(DISTINCT CASE WHEN p.status = 'Retired' THEN p.product_id END) as retired_products,
        SUM(CASE 
            WHEN p.status = 'Available' THEN (
                COALESCE(SUM(CASE WHEN it.transaction_type = 'Purchase' THEN it.quantity ELSE 0 END), 0) - 
                COALESCE(SUM(CASE WHEN it.transaction_type = 'Sale' THEN it.quantity ELSE 0 END), 0) + 
                COALESCE(SUM(CASE WHEN it.transaction_type = 'PurchaseReturn' THEN it.quantity ELSE 0 END), 0) - 
                COALESCE(SUM(CASE WHEN it.transaction_type = 'SaleReturn' THEN it.quantity ELSE 0 END), 0)
            ) 
            ELSE 0 
        END) as total_available_stock,
        SUM(p.purchase_price * (
            COALESCE(SUM(CASE WHEN it.transaction_type = 'Purchase' THEN it.quantity ELSE 0 END), 0) - 
            COALESCE(SUM(CASE WHEN it.transaction_type = 'Sale' THEN it.quantity ELSE 0 END), 0) + 
            COALESCE(SUM(CASE WHEN it.transaction_type = 'PurchaseReturn' THEN it.quantity ELSE 0 END), 0) - 
            COALESCE(SUM(CASE WHEN it.transaction_type = 'SaleReturn' THEN it.quantity ELSE 0 END), 0)
        )) as total_inventory_value
    FROM products p
    LEFT JOIN inventory_transactions it ON p.product_id = it.product_id
";

$summary_result = $conn->query($summary_query);
$summary = $summary_result->fetch_assoc();

// Get outlet-wise inventory summary
$outlet_summary_query = "
    SELECT 
        o.outlet_name,
        COUNT(p.product_id) as total_products,
        COUNT(CASE WHEN p.status = 'Available' THEN 1 END) as available_products,
        COUNT(CASE WHEN p.status = 'Leased' THEN 1 END) as leased_products,
        SUM(CASE 
            WHEN p.status = 'Available' THEN (
                COALESCE(SUM(CASE WHEN it.transaction_type = 'Purchase' THEN it.quantity ELSE 0 END), 0) - 
                COALESCE(SUM(CASE WHEN it.transaction_type = 'Sale' THEN it.quantity ELSE 0 END), 0) + 
                COALESCE(SUM(CASE WHEN it.transaction_type = 'PurchaseReturn' THEN it.quantity ELSE 0 END), 0) - 
                COALESCE(SUM(CASE WHEN it.transaction_type = 'SaleReturn' THEN it.quantity ELSE 0 END), 0)
            ) 
            ELSE 0 
        END) as available_stock,
        SUM(p.purchase_price * (
            COALESCE(SUM(CASE WHEN it.transaction_type = 'Purchase' THEN it.quantity ELSE 0 END), 0) - 
            COALESCE(SUM(CASE WHEN it.transaction_type = 'Sale' THEN it.quantity ELSE 0 END), 0) + 
            COALESCE(SUM(CASE WHEN it.transaction_type = 'PurchaseReturn' THEN it.quantity ELSE 0 END), 0) - 
            COALESCE(SUM(CASE WHEN it.transaction_type = 'SaleReturn' THEN it.quantity ELSE 0 END), 0)
        )) as inventory_value
    FROM outlets o
    LEFT JOIN products p ON o.outlet_id = p.outlet_id
    LEFT JOIN inventory_transactions it ON p.product_id = it.product_id
    GROUP BY o.outlet_id, o.outlet_name
    ORDER BY o.outlet_name
";

$outlet_summary_result = $conn->query($outlet_summary_query);

// Get low stock alerts (products with 5 or fewer units)
$low_stock_query = "
    SELECT 
        p.product_id,
        p.product_name,
        p.category,
        o.outlet_name,
        (COALESCE(SUM(CASE WHEN it.transaction_type = 'Purchase' THEN it.quantity ELSE 0 END), 0) - 
         COALESCE(SUM(CASE WHEN it.transaction_type = 'Sale' THEN it.quantity ELSE 0 END), 0) + 
         COALESCE(SUM(CASE WHEN it.transaction_type = 'PurchaseReturn' THEN it.quantity ELSE 0 END), 0) - 
         COALESCE(SUM(CASE WHEN it.transaction_type = 'SaleReturn' THEN it.quantity ELSE 0 END), 0)) as available_stock
    FROM products p
    JOIN outlets o ON p.outlet_id = o.outlet_id
    LEFT JOIN inventory_transactions it ON p.product_id = it.product_id
    WHERE p.status = 'Available'
    GROUP BY p.product_id, p.product_name, p.category, o.outlet_name
    HAVING available_stock <= 5
    ORDER BY available_stock ASC
";

$low_stock_result = $conn->query($low_stock_query);

// Get top products by sales value
$top_products_query = "
    SELECT 
        p.product_name,
        SUM(it.quantity * it.unit_cost) as total_sales_value,
        SUM(it.quantity) as total_quantity_sold
    FROM inventory_transactions it
    JOIN products p ON it.product_id = p.product_id
    WHERE it.transaction_type = 'Sale'
    GROUP BY p.product_id, p.product_name
    ORDER BY total_sales_value DESC
    LIMIT 5
";

$top_products_result = $conn->query($top_products_query);

// Get inventory turnover ratio
$turnover_query = "
    SELECT 
        SUM(CASE WHEN it.transaction_type = 'Sale' THEN it.quantity * it.unit_cost ELSE 0 END) as total_sales,
        SUM(CASE WHEN it.transaction_type = 'Purchase' THEN it.quantity * it.unit_cost ELSE 0 END) as total_purchases,
        SUM(p.purchase_price * (
            COALESCE(SUM(CASE WHEN it2.transaction_type = 'Purchase' THEN it2.quantity ELSE 0 END), 0) - 
            COALESCE(SUM(CASE WHEN it2.transaction_type = 'Sale' THEN it2.quantity ELSE 0 END), 0) + 
            COALESCE(SUM(CASE WHEN it2.transaction_type = 'PurchaseReturn' THEN it2.quantity ELSE 0 END), 0) - 
            COALESCE(SUM(CASE WHEN it2.transaction_type = 'SaleReturn' THEN it2.quantity ELSE 0 END), 0)
        )) as average_inventory_value
    FROM products p
    LEFT JOIN inventory_transactions it ON p.product_id = it.product_id
    LEFT JOIN inventory_transactions it2 ON p.product_id = it2.product_id
    WHERE p.status = 'Available'
";

$turnover_result = $conn->query($turnover_query);
$turnover_data = $turnover_result->fetch_assoc();

// Calculate turnover ratio
$turnover_ratio = 0;
if ($turnover_data['average_inventory_value'] > 0) {
    $turnover_ratio = $turnover_data['total_sales'] / $turnover_data['average_inventory_value'];
}

// Get category-wise inventory distribution
$category_query = "
    SELECT 
        p.category,
        COUNT(p.product_id) as product_count,
        SUM(p.purchase_price * (
            COALESCE(SUM(CASE WHEN it.transaction_type = 'Purchase' THEN it.quantity ELSE 0 END), 0) - 
            COALESCE(SUM(CASE WHEN it.transaction_type = 'Sale' THEN it.quantity ELSE 0 END), 0) + 
            COALESCE(SUM(CASE WHEN it.transaction_type = 'PurchaseReturn' THEN it.quantity ELSE 0 END), 0) - 
            COALESCE(SUM(CASE WHEN it.transaction_type = 'SaleReturn' THEN it.quantity ELSE 0 END), 0)
        )) as category_value
    FROM products p
    LEFT JOIN inventory_transactions it ON p.product_id = it.product_id
    WHERE p.category IS NOT NULL AND p.category != ''
    GROUP BY p.category
    ORDER BY category_value DESC
";

$category_result = $conn->query($category_query);

// Get recent transactions
$recent_transactions_query = "
    SELECT 
        it.transaction_id,
        it.transaction_type,
        p.product_name,
        o.outlet_name,
        it.quantity,
        it.unit_cost,
        it.transaction_date,
        u.username as created_by
    FROM inventory_transactions it
    JOIN products p ON it.product_id = p.product_id
    JOIN outlets o ON it.outlet_id = o.outlet_id
    JOIN users u ON it.created_by = u.user_id
    ORDER BY it.transaction_date DESC
    LIMIT 10
";

$recent_transactions_result = $conn->query($recent_transactions_query);

// Store the connection for later use in JavaScript
$GLOBALS['db_connection'] = $conn;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard - Lease Management System</title>
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
                            <h1><i class="bi bi-speedometer2 me-3"></i>Inventory Dashboard</h1>
                            <p class="mb-0">Overview of inventory across all outlets</p>
                        </div>
                    </div>
                </div>

                <!-- Breadcrumb Navigation -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="products.php">Inventory & Product</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Inventory Dashboard</li>
                    </ol>
                </nav>

                <!-- Inventory Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Products</h5>
                                <p class="card-text display-6"><?php echo $summary['total_products'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Available Products</h5>
                                <p class="card-text display-6"><?php echo $summary['available_products'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Leased Products</h5>
                                <p class="card-text display-6"><?php echo $summary['leased_products'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Available Stock</h5>
                                <p class="card-text display-6"><?php echo $summary['total_available_stock'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-secondary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Maintenance</h5>
                                <p class="card-text display-6"><?php echo $summary['maintenance_products'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h5 class="card-title">Retired Products</h5>
                                <p class="card-text display-6"><?php echo $summary['retired_products'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-dark text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Inventory Value</h5>
                                <p class="card-text display-6">$<?php echo number_format($summary['total_inventory_value'] ?? 0, 2); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Turnover Ratio</h5>
                                <p class="card-text display-6"><?php echo number_format($turnover_ratio, 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <canvas id="statusChart" width="400" height="400"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <canvas id="outletChart" width="400" height="400"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <canvas id="categoryChart" width="400" height="400"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transaction Chart -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <canvas id="transactionChart" width="400" height="400"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Products Section -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="bi bi-star me-2"></i>Top Products by Sales Value</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Product Name</th>
                                                <th>Quantity Sold</th>
                                                <th>Sales Value ($)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($top_products_result && $top_products_result->num_rows > 0): ?>
                                                <?php while ($product = $top_products_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                                        <td><?php echo $product['total_quantity_sold']; ?></td>
                                                        <td>$<?php echo number_format($product['total_sales_value'], 2); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No sales data available.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Outlet-wise Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-shop me-2"></i>Outlet-wise Inventory Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Outlet</th>
                                        <th>Total Products</th>
                                        <th>Available</th>
                                        <th>Leased</th>
                                        <th>Available Stock</th>
                                        <th>Inventory Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($outlet_summary_result && $outlet_summary_result->num_rows > 0): ?>
                                        <?php while ($outlet = $outlet_summary_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($outlet['outlet_name']); ?></td>
                                                <td><?php echo $outlet['total_products']; ?></td>
                                                <td><?php echo $outlet['available_products']; ?></td>
                                                <td><?php echo $outlet['leased_products']; ?></td>
                                                <td><?php echo $outlet['available_stock']; ?></td>
                                                <td>$<?php echo number_format($outlet['inventory_value'], 2); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No outlet data found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alerts -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-exclamation-triangle me-2"></i>Low Stock Alerts</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($low_stock_result && $low_stock_result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Product ID</th>
                                            <th>Product Name</th>
                                            <th>Category</th>
                                            <th>Outlet</th>
                                            <th>Available Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($product = $low_stock_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                                <td><?php echo htmlspecialchars($product['outlet_name']); ?></td>
                                                <td class="text-danger fw-bold"><?php echo $product['available_stock']; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-success">No low stock alerts. All products are adequately stocked.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-list me-2"></i>Recent Inventory Transactions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Type</th>
                                        <th>Product</th>
                                        <th>Outlet</th>
                                        <th>Quantity</th>
                                        <th>Unit Cost</th>
                                        <th>Date</th>
                                        <th>Created By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_transactions_result && $recent_transactions_result->num_rows > 0): ?>
                                        <?php while ($transaction = $recent_transactions_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($transaction['transaction_id']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $transaction['transaction_type'] == 'Purchase' ? 'success' : 
                                                            ($transaction['transaction_type'] == 'Sale' ? 'primary' : 
                                                            ($transaction['transaction_type'] == 'PurchaseReturn' ? 'warning' : 
                                                            ($transaction['transaction_type'] == 'SaleReturn' ? 'info' : 'secondary'))); 
                                                    ?>">
                                                        <?php echo htmlspecialchars($transaction['transaction_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($transaction['product_name']); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['outlet_name']); ?></td>
                                                <td><?php echo $transaction['quantity']; ?></td>
                                                <td>$<?php echo number_format($transaction['unit_cost'], 2); ?></td>
                                                <td><?php echo date('M j, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['created_by']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No recent transactions found.</td>
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

    <footer class="bg-light text-center py-4">
        <div class="container">
            <p class="mb-0">&copy; 2025 Lease Management System. All rights reserved.</p>
            <p class="mb-0 text-muted small">Version 1.0.0</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/chart.js"></script>
    <?php $GLOBALS['db_connection']->close(); ?>
    <script>
        // Initialize charts after the page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Product Status Distribution Chart
            var statusCtx = document.getElementById('statusChart').getContext('2d');
            var statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Available', 'Leased', 'Maintenance', 'Retired'],
                    datasets: [{
                        data: [<?php echo $summary['available_products'] ?? 0; ?>, <?php echo $summary['leased_products'] ?? 0; ?>, <?php echo $summary['maintenance_products'] ?? 0; ?>, <?php echo $summary['retired_products'] ?? 0; ?>],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(255, 193, 7, 0.8)',
                            'rgba(108, 117, 125, 0.8)',
                            'rgba(220, 53, 69, 0.8)'
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(108, 117, 125, 1)',
                            'rgba(220, 53, 69, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Product Status Distribution'
                        }
                    }
                }
            });

            // Inventory Value by Outlet Chart
            var outletCtx = document.getElementById('outletChart').getContext('2d');
            var outletLabels = [];
            var outletValues = [];
            
            <?php 
            // Reset the result pointer
            mysqli_data_seek($outlet_summary_result, 0);
            while ($outlet = $outlet_summary_result->fetch_assoc()): ?>
                outletLabels.push('<?php echo htmlspecialchars($outlet['outlet_name']); ?>');
                outletValues.push(<?php echo $outlet['inventory_value']; ?>);
            <?php endwhile; ?>
            
            // Category-wise Inventory Distribution Chart
            var categoryCtx = document.getElementById('categoryChart').getContext('2d');
            var categoryLabels = [];
            var categoryValues = [];
            
            <?php 
            // Reset the result pointer
            if ($category_result && $category_result->num_rows > 0) {
                mysqli_data_seek($category_result, 0);
                while ($category = $category_result->fetch_assoc()): ?>
                    categoryLabels.push('<?php echo htmlspecialchars($category['category']); ?>');
                    categoryValues.push(<?php echo $category['category_value']; ?>);
                <?php endwhile;
            }
            ?>
            
            var categoryChart = new Chart(categoryCtx, {
                type: 'pie',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        label: 'Inventory Value by Category',
                        data: categoryValues,
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(13, 110, 253, 0.8)',
                            'rgba(255, 193, 7, 0.8)',
                            'rgba(220, 53, 69, 0.8)',
                            'rgba(111, 66, 193, 0.8)',
                            'rgba(108, 117, 125, 0.8)',
                            'rgba(0, 123, 255, 0.8)',
                            'rgba(23, 162, 184, 0.8)'
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(13, 110, 253, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(220, 53, 69, 1)',
                            'rgba(111, 66, 193, 1)',
                            'rgba(108, 117, 125, 1)',
                            'rgba(0, 123, 255, 1)',
                            'rgba(23, 162, 184, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Inventory Value by Category'
                        }
                    }
                }
            });

            var outletChart = new Chart(outletCtx, {
                type: 'bar',
                data: {
                    labels: outletLabels,
                    datasets: [{
                        label: 'Inventory Value ($)',
                        data: outletValues,
                        backgroundColor: 'rgba(13, 110, 253, 0.8)',
                        borderColor: 'rgba(13, 110, 253, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Inventory Value by Outlet'
                        }
                    },
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

            // Transaction Type Distribution Chart
            var transactionCtx = document.getElementById('transactionChart').getContext('2d');
            var transactionChart = new Chart(transactionCtx, {
                type: 'pie',
                data: {
                    labels: ['Purchase', 'Sale', 'Purchase Return', 'Sale Return', 'Adjustment', 'Transfer'],
                    datasets: [{
                        data: [
                            <?php 
                            // Get transaction counts by type
                            $purchase_count = $GLOBALS['db_connection']->query("SELECT COUNT(*) as count FROM inventory_transactions WHERE transaction_type = 'Purchase'")->fetch_assoc()['count'];
                            $sale_count = $GLOBALS['db_connection']->query("SELECT COUNT(*) as count FROM inventory_transactions WHERE transaction_type = 'Sale'")->fetch_assoc()['count'];
                            $purchase_return_count = $GLOBALS['db_connection']->query("SELECT COUNT(*) as count FROM inventory_transactions WHERE transaction_type = 'PurchaseReturn'")->fetch_assoc()['count'];
                            $sale_return_count = $GLOBALS['db_connection']->query("SELECT COUNT(*) as count FROM inventory_transactions WHERE transaction_type = 'SaleReturn'")->fetch_assoc()['count'];
                            $adjustment_count = $GLOBALS['db_connection']->query("SELECT COUNT(*) as count FROM inventory_transactions WHERE transaction_type = 'Adjustment'")->fetch_assoc()['count'];
                            $transfer_count = $GLOBALS['db_connection']->query("SELECT COUNT(*) as count FROM inventory_transactions WHERE transaction_type = 'Transfer'")->fetch_assoc()['count'];
                            echo "$purchase_count, $sale_count, $purchase_return_count, $sale_return_count, $adjustment_count, $transfer_count";
                            ?>
                        ],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(13, 110, 253, 0.8)',
                            'rgba(255, 193, 7, 0.8)',
                            'rgba(220, 53, 69, 0.8)',
                            'rgba(111, 66, 193, 0.8)',
                            'rgba(108, 117, 125, 0.8)'
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(13, 110, 253, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(220, 53, 69, 1)',
                            'rgba(111, 66, 193, 1)',
                            'rgba(108, 117, 125, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Transaction Type Distribution'
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>