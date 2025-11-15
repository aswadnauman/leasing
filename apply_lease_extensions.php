<?php
require_once 'config/db.php';

$conn = getDBConnection();

// Read the SQL file
$sql_content = file_get_contents('lease_management_extensions.sql');

// Split the content into individual statements
$statements = explode(';', $sql_content);

echo "<h2>Applying Lease Management Extensions</h2>\n";

$success_count = 0;
$error_count = 0;
$create_table_statements = [];
$alter_table_statements = [];
$index_statements = [];
$insert_statements = [];
$other_statements = [];

// Categorize statements
foreach ($statements as $statement) {
    $statement = trim($statement);
    
    // Skip empty statements
    if (empty($statement)) {
        continue;
    }
    
    // Skip comments
    if (strpos($statement, '--') === 0) {
        continue;
    }
    
    // Categorize statements
    if (stripos($statement, 'CREATE TABLE') !== false) {
        $create_table_statements[] = $statement;
    } elseif (stripos($statement, 'ALTER TABLE') !== false) {
        $alter_table_statements[] = $statement;
    } elseif (stripos($statement, 'CREATE INDEX') !== false) {
        $index_statements[] = $statement;
    } elseif (stripos($statement, 'INSERT') !== false) {
        $insert_statements[] = $statement;
    } else {
        $other_statements[] = $statement;
    }
}

// Execute statements in proper order
$all_statements = array_merge($create_table_statements, $alter_table_statements, $index_statements, $insert_statements, $other_statements);

foreach ($all_statements as $statement) {
    // Skip empty statements
    if (empty($statement)) {
        continue;
    }
    
    // Execute the statement
    if ($conn->query($statement) === TRUE) {
        $success_count++;
        echo "<p style='color:green;'>Statement " . $success_count . " executed successfully</p>\n";
    } else {
        $error_count++;
        echo "<p style='color:orange;'>Warning: " . $conn->error . "</p>\n";
        // Continue with other statements even if one fails
    }
}

$conn->close();

echo "<h2>Process Complete</h2>\n";
echo "<p>Successfully executed: " . $success_count . " statements</p>\n";
echo "<p>Warnings: " . $error_count . "</p>\n";
echo "<p>Lease management extensions have been applied.</p>\n";
?>