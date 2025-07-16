<?php
require_once 'config.php';
requireLogin();

// Redirect if not customer
if (getUserType() != 1) {
    header("Location: login.php");
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

        .orders-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .order-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .order-card:hover {
            border-color: #2d5a3d;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(45, 90, 61, 0.15);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .order-id {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2d5a3d;
        }

        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .status-processing {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .info-item {
            text-align: center;
        }

        .info-label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }

        .info-value {
            font-weight: bold;
            color: #2d5a3d;
            font-size: 1.1rem;
        }

        .view-details-btn {
            background: #2d5a3d;
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .view-details-btn:hover {
            background: #1e3d2a;
        }

        .order-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1rem;
        }

        .details-title {
            color: #2d5a3d;
            font-size: 1.3rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #2d5a3d;
            padding-bottom: 0.5rem;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .product-info h4 {
            color: #2d5a3d;
            margin-bottom: 0.3rem;
        }

        .product-category {
            background: #2d5a3d;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 15px;
            font-size: 0.8rem;
            display: inline-block;
        }

        .quantity-price {
            text-align: right;
        }

        .quantity {
            color: #666;
            margin-bottom: 0.3rem;
        }

        .subtotal {
            font-weight: bold;
            color: #2d5a3d;
            font-size: 1.1rem;
        }

        .no-orders {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-orders h2 {
            color: #2d5a3d;
            margin-bottom: 1rem;
        }

        .back-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .back-btn:hover {
            background: #5a6268;
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

            .order-header {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }

            .order-info {
                grid-template-columns: 1fr 1fr;
            }

            .detail-item {
                flex-direction: column;
                align-items: stretch;
                gap: 0.5rem;
            }

            .quantity-price {
                text-align: left;
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