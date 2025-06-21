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

// Get buyer's wishlist
$buyer_id = $_SESSION['user_id'];
$query = "SELECT w.*, p.*, u.username as seller_username 
          FROM wishlist w
          JOIN products p ON w.product_id = p.id
          JOIN users u ON p.seller_id = u.id
          WHERE w.buyer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$result = $stmt->get_result();
$wishlist = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - StrathConnect</title>
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
            <li><a href="buyer_messages.php">Messages</a></li>
            <li><a href="../public/login.php">Logout</a></li>
        </ul>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="profile-summary">
                <img src="../assets/images/profile-placeholder.png" alt="Profile" class="profile-pic">
                <h3><?php echo htmlspecialchars($_SESSION['username'] ?? 'Buyer'); ?></h3>
                <p>@<?php echo htmlspecialchars($_SESSION['username'] ?? 'username'); ?></p>
            </div>
            <nav class="sidebar-nav">
                <a href="buyer_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="marketplace.php"><i class="fas fa-store"></i> Marketplace</a>
                <a href="services.php"><i class="fas fa-concierge-bell"></i> Services</a>
                <a href="buyer_orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a>
                <a href="buyer_messages.php"><i class="fas fa-envelope"></i> Messages</a>
                <a href="buyer_wishlist.php" class="active"><i class="fas fa-heart"></i> Wishlist</a>
                <a href="buyer_settings.php"><i class="fas fa-cog"></i> Settings</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <h1>My Wishlist</h1>
            
            <?php if (empty($wishlist)): ?>
                <div class="empty-state">
                    <i class="fas fa-heart"></i>
                    <h3>Your wishlist is empty</h3>
                    <p>Add products to your wishlist to save them for later</p>
                    <a href="marketplace.php" class="btn btn-primary">Browse Marketplace</a>
                </div>
            <?php else: ?>
                <div class="wishlist-grid">
                    <?php foreach ($wishlist as $item): ?>
                        <div class="wishlist-item">
                            <img src="../assets/images/products/<?php echo htmlspecialchars($item['image_path'] ?? 'placeholder.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <div class="price">KSh <?php echo number_format($item['price'], 2); ?></div>
                                <div class="seller-info">
                                    <img src="../assets/images/profile-placeholder.png" alt="Seller">
                                    <span>@<?php echo htmlspecialchars($item['seller_username']); ?></span>
                                </div>
                            </div>
                            <div class="item-actions">
                                <button class="btn-add-to-cart" data-product-id="<?php echo $item['product_id']; ?>">
                                    Add to Cart
                                </button>
                                <button class="btn-remove" data-wishlist-id="<?php echo $item['id']; ?>">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> StrathConnect. All rights reserved.</p>
    </footer>

    <script>
    // Add to cart from wishlist
    document.querySelectorAll('.btn-add-to-cart').forEach(button => {
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

    // Remove from wishlist
    document.querySelectorAll('.btn-remove').forEach(button => {
        button.addEventListener('click', function() {
            const wishlistId = this.getAttribute('data-wishlist-id');
            if (confirm('Remove this item from your wishlist?')) {
                fetch('remove_from_wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `wishlist_id=${wishlistId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Item removed from wishlist');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        });
    });
    </script>
</body>
</html>