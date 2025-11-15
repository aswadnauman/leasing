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
    
    // Handle City CRUD
    if (isset($_POST['add_city'])) {
        $city = $_POST['city'];
        $stmt = $conn->prepare("INSERT INTO master_city (city) VALUES (?)");
        $stmt->bind_param("s", $city);
        if ($stmt->execute()) {
            $success = "City added successfully!";
        } else {
            $error = "Error adding city: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_city'])) {
        $id = $_POST['id'];
        $city = $_POST['city'];
        $stmt = $conn->prepare("UPDATE master_city SET city=? WHERE id=?");
        $stmt->bind_param("si", $city, $id);
        if ($stmt->execute()) {
            $success = "City updated successfully!";
        } else {
            $error = "Error updating city: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_city'])) {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM master_city WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "City deleted successfully!";
        } else {
            $error = "Error deleting city: " . $conn->error;
        }
        $stmt->close();
    }
    
    // Handle Recovery Person CRUD
    if (isset($_POST['add_recovery_person'])) {
        $recovery_person_id = $_POST['recovery_person_id'];
        $full_name = $_POST['full_name'];
        $cnic = $_POST['cnic'];
        $mobile_number = $_POST['mobile_number'];
        $address = $_POST['address'];
        $city_id = $_POST['city_id'];
        $area_id = $_POST['area_id'];
        $email = $_POST['email'];
        $outlet_id = $_POST['outlet_id'];
        $status = $_POST['status'];
        
        // Handle photo upload
        $photo_path = null;
        if (isset($_POST['photo_data']) && !empty($_POST['photo_data'])) {
            $photo_data = $_POST['photo_data'];
            // Remove data URL prefix
            $photo_data = substr($photo_data, strpos($photo_data, ',') + 1);
            $photo_data = base64_decode($photo_data);
            
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/recovery_persons/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $filename = 'rp_' . $recovery_person_id . '_photo.jpg';
            $photo_path = $upload_dir . $filename;
            
            // Save photo
            file_put_contents($photo_path, $photo_data);
        }
        
        $stmt = $conn->prepare("INSERT INTO recovery_persons (recovery_person_id, full_name, cnic, mobile_number, address, city_id, area_id, email, outlet_id, status, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssiissis", $recovery_person_id, $full_name, $cnic, $mobile_number, $address, $city_id, $area_id, $email, $outlet_id, $status, $photo_path);
        if ($stmt->execute()) {
            $success = "Recovery person added successfully!";
        } else {
            $error = "Error adding recovery person: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_recovery_person'])) {
        $id = $_POST['id'];
        $recovery_person_id = $_POST['recovery_person_id'];
        $full_name = $_POST['full_name'];
        $cnic = $_POST['cnic'];
        $mobile_number = $_POST['mobile_number'];
        $address = $_POST['address'];
        $city_id = $_POST['city_id'];
        $area_id = $_POST['area_id'];
        $email = $_POST['email'];
        $outlet_id = $_POST['outlet_id'];
        $status = $_POST['status'];
        
        // Handle photo upload
        $photo_path = $_POST['existing_photo_path']; // Keep existing photo by default
        if (isset($_POST['photo_data']) && !empty($_POST['photo_data'])) {
            $photo_data = $_POST['photo_data'];
            // Remove data URL prefix
            $photo_data = substr($photo_data, strpos($photo_data, ',') + 1);
            $photo_data = base64_decode($photo_data);
            
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/recovery_persons/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $filename = 'rp_' . $recovery_person_id . '_photo.jpg';
            $photo_path = $upload_dir . $filename;
            
            // Save photo
            file_put_contents($photo_path, $photo_data);
        }
        
        $stmt = $conn->prepare("UPDATE recovery_persons SET recovery_person_id=?, full_name=?, cnic=?, mobile_number=?, address=?, city_id=?, area_id=?, email=?, outlet_id=?, status=?, photo_path=? WHERE id=?");
        $stmt->bind_param("sssssiissisi", $recovery_person_id, $full_name, $cnic, $mobile_number, $address, $city_id, $area_id, $email, $outlet_id, $status, $photo_path, $id);
        if ($stmt->execute()) {
            $success = "Recovery person updated successfully!";
        } else {
            $error = "Error updating recovery person: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_recovery_person'])) {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM recovery_persons WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Recovery person deleted successfully!";
        } else {
            $error = "Error deleting recovery person: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch data for all master tables with search functionality
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'profession';

// Fetch data for all master tables
if (!empty($search_term)) {
    $professions_result = $conn->prepare("SELECT * FROM master_profession WHERE profession LIKE ? ORDER BY profession");
    $search_param = "%" . $search_term . "%";
    $professions_result->bind_param("s", $search_param);
    $professions_result->execute();
    $professions_result = $professions_result->get_result();
    
    $areas_result = $conn->prepare("SELECT * FROM master_area WHERE area LIKE ? ORDER BY area");
    $areas_result->bind_param("s", $search_param);
    $areas_result->execute();
    $areas_result = $areas_result->get_result();
    
    $roads_result = $conn->prepare("SELECT r.*, a.area FROM master_road r LEFT JOIN master_area a ON r.area_id = a.id WHERE r.road LIKE ? OR a.area LIKE ? ORDER BY r.road");
    $roads_result->bind_param("ss", $search_param, $search_param);
    $roads_result->execute();
    $roads_result = $roads_result->get_result();
    
    $cities_result = $conn->prepare("SELECT * FROM master_city WHERE city LIKE ? ORDER BY city");
    $cities_result->bind_param("s", $search_param);
    $cities_result->execute();
    $cities_result = $cities_result->get_result();
    
    $recovery_persons_result = $conn->prepare("SELECT rp.*, c.city, a.area, o.outlet_name FROM recovery_persons rp LEFT JOIN master_city c ON rp.city_id = c.id LEFT JOIN master_area a ON rp.area_id = a.id LEFT JOIN outlets o ON rp.outlet_id = o.outlet_id WHERE rp.full_name LIKE ? OR rp.recovery_person_id LIKE ? OR rp.cnic LIKE ? OR c.city LIKE ? OR a.area LIKE ? OR o.outlet_name LIKE ? ORDER BY rp.full_name");
    $recovery_persons_result->bind_param("ssssss", $search_param, $search_param, $search_param, $search_param, $search_param, $search_param);
    $recovery_persons_result->execute();
    $recovery_persons_result = $recovery_persons_result->get_result();
} else {
    $professions_result = $conn->query("SELECT * FROM master_profession ORDER BY profession");
    $areas_result = $conn->query("SELECT * FROM master_area ORDER BY area");
    $roads_result = $conn->query("SELECT r.*, a.area FROM master_road r LEFT JOIN master_area a ON r.area_id = a.id ORDER BY r.road");
    $cities_result = $conn->query("SELECT * FROM master_city ORDER BY city");
    $recovery_persons_result = $conn->query("SELECT rp.*, c.city, a.area, o.outlet_name FROM recovery_persons rp LEFT JOIN master_city c ON rp.city_id = c.id LEFT JOIN master_area a ON rp.area_id = a.id LEFT JOIN outlets o ON rp.outlet_id = o.outlet_id ORDER BY rp.full_name");
}

// Fetch data for dropdowns
if (!empty($search_term)) {
    $areas_dropdown = $conn->prepare("SELECT * FROM master_area WHERE area LIKE ? ORDER BY area");
    $areas_dropdown->bind_param("s", $search_param);
    $areas_dropdown->execute();
    $areas_dropdown = $areas_dropdown->get_result();
    
    $cities_dropdown = $conn->prepare("SELECT * FROM master_city WHERE city LIKE ? ORDER BY city");
    $cities_dropdown->bind_param("s", $search_param);
    $cities_dropdown->execute();
    $cities_dropdown = $cities_dropdown->get_result();
    
    $outlets_dropdown = $conn->prepare("SELECT * FROM outlets WHERE outlet_name LIKE ? ORDER BY outlet_name");
    $outlets_dropdown->bind_param("s", $search_param);
    $outlets_dropdown->execute();
    $outlets_dropdown = $outlets_dropdown->get_result();
} else {
    $areas_dropdown = $conn->query("SELECT * FROM master_area ORDER BY area");
    $cities_dropdown = $conn->query("SELECT * FROM master_city ORDER BY city");
    $outlets_dropdown = $conn->query("SELECT * FROM outlets ORDER BY outlet_name");
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Settings - Lease Management System</title>
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
                <h1 class="h3 mb-1"><i class="bi bi-gear me-2"></i>Master Settings</h1>
                <p class="mb-0 text-muted">Manage system reference data</p>
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
        <div class="row">
            <div class="col-md-12">
                <h2>Master Settings</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Tabs for different master settings -->
                <ul class="nav nav-tabs" id="masterSettingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profession-tab" data-bs-toggle="tab" data-bs-target="#profession" type="button" role="tab">Profession</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="area-tab" data-bs-toggle="tab" data-bs-target="#area" type="button" role="tab">Area</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="road-tab" data-bs-toggle="tab" data-bs-target="#road" type="button" role="tab">Road</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="city-tab" data-bs-toggle="tab" data-bs-target="#city" type="button" role="tab">City</button>
                    </li>
                </ul>
                
                <div class="tab-content" id="masterSettingsTabContent">
                    <!-- Profession Tab -->
                    <div class="tab-pane fade show active" id="profession" role="tabpanel">
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5>Manage Professions</h5>
                            </div>
                            <div class="card-body">
                                <!-- Search Form -->
                                <form method="GET" class="mb-3">
                                    <input type="hidden" name="tab" value="profession">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" name="search" placeholder="Search professions..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <div class="btn-group" role="group">
                                                <button type="submit" class="btn btn-primary">Search</button>
                                                <a href="master_settings.php?tab=profession" class="btn btn-outline-secondary">Clear</a>
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
                    
                    <!-- Area Tab -->
                    <div class="tab-pane fade" id="area" role="tabpanel">
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5>Manage Areas</h5>
                            </div>
                            <div class="card-body">
                                <!-- Search Form -->
                                <form method="GET" class="mb-3">
                                    <input type="hidden" name="tab" value="area">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" name="search" placeholder="Search areas..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <div class="btn-group" role="group">
                                                <button type="submit" class="btn btn-primary">Search</button>
                                                <a href="master_settings.php?tab=area" class="btn btn-outline-secondary">Clear</a>
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
                    
                    <!-- Road Tab -->
                    <div class="tab-pane fade" id="road" role="tabpanel">
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5>Manage Roads</h5>
                            </div>
                            <div class="card-body">
                                <!-- Search Form -->
                                <form method="GET" class="mb-3">
                                    <input type="hidden" name="tab" value="road">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" name="search" placeholder="Search roads..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <div class="btn-group" role="group">
                                                <button type="submit" class="btn btn-primary">Search</button>
                                                <a href="master_settings.php?tab=road" class="btn btn-outline-secondary">Clear</a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                
                                <!-- Add Road Form -->
                                <form method="POST" class="mb-4" id="road-form">
                                    <input type="hidden" name="id" id="road-id">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <input type="text" class="form-control" name="road" id="road-name" placeholder="Enter road" required>
                                        </div>
                                        <div class="col-md-5">
                                            <select class="form-select" name="area_id" id="road-area-id" required>
                                                <option value="">Select Area</option>
                                                <?php if ($areas_dropdown && $areas_dropdown->num_rows > 0): ?>
                                                    <?php while ($area = $areas_dropdown->fetch_assoc()): ?>
                                                        <option value="<?php echo $area['id']; ?>"><?php echo htmlspecialchars($area['area']); ?></option>
                                                    <?php endwhile; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" name="add_road" class="btn btn-primary" id="road-submit-btn">Add Road</button>
                                            <button type="submit" name="update_road" class="btn btn-success" id="road-update-btn" style="display: none;">Update Road</button>
                                            <button type="button" class="btn btn-secondary" id="road-cancel-btn" style="display: none;">Cancel</button>
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
                    
                    <!-- City Tab -->
                    <div class="tab-pane fade" id="city" role="tabpanel">
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5>Manage Cities</h5>
                            </div>
                            <div class="card-body">
                                <!-- Search Form -->
                                <form method="GET" class="mb-3">
                                    <input type="hidden" name="tab" value="city">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" name="search" placeholder="Search cities..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <div class="btn-group" role="group">
                                                <button type="submit" class="btn btn-primary">Search</button>
                                                <a href="master_settings.php?tab=city" class="btn btn-outline-secondary">Clear</a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                
                                <!-- Add City Form -->
                                <form method="POST" class="mb-4" id="city-form">
                                    <input type="hidden" name="id" id="city-id">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" name="city" id="city-name" placeholder="Enter city" required>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="submit" name="add_city" class="btn btn-primary" id="city-submit-btn">Add City</button>
                                            <button type="submit" name="update_city" class="btn btn-success" id="city-update-btn" style="display: none;">Update City</button>
                                            <button type="button" class="btn btn-secondary" id="city-cancel-btn" style="display: none;">Cancel</button>
                                        </div>
                                    </div>
                                </form>
                                
                                <!-- Cities List -->
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>City</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($cities_result && $cities_result->num_rows > 0): ?>
                                                <?php while ($city = $cities_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($city['city']); ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary edit-city-btn" 
                                                                    data-id="<?php echo $city['id']; ?>"
                                                                    data-city="<?php echo htmlspecialchars($city['city']); ?>">
                                                                Edit
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger delete-city-btn" 
                                                                    data-id="<?php echo $city['id']; ?>"
                                                                    data-city="<?php echo htmlspecialchars($city['city']); ?>">
                                                                Delete
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="2" class="text-center">No cities found.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recovery Person Tab -->
                    <div class="tab-pane fade" id="recovery-person" role="tabpanel">
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5>Manage Recovery Persons</h5>
                            </div>
                            <div class="card-body">
                                <!-- Search Form -->
                                <form method="GET" class="mb-3">
                                    <input type="hidden" name="tab" value="recovery-person">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" name="search" placeholder="Search recovery persons..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <div class="btn-group" role="group">
                                                <button type="submit" class="btn btn-primary">Search</button>
                                                <a href="master_settings.php?tab=recovery-person" class="btn btn-outline-secondary">Clear</a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                
                                <!-- Add Recovery Person Form -->
                                <form method="POST" id="recovery-person-form">
                                    <input type="hidden" id="rp-id" name="id">
                                    <input type="hidden" id="rp-existing-photo-path" name="existing_photo_path">
                                    <input type="hidden" id="rp-photo-data" name="photo_data">
                                        
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="recovery_person_id" class="form-label">Recovery Person ID *</label>
                                            <input type="text" class="form-control" id="recovery_person_id" name="recovery_person_id" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="full_name" class="form-label">Full Name *</label>
                                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                                        </div>
                                    </div>
                                        
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="cnic" class="form-label">CNIC *</label>
                                            <input type="text" class="form-control" id="cnic" name="cnic" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="mobile_number" class="form-label">Mobile Number</label>
                                            <input type="text" class="form-control" id="mobile_number" name="mobile_number">
                                        </div>
                                    </div>
                                        
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                                    </div>
                                        
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="city_id" class="form-label">City</label>
                                            <select class="form-select" id="city_id" name="city_id">
                                                <option value="">Select City</option>
                                                <?php if ($cities_dropdown && $cities_dropdown->num_rows > 0): ?>
                                                    <?php while ($city = $cities_dropdown->fetch_assoc()): ?>
                                                        <option value="<?php echo $city['id']; ?>"><?php echo htmlspecialchars($city['city']); ?></option>
                                                    <?php endwhile; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="area_id" class="form-label">Area</label>
                                            <select class="form-select" id="area_id" name="area_id">
                                                <option value="">Select Area</option>
                                                <?php if ($areas_dropdown && $areas_dropdown->num_rows > 0): ?>
                                                    <?php while ($area = $areas_dropdown->fetch_assoc()): ?>
                                                        <option value="<?php echo $area['id']; ?>"><?php echo htmlspecialchars($area['area']); ?></option>
                                                    <?php endwhile; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                        
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="outlet_id" class="form-label">Assigned Outlet *</label>
                                            <select class="form-select" id="outlet_id" name="outlet_id" required>
                                                <option value="">Select Outlet</option>
                                                <?php if ($outlets_dropdown && $outlets_dropdown->num_rows > 0): ?>
                                                    <?php while ($outlet = $outlets_dropdown->fetch_assoc()): ?>
                                                        <option value="<?php echo $outlet['outlet_id']; ?>"><?php echo htmlspecialchars($outlet['outlet_name']); ?></option>
                                                    <?php endwhile; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                        
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="1">Active</option>
                                                <option value="0">Inactive</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <!-- Photo Capture Section -->
                                            <div class="card">
                                                <div class="card-header">
                                                    <h6>Recovery Person Photo</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <div class="mb-3">
                                                                <label for="rp-photo-upload" class="form-label">Upload Photo</label>
                                                                <input type="file" class="form-control" id="rp-photo-upload" accept="image/*">
                                                                <div class="form-text">JPG or PNG format, automatically optimized to ≤ 20KB</div>
                                                                <img id="rp-photo-preview" src="" alt="Photo Preview" style="display: none; max-width: 100%; height: 150px; margin-top: 10px;">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                        
                                    <button type="submit" name="add_recovery_person" class="btn btn-primary">Add Recovery Person</button>
                                    <button type="button" class="btn btn-secondary" id="rp-cancel-btn" style="display:none;">Cancel</button>
                                </form>
                                
                                <!-- Recovery Persons List -->
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Photo</th>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>CNIC</th>
                                                <th>Mobile</th>
                                                <th>City</th>
                                                <th>Area</th>
                                                <th>Outlet</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($recovery_persons_result && $recovery_persons_result->num_rows > 0): ?>
                                                <?php while ($person = $recovery_persons_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td>
                                                            <?php if (!empty($person['photo_path']) && file_exists($person['photo_path'])): ?>
                                                                <img src="<?php echo htmlspecialchars($person['photo_path']); ?>" alt="Photo" width="50" height="50" class="rounded">
                                                            <?php else: ?>
                                                                <div class="bg-light border rounded" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                                                    <span class="text-muted">No Photo</span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($person['recovery_person_id']); ?></td>
                                                        <td><?php echo htmlspecialchars($person['full_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($person['cnic']); ?></td>
                                                        <td><?php echo htmlspecialchars($person['mobile_number']); ?></td>
                                                        <td><?php echo htmlspecialchars($person['city'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($person['area'] ?? 'N/A'); ?></td>
                                                        <td><?php echo htmlspecialchars($person['outlet_name'] ?? 'N/A'); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $person['status'] == 'Active' ? 'success' : 'secondary'; ?>">
                                                                <?php echo htmlspecialchars($person['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary edit-recovery-person-btn" 
                                                                    data-id="<?php echo $person['id']; ?>"
                                                                    data-recovery_person_id="<?php echo htmlspecialchars($person['recovery_person_id']); ?>"
                                                                    data-full_name="<?php echo htmlspecialchars($person['full_name']); ?>"
                                                                    data-cnic="<?php echo htmlspecialchars($person['cnic']); ?>"
                                                                    data-mobile_number="<?php echo htmlspecialchars($person['mobile_number']); ?>"
                                                                    data-address="<?php echo htmlspecialchars($person['address']); ?>"
                                                                    data-city_id="<?php echo $person['city_id'] ?? ''; ?>"
                                                                    data-area_id="<?php echo $person['area_id'] ?? ''; ?>"
                                                                    data-email="<?php echo htmlspecialchars($person['email']); ?>"
                                                                    data-outlet_id="<?php echo htmlspecialchars($person['outlet_id']); ?>"
                                                                    data-status="<?php echo $person['status'] == 'Active' ? 1 : 0; ?>"
                                                                    data-photo_path="<?php echo htmlspecialchars($person['photo_path']); ?>">
                                                                Edit
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger delete-recovery-person-btn" 
                                                                    data-id="<?php echo $person['id']; ?>"
                                                                    data-name="<?php echo htmlspecialchars($person['full_name']); ?>">
                                                                Delete
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="10" class="text-center">No recovery persons found.</td>
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
        </div>
    </div>

    <!-- Delete Confirmation Modals -->
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

    <div class="modal fade" id="deleteCityModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the city "<span id="delete-city-name"></span>"?</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" id="delete-city-id" name="id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_city" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteRecoveryPersonModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the recovery person "<span id="delete-recovery-person-name"></span>"?</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" id="delete-recovery-person-id" name="id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_recovery_person" class="btn btn-danger">Delete</button>
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
        // Initialize Select2 on all select elements
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2 on all select elements with search functionality
            $('select').select2({
                placeholder: "Select an option",
                allowClear: true,
                width: '100%'
            });
            
            // Handle file upload for recovery person
            handleFileUpload('rp-photo-upload', 'rp-photo-preview');
        });
        
        // Pass PHP data to JavaScript
        const areasData = <?php 
        $areas_array = [];
        if ($areas_dropdown && $areas_dropdown->num_rows > 0) {
            while ($area = $areas_dropdown->fetch_assoc()) {
                $areas_array[] = $area;
            }
        }
        echo json_encode($areas_array);
        ?>;
        
        // Initialize file upload handler when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Handle file upload for recovery person
            handleFileUpload('rp-photo-upload', 'rp-photo-preview');
        });
        
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
        
        // Edit City Button
        document.querySelectorAll('.edit-city-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const city = this.getAttribute('data-city');
                
                // Populate form fields
                document.getElementById('city-id').value = id;
                document.getElementById('city-name').value = city;
                
                // Show update buttons and hide add button
                document.getElementById('city-submit-btn').style.display = 'none';
                document.getElementById('city-update-btn').style.display = 'inline-block';
                document.getElementById('city-cancel-btn').style.display = 'inline-block';
                
                // Scroll to the form
                document.getElementById('city-form').scrollIntoView({ behavior: 'smooth' });
            });
        });
        
        // Cancel City Edit
        document.getElementById('city-cancel-btn').addEventListener('click', function() {
            // Reset form
            document.getElementById('city-form').reset();
            document.getElementById('city-id').value = '';
            
            // Show add button and hide update buttons
            document.getElementById('city-submit-btn').style.display = 'inline-block';
            document.getElementById('city-update-btn').style.display = 'none';
            this.style.display = 'none';
        });
        
        // Delete City Button
        document.querySelectorAll('.delete-city-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const city = this.getAttribute('data-city');
                
                document.getElementById('delete-city-id').value = id;
                document.getElementById('delete-city-name').textContent = city;
                
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteCityModal'));
                deleteModal.show();
            });
        });
        
        // Activate tab based on URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab) {
            const tabButton = document.querySelector(`button[data-bs-target="#${tab}"]`);
            if (tabButton) {
                const tab = new bootstrap.Tab(tabButton);
                tab.show();
            }
        }
    </script>
</body>
</html>