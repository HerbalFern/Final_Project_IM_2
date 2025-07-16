<?php
require_once 'config.php';

$error_message = "";

// Handle login form submission
if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    // Use authentication function
    $auth_result = authenticateUser($email, $password, $conn);
    
    if ($auth_result['success']) {
        redirectUserToDashboard($auth_result['user']['user_type_id']);
    } else {
        $error_message = $auth_result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rosello Farms</title>
    <link rel="stylesheet" href="css/index.css">
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