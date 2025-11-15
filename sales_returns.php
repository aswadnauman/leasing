<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Handle form submission for adding sales returns
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_return'])) {
        // Add new sales return
        $return_id = $_POST['return_id'];
        $sale_id = $_POST['sale_id'];
        $customer_id = $_POST['customer_id'];
        $outlet_id = $_POST['outlet_id'];
        $return_date = $_POST['return_date'];
        $total_amount = $_POST['total_amount'];
        $remarks = $_POST['remarks'] ?? '';
        $created_by = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("INSERT INTO sales_returns (return_id, sale_id, customer_id, outlet_id, return_date, total_amount, remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssdss", $return_id, $sale_id, $customer_id, $outlet_id, $return_date, $total_amount, $remarks, $created_by);
        
        if ($stmt->execute()) {
            $success = "Sales return added successfully!";
        } else {
            $error = "Error adding sales return: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all sales returns with related data
$sales_returns_result = $conn->query("
    SELECT sr.*, s.sale_id as original_sale_id, c.customer_name, o.outlet_name, u.username as created_by_name
    FROM sales_returns sr
    JOIN sales s ON sr.sale_id = s.sale_id
    JOIN customers c ON sr.customer_id = c.customer_id
    JOIN outlets o ON sr.outlet_id = o.outlet_id
    JOIN users u ON sr.created_by = u.user_id
    ORDER BY sr.created_at DESC
");

// Fetch sales for dropdown
$sales_result = $conn->query("SELECT sale_id FROM sales ORDER BY sale_id");

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
    <title>Sales Returns - Lease Management System</title>
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
                        <a class="nav-link active" href="sales_returns.php">Sales Returns</a>
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
                <h2>Sales Returns Management</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Add Sales Return Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Add New Sales Return</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="return_id" class="form-label">Return ID *</label>
                                        <input type="text" class="form-control" id="return_id" name="return_id" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="sale_id" class="form-label">Original Sale ID *</label>
                                        <select class="form-select" id="sale_id" name="sale_id" required>
                                            <option value="">Select Sale</option>
                                            <?php if ($sales_result && $sales_result->num_rows > 0): ?>
                                                <?php while ($sale = $sales_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $sale['sale_id']; ?>"><?php echo htmlspecialchars($sale['sale_id']); ?></option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
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
                                        <label for="return_date" class="form-label">Return Date *</label>
                                        <input type="date" class="form-control" id="return_date" name="return_date" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="total_amount" class="form-label">Total Amount *</label>
                                        <input type="number" class="form-control" id="total_amount" name="total_amount" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="remarks" class="form-label">Remarks</label>
                                <textarea class="form-control" id="remarks" name="remarks" rows="2"></textarea>
                            </div>
                            
                            <button type="submit" name="add_return" class="btn btn-primary">Add Sales Return</button>
                        </form>
                    </div>
                </div>
                
                <!-- Sales Returns List -->
                <div class="card">
                    <div class="card-header">
                        <h5>All Sales Returns</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Return ID</th>
                                        <th>Original Sale</th>
                                        <th>Customer</th>
                                        <th>Outlet</th>
                                        <th>Return Date</th>
                                        <th>Total Amount</th>
                                        <th>Created By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($sales_returns_result && $sales_returns_result->num_rows > 0): ?>
                                        <?php while ($return = $sales_returns_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($return['return_id']); ?></td>
                                                <td><?php echo htmlspecialchars($return['original_sale_id']); ?></td>
                                                <td><?php echo htmlspecialchars($return['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($return['outlet_name']); ?></td>
                                                <td><?php echo htmlspecialchars($return['return_date']); ?></td>
                                                <td>$<?php echo number_format($return['total_amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($return['created_by_name']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No sales returns found.</td>
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
</body>
</html>