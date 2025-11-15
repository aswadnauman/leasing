<?php
function generateDynamicMenu($conn, $user_role, $user_id) {
    // For admin users, they have all permissions
    $is_admin = ($user_role == 'Admin');
    
    // Get user permissions if not admin
    $user_permissions = [];
    if (!$is_admin) {
        // Get role name for the user
        $role_stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
        $role_stmt->bind_param("s", $user_id);
        $role_stmt->execute();
        $role_result = $role_stmt->get_result();
        $user = $role_result->fetch_assoc();
        $role_stmt->close();
        
        if ($user) {
            // Get permissions for the user's role
            $perm_stmt = $conn->prepare("
                SELECT rp.permission 
                FROM role_permissions rp
                JOIN user_roles ur ON rp.role_id = ur.id
                WHERE ur.role_name = ?
            ");
            $perm_stmt->bind_param("s", $user['role']);
            $perm_stmt->execute();
            $perm_result = $perm_stmt->get_result();
            
            while ($perm = $perm_result->fetch_assoc()) {
                $user_permissions[] = $perm['permission'];
            }
            $perm_stmt->close();
        }
    }
    
    // Get all active menu items ordered by sort_order
    $menu_result = $conn->query("
        SELECT * FROM menu_master 
        WHERE is_active = 1 
        ORDER BY parent_id, sort_order
    ");
    
    $menu_items = [];
    if ($menu_result && $menu_result->num_rows > 0) {
        while ($row = $menu_result->fetch_assoc()) {
            $menu_items[] = $row;
        }
    }
    
    // Organize menu items into parent-child structure
    $organized_menu = [];
    $children = [];
    
    foreach ($menu_items as $item) {
        // Check if user has permission to see this menu item
        if (!$is_admin && !empty($item['required_permission']) && !in_array($item['required_permission'], $user_permissions)) {
            continue; // Skip this menu item
        }
        
        if ($item['parent_id'] == 0) {
            // Parent menu item
            $organized_menu[$item['id']] = $item;
            $organized_menu[$item['id']]['children'] = [];
        } else {
            // Child menu item
            $children[$item['parent_id']][] = $item;
        }
    }
    
    // Attach children to their parents
    foreach ($children as $parent_id => $child_items) {
        if (isset($organized_menu[$parent_id])) {
            $organized_menu[$parent_id]['children'] = $child_items;
        }
    }
    
    // Generate HTML for the menu
    $menu_html = '<div class="sidebar-nav">';
    $menu_html .= '<div class="nav flex-column">';
    
    foreach ($organized_menu as $item) {
        $has_children = !empty($item['children']);
        $is_active = (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], $item['menu_url']) !== false);
        
        if ($has_children) {
            // Parent menu item with children
            $menu_html .= '<div class="nav-item">';
            $menu_html .= '<a class="nav-link collapsed" href="#submenu-' . $item['id'] . '" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="submenu-' . $item['id'] . '">';
            $menu_html .= '<i class="bi ' . htmlspecialchars($item['icon_class']) . ' me-2"></i>';
            $menu_html .= '<span>' . htmlspecialchars($item['menu_name']) . '</span>';
            $menu_html .= '</a>';
            
            // Child menu items
            $menu_html .= '<div class="collapse" id="submenu-' . $item['id'] . '">';
            $menu_html .= '<ul class="nav flex-column ms-3">';
            
            foreach ($item['children'] as $child) {
                $child_is_active = (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], $child['menu_url']) !== false);
                $menu_html .= '<li class="nav-item">';
                $menu_html .= '<a class="nav-link ' . ($child_is_active ? 'active' : '') . '" href="' . htmlspecialchars($child['menu_url']) . '">';
                $menu_html .= '<i class="bi ' . htmlspecialchars($child['icon_class']) . ' me-2"></i>';
                $menu_html .= '<span>' . htmlspecialchars($child['menu_name']) . '</span>';
                $menu_html .= '</a>';
                $menu_html .= '</li>';
            }
            
            $menu_html .= '</ul>';
            $menu_html .= '</div>';
            $menu_html .= '</div>';
        } else {
            // Single menu item (no children)
            if ($item['menu_url'] == '#') {
                // Menu header (no link)
                $menu_html .= '<div class="nav-item">';
                $menu_html .= '<span class="nav-link disabled">';
                $menu_html .= '<i class="bi ' . htmlspecialchars($item['icon_class']) . ' me-2"></i>';
                $menu_html .= '<span>' . htmlspecialchars($item['menu_name']) . '</span>';
                $menu_html .= '</span>';
                $menu_html .= '</div>';
            } else {
                // Regular menu link
                $menu_html .= '<div class="nav-item">';
                $menu_html .= '<a class="nav-link ' . ($is_active ? 'active' : '') . '" href="' . htmlspecialchars($item['menu_url']) . '">';
                $menu_html .= '<i class="bi ' . htmlspecialchars($item['icon_class']) . ' me-2"></i>';
                $menu_html .= '<span>' . htmlspecialchars($item['menu_name']) . '</span>';
                $menu_html .= '</a>';
                $menu_html .= '</div>';
            }
        }
    }
    
    $menu_html .= '</div>';
    $menu_html .= '</div>';
    
    return $menu_html;
}
?>