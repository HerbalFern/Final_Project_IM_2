<?php
require_once 'config.php';
requireLogin();

// Redirect if not customer
if (getUserType() != 1) {
    header("Location: login.php");
    exit();
}

$message = "";

// Handle remove from cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_item'])) {
    $order_pool_id = (int)$_POST['order_pool_id'];
    
    $remove_query = "DELETE FROM order_pool WHERE OrderPoolID = ?";
    $stmt = mysqli_prepare($conn, $remove_query);
    mysqli_stmt_bind_param($stmt, "i", $order_pool_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = "Item removed from cart successfully!";
    } else {
        $message = "Error removing item from cart.";
    }
}

// Handle update quantity
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_quantity'])) {
    $order_pool_id = (int)$_POST['order_pool_id'];
    $new_quantity = (int)$_POST['quantity'];
    
    if ($new_quantity > 0) {
        // Get product price for recalculating subtotal
        $get_price = "SELECT 
                        CASE 
                            WHEN p.product_type = 1 THEN a.price 
                            WHEN p.product_type = 2 THEN c.price 
                            ELSE 0 
                        END as price
                      FROM order_pool op
                      JOIN product p ON op.ProductID = p.product_id
                      LEFT JOIN animal a ON p.product_id = a.animal_id AND p.product_type = 1
                      LEFT JOIN crop c ON p.product_id = c.crop_id AND p.product_type = 2
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
            $message = "Quantity updated successfully!";
        } else {
            $message = "Error updating quantity.";
        }
    }
}

// Handle checkout
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['checkout'])) {
    $user_id = $_SESSION['user_id'];
    
    // Update order status to 'processing'
    $checkout_query = "UPDATE orders SET Order_Status = 'processing' 
                       WHERE User_ID = ? AND Order_Status = 'pending'";
    $stmt = mysqli_prepare($conn, $checkout_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = "Order placed successfully! Thank you for your purchase.";
        header("refresh:2;url=customer_dashboard.php");
    } else {
        $message = "Error processing checkout.";
    }
}

// Get cart items
$user_id = $_SESSION['user_id'];
$cart_query = "
    SELECT 
        op.OrderPoolID,
        op.Quantity,
        op.Subtotal,
        CASE 
            WHEN p.product_type = 1 THEN at.animal 
            WHEN p.product_type = 2 THEN ct.crop 
            ELSE 'Unknown' 
        END as product_name,
        CASE 
            WHEN p.product_type = 1 THEN a.price 
            WHEN p.product_type = 2 THEN c.price 
            ELSE 0 
        END as unit_price,
        CASE 
            WHEN p.product_type = 1 THEN a.weight 
            WHEN p.product_type = 2 THEN c.weight 
            ELSE 0 
        END as weight,
        CASE 
            WHEN p.product_type = 1 THEN 'Animal' 
            WHEN p.product_type = 2 THEN 'Crop' 
            ELSE 'Unknown' 
        END as category
    FROM order_pool op
    JOIN orders o ON op.OrderID = o.Order_ID
    JOIN product p ON op.ProductID = p.product_id
    LEFT JOIN animal a ON p.product_id = a.animal_id AND p.product_type = 1
    LEFT JOIN animal_type at ON a.animal_type_id = at.animal_type_id
    LEFT JOIN crop c ON p.product_id = c.crop_id AND p.product_type = 2
    LEFT JOIN crop_type ct ON c.crop_type_id = ct.crop_type_id
    WHERE o.User_ID = ? AND o.Order_Status = 'pending'
    ORDER BY op.OrderPoolID
";

$stmt = mysqli_prepare($conn, $cart_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$cart_result = mysqli_stmt_get_result($stmt);

// Calculate total
$total_amount = 0;
$cart_items = [];
while ($item = mysqli_fetch_assoc($cart_result)) {
    $cart_items[] = $item;
    $total_amount += $item['Subtotal'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Rosello Farms</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #2d5a3d, #1e3d2a);
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
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
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

        .page-title {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .page-title h1 {
            color: #2d5a3d;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .page-title p {
            color: #666;
            font-size: 1.1rem;
        }

        .cart-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .cart-item {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 1rem;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
            transition: background 0.3s ease;
        }

        .cart-item:hover {
            background: #f8f9fa;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-header {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 1rem;
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-weight: bold;
            color: #2d5a3d;
        }

        .product-info h3 {
            color: #2d5a3d;
            margin-bottom: 0.5rem;
        }

        .product-details {
            color: #666;
            font-size: 0.9rem;
        }

        .product-category {
            background: #2d5a3d;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 15px;
            font-size: 0.8rem;
            display: inline-block;
            margin-top: 0.5rem;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quantity-input {
            width: 60px;
            padding: 0.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            text-align: center;
            font-size: 1rem;
        }

        .quantity-btn {
            background: #2d5a3d;
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.3s ease;
        }

        .quantity-btn:hover {
            background: #1e3d2a;
        }

        .price {
            font-weight: bold;
            color: #2d5a3d;
            font-size: 1.1rem;
        }

        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .remove-btn:hover {
            background: #c82333;
        }

        .cart-summary {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
            margin-top: 2rem;
            text-align: center;
        }

        .total-amount {
            font-size: 2rem;
            font-weight: bold;
            color: #2d5a3d;
            margin-bottom: 1rem;
        }

        .checkout-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 1rem 3rem;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 1rem;
        }

        .checkout-btn:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
        }

        .continue-shopping-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .continue-shopping-btn:hover {
            background: #5a6268;
        }

        .empty-cart {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-cart h2 {
            color: #2d5a3d;
            margin-bottom: 1rem;
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

            .cart-header,
            .cart-item {
                grid-template-columns: 1fr;
                gap: 0.5rem;
                text-align: center;
            }

            .cart-header {
                display: none;
            }

            .cart-item {
                border: 1px solid #e0e0e0;
                border-radius: 10px;
                margin-bottom: 1rem;
            }

            .quantity-controls {
                justify-content: center;
            }

            .page-title h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">ROSELLO FARMS</div>
        <nav class="nav-links">
            <a href="customer_dashboard.php">Dashboard</a>
            <a href="cart.php">My Cart</a>
            <a href="orders.php">My Orders</a>
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <div class="container">
        <section class="page-title">
            <h1>Shopping Cart</h1>
            <p>Review your items before checkout</p>
        </section>

        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <section class="cart-section">
            <?php if (!empty($cart_items)): ?>
                <div class="cart-header">
                    <div>Product</div>
                    <div>Unit Price</div>
                    <div>Quantity</div>
                    <div>Subtotal</div>
                    <div>Action</div>
                </div>

                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($item['product_name']); ?></h3>
                            <div class="product-details">
                                Weight: <?php echo number_format($item['weight'], 2); ?> kg
                            </div>
                            <span class="product-category"><?php echo htmlspecialchars($item['category']); ?></span>
                        </div>
                        
                        <div class="price">
                            ₱<?php echo number_format($item['unit_price'], 2); ?>
                        </div>
                        
                        <div class="quantity-controls">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="order_pool_id" value="<?php echo $item['OrderPoolID']; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['Quantity']; ?>" 
                                       min="1" class="quantity-input" required>
                                <button type="submit" name="update_quantity" class="quantity-btn">Update</button>
                            </form>
                        </div>
                        
                        <div class="price">
                            ₱<?php echo number_format($item['Subtotal'], 2); ?>
                        </div>
                        
                        <div>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="order_pool_id" value="<?php echo $item['OrderPoolID']; ?>">
                                <button type="submit" name="remove_item" class="remove-btn" 
                                        onclick="return confirm('Are you sure you want to remove this item?')">
                                    Remove
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="cart-summary">
                    <div class="total-amount">
                        Total: ₱<?php echo number_format($total_amount, 2); ?>
                    </div>
                    <div>
                        <a href="customer_dashboard.php" class="continue-shopping-btn">Continue Shopping</a>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="checkout" class="checkout-btn" 
                                    onclick="return confirm('Are you sure you want to place this order?')">
                                Proceed to Checkout
                            </button>
                        </form>
                    </div>
                </div>

            <?php else: ?>
                <div class="empty-cart">
                    <h2>Your cart is empty</h2>
                    <p>Add some products to your cart to get started.</p>
                    <br>
                    <a href="customer_dashboard.php" class="continue-shopping-btn">Start Shopping</a>
                </div>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>