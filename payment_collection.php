<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Handle form submission for recording lease payments
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['record_payment'])) {
        // Record new lease payment
        $payment_id = $_POST['payment_id'];
        $lease_id = $_POST['lease_id'];
        $installment_id = $_POST['installment_id'] ?? null;
        $payment_date = $_POST['payment_date'];
        $amount = $_POST['amount'];
        $payment_method = $_POST['payment_method'];
        $reference_no = $_POST['reference_no'] ?? '';
        $bank_name = $_POST['bank_name'] ?? '';
        $remarks = $_POST['remarks'] ?? '';
        $created_by = $_SESSION['user_id'];
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Insert payment record
            $stmt = $conn->prepare("INSERT INTO lease_payments (payment_id, lease_id, installment_id, payment_date, amount, payment_method, reference_no, bank_name, remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssissssss", $payment_id, $lease_id, $installment_id, $payment_date, $amount, $payment_method, $reference_no, $bank_name, $remarks, $created_by);
            
            if (!$stmt->execute()) {
                throw new Exception("Error recording payment: " . $conn->error);
            }
            $stmt->close();
            
            // Update installment schedule if installment_id is provided
            if ($installment_id) {
                $stmt = $conn->prepare("UPDATE installment_schedule SET paid_amount = paid_amount + ?, payment_date = ?, status = CASE WHEN paid_amount + ? >= amount THEN 'Paid' ELSE 'PartiallyPaid' END WHERE id = ?");
                $stmt->bind_param("dsdi", $amount, $payment_date, $amount, $installment_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error updating installment schedule: " . $conn->error);
                }
                $stmt->close();
            }
            
            // Update lease outstanding amount
            $stmt = $conn->prepare("UPDATE leases SET outstanding_amount = outstanding_amount - ? WHERE lease_id = ?");
            $stmt->bind_param("ds", $amount, $lease_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error updating lease: " . $conn->error);
            }
            $stmt->close();
            
            // Check if lease is fully paid and update status
            $stmt = $conn->prepare("UPDATE leases SET status = CASE WHEN outstanding_amount <= 0 THEN 'Closed' ELSE status END WHERE lease_id = ?");
            $stmt->bind_param("s", $lease_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error updating lease status: " . $conn->error);
            }
            $stmt->close();
            
            // Commit transaction
            $conn->commit();
            $success = "Payment recorded successfully!";
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Handle viewing a single payment (for details)
$payment_detail = null;
if (isset($_GET['view'])) {
    $view_id = $_GET['view'];
    $stmt = $conn->prepare("SELECT lp.*, u.username FROM lease_payments lp LEFT JOIN users u ON lp.created_by = u.user_id WHERE lp.payment_id = ?");
    $stmt->bind_param("s", $view_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $payment_detail = $res->fetch_assoc();
    }
    $stmt->close();
}

// Handle deletion of a payment (with basic reversal of amounts)
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    // Only Admin or AccountsOfficer may delete payments
    $role = $_SESSION['role'] ?? '';
    if ($role == 'Admin' || $role == 'AccountsOfficer') {
        $conn->begin_transaction();
        try {
            // Get payment record
            $stmt = $conn->prepare("SELECT lease_id, installment_id, amount FROM lease_payments WHERE payment_id = ?");
            $stmt->bind_param("s", $delete_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $rec = $result->fetch_assoc();
                $lease_id = $rec['lease_id'];
                $installment_id = $rec['installment_id'];
                $amount = $rec['amount'];

                // Reverse installment paid amount if applicable
                if ($installment_id) {
                    $stmt2 = $conn->prepare("UPDATE installment_schedule SET paid_amount = paid_amount - ?, payment_date = NULL, status = 'Pending' WHERE id = ?");
                    $stmt2->bind_param("di", $amount, $installment_id);
                    $stmt2->execute();
                    $stmt2->close();
                }

                // Reverse lease outstanding amount
                $stmt3 = $conn->prepare("UPDATE leases SET outstanding_amount = outstanding_amount + ? WHERE lease_id = ?");
                $stmt3->bind_param("ds", $amount, $lease_id);
                $stmt3->execute();
                $stmt3->close();

                // Delete payment record
                $stmt4 = $conn->prepare("DELETE FROM lease_payments WHERE payment_id = ?");
                $stmt4->bind_param("s", $delete_id);
                $stmt4->execute();
                $stmt4->close();

                $conn->commit();
                $success = "Payment deleted and amounts reversed successfully.";
                // Redirect to avoid repeated delete on refresh
                header("Location: payment_collection.php");
                exit();
            } else {
                throw new Exception("Payment record not found");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    } else {
        $error = "You do not have permission to delete payments.";
    }
}

// Fetch leases for dropdown
$leases_result = $conn->query("
    SELECT l.lease_id, c.full_name as client_name, p.product_name
    FROM leases l
    JOIN clients c ON l.client_id = c.client_id
    JOIN products p ON l.product_id = p.product_id
    WHERE l.status IN ('Active', 'Overdue')
    ORDER BY l.lease_id
");

// Fetch installment schedules for a selected lease (will be populated via AJAX)
$installments_result = null;
if (isset($_GET['lease_id'])) {
    $lease_id = $_GET['lease_id'];
    $installments_result = $conn->query("
        SELECT id, installment_number, due_date, amount, paid_amount, (amount - paid_amount) as due_amount, status
        FROM installment_schedule
        WHERE lease_id = '$lease_id' AND status != 'Paid'
        ORDER BY installment_number
    ");
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Collection - Lease Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <!-- Permanent Sidebar Navigation -->
    <?php include 'includes/permanent_sidebar.php'; ?>

    <div class="main-content" style="margin-left: 250px; padding: 20px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1"><i class="bi bi-cash-stack me-2"></i>Payment Collection</h1>
                <p class="mb-0 text-muted">Record lease payments and collections</p>
            </div>
            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>

                <!-- Breadcrumb Navigation -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="collections.php">Collections</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Payment Collection</li>
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
                
                <?php if (!empty($payment_detail)): ?>
                <div class="card mb-4 border-info">
                    <div class="card-header bg-info text-white">
                        <strong>Payment Details: <?php echo htmlspecialchars($payment_detail['payment_id']); ?></strong>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4"><strong>Lease ID:</strong> <?php echo htmlspecialchars($payment_detail['lease_id']); ?></div>
                            <div class="col-md-4"><strong>Amount:</strong> ₹<?php echo number_format($payment_detail['amount'],2); ?></div>
                            <div class="col-md-4"><strong>Method:</strong> <?php echo htmlspecialchars($payment_detail['payment_method']); ?></div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-4"><strong>Date:</strong> <?php echo date('M j, Y', strtotime($payment_detail['payment_date'])); ?></div>
                            <div class="col-md-4"><strong>Reference:</strong> <?php echo htmlspecialchars($payment_detail['reference_no']); ?></div>
                            <div class="col-md-4"><strong>Bank:</strong> <?php echo htmlspecialchars($payment_detail['bank_name']); ?></div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12"><strong>Remarks:</strong> <?php echo nl2br(htmlspecialchars($payment_detail['remarks'])); ?></div>
                        </div>
                        <div class="mt-3">
                            <a href="payment_collection.php" class="btn btn-secondary btn-sm">Close</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Payment Collection Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-cash me-2"></i>Record New Payment</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_id" class="form-label">Payment ID *</label>
                                        <input type="text" class="form-control" id="payment_id" name="payment_id" value="PAY<?php echo date('YmdHis'); ?>" required readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_date" class="form-label">Payment Date *</label>
                                        <input type="date" class="form-control" id="payment_date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="lease_id" class="form-label">Lease *</label>
                                        <select class="form-select" id="lease_id" name="lease_id" required onchange="loadInstallments(this.value)">
                                            <option value="">Select Lease</option>
                                            <?php if ($leases_result && $leases_result->num_rows > 0): ?>
                                                <?php while ($lease = $leases_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $lease['lease_id']; ?>" <?php echo (isset($_GET['lease_id']) && $_GET['lease_id'] == $lease['lease_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($lease['lease_id'] . ' - ' . $lease['client_name'] . ' - ' . $lease['product_name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="installment_id" class="form-label">Installment</label>
                                        <select class="form-select" id="installment_id" name="installment_id">
                                            <option value="">Select Installment (Optional)</option>
                                            <?php if ($installments_result && $installments_result->num_rows > 0): ?>
                                                <?php while ($installment = $installments_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $installment['id']; ?>" data-amount="<?php echo $installment['due_amount']; ?>">
                                                        Installment #<?php echo $installment['installment_number']; ?> - Due: <?php echo date('M j, Y', strtotime($installment['due_date'])); ?> - Due Amount: ₹<?php echo number_format($installment['due_amount'], 2); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">Amount *</label>
                                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_method" class="form-label">Payment Method *</label>
                                        <select class="form-select" id="payment_method" name="payment_method" required>
                                            <option value="">Select Payment Method</option>
                                            <option value="Cash">Cash</option>
                                            <option value="BankTransfer">Bank Transfer</option>
                                            <option value="Online">Online Payment</option>
                                            <option value="Cheque">Cheque</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="reference_no" class="form-label">Reference No.</label>
                                        <input type="text" class="form-control" id="reference_no" name="reference_no">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="bank_name" class="form-label">Bank Name</label>
                                        <input type="text" class="form-control" id="bank_name" name="bank_name">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="remarks" class="form-label">Remarks</label>
                                <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" name="record_payment" class="btn btn-primary">
                                <i class="bi bi-cash me-1"></i>Record Payment
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Recent Payments -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-list me-2"></i>Recent Payments</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Lease ID</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Date</th>
                                        <th>Recorded By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch recent payments
                                    $conn = getDBConnection();
                                    $recent_payments_result = $conn->query("
                                        SELECT lp.*, u.username
                                        FROM lease_payments lp
                                        JOIN users u ON lp.created_by = u.user_id
                                        ORDER BY lp.payment_date DESC
                                        LIMIT 10
                                    ");
                                    $conn->close();
                                    
                                    if ($recent_payments_result && $recent_payments_result->num_rows > 0): 
                                        while ($payment = $recent_payments_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($payment['payment_id']); ?></td>
                                                <td><?php echo htmlspecialchars($payment['lease_id']); ?></td>
                                                <td>₹<?php echo number_format($payment['amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($payment['username']); ?></td>
                                                <td>
                                                    <a href="payment_collection.php?view=<?php echo urlencode($payment['payment_id']); ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                    <?php if (in_array($_SESSION['role'] ?? '', ['Admin','AccountsOfficer'])): ?>
                                                        <a href="payment_collection.php?delete=<?php echo urlencode($payment['payment_id']); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this payment? This will attempt to reverse amounts.');">Delete</a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; 
                                    else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No recent payments found</td>
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

    <footer class="bg-light text-center py-4 mt-4">
        <div class="container">
            <p class="mb-0">&copy; 2025 Lease Management System. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-fill amount when installment is selected
        document.getElementById('installment_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const amount = selectedOption.getAttribute('data-amount');
                document.getElementById('amount').value = amount;
            }
        });
        
        // Function to load installments via AJAX (simplified version)
        function loadInstallments(leaseId) {
            if (leaseId) {
                // In a real implementation, this would make an AJAX call to fetch installments
                // For now, we'll just reload the page with the lease_id parameter
                window.location.href = 'payment_collection.php?lease_id=' + leaseId;
            }
        }
    </script>
</body>
</html>