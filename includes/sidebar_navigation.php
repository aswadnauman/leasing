<?php
// Enhanced Sidebar navigation component with role-based access control
// This component provides a structured, module-wise navigation menu with collapsible sections

// Get user role from session
$user_role = $_SESSION['role'] ?? 'User';
?>

<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="sidebarMenuLabel">Lease Management System</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body d-flex flex-column p-0">
    <nav class="nav flex-column flex-grow-1">
      <!-- Dashboard Section -->
      <div class="nav-item">
        <a class="nav-link" href="dashboard.php">
          <i class="bi bi-speedometer2 me-2"></i>Dashboard
        </a>
      </div>
      
      <?php if ($user_role == 'Admin' || $user_role == 'BranchManager' || $user_role == 'Sales'): ?>
      <!-- Master Settings Section (Collapsible) -->
      <div class="nav-item mt-3">
        <a class="nav-link dropdown-toggle" data-bs-toggle="collapse" href="#masterSettings" role="button" aria-expanded="false" aria-controls="masterSettings">
          <i class="bi bi-gear me-2"></i>Master Settings
        </a>
        <div class="collapse" id="masterSettings">
          <div class="nav flex-column ms-4">
            <div class="nav-item">
              <a class="nav-link" href="profession.php">
                <i class="bi bi-briefcase me-2"></i>Profession
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="area.php">
                <i class="bi bi-map me-2"></i>Area
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="road.php">
                <i class="bi bi-signpost me-2"></i>Road
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="city.php">
                <i class="bi bi-building me-2"></i>City
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="recovery_person.php">
                <i class="bi bi-person-badge me-2"></i>Recovery Person
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <?php if ($user_role == 'Admin' || $user_role == 'BranchManager' || $user_role == 'Sales'): ?>
      <!-- Client Management Section (Collapsible) -->
      <div class="nav-item mt-3">
        <a class="nav-link dropdown-toggle" data-bs-toggle="collapse" href="#clientManagement" role="button" aria-expanded="false" aria-controls="clientManagement">
          <i class="bi bi-people me-2"></i>Client Management
        </a>
        <div class="collapse" id="clientManagement">
          <div class="nav flex-column ms-4">
            <div class="nav-item">
              <a class="nav-link" href="clients.php?action=add">
                <i class="bi bi-person-plus me-2"></i>Add Client
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="clients.php">
                <i class="bi bi-search me-2"></i>Search Clients
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="clients.php#list">
                <i class="bi bi-list me-2"></i>Client List
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <?php if ($user_role == 'Admin' || $user_role == 'BranchManager' || $user_role == 'Sales'): ?>
      <!-- Lease Management Section (Collapsible) -->
      <div class="nav-item mt-3">
        <a class="nav-link dropdown-toggle" data-bs-toggle="collapse" href="#leaseManagement" role="button" aria-expanded="false" aria-controls="leaseManagement">
          <i class="bi bi-file-earmark-text me-2"></i>Lease Management
        </a>
        <div class="collapse" id="leaseManagement">
          <div class="nav flex-column ms-4">
            <div class="nav-item">
              <a class="nav-link" href="lease_registration.php">
                <i class="bi bi-file-earmark-plus me-2"></i>New Lease Registration
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="leases.php">
                <i class="bi bi-search me-2"></i>Lease List / Search
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="lease_terminations.php">
                <i class="bi bi-x-circle me-2"></i>Lease Renewal / Termination
              </a>
            </div>
            <?php if ($user_role == 'Admin' || $user_role == 'BranchManager'): ?>
            <div class="nav-item">
              <a class="nav-link" href="leases.php#approval">
                <i class="bi bi-check-circle me-2"></i>Lease Approval
              </a>
            </div>
            <?php endif; ?>
            <div class="nav-item">
              <a class="nav-link" href="leases.php#recovery">
                <i class="bi bi-person-check me-2"></i>Recovery Assignment
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="reports.php#outstanding">
                <i class="bi bi-cash-stack me-2"></i>Outstanding & Overdue Reports
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <?php if ($user_role == 'Admin' || $user_role == 'BranchManager' || $user_role == 'RecoveryOfficer' || $user_role == 'AccountsOfficer'): ?>
      <!-- Payment & Collection Section (Collapsible) -->
      <div class="nav-item mt-3">
        <a class="nav-link dropdown-toggle" data-bs-toggle="collapse" href="#collectionManagement" role="button" aria-expanded="false" aria-controls="collectionManagement">
          <i class="bi bi-cash-stack me-2"></i>Payment & Collection
        </a>
        <div class="collapse" id="collectionManagement">
          <div class="nav flex-column ms-4">
            <div class="nav-item">
              <a class="nav-link" href="payment_collection.php">
                <i class="bi bi-credit-card me-2"></i>Record Installment / Payment
              </a>
            </div>
            <?php if ($user_role == 'Admin' || $user_role == 'BranchManager' || $user_role == 'AccountsOfficer'): ?>
            <div class="nav-item">
              <a class="nav-link" href="collections.php#approval">
                <i class="bi bi-shield-check me-2"></i>Cash & Bank Collection Approval
              </a>
            </div>
            <?php endif; ?>
            <div class="nav-item">
              <a class="nav-link" href="reports.php#recovery">
                <i class="bi bi-person-lines-fill me-2"></i>Recovery Person Wise Summary
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="reports.php#daily">
                <i class="bi bi-calendar-day me-2"></i>Daily Collection Report
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="reports.php#overdue">
                <i class="bi bi-exclamation-triangle me-2"></i>Overdue Collection Report
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <?php if ($user_role == 'Admin' || $user_role == 'BranchManager' || $user_role == 'Sales'): ?>
      <!-- Inventory & Product Management Section (Collapsible) -->
      <div class="nav-item mt-3">
        <a class="nav-link dropdown-toggle" data-bs-toggle="collapse" href="#inventoryManagement" role="button" aria-expanded="false" aria-controls="inventoryManagement">
          <i class="bi bi-box-seam me-2"></i>Inventory & Product
        </a>
        <div class="collapse" id="inventoryManagement">
          <div class="nav flex-column ms-4">
            <div class="nav-item">
              <a class="nav-link" href="inventory_module.php">
                <i class="bi bi-grid me-2"></i>Inventory Module
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="products.php?action=add">
                <i class="bi bi-plus-circle me-2"></i>Add Product
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="products.php">
                <i class="bi bi-search me-2"></i>Product Master
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="products.php#category">
                <i class="bi bi-tags me-2"></i>Product Category / Brand
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="reports.php#inventory">
                <i class="bi bi-boxes me-2"></i>Product Tracking
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="products.php#maintenance">
                <i class="bi bi-tools me-2"></i>Maintenance Log
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="inventory_dashboard.php">
                <i class="bi bi-speedometer2 me-2"></i>Inventory Dashboard
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="inventory_management.php">
                <i class="bi bi-arrow-left-right me-2"></i>Inventory Transactions
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="stock_adjustment.php">
                <i class="bi bi-tools me-2"></i>Stock Adjustment
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="sales.php">
                <i class="bi bi-currency-dollar me-2"></i>Sales
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="sales_returns.php">
                <i class="bi bi-arrow-return-left me-2"></i>Sales Returns
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="purchases.php">
                <i class="bi bi-cart-plus me-2"></i>Purchases
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="purchase_returns.php">
                <i class="bi bi-arrow-return-right me-2"></i>Purchase Returns
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- Reporting Section (Collapsible) -->
      <div class="nav-item mt-3">
        <a class="nav-link dropdown-toggle" data-bs-toggle="collapse" href="#reporting" role="button" aria-expanded="false" aria-controls="reporting">
          <i class="bi bi-bar-chart me-2"></i>Reporting
        </a>
        <div class="collapse" id="reporting">
          <div class="nav flex-column ms-4">
            <div class="nav-item">
              <a class="nav-link" href="reports.php#client">
                <i class="bi bi-people me-2"></i>Client Summary Report
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="reports.php#lease">
                <i class="bi bi-file-earmark-text me-2"></i>Lease Register
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="reports.php#recovery">
                <i class="bi bi-person-badge me-2"></i>Recovery Analysis
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="reports.php#revenue">
                <i class="bi bi-currency-dollar me-2"></i>Revenue Summary
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="branch_analysis.php">
                <i class="bi bi-diagram-3 me-2"></i>Multi-Outlet Consolidated Report
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="branch_analysis.php#outlet">
                <i class="bi bi-shop me-2"></i>Outlet-Wise Analysis
              </a>
            </div>
          </div>
        </div>
      </div>
      
      <?php if ($user_role == 'Admin'): ?>
      <!-- Administration Section (Collapsible) -->
      <div class="nav-item mt-3">
        <a class="nav-link dropdown-toggle" data-bs-toggle="collapse" href="#administration" role="button" aria-expanded="false" aria-controls="administration">
          <i class="bi bi-shield-lock me-2"></i>Administration
        </a>
        <div class="collapse" id="administration">
          <div class="nav flex-column ms-4">
            <div class="nav-item">
              <a class="nav-link" href="users.php">
                <i class="bi bi-people me-2"></i>User Management
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="users.php#roles">
                <i class="bi bi-person-check me-2"></i>Role & Rights Setup
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="audit_trail.php">
                <i class="bi bi-journal-text me-2"></i>Audit Trail
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="profession.php">
                <i class="bi bi-gear me-2"></i>System Configuration
              </a>
            </div>
            <div class="nav-item">
              <a class="nav-link" href="backup_restore.php">
                <i class="bi bi-cloud-arrow-up me-2"></i>Backup & Restore
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- Outlet Management (Admin only) -->
      <?php if ($user_role == 'Admin'): ?>
      <div class="nav-item mt-3">
        <a class="nav-link" href="outlets.php">
          <i class="bi bi-shop me-2"></i>Outlet Management
        </a>
      </div>
      <?php endif; ?>
    </nav>
  </div>
</div>