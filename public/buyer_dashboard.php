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

// Get recommended products
$recommended_products = [];
$product_query = "SELECT p.*, u.username as seller_username 
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
$service_query = "SELECT s.*, u.username as seller_username 
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
</head>
<body>
    <nav class="navbar">
        <div class="logo">StrathConnect</div>
        <ul class="nav-links">
            <li><a href="../public/index.php">Home</a></li>
            <li><a href="marketplace.php">Marketplace</a></li>
            <li><a href="services.php">Services</a></li>
            <li><a href="buyer_orders.php">My Orders</a></li>
            <li><a href="../public/login.php">Logout</a></li>
        </ul>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="profile-summary">
                <img src="../assets/images/profile-placeholder.png" alt="Profile" class="profile-pic">
                <h3><?php echo htmlspecialchars($_SESSION['username'] ?? 'Buyer'); ?></h3>
                <p>@<?php echo htmlspecialchars($_SESSION['username'] ?? 'username'); ?></p>
                <div class="wallet-balance">
                    <i class="fas fa-wallet"></i> KSh 1,250
                </div>
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
            <h1>Welcome Back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Buyer'); ?>!</h1>
            
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
                                    <img src="../assets/images/profile-placeholder.png" alt="Seller">
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
                                <img src="../assets/images/services/<?php echo htmlspecialchars($service['image_path'] ?? 'service-placeholder.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($service['title']); ?>">
                                <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                                <div class="rate">KSh <?php echo number_format($service['price'], 2); ?></div>
                                <div class="seller-info">
                                    <img src="../assets/images/profile-placeholder.png" alt="Seller">
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
    // Add to cart functionality
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
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