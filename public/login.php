<?php
// Start session and verify admin access
session_start();

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'strathconnect';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Strath Connect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <!-- Navbar (consistent across all pages) -->
    <nav class="navbar">
        <div class="logo">Strath Connect</div>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="services.php">Services</a></li>
            <li><a href="login.php" class="login-btn">Login</a></li>
        </ul>
    </nav>

    <!-- Login Form Section -->
    <main class="auth-container">
        <div class="auth-card">
            <h1>Welcome Back</h1>
            <p class="auth-subtitle">Login to your Strath Connect account</p>
            
            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="error-message" style="color: red; margin-bottom: 1rem;">
                    <?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?>
                </div>
            <?php endif; ?>
            
            <form class="auth-form" action="../database/login_process.php" method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="student@strathmore.edu" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                    <a href="#" class="forgot-password">Forgot password?</a>
                </div>
                
                <button type="submit" class="auth-btn">Login</button>
                
                <div class="auth-footer">
                    <p>Don't have an account? <a href="../public/signup.php">Sign up</a></p>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer (consistent across all pages) -->
    <footer class="footer">
        <p>&copy; 2023 Strath Connect. All rights reserved.</p>
    </footer>
</body>
</html>