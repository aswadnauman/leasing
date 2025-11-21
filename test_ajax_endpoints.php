<?php
// Test AJAX endpoints for dropdowns
include 'config/db.php';

if (isset($_GET['action']) && $_GET['action'] == 'search_master' && isset($_GET['type']) && isset($_GET['q'])) {
    $conn = getDBConnection();
    $type = $_GET['type'];
    $search = $_GET['q'];
    $searchTerm = "%$search%";
    
    switch($type) {
        case 'profession':
            $stmt = $conn->prepare("SELECT profession as id, profession as text FROM master_profession WHERE profession LIKE ? ORDER BY profession LIMIT 20");
            break;
        case 'area':
            $stmt = $conn->prepare("SELECT area as id, area as text FROM master_area WHERE area LIKE ? ORDER BY area LIMIT 20");
            break;
        case 'road':
            $stmt = $conn->prepare("SELECT road as id, road as text FROM master_road WHERE road LIKE ? ORDER BY road LIMIT 20");
            break;
        case 'city':
            $stmt = $conn->prepare("SELECT city as id, city as text FROM master_city WHERE city LIKE ? ORDER BY city LIMIT 20");
            break;
        default:
            // Format the response correctly for Select2
            $response = array(
                "results" => array()
            );
            
            header('Content-Type: application/json');
            echo json_encode($response);
            $conn->close();
            exit();
    }
    
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = array();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    // Format the response correctly for Select2
    $response = array(
        "results" => $data
    );
    
    header('Content-Type: application/json');
    echo json_encode($response);
    $stmt->close();
    $conn->close();
    exit();
}

// Test the endpoint
echo "<h2>Testing AJAX Endpoints</h2>";
echo "<p>Test URL: test_ajax_endpoints.php?action=search_master&type=profession&q=eng</p>";
echo "<p>Expected response format:</p>";
echo "<pre>";
echo '{
    "results": [
        {
            "id": "Engineer",
            "text": "Engineer"
        },
        {
            "id": "Engineering",
            "text": "Engineering"
        }
    ]
}';
echo "</pre>";
?>