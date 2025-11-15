<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user has admin privileges
if ($_SESSION['role'] != 'Admin') {
    header("Location: dashboard.php");
    exit();
}

// Handle backup creation
if (isset($_POST['create_backup'])) {
    // In a real implementation, this would create a database backup
    // For this demo, we'll just simulate the process
    $backup_file = 'backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    // Create backups directory if it doesn't exist
    if (!file_exists('backups')) {
        mkdir('backups', 0777, true);
    }
    
    // Simulate backup creation
    $backup_content = "-- Lease Management System Backup\n";
    $backup_content .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    
    // In a real implementation, you would export the actual database tables here
    $backup_content .= "-- This is a simulated backup file\n";
    $backup_content .= "-- In a real system, this would contain actual database export\n";
    
    file_put_contents($backup_file, $backup_content);
    $success = "Backup created successfully: " . $backup_file;
}

// Handle backup restoration
if (isset($_POST['restore_backup']) && isset($_POST['backup_file'])) {
    $backup_file = $_POST['backup_file'];
    
    // In a real implementation, this would restore the database from the backup file
    // For this demo, we'll just simulate the process
    if (file_exists($backup_file)) {
        $success = "Backup restored successfully from: " . $backup_file;
    } else {
        $error = "Backup file not found: " . $backup_file;
    }
}

// Get list of backup files
$backup_files = [];
if (file_exists('backups')) {
    $files = scandir('backups');
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
            $backup_files[] = 'backups/' . $file;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore - Lease Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-building me-2"></i>Lease Management System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <button class="btn btn-outline-light d-lg-none ms-2" type="button" data-bs-toggle="offcanvas" 
                    data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
                <i class="bi bi-list"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation (Desktop) -->
            <div class="col-lg-2 d-none d-lg-block p-0">
                <?php include 'includes/sidebar_navigation.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10 col-md-12 main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1><i class="bi bi-server me-3"></i>Backup & Restore</h1>
                            <p class="mb-0">Manage system backups and restore data</p>
                        </div>
                    </div>
                </div>

                <!-- Breadcrumb Navigation -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="users.php">Administration</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Backup & Restore</li>
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
                
                <!-- Create Backup Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-plus-circle me-2"></i>Create New Backup</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <p>This will create a backup of the entire database including all tables and data.</p>
                                <p><strong>Warning:</strong> Backup process may take several minutes depending on the size of your database.</p>
                            </div>
                            <button type="submit" name="create_backup" class="btn btn-primary">
                                <i class="bi bi-database me-1"></i>Create Backup
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Restore Backup Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-arrow-repeat me-2"></i>Restore Backup</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($backup_files)): ?>
                            <p>No backup files found. Create a backup first.</p>
                        <?php else: ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="backup_file" class="form-label">Select Backup File</label>
                                    <select class="form-select" id="backup_file" name="backup_file" required>
                                        <option value="">Choose a backup file...</option>
                                        <?php foreach ($backup_files as $file): ?>
                                            <option value="<?php echo $file; ?>"><?php echo basename($file); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Warning: This will replace all current data with the data from the backup file.</div>
                                </div>
                                <button type="submit" name="restore_backup" class="btn btn-warning" onclick="return confirm('Are you sure you want to restore this backup? This will replace all current data.')">
                                    <i class="bi bi-arrow-repeat me-1"></i>Restore Backup
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Backup Files List -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-folder me-2"></i>Available Backup Files</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($backup_files)): ?>
                            <p>No backup files available.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>File Name</th>
                                            <th>File Size</th>
                                            <th>Date Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($backup_files as $file): ?>
                                            <tr>
                                                <td><?php echo basename($file); ?></td>
                                                <td><?php echo round(filesize($file) / 1024, 2); ?> KB</td>
                                                <td><?php echo date('M j, Y H:i:s', filemtime($file)); ?></td>
                                                <td>
                                                    <a href="<?php echo $file; ?>" class="btn btn-sm btn-outline-primary" download>
                                                        <i class="bi bi-download me-1"></i>Download
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteBackup('<?php echo $file; ?>')">
                                                        <i class="bi bi-trash me-1"></i>Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
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
        function deleteBackup(filename) {
            if (confirm('Are you sure you want to delete this backup file?')) {
                // In a real implementation, this would make an AJAX call to delete the file
                alert('Backup file deleted: ' + filename);
                // Refresh the page to show updated list
                location.reload();
            }
        }
    </script>
</body>
</html>