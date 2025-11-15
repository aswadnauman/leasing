<?php
// Setup script for Lease Management System
require_once 'config/db.php';

echo "<h2>Lease Management System - Database Setup</h2>";

// Create database connection without specifying database
$connection = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($connection->query($sql) === TRUE) {
    echo "<p>Database created successfully or already exists.</p>";
} else {
    echo "<p>Error creating database: " . $connection->error . "</p>";
}

// Select database
$connection->select_db(DB_NAME);

// Drop existing tables in correct order to avoid foreign key constraints
$tables = ['recovery_collections', 'leases', 'clients', 'recovery_persons', 'products', 'users', 'master_road', 'master_area', 'master_city', 'master_profession', 'outlets'];
foreach ($tables as $table) {
    $drop_sql = "DROP TABLE IF EXISTS `$table`";
    if ($connection->query($drop_sql)) {
        echo "<p>Dropped table `$table` if it existed.</p>";
    } else {
        echo "<p style='color: red;'>Error dropping table `$table`: " . $connection->error . "</p>";
    }
}

// Create tables in correct order
echo "<h3>Creating tables...</h3>";

// Outlet table
$outlet_table = "CREATE TABLE outlets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    outlet_id VARCHAR(50) UNIQUE NOT NULL,
    outlet_name VARCHAR(100) NOT NULL,
    city VARCHAR(50) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    outlet_type ENUM('Main', 'Sub', 'Franchise') NOT NULL,
    assigned_branch_manager VARCHAR(50) NOT NULL,
    linked_cash_account VARCHAR(50) NOT NULL,
    linked_bank_account VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($connection->query($outlet_table)) {
    echo "<p>Created table: outlets</p>";
} else {
    echo "<p style='color: red;'>Error creating outlets table: " . $connection->error . "</p>";
}

// Users table
$users_table = "CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'BranchManager', 'RecoveryOfficer', 'AccountsOfficer', 'DataEntry', 'Sales') NOT NULL,
    outlet_id VARCHAR(50) NOT NULL,
    assigned_areas TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (outlet_id) REFERENCES outlets(outlet_id)
)";

if ($connection->query($users_table)) {
    echo "<p>Created table: users</p>";
} else {
    echo "<p style='color: red;'>Error creating users table: " . $connection->error . "</p>";
}

// Master Profession table
$master_profession_table = "CREATE TABLE master_profession (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profession VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($connection->query($master_profession_table)) {
    echo "<p>Created table: master_profession</p>";
} else {
    echo "<p style='color: red;'>Error creating master_profession table: " . $connection->error . "</p>";
}

// Master Area table
$master_area_table = "CREATE TABLE master_area (
    id INT AUTO_INCREMENT PRIMARY KEY,
    area VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($connection->query($master_area_table)) {
    echo "<p>Created table: master_area</p>";
} else {
    echo "<p style='color: red;'>Error creating master_area table: " . $connection->error . "</p>";
}

// Master City table
$master_city_table = "CREATE TABLE master_city (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($connection->query($master_city_table)) {
    echo "<p>Created table: master_city</p>";
} else {
    echo "<p style='color: red;'>Error creating master_city table: " . $connection->error . "</p>";
}

// Master Road table
$master_road_table = "CREATE TABLE master_road (
    id INT AUTO_INCREMENT PRIMARY KEY,
    road VARCHAR(100) UNIQUE NOT NULL,
    area_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (area_id) REFERENCES master_area(id)
)";

if ($connection->query($master_road_table)) {
    echo "<p>Created table: master_road</p>";
} else {
    echo "<p style='color: red;'>Error creating master_road table: " . $connection->error . "</p>";
}

// Products table
$products_table = "CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id VARCHAR(50) UNIQUE NOT NULL,
    product_name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50) NOT NULL,
    brand VARCHAR(50),
    model VARCHAR(50),
    serial_number VARCHAR(100),
    purchase_price DECIMAL(10, 2) NOT NULL,
    leasing_rate DECIMAL(5, 2) NOT NULL,
    outlet_id VARCHAR(50) NOT NULL,
    status ENUM('Available', 'Leased', 'UnderMaintenance', 'Retired') DEFAULT 'Available',
    `condition` ENUM('New', 'Good', 'Fair', 'Poor'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (outlet_id) REFERENCES outlets(outlet_id)
)";

if ($connection->query($products_table)) {
    echo "<p>Created table: products</p>";
} else {
    echo "<p style='color: red;'>Error creating products table: " . $connection->error . "</p>";
}

// Recovery Persons table
$recovery_persons_table = "CREATE TABLE recovery_persons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recovery_person_id VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    cnic VARCHAR(20) UNIQUE NOT NULL,
    mobile_number VARCHAR(20),
    address TEXT,
    city_id INT,
    area_id INT,
    email VARCHAR(100),
    outlet_id VARCHAR(50) NOT NULL,
    photo_path VARCHAR(255),
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (city_id) REFERENCES master_city(id),
    FOREIGN KEY (area_id) REFERENCES master_area(id),
    FOREIGN KEY (outlet_id) REFERENCES outlets(outlet_id)
)";

if ($connection->query($recovery_persons_table)) {
    echo "<p>Created table: recovery_persons</p>";
} else {
    echo "<p style='color: red;'>Error creating recovery_persons table: " . $connection->error . "</p>";
}

// Clients table
$clients_table = "CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    father_husband_name VARCHAR(100),
    cnic VARCHAR(20) UNIQUE NOT NULL,
    mobile_primary VARCHAR(20),
    mobile_secondary VARCHAR(20),
    address_current TEXT,
    address_permanent TEXT,
    area VARCHAR(100),
    road VARCHAR(100),
    city VARCHAR(50),
    profession VARCHAR(100),
    manual_reference_no VARCHAR(50),
    status ENUM('Active', 'Blocked') DEFAULT 'Active',
    remarks TEXT,
    photo_path VARCHAR(255),
    outlet_id VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (outlet_id) REFERENCES outlets(outlet_id)
)";

if ($connection->query($clients_table)) {
    echo "<p>Created table: clients</p>";
} else {
    echo "<p style='color: red;'>Error creating clients table: " . $connection->error . "</p>";
}

// Leases table
$leases_table = "CREATE TABLE leases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lease_id VARCHAR(50) UNIQUE NOT NULL,
    client_id VARCHAR(50) NOT NULL,
    guarantor_id VARCHAR(50),
    recovery_person_id VARCHAR(50),
    product_id VARCHAR(50) NOT NULL,
    outlet_id VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    installment_amount DECIMAL(10, 2) NOT NULL,
    number_of_installments INT NOT NULL,
    paid_installments INT DEFAULT 0,
    outstanding_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('Active', 'Overdue', 'Closed', 'Cancelled') DEFAULT 'Active',
    late_fee DECIMAL(10, 2) DEFAULT 0,
    discount DECIMAL(10, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(client_id),
    FOREIGN KEY (guarantor_id) REFERENCES clients(client_id),
    FOREIGN KEY (recovery_person_id) REFERENCES recovery_persons(recovery_person_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (outlet_id) REFERENCES outlets(outlet_id)
)";

if ($connection->query($leases_table)) {
    echo "<p>Created table: leases</p>";
} else {
    echo "<p style='color: red;'>Error creating leases table: " . $connection->error . "</p>";
}

// Recovery collections table
$collections_table = "CREATE TABLE recovery_collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recovery_person_id VARCHAR(50) NOT NULL,
    collection_type ENUM('Cash', 'Bank', 'OnlineTransfer') NOT NULL,
    bank_name VARCHAR(100),
    account_number VARCHAR(50),
    reference_no VARCHAR(100),
    transaction_id VARCHAR(100),
    collection_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    amount DECIMAL(10, 2) NOT NULL,
    latitude VARCHAR(20),
    longitude VARCHAR(20),
    outlet_id VARCHAR(50) NOT NULL,
    approval_status ENUM('Pending', 'Verified', 'Approved', 'Rejected') DEFAULT 'Pending',
    supervisor_remarks TEXT,
    accounts_remarks TEXT,
    final_approval_remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (recovery_person_id) REFERENCES recovery_persons(recovery_person_id),
    FOREIGN KEY (outlet_id) REFERENCES outlets(outlet_id)
)";

if ($connection->query($collections_table)) {
    echo "<p>Created table: recovery_collections</p>";
} else {
    echo "<p style='color: red;'>Error creating recovery_collections table: " . $connection->error . "</p>";
}

// Insert sample data
echo "<h3>Inserting sample data...</h3>";

// Insert outlets
$insert_outlets = "INSERT INTO outlets (outlet_id, outlet_name, city, address, phone, email, outlet_type, assigned_branch_manager, linked_cash_account, linked_bank_account) VALUES
('OUT001', 'Main Branch', 'New York', '123 Main St, NY 10001', '212-555-1234', 'main@leasecompany.com', 'Main', 'USR001', 'CASH001', 'BANK001'),
('OUT002', 'Downtown Branch', 'New York', '456 Downtown Ave, NY 10002', '212-555-5678', 'downtown@leasecompany.com', 'Sub', 'USR002', 'CASH002', 'BANK002')";

if ($connection->query($insert_outlets)) {
    echo "<p>Inserted sample outlets</p>";
} else {
    echo "<p style='color: red;'>Error inserting outlets: " . $connection->error . "</p>";
}

// Insert users
$insert_users = "INSERT INTO users (user_id, username, email, password, role, outlet_id, assigned_areas, is_active) VALUES
('USR001', 'admin', 'admin@leasecompany.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'OUT001', NULL, TRUE),
('USR002', 'manager1', 'manager1@leasecompany.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'BranchManager', 'OUT001', NULL, TRUE),
('USR003', 'recovery1', 'recovery1@leasecompany.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'RecoveryOfficer', 'OUT001', '[\"Area A\", \"Area B\"]', TRUE),
('USR004', 'sales1', 'sales1@leasecompany.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sales', 'OUT001', NULL, TRUE)";

if ($connection->query($insert_users)) {
    echo "<p>Inserted sample users</p>";
} else {
    echo "<p style='color: red;'>Error inserting users: " . $connection->error . "</p>";
}

// Insert products
$insert_products = "INSERT INTO products (product_id, product_name, description, category, brand, model, serial_number, purchase_price, leasing_rate, outlet_id, status, `condition`) VALUES
('PROD001', 'Samsung TV 55\"', '55 inch 4K Smart TV', 'Electronics', 'Samsung', 'UN55TU7000', 'SAMSUNG001', 499.99, 15.50, 'OUT001', 'Available', 'New'),
('PROD002', 'Whirlpool Refrigerator', '25 cu ft French Door Refrigerator', 'Appliances', 'Whirlpool', 'WRX735SDHZ', 'WHIRLPOOL001', 1299.99, 35.75, 'OUT001', 'Available', 'New')";

if ($connection->query($insert_products)) {
    echo "<p>Inserted sample products</p>";
} else {
    echo "<p style='color: red;'>Error inserting products: " . $connection->error . "</p>";
}

// Insert clients
$insert_clients = "INSERT INTO clients (client_id, full_name, father_husband_name, cnic, mobile_primary, mobile_secondary, address_current, address_permanent, area, road, city, profession, manual_reference_no, status, remarks, outlet_id) VALUES
('CLI001', 'John Doe', 'Robert Doe', '12345-6789012-3', '0300-1234567', '0300-7654321', 'House 123, Street 45, Area A', 'Village ABC, District XYZ', 'Area A', 'Street 45', 'New York', 'Engineer', 'REF001', 'Active', 'Regular customer', 'OUT001'),
('CLI002', 'Jane Smith', 'Michael Smith', '23456-7890123-4', '0311-2345678', NULL, 'Apartment 456, Building B', 'House 789, Street C', 'Area B', 'Building B', 'New York', 'Teacher', 'REF002', 'Active', 'Good payment history', 'OUT001')";

if ($connection->query($insert_clients)) {
    echo "<p>Inserted sample clients</p>";
} else {
    echo "<p style='color: red;'>Error inserting clients: " . $connection->error . "</p>";
}

// Insert master data
$insert_master_city = "INSERT INTO master_city (city) VALUES ('New York'), ('Los Angeles'), ('Chicago')";
if ($connection->query($insert_master_city)) {
    echo "<p>Inserted sample cities</p>";
} else {
    echo "<p style='color: red;'>Error inserting cities: " . $connection->error . "</p>";
}

$insert_master_area = "INSERT INTO master_area (area) VALUES ('Area A'), ('Area B'), ('Area C')";
if ($connection->query($insert_master_area)) {
    echo "<p>Inserted sample areas</p>";
} else {
    echo "<p style='color: red;'>Error inserting areas: " . $connection->error . "</p>";
}

$insert_master_road = "INSERT INTO master_road (road, area_id) VALUES ('Street 45', 1), ('Building B', 2)";
if ($connection->query($insert_master_road)) {
    echo "<p>Inserted sample roads</p>";
} else {
    echo "<p style='color: red;'>Error inserting roads: " . $connection->error . "</p>";
}

$insert_master_profession = "INSERT INTO master_profession (profession) VALUES ('Engineer'), ('Teacher'), ('Doctor'), ('Lawyer')";
if ($connection->query($insert_master_profession)) {
    echo "<p>Inserted sample professions</p>";
} else {
    echo "<p style='color: red;'>Error inserting professions: " . $connection->error . "</p>";
}

// Insert recovery persons
$insert_recovery_persons = "INSERT INTO recovery_persons (recovery_person_id, full_name, cnic, mobile_number, address, city_id, area_id, email, outlet_id, status) VALUES
('RP001', 'Recovery Person 1', '11111-2222222-3', '0300-1111111', '123 Recovery St', 1, 1, 'recovery1@example.com', 'OUT001', 'Active'),
('RP002', 'Recovery Person 2', '22222-3333333-4', '0300-2222222', '456 Recovery Ave', 1, 2, 'recovery2@example.com', 'OUT001', 'Active')";
if ($connection->query($insert_recovery_persons)) {
    echo "<p>Inserted sample recovery persons</p>";
} else {
    echo "<p style='color: red;'>Error inserting recovery persons: " . $connection->error . "</p>";
}

// Close connection
$connection->close();

echo "<p><strong>Setup Complete!</strong></p>";
echo "<p>You can now access the application at: <a href='index.php'>http://localhost/Leasing</a></p>";
echo "<p>Default login credentials:</p>";
echo "<ul>";
echo "<li>Admin: username 'admin', password 'password123'</li>";
echo "<li>Branch Manager: username 'manager1', password 'password123'</li>";
echo "<li>Recovery Officer: username 'recovery1', password 'password123'</li>";
echo "<li>Sales: username 'sales1', password 'password123'</li>";
echo "</ul>";
echo "<h3>Applying Lease Management Extensions...</h3>";
require_once 'config/db.php';
$conn = getDBConnection();
$sql_content = file_get_contents('lease_management_extensions.sql');
$statements = explode(';', $sql_content);
foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '' || strpos($stmt, '--') === 0) { continue; }
    $conn->query($stmt);
}
// Ensure indexes exist for performance
$index_sql = [
    "CREATE INDEX IF NOT EXISTS idx_leases_client_id ON leases(client_id)",
    "CREATE INDEX IF NOT EXISTS idx_leases_status ON leases(status)",
    "CREATE INDEX IF NOT EXISTS idx_installment_lease_id ON installment_schedule(lease_id)",
    "CREATE INDEX IF NOT EXISTS idx_installment_status ON installment_schedule(status)"
];
foreach ($index_sql as $q) { $conn->query($q); }
$conn->close();
include 'create_menu_table.php';
echo "<p>Extensions applied and menus initialized.</p>";
?>