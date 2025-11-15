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
$selected_outlet = isset($_GET['outlet_filter']) ? $_GET['outlet_filter'] : 'ALL';
$product_category = isset($_GET['category_filter']) ? $_GET['category_filter'] : '';

// Build filter conditions
$filter_conditions = [];
$params = [];
$types = "";

if ($selected_outlet != 'ALL') {
    $filter_conditions[] = "p.outlet_id = ?";
    $params[] = $selected_outlet;
    $types .= "s";
}

if (!empty($product_category)) {
    $filter_conditions[] = "p.category = ?";
    $params[] = $product_category;
    $types .= "s";
}

// Base query for inventory report
$base_query = "
    SELECT 
        p.product_id,
        p.product_name,
        p.category,
        p.brand,
        p.model,
        p.purchase_price,
        p.leasing_rate,
        p.status,
        o.outlet_name,
        COALESCE(SUM(CASE WHEN it.transaction_type = 'Purchase' THEN it.quantity ELSE 0 END), 0) as total_purchased,
        COALESCE(SUM(CASE WHEN it.transaction_type = 'Sale' THEN it.quantity ELSE 0 END), 0) as total_sold,
        COALESCE(SUM(CASE WHEN it.transaction_type = 'PurchaseReturn' THEN it.quantity ELSE 0 END), 0) as total_purchase_returns,
        COALESCE(SUM(CASE WHEN it.transaction_type = 'SaleReturn' THEN it.quantity ELSE 0 END), 0) as total_sale_returns
    FROM products p
    JOIN outlets o ON p.outlet_id = o.outlet_id
    LEFT JOIN inventory_transactions it ON p.product_id = it.product_id
";

// Add filter conditions
if (!empty($filter_conditions)) {
    $base_query .= " WHERE " . implode(" AND ", $filter_conditions);
}

$base_query .= "
    GROUP BY p.id, p.product_id, p.product_name, p.category, p.brand, p.model, p.purchase_price, p.leasing_rate, p.status, o.outlet_name
    ORDER BY p.category, p.product_name
";

// Prepare and execute query
if (!empty($params)) {
    $stmt = $conn->prepare($base_query);
    $stmt->bind_param($types, ...$params);
} else {
    $stmt = $conn->prepare($base_query);
}

$stmt->execute();
$inventory_result = $stmt->get_result();

// Fetch outlets for filter dropdown
$outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets ORDER BY outlet_name");

// Fetch product categories for filter dropdown
$categories_result = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Report - Lease Management System</title>
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
                        <a class="nav-link" href="sales.php">Sales</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="purchases.php">Purchases</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="payment_vouchers.php">Payment Vouchers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="inventory_report.php">Inventory Report</a>
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
                <h2>Inventory Report</h2>
                
                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Filters</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="outlet_filter" class="form-label">Outlet</label>
                                        <select class="form-select" id="outlet_filter" name="outlet_filter">
                                            <option value="ALL" <?php echo ($selected_outlet == 'ALL') ? 'selected' : ''; ?>>All Outlets</option>
                                            <?php if ($outlets_result && $outlets_result->num_rows > 0): ?>
                                                <?php while ($outlet = $outlets_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $outlet['outlet_id']; ?>" <?php echo ($selected_outlet == $outlet['outlet_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($outlet['outlet_name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="category_filter" class="form-label">Category</label>
                                        <select class="form-select" id="category_filter" name="category_filter">
                                            <option value="">All Categories</option>
                                            <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                                                <?php while ($category = $categories_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $category['category']; ?>" <?php echo ($product_category == $category['category']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($category['category']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="mb-3">
                                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                                        <a href="inventory_report.php" class="btn btn-secondary">Clear</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Inventory Summary -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Products</h5>
                                <p class="card-text display-6">
                                    <?php 
                                    $total_products = 0;
                                    $total_available = 0;
                                    $total_value = 0;
                                    mysqli_data_seek($inventory_result, 0);
                                    while ($row = $inventory_result->fetch_assoc()) {
                                        $total_products++;
                                        if ($row['status'] == 'Available') {
                                            $total_available++;
                                        }
                                        $total_value += $row['purchase_price'] * ($row['total_purchased'] - $row['total_sold'] + $row['total_sale_returns'] - $row['total_purchase_returns']);
                                    }
                                    echo $total_products;
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Available Products</h5>
                                <p class="card-text display-6"><?php echo $total_available; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Value</h5>
                                <p class="card-text display-6">$<?php echo number_format($total_value, 2); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Low Stock Items</h5>
                                <p class="card-text display-6">
                                    <?php 
                                    $low_stock_count = 0;
                                    mysqli_data_seek($inventory_result, 0);
                                    while ($row = $inventory_result->fetch_assoc()) {
                                        $available_stock = $row['total_purchased'] - $row['total_sold'] + $row['total_sale_returns'] - $row['total_purchase_returns'];
                                        if ($available_stock <= 5 && $row['status'] == 'Available') {
                                            $low_stock_count++;
                                        }
                                    }
                                    echo $low_stock_count;
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Inventory Details -->
                <div class="card">
                    <div class="card-header">
                        <h5>Inventory Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product ID</th>
                                        <th>Product Name</th>
                                        <th>Category</th>
                                        <th>Brand</th>
                                        <th>Model</th>
                                        <th>Outlet</th>
                                        <th>Purchase Price</th>
                                        <th>Leasing Rate</th>
                                        <th>Status</th>
                                        <th>Purchased</th>
                                        <th>Sold</th>
                                        <th>Returns</th>
                                        <th>Available</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    mysqli_data_seek($inventory_result, 0);
                                    while ($product = $inventory_result->fetch_assoc()): 
                                        $available_stock = $product['total_purchased'] - $product['total_sold'] + $product['total_sale_returns'] - $product['total_purchase_returns'];
                                        $product_value = $product['purchase_price'] * $available_stock;
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                                            <td><?php echo htmlspecialchars($product['brand']); ?></td>
                                            <td><?php echo htmlspecialchars($product['model']); ?></td>
                                            <td><?php echo htmlspecialchars($product['outlet_name']); ?></td>
                                            <td>$<?php echo number_format($product['purchase_price'], 2); ?></td>
                                            <td><?php echo number_format($product['leasing_rate'], 2); ?>%</td>
                                            <td>
                                                <?php if ($product['status'] == 'Available'): ?>
                                                    <span class="badge bg-success">Available</span>
                                                <?php elseif ($product['status'] == 'Leased'): ?>
                                                    <span class="badge bg-primary">Leased</span>
                                                <?php elseif ($product['status'] == 'UnderMaintenance'): ?>
                                                    <span class="badge bg-warning">Maintenance</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Retired</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $product['total_purchased']; ?></td>
                                            <td><?php echo $product['total_sold']; ?></td>
                                            <td><?php echo ($product['total_sale_returns'] + $product['total_purchase_returns']); ?></td>
                                            <td><?php echo $available_stock; ?></td>
                                            <td>$<?php echo number_format($product_value, 2); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php if ($inventory_result->num_rows == 0): ?>
                                        <tr>
                                            <td colspan="14" class="text-center">No inventory data found.</td>
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

    <footer class="bg-light text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">&copy; 2025 Lease Management System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            $('#outlet_filter').select2({
                placeholder: "Select an outlet",
                allowClear: true
            });
            
            $('#category_filter').select2({
                placeholder: "Select a category",
                allowClear: true
            });
        });
    </script>
</body>
</html>