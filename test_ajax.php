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
            echo json_encode([]);
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
?>
<!DOCTYPE html>
<html>
<head>
    <title>AJAX Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body>
    <h2>Test Select2 Dropdowns</h2>
    
    <label for="profession">Profession:</label>
    <select id="profession" class="select2-master" data-type="profession" style="width: 300px;">
        <option value="">Select Profession</option>
    </select>
    <br><br>
    
    <label for="area">Area:</label>
    <select id="area" class="select2-master" data-type="area" style="width: 300px;">
        <option value="">Select Area</option>
    </select>
    <br><br>
    
    <label for="road">Road:</label>
    <select id="road" class="select2-master" data-type="road" style="width: 300px;">
        <option value="">Select Road</option>
    </select>
    <br><br>
    
    <label for="city">City:</label>
    <select id="city" class="select2-master" data-type="city" style="width: 300px;">
        <option value="">Select City</option>
    </select>
    
    <script>
        $(document).ready(function() {
            $('.select2-master').select2({
                placeholder: "Search and select...",
                allowClear: true,
                ajax: {
                    url: 'test_ajax.php',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            action: 'search_master',
                            type: $(this).data('type'),
                            q: params.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1
            });
        });
    </script>
</body>
</html>