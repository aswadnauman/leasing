<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user has appropriate privileges
if (!in_array($_SESSION['role'], ['Admin', 'BranchManager', 'Sales'])) {
    header("Location: dashboard.php");
    exit();
}

$conn = getDBConnection();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lease Dropdowns Test - Lease Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col">
                <h2>Lease Dropdowns Test</h2>
                <p>This page tests if the lease registration dropdowns are working correctly.</p>
                
                <div class="card">
                    <div class="card-header">
                        <h5>Lease Registration Dropdowns</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="client_test" class="form-label">Client Dropdown (Select2)</label>
                                    <select class="form-control select2-client" id="client_test" name="client_test">
                                        <option value="">Search and select client...</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="guarantor_test" class="form-label">Guarantor Dropdown (Select2)</label>
                                    <select class="form-control select2-client" id="guarantor_test" name="guarantor_test">
                                        <option value="">Search and select guarantor (Optional)...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="recovery_test" class="form-label">Recovery Person Dropdown (Select2)</label>
                                    <select class="form-control select2-recovery" id="recovery_test" name="recovery_test">
                                        <option value="">Search and select recovery person...</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product_test" class="form-label">Product Dropdown (Select2)</label>
                                    <select class="form-control select2-product" id="product_test" name="product_test">
                                        <option value="">Search and select product...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="outlet_test" class="form-label">Outlet Dropdown (Select2)</label>
                                    <select class="form-control select2-master" id="outlet_test" name="outlet_test" data-type="outlet">
                                        <option value="">Select Outlet</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="assets/js/master_data_dropdowns.js"></script>
</body>
</html>