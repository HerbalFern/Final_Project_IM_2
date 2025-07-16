<?php
require_once 'config.php';
requireLogin();

// Redirect if not customer
if (getUserType() != 1) {
    switch(getUserType()) {
        case 3:
            header("Location: admin_dashboard.php");
            break;
        case 2:
            header("Location: employee_dashboard.php");
            break;
        default:
            header("Location: login.php");
            break;
    }
    exit();
}

$message = "";

// Handle add to cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity > 0) {
        // Start transaction for order creation
        mysqli_begin_transaction($conn);
        
        try {
            // Create order if not exists or get existing pending order
            $user_id = $_SESSION['user_id'];
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
            
            // Get product price for subtotal calculation
            $get_price = "SELECT 
                            CASE 
                                WHEN p.product_type = 1 THEN a.price 
                                WHEN p.product_type = 2 THEN c.price 
                                ELSE 0 
                            END as price
                          FROM product p
                          LEFT JOIN animal a ON p.product_id = a.animal_id AND p.product_type = 1
                          LEFT JOIN crop c ON p.product_id = c.crop_id AND p.product_type = 2
                          WHERE p.product_id = ?";
            $stmt = mysqli_prepare($conn, $get_price);
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            mysqli_stmt_execute($stmt);
            $price_result = mysqli_stmt_get_result($stmt);
            $price_data = mysqli_fetch_assoc($price_result);
            $subtotal = $price_data['price'] * $quantity;
            
            // Check if product already in cart (order_pool)
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
            $message = "Item added to cart successfully!";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = "Error adding item to cart.";
        }
    }
}

// Get available products (animals and crops)
$products_query = "
    SELECT 
        p.product_id,
        p.product_type,
        CASE 
            WHEN p.product_type = 1 THEN at.animal 
            WHEN p.product_type = 2 THEN ct.crop 
            ELSE 'Unknown' 
        END as product_name,
        CASE 
            WHEN p.product_type = 1 THEN a.weight 
            WHEN p.product_type = 2 THEN c.weight 
            ELSE 0 
        END as weight,
        CASE 
            WHEN p.product_type = 1 THEN a.price 
            WHEN p.product_type = 2 THEN c.price 
            ELSE 0 
        END as price,
        CASE 
            WHEN p.product_type = 1 THEN 'Animal' 
            WHEN p.product_type = 2 THEN 'Crop' 
            ELSE 'Unknown' 
        END as category
    FROM product p
    LEFT JOIN animal a ON p.product_id = a.animal_id AND p.product_type = 1
    LEFT JOIN animal_type at ON a.animal_type_id = at.animal_type_id
    LEFT JOIN crop c ON p.product_id = c.crop_id AND p.product_type = 2
    LEFT JOIN crop_type ct ON c.crop_type_id = ct.crop_type_id
    WHERE (p.product_type = 1 AND a.animal_id IS NOT NULL) 
       OR (p.product_type = 2 AND c.crop_id IS NOT NULL)
    ORDER BY category, product_name
";

$products_result = mysqli_query($conn, $products_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Rosello Farms</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            /* Background image with gradient overlay */
            background: 
                linear-gradient(135deg, rgba(45, 90, 61, 0.6), rgba(30, 61, 42, 0.6)),
                url('images/dashboard-background.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            color: #333;
        }

        .header {
            background: rgba(45, 90, 61, 0.95);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            backdrop-filter: blur(10px);
        }

        .logo {
            display: flex;
            align-items: center;
            min-height: 60px;
        }

        .header-logo-img {
            max-width: 200px;
            max-height: 60px;
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .header-logo-placeholder {
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
            text-align: center;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s ease;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .welcome-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .welcome-title {
            color: #2d5a3d;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            color: #666;
            font-size: 1.1rem;
        }

        .products-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            color: #2d5a3d;
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .product-card {
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            background: rgba(249, 249, 249, 0.95);
            backdrop-filter: blur(5px);
        }

        .product-card:hover {
            border-color: #2d5a3d;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(45, 90, 61, 0.15);
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-size: 1.3rem;
            font-weight: bold;
            color: #2d5a3d;
            margin-bottom: 0.5rem;
        }

        .product-category {
            background: #2d5a3d;
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-block;
        }

        .product-icon {
            margin-left: 1rem;
            flex-shrink: 0;
        }

        .product-icon-img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: 8px;
        }

        .product-icon-placeholder {
            width: 50px;
            height: 50px;
            background: #f0f0f0;
            border: 2px dashed #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 1.5rem;
            text-align: center;
            border-radius: 8px;
        }

        .product-name {
            font-size: 1.3rem;
            font-weight: bold;
            color: #2d5a3d;
        }

        .product-category {
            background: #2d5a3d;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .product-details {
            margin-bottom: 1rem;
        }

        .product-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            color: #666;
        }

        .product-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2d5a3d;
            text-align: center;
            margin-bottom: 1rem;
        }

        .add-to-cart-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .quantity-input {
            width: 60px;
            padding: 0.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            text-align: center;
        }

        .add-to-cart-btn {
            flex: 1;
            background: #2d5a3d;
            color: white;
            border: none;
            padding: 0.7rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .add-to-cart-btn:hover {
            background: #1e3d2a;
        }

        .message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #c3e6cb;
            text-align: center;
        }

        .no-products {
            text-align: center;
            color: #666;
            font-size: 1.1rem;
            padding: 2rem;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .container {
                padding: 0 1rem;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">
            <?php
            // Check if header logo exists, show logo or placeholder
            $header_logo_path = 'images/header-logo.png';
            if (file_exists($header_logo_path)) {
                echo '<img src="images/header-logo.png" alt="Rosello Farms" class="header-logo-img">';
            } else {
                echo '<div class="header-logo-placeholder">ROSELLO FARMS</div>';
            }
            ?>
        </div>
        <nav class="nav-links">
            <a href="customer_dashboard.php">Dashboard</a>
            <a href="cart.php">My Cart</a>
            <a href="orders.php">My Orders</a>
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <div class="container">
        <section class="welcome-section">
            <h1 class="welcome-title">Welcome to Rosello Farms</h1>
            <p class="welcome-subtitle">Browse our fresh livestock and crops available for purchase</p>
        </section>

        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <section class="products-section">
            <h2 class="section-title">Available Products</h2>
            
            <?php if (mysqli_num_rows($products_result) > 0): ?>
                <div class="products-grid">
                    <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                        <div class="product-card">
                            <div class="product-header">
                                <div class="product-info">
                                    <h3 class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                    <span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span>
                                </div>
                                
                                <div class="product-icon">
                                    <!-- Product Icon Image -->
                                    <img src="images/<?php echo $product['category'] == 'Animal' ? 'animal-icon.png' : 'crop-icon.png'; ?>" 
                                         alt="<?php echo htmlspecialchars($product['category']); ?>" 
                                         class="product-icon-img"
                                         onload="this.style.display='block'; this.nextElementSibling.style.display='none';"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                         style="display: none;">
                                    
                                    <!-- Placeholder if image not found -->
                                    <div class="product-icon-placeholder">
                                        <?php echo $product['category'] == 'Animal' ? '🐄' : '🌾'; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="product-details">
                                <div class="product-detail">
                                    <span>Weight:</span>
                                    <span><?php echo number_format($product['weight'], 2); ?> kg</span>
                                </div>
                            </div>
                            
                            <div class="product-price">
                                ₱<?php echo number_format($product['price'], 2); ?>
                            </div>
                            
                            <form method="POST" class="add-to-cart-form">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                <input type="number" name="quantity" value="1" min="1" class="quantity-input" required>
                                <button type="submit" name="add_to_cart" class="add-to-cart-btn">Add to Cart</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-products">
                    <p>No products available at the moment. Please check back later.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>