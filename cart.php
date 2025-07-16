<?php
require_once 'config.php';
requireLogin();

// Redirect if not customer
if (getUserType() != 1) {
    header("Location: index.php");
    exit();
}

$message = "";

// Handle cart actions using functions
if (isset($_POST['remove_item'])) {
    $message = removeFromCart((int)$_POST['order_pool_id'], $conn);
}

if (isset($_POST['update_quantity'])) {
    $message = updateCartQuantity((int)$_POST['order_pool_id'], (int)$_POST['quantity'], $conn);
}

if (isset($_POST['checkout'])) {
    $user_id = $_SESSION['user_id'];
    $message = checkoutCart($user_id, $conn);
    if (strpos($message, 'successfully') !== false) {
        header("refresh:2;url=customer_dashboard.php");
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
            WHEN p.product_type_id = 1 THEN at.animal 
            WHEN p.product_type_id = 2 THEN ct.crop 
            ELSE 'Unknown' 
        END as product_name,
        CASE 
            WHEN p.product_type_id = 1 THEN a.price 
            WHEN p.product_type_id = 2 THEN c.price 
            ELSE 0 
        END as unit_price,
        CASE 
            WHEN p.product_type_id = 1 THEN a.weight 
            WHEN p.product_type_id = 2 THEN c.weight 
            ELSE 0 
        END as weight,
        CASE 
            WHEN p.product_type_id = 1 THEN 'Animal' 
            WHEN p.product_type_id = 2 THEN 'Crop' 
            ELSE 'Unknown' 
        END as category
    FROM order_pool op
    JOIN orders o ON op.OrderID = o.Order_ID
    JOIN product p ON op.ProductID = p.product_id
    LEFT JOIN animal a ON p.product_id = a.animal_id AND p.product_type_id = 1
    LEFT JOIN animal_type at ON a.animal_type_id = at.animal_type_id
    LEFT JOIN crop c ON p.product_id = c.crop_id AND p.product_type_id = 2
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
    <link rel="stylesheet" href="css/cart.css">
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
                