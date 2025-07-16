<?php
require_once 'config.php';
requireEmployee();

$message = "";

// Handle form submissions using functions
if (isset($_POST['add_product'])) {
    $product_type = (int)$_POST['product_type'];
    $weight = (float)$_POST['weight'];
    $price = (float)$_POST['price'];
    $type_id = $product_type == 1 ? (int)$_POST['animal_type_id'] : (int)$_POST['crop_type_id'];
    
    $message = addProduct($product_type, $weight, $price, $type_id, $conn);
}

if (isset($_POST['update_product'])) {
    $product_id = (int)$_POST['product_id'];
    $product_type = (int)$_POST['product_type'];
    $weight = (float)$_POST['weight'];
    $price = (float)$_POST['price'];
    
    mysqli_begin_transaction($conn);
    
    try {
        if ($product_type == 1) { // Animal
            $update_animal = "UPDATE animal SET weight = ?, price = ? WHERE animal_id = ?";
            $stmt = mysqli_prepare($conn, $update_animal);
            mysqli_stmt_bind_param($stmt, "ddi", $weight, $price, $product_id);
            mysqli_stmt_execute($stmt);
        } else if ($product_type == 2) { // Crop
            $update_crop = "UPDATE crop SET weight = ?, price = ? WHERE crop_id = ?";
            $stmt = mysqli_prepare($conn, $update_crop);
            mysqli_stmt_bind_param($stmt, "ddi", $weight, $price, $product_id);
            mysqli_stmt_execute($stmt);
        }
        
        mysqli_commit($conn);
        $message = "Product updated successfully!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $message = "Error updating product.";
    }
}

// Get all products that employee can manage
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

// Get animal types and crop types for the add product form
$animal_types_result = mysqli_query($conn, "SELECT * FROM animal_type ORDER BY animal");
$crop_types_result = mysqli_query($conn, "SELECT * FROM crop_type ORDER BY crop");

// Get pending orders for management
$orders_query = "
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
    WHERE o.Order_Status IN ('pending', 'processing')
    GROUP BY o.Order_ID, o.Order_Date_Time, o.Order_Status, u.full_name, u.email
    ORDER BY o.Order_Date_Time DESC
";
$orders_result = mysqli_query($conn, $orders_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Rosello Farms</title>
    <link rel="stylesheet" href="css/employee_dashboard.css">
</head>
<body>
    <header class="header">
        <div class="logo">ROSELLO FARMS - EMPLOYEE</div>
        <nav class="nav-links">
            <a href="employee_dashboard.php">Dashboard</a>
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <div class="container">
        <section class="welcome-section">
            <h1 class="welcome-title">Employee Dashboard</h1>
            <p>Manage inventory, update products, and process orders</p>
        </section>

        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo mysqli_num_rows($products_result); ?></span>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo mysqli_num_rows($orders_result); ?></span>
                <div class="stat-label">Pending Orders</div>
            </div>
        </div>

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
                        <?php 
                        mysqli_data_seek($products_result, 0);
                        while ($product = mysqli_fetch_assoc($products_result)): 
                        ?>
                            <tr>
                                <td><?php echo $product['product_id']; ?></td>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td><?php echo number_format($product['weight'], 2); ?></td>
                                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <button type="button" class="btn btn-small" onclick="toggleEditForm(<?php echo $product['product_id']; ?>)">
                                        Edit
                                    </button>
                                    
                                    <div id="edit-form-<?php echo $product['product_id']; ?>" class="edit-form">
                                        <form method="POST">
                                            <div class="form-grid">
                                                <div class="form-group">
                                                    <label class="form-label">Weight (kg)</label>
                                                    <input type="number" name="weight" step="0.01" class="form-input" 
                                                           value="<?php echo $product['weight']; ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Price (₱)</label>
                                                    <input type="number" name="price" step="0.01" class="form-input" 
                                                           value="<?php echo $product['price']; ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                                    <input type="hidden" name="product_type" value="<?php echo $product['product_type_id']; ?>">
                                                    <button type="submit" name="update_product" class="btn btn-small">Update</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No products found.</p>
            <?php endif; ?>
        </section>

        <!-- Orders Overview Section -->
        <section class="section">
            <h2 class="section-title">Recent Orders</h2>
            <?php if (mysqli_num_rows($orders_result) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                            <tr>
                                <td><?php echo $order['Order_ID']; ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($order['Order_Date_Time'])); ?></td>
                                <td><?php echo $order['item_count']; ?></td>
                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span style="color: <?php echo $order['Order_Status'] == 'pending' ? '#dc3545' : '#28a745'; ?>; font-weight: bold;">
                                        <?php echo ucfirst($order['Order_Status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No pending orders found.</p>
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

        function toggleEditForm(productId) {
            const editForm = document.getElementById('edit-form-' + productId);
            if (editForm.classList.contains('active')) {
                editForm.classList.remove('active');
            } else {
                // Hide all other edit forms
                const allEditForms = document.querySelectorAll('.edit-form');
                allEditForms.forEach(form => form.classList.remove('active'));
                
                // Show this edit form
                editForm.classList.add('active');
            }
        }
    </script>
</body>
</html>