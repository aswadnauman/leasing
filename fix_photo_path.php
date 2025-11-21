<?php
include 'config/db.php';

$conn = getDBConnection();

// Update the photo_path for RP001
$photo_path = 'uploads/recovery_persons/rp_RP001_photo.jpg';
$recovery_person_id = 'RP001';

$stmt = $conn->prepare("UPDATE recovery_persons SET photo_path = ? WHERE recovery_person_id = ?");
$stmt->bind_param("ss", $photo_path, $recovery_person_id);

if ($stmt->execute()) {
    echo "Photo path updated successfully\n";
} else {
    echo "Error updating photo path: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
?>