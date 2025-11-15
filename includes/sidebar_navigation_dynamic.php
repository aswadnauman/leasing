<?php
require_once 'dynamic_menu.php';

// Generate dynamic menu based on user permissions
echo generateDynamicMenu($conn, $_SESSION['role'] ?? 'User', $_SESSION['user_id'] ?? '');
?>