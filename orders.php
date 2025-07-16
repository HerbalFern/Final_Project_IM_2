<?php
require_once 'config.php';
requireLogin();

// Redirect if not customer
if (getUserType() != 1) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get customer's orders
$orders_query = "
    SELECT 
        o.Order_ID,
        o.Order_Date_Time,
        o.Order_Status,
        COUNT(op.OrderPoolID) as item_count,
        SUM(op.Subtotal) as total_amount
    FROM orders o
    LEFT JOIN order_pool op ON o.Order_ID = op.OrderID
    WHERE o.User_ID = ? AND o.Order_Status != 'pending'
    GROUP BY o.Order_ID, o.Order_Date_Time, o.Order_Status
    ORDER BY o.Order_Date_Time DESC
";

$stmt = mysqli_prepare($conn, $orders_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);

// Get order details for a specific order if requested
$order_details = [];
if (isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    
    $details_query = "
        SELECT 
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
        WHERE o.Order_ID = ? AND o.User_ID = ?
        ORDER BY op.OrderPoolID
    ";
    
    $stmt = mysqli_prepare($conn, $details_query);
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
    mysqli_stmt_execute($stmt);
    $details_result = mysqli_stmt_get_result($stmt);
    
    while ($detail = mysqli_fetch_assoc($details_result)) {
        $order_details[] = $detail;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Rosello Farms</title>
    <link rel="stylesheet" href="css/orders.css">
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
            <h1>My Orders</h1>
            <p>Track your order history and status</p>
        </section>

        <section class="orders-section">
            <?php if (isset($_GET['order_id']) && !empty($order_details)): ?>
                <a href="orders.php" class="back-btn">← Back to Orders</a>
                
                <div class="order-details">
                    <h2 class="details-title">Order #<?php echo (int)$_GET['order_id']; ?> - Items</h2>
                    
                    <?php foreach ($order_details as $detail): ?>
                        <div class="detail-item">
                            <div class="product-info">
                                <h4><?php echo htmlspecialchars($detail['product_name']); ?></h4>
                                <span class="product-category"><?php echo htmlspecialchars($detail['category']); ?></span>
                            </div>
                            <div class="quantity-price">
                                <div class="quantity">Qty: <?php echo $detail['Quantity']; ?> × ₱<?php echo number_format($detail['unit_price'], 2); ?></div>
                                <div class="subtotal">₱<?php echo number_format($detail['Subtotal'], 2); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
            <?php else: ?>
                <?php if (mysqli_num_rows($orders_result) > 0): ?>
                    <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-id">Order #<?php echo $order['Order_ID']; ?></div>
                                <div class="order-status status-<?php echo $order['Order_Status']; ?>">
                                    <?php echo ucfirst($order['Order_Status']); ?>
                                </div>
                            </div>
                            
                            <div class="order-info">
                                <div class="info-item">
                                    <div class="info-label">Order Date</div>
                                    <div class="info-value"><?php echo date('M j, Y', strtotime($order['Order_Date_Time'])); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Items</div>
                                    <div class="info-value"><?php echo $order['item_count']; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Total Amount</div>
                                    <div class="info-value">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                                </div>
                                <div class="info-item">
                                    <a href="orders.php?order_id=<?php echo $order['Order_ID']; ?>" class="view-details-btn">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-orders">
                        <h2>No orders found</h2>
                        <p>You haven't placed any orders yet.</p>
                        <br>
                        <a href="customer_dashboard.php" class="view-details-btn">Start Shopping</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>