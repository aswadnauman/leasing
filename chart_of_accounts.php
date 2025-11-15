<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Handle form submission for adding/updating accounts
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_account'])) {
        // Add new account
        $account_code = $_POST['account_code'];
        $account_name = $_POST['account_name'];
        $account_type = $_POST['account_type'];
        $parent_account_id = !empty($_POST['parent_account_id']) ? $_POST['parent_account_id'] : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if ($parent_account_id) {
            $stmt = $conn->prepare("INSERT INTO chart_of_accounts (account_code, account_name, account_type, parent_account_id, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $account_code, $account_name, $account_type, $parent_account_id, $is_active);
        } else {
            $stmt = $conn->prepare("INSERT INTO chart_of_accounts (account_code, account_name, account_type, is_active) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $account_code, $account_name, $account_type, $is_active);
        }
        
        if ($stmt->execute()) {
            $success = "Account added successfully!";
        } else {
            $error = "Error adding account: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_account'])) {
        // Update account
        $id = $_POST['id'];
        $account_code = $_POST['account_code'];
        $account_name = $_POST['account_name'];
        $account_type = $_POST['account_type'];
        $parent_account_id = !empty($_POST['parent_account_id']) ? $_POST['parent_account_id'] : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if ($parent_account_id) {
            $stmt = $conn->prepare("UPDATE chart_of_accounts SET account_code=?, account_name=?, account_type=?, parent_account_id=?, is_active=? WHERE id=?");
            $stmt->bind_param("ssssii", $account_code, $account_name, $account_type, $parent_account_id, $is_active, $id);
        } else {
            $stmt = $conn->prepare("UPDATE chart_of_accounts SET account_code=?, account_name=?, account_type=?, parent_account_id=NULL, is_active=? WHERE id=?");
            $stmt->bind_param("sssii", $account_code, $account_name, $account_type, $is_active, $id);
        }
        
        if ($stmt->execute()) {
            $success = "Account updated successfully!";
        } else {
            $error = "Error updating account: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_account'])) {
        // Delete account
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM chart_of_accounts WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = "Account deleted successfully!";
        } else {
            $error = "Error deleting account: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all accounts with hierarchical structure
$accounts_result = $conn->query("
    SELECT ca.*, parent.account_name as parent_name
    FROM chart_of_accounts ca
    LEFT JOIN chart_of_accounts parent ON ca.parent_account_id = parent.id
    ORDER BY ca.account_code
");

// Fetch parent accounts for dropdown (only top-level accounts)
$parent_accounts_result = $conn->query("SELECT id, account_code, account_name FROM chart_of_accounts WHERE parent_account_id IS NULL ORDER BY account_code");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chart of Accounts - Lease Management System</title>
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
                        <a class="nav-link" href="payment_vouchers.php">Payment Vouchers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="chart_of_accounts.php">Chart of Accounts</a>
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
                <h2>Chart of Accounts</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Add/Edit Account Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 id="form-title">Add New Account</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" id="account-id" name="id">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="account_code" class="form-label">Account Code *</label>
                                        <input type="text" class="form-control" id="account_code" name="account_code" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="account_name" class="form-label">Account Name *</label>
                                        <input type="text" class="form-control" id="account_name" name="account_name" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="account_type" class="form-label">Account Type *</label>
                                        <select class="form-select" id="account_type" name="account_type" required>
                                            <option value="Asset">Asset</option>
                                            <option value="Liability">Liability</option>
                                            <option value="Equity">Equity</option>
                                            <option value="Income">Income</option>
                                            <option value="Expense">Expense</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="parent_account_id" class="form-label">Parent Account</label>
                                        <select class="form-select" id="parent_account_id" name="parent_account_id">
                                            <option value="">None</option>
                                            <?php if ($parent_accounts_result && $parent_accounts_result->num_rows > 0): ?>
                                                <?php while ($parent = $parent_accounts_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $parent['id']; ?>">
                                                        <?php echo htmlspecialchars($parent['account_code'] . ' - ' . $parent['account_name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                            
                            <button type="submit" name="add_account" class="btn btn-primary" id="submit-btn">Add Account</button>
                            <button type="button" class="btn btn-secondary" id="cancel-btn" style="display:none;">Cancel</button>
                        </form>
                    </div>
                </div>
                
                <!-- Accounts List -->
                <div class="card">
                    <div class="card-header">
                        <h5>All Accounts</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Parent Account</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($accounts_result && $accounts_result->num_rows > 0): ?>
                                        <?php while ($account = $accounts_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($account['account_code']); ?></td>
                                                <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                                                <td><?php echo htmlspecialchars($account['account_type']); ?></td>
                                                <td><?php echo $account['parent_name'] ? htmlspecialchars($account['parent_name']) : '-'; ?></td>
                                                <td>
                                                    <?php if ($account['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary edit-btn" 
                                                            data-id="<?php echo $account['id']; ?>"
                                                            data-account_code="<?php echo htmlspecialchars($account['account_code']); ?>"
                                                            data-account_name="<?php echo htmlspecialchars($account['account_name']); ?>"
                                                            data-account_type="<?php echo htmlspecialchars($account['account_type']); ?>"
                                                            data-parent_account_id="<?php echo htmlspecialchars($account['parent_account_id'] ?? ''); ?>"
                                                            data-is_active="<?php echo $account['is_active']; ?>">
                                                        Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger delete-btn" 
                                                            data-id="<?php echo $account['id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($account['account_name']); ?>">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No accounts found.</td>
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

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the account "<span id="delete-account-name"></span>"?</p>
                    <p class="text-danger"><small>Note: This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" id="delete-account-id" name="id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_account" class="btn btn-danger">Delete</button>
                    </form>
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
        // Edit button functionality
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const account_code = this.getAttribute('data-account_code');
                const account_name = this.getAttribute('data-account_name');
                const account_type = this.getAttribute('data-account_type');
                const parent_account_id = this.getAttribute('data-parent_account_id');
                const is_active = this.getAttribute('data-is_active') == '1';
                
                // Fill form fields
                document.getElementById('account-id').value = id;
                document.getElementById('account_code').value = account_code;
                document.getElementById('account_name').value = account_name;
                document.getElementById('account_type').value = account_type;
                document.getElementById('parent_account_id').value = parent_account_id;
                document.getElementById('is_active').checked = is_active;
                
                // Change form title and submit button
                document.getElementById('form-title').textContent = 'Edit Account';
                document.querySelector('[name="add_account"]').name = 'update_account';
                document.querySelector('[name="update_account"]').textContent = 'Update Account';
                document.getElementById('cancel-btn').style.display = 'inline-block';
            });
        });
        
        // Cancel button functionality
        document.getElementById('cancel-btn').addEventListener('click', function() {
            // Reset form
            document.getElementById('account-id').value = '';
            document.getElementById('account_code').value = '';
            document.getElementById('account_name').value = '';
            document.getElementById('account_type').value = 'Asset';
            document.getElementById('parent_account_id').value = '';
            document.getElementById('is_active').checked = true;
            
            // Reset form title and submit button
            document.getElementById('form-title').textContent = 'Add New Account';
            document.querySelector('[name="update_account"]').name = 'add_account';
            document.querySelector('[name="add_account"]').textContent = 'Add Account';
            this.style.display = 'none';
        });
        
        // Delete button functionality
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                
                document.getElementById('delete-account-id').value = id;
                document.getElementById('delete-account-name').textContent = name;
                
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();
            });
        });
    </script>
</body>
</html>