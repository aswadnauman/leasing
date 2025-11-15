<?php
session_start();
require_once 'config/db.php';
require_once 'includes/dynamic_dropdowns.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle AJAX search requests
handleMasterDataAjax(getDBConnection());

// Handle Select2 AJAX requests
if (isset($_GET['action']) && $_GET['action'] == 'select2_search' && isset($_GET['q'])) {
    $conn = getDBConnection();
    $search = $_GET['q'];
    $searchTerm = "%$search%";
    
    $stmt = $conn->prepare("SELECT product_id, product_name FROM products WHERE status='Available' AND (product_name LIKE ? OR product_id LIKE ? OR serial_number LIKE ?) ORDER BY product_name LIMIT 20");
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $results = array();
    while ($row = $result->fetch_assoc()) {
        $results[] = array(
            'id' => $row['product_id'],
            'text' => $row['product_name']
        );
    }
    
    header('Content-Type: application/json');
    echo json_encode($results);
    $conn->close();
    exit();
}

$conn = getDBConnection();

// Function to compress image
function compressImage($source, $destination, $quality) {
    $info = getimagesize($source);
    
    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
    } elseif ($info['mime'] == 'image/gif') {
        $image = imagecreatefromgif($source);
    } else {
        return false;
    }
    
    // Compress and save image
    imagejpeg($image, $destination, $quality);
    imagedestroy($image);
    
    return true;
}

// Handle form submission for adding/updating products
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        // Add new product
        $product_id = $_POST['product_id'];
        $product_name = $_POST['product_name'];
        $description = $_POST['description'];
        $category = $_POST['category'];
        $brand = $_POST['brand'];
        $model = $_POST['model'];
        $serial_number = $_POST['serial_number'];
        $purchase_price = $_POST['purchase_price'];
        $leasing_rate = $_POST['leasing_rate'];
        $outlet_id = $_POST['outlet_id'];
        $condition = $_POST['condition'];
        
        // Handle photo upload
        $photo_path = null;
        if (isset($_POST['photo_data']) && !empty($_POST['photo_data'])) {
            $photo_data = $_POST['photo_data'];
            // Remove data URL prefix
            $photo_data = substr($photo_data, strpos($photo_data, ',') + 1);
            $photo_data = base64_decode($photo_data);
            
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $filename = 'product_' . $product_id . '_photo.jpg';
            $temp_path = $upload_dir . 'temp_' . $filename;
            $photo_path = $upload_dir . $filename;
            
            // Save temporary photo
            file_put_contents($temp_path, $photo_data);
            
            // Compress image to <= 20KB
            $quality = 70; // Start with 70% quality
            while (filesize($temp_path) > 20480 && $quality > 10) { // 20KB = 20480 bytes
                compressImage($temp_path, $photo_path, $quality);
                $quality -= 10;
            }
            
            // If still too large, resize
            if (filesize($photo_path) > 20480) {
                $image = imagecreatefromjpeg($photo_path);
                $width = imagesx($image);
                $height = imagesy($image);
                
                // Calculate new dimensions while maintaining aspect ratio
                $new_width = $width * 0.8;
                $new_height = $height * 0.8;
                
                $new_image = imagecreatetruecolor($new_width, $new_height);
                imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagejpeg($new_image, $photo_path, 50);
                imagedestroy($image);
                imagedestroy($new_image);
            }
            
            // Clean up temp file
            unlink($temp_path);
        }
        
        $stmt = $conn->prepare("INSERT INTO products (product_id, product_name, description, category, brand, model, serial_number, purchase_price, leasing_rate, outlet_id, `condition`, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssdsss", $product_id, $product_name, $description, $category, $brand, $model, $serial_number, $purchase_price, $leasing_rate, $outlet_id, $condition, $photo_path);
        
        if ($stmt->execute()) {
            $success = "Product added successfully!";
            // Update product status based on stock levels
            updateProductStatus($conn, $product_id);
        } else {
            $error = "Error adding product: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_product'])) {
        // Update product
        $id = $_POST['id'];
        $product_name = $_POST['product_name'];
        $description = $_POST['description'];
        $category = $_POST['category'];
        $brand = $_POST['brand'];
        $model = $_POST['model'];
        $serial_number = $_POST['serial_number'];
        $purchase_price = $_POST['purchase_price'];
        $leasing_rate = $_POST['leasing_rate'];
        $outlet_id = $_POST['outlet_id'];
        $status = $_POST['status'];
        $condition = $_POST['condition'];
        
        // Handle photo upload
        $photo_path = $_POST['existing_photo_path']; // Keep existing photo by default
        if (isset($_POST['photo_data']) && !empty($_POST['photo_data'])) {
            $photo_data = $_POST['photo_data'];
            // Remove data URL prefix
            $photo_data = substr($photo_data, strpos($photo_data, ',') + 1);
            $photo_data = base64_decode($photo_data);
            
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $filename = 'product_' . $_POST['product_id'] . '_photo.jpg';
            $temp_path = $upload_dir . 'temp_' . $filename;
            $photo_path = $upload_dir . $filename;
            
            // Save temporary photo
            file_put_contents($temp_path, $photo_data);
            
            // Compress image to <= 20KB
            $quality = 70; // Start with 70% quality
            while (filesize($temp_path) > 20480 && $quality > 10) { // 20KB = 20480 bytes
                compressImage($temp_path, $photo_path, $quality);
                $quality -= 10;
            }
            
            // If still too large, resize
            if (filesize($photo_path) > 20480) {
                $image = imagecreatefromjpeg($photo_path);
                $width = imagesx($image);
                $height = imagesy($image);
                
                // Calculate new dimensions while maintaining aspect ratio
                $new_width = $width * 0.8;
                $new_height = $height * 0.8;
                
                $new_image = imagecreatetruecolor($new_width, $new_height);
                imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagejpeg($new_image, $photo_path, 50);
                imagedestroy($image);
                imagedestroy($new_image);
            }
            
            // Clean up temp file
            unlink($temp_path);
        }
        
        $stmt = $conn->prepare("UPDATE products SET product_name=?, description=?, category=?, brand=?, model=?, serial_number=?, purchase_price=?, leasing_rate=?, outlet_id=?, status=?, `condition`=?, photo_path=? WHERE id=?");
        $stmt->bind_param("ssssssddssssi", $product_name, $description, $category, $brand, $model, $serial_number, $purchase_price, $leasing_rate, $outlet_id, $status, $condition, $photo_path, $id);
        
        if ($stmt->execute()) {
            $success = "Product updated successfully!";
            // Update product status based on stock levels
            updateProductStatus($conn, $product_id);
        } else {
            $error = "Error updating product: " . $conn->error;
        }
        $stmt->close();
    } elseif (isset($_POST['delete_product'])) {
        // Delete product
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = "Product deleted successfully!";
        } else {
            $error = "Error deleting product: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all products with related data and stock levels
$products_result = $conn->query("
    SELECT 
        p.*, 
        o.outlet_name,
        COALESCE(SUM(CASE WHEN it.transaction_type = 'Purchase' THEN it.quantity ELSE 0 END), 0) as total_purchased,
        COALESCE(SUM(CASE WHEN it.transaction_type = 'Sale' THEN it.quantity ELSE 0 END), 0) as total_sold,
        COALESCE(SUM(CASE WHEN it.transaction_type = 'PurchaseReturn' THEN it.quantity ELSE 0 END), 0) as total_purchase_returns,
        COALESCE(SUM(CASE WHEN it.transaction_type = 'SaleReturn' THEN it.quantity ELSE 0 END), 0) as total_sale_returns
    FROM products p
    JOIN outlets o ON p.outlet_id = o.outlet_id
    LEFT JOIN inventory_transactions it ON p.product_id = it.product_id
    GROUP BY p.id, p.product_id, p.product_name, p.description, p.category, p.brand, p.model, p.serial_number, p.purchase_price, p.leasing_rate, p.outlet_id, p.status, p.`condition`, p.created_at, p.updated_at, o.outlet_name
    ORDER BY p.created_at DESC
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
    <title>Products - Lease Management System</title>
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
                <h1 class="h3 mb-1"><i class="bi bi-box-seam me-2"></i>Product Management</h1>
                <p class="mb-0 text-muted">Manage products in the inventory</p>
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
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1><i class="bi bi-box-seam me-3"></i>Product Management</h1>
                            <p class="mb-0">Manage products and inventory</p>
                        </div>
                    </div>
                </div>

                <!-- Breadcrumb Navigation -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="products.php">Inventory & Product</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Product Management</li>
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
                
                <!-- Add Product Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-box-seam me-2"></i>Add New Product</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="product_id" class="form-label">Product ID *</label>
                                        <input type="text" class="form-control" id="product_id" name="product_id" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="product_name" class="form-label">Product Name *</label>
                                        <input type="text" class="form-control" id="product_name" name="product_name" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="category" class="form-label">Category *</label>
                                        <input type="text" class="form-control" id="category" name="category" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="brand" class="form-label">Brand</label>
                                        <input type="text" class="form-control" id="brand" name="brand">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="model" class="form-label">Model</label>
                                        <input type="text" class="form-control" id="model" name="model">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="serial_number" class="form-label">Serial Number</label>
                                        <input type="text" class="form-control" id="serial_number" name="serial_number">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="purchase_price" class="form-label">Purchase Price *</label>
                                        <input type="number" class="form-control" id="purchase_price" name="purchase_price" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="leasing_rate" class="form-label">Leasing Rate (%) *</label>
                                        <input type="number" class="form-control" id="leasing_rate" name="leasing_rate" step="0.01" required>
                                    </div>
                                </div>
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
                                        <label for="condition" class="form-label">Condition</label>
                                        <select class="form-select" id="condition" name="condition">
                                            <option value="New">New</option>
                                            <option value="Good">Good</option>
                                            <option value="Fair">Fair</option>
                                            <option value="Poor">Poor</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="photo" class="form-label">Product Photo</label>
                                <input type="file" class="form-control" id="photo" accept="image/*" capture="camera">
                                <div class="form-text">Capture or upload a photo of the product. Image will be compressed to ≤20KB.</div>
                                <input type="hidden" id="photo_data" name="photo_data">
                                <div class="mt-2">
                                    <img id="photo_preview" src="" alt="Product Photo Preview" style="max-width: 200px; display: none;">
                                </div>
                            </div>
                            
                            <button type="submit" name="add_product" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Add Product</button>
                        </form>
                    </div>
                </div>
                
                <!-- Products List -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-list me-2"></i>All Products</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product ID</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Brand</th>
                                        <th>Price</th>
                                        <th>Rate (%)</th>
                                        <th>Outlet</th>
                                        <th>Status</th>
                                        <th>Condition</th>
                                        <th>Stock</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($products_result && $products_result->num_rows > 0): ?>
                                        <?php while ($product = $products_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                                <td><?php echo htmlspecialchars($product['brand']); ?></td>
                                                <td>$<?php echo number_format($product['purchase_price'], 2); ?></td>
                                                <td><?php echo number_format($product['leasing_rate'], 2); ?>%</td>
                                                <td><?php echo htmlspecialchars($product['outlet_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $product['status'] == 'Available' ? 'success' : 
                                                            ($product['status'] == 'Leased' ? 'warning' : 
                                                            ($product['status'] == 'UnderMaintenance' ? 'info' : 'secondary')); 
                                                    ?>">
                                                        <?php echo htmlspecialchars($product['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($product['condition']); ?></td>
                                                <td>
                                                    <?php 
                                                    $available_stock = $product['total_purchased'] - $product['total_sold'] + $product['total_purchase_returns'] - $product['total_sale_returns'];
                                                    echo $available_stock;
                                                    ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary edit-btn" 
                                                            data-id="<?php echo $product['id']; ?>"
                                                            data-product_id="<?php echo htmlspecialchars($product['product_id']); ?>"
                                                            data-product_name="<?php echo htmlspecialchars($product['product_name']); ?>"
                                                            data-description="<?php echo htmlspecialchars($product['description']); ?>"
                                                            data-category="<?php echo htmlspecialchars($product['category']); ?>"
                                                            data-brand="<?php echo htmlspecialchars($product['brand']); ?>"
                                                            data-model="<?php echo htmlspecialchars($product['model']); ?>"
                                                            data-serial_number="<?php echo htmlspecialchars($product['serial_number']); ?>"
                                                            data-purchase_price="<?php echo $product['purchase_price']; ?>"
                                                            data-leasing_rate="<?php echo $product['leasing_rate']; ?>"
                                                            data-outlet_id="<?php echo htmlspecialchars($product['outlet_id']); ?>"
                                                            data-status="<?php echo htmlspecialchars($product['status']); ?>"
                                                            data-condition="<?php echo htmlspecialchars($product['condition']); ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger delete-btn" 
                                                            data-id="<?php echo $product['id']; ?>"
                                                            data-product_id="<?php echo htmlspecialchars($product['product_id']); ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="11" class="text-center">No products found.</td>
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

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="edit-id" name="id">
                        <div class="mb-3">
                            <label for="edit-product_name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="edit-product_name" name="product_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit-description" name="description" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit-category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="edit-category" name="category" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-brand" class="form-label">Brand</label>
                            <input type="text" class="form-control" id="edit-brand" name="brand">
                        </div>
                        <div class="mb-3">
                            <label for="edit-model" class="form-label">Model</label>
                            <input type="text" class="form-control" id="edit-model" name="model">
                        </div>
                        <div class="mb-3">
                            <label for="edit-serial_number" class="form-label">Serial Number</label>
                            <input type="text" class="form-control" id="edit-serial_number" name="serial_number">
                        </div>
                        <div class="mb-3">
                            <label for="edit-purchase_price" class="form-label">Purchase Price</label>
                            <input type="number" class="form-control" id="edit-purchase_price" name="purchase_price" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-leasing_rate" class="form-label">Leasing Rate (%)</label>
                            <input type="number" class="form-control" id="edit-leasing_rate" name="leasing_rate" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-outlet_id" class="form-label">Outlet</label>
                            <select class="form-select" id="edit-outlet_id" name="outlet_id" required>
                                <option value="">Select Outlet</option>
                                <?php 
                                // Reset the result pointer
                                mysqli_data_seek($outlets_result, 0);
                                if ($outlets_result && $outlets_result->num_rows > 0): ?>
                                    <?php while ($outlet = $outlets_result->fetch_assoc()): ?>
                                        <option value="<?php echo $outlet['outlet_id']; ?>"><?php echo htmlspecialchars($outlet['outlet_name']); ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit-status" class="form-label">Status</label>
                            <select class="form-select" id="edit-status" name="status">
                                <option value="Available">Available</option>
                                <option value="Leased">Leased</option>
                                <option value="UnderMaintenance">Under Maintenance</option>
                                <option value="Retired">Retired</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit-condition" class="form-label">Condition</label>
                            <select class="form-select" id="edit-condition" name="condition">
                                <option value="New">New</option>
                                <option value="Good">Good</option>
                                <option value="Fair">Fair</option>
                                <option value="Poor">Poor</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_photo" class="form-label">Product Photo</label>
                            <input type="file" class="form-control" id="edit_photo" accept="image/*" capture="camera">
                            <div class="form-text">Capture or upload a photo of the product. Image will be compressed to ≤20KB.</div>
                            <input type="hidden" id="edit_photo_data" name="photo_data">
                            <input type="hidden" id="existing_photo_path" name="existing_photo_path">
                            <div class="mt-2">
                                <img id="edit_photo_preview" src="" alt="Product Photo Preview" style="max-width: 200px; display: none;">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_product" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Delete Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="delete-id" name="id">
                        <p>Are you sure you want to delete product <strong id="delete-product-id"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_product" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="bg-light text-center py-4">
        <div class="container">
            <p class="mb-0">&copy; 2025 Lease Management System. All rights reserved.</p>
            <p class="mb-0 text-muted small">Version 1.0.0</p>
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
            
            // Handle photo upload and preview for add form
            document.getElementById('photo').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // Preview image
                        const preview = document.getElementById('photo_preview');
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                        
                        // Store base64 data in hidden field
                        document.getElementById('photo_data').value = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Handle photo upload and preview for edit form
            document.getElementById('edit_photo').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // Preview image
                        const preview = document.getElementById('edit_photo_preview');
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                        
                        // Store base64 data in hidden field
                        document.getElementById('edit_photo_data').value = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Edit button functionality
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const product_id = this.getAttribute('data-product_id');
                    const product_name = this.getAttribute('data-product_name');
                    const description = this.getAttribute('data-description');
                    const category = this.getAttribute('data-category');
                    const brand = this.getAttribute('data-brand');
                    const model = this.getAttribute('data-model');
                    const serial_number = this.getAttribute('data-serial_number');
                    const purchase_price = this.getAttribute('data-purchase_price');
                    const leasing_rate = this.getAttribute('data-leasing_rate');
                    const outlet_id = this.getAttribute('data-outlet_id');
                    const status = this.getAttribute('data-status');
                    const condition = this.getAttribute('data-condition');
                    
                    // Fill modal fields
                    document.getElementById('edit-id').value = id;
                    document.getElementById('edit-product_name').value = product_name;
                    document.getElementById('edit-description').value = description;
                    document.getElementById('edit-category').value = category;
                    document.getElementById('edit-brand').value = brand;
                    document.getElementById('edit-model').value = model;
                    document.getElementById('edit-serial_number').value = serial_number;
                    document.getElementById('edit-purchase_price').value = purchase_price;
                    document.getElementById('edit-leasing_rate').value = leasing_rate;
                    document.getElementById('edit-outlet_id').value = outlet_id;
                    document.getElementById('edit-status').value = status;
                    document.getElementById('edit-condition').value = condition;
                    
                    // Clear previous photo preview
                    document.getElementById('edit_photo_preview').style.display = 'none';
                    document.getElementById('edit_photo_data').value = '';
                    
                    // Set modal title
                    document.querySelector('.modal-title').textContent = 'Edit Product - ' + product_id;
                    
                    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
                    editModal.show();
                });
            });
            
            // Delete button functionality
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const product_id = this.getAttribute('data-product_id');
                    
                    document.getElementById('delete-id').value = id;
                    document.getElementById('delete-product-id').textContent = product_id;
                    
                    var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                    deleteModal.show();
                });
            });
        });
        
    </script>
</body>
</html>