<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Handle form submission for adding/updating customers
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_customer'])) {
        // Add new customer
        $customer_id = $_POST['customer_id'];
        $customer_name = $_POST['customer_name'];
        $contact_person = $_POST['contact_person'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $address = $_POST['address'] ?? '';
        $city = $_POST['city'] ?? '';
        $outlet_id = $_POST['outlet_id'];
        $status = $_POST['status'] ?? 'Active';
        
        $stmt = $conn->prepare("INSERT INTO customers (customer_id, customer_name, contact_person, phone, email, address, city, outlet_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $customer_id, $customer_name, $contact_person, $phone, $email, $address, $city, $outlet_id, $status);
        
        if ($stmt->execute()) {
            $success = "Customer added successfully!";
        } else {
            $error = "Error adding customer: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_customer'])) {
        // Update customer
        $id = $_POST['id'];
        $customer_name = $_POST['customer_name'];
        $contact_person = $_POST['contact_person'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $address = $_POST['address'] ?? '';
        $city = $_POST['city'] ?? '';
        $outlet_id = $_POST['outlet_id'];
        $status = $_POST['status'] ?? 'Active';
        
        $stmt = $conn->prepare("UPDATE customers SET customer_name=?, contact_person=?, phone=?, email=?, address=?, city=?, outlet_id=?, status=? WHERE id=?");
        $stmt->bind_param("ssssssssi", $customer_name, $contact_person, $phone, $email, $address, $city, $outlet_id, $status, $id);
        
        if ($stmt->execute()) {
            $success = "Customer updated successfully!";
        } else {
            $error = "Error updating customer: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_customer'])) {
        // Delete customer
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM customers WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = "Customer deleted successfully!";
        } else {
            $error = "Error deleting customer: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all customers with related data
$customers_result = $conn->query("
    SELECT c.*, o.outlet_name 
    FROM customers c
    JOIN outlets o ON c.outlet_id = o.outlet_id
    ORDER BY c.created_at DESC
");

// Fetch outlets for dropdown
$outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Lease Management System</title>
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
                        <a class="nav-link active" href="customers.php">Customers</a>
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
                <h2>Customer Management</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Add/Edit Customer Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 id="form-title">Add New Customer</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" id="customer-id" name="id">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="customer_id" class="form-label">Customer ID *</label>
                                        <input type="text" class="form-control" id="customer_id" name="customer_id" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="customer_name" class="form-label">Customer Name *</label>
                                        <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="contact_person" class="form-label">Contact Person</label>
                                        <input type="text" class="form-control" id="contact_person" name="contact_person">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="text" class="form-control" id="phone" name="phone">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="city" name="city">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"></textarea>
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
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="Active">Active</option>
                                            <option value="Inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="add_customer" class="btn btn-primary" id="submit-btn">Add Customer</button>
                            <button type="button" class="btn btn-secondary" id="cancel-btn" style="display:none;">Cancel</button>
                        </form>
                    </div>
                </div>
                
                <!-- Customers List -->
                <div class="card">
                    <div class="card-header">
                        <h5>All Customers</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Customer ID</th>
                                        <th>Name</th>
                                        <th>Contact Person</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>City</th>
                                        <th>Outlet</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($customers_result && $customers_result->num_rows > 0): ?>
                                        <?php while ($customer = $customers_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($customer['customer_id']); ?></td>
                                                <td><?php echo htmlspecialchars($customer['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($customer['contact_person']); ?></td>
                                                <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                                <td><?php echo htmlspecialchars($customer['city']); ?></td>
                                                <td><?php echo htmlspecialchars($customer['outlet_name']); ?></td>
                                                <td>
                                                    <?php if ($customer['status'] == 'Active'): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary edit-btn" 
                                                            data-id="<?php echo $customer['id']; ?>"
                                                            data-customer_id="<?php echo htmlspecialchars($customer['customer_id']); ?>"
                                                            data-customer_name="<?php echo htmlspecialchars($customer['customer_name']); ?>"
                                                            data-contact_person="<?php echo htmlspecialchars($customer['contact_person']); ?>"
                                                            data-phone="<?php echo htmlspecialchars($customer['phone']); ?>"
                                                            data-email="<?php echo htmlspecialchars($customer['email']); ?>"
                                                            data-address="<?php echo htmlspecialchars($customer['address']); ?>"
                                                            data-city="<?php echo htmlspecialchars($customer['city']); ?>"
                                                            data-outlet_id="<?php echo htmlspecialchars($customer['outlet_id']); ?>"
                                                            data-status="<?php echo htmlspecialchars($customer['status']); ?>">
                                                        Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger delete-btn" 
                                                            data-id="<?php echo $customer['id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($customer['customer_name']); ?>">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No customers found.</td>
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
                    <p>Are you sure you want to delete the customer "<span id="delete-customer-name"></span>"?</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" id="delete-customer-id" name="id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_customer" class="btn btn-danger">Delete</button>
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
                const customer_id = this.getAttribute('data-customer_id');
                const customer_name = this.getAttribute('data-customer_name');
                const contact_person = this.getAttribute('data-contact_person');
                const phone = this.getAttribute('data-phone');
                const email = this.getAttribute('data-email');
                const address = this.getAttribute('data-address');
                const city = this.getAttribute('data-city');
                const outlet_id = this.getAttribute('data-outlet_id');
                const status = this.getAttribute('data-status');
                
                // Fill form fields
                document.getElementById('customer-id').value = id;
                document.getElementById('customer_id').value = customer_id;
                document.getElementById('customer_id').readOnly = true;
                document.getElementById('customer_name').value = customer_name;
                document.getElementById('contact_person').value = contact_person;
                document.getElementById('phone').value = phone;
                document.getElementById('email').value = email;
                document.getElementById('address').value = address;
                document.getElementById('city').value = city;
                document.getElementById('outlet_id').value = outlet_id;
                document.getElementById('status').value = status;
                
                // Change form title and submit button
                document.getElementById('form-title').textContent = 'Edit Customer';
                document.querySelector('[name="add_customer"]').name = 'update_customer';
                document.querySelector('[name="update_customer"]').textContent = 'Update Customer';
                document.getElementById('cancel-btn').style.display = 'inline-block';
            });
        });
        
        // Cancel button functionality
        document.getElementById('cancel-btn').addEventListener('click', function() {
            // Reset form
            document.getElementById('customer-id').value = '';
            document.getElementById('customer_id').value = '';
            document.getElementById('customer_id').readOnly = false;
            document.getElementById('customer_name').value = '';
            document.getElementById('contact_person').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('email').value = '';
            document.getElementById('address').value = '';
            document.getElementById('city').value = '';
            document.getElementById('outlet_id').value = '';
            document.getElementById('status').value = 'Active';
            
            // Reset form title and submit button
            document.getElementById('form-title').textContent = 'Add New Customer';
            document.querySelector('[name="update_customer"]').name = 'add_customer';
            document.querySelector('[name="add_customer"]').textContent = 'Add Customer';
            this.style.display = 'none';
        });
        
        // Delete button functionality
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                
                document.getElementById('delete-customer-id').value = id;
                document.getElementById('delete-customer-name').textContent = name;
                
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();
            });
        });
    </script>
</body>
</html>