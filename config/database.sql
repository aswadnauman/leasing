CREATE DATABASE IF NOT EXISTS lease_management;
USE lease_management;

CREATE TABLE outlets (
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
);

CREATE TABLE users (
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
);

CREATE TABLE master_profession (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profession VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE master_area (
    id INT AUTO_INCREMENT PRIMARY KEY,
    area VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE master_road (
    id INT AUTO_INCREMENT PRIMARY KEY,
    road VARCHAR(100) UNIQUE NOT NULL,
    area_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (area_id) REFERENCES master_area(id)
);

CREATE TABLE master_city (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE recovery_persons (
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
);

CREATE TABLE products (
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
);

CREATE TABLE clients (
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
);

CREATE TABLE leases (
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
    agreement_date DATE,
    down_payment DECIMAL(10, 2) DEFAULT 0,
    security_deposit DECIMAL(10, 2) DEFAULT 0,
    lease_agreement_path VARCHAR(255),
    has_multiple_products BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(client_id),
    FOREIGN KEY (guarantor_id) REFERENCES clients(client_id),
    FOREIGN KEY (recovery_person_id) REFERENCES recovery_persons(recovery_person_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (outlet_id) REFERENCES outlets(outlet_id)
);

CREATE TABLE lease_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lease_id VARCHAR(50) NOT NULL,
    product_id VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (lease_id) REFERENCES leases(lease_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

CREATE TABLE installment_schedule (
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
);

CREATE TABLE lease_payments (
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
);

CREATE TABLE recovery_collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recovery_person_id VARCHAR(50) NOT NULL,
    client_id VARCHAR(50),
    lease_id VARCHAR(50),
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
    FOREIGN KEY (client_id) REFERENCES clients(client_id),
    FOREIGN KEY (lease_id) REFERENCES leases(lease_id),
    FOREIGN KEY (outlet_id) REFERENCES outlets(outlet_id)
);

CREATE TABLE audit_trail (
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
);

CREATE TABLE system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE chart_of_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(20) UNIQUE NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    account_type ENUM('Asset', 'Liability', 'Equity', 'Income', 'Expense') NOT NULL,
    parent_account_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_account_id) REFERENCES chart_of_accounts(id)
);

CREATE TABLE suppliers (
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
);

CREATE TABLE customers (
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
);

CREATE TABLE sales (
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
);

CREATE TABLE sales_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id VARCHAR(50) NOT NULL,
    product_id VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(sale_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

CREATE TABLE sales_returns (
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
);

CREATE TABLE sales_return_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    return_id VARCHAR(50) NOT NULL,
    product_id VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    reason TEXT,
    FOREIGN KEY (return_id) REFERENCES sales_returns(return_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

CREATE TABLE purchases (
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
);

CREATE TABLE purchase_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id VARCHAR(50) NOT NULL,
    product_id VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES purchases(purchase_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

CREATE TABLE purchase_returns (
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
);

CREATE TABLE purchase_return_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    return_id VARCHAR(50) NOT NULL,
    product_id VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    reason TEXT,
    FOREIGN KEY (return_id) REFERENCES purchase_returns(return_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

CREATE TABLE payment_vouchers (
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
);

CREATE TABLE receipt_vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voucher_id VARCHAR(50) UNIQUE NOT NULL,
    voucher_date DATE NOT NULL,
    outlet_id VARCHAR(50) NOT NULL,
    payer_type ENUM('Supplier', 'Customer', 'Employee', 'Other') NOT NULL,
    payer_id VARCHAR(50),
    payer_name VARCHAR(100),
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
);

CREATE TABLE journal_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_id VARCHAR(50) UNIQUE NOT NULL,
    entry_date DATE NOT NULL,
    outlet_id VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    total_debit DECIMAL(10, 2) NOT NULL,
    total_credit DECIMAL(10, 2) NOT NULL,
    approval_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    approved_by VARCHAR(50),
    approved_at TIMESTAMP NULL,
    created_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (outlet_id) REFERENCES outlets(outlet_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id)
);

CREATE TABLE journal_entry_lines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_id VARCHAR(50) NOT NULL,
    account_id INT NOT NULL,
    debit_amount DECIMAL(10, 2) DEFAULT 0,
    credit_amount DECIMAL(10, 2) DEFAULT 0,
    description TEXT,
    FOREIGN KEY (entry_id) REFERENCES journal_entries(entry_id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id)
);

CREATE TABLE inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(50) UNIQUE NOT NULL,
    product_id VARCHAR(50) NOT NULL,
    outlet_id VARCHAR(50) NOT NULL,
    transaction_type ENUM('Purchase', 'Sale', 'PurchaseReturn', 'SaleReturn', 'Adjustment', 'Transfer') NOT NULL,
    reference_id VARCHAR(50),
    quantity INT NOT NULL,
    unit_cost DECIMAL(10, 2) NOT NULL,
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    remarks TEXT,
    created_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (outlet_id) REFERENCES outlets(outlet_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

CREATE TABLE lease_terminations (
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
);

CREATE INDEX idx_leases_client_id ON leases(client_id);
CREATE INDEX idx_leases_status ON leases(status);
CREATE INDEX idx_leases_outlet_id ON leases(outlet_id);
CREATE INDEX idx_lease_products_lease_id ON lease_products(lease_id);
CREATE INDEX idx_installment_lease_id ON installment_schedule(lease_id);
CREATE INDEX idx_installment_due_date ON installment_schedule(due_date);
CREATE INDEX idx_installment_status ON installment_schedule(status);
CREATE INDEX idx_clients_cnic ON clients(cnic);
CREATE INDEX idx_clients_outlet_id ON clients(outlet_id);
CREATE INDEX idx_clients_status ON clients(status);
CREATE INDEX idx_products_outlet_id ON products(outlet_id);
CREATE INDEX idx_products_status ON products(status);
CREATE INDEX idx_recovery_collections_outlet_id ON recovery_collections(outlet_id);
CREATE INDEX idx_recovery_collections_date ON recovery_collections(collection_date);
CREATE INDEX idx_recovery_collections_status ON recovery_collections(approval_status);
CREATE INDEX idx_recovery_collections_client ON recovery_collections(client_id);
CREATE INDEX idx_recovery_collections_lease ON recovery_collections(lease_id);
CREATE INDEX idx_suppliers_outlet_id ON suppliers(outlet_id);
CREATE INDEX idx_customers_outlet_id ON customers(outlet_id);
CREATE INDEX idx_sales_outlet_id ON sales(outlet_id);
CREATE INDEX idx_sales_customer_id ON sales(customer_id);
CREATE INDEX idx_sales_date ON sales(sale_date);
CREATE INDEX idx_sales_items_sale ON sales_items(sale_id);
CREATE INDEX idx_purchases_outlet_id ON purchases(outlet_id);
CREATE INDEX idx_purchases_supplier_id ON purchases(supplier_id);
CREATE INDEX idx_purchases_date ON purchases(purchase_date);
CREATE INDEX idx_purchase_items_purchase ON purchase_items(purchase_id);
CREATE INDEX idx_payment_vouchers_outlet_id ON payment_vouchers(outlet_id);
CREATE INDEX idx_receipt_vouchers_outlet_id ON receipt_vouchers(outlet_id);
CREATE INDEX idx_vouchers_date ON payment_vouchers(voucher_date);
CREATE INDEX idx_receipts_date ON receipt_vouchers(voucher_date);
CREATE INDEX idx_journal_entries_outlet_id ON journal_entries(outlet_id);
CREATE INDEX idx_journal_entries_date ON journal_entries(entry_date);
CREATE INDEX idx_journal_entries_status ON journal_entries(approval_status);
CREATE INDEX idx_inventory_tx_outlet_id ON inventory_transactions(outlet_id);
CREATE INDEX idx_inventory_tx_product_id ON inventory_transactions(product_id);
CREATE INDEX idx_inventory_tx_date ON inventory_transactions(transaction_date);
CREATE INDEX idx_inventory_tx_type ON inventory_transactions(transaction_type);
CREATE INDEX idx_lease_terminations_outlet_id ON lease_terminations(outlet_id);
CREATE INDEX idx_lease_terminations_status ON lease_terminations(status);
CREATE INDEX idx_lease_terminations_date ON lease_terminations(termination_date);