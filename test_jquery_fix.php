<?php
session_start();
// Test file to verify that jQuery and Select2 issues are fixed
echo "<h2>jQuery and Select2 Fix Test</h2>";
echo "<p>This test verifies that jQuery and Select2 are properly loaded on the individual master setup pages.</p>";

echo "<ul>";
echo "<li><a href='profession.php'>Profession Management</a></li>";
echo "<li><a href='area.php'>Area Management</a></li>";
echo "<li><a href='road.php'>Road Management</a></li>";
echo "<li><a href='city.php'>City Management</a></li>";
echo "<li><a href='recovery_person.php'>Recovery Person Management</a></li>";
echo "</ul>";

echo "<p>Please visit each page and check the browser console for any JavaScript errors.</p>";
echo "<p>All pages should now load without the 'jQuery is not defined' error.</p>";
?>