<?php
require_once 'config.php';

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    $sql = "SELECT u.user_id, u.email, u.password, u.full_name, u.user_type_id, u.account_status, ut.user_type 
            FROM user u 
            JOIN user_type ut ON u.user_type_id = ut.user_type_id 
            WHERE u.email = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        if ($user['account_status'] == 1) {
            $error_message = "Account is deactivated. Please contact administrator.";
        } else if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_type_id'] = $user['user_type_id'];
            $_SESSION['user_type'] = $user['user_type'];
            
            // Redirect based on user type
            switch($user['user_type_id']) {
                case 3: // Admin
                    header("Location: admin_dashboard.php");
                    break;
                case 2: // Employee
                    header("Location: employee_dashboard.php");
                    break;
                case 1: // Customer
                default:
                    header("Location: customer_dashboard.php");
                    break;
            }
            exit();
        } else {
            $error_message = "Invalid email or password.";
        }
    } else {
        $error_message = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rosello Farms</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            /* Background image - update the filename to match your image */
            background: url('images/background.jpg') no-repeat center center fixed;
            background-size: cover;
            
            /* If your background image is too bright, uncomment this for overlay: */
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

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            position: relative;
            backdrop-filter: blur(10px);
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo img {
            max-width: 200px;
            max-height: 120px;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 10px;
            /* Removed shadow effect */
            display: block;
            margin: 0 auto;
        }

        .logo-placeholder {
            width: 200px;
            height: 120px;
            background: linear-gradient(135deg, #f0f0f0, #e0e0e0);
            border: 2px dashed #2d5a3d;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2d5a3d;
            font-size: 1rem;
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

        .login-title {
            text-align: center;
            font-size: 2rem;
            font-weight: bold;
            color: #2d5a3d;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .login-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 2rem;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            background: rgba(249, 249, 249, 0.8);
        }

        .form-input:focus {
            outline: none;
            border-color: #2d5a3d;
            background: rgba(255, 255, 255, 0.9);
        }

        .login-button {
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

        .login-button:hover {
            background: #1e3d2a;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(45, 90, 61, 0.3);
        }

        .signup-link {
            text-align: center;
            padding: 1.5rem;
            background: rgba(248, 249, 250, 0.9);
            border-radius: 10px;
            border: 1px solid #e0e0e0;
        }

        .signup-link p {
            color: #666;
            margin-bottom: 0.5rem;
        }

        .signup-link a {
            color: #2d5a3d;
            text-decoration: none;
            font-weight: bold;
        }

        .signup-link a:hover {
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

        /* Instructions removed */

        @media (max-width: 768px) {
            .login-container {
                margin: 2rem;
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
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

        <h1 class="login-title">Welcome Back</h1>
        <p class="login-subtitle">Sign in to your account</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>

            <button type="submit" class="login-button">Sign In</button>
        </form>

        <div class="signup-link">
            <p>Don't have an account?</p>
            <a href="signup.php">Create one here</a>
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