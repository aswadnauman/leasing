<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Handle form submission for adding/updating outlets
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_outlet'])) {
        // Add new outlet
        $outlet_id = $_POST['outlet_id'];
        $outlet_name = $_POST['outlet_name'];
        $city = $_POST['city'];
        $address = $_POST['address'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $outlet_type = $_POST['outlet_type'];
        $assigned_branch_manager = $_POST['assigned_branch_manager'];
        $linked_cash_account = $_POST['linked_cash_account'];
        $linked_bank_account = $_POST['linked_bank_account'];
        
        $stmt = $conn->prepare("INSERT INTO outlets (outlet_id, outlet_name, city, address, phone, email, outlet_type, assigned_branch_manager, linked_cash_account, linked_bank_account) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssss", $outlet_id, $outlet_name, $city, $address, $phone, $email, $outlet_type, $assigned_branch_manager, $linked_cash_account, $linked_bank_account);
        
        if ($stmt->execute()) {
            $success = "Outlet added successfully!";
        } else {
            $error = "Error adding outlet: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_outlet'])) {
        // Update outlet
        $id = $_POST['id'];
        $outlet_name = $_POST['outlet_name'];
        $city = $_POST['city'];
        $address = $_POST['address'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $outlet_type = $_POST['outlet_type'];
        $assigned_branch_manager = $_POST['assigned_branch_manager'];
        $linked_cash_account = $_POST['linked_cash_account'];
        $linked_bank_account = $_POST['linked_bank_account'];
        
        $stmt = $conn->prepare("UPDATE outlets SET outlet_name=?, city=?, address=?, phone=?, email=?, outlet_type=?, assigned_branch_manager=?, linked_cash_account=?, linked_bank_account=? WHERE id=?");
        $stmt->bind_param("sssssssssi", $outlet_name, $city, $address, $phone, $email, $outlet_type, $assigned_branch_manager, $linked_cash_account, $linked_bank_account, $id);
        
        if ($stmt->execute()) {
            $success = "Outlet updated successfully!";
        } else {
            $error = "Error updating outlet: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_outlet'])) {
        // Delete outlet
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM outlets WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = "Outlet deleted successfully!";
        } else {
            $error = "Error deleting outlet: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all outlets
$outlets_result = $conn->query("SELECT * FROM outlets ORDER BY outlet_name");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outlets - Lease Management System</title>
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
                        <a class="nav-link active" href="outlets.php">Outlets</a>
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
                <h2>Outlet Management</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Add/Edit Outlet Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 id="form-title">Add New Outlet</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" id="outlet-id" name="id">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="outlet_id" class="form-label">Outlet ID *</label>
                                        <input type="text" class="form-control" id="outlet_id" name="outlet_id" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="outlet_name" class="form-label">Outlet Name *</label>
                                        <input type="text" class="form-control" id="outlet_name" name="outlet_name" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="city" class="form-label">City *</label>
                                        <input type="text" class="form-control" id="city" name="city" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="outlet_type" class="form-label">Outlet Type *</label>
                                        <select class="form-select" id="outlet_type" name="outlet_type" required>
                                            <option value="Main">Main</option>
                                            <option value="Sub">Sub</option>
                                            <option value="Franchise">Franchise</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address *</label>
                                <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="text" class="form-control" id="phone" name="phone">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="assigned_branch_manager" class="form-label">Assigned Branch Manager</label>
                                        <input type="text" class="form-control" id="assigned_branch_manager" name="assigned_branch_manager">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="linked_cash_account" class="form-label">Linked Cash Account *</label>
                                        <input type="text" class="form-control" id="linked_cash_account" name="linked_cash_account" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="linked_bank_account" class="form-label">Linked Bank Account *</label>
                                <input type="text" class="form-control" id="linked_bank_account" name="linked_bank_account" required>
                            </div>
                            
                            <button type="submit" name="add_outlet" class="btn btn-primary" id="submit-btn">Add Outlet</button>
                            <button type="button" class="btn btn-secondary" id="cancel-btn" style="display:none;">Cancel</button>
                        </form>
                    </div>
                </div>
                
                <!-- Outlets List -->
                <div class="card">
                    <div class="card-header">
                        <h5>All Outlets</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Outlet ID</th>
                                        <th>Name</th>
                                        <th>City</th>
                                        <th>Type</th>
                                        <th>Manager</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($outlets_result && $outlets_result->num_rows > 0): ?>
                                        <?php while ($outlet = $outlets_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($outlet['outlet_id']); ?></td>
                                                <td><?php echo htmlspecialchars($outlet['outlet_name']); ?></td>
                                                <td><?php echo htmlspecialchars($outlet['city']); ?></td>
                                                <td><?php echo htmlspecialchars($outlet['outlet_type']); ?></td>
                                                <td><?php echo htmlspecialchars($outlet['assigned_branch_manager']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary edit-btn" 
                                                            data-id="<?php echo $outlet['id']; ?>"
                                                            data-outlet_id="<?php echo htmlspecialchars($outlet['outlet_id']); ?>"
                                                            data-outlet_name="<?php echo htmlspecialchars($outlet['outlet_name']); ?>"
                                                            data-city="<?php echo htmlspecialchars($outlet['city']); ?>"
                                                            data-address="<?php echo htmlspecialchars($outlet['address']); ?>"
                                                            data-phone="<?php echo htmlspecialchars($outlet['phone']); ?>"
                                                            data-email="<?php echo htmlspecialchars($outlet['email']); ?>"
                                                            data-outlet_type="<?php echo htmlspecialchars($outlet['outlet_type']); ?>"
                                                            data-assigned_branch_manager="<?php echo htmlspecialchars($outlet['assigned_branch_manager']); ?>"
                                                            data-linked_cash_account="<?php echo htmlspecialchars($outlet['linked_cash_account']); ?>"
                                                            data-linked_bank_account="<?php echo htmlspecialchars($outlet['linked_bank_account']); ?>">
                                                        Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger delete-btn" 
                                                            data-id="<?php echo $outlet['id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($outlet['outlet_name']); ?>">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No outlets found.</td>
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
                    <p>Are you sure you want to delete the outlet "<span id="delete-outlet-name"></span>"?</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" id="delete-outlet-id" name="id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_outlet" class="btn btn-danger">Delete</button>
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
                const outlet_id = this.getAttribute('data-outlet_id');
                const outlet_name = this.getAttribute('data-outlet_name');
                const city = this.getAttribute('data-city');
                const address = this.getAttribute('data-address');
                const phone = this.getAttribute('data-phone');
                const email = this.getAttribute('data-email');
                const outlet_type = this.getAttribute('data-outlet_type');
                const assigned_branch_manager = this.getAttribute('data-assigned_branch_manager');
                const linked_cash_account = this.getAttribute('data-linked_cash_account');
                const linked_bank_account = this.getAttribute('data-linked_bank_account');
                
                // Fill form fields
                document.getElementById('outlet-id').value = id;
                document.getElementById('outlet_id').value = outlet_id;
                document.getElementById('outlet_id').readOnly = true;
                document.getElementById('outlet_name').value = outlet_name;
                document.getElementById('city').value = city;
                document.getElementById('address').value = address;
                document.getElementById('phone').value = phone;
                document.getElementById('email').value = email;
                document.getElementById('outlet_type').value = outlet_type;
                document.getElementById('assigned_branch_manager').value = assigned_branch_manager;
                document.getElementById('linked_cash_account').value = linked_cash_account;
                document.getElementById('linked_bank_account').value = linked_bank_account;
                
                // Change form title and submit button
                document.getElementById('form-title').textContent = 'Edit Outlet';
                document.querySelector('[name="add_outlet"]').name = 'update_outlet';
                document.querySelector('[name="update_outlet"]').textContent = 'Update Outlet';
                document.getElementById('cancel-btn').style.display = 'inline-block';
            });
        });
        
        // Cancel button functionality
        document.getElementById('cancel-btn').addEventListener('click', function() {
            // Reset form
            document.getElementById('outlet-id').value = '';
            document.getElementById('outlet_id').value = '';
            document.getElementById('outlet_id').readOnly = false;
            document.getElementById('outlet_name').value = '';
            document.getElementById('city').value = '';
            document.getElementById('address').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('email').value = '';
            document.getElementById('outlet_type').value = 'Main';
            document.getElementById('assigned_branch_manager').value = '';
            document.getElementById('linked_cash_account').value = '';
            document.getElementById('linked_bank_account').value = '';
            
            // Reset form title and submit button
            document.getElementById('form-title').textContent = 'Add New Outlet';
            document.querySelector('[name="update_outlet"]').name = 'add_outlet';
            document.querySelector('[name="add_outlet"]').textContent = 'Add Outlet';
            this.style.display = 'none';
        });
        
        // Delete button functionality
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                
                document.getElementById('delete-outlet-id').value = id;
                document.getElementById('delete-outlet-name').textContent = name;
                
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();
            });
        });
    </script>
</body>
</html>