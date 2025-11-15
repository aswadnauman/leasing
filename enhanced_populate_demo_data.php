<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Function to generate comprehensive demo data
function generateComprehensiveDemoData($conn) {
    echo "<h2>Generating Comprehensive Demo Data</h2>\n";
    
    // Get outlet ID
    $result = $conn->query("SELECT outlet_id FROM outlets LIMIT 1");
    $outlet = $result->fetch_assoc();
    $outlet_id = $outlet['outlet_id'];
    
    // Insert additional master data
    $professions = ['Engineer', 'Doctor', 'Teacher', 'Lawyer', 'Accountant', 'Manager', 'Salesperson', 'Technician', 'Driver', 'Clerk', 'Designer', 'Consultant', 'Analyst', 'Developer', 'Nurse'];
    foreach ($professions as $profession) {
        $stmt = $conn->prepare("INSERT IGNORE INTO master_profession (profession) VALUES (?)");
        $stmt->bind_param("s", $profession);
        $stmt->execute();
        $stmt->close();
    }
    
    $cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'San Jose', 'Austin', 'Jacksonville', 'Fort Worth', 'Columbus', 'Charlotte'];
    foreach ($cities as $city) {
        $stmt = $conn->prepare("INSERT IGNORE INTO master_city (city) VALUES (?)");
        $stmt->bind_param("s", $city);
        $stmt->execute();
        $stmt->close();
    }
    
    $areas = ['Downtown', 'Uptown', 'Midtown', 'Suburb A', 'Suburb B', 'District 1', 'District 2', 'District 3', 'Westside', 'Eastside', 'Northside', 'Southside', 'Central', 'Old Town', 'New District'];
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
    
    $roads = ['Main Street', 'First Avenue', 'Second Street', 'Park Avenue', 'Elm Street', 'Oak Street', 'Pine Street', 'Maple Avenue', 'Cedar Road', 'Walnut Drive', 'Broadway', 'Market Street', 'King Street', 'Queen Street', 'Princess Street'];
    foreach ($area_ids as $area_id) {
        foreach ($roads as $road) {
            $stmt = $conn->prepare("INSERT IGNORE INTO master_road (road, area_id) VALUES (?, ?)");
            $stmt->bind_param("si", $road, $area_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Insert additional recovery persons
    $recovery_persons = [
        ['RP001', 'John Recovery', '12345-6789012-3', '0300-1234567', '123 Recovery St', 1, 1, 'john@recovery.com', $outlet_id, 'Active'],
        ['RP002', 'Jane Collector', '23456-7890123-4', '0300-2345678', '456 Collection Ave', 2, 2, 'jane@recovery.com', $outlet_id, 'Active'],
        ['RP003', 'Mike Followup', '34567-8901234-5', '0300-3456789', '789 Followup Rd', 3, 3, 'mike@recovery.com', $outlet_id, 'Active'],
        ['RP004', 'Sarah Tracker', '45678-9012345-6', '0300-4567890', '321 Tracker Ln', 4, 4, 'sarah@recovery.com', $outlet_id, 'Active'],
        ['RP005', 'David Pursuer', '56789-0123456-7', '0300-5678901', '654 Pursuer Blvd', 5, 5, 'david@recovery.com', $outlet_id, 'Active'],
        ['RP006', 'Lisa Locator', '67890-1234567-8', '0300-6789012', '987 Locator Way', 6, 6, 'lisa@recovery.com', $outlet_id, 'Active'],
        ['RP007', 'Tom Tracker', '78901-2345678-9', '0300-7890123', '147 Tracker Pl', 7, 7, 'tom@recovery.com', $outlet_id, 'Active'],
        ['RP008', 'Emma Enforcer', '89012-3456789-0', '0300-8901234', '258 Enforcer Ave', 8, 8, 'emma@recovery.com', $outlet_id, 'Active']
    ];
    
    foreach ($recovery_persons as $rp) {
        $stmt = $conn->prepare("INSERT IGNORE INTO recovery_persons (recovery_person_id, full_name, cnic, mobile_number, address, city_id, area_id, email, outlet_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssiissis", $rp[0], $rp[1], $rp[2], $rp[3], $rp[4], $rp[5], $rp[6], $rp[7], $rp[8], $rp[9]);
        $stmt->execute();
        $stmt->close();
    }
    
    // Insert additional clients (100+ as requested)
    $clients = [];
    $first_names = ['John', 'Jane', 'Robert', 'Emily', 'Michael', 'Sarah', 'David', 'Lisa', 'James', 'Mary', 'William', 'Patricia', 'Thomas', 'Linda', 'Charles', 'Barbara', 'Christopher', 'Elizabeth', 'Daniel', 'Jennifer'];
    $last_names = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin'];
    $professions_list = ['Engineer', 'Doctor', 'Teacher', 'Lawyer', 'Accountant', 'Manager', 'Salesperson', 'Technician', 'Driver', 'Clerk', 'Designer', 'Consultant', 'Analyst', 'Developer', 'Nurse'];
    $areas_list = ['Downtown', 'Uptown', 'Midtown', 'Suburb A', 'Suburb B', 'District 1', 'District 2', 'District 3', 'Westside', 'Eastside', 'Northside', 'Southside', 'Central', 'Old Town', 'New District'];
    $roads_list = ['Main Street', 'First Avenue', 'Second Street', 'Park Avenue', 'Elm Street', 'Oak Street', 'Pine Street', 'Maple Avenue', 'Cedar Road', 'Walnut Drive', 'Broadway', 'Market Street', 'King Street', 'Queen Street', 'Princess Street'];
    $cities_list = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'San Jose', 'Austin', 'Jacksonville', 'Fort Worth', 'Columbus', 'Charlotte'];
    
    for ($i = 1; $i <= 100; $i++) {
        $client_id = 'CLI' . str_pad($i, 3, '0', STR_PAD_LEFT);
        $first_name = $first_names[array_rand($first_names)];
        $last_name = $last_names[array_rand($last_names)];
        $full_name = $first_name . ' ' . $last_name;
        $father_name = $first_names[array_rand($first_names)] . ' ' . $last_name;
        $cnic = rand(10000, 99999) . '-' . rand(1000000, 9999999) . '-' . rand(1, 9);
        $mobile_primary = '03' . rand(0, 9) . rand(0, 9) . '-' . rand(1000000, 9999999);
        $mobile_secondary = (rand(1, 3) == 1) ? '03' . rand(0, 9) . rand(0, 9) . '-' . rand(1000000, 9999999) : null;
        $address_current = 'House ' . rand(1, 999) . ', ' . $roads_list[array_rand($roads_list)] . ', ' . $areas_list[array_rand($areas_list)];
        $address_permanent = (rand(1, 2) == 1) ? $address_current : 'Village ' . $last_name . ', District ' . $cities_list[array_rand($cities_list)];
        $area = $areas_list[array_rand($areas_list)];
        $road = $roads_list[array_rand($roads_list)];
        $city = $cities_list[array_rand($cities_list)];
        $profession = $professions_list[array_rand($professions_list)];
        $reference_no = 'REF' . str_pad($i, 3, '0', STR_PAD_LEFT);
        $status = (rand(1, 20) == 1) ? 'Blocked' : 'Active'; // 5% blocked clients
        $remarks = (rand(1, 5) == 1) ? 'Good payment history' : ((rand(1, 10) == 1) ? 'Late payments' : 'Regular customer');
        
        $clients[] = [$client_id, $full_name, $father_name, $cnic, $mobile_primary, $mobile_secondary, $address_current, $address_permanent, $area, $road, $city, $profession, $reference_no, $status, $remarks, $outlet_id];
    }
    
    foreach ($clients as $client) {
        $stmt = $conn->prepare("INSERT IGNORE INTO clients (client_id, full_name, father_husband_name, cnic, mobile_primary, mobile_secondary, address_current, address_permanent, area, road, city, profession, manual_reference_no, status, remarks, outlet_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssssssss", $client[0], $client[1], $client[2], $client[3], $client[4], $client[5], $client[6], $client[7], $client[8], $client[9], $client[10], $client[11], $client[12], $client[13], $client[14], $client[15]);
        $stmt->execute();
        $stmt->close();
    }
    
    echo "<p>Inserted " . count($clients) . " clients</p>\n";
    
    // Insert additional products (200+ as requested)
    $products = [];
    $electronics = [
        ['Samsung TV', '55 inch 4K Smart TV', 'Electronics', 'Samsung', 'UN55TU7000'],
        ['Samsung TV', '65 inch 4K QLED TV', 'Electronics', 'Samsung', 'QN65Q700T'],
        ['LG TV', '55 inch OLED TV', 'Electronics', 'LG', 'OLED55CXPUA'],
        ['Sony TV', '65 inch 4K LED TV', 'Electronics', 'Sony', 'XBR65X900H'],
        ['iPhone', 'Latest Apple smartphone', 'Electronics', 'Apple', 'iPhone14'],
        ['iPhone', 'Previous generation smartphone', 'Electronics', 'Apple', 'iPhone13'],
        ['Samsung Galaxy', 'Latest Android smartphone', 'Electronics', 'Samsung', 'GalaxyS22'],
        ['Google Pixel', '5G smartphone with advanced camera', 'Electronics', 'Google', 'Pixel7'],
        ['iPad', '10.9-inch tablet with A14 Bionic chip', 'Electronics', 'Apple', 'iPadAir5'],
        ['iPad', '12.9-inch tablet with M1 chip', 'Electronics', 'Apple', 'iPadPro'],
        ['Samsung Galaxy Tab', '12.4-inch tablet with S Pen', 'Electronics', 'Samsung', 'SM-T970'],
        ['MacBook Air', 'M1 chip, 13-inch laptop', 'Electronics', 'Apple', 'MacBookAirM1'],
        ['MacBook Pro', 'M2 chip, 14-inch laptop', 'Electronics', 'Apple', 'MacBookProM2'],
        ['Dell Laptop', 'Inspiron 15 3000 series', 'Electronics', 'Dell', 'Inspiron15'],
        ['HP Laptop', 'Pavilion 15 series', 'Electronics', 'HP', 'Pavilion15'],
        ['Lenovo Laptop', 'ThinkPad E15 series', 'Electronics', 'Lenovo', 'ThinkPadE15'],
        ['Sony Headphones', 'Wireless noise-cancelling headphones', 'Electronics', 'Sony', 'WH-1000XM4'],
        ['Apple AirPods', 'Wireless earbuds', 'Electronics', 'Apple', 'AirPodsPro'],
        ['PlayStation', 'Next-generation gaming console', 'Electronics', 'Sony', 'PS5'],
        ['Xbox', 'Next-generation gaming console', 'Electronics', 'Microsoft', 'XboxSeriesX']
    ];
    
    $appliances = [
        ['Whirlpool Refrigerator', '25 cu ft French Door Refrigerator', 'Appliances', 'Whirlpool', 'WRX735SDHZ'],
        ['Samsung Refrigerator', '28 cu ft Side-by-Side Refrigerator', 'Appliances', 'Samsung', 'RF28R7351SR'],
        ['LG Refrigerator', '22 cu ft French Door Refrigerator', 'Appliances', 'LG', 'LFX25978ST'],
        ['GE Refrigerator', '18 cu ft Top-Freezer Refrigerator', 'Appliances', 'GE', 'GTE18GTHWW'],
        ['Bosch Washing Machine', '8 kg Front Load Washing Machine', 'Appliances', 'Bosch', 'WAT28480'],
        ['LG Washing Machine', '9 kg Front Load Washing Machine', 'Appliances', 'LG', 'WM3997HWA'],
        ['Samsung Washing Machine', '7 kg Top Load Washing Machine', 'Appliances', 'Samsung', 'WA70F5E5U2W'],
        ['Whirlpool Washing Machine', '6 kg Semi-Automatic Washing Machine', 'Appliances', 'Whirlpool', 'WMW2020LW'],
        ['Bosch Dryer', 'Electric dryer with steam technology', 'Appliances', 'Bosch', 'WTG86400'],
        ['Samsung Dryer', 'Electric dryer with steam technology', 'Appliances', 'Samsung', 'DV45T6000EV'],
        ['LG Dryer', 'Gas dryer with sensor dry', 'Appliances', 'LG', 'DLGX7881WE'],
        ['GE Dryer', 'Electric dryer with wrinkle guard', 'Appliances', 'GE', 'GTD65EBSJWW'],
        ['KitchenAid Stand Mixer', '5-quart tilt-head stand mixer', 'Appliances', 'KitchenAid', 'KSM150PSER'],
        ['Cuisinart Food Processor', '14-cup food processor', 'Appliances', 'Cuisinart', 'DLC-8SMP'],
        ['Ninja Blender', '1500-watt professional blender', 'Appliances', 'Ninja', 'BL660'],
        ['Vitamix Blender', 'Professional-grade blender', 'Appliances', 'Vitamix', '5200'],
        ['Dyson Vacuum Cleaner', 'Cordless stick vacuum cleaner', 'Appliances', 'Dyson', 'V11'],
        ['Shark Vacuum Cleaner', 'Upright vacuum with lift-away', 'Appliances', 'Shark', 'NV752'],
        ['Bissell Vacuum Cleaner', 'Cordless handheld vacuum', 'Appliances', 'Bissell', '2594A'],
        ['Instant Pot', '7-in-1 electric pressure cooker', 'Appliances', 'Instant Pot', 'IP-DUO60']
    ];
    
    // Generate 200+ products
    for ($i = 1; $i <= 200; $i++) {
        if ($i <= 100) {
            // Electronics
            $product_data = $electronics[array_rand($electronics)];
        } else {
            // Appliances
            $product_data = $appliances[array_rand($appliances)];
        }
        
        $product_id = 'PROD' . str_pad($i, 3, '0', STR_PAD_LEFT);
        $product_name = $product_data[0] . ' ' . rand(1, 100);
        $description = $product_data[1];
        $category = $product_data[2];
        $brand = $product_data[3];
        $model = $product_data[4] . '-' . rand(100, 999);
        $serial_number = strtoupper(substr($brand, 0, 3)) . rand(10000, 99999);
        $purchase_price = rand(100, 2000) + (rand(0, 99) / 100);
        $leasing_rate = rand(5, 30) + (rand(0, 99) / 100);
        $status = (rand(1, 10) == 1) ? 'Leased' : 'Available'; // 10% already leased
        $condition = 'New';
        
        $products[] = [$product_id, $product_name, $description, $category, $brand, $model, $serial_number, $purchase_price, $leasing_rate, $outlet_id, $status, $condition];
    }
    
    foreach ($products as $product) {
        $stmt = $conn->prepare("INSERT IGNORE INTO products (product_id, product_name, description, category, brand, model, serial_number, purchase_price, leasing_rate, outlet_id, status, `condition`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssdssss", $product[0], $product[1], $product[2], $product[3], $product[4], $product[5], $product[6], $product[7], $product[8], $product[9], $product[10], $product[11]);
        $stmt->execute();
        $stmt->close();
    }
    
    echo "<p>Inserted " . count($products) . " products</p>\n";
    
    // Insert leases (50+ as requested)
    $leases = [];
    $client_ids = [];
    $result = $conn->query("SELECT client_id FROM clients WHERE status = 'Active' LIMIT 50");
    while ($row = $result->fetch_assoc()) {
        $client_ids[] = $row['client_id'];
    }
    
    $product_ids = [];
    $result = $conn->query("SELECT product_id FROM products WHERE status = 'Available' LIMIT 50");
    while ($row = $result->fetch_assoc()) {
        $product_ids[] = $row['product_id'];
    }
    
    $recovery_person_ids = [];
    $result = $conn->query("SELECT recovery_person_id FROM recovery_persons WHERE status = 'Active'");
    while ($row = $result->fetch_assoc()) {
        $recovery_person_ids[] = $row['recovery_person_id'];
    }
    
    for ($i = 1; $i <= 50; $i++) {
        $lease_id = 'LEASE' . str_pad($i, 3, '0', STR_PAD_LEFT);
        $client_id = $client_ids[array_rand($client_ids)];
        $product_id = $product_ids[array_rand($product_ids)];
        $recovery_person_id = $recovery_person_ids[array_rand($recovery_person_ids)];
        
        // Get product details for lease calculation
        $prod_result = $conn->query("SELECT purchase_price, leasing_rate FROM products WHERE product_id = '$product_id'");
        $product_data = $prod_result->fetch_assoc();
        
        $start_date = date('Y-m-d', strtotime('-' . rand(1, 12) . ' months'));
        $lease_term_months = rand(6, 24);
        $end_date = date('Y-m-d', strtotime($start_date . ' + ' . $lease_term_months . ' months'));
        $total_amount = $product_data['purchase_price'];
        $installment_amount = round($total_amount / $lease_term_months, 2);
        $number_of_installments = $lease_term_months;
        $outstanding_amount = $total_amount;
        $status = (rand(1, 10) == 1) ? 'Overdue' : 'Active'; // 10% overdue
        
        $leases[] = [$lease_id, $client_id, null, $recovery_person_id, $product_id, $outlet_id, $start_date, $end_date, $total_amount, $installment_amount, $number_of_installments, $outstanding_amount, $status];
    }
    
    foreach ($leases as $lease) {
        // Insert lease
        $stmt = $conn->prepare("INSERT IGNORE INTO leases (lease_id, client_id, guarantor_id, recovery_person_id, product_id, outlet_id, start_date, end_date, total_amount, installment_amount, number_of_installments, outstanding_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssddds", $lease[0], $lease[1], $lease[2], $lease[3], $lease[4], $lease[5], $lease[6], $lease[7], $lease[8], $lease[9], $lease[10], $lease[11], $lease[12]);
        $stmt->execute();
        $lease_id = $conn->insert_id;
        $stmt->close();
        
        // Update product status to Leased
        $stmt = $conn->prepare("UPDATE products SET status = 'Leased' WHERE product_id = ?");
        $stmt->bind_param("s", $lease[4]);
        $stmt->execute();
        $stmt->close();
        
        // Generate installment schedule
        if ($lease_id) {
            $current_date = new DateTime($lease[6]); // start_date
            for ($j = 1; $j <= $lease[10]; $j++) { // number_of_installments
                $due_date = clone $current_date;
                $amount = $lease[9]; // installment_amount
                $status = ($j <= rand(1, $lease[10])) ? 'Paid' : 'Pending'; // Randomly mark some as paid
                $paid_amount = ($status == 'Paid') ? $amount : 0;
                $payment_date = ($status == 'Paid') ? $due_date->format('Y-m-d') : null;
                
                $stmt = $conn->prepare("INSERT IGNORE INTO installment_schedule (lease_id, installment_number, due_date, amount, paid_amount, status, payment_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sisddss", $lease[0], $j, $due_date->format('Y-m-d'), $amount, $paid_amount, $status, $payment_date);
                $stmt->execute();
                $stmt->close();
                
                // Move to next month
                $current_date->modify('+1 month');
            }
        }
    }
    
    echo "<p>Inserted " . count($leases) . " leases with installment schedules</p>\n";
    
    // Insert overdue leases (30 as requested)
    $overdue_leases = [];
    $client_ids = [];
    $result = $conn->query("SELECT client_id FROM clients WHERE status = 'Active' LIMIT 30");
    while ($row = $result->fetch_assoc()) {
        $client_ids[] = $row['client_id'];
    }
    
    $product_ids = [];
    $result = $conn->query("SELECT product_id FROM products WHERE status = 'Available' LIMIT 30");
    while ($row = $result->fetch_assoc()) {
        $product_ids[] = $row['product_id'];
    }
    
    for ($i = 51; $i <= 80; $i++) {
        $lease_id = 'LEASE' . str_pad($i, 3, '0', STR_PAD_LEFT);
        $client_id = $client_ids[array_rand($client_ids)];
        $product_id = $product_ids[array_rand($product_ids)];
        $recovery_person_id = $recovery_person_ids[array_rand($recovery_person_ids)];
        
        // Get product details for lease calculation
        $prod_result = $conn->query("SELECT purchase_price, leasing_rate FROM products WHERE product_id = '$product_id'");
        $product_data = $prod_result->fetch_assoc();
        
        $start_date = date('Y-m-d', strtotime('-' . rand(13, 24) . ' months'));
        $lease_term_months = rand(6, 12);
        $end_date = date('Y-m-d', strtotime($start_date . ' + ' . $lease_term_months . ' months'));
        $total_amount = $product_data['purchase_price'];
        $installment_amount = round($total_amount / $lease_term_months, 2);
        $number_of_installments = $lease_term_months;
        $outstanding_amount = $total_amount * (rand(20, 80) / 100); // 20-80% outstanding
        $status = 'Overdue';
        
        $overdue_leases[] = [$lease_id, $client_id, null, $recovery_person_id, $product_id, $outlet_id, $start_date, $end_date, $total_amount, $installment_amount, $number_of_installments, $outstanding_amount, $status];
    }
    
    foreach ($overdue_leases as $lease) {
        // Insert lease
        $stmt = $conn->prepare("INSERT IGNORE INTO leases (lease_id, client_id, guarantor_id, recovery_person_id, product_id, outlet_id, start_date, end_date, total_amount, installment_amount, number_of_installments, outstanding_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssddds", $lease[0], $lease[1], $lease[2], $lease[3], $lease[4], $lease[5], $lease[6], $lease[7], $lease[8], $lease[9], $lease[10], $lease[11], $lease[12]);
        $stmt->execute();
        $lease_id = $conn->insert_id;
        $stmt->close();
        
        // Update product status to Leased
        $stmt = $conn->prepare("UPDATE products SET status = 'Leased' WHERE product_id = ?");
        $stmt->bind_param("s", $lease[4]);
        $stmt->execute();
        $stmt->close();
        
        // Generate installment schedule
        if ($lease_id) {
            $current_date = new DateTime($lease[6]); // start_date
            for ($j = 1; $j <= $lease[10]; $j++) { // number_of_installments
                $due_date = clone $current_date;
                $amount = $lease[9]; // installment_amount
                $status = (rand(1, 3) == 1) ? 'Overdue' : 'Pending'; // 33% overdue
                $paid_amount = 0;
                $payment_date = null;
                
                $stmt = $conn->prepare("INSERT IGNORE INTO installment_schedule (lease_id, installment_number, due_date, amount, paid_amount, status, payment_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sisddss", $lease[0], $j, $due_date->format('Y-m-d'), $amount, $paid_amount, $status, $payment_date);
                $stmt->execute();
                $stmt->close();
                
                // Move to next month
                $current_date->modify('+1 month');
            }
        }
    }
    
    echo "<p>Inserted " . count($overdue_leases) . " overdue leases with installment schedules</p>\n";
    
    // Insert recovery collections (200+ as requested)
    $collections = [];
    for ($i = 1; $i <= 200; $i++) {
        $recovery_person_id = $recovery_person_ids[array_rand($recovery_person_ids)];
        $collection_type = ['Cash', 'Bank', 'OnlineTransfer'][array_rand(['Cash', 'Bank', 'OnlineTransfer'])];
        $amount = rand(100, 1000) + (rand(0, 99) / 100);
        $collection_date = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'));
        $bank_name = ($collection_type == 'Bank') ? ['Chase', 'Bank of America', 'Wells Fargo', 'Citi'][array_rand(['Chase', 'Bank of America', 'Wells Fargo', 'Citi'])] : null;
        $account_number = ($collection_type == 'Bank') ? rand(1000, 9999) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999) : null;
        $reference_no = 'REF' . str_pad($i, 4, '0', STR_PAD_LEFT);
        $approval_status = ['Pending', 'Verified', 'Approved', 'Rejected'][array_rand(['Pending', 'Verified', 'Approved', 'Rejected'])];
        
        $collections[] = [$recovery_person_id, $collection_type, $bank_name, $account_number, $reference_no, $amount, $collection_date, $outlet_id, $approval_status];
    }
    
    foreach ($collections as $collection) {
        $stmt = $conn->prepare("INSERT IGNORE INTO recovery_collections (recovery_person_id, collection_type, bank_name, account_number, reference_no, amount, collection_date, outlet_id, approval_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssds", $collection[0], $collection[1], $collection[2], $collection[3], $collection[4], $collection[5], $collection[6], $collection[7], $collection[8]);
        $stmt->execute();
        $stmt->close();
    }
    
    echo "<p>Inserted " . count($collections) . " recovery collections</p>\n";
    
    echo "<h3>Demo data population completed successfully!</h3>\n";
}

// Call the function to generate comprehensive demo data
generateComprehensiveDemoData($conn);

$conn->close();
?>