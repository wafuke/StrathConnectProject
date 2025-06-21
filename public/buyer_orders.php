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

// Get buyer's orders
$buyer_id = $_SESSION['user_id'];
$query = "SELECT o.*, p.name as product_name, p.image_path, u.username as seller_username 
          FROM orders o
          JOIN products p ON o.product_id = p.id
          JOIN users u ON p.seller_id = u.id
          WHERE o.buyer_id = ?
          ORDER BY o.order_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - StrathConnect</title>
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
            <li><a href="buyer_orders.php" class="active">My Orders</a></li>
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
                <a href="buyer_orders.php" class="active"><i class="fas fa-shopping-bag"></i> My Orders</a>
                <a href="buyer_messages.php"><i class="fas fa-envelope"></i> Messages</a>
                <a href="buyer_wishlist.php"><i class="fas fa-heart"></i> Wishlist</a>
                <a href="buyer_settings.php"><i class="fas fa-cog"></i> Settings</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <h1>My Orders</h1>
            
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>No orders yet</h3>
                    <p>Start shopping to see your orders here</p>
                    <a href="marketplace.php" class="btn btn-primary">Browse Marketplace</a>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-image">
                                <img src="../assets/images/products/<?php echo htmlspecialchars($order['image_path'] ?? 'placeholder.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($order['product_name']); ?>">
                            </div>
                            <div class="order-details">
                                <h3><?php echo htmlspecialchars($order['product_name']); ?></h3>
                                <div class="order-meta">
                                    <p><strong>Order ID:</strong> #<?php echo $order['id']; ?></p>
                                    <p><strong>Seller:</strong> @<?php echo htmlspecialchars($order['seller_username']); ?></p>
                                    <p><strong>Date:</strong> <?php echo date('M j, Y', strtotime($order['order_date'])); ?></p>
                                    <p><strong>Price:</strong> KSh <?php echo number_format($order['price'], 2); ?></p>
                                    <p><strong>Status:</strong> 
                                        <span class="status-badge <?php echo strtolower($order['status']); ?>">
                                            <?php echo $order['status']; ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="order-actions">
                                <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-view">
                                    View Details
                                </a>
                                <?php if ($order['status'] === 'Pending' || $order['status'] === 'Processing'): ?>
                                    <button class="btn btn-cancel" data-order-id="<?php echo $order['id']; ?>">
                                        Cancel Order
                                    </button>
                                <?php endif; ?>
                                <?php if ($order['status'] === 'Completed'): ?>
                                    <button class="btn btn-review" data-product-id="<?php echo $order['product_id']; ?>">
                                        Leave Review
                                    </button>
                                <?php endif; ?>
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
    // Cancel order functionality
    document.querySelectorAll('.btn-cancel').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            if (confirm('Are you sure you want to cancel this order?')) {
                fetch('cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `order_id=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Order cancelled successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        });
    });

    // Leave review functionality
    document.querySelectorAll('.btn-review').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            window.location.href = `leave_review.php?product_id=${productId}`;
        });
    });
    </script>
</body>
</html>