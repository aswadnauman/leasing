<?php
// Test file for dropdown functionality
include 'config/db.php';

$conn = getDBConnection();

// Test master data retrieval
echo "<h2>Testing Master Data Retrieval</h2>";

// Test professions
echo "<h3>Professions:</h3>";
$result = $conn->query("SELECT * FROM master_profession LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - Profession: " . $row['profession'] . "<br>";
}

// Test areas
echo "<h3>Areas:</h3>";
$result = $conn->query("SELECT * FROM master_area LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - Area: " . $row['area'] . "<br>";
}

// Test roads
echo "<h3>Roads:</h3>";
$result = $conn->query("SELECT * FROM master_road LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - Road: " . $row['road'] . "<br>";
}

// Test cities
echo "<h3>Cities:</h3>";
$result = $conn->query("SELECT * FROM master_city LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - City: " . $row['city'] . "<br>";
}

$conn->close();
?>