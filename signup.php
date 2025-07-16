<?php
require_once 'config.php';

$error_message = "";
$success_message = "";

// Function to create user account
function createUserAccount($first_name, $last_name, $email, $password, $contact_info, $user_type_id, $conn) {
    // Check if email already exists
    $check_email = "SELECT user_id FROM user WHERE email = ?";
    $stmt = mysqli_prepare($conn, $check_email);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        return ['success' => false, 'message' => 'Email already exists. Please use a different email.'];
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $full_name = $first_name . " " . $last_name;
    
    // Set account status based on user type
    if ($user_type_id == 1) { // Customer
        $account_status = 0; // Active immediately
    } else { // Employee or Admin
        $account_status = 1; // Inactive - needs approval
    }
    
    mysqli_begin_transaction($conn);
    
    try {
        // Insert user
        $insert_user = "INSERT INTO user (email, password, full_name, user_type_id, account_status) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_user);
        mysqli_stmt_bind_param($stmt, "sssii", $email, $hashed_password, $full_name, $user_type_id, $account_status);
        mysqli_stmt_execute($stmt);
        $user_id = mysqli_insert_id($conn);
        
        // Insert role-specific record
        if ($user_type_id == 1) { // Customer
            $insert_customer = "INSERT INTO customer (contact_info, user_id) VALUES (?, ?)";
            $stmt2 = mysqli_prepare($conn, $insert_customer);
            mysqli_stmt_bind_param($stmt2, "si", $contact_info, $user_id);
            mysqli_stmt_execute($stmt2);
            $message = "Account created successfully! You can now log in.";
        } else if ($user_type_id == 2) { // Employee
            // Insert into personnel table with personnel_type_id = 1 (employee)
            $insert_personnel = "INSERT INTO personnel (personnel_type_id, user_id) VALUES (1, ?)";
            $stmt2 = mysqli_prepare($conn, $insert_personnel);
            mysqli_stmt_bind_param($stmt2, "i", $user_id);
            mysqli_stmt_execute($stmt2);
            $message = "Employee account created! Please wait for admin approval before you can login.";
        } else if ($user_type_id == 3) { // Admin
            // Insert into personnel table with personnel_type_id = 2 (admin)
            $insert_personnel = "INSERT INTO personnel (personnel_type_id, user_id) VALUES (2, ?)";
            $stmt2 = mysqli_prepare($conn, $insert_personnel);
            mysqli_stmt_bind_param($stmt2, "i", $user_id);
            mysqli_stmt_execute($stmt2);
            $message = "Admin account created! Please wait for admin approval before you can login.";
        }
        
        mysqli_commit($conn);
        return ['success' => true, 'message' => $message];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return ['success' => false, 'message' => 'Error creating account. Please try again.'];
    }
}

// Handle form submission
if (isset($_POST['first_name'])) {
    $first_name = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $last_name = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $contact_info = mysqli_real_escape_string($conn, trim($_POST['contact_info']));
    $user_type_id = (int)$_POST['user_type_id'];
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error_message = "All fields are required.";
    } else if ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else if (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } else if (!in_array($user_type_id, [1, 2, 3])) {
        $error_message = "Please select a valid user type.";
    } else {
        $result = createUserAccount($first_name, $last_name, $email, $password, $contact_info, $user_type_id, $conn);
        if ($result['success']) {
            $success_message = $result['message'];
        } else {
            $error_message = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Rosello Farms</title>
    <link rel="stylesheet" href="css/signup.css">
</head>
<body>
    <div class="signup-container">
        <a href="index.php" class="back-button">←</a>
        
        <div class="logo">
            <!-- TO ADD YOUR LOGO: Place your image in an 'images' folder and name it logo.png -->
            <img src="images/logo.png" alt="Rosello Farms Logo" style="display: none;" id="farm-logo">
            
            <!-- This placeholder will show if no image is found -->
            <div class="logo-placeholder" id="logo-placeholder">
                ROSELLO FARMS<br>
                <small style="font-size: 0.7rem; margin-top: 5px; display: block;">
                    Add logo.png to images folder
                </small>
            </div>
        </div>

        <h1 class="signup-title">Join Rosello Farms</h1>
        <p class="signup-subtitle">Create your account to get started</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-input" required
                           value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-input" required
                           value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="contact_info">Contact Information</label>
                <input type="text" id="contact_info" name="contact_info" class="form-input" placeholder="Phone number or address"
                       value="<?php echo isset($_POST['contact_info']) ? htmlspecialchars($_POST['contact_info']) : ''; ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
            </div>

            <!-- Role Selection -->
            <div class="role-selection">
                <label class="form-label">Account Type</label>
                <div class="role-options">
                    <div class="role-option">
                        <input type="radio" id="customer" name="user_type_id" value="1" class="role-radio" 
                               <?php echo (!isset($_POST['user_type_id']) || $_POST['user_type_id'] == 1) ? 'checked' : ''; ?>>
                        <label for="customer" class="role-label">
                            Customer
                            <div class="role-description">Browse & buy products</div>
                        </label>
                    </div>
                    <div class="role-option">
                        <input type="radio" id="employee" name="user_type_id" value="2" class="role-radio"
                               <?php echo (isset($_POST['user_type_id']) && $_POST['user_type_id'] == 2) ? 'checked' : ''; ?>>
                        <label for="employee" class="role-label">
                            Employee
                            <div class="role-description">Manage inventory<br><small style="color: #e67e22;">⚠️ Requires approval</small></div>
                        </label>
                    </div>
                    <div class="role-option">
                        <input type="radio" id="admin" name="user_type_id" value="3" class="role-radio"
                               <?php echo (isset($_POST['user_type_id']) && $_POST['user_type_id'] == 3) ? 'checked' : ''; ?>>
                        <label for="admin" class="role-label">
                            Admin
                            <div class="role-description">Full system access<br><small style="color: #e67e22;">⚠️ Requires approval</small></div>
                        </label>
                    </div>
                </div>
            </div>

            <button type="submit" class="signup-button">Create Account</button>
        </form>

        <div class="login-link">
            <p>Already have an account?</p>
            <a href="index.php">Sign in here</a>
        </div>
    </div>

    <script>
        // Auto-show logo if image exists
        document.addEventListener('DOMContentLoaded', function() {
            const logoImg = document.getElementById('farm-logo');
            const placeholder = document.getElementById('logo-placeholder');
            
            logoImg.onload = function() {
                logoImg.style.display = 'block';
                placeholder.style.display = 'none';
            };
            
            logoImg.onerror = function() {
                logoImg.style.display = 'none';
                placeholder.style.display = 'flex';
            };
            
            // Try to load the image
            logoImg.src = logoImg.src;
        });
    </script>
</body>
</html>