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

// Database connection
$conn = getDBConnection();

// Get summary statistics
$summary_query = "
    SELECT 
        COUNT(DISTINCT p.product_id) as total_products,
        COUNT(DISTINCT CASE WHEN p.status = 'Available' THEN p.product_id END) as available_products,
        COUNT(DISTINCT CASE WHEN p.status = 'Leased' THEN p.product_id END) as leased_products,
        COUNT(DISTINCT CASE WHEN p.status = 'UnderMaintenance' THEN p.product_id END) as maintenance_products,
        COUNT(DISTINCT CASE WHEN p.status = 'Retired' THEN p.product_id END) as retired_products,
        0 as total_available_stock
    FROM products p
";

// Get total available stock separately
$stock_query = "
    SELECT 
        SUM(
            CASE WHEN transaction_type = 'Purchase' THEN quantity ELSE 0 END) - 
        SUM(
            CASE WHEN transaction_type = 'Sale' THEN quantity ELSE 0 END) + 
        SUM(
            CASE WHEN transaction_type = 'PurchaseReturn' THEN quantity ELSE 0 END) - 
        SUM(
            CASE WHEN transaction_type = 'SaleReturn' THEN quantity ELSE 0 END
        ) as total_available_stock
    FROM inventory_transactions it
    JOIN products p ON it.product_id = p.product_id
    WHERE p.status = 'Available'
";

$stock_result = $conn->query($stock_query);
$stock_data = $stock_result->fetch_assoc();

$summary_result = $conn->query($summary_query);
$summary = $summary_result->fetch_assoc();
$summary['total_available_stock'] = $stock_data['total_available_stock'] ?? 0;

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
    LIMIT 5
";

$recent_transactions_result = $conn->query($recent_transactions_query);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Module - Lease Management System</title>
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
                            <h1><i class="bi bi-box-seam me-3"></i>Inventory Module</h1>
                            <p class="mb-0">Complete inventory management system</p>
                        </div>
                    </div>
                </div>

                <!-- Breadcrumb Navigation -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Inventory Module</li>
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

                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 col-6 mb-3">
                                <a href="products.php?action=add" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-plus-circle me-2"></i>Add Product
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="sales.php" class="btn btn-outline-success w-100">
                                    <i class="bi bi-currency-dollar me-2"></i>Sales
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="purchases.php" class="btn btn-outline-info w-100">
                                    <i class="bi bi-cart-plus me-2"></i>Purchases
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="inventory_management.php" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-arrow-left-right me-2"></i>Transactions
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="sales_returns.php" class="btn btn-outline-warning w-100">
                                    <i class="bi bi-arrow-return-left me-2"></i>Sales Returns
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="purchase_returns.php" class="btn btn-outline-danger w-100">
                                    <i class="bi bi-arrow-return-right me-2"></i>Purchase Returns
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="stock_adjustment.php" class="btn btn-outline-dark w-100">
                                    <i class="bi bi-tools me-2"></i>Stock Adjustment
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <a href="inventory_report.php" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-bar-chart me-2"></i>Inventory Report
                                </a>
                            </div>
                        </div>
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
</body>
</html>