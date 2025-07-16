<?php
require_once 'config.php';
requireAdmin();

$message = "";

// Handle form submissions using functions
if (isset($_POST['approve_user'])) {
    $message = approveUser((int)$_POST['user_id'], $conn);
}

if (isset($_POST['reject_user'])) {
    $message = rejectUser((int)$_POST['user_id'], $conn);
}

if (isset($_POST['update_user_status'])) {
    $message = updateUserStatus((int)$_POST['user_id'], (int)$_POST['account_status'], $conn);
}

if (isset($_POST['confirm_order'])) {
    $message = confirmOrder((int)$_POST['order_id'], $conn);
}

if (isset($_POST['add_product'])) {
    $product_type = (int)$_POST['product_type'];
    $weight = (float)$_POST['weight'];
    $price = (float)$_POST['price'];
    $type_id = $product_type == 1 ? (int)$_POST['animal_type_id'] : (int)$_POST['crop_type_id'];
    
    $message = addProduct($product_type, $weight, $price, $type_id, $conn);
}

if (isset($_POST['delete_product'])) {
    $message = deleteProduct((int)$_POST['product_id'], (int)$_POST['product_type'], $conn);
}

// Get pending user requests
$pending_requests_query = "SELECT u.user_id, u.email, u.full_name, u.account_status, ut.user_type 
                          FROM user u 
                          JOIN user_type ut ON u.user_type_id = ut.user_type_id 
                          WHERE u.account_status = 1 AND u.user_type_id IN (2, 3)
                          ORDER BY u.user_id";
$pending_requests_result = mysqli_query($conn, $pending_requests_query);

// Get all users
$users_query = "SELECT u.user_id, u.email, u.full_name, u.account_status, ut.user_type 
                FROM user u 
                JOIN user_type ut ON u.user_type_id = ut.user_type_id 
                ORDER BY u.user_id";
$users_result = mysqli_query($conn, $users_query);

// Get processing orders for confirmation
$processing_orders_query = "
    SELECT 
        o.Order_ID,
        o.Order_Date_Time,
        o.Order_Status,
        u.full_name as customer_name,
        u.email as customer_email,
        COUNT(op.OrderPoolID) as item_count,
        SUM(op.Subtotal) as total_amount
    FROM orders o
    JOIN user u ON o.User_ID = u.user_id
    LEFT JOIN order_pool op ON o.Order_ID = op.OrderID
    WHERE o.Order_Status = 'processing'
    GROUP BY o.Order_ID, o.Order_Date_Time, o.Order_Status, u.full_name, u.email
    ORDER BY o.Order_Date_Time DESC
";
$processing_orders_result = mysqli_query($conn, $processing_orders_query);

// Get all products
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

// Get animal types and crop types
$animal_types_result = mysqli_query($conn, "SELECT * FROM animal_type ORDER BY animal");
$crop_types_result = mysqli_query($conn, "SELECT * FROM crop_type ORDER BY crop");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Rosello Farms</title>
    <link rel="stylesheet" href="css/admin_dashboard.css">
</head>
<body>
    <header class="header">
        <div class="logo">ROSELLO FARMS - ADMIN</div>
        <nav class="nav-links">
            <a href="admin_dashboard.php">Dashboard</a>
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <div class="container">
        <section class="welcome-section">
            <h1 class="welcome-title">Admin Dashboard</h1>
            <p>Manage users, products, and system settings</p>
        </section>

        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Processing Orders Section -->
        <section class="section">
            <h2 class="section-title">🛍️ Orders Awaiting Confirmation</h2>
            <?php if (mysqli_num_rows($processing_orders_result) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = mysqli_fetch_assoc($processing_orders_result)): ?>
                            <tr style="background: #fff3cd;">
                                <td><?php echo $order['Order_ID']; ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($order['Order_Date_Time'])); ?></td>
                                <td><?php echo $order['item_count']; ?></td>
                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['Order_ID']; ?>">
                                        <button type="submit" name="confirm_order" class="btn btn-small" 
                                                style="background: #28a745;"
                                                onclick="return confirm('Confirm this order?')">
                                            Confirm Order
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 2rem;">
                    No orders awaiting confirmation.
                </p>
            <?php endif; ?>
        </section>

        <!-- Pending Requests Section -->
        <section class="section">
            <h2 class="section-title">👤 Account Approval Requests</h2>
            <?php if (mysqli_num_rows($pending_requests_result) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Full Name</th>
                            <th>Requested Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($request = mysqli_fetch_assoc($pending_requests_result)): ?>
                            <tr style="background: #fff3cd;">
                                <td><?php echo $request['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($request['email']); ?></td>
                                <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                                <td>
                                    <span style="color: #e67e22; font-weight: bold;">
                                        <?php echo htmlspecialchars($request['user_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline; margin-right: 0.5rem;">
                                        <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                        <button type="submit" name="approve_user" class="btn btn-small" 
                                                style="background: #28a745;">
                                            Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                        <button type="submit" name="reject_user" class="btn btn-danger btn-small" 
                                                onclick="return confirm('Are you sure you want to reject this request? This will delete the account.')">
                                            Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 2rem;">
                    No pending account requests at this time.
                </p>
            <?php endif; ?>
        </section>

        <!-- Add Product Section -->
        <section class="section">
            <h2 class="section-title">Add New Product</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Product Type</label>
                        <select name="product_type" class="form-select" required onchange="toggleProductFields(this.value)">
                            <option value="">Select Type</option>
                            <option value="1">Animal</option>
                            <option value="2">Crop</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="animal-type-group" style="display: none;">
                        <label class="form-label">Animal Type</label>
                        <select name="animal_type_id" class="form-select">
                            <option value="">Select Animal Type</option>
                            <?php 
                            mysqli_data_seek($animal_types_result, 0);
                            while ($type = mysqli_fetch_assoc($animal_types_result)): 
                            ?>
                                <option value="<?php echo $type['animal_type_id']; ?>">
                                    <?php echo htmlspecialchars($type['animal']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" id="crop-type-group" style="display: none;">
                        <label class="form-label">Crop Type</label>
                        <select name="crop_type_id" class="form-select">
                            <option value="">Select Crop Type</option>
                            <?php 
                            mysqli_data_seek($crop_types_result, 0);
                            while ($type = mysqli_fetch_assoc($crop_types_result)): 
                            ?>
                                <option value="<?php echo $type['crop_type_id']; ?>">
                                    <?php echo htmlspecialchars($type['crop']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Weight (kg)</label>
                        <input type="number" name="weight" step="0.01" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Price (₱)</label>
                        <input type="number" name="price" step="0.01" class="form-input" required>
                    </div>
                </div>
                <button type="submit" name="add_product" class="btn">Add Product</button>
            </form>
        </section>

        <!-- Products Management Section -->
        <section class="section">
            <h2 class="section-title">Products Management</h2>
            <?php if (mysqli_num_rows($products_result) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Weight (kg)</th>
                            <th>Price (₱)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                            <tr>
                                <td><?php echo $product['product_id']; ?></td>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td><?php echo number_format($product['weight'], 2); ?></td>
                                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                        <input type="hidden" name="product_type" value="<?php echo $product['product_type_id']; ?>">
                                        <button type="submit" name="delete_product" class="btn btn-danger btn-small" 
                                                onclick="return confirm('Are you sure you want to delete this product?')">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No products found.</p>
            <?php endif; ?>
        </section>

        <!-- Users Management Section -->
        <section class="section">
            <h2 class="section-title">Users Management</h2>
            <?php if (mysqli_num_rows($users_result) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Full Name</th>
                            <th>User Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['user_type']); ?></td>
                                <td>
                                    <span class="<?php echo $user['account_status'] == 0 ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $user['account_status'] == 0 ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <input type="hidden" name="account_status" value="<?php echo $user['account_status'] == 0 ? 1 : 0; ?>">
                                        <button type="submit" name="update_user_status" class="btn btn-small">
                                            <?php echo $user['account_status'] == 0 ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No users found.</p>
            <?php endif; ?>
        </section>
    </div>

    <script>
        function toggleProductFields(productType) {
            const animalGroup = document.getElementById('animal-type-group');
            const cropGroup = document.getElementById('crop-type-group');
            
            if (productType === '1') {
                animalGroup.style.display = 'block';
                cropGroup.style.display = 'none';
                animalGroup.querySelector('select').required = true;
                cropGroup.querySelector('select').required = false;
            } else if (productType === '2') {
                animalGroup.style.display = 'none';
                cropGroup.style.display = 'block';
                animalGroup.querySelector('select').required = false;
                cropGroup.querySelector('select').required = true;
            } else {
                animalGroup.style.display = 'none';
                cropGroup.style.display = 'none';
                animalGroup.querySelector('select').required = false;
                cropGroup.querySelector('select').required = false;
            }
        }
    </script>
</body>
</html>