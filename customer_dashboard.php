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
            header("Location: index.php");
            break;
    }
    exit();
}

$message = "";

// Handle add to cart using function
if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $user_id = $_SESSION['user_id'];
    
    $message = addToCart($user_id, $product_id, $quantity, $conn);
}

// Get available products (animals and crops)
$products_query = "
    SELECT 
        p.product_id,
        p.product_type_id,
        CASE 
            WHEN p.product_type_id = 1 THEN at.animal 
            WHEN p.product_type_id = 2 THEN ct.crop 
            ELSE 'Unknown' 
        END as product_name,
        CASE 
            WHEN p.product_type_id = 1 THEN a.weight 
            WHEN p.product_type_id = 2 THEN c.weight 
            ELSE 0 
        END as weight,
        CASE 
            WHEN p.product_type_id = 1 THEN a.price 
            WHEN p.product_type_id = 2 THEN c.price 
            ELSE 0 
        END as price,
        CASE 
            WHEN p.product_type_id = 1 THEN 'Animal' 
            WHEN p.product_type_id = 2 THEN 'Crop' 
            ELSE 'Unknown' 
        END as category
    FROM product p
    LEFT JOIN animal a ON p.product_id = a.animal_id AND p.product_type_id = 1
    LEFT JOIN animal_type at ON a.animal_type_id = at.animal_type_id
    LEFT JOIN crop c ON p.product_id = c.crop_id AND p.product_type_id = 2
    LEFT JOIN crop_type ct ON c.crop_type_id = ct.crop_type_id
    WHERE (p.product_type_id = 1 AND a.animal_id IS NOT NULL) 
       OR (p.product_type_id = 2 AND c.crop_id IS NOT NULL)
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
    <link rel="stylesheet" href="css/customer_dashboard.css">
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