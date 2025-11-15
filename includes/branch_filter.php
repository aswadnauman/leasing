<?php
// Branch filter component for reports
// This component provides a consistent branch filter dropdown for all reports

// Check if we have a database connection, if not, create one
if (!isset($conn) || !$conn || !($conn instanceof mysqli) || $conn->connect_errno) {
    // Connection is not available, create a new one
    require_once dirname(__DIR__) . '/config/db.php';
    $conn = getDBConnection();
    $connection_created_here = true;
} else {
    // Check if the connection is still alive
    if ($conn->ping() === false) {
        // Connection is dead, create a new one
        require_once dirname(__DIR__) . '/config/db.php';
        $conn = getDBConnection();
        $connection_created_here = true;
    } else {
        $connection_created_here = false;
    }
}

// Fetch all outlets for branch filter
$outlets_result = $conn->query("SELECT outlet_id, outlet_name FROM outlets ORDER BY outlet_name");

// Get selected branch from filter (if any)
$selected_branch = isset($_GET['branch_filter']) ? $_GET['branch_filter'] : 'ALL';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Only close connection if we created it here and no other code will use it
// This prevents issues when this include is used in scripts that need the connection later
if ($connection_created_here) {
    // We'll leave it open for the calling script to manage
    // The calling script should close it if it created it
}
?>

<div class="row mb-3">
    <div class="col-md-4">
        <label for="branchFilter" class="form-label">Branch Filter</label>
        <select class="form-select" id="branchFilter" name="branch_filter">
            <option value="ALL" <?php echo ($selected_branch == 'ALL') ? 'selected' : ''; ?>>All Branches</option>
            <?php if ($outlets_result && $outlets_result->num_rows > 0): ?>
                <?php while ($outlet = $outlets_result->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($outlet['outlet_id']); ?>" 
                            <?php echo ($selected_branch == $outlet['outlet_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($outlet['outlet_name']); ?>
                    </option>
                <?php endwhile; ?>
            <?php endif; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label for="startDate" class="form-label">Start Date</label>
        <input type="date" class="form-control" id="startDate" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
    </div>
    <div class="col-md-3">
        <label for="endDate" class="form-label">End Date</label>
        <input type="date" class="form-control" id="endDate" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-primary w-100" id="applyFilter">Apply Filters</button>
    </div>
</div>

<script>
// Initialize Select2 on the branch filter dropdown when the DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if jQuery and Select2 are loaded
    if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
        // Initialize Select2 on the branch filter dropdown
        $('#branchFilter').select2({
            placeholder: "Select a branch",
            allowClear: true,
            width: '100%'
        });
    }
});
</script>