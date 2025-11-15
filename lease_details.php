<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get lease ID from URL parameter
$lease_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$lease_id) {
    header("Location: leases.php");
    exit();
}

$conn = getDBConnection();

// Fetch lease details with related data
$stmt = $conn->prepare("
    SELECT l.*, 
           c.full_name as client_name, c.cnic as client_cnic, c.mobile_primary as client_mobile, c.address_current as client_address,
           g.full_name as guarantor_name, g.cnic as guarantor_cnic, g.mobile_primary as guarantor_mobile, g.address_current as guarantor_address,
           u.username as recovery_person_name,
           o.outlet_name, o.address as outlet_address, o.phone as outlet_phone,
           p.product_name, p.description as product_description, p.category as product_category, p.brand as product_brand, p.model as product_model
    FROM leases l
    JOIN clients c ON l.client_id = c.client_id
    LEFT JOIN clients g ON l.guarantor_id = g.client_id
    LEFT JOIN users u ON l.recovery_person_id = u.user_id
    JOIN outlets o ON l.outlet_id = o.outlet_id
    JOIN products p ON l.product_id = p.product_id
    WHERE l.lease_id = ?
");
$stmt->bind_param("s", $lease_id);
$stmt->execute();
$lease_result = $stmt->get_result();
$lease = $lease_result->fetch_assoc();

if (!$lease) {
    header("Location: leases.php");
    exit();
}

// Fetch installment schedule for this lease
$installment_stmt = $conn->prepare("SELECT * FROM installment_schedule WHERE lease_id = ? ORDER BY installment_number");
$installment_stmt->bind_param("s", $lease_id);
$installment_stmt->execute();
$installments_result = $installment_stmt->get_result();
$installments = [];
while ($row = $installments_result->fetch_assoc()) {
    $installments[] = $row;
}

// Fetch all products for this lease (in case of multiple products)
$products_stmt = $conn->prepare("
    SELECT lp.*, p.product_name, p.description, p.category, p.brand, p.model
    FROM lease_products lp
    JOIN products p ON lp.product_id = p.product_id
    WHERE lp.lease_id = ?
    ORDER BY lp.id
");
$products_stmt->bind_param("s", $lease_id);
$products_stmt->execute();
$products_result = $products_stmt->get_result();
$lease_products = [];
while ($row = $products_result->fetch_assoc()) {
    $lease_products[] = $row;
}

$stmt->close();
$installment_stmt->close();
$products_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lease Details - <?php echo htmlspecialchars($lease['lease_id']); ?> - Lease Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .print-header {
                text-align: center;
                margin-bottom: 20px;
            }
            .print-table {
                width: 100%;
                border-collapse: collapse;
            }
            .print-table th, .print-table td {
                border: 1px solid #000;
                padding: 8px;
                text-align: left;
            }
            .print-table th {
                background-color: #f2f2f2;
            }
        }
        .installment-table th, .installment-table td {
            text-align: center;
        }
        .lease-section {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .section-title {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Permanent Sidebar Navigation -->
    <?php include 'includes/permanent_sidebar.php'; ?>

    <div class="main-content" style="margin-left: 250px; padding: 20px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1"><i class="bi bi-file-earmark-text me-2"></i>Lease Details</h1>
                <p class="mb-0 text-muted">Lease ID: <?php echo htmlspecialchars($lease['lease_id']); ?></p>
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

        <div class="row">
            <!-- Lease Information -->
            <div class="col-md-12">
                <div class="lease-section">
                    <h4 class="section-title">Lease Information</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th>Lease ID:</th>
                                    <td><?php echo htmlspecialchars($lease['lease_id']); ?></td>
                                </tr>
                                <tr>
                                    <th>Agreement Date:</th>
                                    <td><?php echo date('F j, Y', strtotime($lease['agreement_date'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Start Date:</th>
                                    <td><?php echo date('F j, Y', strtotime($lease['start_date'])); ?></td>
                                </tr>
                                <tr>
                                    <th>End Date:</th>
                                    <td><?php echo date('F j, Y', strtotime($lease['end_date'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $lease['status'] == 'Active' ? 'success' : 
                                                ($lease['status'] == 'Overdue' ? 'danger' : 
                                                ($lease['status'] == 'Closed' ? 'secondary' : 'warning')); 
                                        ?>">
                                            <?php echo htmlspecialchars($lease['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th>Total Amount:</th>
                                    <td>$<?php echo number_format($lease['total_amount'], 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Installment Amount:</th>
                                    <td>$<?php echo number_format($lease['installment_amount'], 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Number of Installments:</th>
                                    <td><?php echo $lease['number_of_installments']; ?></td>
                                </tr>
                                <tr>
                                    <th>Paid Installments:</th>
                                    <td><?php echo $lease['paid_installments']; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Client Information -->
            <div class="col-md-6">
                <div class="lease-section">
                    <h4 class="section-title">Client Information</h4>
                    <table class="table table-borderless">
                        <tr>
                            <th>Name:</th>
                            <td><?php echo htmlspecialchars($lease['client_name']); ?></td>
                        </tr>
                        <tr>
                            <th>CNIC:</th>
                            <td><?php echo htmlspecialchars($lease['client_cnic']); ?></td>
                        </tr>
                        <tr>
                            <th>Mobile:</th>
                            <td><?php echo htmlspecialchars($lease['client_mobile']); ?></td>
                        </tr>
                        <tr>
                            <th>Address:</th>
                            <td><?php echo htmlspecialchars($lease['client_address']); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Guarantor Information -->
            <div class="col-md-6">
                <div class="lease-section">
                    <h4 class="section-title">Guarantor Information</h4>
                    <?php if ($lease['guarantor_name']): ?>
                        <table class="table table-borderless">
                            <tr>
                                <th>Name:</th>
                                <td><?php echo htmlspecialchars($lease['guarantor_name']); ?></td>
                            </tr>
                            <tr>
                                <th>CNIC:</th>
                                <td><?php echo htmlspecialchars($lease['guarantor_cnic']); ?></td>
                            </tr>
                            <tr>
                                <th>Mobile:</th>
                                <td><?php echo htmlspecialchars($lease['guarantor_mobile']); ?></td>
                            </tr>
                            <tr>
                                <th>Address:</th>
                                <td><?php echo htmlspecialchars($lease['guarantor_address']); ?></td>
                            </tr>
                        </table>
                    <?php else: ?>
                        <p>No guarantor assigned to this lease.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Products Information -->
            <div class="col-md-12">
                <div class="lease-section">
                    <h4 class="section-title">Leased Product(s)</h4>
                    <?php if (!empty($lease_products)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Category</th>
                                        <th>Brand</th>
                                        <th>Model</th>
                                        <th>Description</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Total Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lease_products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                                            <td><?php echo htmlspecialchars($product['brand']); ?></td>
                                            <td><?php echo htmlspecialchars($product['model']); ?></td>
                                            <td><?php echo htmlspecialchars($product['description']); ?></td>
                                            <td><?php echo $product['quantity']; ?></td>
                                            <td>$<?php echo number_format($product['unit_price'], 2); ?></td>
                                            <td>$<?php echo number_format($product['total_price'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <!-- Fallback to single product if no lease_products records exist -->
                        <table class="table table-borderless">
                            <tr>
                                <th>Product Name:</th>
                                <td><?php echo htmlspecialchars($lease['product_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Category:</th>
                                <td><?php echo htmlspecialchars($lease['product_category']); ?></td>
                            </tr>
                            <tr>
                                <th>Brand:</th>
                                <td><?php echo htmlspecialchars($lease['product_brand']); ?></td>
                            </tr>
                            <tr>
                                <th>Model:</th>
                                <td><?php echo htmlspecialchars($lease['product_model']); ?></td>
                            </tr>
                            <tr>
                                <th>Description:</th>
                                <td><?php echo htmlspecialchars($lease['product_description']); ?></td>
                            </tr>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Outlet Information -->
            <div class="col-md-6">
                <div class="lease-section">
                    <h4 class="section-title">Outlet Information</h4>
                    <table class="table table-borderless">
                        <tr>
                            <th>Outlet Name:</th>
                            <td><?php echo htmlspecialchars($lease['outlet_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Address:</th>
                            <td><?php echo htmlspecialchars($lease['outlet_address']); ?></td>
                        </tr>
                        <tr>
                            <th>Phone:</th>
                            <td><?php echo htmlspecialchars($lease['outlet_phone']); ?></td>
                        </tr>
                        <tr>
                            <th>Recovery Person:</th>
                            <td><?php echo htmlspecialchars($lease['recovery_person_name'] ?? 'N/A'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="col-md-6">
                <div class="lease-section">
                    <h4 class="section-title">Financial Summary</h4>
                    <table class="table table-borderless">
                        <tr>
                            <th>Total Amount:</th>
                            <td>$<?php echo number_format($lease['total_amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <th>Down Payment:</th>
                            <td>$<?php echo number_format($lease['down_payment'] ?? 0, 2); ?></td>
                        </tr>
                        <tr>
                            <th>Security Deposit:</th>
                            <td>$<?php echo number_format($lease['security_deposit'] ?? 0, 2); ?></td>
                        </tr>
                        <tr>
                            <th>Paid Amount:</th>
                            <td>$<?php echo number_format(($lease['total_amount'] - $lease['outstanding_amount']), 2); ?></td>
                        </tr>
                        <tr>
                            <th>Outstanding Amount:</th>
                            <td>$<?php echo number_format($lease['outstanding_amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <th>Late Fees:</th>
                            <td>$<?php echo number_format($lease['late_fee'], 2); ?></td>
                        </tr>
                        <tr>
                            <th>Discounts:</th>
                            <td>$<?php echo number_format($lease['discount'], 2); ?></td>
                        </tr>
                        <tr>
                            <th>Net Outstanding:</th>
                            <td>$<?php echo number_format($lease['outstanding_amount'] + $lease['late_fee'] - $lease['discount'], 2); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Installment Schedule -->
            <div class="col-md-12">
                <div class="lease-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="section-title mb-0">Installment Schedule</h4>
                        <button type="button" class="btn btn-success no-print" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i>Print Schedule
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped installment-table">
                            <thead>
                                <tr>
                                    <th>Installment #</th>
                                    <th>Due Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Payment Date</th>
                                    <th>Remarks</th>
                                    <th class="no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($installments) > 0): ?>
                                    <?php foreach ($installments as $installment): ?>
                                        <tr>
                                            <td><?php echo $installment['installment_number']; ?></td>
                                            <td><?php echo date('F j, Y', strtotime($installment['due_date'])); ?></td>
                                            <td>$<?php echo number_format($installment['amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $installment['status'] == 'Paid' ? 'success' : 
                                                        ($installment['status'] == 'Overdue' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo htmlspecialchars($installment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($installment['payment_date']) {
                                                    echo date('F j, Y', strtotime($installment['payment_date']));
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($installment['remarks'] ?? 'N/A'); ?></td>
                                            <td class="no-print">
                                                <?php if ($installment['status'] == 'Pending' || $installment['status'] == 'Overdue'): ?>
                                                    <button class="btn btn-sm btn-primary">Record Payment</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- Generate installment schedule if no data exists -->
                                    <?php
                                    // Generate installment schedule
                                    $start = new DateTime($lease['start_date']);
                                    $end = new DateTime($lease['end_date']);
                                    $intervalDays = ceil(($end->getTimestamp() - $start->getTimestamp()) / (60 * 60 * 24) / $lease['number_of_installments']);
                                    
                                    $currentDate = clone $start;
                                    $paidInstallments = $lease['paid_installments'];
                                    
                                    for ($i = 1; $i <= $lease['number_of_installments']; $i++) {
                                        $dueDate = clone $currentDate;
                                        $dueDate->modify("+$intervalDays days");
                                        
                                        $status = 'Pending';
                                        if ($i <= $paidInstallments) {
                                            $status = 'Paid';
                                        } else if ($dueDate < new DateTime()) {
                                            $status = 'Overdue';
                                        }
                                        
                                        echo "<tr>";
                                        echo "<td>$i</td>";
                                        echo "<td>" . $dueDate->format('F j, Y') . "</td>";
                                        echo "<td>$" . number_format($lease['installment_amount'], 2) . "</td>";
                                        echo "<td><span class='badge bg-" . 
                                            ($status == 'Paid' ? 'success' : ($status == 'Overdue' ? 'danger' : 'warning')) . 
                                            "'>$status</span></td>";
                                        echo "<td>N/A</td>";
                                        echo "<td>N/A</td>";
                                        echo "<td class='no-print'>";
                                        if ($status == 'Pending' || $status == 'Overdue') {
                                            echo "<button class='btn btn-sm btn-primary'>Record Payment</button>";
                                        }
                                        echo "</td>";
                                        echo "</tr>";
                                        
                                        $currentDate = $dueDate;
                                    }
                                    ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="no-print mt-4">
            <a href="leases.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Leases</a>
            <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print Lease Details</button>
        </div>
    </div>

    <footer class="bg-light text-center py-4 no-print">
        <div class="container">
            <p class="mb-0">&copy; 2025 Lease Management System. All rights reserved.</p>
            <p class="mb-0 text-muted small">Version 1.0.0</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>