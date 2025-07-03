<?php
session_start();

// Verify buyer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
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

// Get user profile data
$user_id = $_SESSION['user_id'];
$user_query = "SELECT username, profile_pic FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Get recommended products
$recommended_products = [];
$product_query = "SELECT p.*, u.username as seller_username, u.profile_pic as seller_profile
                 FROM products p 
                 JOIN users u ON p.seller_id = u.id 
                 WHERE p.is_approved = 1 
                 ORDER BY p.created_at DESC 
                 LIMIT 4";
$product_result = $conn->query($product_query);
if ($product_result) {
    $recommended_products = $product_result->fetch_all(MYSQLI_ASSOC);
}

// Get popular services
$popular_services = [];
$service_query = "SELECT s.*, u.username as seller_username, u.profile_pic as seller_profile, s.description
                FROM services s 
                JOIN users u ON s.seller_id = u.id 
                WHERE s.is_approved = 1 
                ORDER BY s.created_at DESC 
                LIMIT 4";
$service_result = $conn->query($service_query);
if ($service_result) {
    $popular_services = $service_result->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buyer Dashboard - StrathConnect</title>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .seller-info {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 12px;
      color: #555;
      margin-top: 5px;
    }
    .seller-info img {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      object-fit: cover;
    }
    .products-grid, .services-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 15px;
      margin: 15px 0;
    }
    .product-card, .service-card {
      background: #fff;
      border-radius: 8px;
      padding: 10px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      text-align: center;
    }
    .product-card img {
      width: 100%;
      height: 150px;
      object-fit: cover;
      border-radius: 6px;
      margin-bottom: 8px;
    }
    .product-card h3, .service-card h3 {
      font-size: 16px;
      margin: 5px 0;
    }
    .price, .rate {
      color: #dc3545;
      font-weight: bold;
      margin-bottom: 5px;
    }
    .service-description {
      font-size: 13px;
      color: #555;
      margin-bottom: 8px;
      min-height: 40px;
    }
    .add-to-cart, .btn-view-details, .book-service {
      display: inline-block;
      background: #003366;
      color: #fff;
      padding: 6px 10px;
      border-radius: 4px;
      font-size: 12px;
      margin: 3px 0;
      text-decoration: none;
    }
    .add-to-cart:hover, .btn-view-details:hover, .book-service:hover {
      background: #004080;
    }
    .view-all {
      text-align: right;
      margin-top: 10px;
    }
    .btn-view-all {
      background: #FF9900;
      color: #fff;
      padding: 8px 14px;
      border-radius: 4px;
      text-decoration: none;
      font-size: 13px;
    }
    .btn-view-all:hover {
      background: #e68a00;
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="logo">StrathConnect</div>
    <ul class="nav-links">
      <li><a href="marketplace.php">Marketplace</a></li>
      <li><a href="services.php">Services</a></li>
      <li><a href="buyer_orders.php">My Orders</a></li>
      <li><a href="../public/login.php">Logout</a></li>
    </ul>
  </nav>

  <div class="dashboard-container">
    <aside class="sidebar">
      <div class="profile-summary">
        <img src="<?php echo htmlspecialchars($user_data['profile_pic'] ?? '../assets/images/profile-placeholder.png'); ?>" alt="Profile" class="profile-pic">
        <h3><?php echo htmlspecialchars($user_data['username'] ?? 'Buyer'); ?></h3>
        <p>@<?php echo htmlspecialchars($user_data['username'] ?? 'username'); ?></p>
      </div>
      <nav class="sidebar-nav">
        <a href="buyer_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="marketplace.php"><i class="fas fa-store"></i> Marketplace</a>
        <a href="services.php"><i class="fas fa-concierge-bell"></i> Services</a>
        <a href="buyer_orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a>
        <a href="buyer_wishlist.php"><i class="fas fa-heart"></i> Wishlist</a>
        <a href="buyer_settings.php"><i class="fas fa-cog"></i> Settings</a>
      </nav>
    </aside>

    <main class="dashboard-content">
      <h1>Welcome Back, <?php echo htmlspecialchars($user_data['username'] ?? 'Buyer'); ?>!</h1>

      <section class="search-section">
        <form action="marketplace.php" method="GET" class="search-bar">
          <input type="text" name="search" placeholder="Search for products or services...">
          <button type="submit"><i class="fas fa-search"></i></button>
        </form>
      </section>

      <section class="featured-products">
        <h2>Recommended Products</h2>
        <div class="products-grid">
          <?php if (empty($recommended_products)): ?>
            <p>No recommended products found.</p>
          <?php else: ?>
            <?php foreach ($recommended_products as $product): ?>
              <div class="product-card">
                <img src="../assets/images/products/<?php echo htmlspecialchars($product['image_path'] ?? 'placeholder.jpg'); ?>" 
                  alt="<?php echo htmlspecialchars($product['name']); ?>">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <div class="price">KSh <?php echo number_format($product['price'], 2); ?></div>
                <div class="seller-info">
                  <img src="<?php echo htmlspecialchars($product['seller_profile'] ?? '../assets/images/profile-placeholder.png'); ?>" alt="Seller">
                  <span>@<?php echo htmlspecialchars($product['seller_username']); ?></span>
                </div>
                <button class="add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                  Add to Cart
                </button>
                <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn-view-details">
                  View Details
                </a>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <div class="view-all">
          <a href="marketplace.php" class="btn-view-all">View All Products</a>
        </div>
      </section>

      <section class="featured-services">
        <h2>Popular Services</h2>
        <div class="services-grid">
          <?php if (empty($popular_services)): ?>
            <p>No popular services found.</p>
          <?php else: ?>
            <?php foreach ($popular_services as $service): ?>
              <div class="service-card">
                <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                <div class="rate">KSh <?php echo number_format($service['price'], 2); ?></div>
                <div class="service-description">
                  <?php echo htmlspecialchars(mb_strimwidth($service['description'], 0, 80, '...')); ?>
                </div>
                <div class="seller-info">
                  <img src="<?php echo htmlspecialchars($service['seller_profile'] ?? '../assets/images/profile-placeholder.png'); ?>" alt="Seller">
                  <span>@<?php echo htmlspecialchars($service['seller_username']); ?></span>
                </div>
                <a href="service_detail.php?id=<?php echo $service['id']; ?>" class="book-service">
                  View Details
                </a>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <div class="view-all">
          <a href="services.php" class="btn-view-all">View All Services</a>
        </div>
      </section>
    </main>
  </div>

  <footer class="footer">
    <p>&copy; <?php echo date('Y'); ?> StrathConnect. All rights reserved.</p>
  </footer>

  <script>
    document.querySelectorAll('.add-to-cart').forEach(button => {
      button.addEventListener('click', function() {
        const productId = this.getAttribute('data-product-id');
        fetch('add_to_cart.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Product added to cart!');
          } else {
            alert('Error: ' + data.message);
          }
        });
      });
    });
  </script>
</body>
</html>
