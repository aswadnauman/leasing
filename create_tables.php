<?php
require_once 'config/db.php';

$conn = getDBConnection();

// Create chart_of_accounts table
$sql = "CREATE TABLE IF NOT EXISTS chart_of_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(20) UNIQUE NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    account_type ENUM('Asset', 'Liability', 'Equity', 'Income', 'Expense') NOT NULL,
    parent_account_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_account_id) REFERENCES chart_of_accounts(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table chart_of_accounts created successfully\n";
} else {
    echo "Error creating table chart_of_accounts: " . $conn->error . "\n";
}

// Create suppliers table
$sql = "CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id VARCHAR(50) UNIQUE NOT NULL,
    supplier_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    city VARCHAR(50),
    outlet_id VARCHAR(50) NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (outlet_id) REFERENCES outlets(outlet_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table suppliers created successfully\n";
} else {
    echo "Error creating table suppliers: " . $conn->error . "\n";
}

// Create customers table
$sql = "CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id VARCHAR(50) UNIQUE NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    city VARCHAR(50),
    outlet_id VARCHAR(50) NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (outlet_id) REFERENCES outlets(outlet_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table customers created successfully\n";
} else {
    echo "Error creating table customers: " . $conn->error . "\n";
}

// Create sales table
$sql = "CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id VARCHAR(50) UNIQUE NOT NULL,
    customer_id VARCHAR(50) NOT NULL,
    outlet_id VARCHAR(50) NOT NULL,
    sale_date DATE NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    discount DECIMAL(10, 2) DEFAULT 0,
    tax_amount DECIMAL(10, 2) DEFAULT 0,
    net_amount DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('Paid', 'Partial', 'Unpaid') DEFAULT 'Unpaid',
    remarks TEXT,
    created_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (outlet_id) REFERENCES outlets(outlet_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table sales created successfully\n";
} else {
    echo "Error creating table sales: " . $conn->error . "\n";
}

$conn->close();
?>