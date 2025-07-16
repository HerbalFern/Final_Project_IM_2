<?php
require_once 'config.php';

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $last_name = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $contact_info = mysqli_real_escape_string($conn, trim($_POST['contact_info']));
    $user_type_id = (int)$_POST['user_type_id']; // Get selected role
    
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
        // Check if email already exists
        $check_email = "SELECT user_id FROM user WHERE email = ?";
        $stmt = mysqli_prepare($conn, $check_email);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error_message = "Email already exists. Please use a different email.";
        } else {
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
                    $success_message = "Account created successfully! You can now log in.";
                } else if ($user_type_id == 2) { // Employee
                    $personnel_type = "Farm Worker"; // Default personnel type
                    $insert_personnel = "INSERT INTO personnel (personnel_type, user_id) VALUES (?, ?)";
                    $stmt2 = mysqli_prepare($conn, $insert_personnel);
                    mysqli_stmt_bind_param($stmt2, "si", $personnel_type, $user_id);
                    mysqli_stmt_execute($stmt2);
                    $success_message = "Employee account created! Please wait for admin approval before you can login.";
                } else if ($user_type_id == 3) { // Admin
                    $success_message = "Admin account created! Please wait for admin approval before you can login.";
                }
                // Admin (user_type_id = 3) doesn't need additional table entry
                
                mysqli_commit($conn);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error_message = "Error creating account. Please try again.";
            }
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            /* Background image with gradient overlay for better readability */
            background: 
                linear-gradient(135deg, rgba(45, 90, 61, 0.6), rgba(30, 61, 42, 0.6)),
                url('images/background.jpg') no-repeat center center fixed;
            background-size: cover;
            
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .signup-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            padding: 3rem;
            width: 100%;
            max-width: 550px;
            position: relative;
            margin: 2rem;
            backdrop-filter: blur(10px);
        }

        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            transition: color 0.3s ease;
            z-index: 10;
            text-decoration: none;
        }

        .back-button:hover {
            color: #2d5a3d;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo img {
            max-width: 180px;
            max-height: 100px;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 10px;
            display: block;
            margin: 0 auto;
        }

        .logo-placeholder {
            width: 180px;
            height: 100px;
            background: linear-gradient(135deg, #f0f0f0, #e0e0e0);
            border: 2px dashed #2d5a3d;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2d5a3d;
            font-size: 0.9rem;
            font-weight: bold;
            margin: 0 auto;
            text-align: center;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .logo-placeholder:hover {
            background: linear-gradient(135deg, #e8f5e8, #d4f0d4);
            border-color: #1e3d2a;
        }

        .signup-title {
            text-align: center;
            font-size: 2rem;
            font-weight: bold;
            color: #2d5a3d;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .signup-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 2rem;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: flex;
            gap: 1rem;
        }

        .form-row .form-group {
            flex: 1;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            background: rgba(249, 249, 249, 0.8);
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #2d5a3d;
            background: rgba(255, 255, 255, 0.9);
        }

        .role-selection {
            background: rgba(248, 249, 250, 0.9);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e0e0e0;
        }

        .role-options {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .role-option {
            flex: 1;
            min-width: 120px;
        }

        .role-radio {
            display: none;
        }

        .role-label {
            display: block;
            padding: 1rem;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .role-radio:checked + .role-label {
            background: #2d5a3d;
            color: white;
            border-color: #2d5a3d;
        }

        .role-label:hover {
            border-color: #2d5a3d;
            background: #f0f8f0;
        }

        .role-radio:checked + .role-label:hover {
            background: #1e3d2a;
        }

        .role-description {
            font-size: 0.8rem;
            margin-top: 0.3rem;
            opacity: 0.8;
        }

        .signup-button {
            width: 100%;
            padding: 15px;
            background: #2d5a3d;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1.5rem;
        }

        .signup-button:hover {
            background: #1e3d2a;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(45, 90, 61, 0.3);
        }

        .login-link {
            text-align: center;
            padding: 1.5rem;
            background: rgba(248, 249, 250, 0.9);
            border-radius: 10px;
            border: 1px solid #e0e0e0;
        }

        .login-link p {
            color: #666;
            margin-bottom: 0.5rem;
        }

        .login-link a {
            color: #2d5a3d;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: rgba(248, 215, 218, 0.9);
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
        }

        .success-message {
            background: rgba(212, 237, 218, 0.9);
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #c3e6cb;
        }

        @media (max-width: 768px) {
            .signup-container {
                margin: 1rem;
                padding: 2rem;
            }
            
            .back-button {
                top: 15px;
                left: 15px;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .role-options {
                flex-direction: column;
            }

            .role-option {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <a href="login.php" class="back-button">←</a>
        
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
            <a href="login.php">Sign in here</a>
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