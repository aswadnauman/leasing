<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Lease Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <h2>Database Setup</h2>
                
                <?php
                if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_tables'])) {
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
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )";
                    
                    if ($conn->query($sql) === TRUE) {
                        echo "<div class=\"alert alert-success\">Table chart_of_accounts created successfully</div>";
                    } else {
                        echo "<div class=\"alert alert-danger\">Error creating table chart_of_accounts: " . $conn->error . "</div>";
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
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )";
                    
                    if ($conn->query($sql) === TRUE) {
                        echo "<div class=\"alert alert-success\">Table suppliers created successfully</div>";
                    } else {
                        echo "<div class=\"alert alert-danger\">Error creating table suppliers: " . $conn->error . "</div>";
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
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )";
                    
                    if ($conn->query($sql) === TRUE) {
                        echo "<div class=\"alert alert-success\">Table customers created successfully</div>";
                    } else {
                        echo "<div class=\"alert alert-danger\">Error creating table customers: " . $conn->error . "</div>";
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
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )";
                    
                    if ($conn->query($sql) === TRUE) {
                        echo "<div class=\"alert alert-success\">Table sales created successfully</div>";
                    } else {
                        echo "<div class=\"alert alert-danger\">Error creating table sales: " . $conn->error . "</div>";
                    }
                    
                    $conn->close();
                }
                ?>
                
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Create Database Tables</h5>
                        <p class="card-text">Click the button below to create the necessary database tables for the new features.</p>
                        <form method="POST">
                            <button type="submit" name="create_tables" class="btn btn-primary">Create Tables</button>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Current Database Tables</h5>
                        <?php
                        require_once 'config/db.php';
                        $conn = getDBConnection();
                        $result = $conn->query("SHOW TABLES");
                        
                        if ($result && $result->num_rows > 0) {
                            echo "<ul>";
                            while ($row = $result->fetch_row()) {
                                echo "<li>" . $row[0] . "</li>";
                            }
                            echo "</ul>";
                        } else {
                            echo "<p>No tables found or error occurred.</p>";
                        }
                        
                        $conn->close();
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>