<?php
// Start session and verify admin access
session_start();
if (!isset($_SESSION['user_id'])) {
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
  <title>My Products - StrathConnect</title>
  <link rel="stylesheet" href="../assets/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body>
  <nav class="navbar">
    <div class="logo">StrathConnect</div>
    <ul class="nav-links">
      <li><a href="../public/index.php">Home</a></li>
      <li><a href="seller_dashboard.php">Dashboard</a></li>
      <li><a href="seller_products.php" class="active">My Products</a></li>
      <li><a href="seller_services.php">My Services</a></li>
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
        <a href="seller_products.php" class="active"><i class="fas fa-box-open"></i> My Products</a>
        <a href="seller_services.php"><i class="fas fa-concierge-bell"></i> My Services</a>
        <a href="seller_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
        <a href="seller_messages.php"><i class="fas fa-envelope"></i> Messages</a>
        <a href="seller_settings.php"><i class="fas fa-cog"></i> Settings</a>
      </nav>
    </aside>

    <main class="dashboard-content">
      <h1>My Products</h1>

      <section class="products-list">
        <!-- Example product item -->
        <div class="product-item">
          <img src="../assets/images/product-placeholder.png" alt="Product 1" />
          <div class="product-info">
            <h3>Product Name</h3>
            <p>Category: Electronics</p>
            <p>Price: KSh 3,000</p>
          </div>
          <div class="product-actions">
            <button>Edit</button>
            <button>Delete</button>
          </div>
        </div>
        <!-- Repeat for more products -->
      </section>

      <button class="action-btn" onclick="location.href='./database/add_product.php'">
        <i class="fas fa-plus-circle"></i> Add New Product
      </button>
    </main>
  </div>

  <footer class="footer">
    <p>&copy; 2023 StrathConnect. All rights reserved.</p>
  </footer>
</body>
</html>
