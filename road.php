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
    // Handle Road CRUD
    if (isset($_POST['add_road'])) {
        $road = $_POST['road'];
        $area_id = $_POST['area_id'];
        $stmt = $conn->prepare("INSERT INTO master_road (road, area_id) VALUES (?, ?)");
        $stmt->bind_param("si", $road, $area_id);
        if ($stmt->execute()) {
            $success = "Road added successfully!";
        } else {
            $error = "Error adding road: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_road'])) {
        $id = $_POST['id'];
        $road = $_POST['road'];
        $area_id = $_POST['area_id'];
        $stmt = $conn->prepare("UPDATE master_road SET road=?, area_id=? WHERE id=?");
        $stmt->bind_param("sii", $road, $area_id, $id);
        if ($stmt->execute()) {
            $success = "Road updated successfully!";
        } else {
            $error = "Error updating road: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_road'])) {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM master_road WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Road deleted successfully!";
        } else {
            $error = "Error deleting road: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchTerm = "%$search%";

// Fetch areas for dropdown
$areas_stmt = $conn->prepare("SELECT id, area FROM master_area ORDER BY area");
$areas_stmt->execute();
$areas_dropdown = $areas_stmt->get_result();
$areas_stmt->close();

// Fetch roads with area information
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT r.*, a.area FROM master_road r LEFT JOIN master_area a ON r.area_id = a.id WHERE r.road LIKE ? ORDER BY r.road");
    $stmt->bind_param("s", $searchTerm);
} else {
    $stmt = $conn->prepare("SELECT r.*, a.area FROM master_road r LEFT JOIN master_area a ON r.area_id = a.id ORDER BY r.road");
}
$stmt->execute();
$roads_result = $stmt->get_result();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Road Management - Lease Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <!-- Permanent Sidebar Navigation -->
    <?php include 'includes/permanent_sidebar.php'; ?>

    <div class="main-content" style="margin-left: 250px; padding: 20px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1"><i class="bi bi-signpost me-2"></i>Road Management</h1>
                <p class="mb-0 text-muted">Manage system road data</p>
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
                        <h5>Manage Roads</h5>
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
                                    <input type="text" class="form-control" name="search" placeholder="Search roads..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-4">
                                    <div class="btn-group" role="group">
                                        <button type="submit" class="btn btn-primary">Search</button>
                                        <a href="road.php" class="btn btn-outline-secondary">Clear</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Add Road Form -->
                        <form method="POST" class="mb-4" id="road-form">
                            <input type="hidden" name="id" id="road-id">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="road-name" class="form-label">Road Name *</label>
                                    <input type="text" class="form-control" name="road" id="road-name" placeholder="Enter road name" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="road-area-id" class="form-label">Area *</label>
                                    <select class="form-select" name="area_id" id="road-area-id" required>
                                        <option value="">Select Area</option>
                                        <?php if ($areas_dropdown && $areas_dropdown->num_rows > 0): ?>
                                            <?php while ($area = $areas_dropdown->fetch_assoc()): ?>
                                                <option value="<?php echo $area['id']; ?>"><?php echo htmlspecialchars($area['area']); ?></option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div>
                                        <button type="submit" name="add_road" class="btn btn-primary" id="road-submit-btn">Add Road</button>
                                        <button type="submit" name="update_road" class="btn btn-success" id="road-update-btn" style="display: none;">Update Road</button>
                                        <button type="button" class="btn btn-secondary" id="road-cancel-btn" style="display: none;">Cancel</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Roads List -->
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Road</th>
                                        <th>Area</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($roads_result && $roads_result->num_rows > 0): ?>
                                        <?php while ($road = $roads_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($road['road']); ?></td>
                                                <td><?php echo htmlspecialchars($road['area'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary edit-road-btn" 
                                                            data-id="<?php echo $road['id']; ?>"
                                                            data-road="<?php echo htmlspecialchars($road['road']); ?>"
                                                            data-area_id="<?php echo $road['area_id'] ?? ''; ?>">
                                                        Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger delete-road-btn" 
                                                            data-id="<?php echo $road['id']; ?>"
                                                            data-road="<?php echo htmlspecialchars($road['road']); ?>">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center">No roads found.</td>
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
    <div class="modal fade" id="deleteRoadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the road "<span id="delete-road-name"></span>"?</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" id="delete-road-id" name="id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_road" class="btn btn-danger">Delete</button>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2 on all select elements
            $('select').select2({
                placeholder: "Select an option",
                allowClear: true,
                width: '100%'
            });
            
            // Edit Road Button
            document.querySelectorAll('.edit-road-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const road = this.getAttribute('data-road');
                    const area_id = this.getAttribute('data-area_id');
                    
                    // Populate form fields
                    document.getElementById('road-id').value = id;
                    document.getElementById('road-name').value = road;
                    document.getElementById('road-area-id').value = area_id;
                    
                    // Show update buttons and hide add button
                    document.getElementById('road-submit-btn').style.display = 'none';
                    document.getElementById('road-update-btn').style.display = 'inline-block';
                    document.getElementById('road-cancel-btn').style.display = 'inline-block';
                    
                    // Scroll to the form
                    document.getElementById('road-form').scrollIntoView({ behavior: 'smooth' });
                });
            });
            
            // Cancel Road Edit
            document.getElementById('road-cancel-btn').addEventListener('click', function() {
                // Reset form
                document.getElementById('road-form').reset();
                document.getElementById('road-id').value = '';
                
                // Show add button and hide update buttons
                document.getElementById('road-submit-btn').style.display = 'inline-block';
                document.getElementById('road-update-btn').style.display = 'none';
                this.style.display = 'none';
            });
            
            // Delete Road Button
            document.querySelectorAll('.delete-road-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const road = this.getAttribute('data-road');
                    
                    document.getElementById('delete-road-id').value = id;
                    document.getElementById('delete-road-name').textContent = road;
                    
                    var deleteModal = new bootstrap.Modal(document.getElementById('deleteRoadModal'));
                    deleteModal.show();
                });
            });
        });
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</body>
</html>