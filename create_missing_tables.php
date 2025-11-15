<?php
require_once 'config/db.php';

$conn = getDBConnection();

// Create tables that are missing
$tables_to_create = [
    // Chart of Accounts table
    "CREATE TABLE IF NOT EXISTS chart_of_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        account_code VARCHAR(20) UNIQUE NOT NULL,
        account_name VARCHAR(100) NOT NULL,
        account_type ENUM('Asset', 'Liability', 'Equity', 'Income', 'Expense') NOT NULL,
        parent_account_id INT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_account_id) REFERENCES chart_of_accounts(id)
    )",
    
    // Customers table
    "CREATE TABLE IF NOT EXISTS customers (
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
    )",
    
    // Suppliers table
    "CREATE TABLE IF NOT EXISTS suppliers (
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
    )",
    
    // Sales table
    "CREATE TABLE IF NOT EXISTS sales (
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
    )",
    
    // Sales Items table
    "CREATE TABLE IF NOT EXISTS sales_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sale_id VARCHAR(50) NOT NULL,
        product_id VARCHAR(50) NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10, 2) NOT NULL,
        total_price DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (sale_id) REFERENCES sales(sale_id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(product_id)
    )",
    
    // Sales Returns table
    "CREATE TABLE IF NOT EXISTS sales_returns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        return_id VARCHAR(50) UNIQUE NOT NULL,
        sale_id VARCHAR(50) NOT NULL,
        customer_id VARCHAR(50) NOT NULL,
        outlet_id VARCHAR(50) NOT NULL,
        return_date DATE NOT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        remarks TEXT,
        created_by VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (sale_id) REFERENCES sales(sale_id),
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
        FOREIGN KEY (outlet_id) REFERENCES outlets(outlet_id),
        FOREIGN KEY (created_by) REFERENCES users(user_id)
    )",
    
    // Sales Return Items table
    "CREATE TABLE IF NOT EXISTS sales_return_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        return_id VARCHAR(50) NOT NULL,
        product_id VARCHAR(50) NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10, 2) NOT NULL,
        total_price DECIMAL(10, 2) NOT NULL,
        reason TEXT,
        FOREIGN KEY (return_id) REFERENCES sales_returns(return_id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(product_id)
    )",
    
    // Purchases table
    "CREATE TABLE IF NOT EXISTS purchases (
        id INT AUTO_INCREMENT PRIMARY KEY,
        purchase_id VARCHAR(50) UNIQUE NOT NULL,
        supplier_id VARCHAR(50) NOT NULL,
        outlet_id VARCHAR(50) NOT NULL,
        purchase_date DATE NOT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        discount DECIMAL(10, 2) DEFAULT 0,
        tax_amount DECIMAL(10, 2) DEFAULT 0,
        net_amount DECIMAL(10, 2) NOT NULL,
        payment_status ENUM('Paid', 'Partial', 'Unpaid') DEFAULT 'Unpaid',
        remarks TEXT,
        created_by VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
        FOREIGN KEY (outlet_id) REFERENCES outlets(outlet_id),
        FOREIGN KEY (created_by) REFERENCES users(user_id)
    )",
    
    // Purchase Items table
    "CREATE TABLE IF NOT EXISTS purchase_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        purchase_id VARCHAR(50) NOT NULL,
        product_id VARCHAR(50) NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10, 2) NOT NULL,
        total_price DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (purchase_id) REFERENCES purchases(purchase_id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(product_id)
    )",
    
    // Purchase Returns table
    "CREATE TABLE IF NOT EXISTS purchase_returns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        return_id VARCHAR(50) UNIQUE NOT NULL,
        purchase_id VARCHAR(50) NOT NULL,
        supplier_id VARCHAR(50) NOT NULL,
        outlet_id VARCHAR(50) NOT NULL,
        return_date DATE NOT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        remarks TEXT,
        created_by VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (purchase_id) REFERENCES purchases(purchase_id),
        FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
        FOREIGN KEY (outlet_id) REFERENCES outlets(outlet_id),
        FOREIGN KEY (created_by) REFERENCES users(user_id)
    )",
    
    // Purchase Return Items table
    "CREATE TABLE IF NOT EXISTS purchase_return_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        return_id VARCHAR(50) NOT NULL,
        product_id VARCHAR(50) NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10, 2) NOT NULL,
        total_price DECIMAL(10, 2) NOT NULL,
        reason TEXT,
        FOREIGN KEY (return_id) REFERENCES purchase_returns(return_id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(product_id)
    )",
    
    // Payment Vouchers table
    "CREATE TABLE IF NOT EXISTS payment_vouchers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        voucher_id VARCHAR(50) UNIQUE NOT NULL,
        voucher_date DATE NOT NULL,
        outlet_id VARCHAR(50) NOT NULL,
        payee_type ENUM('Supplier', 'Customer', 'Employee', 'Other') NOT NULL,
        payee_id VARCHAR(50),
        payee_name VARCHAR(100),
        account_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        payment_method ENUM('Cash', 'Bank', 'Online') NOT NULL,
        reference_no VARCHAR(100),
        remarks TEXT,
        approval_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
        approved_by VARCHAR(50),
        approved_at TIMESTAMP NULL,
        created_by VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (outlet_id) REFERENCES outlets(outlet_id),
        FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id),
        FOREIGN KEY (created_by) REFERENCES users(user_id),
        FOREIGN KEY (approved_by) REFERENCES users(user_id)
    )",
    
    // Inventory Transactions table
    "CREATE TABLE IF NOT EXISTS inventory_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_id VARCHAR(50) UNIQUE NOT NULL,
        product_id VARCHAR(50) NOT NULL,
        outlet_id VARCHAR(50) NOT NULL,
        transaction_type ENUM('Purchase', 'Sale', 'PurchaseReturn', 'SaleReturn', 'Adjustment', 'Transfer') NOT NULL,
        reference_id VARCHAR(50), -- ID of related sale, purchase, etc.
        quantity INT NOT NULL,
        unit_cost DECIMAL(10, 2) NOT NULL,
        transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        remarks TEXT,
        created_by VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(product_id),
        FOREIGN KEY (outlet_id) REFERENCES outlets(outlet_id),
        FOREIGN KEY (created_by) REFERENCES users(user_id)
    )",
    
    // Lease Termination table
    "CREATE TABLE IF NOT EXISTS lease_terminations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        termination_id VARCHAR(50) UNIQUE NOT NULL,
        lease_id VARCHAR(50) NOT NULL,
        outlet_id VARCHAR(50) NOT NULL,
        termination_date DATE NOT NULL,
        reason TEXT NOT NULL,
        termination_fee DECIMAL(10, 2) DEFAULT 0,
        refund_amount DECIMAL(10, 2) DEFAULT 0,
        status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
        approved_by VARCHAR(50),
        approved_at TIMESTAMP NULL,
        remarks TEXT,
        created_by VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (lease_id) REFERENCES leases(lease_id),
        FOREIGN KEY (outlet_id) REFERENCES outlets(outlet_id),
        FOREIGN KEY (created_by) REFERENCES users(user_id),
        FOREIGN KEY (approved_by) REFERENCES users(user_id)
    )"
];

echo "<h2>Creating Missing Tables</h2>\n";

foreach ($tables_to_create as $index => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green;'>Table " . ($index + 1) . " created successfully</p>\n";
    } else {
        echo "<p style='color:red;'>Error creating table " . ($index + 1) . ": " . $conn->error . "</p>\n";
    }
}

// Add missing columns to recovery_collections table
$alter_table_queries = [
    "ALTER TABLE recovery_collections ADD COLUMN IF NOT EXISTS lease_id VARCHAR(50)",
    "ALTER TABLE recovery_collections ADD COLUMN IF NOT EXISTS expense_category VARCHAR(100) DEFAULT 'General Expenses'"
];

echo "<h2>Updating recovery_collections Table</h2>\n";

foreach ($alter_table_queries as $index => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green;'>Column " . ($index + 1) . " added successfully</p>\n";
    } else {
        echo "<p style='color:red;'>Error adding column " . ($index + 1) . ": " . $conn->error . "</p>\n";
    }
}

$conn->close();

echo "<h2>Process Complete</h2>\n";
echo "<p>All missing tables and columns have been created/updated.</p>\n";
?>