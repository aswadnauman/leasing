<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check user role - only Admin and BranchManager can adjust stock
$user_role = $_SESSION['role'];
if ($user_role != 'Admin' && $user_role != 'BranchManager') {
    header("Location: dashboard.php");
    exit();
}

$conn = getDBConnection();

// Handle form submission for stock adjustment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['adjust_stock'])) {
    $product_id = $_POST['product_id'];
    $outlet_id = $_POST['outlet_id'];
    $quantity = $_POST['quantity'];
    $unit_cost = $_POST['unit_cost'];
    $reason = $_POST['reason'];
    $created_by = $_SESSION['user_id'];
    
    // Generate unique transaction ID
    $transaction_id = 'ADJ' . strtoupper(uniqid());
    
    // Add adjustment transaction
    $stmt = $conn->prepare("INSERT INTO inventory_transactions (transaction_id, product_id, outlet_id, transaction_type, quantity, unit_cost, remarks, created_by) VALUES (?, ?, ?, 'Adjustment', ?, ?, ?, ?)");
    $stmt->bind_param("sssiiss", $transaction_id, $product_id, $outlet_id, $quantity, $unit_cost, $reason, $created_by);
    
    if ($stmt->execute()) {
        // Update product status based on new stock levels
        updateProductStatus($conn, $product_id);
        $success = "Stock adjusted successfully!";
    } else {
        $error = "Error adjusting stock: " . $conn->error;
    }
    $stmt->close();
}

// Fetch products for dropdown
$products_result = $conn->query("SELECT product_id, product_name, purchase_price, outlet_id FROM products ORDER BY product_name");

// Fetch outlets for dropdown
$outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets ORDER BY outlet_name");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Adjustment - Lease Management System</title>
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
                            <h1><i class="bi bi-tools me-3"></i>Stock Adjustment</h1>
                            <p class="mb-0">Adjust inventory levels for discrepancies</p>
                        </div>
                    </div>
                </div>

                <!-- Breadcrumb Navigation -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="products.php">Inventory & Product</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Stock Adjustment</li>
                    </ol>
                </nav>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Stock Adjustment Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-tools me-2"></i>Adjust Stock Levels</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="product_id" class="form-label">Product *</label>
                                        <select class="form-select" id="product_id" name="product_id" required>
                                            <option value="">Select Product</option>
                                            <?php if ($products_result && $products_result->num_rows > 0): ?>
                                                <?php while ($product = $products_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $product['product_id']; ?>" 
                                                            data-price="<?php echo $product['purchase_price']; ?>" 
                                                            data-outlet="<?php echo $product['outlet_id']; ?>">
                                                        <?php echo htmlspecialchars($product['product_name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="outlet_id" class="form-label">Outlet *</label>
                                        <select class="form-select" id="outlet_id" name="outlet_id" required>
                                            <option value="">Select Outlet</option>
                                            <?php if ($outlets_result && $outlets_result->num_rows > 0): ?>
                                                <?php while ($outlet = $outlets_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $outlet['outlet_id']; ?>"><?php echo htmlspecialchars($outlet['outlet_name']); ?></option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label">Quantity Adjustment *</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" required>
                                        <div class="form-text">Positive numbers to increase stock, negative to decrease</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="unit_cost" class="form-label">Unit Cost *</label>
                                        <input type="number" class="form-control" id="unit_cost" name="unit_cost" step="0.01" min="0" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason for Adjustment *</label>
                                <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                            </div>
                            
                            <button type="submit" name="adjust_stock" class="btn btn-primary"><i class="bi bi-tools me-1"></i>Adjust Stock</button>
                        </form>
                    </div>
                </div>
                
                <!-- Instructions -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-info-circle me-2"></i>Instructions</h5>
                    </div>
                    <div class="card-body">
                        <ul>
                            <li>Use this form to adjust stock levels for products when there are discrepancies</li>
                            <li>Enter positive numbers to increase stock (e.g., found missing items)</li>
                            <li>Enter negative numbers to decrease stock (e.g., damaged items, theft)</li>
                            <li>Always provide a reason for the adjustment</li>
                            <li>Stock adjustments will be recorded in the inventory transactions log</li>
                        </ul>
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
    <script>
        // Auto-fill unit cost and outlet when product is selected
        document.getElementById('product_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            const outlet = selectedOption.getAttribute('data-outlet');
            
            if (price) {
                document.getElementById('unit_cost').value = price;
            }
            
            if (outlet) {
                document.getElementById('outlet_id').value = outlet;
            }
        });
    </script>
</body>
</html>