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
    // Handle Profession CRUD
    if (isset($_POST['add_profession'])) {
        $profession = $_POST['profession'];
        $stmt = $conn->prepare("INSERT INTO master_profession (profession) VALUES (?)");
        $stmt->bind_param("s", $profession);
        if ($stmt->execute()) {
            $success = "Profession added successfully!";
        } else {
            $error = "Error adding profession: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_profession'])) {
        $id = $_POST['id'];
        $profession = $_POST['profession'];
        $stmt = $conn->prepare("UPDATE master_profession SET profession=? WHERE id=?");
        $stmt->bind_param("si", $profession, $id);
        if ($stmt->execute()) {
            $success = "Profession updated successfully!";
        } else {
            $error = "Error updating profession: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_profession'])) {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM master_profession WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Profession deleted successfully!";
        } else {
            $error = "Error deleting profession: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchTerm = "%$search%";

// Fetch professions
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM master_profession WHERE profession LIKE ? ORDER BY profession");
    $stmt->bind_param("s", $searchTerm);
} else {
    $stmt = $conn->prepare("SELECT * FROM master_profession ORDER BY profession");
}
$stmt->execute();
$professions_result = $stmt->get_result();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profession Management - Lease Management System</title>
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
                <h1 class="h3 mb-1"><i class="bi bi-briefcase me-2"></i>Profession Management</h1>
                <p class="mb-0 text-muted">Manage system profession data</p>
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
                        <h5>Manage Professions</h5>
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
                                    <input type="text" class="form-control" name="search" placeholder="Search professions..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-4">
                                    <div class="btn-group" role="group">
                                        <button type="submit" class="btn btn-primary">Search</button>
                                        <a href="profession.php" class="btn btn-outline-secondary">Clear</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Add Profession Form -->
                        <form method="POST" class="mb-4" id="profession-form">
                            <input type="hidden" name="id" id="profession-id">
                            <div class="row">
                                <div class="col-md-8">
                                    <input type="text" class="form-control" name="profession" id="profession-name" placeholder="Enter profession" required>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" name="add_profession" class="btn btn-primary" id="profession-submit-btn">Add Profession</button>
                                    <button type="submit" name="update_profession" class="btn btn-success" id="profession-update-btn" style="display: none;">Update Profession</button>
                                    <button type="button" class="btn btn-secondary" id="profession-cancel-btn" style="display: none;">Cancel</button>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Professions List -->
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Profession</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($professions_result && $professions_result->num_rows > 0): ?>
                                        <?php while ($profession = $professions_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($profession['profession']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary edit-profession-btn" 
                                                            data-id="<?php echo $profession['id']; ?>"
                                                            data-profession="<?php echo htmlspecialchars($profession['profession']); ?>">
                                                        Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger delete-profession-btn" 
                                                            data-id="<?php echo $profession['id']; ?>"
                                                            data-profession="<?php echo htmlspecialchars($profession['profession']); ?>">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="2" class="text-center">No professions found.</td>
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
    <div class="modal fade" id="deleteProfessionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the profession "<span id="delete-profession-name"></span>"?</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" id="delete-profession-id" name="id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_profession" class="btn btn-danger">Delete</button>
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
            // Edit Profession Button
            document.querySelectorAll('.edit-profession-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const profession = this.getAttribute('data-profession');
                    
                    // Populate form fields
                    document.getElementById('profession-id').value = id;
                    document.getElementById('profession-name').value = profession;
                    
                    // Show update buttons and hide add button
                    document.getElementById('profession-submit-btn').style.display = 'none';
                    document.getElementById('profession-update-btn').style.display = 'inline-block';
                    document.getElementById('profession-cancel-btn').style.display = 'inline-block';
                    
                    // Scroll to the form
                    document.getElementById('profession-form').scrollIntoView({ behavior: 'smooth' });
                });
            });
            
            // Cancel Profession Edit
            document.getElementById('profession-cancel-btn').addEventListener('click', function() {
                // Reset form
                document.getElementById('profession-form').reset();
                document.getElementById('profession-id').value = '';
                
                // Show add button and hide update buttons
                document.getElementById('profession-submit-btn').style.display = 'inline-block';
                document.getElementById('profession-update-btn').style.display = 'none';
                this.style.display = 'none';
            });
            
            // Delete Profession Button
            document.querySelectorAll('.delete-profession-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const profession = this.getAttribute('data-profession');
                    
                    document.getElementById('delete-profession-id').value = id;
                    document.getElementById('delete-profession-name').textContent = profession;
                    
                    var deleteModal = new bootstrap.Modal(document.getElementById('deleteProfessionModal'));
                    deleteModal.show();
                });
            });
        });
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>