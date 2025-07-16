<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration for XAMPP
$host = "localhost";
$username = "root";          // Default XAMPP MySQL username
$password = "";              // Default XAMPP MySQL password (empty)
$database = "rosellofarms";  // Your database name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "<br><br>Please check:<br>1. MySQL is running in XAMPP<br>2. Database 'rosellofarms' exists<br>3. Database credentials are correct");
}

// Set charset
$conn->set_charset("utf8mb4");

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to authenticate user
function authenticateUser($email, $password, $conn) {
    $sql = "SELECT u.user_id, u.email, u.password, u.full_name, u.user_type_id, u.account_status, ut.user_type 
            FROM user u 
            JOIN user_type ut ON u.user_type_id = ut.user_type_id 
            WHERE u.email = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        if ($user['account_status'] == 1) {
            return ['success' => false, 'message' => 'Account is deactivated. Please contact administrator.'];
        } else if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_type_id'] = $user['user_type_id'];
            $_SESSION['user_type'] = $user['user_type'];
            
            return ['success' => true, 'user' => $user];
        } else {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }
    } else {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }
}

// Function to redirect based on user type
function redirectUserToDashboard($user_type_id) {
    switch($user_type_id) {
        case 3: // Admin
            header("Location: admin_dashboard.php");
            break;
        case 2: // Employee
            header("Location: employee_dashboard.php");
            break;
        default:
            header("Location: customer_dashboard.php");
            break;
    }
    exit();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check user type
function getUserType() {
    return isset($_SESSION['user_type_id']) ? $_SESSION['user_type_id'] : null;
}

// Function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
}

// Function to redirect if not admin
function requireAdmin() {
    requireLogin();
    if (getUserType() != 3) { // Admin user type
        header("Location: customer_dashboard.php");
        exit();
    }
}

// Function to redirect if not employee
function requireEmployee() {
    requireLogin();
    if (getUserType() != 2) { // Employee user type
        header("Location: customer_dashboard.php");
        exit();
    }
}

// Function to handle user approval
function approveUser($user_id, $conn) {
    $approve_user = "UPDATE user SET account_status = 0 WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $approve_user);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        return "User approved successfully!";
    } else {
        return "Error approving user.";
    }
}

// Function to handle user rejection
function rejectUser($user_id, $conn) {
    mysqli_begin_transaction($conn);
    
    try {
        // Get user type to determine which table to clean up
        $get_user = "SELECT user_type_id FROM user WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $get_user);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user_data = mysqli_fetch_assoc($result);
        
        // Delete from personnel table if employee
        if ($user_data['user_type_id'] == 2) {
            $delete_personnel = "DELETE FROM personnel WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $delete_personnel);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
        }
        
        // Delete user
        $delete_user = "DELETE FROM user WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $delete_user);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($conn);
        return "User request rejected and removed.";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return "Error rejecting user.";
    }
}

// Function to update user status
function updateUserStatus($user_id, $new_status, $conn) {
    $update_status = "UPDATE user SET account_status = ? WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $update_status);
    mysqli_stmt_bind_param($stmt, "ii", $new_status, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        return "User status updated successfully!";
    } else {
        return "Error updating user status.";
    }
}

// Function to confirm order
function confirmOrder($order_id, $conn) {
    $confirm_query = "UPDATE orders SET Order_Status = 'confirmed' WHERE Order_ID = ?";
    $stmt = mysqli_prepare($conn, $confirm_query);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        return "Order confirmed successfully!";
    } else {
        return "Error confirming order.";
    }
}

// Function to add product
function addProduct($product_type, $weight, $price, $type_id, $conn) {
    mysqli_begin_transaction($conn);
    
    try {
        if ($product_type == 1) { // Animal
            // Insert into animal table
            $insert_animal = "INSERT INTO animal (weight, price, animal_type_id) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_animal);
            mysqli_stmt_bind_param($stmt, "ddi", $weight, $price, $type_id);
            mysqli_stmt_execute($stmt);
            $animal_id = mysqli_insert_id($conn);
            
            // Insert into product table
            $insert_product = "INSERT INTO product (product_id, product_type_id) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $insert_product);
            mysqli_stmt_bind_param($stmt, "ii", $animal_id, $product_type);
            mysqli_stmt_execute($stmt);
            
        } else if ($product_type == 2) { // Crop
            // Insert into crop table
            $insert_crop = "INSERT INTO crop (weight, price, crop_type_id) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_crop);
            mysqli_stmt_bind_param($stmt, "ddi", $weight, $price, $type_id);
            mysqli_stmt_execute($stmt);
            $crop_id = mysqli_insert_id($conn);
            
            // Insert into product table
            $insert_product = "INSERT INTO product (product_id, product_type_id) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $insert_product);
            mysqli_stmt_bind_param($stmt, "ii", $crop_id, $product_type);
            mysqli_stmt_execute($stmt);
        }
        
        mysqli_commit($conn);
        return "Product added successfully!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return "Error adding product.";
    }
}

// Function to delete product
function deleteProduct($product_id, $product_type, $conn) {
    mysqli_begin_transaction($conn);
    
    try {
        // Delete from product table
        $delete_product = "DELETE FROM product WHERE product_id = ? AND product_type_id = ?";
        $stmt = mysqli_prepare($conn, $delete_product);
        mysqli_stmt_bind_param($stmt, "ii", $product_id, $product_type);
        mysqli_stmt_execute($stmt);
        
        // Delete from animal or crop table
        if ($product_type == 1) {
            $delete_animal = "DELETE FROM animal WHERE animal_id = ?";
            $stmt = mysqli_prepare($conn, $delete_animal);
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            mysqli_stmt_execute($stmt);
        } else if ($product_type == 2) {
            $delete_crop = "DELETE FROM crop WHERE crop_id = ?";
            $stmt = mysqli_prepare($conn, $delete_crop);
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            mysqli_stmt_execute($stmt);
        }
        
        mysqli_commit($conn);
        return "Product deleted successfully!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return "Error deleting product.";
    }
}

// Function to add item to cart
function addToCart($user_id, $product_id, $quantity, $conn) {
    if ($quantity <= 0) {
        return "Invalid quantity.";
    }
    
    mysqli_begin_transaction($conn);
    
    try {
        // Create order if not exists or get existing pending order
        $check_order = "SELECT Order_ID FROM orders WHERE User_ID = ? AND Order_Status = 'pending' ORDER BY Order_ID DESC LIMIT 1";
        $stmt = mysqli_prepare($conn, $check_order);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($order = mysqli_fetch_assoc($result)) {
            $order_id = $order['Order_ID'];
        } else {
            // Create new order
            $insert_order = "INSERT INTO orders (User_ID, Order_Date_Time, Order_Status) VALUES (?, CURDATE(), 'pending')";
            $stmt = mysqli_prepare($conn, $insert_order);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $order_id = mysqli_insert_id($conn);
        }
        
        // Get product price
        $get_price = "SELECT 
                        CASE 
                            WHEN p.product_type_id = 1 THEN a.price 
                            WHEN p.product_type_id = 2 THEN c.price 
                            ELSE 0 
                        END as price
                      FROM product p
                      LEFT JOIN animal a ON p.product_id = a.animal_id AND p.product_type_id = 1
                      LEFT JOIN crop c ON p.product_id = c.crop_id AND p.product_type_id = 2
                      WHERE p.product_id = ?";
        $stmt = mysqli_prepare($conn, $get_price);
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        mysqli_stmt_execute($stmt);
        $price_result = mysqli_stmt_get_result($stmt);
        $price_data = mysqli_fetch_assoc($price_result);
        $subtotal = $price_data['price'] * $quantity;
        
        // Check if product already in cart
        $check_cart = "SELECT OrderPoolID, Quantity FROM order_pool WHERE OrderID = ? AND ProductID = ?";
        $stmt = mysqli_prepare($conn, $check_cart);
        mysqli_stmt_bind_param($stmt, "ii", $order_id, $product_id);
        mysqli_stmt_execute($stmt);
        $cart_result = mysqli_stmt_get_result($stmt);
        
        if ($cart_item = mysqli_fetch_assoc($cart_result)) {
            // Update existing cart item
            $new_quantity = $cart_item['Quantity'] + $quantity;
            $new_subtotal = $price_data['price'] * $new_quantity;
            $update_cart = "UPDATE order_pool SET Quantity = ?, Subtotal = ? WHERE OrderPoolID = ?";
            $stmt = mysqli_prepare($conn, $update_cart);
            mysqli_stmt_bind_param($stmt, "iii", $new_quantity, $new_subtotal, $cart_item['OrderPoolID']);
            mysqli_stmt_execute($stmt);
        } else {
            // Add new cart item
            $add_to_cart = "INSERT INTO order_pool (OrderID, ProductID, Quantity, Subtotal) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $add_to_cart);
            mysqli_stmt_bind_param($stmt, "iiii", $order_id, $product_id, $quantity, $subtotal);
            mysqli_stmt_execute($stmt);
        }
        
        mysqli_commit($conn);
        return "Item added to cart successfully!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return "Error adding item to cart.";
    }
}

// Function to remove item from cart
function removeFromCart($order_pool_id, $conn) {
    mysqli_begin_transaction($conn);
    
    try {
        // Get order ID before removing item
        $get_order = "SELECT OrderID FROM order_pool WHERE OrderPoolID = ?";
        $stmt = mysqli_prepare($conn, $get_order);
        mysqli_stmt_bind_param($stmt, "i", $order_pool_id);
        mysqli_stmt_execute($stmt);
        $order_result = mysqli_stmt_get_result($stmt);
        $order_data = mysqli_fetch_assoc($order_result);
        $order_id = $order_data['OrderID'];
        
        // Remove item from cart
        $remove_query = "DELETE FROM order_pool WHERE OrderPoolID = ?";
        $stmt = mysqli_prepare($conn, $remove_query);
        mysqli_stmt_bind_param($stmt, "i", $order_pool_id);
        mysqli_stmt_execute($stmt);
        
        // Check if order has any remaining items
        $check_items = "SELECT COUNT(*) as item_count FROM order_pool WHERE OrderID = ?";
        $stmt = mysqli_prepare($conn, $check_items);
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);
        $count_result = mysqli_stmt_get_result($stmt);
        $count_data = mysqli_fetch_assoc($count_result);
        
        // If no items left, remove the order
        if ($count_data['item_count'] == 0) {
            $remove_order = "DELETE FROM orders WHERE Order_ID = ? AND Order_Status = 'pending'";
            $stmt = mysqli_prepare($conn, $remove_order);
            mysqli_stmt_bind_param($stmt, "i", $order_id);
            mysqli_stmt_execute($stmt);
        }
        
        mysqli_commit($conn);
        return "Item removed from cart successfully!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return "Error removing item from cart.";
    }
}

// Function to update cart quantity
function updateCartQuantity($order_pool_id, $new_quantity, $conn) {
    if ($new_quantity <= 0) {
        return "Invalid quantity.";
    }
    
    // Get product price for recalculating subtotal
    $get_price = "SELECT 
                    CASE 
                        WHEN p.product_type_id = 1 THEN a.price 
                        WHEN p.product_type_id = 2 THEN c.price 
                        ELSE 0 
                    END as price
                  FROM order_pool op
                  JOIN product p ON op.ProductID = p.product_id
                  LEFT JOIN animal a ON p.product_id = a.animal_id AND p.product_type_id = 1
                  LEFT JOIN crop c ON p.product_id = c.crop_id AND p.product_type_id = 2
                  WHERE op.OrderPoolID = ?";
    $stmt = mysqli_prepare($conn, $get_price);
    mysqli_stmt_bind_param($stmt, "i", $order_pool_id);
    mysqli_stmt_execute($stmt);
    $price_result = mysqli_stmt_get_result($stmt);
    $price_data = mysqli_fetch_assoc($price_result);
    
    $new_subtotal = $price_data['price'] * $new_quantity;
    
    $update_query = "UPDATE order_pool SET Quantity = ?, Subtotal = ? WHERE OrderPoolID = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "iii", $new_quantity, $new_subtotal, $order_pool_id);
    
    if (mysqli_stmt_execute($stmt)) {
        return "Quantity updated successfully!";
    } else {
        return "Error updating quantity.";
    }
}

// Function to checkout cart
function checkoutCart($user_id, $conn) {
    $checkout_query = "UPDATE orders SET Order_Status = 'processing' 
                       WHERE User_ID = ? AND Order_Status = 'pending'";
    $stmt = mysqli_prepare($conn, $checkout_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        return "Order placed successfully! Thank you for your purchase.";
    } else {
        return "Error processing checkout.";
    }
}
?>