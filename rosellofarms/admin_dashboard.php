<?php
require_once 'config.php';
requireAdmin();

$message = "";

// Handle approve/reject user requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_user'])) {
    $user_id = (int)$_POST['user_id'];
    
    $approve_user = "UPDATE user SET account_status = 0 WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $approve_user);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = "User approved successfully!";
    } else {
        $message = "Error approving user.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reject_user'])) {
    $user_id = (int)$_POST['user_id'];
    
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
        $message = "User request rejected and removed.";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $message = "Error rejecting user.";
    }
}

// Handle user status updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user_status'])) {
    $user_id = (int)$_POST['user_id'];
    $new_status = (int)$_POST['account_status'];
    
    $update_status = "UPDATE user SET account_status = ? WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $update_status);
    mysqli_stmt_bind_param($stmt, "ii", $new_status, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = "User status updated successfully!";
    } else {
        $message = "Error updating user status.";
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

// Handle delete product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_product'])) {
    $product_id = (int)$_POST['product_id'];
    $product_type = (int)$_POST['product_type'];
    
    mysqli_begin_transaction($conn);
    
    try {
        // Delete from product table
        $delete_product = "DELETE FROM product WHERE product_id = ? AND product_type = ?";
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
        $message = "Product deleted successfully!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $message = "Error deleting product.";
    }
}

// Get pending user requests (inactive accounts that need approval)
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

// Get all products
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Rosello Farms</title>
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

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
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

        .status-active {
            color: #28a745;
            font-weight: bold;
        }

        .status-inactive {
            color: #dc3545;
            font-weight: bold;
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

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .tab {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            padding: 1rem 2rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #333;
            font-weight: bold;
        }

        .tab.active {
            background: #2d5a3d;
            color: white;
            border-color: #2d5a3d;
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

            .tabs {
                flex-direction: column;
            }
        }
    </style>
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
                                        <input type="hidden" name="product_type" value="<?php echo $product['product_type']; ?>">
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