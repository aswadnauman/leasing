<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Handle form submission for adding lease termination
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_termination'])) {
        // Add new lease termination
        $termination_id = $_POST['termination_id'];
        $lease_id = $_POST['lease_id'];
        $outlet_id = $_POST['outlet_id'];
        $termination_date = $_POST['termination_date'];
        $reason = $_POST['reason'];
        $termination_fee = $_POST['termination_fee'] ?? 0;
        $refund_amount = $_POST['refund_amount'] ?? 0;
        $remarks = $_POST['remarks'] ?? '';
        $created_by = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("INSERT INTO lease_terminations (termination_id, lease_id, outlet_id, termination_date, reason, termination_fee, refund_amount, remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssdss", $termination_id, $lease_id, $outlet_id, $termination_date, $reason, $termination_fee, $refund_amount, $remarks, $created_by);
        
        if ($stmt->execute()) {
            $success = "Lease termination added successfully!";
        } else {
            $error = "Error adding lease termination: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all lease terminations with related data
$terminations_result = $conn->query("
    SELECT lt.*, l.lease_id as original_lease_id, o.outlet_name, u.username as created_by_name
    FROM lease_terminations lt
    JOIN leases l ON lt.lease_id = l.lease_id
    JOIN outlets o ON lt.outlet_id = o.outlet_id
    JOIN users u ON lt.created_by = u.user_id
    ORDER BY lt.created_at DESC
");

// Fetch leases for dropdown
$leases_result = $conn->query("SELECT lease_id FROM leases WHERE status != 'Closed' AND status != 'Cancelled' ORDER BY lease_id");

// Fetch outlets for dropdown
$outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lease Terminations - Lease Management System</title>
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
                <h2>Lease Terminations Management</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Add Lease Termination Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Add New Lease Termination</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="termination_id" class="form-label">Termination ID *</label>
                                        <input type="text" class="form-control" id="termination_id" name="termination_id" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="lease_id" class="form-label">Original Lease ID *</label>
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
                                        <label for="termination_date" class="form-label">Termination Date *</label>
                                        <input type="date" class="form-control" id="termination_date" name="termination_date" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="termination_fee" class="form-label">Termination Fee</label>
                                        <input type="number" class="form-control" id="termination_fee" name="termination_fee" step="0.01" value="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="refund_amount" class="form-label">Refund Amount</label>
                                        <input type="number" class="form-control" id="refund_amount" name="refund_amount" step="0.01" value="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason *</label>
                                <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="remarks" class="form-label">Remarks</label>
                                <textarea class="form-control" id="remarks" name="remarks" rows="2"></textarea>
                            </div>
                            
                            <button type="submit" name="add_termination" class="btn btn-primary">Add Lease Termination</button>
                        </form>
                    </div>
                </div>
                
                <!-- Lease Terminations List -->
                <div class="card">
                    <div class="card-header">
                        <h5>All Lease Terminations</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Termination ID</th>
                                        <th>Original Lease</th>
                                        <th>Outlet</th>
                                        <th>Termination Date</th>
                                        <th>Reason</th>
                                        <th>Termination Fee</th>
                                        <th>Refund Amount</th>
                                        <th>Created By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($terminations_result && $terminations_result->num_rows > 0): ?>
                                        <?php while ($termination = $terminations_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($termination['termination_id']); ?></td>
                                                <td><?php echo htmlspecialchars($termination['original_lease_id']); ?></td>
                                                <td><?php echo htmlspecialchars($termination['outlet_name']); ?></td>
                                                <td><?php echo htmlspecialchars($termination['termination_date']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($termination['reason'], 0, 50)) . (strlen($termination['reason']) > 50 ? '...' : ''); ?></td>
                                                <td>$<?php echo number_format($termination['termination_fee'], 2); ?></td>
                                                <td>$<?php echo number_format($termination['refund_amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($termination['created_by_name']); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No lease terminations found.</td>
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