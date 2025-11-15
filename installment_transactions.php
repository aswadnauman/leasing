<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Handle form submission for adding installment transaction
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_transaction'])) {
        // Add new installment transaction
        $transaction_id = $_POST['transaction_id'];
        $lease_id = $_POST['lease_id'];
        $client_id = $_POST['client_id'];
        $outlet_id = $_POST['outlet_id'];
        $transaction_date = $_POST['transaction_date'];
        $amount = $_POST['amount'];
        $payment_method = $_POST['payment_method'];
        $reference_no = $_POST['reference_no'] ?? '';
        $remarks = $_POST['remarks'] ?? '';
        $created_by = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("INSERT INTO recovery_collections (lease_id, client_id, recovery_person_id, outlet_id, bank_name, account_number, reference_no, transaction_id, collection_date, amount, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        // Using recovery_person_id as a placeholder for now
        $recovery_person_id = $_SESSION['user_id'];
        $bank_name = '';
        $account_number = '';
        
        $stmt->bind_param("ssssssssdss", $lease_id, $client_id, $recovery_person_id, $outlet_id, $bank_name, $account_number, $reference_no, $transaction_id, $transaction_date, $amount, $created_by);
        
        if ($stmt->execute()) {
            $success = "Installment transaction added successfully!";
        } else {
            $error = "Error adding installment transaction: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all installment transactions with related data
$transactions_result = $conn->query("
    SELECT rc.*, l.lease_id as original_lease_id, c.full_name as client_name, o.outlet_name, u.username as created_by_name
    FROM recovery_collections rc
    LEFT JOIN leases l ON rc.lease_id = l.lease_id
    JOIN clients c ON rc.client_id = c.client_id
    JOIN outlets o ON rc.outlet_id = o.outlet_id
    JOIN users u ON rc.created_by = u.user_id
    ORDER BY rc.collection_date DESC
");

// Fetch leases for dropdown
$leases_result = $conn->query("SELECT lease_id FROM leases WHERE status = 'Active' OR status = 'Overdue' ORDER BY lease_id");

// Fetch clients for dropdown
$clients_result = $conn->query("SELECT client_id, full_name FROM clients WHERE status = 'Active' ORDER BY full_name");

// Fetch outlets for dropdown
$outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installment Transactions - Lease Management System</title>
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
                        <a class="nav-link" href="lease_terminations.php">Lease Terminations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="collections.php">Collections</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="installment_transactions.php">Installment Transactions</a>
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
                <h2>Installment Transactions Management</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Add Installment Transaction Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Add New Installment Transaction</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="transaction_id" class="form-label">Transaction ID *</label>
                                        <input type="text" class="form-control" id="transaction_id" name="transaction_id" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="lease_id" class="form-label">Lease ID *</label>
                                        <select class="form-select" id="lease_id" name="lease_id" required>
                                            <option value="">Select Lease</option>
                                            <?php if ($leases_result && $leases_result->num_rows > 0): ?>
                                                <?php while ($lease = $leases_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $lease['lease_id']; ?>"><?php echo htmlspecialchars($lease['lease_id']); ?></option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="client_id" class="form-label">Client *</label>
                                        <select class="form-select" id="client_id" name="client_id" required>
                                            <option value="">Select Client</option>
                                            <?php if ($clients_result && $clients_result->num_rows > 0): ?>
                                                <?php while ($client = $clients_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $client['client_id']; ?>"><?php echo htmlspecialchars($client['full_name']); ?></option>
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
                                        <label for="transaction_date" class="form-label">Transaction Date *</label>
                                        <input type="date" class="form-control" id="transaction_date" name="transaction_date" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">Amount *</label>
                                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_method" class="form-label">Payment Method *</label>
                                        <select class="form-select" id="payment_method" name="payment_method" required>
                                            <option value="">Select Payment Method</option>
                                            <option value="Cash">Cash</option>
                                            <option value="Bank">Bank</option>
                                            <option value="Online">Online</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
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
                            
                            <button type="submit" name="add_transaction" class="btn btn-primary">Add Installment Transaction</button>
                        </form>
                    </div>
                </div>
                
                <!-- Installment Transactions List -->
                <div class="card">
                    <div class="card-header">
                        <h5>All Installment Transactions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Lease ID</th>
                                        <th>Client</th>
                                        <th>Outlet</th>
                                        <th>Transaction Date</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Reference No</th>
                                        <th>Created By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($transactions_result && $transactions_result->num_rows > 0): ?>
                                        <?php while ($transaction = $transactions_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($transaction['collection_id']); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['original_lease_id']); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['client_name']); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['outlet_name']); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['collection_date']); ?></td>
                                                <td>$<?php echo number_format($transaction['amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['payment_method']); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['reference_no']); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['created_by_name']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No installment transactions found.</td>
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