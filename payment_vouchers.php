<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Handle form submission for adding payment vouchers
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_voucher'])) {
        // Add new payment voucher
        $voucher_id = $_POST['voucher_id'];
        $voucher_date = $_POST['voucher_date'];
        $outlet_id = $_POST['outlet_id'];
        $payee_type = $_POST['payee_type'];
        $payee_name = $_POST['payee_name'];
        $account_id = $_POST['account_id'];
        $amount = $_POST['amount'];
        $payment_method = $_POST['payment_method'];
        $reference_no = $_POST['reference_no'] ?? '';
        $remarks = $_POST['remarks'] ?? '';
        $created_by = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("INSERT INTO payment_vouchers (voucher_id, voucher_date, outlet_id, payee_type, payee_name, account_id, amount, payment_method, reference_no, remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssdssss", $voucher_id, $voucher_date, $outlet_id, $payee_type, $payee_name, $account_id, $amount, $payment_method, $reference_no, $remarks, $created_by);
        
        if ($stmt->execute()) {
            $success = "Payment voucher added successfully!";
        } else {
            $error = "Error adding payment voucher: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all payment vouchers with related data
$vouchers_result = $conn->query("
    SELECT pv.*, o.outlet_name, coa.account_name, u.username as created_by_name
    FROM payment_vouchers pv
    JOIN outlets o ON pv.outlet_id = o.outlet_id
    JOIN chart_of_accounts coa ON pv.account_id = coa.id
    JOIN users u ON pv.created_by = u.user_id
    ORDER BY pv.created_at DESC
");

// Fetch outlets for dropdown
$outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets");

// Fetch chart of accounts for dropdown
$accounts_result = $conn->query("SELECT id, account_code, account_name FROM chart_of_accounts WHERE is_active=1 ORDER BY account_code");

// We'll close the connection after we're done with the HTML output
// $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Vouchers - Lease Management System</title>
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
                        <a class="nav-link" href="purchases.php">Purchases</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="payment_vouchers.php">Payment Vouchers</a>
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
                <h2>Payment Voucher Management</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Add Payment Voucher Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Add New Payment Voucher</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="voucher_id" class="form-label">Voucher ID *</label>
                                        <input type="text" class="form-control" id="voucher_id" name="voucher_id" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="voucher_date" class="form-label">Voucher Date *</label>
                                        <input type="date" class="form-control" id="voucher_date" name="voucher_date" required>
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
                                        <label for="payee_type" class="form-label">Payee Type *</label>
                                        <select class="form-select" id="payee_type" name="payee_type" required>
                                            <option value="Supplier">Supplier</option>
                                            <option value="Customer">Customer</option>
                                            <option value="Employee">Employee</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payee_name" class="form-label">Payee Name *</label>
                                        <input type="text" class="form-control" id="payee_name" name="payee_name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="account_id" class="form-label">Account *</label>
                                        <select class="form-select" id="account_id" name="account_id" required>
                                            <option value="">Select Account</option>
                                            <?php if ($accounts_result && $accounts_result->num_rows > 0): ?>
                                                <?php while ($account = $accounts_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $account['id']; ?>"><?php echo htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']); ?></option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">Amount *</label>
                                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="payment_method" class="form-label">Payment Method *</label>
                                        <select class="form-select" id="payment_method" name="payment_method" required>
                                            <option value="Cash">Cash</option>
                                            <option value="Bank">Bank</option>
                                            <option value="Online">Online</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="reference_no" class="form-label">Reference No</label>
                                        <input type="text" class="form-control" id="reference_no" name="reference_no">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="remarks" class="form-label">Remarks</label>
                                <textarea class="form-control" id="remarks" name="remarks" rows="2"></textarea>
                            </div>
                            
                            <button type="submit" name="add_voucher" class="btn btn-primary">Add Payment Voucher</button>
                        </form>
                    </div>
                </div>
                
                <!-- Payment Vouchers List -->
                <div class="card">
                    <div class="card-header">
                        <h5>All Payment Vouchers</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Voucher ID</th>
                                        <th>Date</th>
                                        <th>Outlet</th>
                                        <th>Payee</th>
                                        <th>Account</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th>Created By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($vouchers_result && $vouchers_result->num_rows > 0): ?>
                                        <?php while ($voucher = $vouchers_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($voucher['voucher_id']); ?></td>
                                                <td><?php echo htmlspecialchars($voucher['voucher_date']); ?></td>
                                                <td><?php echo htmlspecialchars($voucher['outlet_name']); ?></td>
                                                <td><?php echo htmlspecialchars($voucher['payee_name']); ?></td>
                                                <td><?php echo htmlspecialchars($voucher['account_name']); ?></td>
                                                <td>$<?php echo number_format($voucher['amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($voucher['payment_method']); ?></td>
                                                <td><?php echo htmlspecialchars($voucher['reference_no']); ?></td>
                                                <td><?php echo htmlspecialchars($voucher['created_by_name']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No payment vouchers found.</td>
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
<?php
// Close the database connection after all HTML output is done
$conn->close();
?>