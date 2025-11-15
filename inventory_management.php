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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_transaction'])) {
        // Add inventory transaction
        $product_id = $_POST['product_id'];
        $transaction_type = $_POST['transaction_type'];
        $quantity = $_POST['quantity'];
        $unit_cost = $_POST['unit_cost'];
        $reference_id = !empty($_POST['reference_id']) ? $_POST['reference_id'] : null;
        $remarks = $_POST['remarks'];
        $outlet_id = $_POST['outlet_id'];
        $created_by = $_SESSION['user_id'];
        
        // Generate unique transaction ID
        $transaction_id = 'TXN' . strtoupper(uniqid());
        
        $stmt = $conn->prepare("INSERT INTO inventory_transactions (transaction_id, product_id, outlet_id, transaction_type, reference_id, quantity, unit_cost, remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssiiss", $transaction_id, $product_id, $outlet_id, $transaction_type, $reference_id, $quantity, $unit_cost, $remarks, $created_by);
        
        if ($stmt->execute()) {
            $success = "Transaction added successfully!";
            
            // Update product status based on stock levels
            updateProductStatus($conn, $product_id);
            
            // If it's a sale, also set status to Leased
            if ($transaction_type == 'Sale') {
                $stmt2 = $conn->prepare("UPDATE products SET status = 'Leased' WHERE product_id = ?");
                $stmt2->bind_param("s", $product_id);
                $stmt2->execute();
                $stmt2->close();
            }
        } else {
            $error = "Error adding transaction: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch products for dropdown
$products_result = $conn->query("SELECT product_id, product_name, purchase_price, outlet_id FROM products WHERE status = 'Available' ORDER BY product_name");

// Fetch outlets for dropdown
$outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets ORDER BY outlet_name");

// Fetch recent transactions
$transactions_result = $conn->query("
    SELECT 
        it.*, 
        p.product_name,
        o.outlet_name,
        u.username as created_by_name
    FROM inventory_transactions it
    JOIN products p ON it.product_id = p.product_id
    JOIN outlets o ON it.outlet_id = o.outlet_id
    JOIN users u ON it.created_by = u.user_id
    ORDER BY it.transaction_date DESC
    LIMIT 20
");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Lease Management System</title>
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
                            <h1><i class="bi bi-box-seam me-3"></i>Inventory Management</h1>
                            <p class="mb-0">Manage product inventory and track stock levels</p>
                        </div>
                    </div>
                </div>

                <!-- Breadcrumb Navigation -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="products.php">Inventory & Product</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Inventory Management</li>
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
                
                <!-- Add Transaction Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-arrow-left-right me-2"></i>Add Inventory Transaction</h5>
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
                                        <label for="transaction_type" class="form-label">Transaction Type *</label>
                                        <select class="form-select" id="transaction_type" name="transaction_type" required>
                                            <option value="">Select Type</option>
                                            <option value="Purchase">Purchase</option>
                                            <option value="Sale">Sale</option>
                                            <option value="PurchaseReturn">Purchase Return</option>
                                            <option value="SaleReturn">Sale Return</option>
                                            <option value="Adjustment">Adjustment</option>
                                            <option value="Transfer">Transfer</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label">Quantity *</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="unit_cost" class="form-label">Unit Cost *</label>
                                        <input type="number" class="form-control" id="unit_cost" name="unit_cost" step="0.01" min="0" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="reference_id" class="form-label">Reference ID (Optional)</label>
                                        <input type="text" class="form-control" id="reference_id" name="reference_id">
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
                            
                            <div class="mb-3">
                                <label for="remarks" class="form-label">Remarks</label>
                                <textarea class="form-control" id="remarks" name="remarks" rows="2"></textarea>
                            </div>
                            
                            <button type="submit" name="add_transaction" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Add Transaction</button>
                        </form>
                    </div>
                </div>
                
                <!-- Recent Transactions -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-list me-2"></i>Recent Transactions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Product</th>
                                        <th>Type</th>
                                        <th>Quantity</th>
                                        <th>Unit Cost</th>
                                        <th>Outlet</th>
                                        <th>Date</th>
                                        <th>Created By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($transactions_result && $transactions_result->num_rows > 0): ?>
                                        <?php while ($transaction = $transactions_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($transaction['transaction_id']); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['product_name']); ?></td>
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
                                                <td><?php echo $transaction['quantity']; ?></td>
                                                <td>$<?php echo number_format($transaction['unit_cost'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['outlet_name']); ?></td>
                                                <td><?php echo date('M j, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['created_by_name']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No transactions found.</td>
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
    <script>
        // Auto-fill unit cost when product is selected
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