<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user has admin privileges
if (($_SESSION['role'] ?? '') != 'Admin') {
    header("Location: dashboard.php");
    exit();
}

$conn = getDBConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle Area CRUD
    if (isset($_POST['add_area'])) {
        $area = $_POST['area'];
        $stmt = $conn->prepare("INSERT INTO master_area (area) VALUES (?)");
        $stmt->bind_param("s", $area);
        if ($stmt->execute()) {
            $success = "Area added successfully!";
        } else {
            $error = "Error adding area: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_area'])) {
        $id = $_POST['id'];
        $area = $_POST['area'];
        $stmt = $conn->prepare("UPDATE master_area SET area=? WHERE id=?");
        $stmt->bind_param("si", $area, $id);
        if ($stmt->execute()) {
            $success = "Area updated successfully!";
        } else {
            $error = "Error updating area: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_area'])) {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM master_area WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Area deleted successfully!";
        } else {
            $error = "Error deleting area: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchTerm = "%$search%";

// Fetch areas
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM master_area WHERE area LIKE ? ORDER BY area");
    $stmt->bind_param("s", $searchTerm);
} else {
    $stmt = $conn->prepare("SELECT * FROM master_area ORDER BY area");
}
$stmt->execute();
$areas_result = $stmt->get_result();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Area Management - Lease Management System</title>
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
                <h1 class="h3 mb-1"><i class="bi bi-map me-2"></i>Area Management</h1>
                <p class="mb-0 text-muted">Manage system area data</p>
            </div>
            <div class="d-flex">
                <button class="btn btn-secondary ms-2" onclick="window.location.href='dashboard.php'">
                    <i class="bi bi-arrow-left-circle me-1"></i>Back to Dashboard
                </button>
                <div class="dropdown ms-2">
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
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Manage Areas</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <!-- Search Form -->
                        <form method="GET" class="mb-3">
                            <div class="row">
                                <div class="col-md-8">
                                    <input type="text" class="form-control" name="search" placeholder="Search areas..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-4">
                                    <div class="btn-group" role="group">
                                        <button type="submit" class="btn btn-primary">Search</button>
                                        <a href="area.php" class="btn btn-outline-secondary">Clear</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Add Area Form -->
                        <form method="POST" class="mb-4" id="area-form">
                            <input type="hidden" name="id" id="area-id">
                            <div class="row">
                                <div class="col-md-8">
                                    <input type="text" class="form-control" name="area" id="area-name" placeholder="Enter area" required>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" name="add_area" class="btn btn-primary" id="area-submit-btn">Add Area</button>
                                    <button type="submit" name="update_area" class="btn btn-success" id="area-update-btn" style="display: none;">Update Area</button>
                                    <button type="button" class="btn btn-secondary" id="area-cancel-btn" style="display: none;">Cancel</button>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Areas List -->
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Area</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($areas_result && $areas_result->num_rows > 0): ?>
                                        <?php while ($area = $areas_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($area['area']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary edit-area-btn" 
                                                            data-id="<?php echo $area['id']; ?>"
                                                            data-area="<?php echo htmlspecialchars($area['area']); ?>">
                                                        Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger delete-area-btn" 
                                                            data-id="<?php echo $area['id']; ?>"
                                                            data-area="<?php echo htmlspecialchars($area['area']); ?>">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="2" class="text-center">No areas found.</td>
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
    <div class="modal fade" id="deleteAreaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the area "<span id="delete-area-name"></span>"?</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" id="delete-area-id" name="id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_area" class="btn btn-danger">Delete</button>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Edit Area Button
            document.querySelectorAll('.edit-area-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const area = this.getAttribute('data-area');
                    
                    // Populate form fields
                    document.getElementById('area-id').value = id;
                    document.getElementById('area-name').value = area;
                    
                    // Show update buttons and hide add button
                    document.getElementById('area-submit-btn').style.display = 'none';
                    document.getElementById('area-update-btn').style.display = 'inline-block';
                    document.getElementById('area-cancel-btn').style.display = 'inline-block';
                    
                    // Scroll to the form
                    document.getElementById('area-form').scrollIntoView({ behavior: 'smooth' });
                });
            });
            
            // Cancel Area Edit
            document.getElementById('area-cancel-btn').addEventListener('click', function() {
                // Reset form
                document.getElementById('area-form').reset();
                document.getElementById('area-id').value = '';
                
                // Show add button and hide update buttons
                document.getElementById('area-submit-btn').style.display = 'inline-block';
                document.getElementById('area-update-btn').style.display = 'none';
                this.style.display = 'none';
            });
            
            // Delete Area Button
            document.querySelectorAll('.delete-area-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const area = this.getAttribute('data-area');
                    
                    document.getElementById('delete-area-id').value = id;
                    document.getElementById('delete-area-name').textContent = area;
                    
                    var deleteModal = new bootstrap.Modal(document.getElementById('deleteAreaModal'));
                    deleteModal.show();
                });
            });
        });
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>