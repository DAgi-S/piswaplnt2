<?php
require_once 'core.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    echo json_encode(array('success' => false, 'message' => 'User not logged in'));
    exit();
}

// Get the action from the request
$action = isset($_GET['action']) ? $_GET['action'] : null;

if (!$action) {
    echo json_encode(array('success' => false, 'message' => 'No action specified'));
    exit();
}

try {
    $response = array('success' => true);
    
    switch ($action) {
        case 'add_product':
            // Check if user has permission to add products
            if (!hasPermission('createProduct')) {
                throw new Exception('You do not have permission to add products');
            }
            $response['redirect'] = 'product.php?o=add';
            break;
            
        case 'add_order':
            // Check if user has permission to add orders
            if (!hasPermission('createOrder')) {
                throw new Exception('You do not have permission to add orders');
            }
            $response['redirect'] = 'orders.php?o=add';
            break;
            
        case 'add_category':
            // Check if user has permission to add categories
            if (!hasPermission('createCategory')) {
                throw new Exception('You do not have permission to add categories');
            }
            $response['redirect'] = 'categories.php?o=add';
            break;
            
        case 'add_brand':
            // Check if user has permission to add brands
            if (!hasPermission('createBrand')) {
                throw new Exception('You do not have permission to add brands');
            }
            $response['redirect'] = 'brands.php?o=add';
            break;
            
        case 'manage_users':
            // Check if user has permission to manage users
            if (!hasPermission('manageUsers')) {
                throw new Exception('You do not have permission to manage users');
            }
            $response['redirect'] = 'users.php';
            break;
            
        case 'reports':
            // Check if user has permission to view reports
            if (!hasPermission('viewReports')) {
                throw new Exception('You do not have permission to view reports');
            }
            $response['redirect'] = 'report.php';
            break;
            
        case 'view_products':
            // Check if user has permission to view products
            if (!hasPermission('viewProduct')) {
                throw new Exception('You do not have permission to view products');
            }
            $response['redirect'] = 'product.php';
            break;
            
        case 'view_orders':
            // Check if user has permission to view orders
            if (!hasPermission('viewOrder')) {
                throw new Exception('You do not have permission to view orders');
            }
            $response['redirect'] = 'orders.php';
            break;
            
        case 'view_categories':
            // Check if user has permission to view categories
            if (!hasPermission('viewCategories')) {
                throw new Exception('You do not have permission to view categories');
            }
            $response['redirect'] = 'categories.php';
            break;
            
        case 'view_brands':
            // Check if user has permission to view brands
            if (!hasPermission('viewBrands')) {
                throw new Exception('You do not have permission to view brands');
            }
            $response['redirect'] = 'brands.php';
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'message' => $e->getMessage()
    ));
}

// Helper function to check user permissions
function hasPermission($permission) {
    // Get user's role from session
    $userRole = isset($_SESSION['userRole']) ? $_SESSION['userRole'] : null;
    
    // Define permission matrix
    $permissions = array(
        'admin' => array(
            'createProduct', 'editProduct', 'viewProduct', 'deleteProduct',
            'createOrder', 'editOrder', 'viewOrder', 'deleteOrder',
            'createCategory', 'editCategory', 'viewCategories', 'deleteCategory',
            'createBrand', 'editBrand', 'viewBrands', 'deleteBrand',
            'manageUsers', 'viewReports'
        ),
        'manager' => array(
            'createProduct', 'editProduct', 'viewProduct',
            'createOrder', 'editOrder', 'viewOrder',
            'createCategory', 'viewCategories',
            'createBrand', 'viewBrands',
            'viewReports'
        ),
        'user' => array(
            'viewProduct',
            'createOrder', 'viewOrder',
            'viewCategories',
            'viewBrands'
        )
    );
    
    // Check if user has the required permission
    if ($userRole && isset($permissions[$userRole])) {
        return in_array($permission, $permissions[$userRole]);
    }
    
    return false;
}
?> 