<?php
require_once 'config/db.php';

$conn = getDBConnection();

// Create menu_master table
$sql = "CREATE TABLE IF NOT EXISTS menu_master (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_name VARCHAR(100) NOT NULL,
    menu_url VARCHAR(255),
    parent_id INT DEFAULT 0,
    icon_class VARCHAR(50),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    required_permission VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>Menu master table created successfully</p>\n";
} else {
    echo "<p style='color:red;'>Error creating menu master table: " . $conn->error . "</p>\n";
}

// Insert default menu items
$menu_items = [
    // Dashboard
    ['Dashboard', 'dashboard.php', 0, 'bi-speedometer2', 1, 1, 'dashboard_view'],
    
    // Master Settings
    ['Master Settings', 'master_settings.php', 0, 'bi-gear', 2, 1, 'client_manage'],
    
    // Clients
    ['Clients', 'clients.php', 0, 'bi-people', 3, 1, 'client_manage'],
    
    // Lease Management
    ['Lease Management', 'leases.php', 0, 'bi-file-earmark-text', 4, 1, 'lease_manage'],
    ['Lease Registration', 'lease_registration.php', 4, 'bi-file-earmark-plus', 1, 1, 'lease_manage'],
    
    // Products & Inventory
    ['Products & Inventory', 'products.php', 0, 'bi-box-seam', 5, 1, 'product_manage'],
    ['Inventory Management', 'inventory_module.php', 5, 'bi-boxes', 1, 1, 'product_manage'],
    
    // Collections
    ['Collections', 'collections.php', 0, 'bi-cash-stack', 6, 1, 'collection_manage'],
    ['Payment Collection', 'payment_collection.php', 6, 'bi-cash', 1, 1, 'collection_manage'],
    
    // Reports
    ['Reports', 'reports.php', 0, 'bi-bar-chart', 7, 1, 'report_view'],
    ['Lease Register', 'lease_register_report.php', 7, 'bi-file-text', 1, 1, 'report_view'],
    ['Outstanding & Overdue', 'outstanding_overdue_report.php', 7, 'bi-exclamation-triangle', 2, 1, 'report_view'],
    ['Recovery Summary', 'recovery_summary_report.php', 7, 'bi-person-badge', 3, 1, 'report_view'],
    ['Client Summary', 'client_summary_report.php', 7, 'bi-people-fill', 4, 1, 'report_view'],
    ['Lease Income', 'lease_income_report.php', 7, 'bi-currency-dollar', 5, 1, 'report_view'],
    ['Multi-Outlet Consolidated', 'multi_outlet_consolidated_report.php', 7, 'bi-diagram-3', 6, 1, 'report_view'],
    
    // Administration
    ['Administration', '#', 0, 'bi-shield-lock', 8, 1, 'user_manage'],
    ['User Management', 'users.php', 8, 'bi-person', 1, 1, 'user_manage'],
    ['Role Management', 'role_management.php', 8, 'bi-shield-shaded', 2, 1, 'role_manage'],
    ['Audit Trail', 'audit_trail.php', 8, 'bi-activity', 3, 1, 'audit_view'],
    ['Backup & Restore', 'backup_restore.php', 8, 'bi-server', 4, 1, 'backup_manage']
];

// Clear existing menu items
$conn->query("DELETE FROM menu_master");

// Insert menu items
$stmt = $conn->prepare("INSERT INTO menu_master (menu_name, menu_url, parent_id, icon_class, sort_order, is_active, required_permission) VALUES (?, ?, ?, ?, ?, ?, ?)");

foreach ($menu_items as $item) {
    $stmt->bind_param("sssisss", $item[0], $item[1], $item[2], $item[3], $item[4], $item[5], $item[6]);
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'>Menu item '" . $item[0] . "' inserted successfully</p>\n";
    } else {
        echo "<p style='color:red;'>Error inserting menu item '" . $item[0] . "': " . $conn->error . "</p>\n";
    }
}

$stmt->close();
$conn->close();

echo "<h2>Process Complete</h2>\n";
echo "<p>Menu master table and default menu items have been created.</p>\n";
?>