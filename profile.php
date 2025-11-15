<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

$user_id = $_SESSION['user_id'] ?? '';
$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    // Get current user data
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        $error = "User not found.";
    } else {
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $error = "Current password is incorrect.";
        } else {
        // Update username and email
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ?");
        $stmt->bind_param("sss", $username, $email, $user_id);
        
        if ($stmt->execute()) {
            // Update session variables
            $_SESSION['username'] = $username ?? $_SESSION['username'];
            
            // Update password if provided
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt2 = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt2->bind_param("ss", $hashed_password, $user_id);
                $stmt2->execute();
                $stmt2->close();
                $success = "Profile and password updated successfully!";
            } else {
                $success = "Profile updated successfully!";
            }
        } else {
            $error = "Error updating profile: " . $conn->error;
        }
        $stmt->close();
        }
    }
}

// Get user data
$stmt = $conn->prepare("SELECT u.username, u.email, u.role, u.outlet_id, o.outlet_name FROM users u LEFT JOIN outlets o ON u.outlet_id = o.outlet_id WHERE u.user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$user_data) {
    $user_data = [
        'username' => $_SESSION['username'] ?? '',
        'email' => '',
        'role' => $_SESSION['role'] ?? '',
        'outlet_id' => $_SESSION['outlet_id'] ?? '',
        'outlet_name' => ''
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Lease Management System</title>
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
                        <a class="nav-link" href="leases.php">Leases</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="collections.php">Collections</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>User Profile</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h5>Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <input type="text" class="form-control" id="role" value="<?php echo htmlspecialchars($user_data['role'] ?? ''); ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label for="outlet_name" class="form-label">Outlet</label>
                                <input type="text" class="form-control" id="outlet_name" value="<?php echo htmlspecialchars(($user_data['outlet_name'] ?? '') ?: ($user_data['outlet_id'] ?? '')); ?>" readonly>
                            </div>
                            
                            <hr>
                            
                            <h5>Change Password</h5>
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password *</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <div class="form-text">Leave blank to keep current password</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
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
</body>
</html>