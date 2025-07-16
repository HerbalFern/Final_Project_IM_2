<?php
require_once 'config.php';
requireEmployee();

$message = "";

// Handle update product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
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

// Handle add product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $product_type = (int)$_POST['product_type'];
    $weight = (float)$_POST['weight'];
    $price = (float)$_POST['price'];
    
    mysqli_begin_transaction($conn);
    
    try {
        if ($product_type == 1) { // Animal
            $animal_type_id = (int)$_POST['animal_type_id'];
            
            // Insert into animal table
            $insert_animal = "INSERT INTO animal (weight, price, animal_type_id) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_animal);
            mysqli_stmt_bind_param($stmt, "ddi", $weight, $price, $animal_type_id);
            mysqli_stmt_execute($stmt);
            $animal_id = mysqli_insert_id($conn);
            
            // Insert into product table
            $insert_product = "INSERT INTO product (product_id, product_type) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $insert_product);
            mysqli_stmt_bind_param($stmt, "ii", $animal_id, $product_type);
            mysqli_stmt_execute($stmt);
            
        } else if ($product_type == 2) { // Crop
            $crop_type_id = (int)$_POST['crop_type_id'];
            
            // Insert into crop table
            $insert_crop = "INSERT INTO crop (weight, price, crop_type_id) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_crop);
            mysqli_stmt_bind_param($stmt, "ddi", $weight, $price, $crop_type_id);
            mysqli_stmt_execute($stmt);
            $crop_id = mysqli_insert_id($conn);
            
            // Insert into product table
            $insert_product = "INSERT INTO product (product_id, product_type) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $insert_product);
            mysqli_stmt_bind_param($stmt, "ii", $crop_id, $product_type);
            mysqli_stmt_execute($stmt);
        }
        
        mysqli_commit($conn);
        $message = "Product added successfully!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $message = "Error adding product.";
    }
}

// Get all products that employee can manage
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
            max-width: 1400px;
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

        .section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            color: #2d5a3d;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #2d5a3d;
            padding-bottom: 0.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #555;
        }

        .form-input, .form-select {
            padding: 0.7rem;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #2d5a3d;
        }

        .btn {
            background: #2d5a3d;
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .btn:hover {
            background: #1e3d2a;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table th,
        .table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #2d5a3d;
        }

        .table tr:hover {
            background: #f8f9fa;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #e0e0e0;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2d5a3d;
            display: block;
        }

        .stat-label {
            color: #666;
            margin-top: 0.5rem;
        }

        .edit-form {
            display: none;
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 0.5rem;
        }

        .edit-form.active {
            display: block;
        }

        .edit-form .form-grid {
            grid-template-columns: 1fr 1fr auto;
            align-items: end;
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

            .form-grid {
                grid-template-columns: 1fr;
            }

            .table {
                font-size: 0.9rem;
            }
        }
    </style>
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
                                                    <input type="hidden" name="product_type" value="<?php echo $product['product_type']; ?>">
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