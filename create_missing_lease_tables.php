<?php
require_once 'config/db.php';

$conn = getDBConnection();

// Create the missing tables for lease management
$tables_to_create = [
    // User Roles table
    "CREATE TABLE IF NOT EXISTS user_roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role_name VARCHAR(50) UNIQUE NOT NULL,
        description TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    // Role Permissions table
    "CREATE TABLE IF NOT EXISTS role_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role_id INT NOT NULL,
        permission VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (role_id) REFERENCES user_roles(id) ON DELETE CASCADE,
        UNIQUE KEY unique_role_permission (role_id, permission)
    )",
    
    // Installment Schedule table
    "CREATE TABLE IF NOT EXISTS installment_schedule (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lease_id VARCHAR(50) NOT NULL,
        installment_number INT NOT NULL,
        due_date DATE NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        paid_amount DECIMAL(10, 2) DEFAULT 0,
        status ENUM('Pending', 'Paid', 'Overdue', 'PartiallyPaid') DEFAULT 'Pending',
        payment_date DATE NULL,
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (lease_id) REFERENCES leases(lease_id) ON DELETE CASCADE
    )",
    
    // Lease Documents table
    "CREATE TABLE IF NOT EXISTS lease_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lease_id VARCHAR(50) NOT NULL,
        document_type ENUM('Agreement', 'CNIC', 'GuarantorCNIC', 'ProductPhoto', 'Other') NOT NULL,
        document_path VARCHAR(255) NOT NULL,
        uploaded_by VARCHAR(50) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (lease_id) REFERENCES leases(lease_id) ON DELETE CASCADE,
        FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
    )",
    
    // Lease Renewals table
    "CREATE TABLE IF NOT EXISTS lease_renewals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        renewal_id VARCHAR(50) UNIQUE NOT NULL,
        original_lease_id VARCHAR(50) NOT NULL,
        new_lease_id VARCHAR(50) NOT NULL,
        renewal_date DATE NOT NULL,
        renewal_amount DECIMAL(10, 2) NOT NULL,
        remarks TEXT,
        created_by VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (original_lease_id) REFERENCES leases(lease_id),
        FOREIGN KEY (new_lease_id) REFERENCES leases(lease_id),
        FOREIGN KEY (created_by) REFERENCES users(user_id)
    )",
    
    // Lease Payments table (more detailed than recovery_collections)
    "CREATE TABLE IF NOT EXISTS lease_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        payment_id VARCHAR(50) UNIQUE NOT NULL,
        lease_id VARCHAR(50) NOT NULL,
        installment_id INT,
        payment_date DATE NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        payment_method ENUM('Cash', 'BankTransfer', 'Online', 'Cheque') NOT NULL,
        reference_no VARCHAR(100),
        bank_name VARCHAR(100),
        remarks TEXT,
        status ENUM('Pending', 'Completed', 'Cancelled') DEFAULT 'Completed',
        created_by VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (lease_id) REFERENCES leases(lease_id),
        FOREIGN KEY (installment_id) REFERENCES installment_schedule(id),
        FOREIGN KEY (created_by) REFERENCES users(user_id)
    )",
    
    // Overdue Tracking table
    "CREATE TABLE IF NOT EXISTS overdue_tracking (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lease_id VARCHAR(50) NOT NULL,
        installment_id INT,
        overdue_days INT NOT NULL,
        overdue_amount DECIMAL(10, 2) NOT NULL,
        late_fee DECIMAL(10, 2) DEFAULT 0,
        last_reminder_date DATE,
        reminder_count INT DEFAULT 0,
        status ENUM('Open', 'Closed', 'Waived') DEFAULT 'Open',
        closed_date DATE NULL,
        closed_by VARCHAR(50) NULL,
        remarks TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (lease_id) REFERENCES leases(lease_id),
        FOREIGN KEY (installment_id) REFERENCES installment_schedule(id),
        FOREIGN KEY (closed_by) REFERENCES users(user_id)
    )",
    
    // Audit Trail table
    "CREATE TABLE IF NOT EXISTS audit_trail (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(50) NOT NULL,
        action_type ENUM('CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'OTHER') NOT NULL,
        table_name VARCHAR(50),
        record_id VARCHAR(50),
        old_values TEXT,
        new_values TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )",
    
    // System Configuration table
    "CREATE TABLE IF NOT EXISTS system_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        config_key VARCHAR(100) UNIQUE NOT NULL,
        config_value TEXT,
        description VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    // Notifications table
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('Info', 'Warning', 'Error', 'Success') DEFAULT 'Info',
        is_read BOOLEAN DEFAULT FALSE,
        related_module VARCHAR(50),
        related_id VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )"
];

echo "<h2>Creating Missing Lease Management Tables</h2>\n";

$success_count = 0;
$error_count = 0;

foreach ($tables_to_create as $index => $sql) {
    if ($conn->query($sql) === TRUE) {
        $success_count++;
        echo "<p style='color:green;'>Table " . ($index + 1) . " created successfully</p>\n";
    } else {
        $error_count++;
        echo "<p style='color:red;'>Error creating table " . ($index + 1) . ": " . $conn->error . "</p>\n";
    }
}

// Add missing columns to existing tables if needed
$alter_table_queries = [
    // Add columns to leases table
    "ALTER TABLE leases ADD COLUMN IF NOT EXISTS agreement_date DATE",
    
    // Add columns to clients table
    "ALTER TABLE clients ADD COLUMN IF NOT EXISTS date_of_birth DATE",
    "ALTER TABLE clients ADD COLUMN IF NOT EXISTS emergency_contact_name VARCHAR(100)",
    "ALTER TABLE clients ADD COLUMN IF NOT EXISTS emergency_contact_phone VARCHAR(20)",
    "ALTER TABLE clients ADD COLUMN IF NOT EXISTS monthly_income DECIMAL(10, 2)",
    "ALTER TABLE clients ADD COLUMN IF NOT EXISTS employer_name VARCHAR(100)",
    "ALTER TABLE clients ADD COLUMN IF NOT EXISTS employer_address TEXT",
    "ALTER TABLE clients ADD COLUMN IF NOT EXISTS employer_phone VARCHAR(20)",
    
    // Add columns to products table
    "ALTER TABLE products ADD COLUMN IF NOT EXISTS warranty_period INT",
    "ALTER TABLE products ADD COLUMN IF NOT EXISTS warranty_expiry DATE"
];

echo "<h2>Adding Missing Columns to Existing Tables</h2>\n";

foreach ($alter_table_queries as $index => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green;'>Column added successfully: " . $sql . "</p>\n";
    } else {
        echo "<p style='color:orange;'>Warning adding column: " . $conn->error . "</p>\n";
    }
}

// Insert default system configuration values
$config_values = [
    "('company_name', 'Lease Management Company', 'Company name for reports and documents')",
    "('currency_symbol', '₹', 'Currency symbol to display')",
    "('late_fee_percentage', '2', 'Late fee percentage per month')",
    "('grace_period_days', '3', 'Grace period in days before marking as overdue')",
    "('default_down_payment_percentage', '10', 'Default down payment percentage')",
    "('default_security_deposit_months', '2', 'Default security deposit in months of installment')",
    "('max_overdue_days', '90', 'Maximum days a lease can be overdue before termination')",
    "('notification_email', 'admin@leasemanagement.com', 'Email for system notifications')",
    "('backup_frequency', 'daily', 'Database backup frequency')",
    "('data_retention_months', '60', 'Data retention period in months')"
];

echo "<h2>Inserting Default System Configuration Values</h2>\n";

$insert_sql = "INSERT IGNORE INTO system_config (config_key, config_value, description) VALUES " . implode(',', $config_values);
if ($conn->query($insert_sql) === TRUE) {
    echo "<p style='color:green;'>Default system configuration values inserted successfully</p>\n";
} else {
    echo "<p style='color:orange;'>Warning inserting default system configuration values: " . $conn->error . "</p>\n";
}

// Create indexes for performance optimization
$indexes_to_create = [
    "CREATE INDEX IF NOT EXISTS idx_leases_client_id ON leases(client_id)",
    "CREATE INDEX IF NOT EXISTS idx_leases_status ON leases(status)",
    "CREATE INDEX IF NOT EXISTS idx_leases_outlet_id ON leases(outlet_id)",
    "CREATE INDEX IF NOT EXISTS idx_installment_lease_id ON installment_schedule(lease_id)",
    "CREATE INDEX IF NOT EXISTS idx_installment_due_date ON installment_schedule(due_date)",
    "CREATE INDEX IF NOT EXISTS idx_installment_status ON installment_schedule(status)",
    "CREATE INDEX IF NOT EXISTS idx_clients_cnic ON clients(cnic)",
    "CREATE INDEX IF NOT EXISTS idx_clients_outlet_id ON clients(outlet_id)",
    "CREATE INDEX IF NOT EXISTS idx_clients_status ON clients(status)",
    "CREATE INDEX IF NOT EXISTS idx_products_outlet_id ON products(outlet_id)",
    "CREATE INDEX IF NOT EXISTS idx_products_status ON products(status)",
    "CREATE INDEX IF NOT EXISTS idx_recovery_collections_outlet_id ON recovery_collections(outlet_id)",
    "CREATE INDEX IF NOT EXISTS idx_recovery_collections_date ON recovery_collections(collection_date)",
    "CREATE INDEX IF NOT EXISTS idx_recovery_collections_status ON recovery_collections(approval_status)"
];

echo "<h2>Creating Indexes for Performance Optimization</h2>\n";

foreach ($indexes_to_create as $index => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green;'>Index " . ($index + 1) . " created successfully</p>\n";
    } else {
        echo "<p style='color:orange;'>Warning creating index " . ($index + 1) . ": " . $conn->error . "</p>\n";
    }
}

$conn->close();

echo "<h2>Process Complete</h2>\n";
echo "<p>Successfully created: " . $success_count . " tables</p>\n";
echo "<p>Warnings: " . $error_count . "</p>\n";
echo "<p>Missing lease management tables have been created.</p>\n";
?>