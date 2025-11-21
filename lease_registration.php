<?php
session_start();
require_once 'config/db.php';
require_once 'includes/dynamic_dropdowns.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user has appropriate privileges
if (!in_array($_SESSION['role'], ['Admin', 'BranchManager', 'Sales'])) {
    header("Location: dashboard.php");
    exit();
}

// Handle AJAX search requests for master data
handleMasterDataAjax(getDBConnection());

// Handle AJAX search requests for clients with additional information
if (isset($_GET['action']) && $_GET['action'] == 'search' && isset($_GET['q'])) {
    $conn = getDBConnection();
    $search = $_GET['q'];
    $searchTerm = "%$search%";
    
    $stmt = $conn->prepare("SELECT client_id, full_name, father_husband_name, mobile_primary, address_current FROM clients WHERE full_name LIKE ? OR cnic LIKE ? OR manual_reference_no LIKE ? ORDER BY full_name LIMIT 20");
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $clients = array();
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($clients);
    $conn->close();
    exit();
}

// Handle AJAX search requests for products
if (isset($_GET['action']) && $_GET['action'] == 'search' && isset($_GET['q']) && strpos($_SERVER['HTTP_REFERER'], 'products.php') !== false) {
    $conn = getDBConnection();
    $search = $_GET['q'];
    $searchTerm = "%$search%";
    
    $stmt = $conn->prepare("SELECT product_id, product_name, purchase_price FROM products WHERE status='Available' AND (product_name LIKE ? OR product_id LIKE ? OR serial_number LIKE ?) ORDER BY product_name LIMIT 20");
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = array();
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($products);
    $conn->close();
    exit();
}

$conn = getDBConnection();

// Handle form submission for adding new lease
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_lease'])) {
    // Get form data
    $lease_id = $_POST['lease_id'];
    $client_id = $_POST['client_id'];
    $guarantor_id = $_POST['guarantor_id'] ?? null;
    $recovery_person_id = $_POST['recovery_person_id'];
    $outlet_id = $_POST['outlet_id'];
    $agreement_date = $_POST['agreement_date'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $lease_term_months = $_POST['lease_term_months'];
    $total_amount = $_POST['total_amount'];
    $down_payment = $_POST['down_payment'];
    $security_deposit = $_POST['security_deposit'];
    $installment_amount = $_POST['installment_amount'];
    $number_of_installments = $_POST['number_of_installments'];
    $outstanding_amount = $_POST['outstanding_amount'];
    $late_fee_percentage = $_POST['late_fee_percentage'];
    
    // Get product data (supporting multiple products)
    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $unit_prices = $_POST['unit_price'] ?? [];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // For multiple products, we'll use the first product as the main product in the leases table
        // and store all products in the lease_products table
        $main_product_id = !empty($product_ids) ? $product_ids[0] : null;
        $has_multiple_products = count($product_ids) > 1;
        
        // Insert lease record
        $stmt = $conn->prepare("INSERT INTO leases (lease_id, client_id, guarantor_id, recovery_person_id, product_id, outlet_id, agreement_date, start_date, end_date, total_amount, down_payment, security_deposit, installment_amount, number_of_installments, outstanding_amount, late_fee, has_multiple_products) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssdddiddi", $lease_id, $client_id, $guarantor_id, $recovery_person_id, $main_product_id, $outlet_id, $agreement_date, $start_date, $end_date, $total_amount, $down_payment, $security_deposit, $installment_amount, $number_of_installments, $outstanding_amount, $late_fee_percentage, $has_multiple_products);
        
        if (!$stmt->execute()) {
            throw new Exception("Error adding lease: " . $conn->error);
        }
        $stmt->close();
        
        // Insert lease products
        if (!empty($product_ids)) {
            $product_stmt = $conn->prepare("INSERT INTO lease_products (lease_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            
            for ($i = 0; $i < count($product_ids); $i++) {
                $product_id = $product_ids[$i];
                $quantity = $quantities[$i] ?? 1;
                $unit_price = $unit_prices[$i] ?? 0;
                $total_price = $quantity * $unit_price;
                
                $product_stmt->bind_param("ssidd", $lease_id, $product_id, $quantity, $unit_price, $total_price);
                
                if (!$product_stmt->execute()) {
                    throw new Exception("Error adding lease product: " . $conn->error);
                }
                
                // Update product status to 'Leased'
                $update_stmt = $conn->prepare("UPDATE products SET status='Leased' WHERE product_id=?");
                $update_stmt->bind_param("s", $product_id);
                if (!$update_stmt->execute()) {
                    throw new Exception("Error updating product status: " . $conn->error);
                }
                $update_stmt->close();
            }
            $product_stmt->close();
        }
        
        // Generate installment schedule
        $installment_stmt = $conn->prepare("INSERT INTO installment_schedule (lease_id, installment_number, due_date, amount) VALUES (?, ?, ?, ?)");
        
        // Calculate installment dates
        $current_date = new DateTime($start_date);
        for ($i = 1; $i <= $number_of_installments; $i++) {
            $due_date = clone $current_date;
            $installment_number = $i;
            
            $installment_stmt->bind_param("sids", $lease_id, $installment_number, $due_date->format('Y-m-d'), $installment_amount);
            
            if (!$installment_stmt->execute()) {
                throw new Exception("Error generating installment schedule: " . $conn->error);
            }
            
            // Move to next month
            $current_date->modify('+1 month');
        }
        $installment_stmt->close();
        
        // Commit transaction
        $conn->commit();
        $success = "Lease registered successfully!";
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        $error = $e->getMessage();
    }
}

// Handle transaction search
$search_results = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_transactions'])) {
    $search_term = $_POST['transaction_search'] ?? '';
    
    if (!empty($search_term)) {
        // Search in recovery collections
        $collections_stmt = $conn->prepare("
            SELECT rc.*, u.username as recovery_person_name, o.outlet_name, c.full_name as client_name, l.lease_id as lease_identifier
            FROM recovery_collections rc
            JOIN users u ON rc.recovery_person_id = u.user_id
            JOIN outlets o ON rc.outlet_id = o.outlet_id
            LEFT JOIN clients c ON rc.client_id = c.client_id
            LEFT JOIN leases l ON rc.lease_id = l.lease_id
            WHERE rc.transaction_id LIKE ? OR rc.reference_no LIKE ? OR c.full_name LIKE ? OR l.lease_id LIKE ?
            ORDER BY rc.collection_date DESC
            LIMIT 10
        ");
        $search_param = "%$search_term%";
        $collections_stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
        $collections_stmt->execute();
        $collections_result = $collections_stmt->get_result();
        
        // Search in installment schedule
        $installments_stmt = $conn->prepare("
            SELECT i.*, l.lease_id as lease_identifier, c.full_name as client_name
            FROM installment_schedule i
            JOIN leases l ON i.lease_id = l.lease_id
            JOIN clients c ON l.client_id = c.client_id
            WHERE i.installment_number LIKE ? OR l.lease_id LIKE ? OR c.full_name LIKE ?
            ORDER BY i.due_date DESC
            LIMIT 10
        ");
        $installments_stmt->bind_param("sss", $search_param, $search_param, $search_param);
        $installments_stmt->execute();
        $installments_result = $installments_stmt->get_result();
        
        // Search in lease payments
        $payments_stmt = $conn->prepare("
            SELECT lp.*, l.lease_id as lease_identifier, c.full_name as client_name
            FROM lease_payments lp
            JOIN leases l ON lp.lease_id = l.lease_id
            JOIN clients c ON l.client_id = c.client_id
            WHERE lp.payment_id LIKE ? OR lp.reference_no LIKE ? OR l.lease_id LIKE ? OR c.full_name LIKE ?
            ORDER BY lp.payment_date DESC
            LIMIT 10
        ");
        $payments_stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
        $payments_stmt->execute();
        $payments_result = $payments_stmt->get_result();
        
        // Combine results
        while ($row = $collections_result->fetch_assoc()) {
            $row['type'] = 'Collection';
            $search_results[] = $row;
        }
        
        while ($row = $installments_result->fetch_assoc()) {
            $row['type'] = 'Installment';
            $search_results[] = $row;
        }
        
        while ($row = $payments_result->fetch_assoc()) {
            $row['type'] = 'Payment';
            $search_results[] = $row;
        }
        
        $collections_stmt->close();
        $installments_stmt->close();
        $payments_stmt->close();
    }
}

// Fetch data for dropdowns
$clients_result = $conn->query("SELECT client_id, full_name FROM clients WHERE status='Active' ORDER BY full_name");
$recovery_officers_result = $conn->query("SELECT user_id, username FROM users WHERE role='RecoveryOfficer' AND is_active=1");
$outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets");
$products_result = $conn->query("SELECT product_id, product_name, purchase_price FROM products WHERE status='Available' ORDER BY product_name");

// Get system configuration for default values
$config_result = $conn->query("SELECT config_key, config_value FROM system_config WHERE config_key IN ('default_down_payment_percentage', 'default_security_deposit_months', 'late_fee_percentage')");
$config = [];
while ($row = $config_result->fetch_assoc()) {
    $config[$row['config_key']] = $row['config_value'];
}

// Set default values
$default_down_payment_percentage = $config['default_down_payment_percentage'] ?? 25;
$default_security_deposit_months = $config['default_security_deposit_months'] ?? 3;
$late_fee_percentage = $config['late_fee_percentage'] ?? 5;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lease Registration - Lease Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        .select2-results__option {
            white-space: normal;
            line-height: 1.4;
        }
        .client-info {
            font-size: 0.85em;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Permanent Sidebar Navigation -->
    <?php include 'includes/permanent_sidebar.php'; ?>
    
    <div class="main-content" style="margin-left: 250px; padding: 20px;">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col">
                    <h2 class="text-primary">
                        <i class="bi bi-file-earmark-plus me-2"></i>Lease Registration
                    </h2>
                    <p class="text-muted">Register new lease agreements with clients</p>
                </div>
            </div>
            
            <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-12">
                    <!-- Transaction Search -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-search me-2"></i>Search Existing Transactions</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="transaction_search" placeholder="Search by transaction ID, reference number, client name, or lease ID..." 
                                                   value="<?php echo isset($_POST['transaction_search']) ? htmlspecialchars($_POST['transaction_search']) : ''; ?>">
                                            <input type="hidden" name="search_transactions" value="1">
                                            <button class="btn btn-outline-primary" type="submit">
                                                <i class="bi bi-search"></i> Search
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#transactionInfoModal">
                                            <i class="bi bi-info-circle me-1"></i>Search Help
                                        </button>
                                    </div>
                                </div>
                            </form>
                            
                            <?php if (!empty($search_results)): ?>
                                <div class="mt-4">
                                    <h6><i class="bi bi-list me-2"></i>Search Results</h6>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Type</th>
                                                    <th>Transaction ID</th>
                                                    <th>Client/Lease</th>
                                                    <th>Amount</th>
                                                    <th>Date</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($search_results as $result): ?>
                                                    <tr>
                                                        <td>
                                                            <?php if ($result['type'] == 'Collection'): ?>
                                                                <span class="badge bg-info">Collection</span>
                                                            <?php elseif ($result['type'] == 'Installment'): ?>
                                                                <span class="badge bg-warning">Installment</span>
                                                            <?php elseif ($result['type'] == 'Payment'): ?>
                                                                <span class="badge bg-success">Payment</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            if ($result['type'] == 'Collection') {
                                                                echo htmlspecialchars($result['transaction_id'] ?? $result['reference_no'] ?? 'N/A');
                                                            } elseif ($result['type'] == 'Installment') {
                                                                echo "Installment #" . htmlspecialchars($result['installment_number']);
                                                            } elseif ($result['type'] == 'Payment') {
                                                                echo htmlspecialchars($result['payment_id'] ?? 'N/A');
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            if ($result['type'] == 'Collection') {
                                                                echo htmlspecialchars($result['client_name'] ?? 'N/A') . "<br>" . htmlspecialchars($result['lease_identifier'] ?? 'N/A');
                                                            } elseif ($result['type'] == 'Installment') {
                                                                echo htmlspecialchars($result['client_name']) . "<br>" . htmlspecialchars($result['lease_identifier']);
                                                            } elseif ($result['type'] == 'Payment') {
                                                                echo htmlspecialchars($result['client_name'] ?? 'N/A') . "<br>" . htmlspecialchars($result['lease_identifier'] ?? 'N/A');
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>$<?php echo number_format($result['amount'] ?? 0, 2); ?></td>
                                                        <td>
                                                            <?php 
                                                            if ($result['type'] == 'Collection') {
                                                                echo date('Y-m-d', strtotime($result['collection_date']));
                                                            } elseif ($result['type'] == 'Installment') {
                                                                echo date('Y-m-d', strtotime($result['due_date']));
                                                            } elseif ($result['type'] == 'Payment') {
                                                                echo date('Y-m-d', strtotime($result['payment_date']));
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            if ($result['type'] == 'Collection') {
                                                                echo htmlspecialchars($result['approval_status']);
                                                            } elseif ($result['type'] == 'Installment') {
                                                                echo htmlspecialchars($result['status']);
                                                            } elseif ($result['type'] == 'Payment') {
                                                                echo htmlspecialchars($result['status']);
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                    onclick="useTransactionData('<?php echo $result['type']; ?>', '<?php echo htmlspecialchars(json_encode($result)); ?>')">
                                                                <i class="bi bi-arrow-down-circle"></i> Use
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php elseif (isset($_POST['search_transactions'])): ?>
                                <div class="mt-4">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>No transactions found matching your search criteria.
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Lease Registration Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="bi bi-file-earmark-plus me-2"></i>New Lease Registration</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="leaseRegistrationForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="lease_id" class="form-label">Lease ID *</label>
                                            <input type="text" class="form-control" id="lease_id" name="lease_id" value="L<?php echo date('Ym').rand(1000, 9999); ?>" readonly required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="outlet_id" class="form-label">Outlet *</label>
                                            <select class="form-select select2-master" id="outlet_id" name="outlet_id" required>
                                                <option value="">Select Outlet</option>
                                                <?php if ($outlets_result && $outlets_result->num_rows > 0): ?>
                                                    <?php while ($outlet = $outlets_result->fetch_assoc()): ?>
                                                        <option value="<?php echo $outlet['outlet_id']; ?>" <?php echo (isset($_SESSION['outlet_id']) && $_SESSION['outlet_id'] == $outlet['outlet_id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($outlet['outlet_name']); ?>
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
                                            <label for="client_id" class="form-label">Client *</label>
                                            <select class="form-control select2-client" id="client_id" name="client_id" required>
                                                <option value="">Search and select client...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="guarantor_id" class="form-label">Guarantor</label>
                                            <select class="form-control select2-client" id="guarantor_id" name="guarantor_id">
                                                <option value="">Search and select guarantor (Optional)...</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="recovery_person_id" class="form-label">Recovery Person *</label>
                                            <select class="form-control select2-recovery" id="recovery_person_id" name="recovery_person_id" required>
                                                <option value="">Search and select recovery person...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="agreement_date" class="form-label">Agreement Date *</label>
                                            <input type="date" class="form-control" id="agreement_date" name="agreement_date" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Multiple Products Section -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Lease Products</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="products-container">
                                            <div class="product-row row mb-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">Product *</label>
                                                    <select class="form-control select2-product" name="product_id[]" required>
                                                        <option value="">Search and select product...</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Quantity</label>
                                                    <input type="number" class="form-control quantity-input" name="quantity[]" min="1" value="1">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Unit Price</label>
                                                    <input type="number" class="form-control unit-price-input" name="unit_price[]" step="0.01" min="0" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Total</label>
                                                    <input type="number" class="form-control product-total" readonly step="0.01">
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary" id="add-product-btn">
                                            <i class="bi bi-plus-circle me-1"></i>Add Another Product
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="lease_term_months" class="form-label">Lease Term (Months) *</label>
                                            <input type="number" class="form-control" id="lease_term_months" name="lease_term_months" min="1" max="60" value="12" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="start_date" class="form-label">Lease Start Date *</label>
                                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="end_date" class="form-label">Lease End Date *</label>
                                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo date('Y-m-d', strtotime('+12 months')); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="total_amount" class="form-label">Total Amount *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" id="total_amount" name="total_amount" step="0.01" min="0" value="0.00" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="down_payment" class="form-label">Down Payment (<?php echo $default_down_payment_percentage; ?>%) *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" id="down_payment" name="down_payment" step="0.01" min="0" value="0.00" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="security_deposit" class="form-label">Security Deposit (<?php echo $default_security_deposit_months; ?> months) *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" id="security_deposit" name="security_deposit" step="0.01" min="0" value="0.00" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="outstanding_amount" class="form-label">Outstanding Amount *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" id="outstanding_amount" name="outstanding_amount" step="0.01" min="0" value="0.00" readonly required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="number_of_installments" class="form-label">Number of Installments *</label>
                                            <input type="number" class="form-control" id="number_of_installments" name="number_of_installments" min="1" max="60" value="12" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="installment_amount" class="form-label">Installment Amount *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" id="installment_amount" name="installment_amount" step="0.01" min="0" value="0.00" readonly required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="late_fee_percentage" class="form-label">Late Fee Percentage *</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="late_fee_percentage" name="late_fee_percentage" step="0.01" min="0" max="100" value="<?php echo $late_fee_percentage; ?>" required>
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <button type="reset" class="btn btn-secondary">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                                    </button>
                                    <button type="submit" name="register_lease" class="btn btn-success">
                                        <i class="bi bi-check-circle me-1"></i>Register Lease
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Transaction Info Modal -->
    <div class="modal fade" id="transactionInfoModal" tabindex="-1" aria-labelledby="transactionInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="transactionInfoModalLabel">Search Help</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>You can search for existing transactions using the following criteria:</p>
                    <ul>
                        <li><strong>Transaction ID</strong> - Unique identifier for each transaction</li>
                        <li><strong>Reference Number</strong> - Manual reference numbers</li>
                        <li><strong>Client Name</strong> - Full name of the client</li>
                        <li><strong>Lease ID</strong> - Lease agreement identifier</li>
                    </ul>
                    <p>Use the "Use" button next to a transaction to pre-fill some fields in the lease registration form.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="assets/js/master_data_dropdowns.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update end date when lease term changes
            document.getElementById('lease_term_months').addEventListener('change', function() {
                var startDate = new Date(document.getElementById('start_date').value);
                var termMonths = parseInt(this.value);
                if (!isNaN(startDate.getTime()) && !isNaN(termMonths)) {
                    startDate.setMonth(startDate.getMonth() + termMonths);
                    document.getElementById('end_date').value = startDate.toISOString().split('T')[0];
                }
            });
            
            // Update start date when agreement date changes
            document.getElementById('agreement_date').addEventListener('change', function() {
                document.getElementById('start_date').value = this.value;
            });
            
            // Handle product selection to auto-fill unit price
            $(document).on('select2:select', '.select2-product', function (e) {
                var data = e.params.data;
                if (data.price) {
                    // Find the unit price input in the same row
                    var unitPriceInput = $(this).closest('.product-row').find('.unit-price-input');
                    unitPriceInput.val(data.price);
                    
                    // Trigger change to update total
                    unitPriceInput.trigger('change');
                }
            });
            
            // Handle quantity or unit price change to update total
            $(document).on('change', '.quantity-input, .unit-price-input', function() {
                var row = $(this).closest('.product-row');
                var quantity = parseFloat(row.find('.quantity-input').val()) || 0;
                var unitPrice = parseFloat(row.find('.unit-price-input').val()) || 0;
                var total = quantity * unitPrice;
                row.find('.product-total').val(total.toFixed(2));
                
                // Update overall totals
                updateOverallTotals();
            });
            
            // Function to update overall totals
            function updateOverallTotals() {
                var totalAmount = 0;
                $('.product-total').each(function() {
                    totalAmount += parseFloat($(this).val()) || 0;
                });
                $('#total_amount').val(totalAmount.toFixed(2));
                
                // Update outstanding amount
                var downPayment = parseFloat($('#down_payment').val()) || 0;
                var securityDeposit = parseFloat($('#security_deposit').val()) || 0;
                var outstandingAmount = totalAmount - downPayment - securityDeposit;
                $('#outstanding_amount').val(outstandingAmount.toFixed(2));
                
                // Update installment amount
                var numberOfInstallments = parseFloat($('#number_of_installments').val()) || 1;
                var installmentAmount = outstandingAmount / numberOfInstallments;
                $('#installment_amount').val(installmentAmount.toFixed(2));
            }
            
            // Handle changes to down payment, security deposit, and number of installments
            $('#down_payment, #security_deposit, #number_of_installments').on('change', function() {
                updateOverallTotals();
            });
            
            // Reinitialize Select2 on dynamically added product dropdowns
            $('#add-product-btn').on('click', function() {
                // Clone the product row template
                var newRow = $('.product-row:first').clone();
                
                // Clear the values in the new row
                newRow.find('select').val(null).trigger('change');
                newRow.find('input').val('');
                
                // Append the new row to the container
                $('#products-container').append(newRow);
                
                // Initialize Select2 on the new product dropdown
                newRow.find('.select2-product').select2({
                    placeholder: "Search and select product...",
                    allowClear: true,
                    ajax: {
                        url: 'products.php',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                action: 'select2_search',
                                q: params.term
                            };
                        },
                        processResults: function (data) {
                            return {
                                results: data
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 1
                });
            });
            
            // Initialize date calculations
            document.getElementById('lease_term_months').dispatchEvent(new Event('change'));
        });
        
        // Function to use transaction data
        function useTransactionData(type, data) {
            data = JSON.parse(data.replace(/&quot;/g, '"'));
            // This function can be expanded to pre-fill form fields based on selected transaction
            alert('Functionality to use transaction data would be implemented here. Type: ' + type);
        }
    </script>
</body>
</html>