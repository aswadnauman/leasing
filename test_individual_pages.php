<?php
session_start();
// Test file to verify individual master setup pages
echo "<h2>Individual Master Setup Pages Test</h2>";
echo "<p>This test verifies that all individual master setup pages have been created successfully.</p>";

echo "<ul>";
echo "<li><a href='profession.php'>Profession Management</a></li>";
echo "<li><a href='area.php'>Area Management</a></li>";
echo "<li><a href='road.php'>Road Management</a></li>";
echo "<li><a href='city.php'>City Management</a></li>";
echo "<li><a href='recovery_person.php'>Recovery Person Management</a></li>";
echo "</ul>";

echo "<p>Please click on each link above to verify that the individual pages load correctly.</p>";
echo "<p>Each page should have:</p>";
echo "<ol>";
echo "<li>Proper navigation sidebar</li>";
echo "<li>Back to Dashboard button</li>";
echo "<li>Full CRUD functionality (Create, Read, Update, Delete)</li>";
echo "<li>Search functionality</li>";
echo "<li>User authentication checks</li>";
echo "</ol>";
?>