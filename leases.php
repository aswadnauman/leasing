<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Handle form submission for adding/updating leases
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_lease'])) {
        // Add new lease
        $lease_id = $_POST['lease_id'];
        $client_id = $_POST['client_id'];
        $guarantor_id = $_POST['guarantor_id'] ?? null;
        $recovery_person_id = $_POST['recovery_person_id'];
        $product_id = $_POST['product_id'];
        $outlet_id = $_POST['outlet_id'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $total_amount = $_POST['total_amount'];
        $installment_amount = $_POST['installment_amount'];
        $number_of_installments = $_POST['number_of_installments'];
        $outstanding_amount = $_POST['outstanding_amount'];
        
        $stmt = $conn->prepare("INSERT INTO leases (lease_id, client_id, guarantor_id, recovery_person_id, product_id, outlet_id, start_date, end_date, total_amount, installment_amount, number_of_installments, outstanding_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssddds", $lease_id, $client_id, $guarantor_id, $recovery_person_id, $product_id, $outlet_id, $start_date, $end_date, $total_amount, $installment_amount, $number_of_installments, $outstanding_amount);
        
        if ($stmt->execute()) {
            // Update product status to 'Leased'
            $update_product_stmt = $conn->prepare("UPDATE products SET status='Leased' WHERE product_id=?");
            $update_product_stmt->bind_param("s", $product_id);
            $update_product_stmt->execute();
            $update_product_stmt->close();
            
            // Generate installment payments
            generateInstallmentPayments($conn, $lease_id, $start_date, $end_date, $number_of_installments, $installment_amount);
            
            $success = "Lease added successfully!";
        } else {
            $error = "Error adding lease: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_lease'])) {
        // Update lease
        $id = $_POST['id'];
        $status = $_POST['status'];
        $paid_installments = $_POST['paid_installments'];
        $outstanding_amount = $_POST['outstanding_amount'];
        $late_fee = $_POST['late_fee'];
        $discount = $_POST['discount'];
        
        $stmt = $conn->prepare("UPDATE leases SET status=?, paid_installments=?, outstanding_amount=?, late_fee=?, discount=? WHERE id=?");
        $stmt->bind_param("siddii", $status, $paid_installments, $outstanding_amount, $late_fee, $discount, $id);
        
        if ($stmt->execute()) {
            $success = "Lease updated successfully!";
        } else {
            $error = "Error updating lease: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_lease'])) {
        // Delete lease
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM leases WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = "Lease deleted successfully!";
        } else {
            $error = "Error deleting lease: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle search parameters
$search_lease_id = isset($_GET['search_lease_id']) ? $_GET['search_lease_id'] : '';
$search_client = isset($_GET['search_client']) ? $_GET['search_client'] : '';
$search_product = isset($_GET['search_product']) ? $_GET['search_product'] : '';
$search_outlet = isset($_GET['search_outlet']) ? $_GET['search_outlet'] : '';
$search_status = isset($_GET['search_status']) ? $_GET['search_status'] : '';
$search_start_date = isset($_GET['search_start_date']) ? $_GET['search_start_date'] : '';
$search_end_date = isset($_GET['search_end_date']) ? $_GET['search_end_date'] : '';
$search_recovery_person = isset($_GET['search_recovery_person']) ? $_GET['search_recovery_person'] : '';
$search_guarantor = isset($_GET['search_guarantor']) ? $_GET['search_guarantor'] : '';

// Build search query
$search_conditions = [];
$params = [];
$types = "";

if (!empty($search_lease_id)) {
    $search_conditions[] = "l.lease_id LIKE ?";
    $params[] = "%$search_lease_id%";
    $types .= "s";
}

if (!empty($search_client)) {
    $search_conditions[] = "(c.full_name LIKE ? OR c.client_id LIKE ?)";
    $params[] = "%$search_client%";
    $params[] = "%$search_client%";
    $types .= "ss";
}

if (!empty($search_product)) {
    $search_conditions[] = "(p.product_name LIKE ? OR lp.product_names LIKE ?)";
    $params[] = "%$search_product%";
    $params[] = "%$search_product%";
    $types .= "ss";
}

if (!empty($search_outlet)) {
    $search_conditions[] = "o.outlet_name LIKE ?";
    $params[] = "%$search_outlet%";
    $types .= "s";
}

if (!empty($search_status)) {
    $search_conditions[] = "l.status = ?";
    $params[] = $search_status;
    $types .= "s";
}

if (!empty($search_recovery_person)) {
    $search_conditions[] = "u.username LIKE ?";
    $params[] = "%$search_recovery_person%";
    $types .= "s";
}

if (!empty($search_guarantor)) {
    $search_conditions[] = "g.full_name LIKE ?";
    $params[] = "%$search_guarantor%";
    $types .= "s";
}

if (!empty($search_start_date)) {
    $search_conditions[] = "l.start_date >= ?";
    $params[] = $search_start_date;
    $types .= "s";
}

if (!empty($search_end_date)) {
    $search_conditions[] = "l.end_date <= ?";
    $params[] = $search_end_date;
    $types .= "s";
}

// Base query with subquery to get product names for leases with multiple products
$base_query = "
    SELECT l.*, 
           c.full_name as client_name,
           g.full_name as guarantor_name,
           u.username as recovery_person_name,
           o.outlet_name,
           COALESCE(lp.product_names, p.product_name) as product_name,
           p.product_id as product_id
    FROM leases l
    JOIN clients c ON l.client_id = c.client_id
    LEFT JOIN clients g ON l.guarantor_id = g.client_id
    LEFT JOIN users u ON l.recovery_person_id = u.user_id
    JOIN outlets o ON l.outlet_id = o.outlet_id
    JOIN products p ON l.product_id = p.product_id
    LEFT JOIN (
        SELECT lease_id, GROUP_CONCAT(pr.product_name SEPARATOR ', ') as product_names
        FROM lease_products lp
        JOIN products pr ON lp.product_id = pr.product_id
        GROUP BY lease_id
    ) lp ON l.lease_id = lp.lease_id
";

// Add search conditions
if (!empty($search_conditions)) {
    $base_query .= " WHERE " . implode(" AND ", $search_conditions);
}

// Add ordering
$base_query .= " ORDER BY l.created_at DESC";

// Prepare and execute query
if (!empty($params)) {
    $leases_stmt = $conn->prepare($base_query);
    $leases_stmt->bind_param($types, ...$params);
    $leases_stmt->execute();
    $leases_result = $leases_stmt->get_result();
} else {
    $leases_result = $conn->query($base_query);
}

// Fetch clients for dropdown (with search)
$client_search = isset($_GET['client_search']) ? $_GET['client_search'] : '';
if (!empty($client_search)) {
    $clients_stmt = $conn->prepare("SELECT client_id, full_name FROM clients WHERE status='Active' AND (full_name LIKE ? OR client_id LIKE ?) ORDER BY full_name");
    $search_param = "%$client_search%";
    $clients_stmt->bind_param("ss", $search_param, $search_param);
    $clients_stmt->execute();
    $clients_result = $clients_stmt->get_result();
} else {
    $clients_result = $conn->query("SELECT client_id, full_name FROM clients WHERE status='Active' ORDER BY full_name");
}

// Fetch recovery officers for dropdown
$recovery_officers_result = $conn->query("SELECT user_id, username FROM users WHERE role='RecoveryOfficer' AND is_active=1");

// Fetch outlets for dropdown (with search)
$outlet_search = isset($_GET['outlet_search']) ? $_GET['outlet_search'] : '';
if (!empty($outlet_search)) {
    $outlets_stmt = $conn->prepare("SELECT outlet_id, outlet_name FROM outlets WHERE outlet_name LIKE ? ORDER BY outlet_name");
    $search_param = "%$outlet_search%";
    $outlets_stmt->bind_param("s", $search_param);
    $outlets_stmt->execute();
    $outlets_result = $outlets_stmt->get_result();
} else {
    $outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets");
}

// Fetch products for dropdown (with search) - only available products
$product_search = isset($_GET['product_search']) ? $_GET['product_search'] : '';
if (!empty($product_search)) {
    $products_stmt = $conn->prepare("SELECT product_id, product_name FROM products WHERE status='Available' AND product_name LIKE ? ORDER BY product_name");
    $search_param = "%$product_search%";
    $products_stmt->bind_param("s", $search_param);
    $products_stmt->execute();
    $products_result = $products_stmt->get_result();
} else {
    $products_result = $conn->query("SELECT product_id, product_name FROM products WHERE status='Available' ORDER BY product_name");
}

// Fetch master data for search filters
$master_clients_result = $conn->query("SELECT DISTINCT client_id, full_name FROM clients WHERE status='Active' ORDER BY full_name LIMIT 20");
$master_products_result = $conn->query("SELECT DISTINCT product_id, product_name FROM products WHERE status='Available' ORDER BY product_name LIMIT 20");
$master_outlets_result = $conn->query("SELECT DISTINCT outlet_id, outlet_name FROM outlets ORDER BY outlet_name LIMIT 20");
$master_recovery_persons_result = $conn->query("SELECT DISTINCT user_id, username FROM users WHERE role='RecoveryOfficer' AND is_active=1 ORDER BY username LIMIT 20");
$master_guarantors_result = $conn->query("SELECT DISTINCT client_id, full_name FROM clients WHERE status='Active' ORDER BY full_name LIMIT 20");
$master_statuses = ['Active', 'Overdue', 'Closed', 'Cancelled'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leases - Lease Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
    </style>
</head>
<body>
    <!-- Permanent Sidebar Navigation -->
    <?php include 'includes/permanent_sidebar.php'; ?>

    <div class="main-content" style="margin-left: 250px; padding: 20px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1"><i class="bi bi-file-earmark-text me-2"></i>Lease Management</h1>
                <p class="mb-0 text-muted">Manage lease agreements in the system</p>
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
                
                <!-- Search Filters -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-search me-2"></i>Search Leases</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="search_lease_id" class="form-label">Lease ID</label>
                                <input type="text" class="form-control" id="search_lease_id" name="search_lease_id" value="<?php echo htmlspecialchars($search_lease_id); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search_client" class="form-label">Client</label>
                                <input type="text" class="form-control" id="search_client" name="search_client" value="<?php echo htmlspecialchars($search_client); ?>" placeholder="Name or ID">
                            </div>
                            <div class="col-md-3">
                                <label for="search_product" class="form-label">Product</label>
                                <input type="text" class="form-control" id="search_product" name="search_product" value="<?php echo htmlspecialchars($search_product); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search_outlet" class="form-label">Outlet</label>
                                <input type="text" class="form-control" id="search_outlet" name="search_outlet" value="<?php echo htmlspecialchars($search_outlet); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search_status" class="form-label">Status</label>
                                <select class="form-select" id="search_status" name="search_status">
                                    <option value="">All Statuses</option>
                                    <?php foreach ($master_statuses as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo ($search_status == $status) ? 'selected' : ''; ?>>
                                            <?php echo $status; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="search_recovery_person" class="form-label">Recovery Person</label>
                                <input type="text" class="form-control" id="search_recovery_person" name="search_recovery_person" value="<?php echo htmlspecialchars($search_recovery_person); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search_guarantor" class="form-label">Guarantor</label>
                                <input type="text" class="form-control" id="search_guarantor" name="search_guarantor" value="<?php echo htmlspecialchars($search_guarantor); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search_start_date" class="form-label">Start Date From</label>
                                <input type="date" class="form-control" id="search_start_date" name="search_start_date" value="<?php echo htmlspecialchars($search_start_date); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="search_end_date" class="form-label">End Date To</label>
                                <input type="date" class="form-control" id="search_end_date" name="search_end_date" value="<?php echo htmlspecialchars($search_end_date); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button>
                                    <a href="leases.php" class="btn btn-secondary"><i class="bi bi-x-circle me-1"></i>Clear</a>
                                    <button type="button" class="btn btn-success" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Add Lease Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-file-earmark-plus me-2"></i>Add New Lease</h5>
                    </div>
                    <div class="card-body">
                        <p>Use the dedicated lease registration form to add a new lease agreement:</p>
                        <a href="lease_registration.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Register New Lease
                        </a>
                    </div>
                </div>
                
                <!-- Print Header (visible only when printing) -->
                <div class="print-header" style="display: none;">
                    <h2>Lease Management Report</h2>
                    <p>
                        <?php if (!empty($search_lease_id)): ?>Lease ID: <?php echo htmlspecialchars($search_lease_id); ?><?php endif; ?>
                        <?php if (!empty($search_client)): ?>Client: <?php echo htmlspecialchars($search_client); ?><?php endif; ?>
                        <?php if (!empty($search_product)): ?>Product: <?php echo htmlspecialchars($search_product); ?><?php endif; ?>
                        <?php if (!empty($search_outlet)): ?>Outlet: <?php echo htmlspecialchars($search_outlet); ?><?php endif; ?>
                        <?php if (!empty($search_status)): ?>Status: <?php echo htmlspecialchars($search_status); ?><?php endif; ?>
                        <?php if (!empty($search_recovery_person)): ?>Recovery Person: <?php echo htmlspecialchars($search_recovery_person); ?><?php endif; ?>
                        <?php if (!empty($search_guarantor)): ?>Guarantor: <?php echo htmlspecialchars($search_guarantor); ?><?php endif; ?>
                        <?php if (!empty($search_start_date)): ?>Start Date From: <?php echo htmlspecialchars($search_start_date); ?><?php endif; ?>
                        <?php if (!empty($search_end_date)): ?>End Date To: <?php echo htmlspecialchars($search_end_date); ?><?php endif; ?>
                    </p>
                </div>
                
                <!-- Leases List -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-list me-2"></i>All Leases</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped print-table">
                                <thead>
                                    <tr>
                                        <th>Lease ID</th>
                                        <th>Client</th>
                                        <th>Guarantor</th>
                                        <th>Product(s)</th>
                                        <th>Recovery Person</th>
                                        <th>Outlet</th>
                                        <th>Amount</th>
                                        <th>Paid</th>
                                        <th>Outstanding</th>
                                        <th>Status</th>
                                        <th class="no-print">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($leases_result && $leases_result->num_rows > 0): ?>
                                        <?php while ($lease = $leases_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($lease['lease_id']); ?></td>
                                                <td><?php echo htmlspecialchars($lease['client_name']); ?></td>
                                                <td><?php echo htmlspecialchars($lease['guarantor_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($lease['product_name']); ?></td>
                                                <td><?php echo htmlspecialchars($lease['recovery_person_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($lease['outlet_name']); ?></td>
                                                <td>$<?php echo number_format($lease['total_amount'], 2); ?></td>
                                                <td><?php echo $lease['paid_installments']; ?>/<?php echo $lease['number_of_installments']; ?></td>
                                                <td>$<?php echo number_format($lease['outstanding_amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $lease['status'] == 'Active' ? 'success' : 
                                                            ($lease['status'] == 'Overdue' ? 'danger' : 
                                                            ($lease['status'] == 'Closed' ? 'secondary' : 'warning')); 
                                                    ?>">
                                                        <?php echo htmlspecialchars($lease['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="no-print">
                                                    <a href="lease_details.php?id=<?php echo urlencode($lease['lease_id']); ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-primary edit-btn" 
                                                            data-id="<?php echo $lease['id']; ?>"
                                                            data-lease_id="<?php echo htmlspecialchars($lease['lease_id']); ?>"
                                                            data-client_id="<?php echo htmlspecialchars($lease['client_id']); ?>"
                                                            data-guarantor_id="<?php echo htmlspecialchars($lease['guarantor_id'] ?? ''); ?>"
                                                            data-recovery_person_id="<?php echo htmlspecialchars($lease['recovery_person_id'] ?? ''); ?>"
                                                            data-product_id="<?php echo htmlspecialchars($lease['product_id']); ?>"
                                                            data-outlet_id="<?php echo htmlspecialchars($lease['outlet_id']); ?>"
                                                            data-start_date="<?php echo $lease['start_date']; ?>"
                                                            data-end_date="<?php echo $lease['end_date']; ?>"
                                                            data-total_amount="<?php echo $lease['total_amount']; ?>"
                                                            data-installment_amount="<?php echo $lease['installment_amount']; ?>"
                                                            data-number_of_installments="<?php echo $lease['number_of_installments']; ?>"
                                                            data-paid_installments="<?php echo $lease['paid_installments']; ?>"
                                                            data-outstanding_amount="<?php echo $lease['outstanding_amount']; ?>"
                                                            data-status="<?php echo $lease['status']; ?>"
                                                            data-late_fee="<?php echo $lease['late_fee']; ?>"
                                                            data-discount="<?php echo $lease['discount']; ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger delete-btn" 
                                                            data-id="<?php echo $lease['id']; ?>"
                                                            data-lease_id="<?php echo htmlspecialchars($lease['lease_id']); ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="11" class="text-center">No leases found.</td>
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

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Lease</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit-id" name="id">
                        <div class="mb-3">
                            <label for="edit-status" class="form-label">Status</label>
                            <select class="form-select" id="edit-status" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Overdue">Overdue</option>
                                <option value="Closed">Closed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit-paid_installments" class="form-label">Paid Installments</label>
                            <input type="number" class="form-control" id="edit-paid_installments" name="paid_installments" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-outstanding_amount" class="form-label">Outstanding Amount</label>
                            <input type="number" class="form-control" id="edit-outstanding_amount" name="outstanding_amount" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-late_fee" class="form-label">Late Fee</label>
                            <input type="number" class="form-control" id="edit-late_fee" name="late_fee" step="0.01" value="0">
                        </div>
                        <div class="mb-3">
                            <label for="edit-discount" class="form-label">Discount</label>
                            <input type="number" class="form-control" id="edit-discount" name="discount" step="0.01" value="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_lease" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Lease</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="delete-id" name="id">
                        <p>Are you sure you want to delete lease <strong id="delete-lease-id"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_lease" class="btn btn-danger">Delete</button>
                    </div>
                </form>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2
            $('.select2-client').select2({
                placeholder: "Search and select client...",
                allowClear: true
            });
            
            $('.select2-product').select2({
                placeholder: "Search and select product...",
                allowClear: true
            });
            
            $('.select2-outlet').select2({
                placeholder: "Search and select outlet...",
                allowClear: true
            });
        });
        
        // Edit button functionality
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const lease_id = this.getAttribute('data-lease_id');
                const client_id = this.getAttribute('data-client_id');
                const guarantor_id = this.getAttribute('data-guarantor_id');
                const recovery_person_id = this.getAttribute('data-recovery_person_id');
                const product_id = this.getAttribute('data-product_id');
                const outlet_id = this.getAttribute('data-outlet_id');
                const start_date = this.getAttribute('data-start_date');
                const end_date = this.getAttribute('data-end_date');
                const total_amount = this.getAttribute('data-total_amount');
                const installment_amount = this.getAttribute('data-installment_amount');
                const number_of_installments = this.getAttribute('data-number_of_installments');
                const paid_installments = this.getAttribute('data-paid_installments');
                const outstanding_amount = this.getAttribute('data-outstanding_amount');
                const status = this.getAttribute('data-status');
                const late_fee = this.getAttribute('data-late_fee');
                const discount = this.getAttribute('data-discount');
                
                // Fill modal fields
                document.getElementById('edit-id').value = id;
                document.getElementById('edit-status').value = status;
                document.getElementById('edit-paid_installments').value = paid_installments;
                document.getElementById('edit-outstanding_amount').value = outstanding_amount;
                document.getElementById('edit-late_fee').value = late_fee;
                document.getElementById('edit-discount').value = discount;
                
                // Set modal title
                document.querySelector('.modal-title').textContent = 'Edit Lease - ' + lease_id;
                
                var editModal = new bootstrap.Modal(document.getElementById('editModal'));
                editModal.show();
            });
        });
        
        // Delete button functionality
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const lease_id = this.getAttribute('data-lease_id');
                
                document.getElementById('delete-id').value = id;
                document.getElementById('delete-lease-id').textContent = lease_id;
                
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();
            });
        });
    </script>
</body>
</html>