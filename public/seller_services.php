<?php
// Start session and verify admin access
session_start();
if (!isset($_SESSION['user_id'])|| $_SESSION['user_type'] !== 'seller') {
    header("Location: ../public/login.php");
    exit();
}
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Services - StrathConnect</title>
  <link rel="stylesheet" href="../assets/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body>
  <nav class="navbar">
    <div class="logo">StrathConnect</div>
    <ul class="nav-links">
      <li><a href="../public/index.php">Home</a></li>
      <li><a href="seller_dashboard.php">Dashboard</a></li>
      <li><a href="seller_products.php">My Products</a></li>
      <li><a href="seller_services.php" class="active">My Services</a></li>
      <li><a href="seller_orders.php">Orders</a></li>
      <li><a href="../public/login.php">Logout</a></li>
    </ul>
  </nav>

  <div class="dashboard-container">
    <aside class="sidebar">
      <div class="profile-summary">
        <img src="../assets/images/profile-placeholder.png" alt="Profile" class="profile-pic" />
        <h3>Seller Name</h3>
        <p>@username</p>
      </div>
      <nav class="sidebar-nav">
        <a href="seller_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="seller_products.php"><i class="fas fa-box-open"></i> My Products</a>
        <a href="seller_services.php" class="active"><i class="fas fa-concierge-bell"></i> My Services</a>
        <a href="seller_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
        <a href="seller_messages.php"><i class="fas fa-envelope"></i> Messages</a>
        <a href="seller_settings.php"><i class="fas fa-cog"></i> Settings</a>
      </nav>
    </aside>

    <main class="dashboard-content">
      <h1>My Services</h1>

      <section class="services-list">
        <!-- Example service item -->
        <div class="service-item">
          <h3>Tutoring in Math</h3>
          <p>Category: Education</p>
          <p>Rate: KSh 1,000/hour</p>
          <div class="service-actions">
            <button>Edit</button>
            <button>Delete</button>
          </div>
        </div>
        <!-- Repeat for more services -->
      </section>

      <button class="action-btn" onclick="location.href='add_service.php'">
        <i class="fas fa-plus-circle"></i> Add New Service
      </button>
    </main>
  </div>

  <footer class="footer">
    <p>&copy; 2023 StrathConnect. All rights reserved.</p>
  </footer>
</body>
</html>
