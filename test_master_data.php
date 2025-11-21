<?php
include 'config/db.php';

$conn = getDBConnection();

echo "<h2>Master Data Test</h2>";

// Test professions
echo "<h3>Professions:</h3>";
$result = $conn->query("SELECT * FROM master_profession LIMIT 5");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " - Profession: " . $row['profession'] . "<br>";
    }
} else {
    echo "No professions found<br>";
}

// Test areas
echo "<h3>Areas:</h3>";
$result = $conn->query("SELECT * FROM master_area LIMIT 5");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " - Area: " . $row['area'] . "<br>";
    }
} else {
    echo "No areas found<br>";
}

// Test roads
echo "<h3>Roads:</h3>";
$result = $conn->query("SELECT * FROM master_road LIMIT 5");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " - Road: " . $row['road'] . "<br>";
    }
} else {
    echo "No roads found<br>";
}

// Test cities
echo "<h3>Cities:</h3>";
$result = $conn->query("SELECT * FROM master_city LIMIT 5");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " - City: " . $row['city'] . "<br>";
    }
} else {
    echo "No cities found<br>";
}

$conn->close();
?>