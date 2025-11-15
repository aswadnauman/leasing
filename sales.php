<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Handle form submission for adding sales
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_sale'])) {
        // Add new sale
        $sale_id = $_POST['sale_id'];
        $customer_id = $_POST['customer_id'];
        $outlet_id = $_POST['outlet_id'];
        $sale_date = $_POST['sale_date'];
        $total_amount = $_POST['total_amount'];
        $discount = $_POST['discount'] ?? 0;
        $tax_amount = $_POST['tax_amount'] ?? 0;
        $net_amount = $_POST['net_amount'];
        $payment_status = $_POST['payment_status'];
        $remarks = $_POST['remarks'] ?? '';
        $created_by = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("INSERT INTO sales (sale_id, customer_id, outlet_id, sale_date, total_amount, discount, tax_amount, net_amount, payment_status, remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssdddddss", $sale_id, $customer_id, $outlet_id, $sale_date, $total_amount, $discount, $tax_amount, $net_amount, $payment_status, $remarks, $created_by);
        
        if ($stmt->execute()) {
            $success = "Sale added successfully!";
        } else {
            $error = "Error adding sale: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all sales with related data
$sales_result = $conn->query("
    SELECT s.*, c.customer_name, o.outlet_name, u.username as created_by_name
    FROM sales s
    JOIN customers c ON s.customer_id = c.customer_id
    JOIN outlets o ON s.outlet_id = o.outlet_id
    JOIN users u ON s.created_by = u.user_id
    ORDER BY s.created_at DESC
");

// Fetch customers for dropdown
$customers_result = $conn->query("SELECT customer_id, customer_name FROM customers WHERE status='Active' ORDER BY customer_name");

// Fetch outlets for dropdown
$outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales - Lease Management System</title>
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
                <h2>Sales Management</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Add Sale Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Add New Sale</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="sale_id" class="form-label">Sale ID *</label>
                                        <input type="text" class="form-control" id="sale_id" name="sale_id" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="customer_id" class="form-label">Customer *</label>
                                        <select class="form-select" id="customer_id" name="customer_id" required>
                                            <option value="">Select Customer</option>
                                            <?php if ($customers_result && $customers_result->num_rows > 0): ?>
                                                <?php while ($customer = $customers_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $customer['customer_id']; ?>"><?php echo htmlspecialchars($customer['customer_name']); ?></option>
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
                                        <label for="sale_date" class="form-label">Sale Date *</label>
                                        <input type="date" class="form-control" id="sale_date" name="sale_date" required>
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
                            
                            <button type="submit" name="add_sale" class="btn btn-primary">Add Sale</button>
                        </form>
                    </div>
                </div>
                
                <!-- Sales List -->
                <div class="card">
                    <div class="card-header">
                        <h5>All Sales</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Sale ID</th>
                                        <th>Customer</th>
                                        <th>Outlet</th>
                                        <th>Sale Date</th>
                                        <th>Total Amount</th>
                                        <th>Net Amount</th>
                                        <th>Payment Status</th>
                                        <th>Created By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($sales_result && $sales_result->num_rows > 0): ?>
                                        <?php while ($sale = $sales_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($sale['sale_id']); ?></td>
                                                <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($sale['outlet_name']); ?></td>
                                                <td><?php echo htmlspecialchars($sale['sale_date']); ?></td>
                                                <td>$<?php echo number_format($sale['total_amount'], 2); ?></td>
                                                <td>$<?php echo number_format($sale['net_amount'], 2); ?></td>
                                                <td>
                                                    <?php 
                                                    $status_class = '';
                                                    switch ($sale['payment_status']) {
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
                                                        <?php echo htmlspecialchars($sale['payment_status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($sale['created_by_name']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No sales found.</td>
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