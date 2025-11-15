<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Handle form submission for adding purchases
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_purchase'])) {
        // Add new purchase
        $purchase_id = $_POST['purchase_id'];
        $supplier_id = $_POST['supplier_id'];
        $outlet_id = $_POST['outlet_id'];
        $purchase_date = $_POST['purchase_date'];
        $total_amount = $_POST['total_amount'];
        $discount = $_POST['discount'] ?? 0;
        $tax_amount = $_POST['tax_amount'] ?? 0;
        $net_amount = $_POST['net_amount'];
        $payment_status = $_POST['payment_status'];
        $remarks = $_POST['remarks'] ?? '';
        $created_by = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("INSERT INTO purchases (purchase_id, supplier_id, outlet_id, purchase_date, total_amount, discount, tax_amount, net_amount, payment_status, remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssdddddss", $purchase_id, $supplier_id, $outlet_id, $purchase_date, $total_amount, $discount, $tax_amount, $net_amount, $payment_status, $remarks, $created_by);
        
        if ($stmt->execute()) {
            $success = "Purchase added successfully!";
        } else {
            $error = "Error adding purchase: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all purchases with related data
$purchases_result = $conn->query("
    SELECT p.*, s.supplier_name, o.outlet_name, u.username as created_by_name
    FROM purchases p
    JOIN suppliers s ON p.supplier_id = s.supplier_id
    JOIN outlets o ON p.outlet_id = o.outlet_id
    JOIN users u ON p.created_by = u.user_id
    ORDER BY p.created_at DESC
");

// Fetch suppliers for dropdown
$suppliers_result = $conn->query("SELECT supplier_id, supplier_name FROM suppliers WHERE status='Active' ORDER BY supplier_name");

// Fetch outlets for dropdown
$outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchases - Lease Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <a class="nav-link" href="sales_returns.php">Sales Returns</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="purchases.php">Purchases</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="purchase_returns.php">Purchase Returns</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="suppliers.php">Suppliers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="payment_vouchers.php">Payment Vouchers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">Reports</a>
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
                <h2>Purchase Management</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Add Purchase Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Add New Purchase</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="purchase_id" class="form-label">Purchase ID *</label>
                                        <input type="text" class="form-control" id="purchase_id" name="purchase_id" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="supplier_id" class="form-label">Supplier *</label>
                                        <select class="form-select" id="supplier_id" name="supplier_id" required>
                                            <option value="">Select Supplier</option>
                                            <?php if ($suppliers_result && $suppliers_result->num_rows > 0): ?>
                                                <?php while ($supplier = $suppliers_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $supplier['supplier_id']; ?>"><?php echo htmlspecialchars($supplier['supplier_name']); ?></option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
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
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="purchase_date" class="form-label">Purchase Date *</label>
                                        <input type="date" class="form-control" id="purchase_date" name="purchase_date" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="total_amount" class="form-label">Total Amount *</label>
                                        <input type="number" class="form-control" id="total_amount" name="total_amount" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="discount" class="form-label">Discount</label>
                                        <input type="number" class="form-control" id="discount" name="discount" step="0.01" value="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="tax_amount" class="form-label">Tax Amount</label>
                                        <input type="number" class="form-control" id="tax_amount" name="tax_amount" step="0.01" value="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="net_amount" class="form-label">Net Amount *</label>
                                        <input type="number" class="form-control" id="net_amount" name="net_amount" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_status" class="form-label">Payment Status *</label>
                                        <select class="form-select" id="payment_status" name="payment_status" required>
                                            <option value="Paid">Paid</option>
                                            <option value="Partial">Partial</option>
                                            <option value="Unpaid" selected>Unpaid</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="remarks" class="form-label">Remarks</label>
                                <textarea class="form-control" id="remarks" name="remarks" rows="2"></textarea>
                            </div>
                            
                            <button type="submit" name="add_purchase" class="btn btn-primary">Add Purchase</button>
                        </form>
                    </div>
                </div>
                
                <!-- Purchases List -->
                <div class="card">
                    <div class="card-header">
                        <h5>All Purchases</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Purchase ID</th>
                                        <th>Supplier</th>
                                        <th>Outlet</th>
                                        <th>Purchase Date</th>
                                        <th>Total Amount</th>
                                        <th>Net Amount</th>
                                        <th>Payment Status</th>
                                        <th>Created By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($purchases_result && $purchases_result->num_rows > 0): ?>
                                        <?php while ($purchase = $purchases_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($purchase['purchase_id']); ?></td>
                                                <td><?php echo htmlspecialchars($purchase['supplier_name']); ?></td>
                                                <td><?php echo htmlspecialchars($purchase['outlet_name']); ?></td>
                                                <td><?php echo htmlspecialchars($purchase['purchase_date']); ?></td>
                                                <td>$<?php echo number_format($purchase['total_amount'], 2); ?></td>
                                                <td>$<?php echo number_format($purchase['net_amount'], 2); ?></td>
                                                <td>
                                                    <?php 
                                                    $status_class = '';
                                                    switch ($purchase['payment_status']) {
                                                        case 'Paid':
                                                            $status_class = 'bg-success';
                                                            break;
                                                        case 'Partial':
                                                            $status_class = 'bg-warning';
                                                            break;
                                                        case 'Unpaid':
                                                            $status_class = 'bg-danger';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>">
                                                        <?php echo htmlspecialchars($purchase['payment_status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($purchase['created_by_name']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No purchases found.</td>
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
    <script>
        // Auto-calculate net amount
        document.addEventListener('DOMContentLoaded', function() {
            const totalAmount = document.getElementById('total_amount');
            const discount = document.getElementById('discount');
            const taxAmount = document.getElementById('tax_amount');
            const netAmount = document.getElementById('net_amount');
            
            function calculateNetAmount() {
                const total = parseFloat(totalAmount.value) || 0;
                const disc = parseFloat(discount.value) || 0;
                const tax = parseFloat(taxAmount.value) || 0;
                const net = total - disc + tax;
                netAmount.value = net.toFixed(2);
            }
            
            totalAmount.addEventListener('input', calculateNetAmount);
            discount.addEventListener('input', calculateNetAmount);
            taxAmount.addEventListener('input', calculateNetAmount);
        });
    </script>
</body>
</html>