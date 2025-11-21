<?php
session_start();
// Test file to verify that all navigation links are working correctly
echo "<h2>Navigation Links Test</h2>";
echo "<p>This test verifies that all navigation links point to the correct individual pages.</p>";

echo "<ul>";
echo "<li><a href='profession.php'>Profession Management</a></li>";
echo "<li><a href='area.php'>Area Management</a></li>";
echo "<li><a href='road.php'>Road Management</a></li>";
echo "<li><a href='city.php'>City Management</a></li>";
echo "<li><a href='recovery_person.php'>Recovery Person Management</a></li>";
echo "</ul>";

echo "<p>All links above should navigate to the respective individual management pages.</p>";
?>