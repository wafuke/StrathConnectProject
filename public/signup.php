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
  <title>Signup - StrathConnect</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
  <div class="auth-container">
    <div class="auth-card">
      <h1>Create Account</h1>
      <p class="auth-subtitle">Join StrathConnect today</p>

      <?php if (isset($_SESSION['signup_errors'])): ?>
        <div class="error-message" style="color: red; margin-bottom: 1rem;">
          <?php 
            foreach($_SESSION['signup_errors'] as $error) {
              echo htmlspecialchars($error) . "<br>";
            }
            unset($_SESSION['signup_errors']);
          ?>
        </div>
      <?php endif; ?>

      <form action="../database/signup_process.php" method="POST" class="auth-form">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" required>
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
          <label for="user_type">User Type</label>
          <select id="user_type" name="user_type" class="user-type-select" required>
            <option value="">Select user type</option>
            <option value="admin">Admin</option>
            <option value="seller">Seller</option>
            <option value="buyer">Buyer</option>
          </select>
        </div>

        <div class="form-group password-wrapper">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" minlength="8"  required>
          <span class="input-hint">Minimum 8 characters</span>
        </div>

        <div class="checkbox-group">
          <input type="checkbox" id="terms" name="terms" required>
          <label for="terms">I agree to the <a href="#">terms and conditions</a></label>
        </div>

        <button type="submit" class="auth-btn">Sign Up</button>
      </form>

      <div class="auth-footer">
        <p>Already have an account? <a href="../public/login.php">Login</a></p>
      </div>
    </div>
  </div>

  <footer class="footer">
    <p>&copy; 2025 StrathConnect. All rights reserved.</p>
  </footer>
</body>
</html>