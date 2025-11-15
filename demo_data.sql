USE lease_management;

INSERT INTO outlets (outlet_id, outlet_name, city, address, phone, email, outlet_type, assigned_branch_manager, linked_cash_account, linked_bank_account) VALUES
('OUT001', 'Main Branch', 'New York', '123 Main St, NY 10001', '212-555-1234', 'main@leasecompany.com', 'Main', 'USR001', 'CASH001', 'BANK001'),
('OUT002', 'Downtown Branch', 'New York', '456 Downtown Ave, NY 10002', '212-555-5678', 'downtown@leasecompany.com', 'Sub', 'USR002', 'CASH002', 'BANK002');

INSERT INTO users (user_id, username, email, password, role, outlet_id, assigned_areas, is_active) VALUES
('USR001', 'admin', 'admin@leasecompany.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'OUT001', NULL, TRUE),
('USR002', 'manager1', 'manager1@leasecompany.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'BranchManager', 'OUT001', NULL, TRUE),
('USR003', 'recovery1', 'recovery1@leasecompany.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'RecoveryOfficer', 'OUT001', '["Area A", "Area B"]', TRUE),
('USR004', 'sales1', 'sales1@leasecompany.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sales', 'OUT001', NULL, TRUE);

INSERT INTO master_city (city) VALUES ('New York'), ('Los Angeles'), ('Chicago');
INSERT INTO master_area (area) VALUES ('Area A'), ('Area B'), ('Area C');
INSERT INTO master_road (road, area_id) VALUES ('Street 45', 1), ('Building B', 2);
INSERT INTO master_profession (profession) VALUES ('Engineer'), ('Teacher'), ('Doctor'), ('Lawyer');

INSERT INTO products (product_id, product_name, description, category, brand, model, serial_number, purchase_price, leasing_rate, outlet_id, status, `condition`) VALUES
('PROD001', 'Samsung TV 55"', '55 inch 4K Smart TV', 'Electronics', 'Samsung', 'UN55TU7000', 'SAMSUNG001', 499.99, 15.50, 'OUT001', 'Available', 'New'),
('PROD002', 'Whirlpool Refrigerator', '25 cu ft French Door Refrigerator', 'Appliances', 'Whirlpool', 'WRX735SDHZ', 'WHIRLPOOL001', 1299.99, 35.75, 'OUT001', 'Available', 'New');

INSERT INTO clients (client_id, full_name, father_husband_name, cnic, mobile_primary, mobile_secondary, address_current, address_permanent, area, road, city, profession, manual_reference_no, status, remarks, outlet_id) VALUES
('CLI001', 'John Doe', 'Robert Doe', '12345-6789012-3', '0300-1234567', '0300-7654321', 'House 123, Street 45, Area A', 'Village ABC, District XYZ', 'Area A', 'Street 45', 'New York', 'Engineer', 'REF001', 'Active', 'Regular customer', 'OUT001'),
('CLI002', 'Jane Smith', 'Michael Smith', '23456-7890123-4', '0311-2345678', NULL, 'Apartment 456, Building B', 'House 789, Street C', 'Area B', 'Building B', 'New York', 'Teacher', 'REF002', 'Active', 'Good payment history', 'OUT001');

INSERT INTO recovery_persons (recovery_person_id, full_name, cnic, mobile_number, address, city_id, area_id, email, outlet_id, status) VALUES
('RP001', 'Recovery Person 1', '11111-2222222-3', '0300-1111111', '123 Recovery St', 1, 1, 'recovery1@example.com', 'OUT001', 'Active'),
('RP002', 'Recovery Person 2', '22222-3333333-4', '0300-2222222', '456 Recovery Ave', 1, 2, 'recovery2@example.com', 'OUT001', 'Active');

INSERT INTO system_config (config_key, config_value, description) VALUES
('company_name', 'Lease Management Company', 'Company name for reports and documents'),
('currency_symbol', '₹', 'Currency symbol to display'),
('late_fee_percentage', '2', 'Late fee percentage per month'),
('grace_period_days', '3', 'Grace period in days before marking as overdue'),
('default_down_payment_percentage', '10', 'Default down payment percentage'),
('default_security_deposit_months', '2', 'Default security deposit in months of installment'),
('max_overdue_days', '90', 'Maximum days a lease can be overdue before termination'),
('notification_email', 'admin@leasemanagement.com', 'Email for system notifications'),
('backup_frequency', 'daily', 'Database backup frequency'),
('data_retention_months', '60', 'Data retention period in months');