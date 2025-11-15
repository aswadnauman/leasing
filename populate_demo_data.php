<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Function to generate random data
function generateRandomData($conn) {
    // Clear existing data (optional, for fresh demo)
    // $tables = ['installment_schedule', 'lease_documents', 'lease_renewals', 'lease_payments', 'overdue_tracking', 'leases', 'clients', 'recovery_persons', 'products', 'master_profession', 'master_area', 'master_road', 'master_city'];
    // foreach ($tables as $table) {
    //     $conn->query("DELETE FROM $table");
    // }
    
    // Insert master data
    $professions = ['Engineer', 'Doctor', 'Teacher', 'Lawyer', 'Accountant', 'Manager', 'Salesperson', 'Technician', 'Driver', 'Clerk'];
    foreach ($professions as $profession) {
        $stmt = $conn->prepare("INSERT IGNORE INTO master_profession (profession) VALUES (?)");
        $stmt->bind_param("s", $profession);
        $stmt->execute();
        $stmt->close();
    }
    
    $cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'San Jose'];
    foreach ($cities as $city) {
        $stmt = $conn->prepare("INSERT IGNORE INTO master_city (city) VALUES (?)");
        $stmt->bind_param("s", $city);
        $stmt->execute();
        $stmt->close();
    }
    
    $areas = ['Downtown', 'Uptown', 'Midtown', 'Suburb A', 'Suburb B', 'District 1', 'District 2', 'District 3', 'Westside', 'Eastside'];
    foreach ($areas as $area) {
        $stmt = $conn->prepare("INSERT IGNORE INTO master_area (area) VALUES (?)");
        $stmt->bind_param("s", $area);
        $stmt->execute();
        $stmt->close();
    }
    
    // Insert roads for each area
    $result = $conn->query("SELECT id FROM master_area");
    $area_ids = [];
    while ($row = $result->fetch_assoc()) {
        $area_ids[] = $row['id'];
    }
    
    $roads = ['Main Street', 'First Avenue', 'Second Street', 'Park Avenue', 'Elm Street', 'Oak Street', 'Pine Street', 'Maple Avenue', 'Cedar Road', 'Walnut Drive'];
    foreach ($area_ids as $area_id) {
        foreach ($roads as $road) {
            $stmt = $conn->prepare("INSERT IGNORE INTO master_road (road, area_id) VALUES (?, ?)");
            $stmt->bind_param("si", $road, $area_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Get outlet IDs
    $result = $conn->query("SELECT outlet_id FROM outlets LIMIT 1");
    $outlet = $result->fetch_assoc();
    $outlet_id = $outlet['outlet_id'];
    
    // Insert demo recovery persons
    $recovery_persons = [
        ['RP001', 'John Recovery', '12345-6789012-3', '0300-1234567', '123 Recovery St', 1, 1, 'john@recovery.com', $outlet_id, 'Active'],
        ['RP002', 'Jane Collector', '23456-7890123-4', '0300-2345678', '456 Collection Ave', 2, 2, 'jane@recovery.com', $outlet_id, 'Active'],
        ['RP003', 'Mike Followup', '34567-8901234-5', '0300-3456789', '789 Followup Rd', 3, 3, 'mike@recovery.com', $outlet_id, 'Active'],
        ['RP004', 'Sarah Tracker', '45678-9012345-6', '0300-4567890', '321 Tracker Ln', 4, 4, 'sarah@recovery.com', $outlet_id, 'Active'],
        ['RP005', 'David Pursuer', '56789-0123456-7', '0300-5678901', '654 Pursuer Blvd', 5, 5, 'david@recovery.com', $outlet_id, 'Active']
    ];
    
    foreach ($recovery_persons as $rp) {
        $stmt = $conn->prepare("INSERT IGNORE INTO recovery_persons (recovery_person_id, full_name, cnic, mobile_number, address, city_id, area_id, email, outlet_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssiissis", $rp[0], $rp[1], $rp[2], $rp[3], $rp[4], $rp[5], $rp[6], $rp[7], $rp[8], $rp[9]);
        $stmt->execute();
        $stmt->close();
    }
    
    // Insert demo clients
    $clients = [
        ['CLI001', 'John Doe', 'Robert Doe', '12345-6789012-3', '0300-1234567', '0300-7654321', 'House 123, Street 45, Area A', 'Village ABC, District XYZ', 'Area A', 'Street 45', 'New York', 'Engineer', 'REF001', 'Active', 'Regular customer', $outlet_id],
        ['CLI002', 'Jane Smith', 'Michael Smith', '23456-7890123-4', '0311-2345678', null, 'Apartment 456, Building B', 'House 789, Street C', 'Area B', 'Building B', 'Los Angeles', 'Teacher', 'REF002', 'Active', 'Good payment history', $outlet_id],
        ['CLI003', 'Robert Johnson', 'William Johnson', '34567-8901234-5', '0322-3456789', '0322-9876543', 'House 789, Road 78', 'Same as current', 'Area C', 'Road 78', 'Chicago', 'Doctor', 'REF003', 'Active', 'High value client', $outlet_id],
        ['CLI004', 'Emily Brown', 'James Brown', '45678-9012345-6', '0333-4567890', null, 'Flat 321, Complex D', 'Same as current', 'Downtown', 'Main Street', 'Houston', 'Lawyer', 'REF004', 'Active', 'Corporate client', $outlet_id],
        ['CLI005', 'Michael Davis', 'David Davis', '56789-0123456-7', '0344-5678901', '0344-1234567', 'Villa 654, Sector E', 'Same as current', 'Uptown', 'First Avenue', 'Phoenix', 'Accountant', 'REF005', 'Active', 'Long term client', $outlet_id]
    ];
    
    foreach ($clients as $client) {
        $stmt = $conn->prepare("INSERT IGNORE INTO clients (client_id, full_name, father_husband_name, cnic, mobile_primary, mobile_secondary, address_current, address_permanent, area, road, city, profession, manual_reference_no, status, remarks, outlet_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssssssss", $client[0], $client[1], $client[2], $client[3], $client[4], $client[5], $client[6], $client[7], $client[8], $client[9], $client[10], $client[11], $client[12], $client[13], $client[14], $client[15]);
        $stmt->execute();
        $stmt->close();
    }
    
    // Insert demo products (electronics and appliances)
    $products = [
        // Electronics
        ['PROD001', 'Samsung TV 55"', '55 inch 4K Smart TV', 'Electronics', 'Samsung', 'UN55TU7000', 'SAMSUNG001', 499.99, 15.50, $outlet_id, 'Available', 'New'],
        ['PROD002', 'iPhone 13 Pro', 'Latest Apple smartphone', 'Electronics', 'Apple', 'iPhone13Pro', 'APPLE001', 999.99, 25.00, $outlet_id, 'Available', 'New'],
        ['PROD003', 'Dell Laptop', 'Inspiron 15 3000 series', 'Electronics', 'Dell', 'Inspiron15', 'DELL001', 599.99, 18.50, $outlet_id, 'Available', 'New'],
        ['PROD004', 'Sony Headphones', 'Wireless noise-cancelling headphones', 'Electronics', 'Sony', 'WH-1000XM4', 'SONY001', 299.99, 12.00, $outlet_id, 'Available', 'New'],
        ['PROD005', 'iPad Air', '10.9-inch tablet with A14 Bionic chip', 'Electronics', 'Apple', 'iPadAir4', 'APPLE002', 599.99, 18.00, $outlet_id, 'Available', 'New'],
        ['PROD006', 'Samsung Galaxy Tab', '12.4-inch tablet with S Pen', 'Electronics', 'Samsung', 'SM-T970', 'SAMSUNG002', 699.99, 20.00, $outlet_id, 'Available', 'New'],
        ['PROD007', 'MacBook Air', 'M1 chip, 13-inch laptop', 'Electronics', 'Apple', 'MacBookAirM1', 'APPLE003', 999.99, 25.00, $outlet_id, 'Available', 'New'],
        ['PROD008', 'Google Pixel 6', '5G smartphone with advanced camera', 'Electronics', 'Google', 'Pixel6', 'GOOGLE001', 699.99, 20.00, $outlet_id, 'Available', 'New'],
        ['PROD009', 'PlayStation 5', 'Next-generation gaming console', 'Electronics', 'Sony', 'PS5', 'SONY002', 499.99, 15.00, $outlet_id, 'Available', 'New'],
        ['PROD010', 'Xbox Series X', 'Next-generation gaming console', 'Electronics', 'Microsoft', 'XboxSeriesX', 'MSFT001', 499.99, 15.00, $outlet_id, 'Available', 'New'],
        
        // Appliances
        ['PROD011', 'Whirlpool Refrigerator', '25 cu ft French Door Refrigerator', 'Appliances', 'Whirlpool', 'WRX735SDHZ', 'WHIRLPOOL001', 1299.99, 35.75, $outlet_id, 'Available', 'New'],
        ['PROD012', 'Bosch Washing Machine', '8 kg Front Load Washing Machine', 'Appliances', 'Bosch', 'WAT28480', 'BOSCH001', 799.99, 22.25, $outlet_id, 'Available', 'New'],
        ['PROD013', 'LG Microwave Oven', '1.5 cu ft Countertop Microwave', 'Appliances', 'LG', 'LMC2075ST', 'LG001', 149.99, 8.00, $outlet_id, 'Available', 'New'],
        ['PROD014', 'KitchenAid Stand Mixer', '5-quart tilt-head stand mixer', 'Appliances', 'KitchenAid', 'KSM150PSER', 'KITCHENAID001', 399.99, 12.50, $outlet_id, 'Available', 'New'],
        ['PROD015', 'Ninja Blender', '1500-watt professional blender', 'Appliances', 'Ninja', 'BL660', 'NINJA001', 129.99, 7.50, $outlet_id, 'Available', 'New'],
        ['PROD016', 'Dyson Vacuum Cleaner', 'Cordless stick vacuum cleaner', 'Appliances', 'Dyson', 'V11', 'DYSON001', 599.99, 18.00, $outlet_id, 'Available', 'New'],
        ['PROD017', 'Cuisinart Coffee Maker', '12-cup programmable coffee maker', 'Appliances', 'Cuisinart', 'DCC-3200P1', 'CUISINART001', 99.99, 6.00, $outlet_id, 'Available', 'New'],
        ['PROD018', 'Instant Pot', '7-in-1 electric pressure cooker', 'Appliances', 'Instant Pot', 'IP-DUO60', 'INSTANTPOT001', 89.99, 5.50, $outlet_id, 'Available', 'New'],
        ['PROD019', 'Samsung Dryer', 'Electric dryer with steam technology', 'Appliances', 'Samsung', 'DV45T6000EV', 'SAMSUNG003', 699.99, 20.00, $outlet_id, 'Available', 'New'],
        ['PROD020', 'GE Dishwasher', 'Built-in dishwasher with 5 wash cycles', 'Appliances', 'GE', 'GDT650PSNRS', 'GE001', 499.99, 15.00, $outlet_id, 'Available', 'New']
    ];
    
    foreach ($products as $product) {
        $stmt = $conn->prepare("INSERT IGNORE INTO products (product_id, product_name, description, category, brand, model, serial_number, purchase_price, leasing_rate, outlet_id, status, `condition`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssdssss", $product[0], $product[1], $product[2], $product[3], $product[4], $product[5], $product[6], $product[7], $product[8], $product[9], $product[10], $product[11]);
        $stmt->execute();
        $stmt->close();
    }
    
    echo "Demo data populated successfully!";
}

// Call the function to generate random data
generateRandomData($conn);

$conn->close();
?>