<?php
// Test script to verify photo saving functionality
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['test_photo'])) {
    $conn = getDBConnection();
    
    // Test data
    $recovery_person_id = 'TEST001';
    $full_name = 'Test Person';
    $cnic = '12345-6789012-3';
    $mobile_number = '0300-1234567';
    $address = 'Test Address';
    $city_id = 1;
    $area_id = 1;
    $email = 'test@example.com';
    $outlet_id = 'OUT001';
    $status = 'Active';
    
    // Handle photo upload
    $photo_path = null;
    if (isset($_POST['photo_data']) && !empty($_POST['photo_data'])) {
        $photo_data = $_POST['photo_data'];
        
        // Remove data URL prefix if present
        if (strpos($photo_data, 'data:image') === 0) {
            $comma_pos = strpos($photo_data, ',');
            if ($comma_pos !== false) {
                $photo_data = substr($photo_data, $comma_pos + 1);
            }
        }
        
        // Decode base64 data
        $decoded_data = base64_decode($photo_data, true);
        
        // Validate that we have data
        if ($decoded_data !== false && strlen($decoded_data) > 0) {
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/recovery_persons/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename using the recovery_person_id from POST
            $filename = 'rp_' . $recovery_person_id . '_photo.jpg';
            $photo_path = $upload_dir . $filename;
            
            // Save photo and check if it was successful
            $bytes_written = file_put_contents($photo_path, $decoded_data);
            if ($bytes_written === false || $bytes_written === 0) {
                // Handle error if needed
                $photo_path = null;
            }
        }
    }
    
    echo "<h3>Test Results:</h3>";
    echo "<p>Photo path: " . htmlspecialchars($photo_path) . "</p>";
    echo "<p>File exists: " . (file_exists($photo_path) ? 'Yes' : 'No') . "</p>";
    
    if (file_exists($photo_path)) {
        echo "<p>File size: " . filesize($photo_path) . " bytes</p>";
        echo "<img src='" . $photo_path . "' alt='Test Photo' style='max-width: 200px;'>";
    }
    
    $conn->close();
} else {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Photo Save</title>
</head>
<body>
    <h2>Test Photo Save Functionality</h2>
    
    <form method="POST">
        <input type="hidden" name="test_photo" value="1">
        <input type="hidden" id="photo-data" name="photo_data">
        
        <div>
            <label for="photo-upload">Select Photo:</label>
            <input type="file" id="photo-upload" accept="image/*">
            <div id="photo-preview" style="margin-top: 10px;"></div>
        </div>
        
        <div style="margin-top: 20px;">
            <button type="submit">Test Save Photo</button>
        </div>
    </form>
    
    <script src="assets/js/image_handler.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize the photo upload handler
            if (typeof handleFileUpload === 'function') {
                handleFileUpload('photo-upload', 'photo-preview', 'photo-data');
            }
        });
    </script>
</body>
</html>
<?php
}
?>