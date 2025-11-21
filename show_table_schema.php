<?php
include 'config/db.php';

$conn = getDBConnection();
$result = $conn->query('SHOW CREATE TABLE recovery_persons');
if ($row = $result->fetch_row()) {
    echo $row[1];
}
$conn->close();
?>