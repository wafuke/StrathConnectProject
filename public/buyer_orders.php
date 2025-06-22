<?php
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

// Verify buyer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    header("Location: ../public/login.php");
    exit();
}

// Get buyer's orders with product details
$buyer_id = $_SESSION['user_id'];
$query = "SELECT o.*, p.name as product_name, p.image_path, u.username as seller_username 
          FROM orders o
          JOIN products p ON o.product_id = p.id
          JOIN users u ON o.seller_id = u.id
          WHERE o.buyer_id = ?
          ORDER BY o.order_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .order-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .order-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-weight: bold;
        }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-processing { background-color: #cce5ff; color: #004085; }
        .status-shipped { background-color: #d4edda; color: #155724; }
        .status-delivered { background-color: #d1ecf1; color: #0c5460; }
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
    </style>
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

    <div class="container">
        <h1>My Orders</h1>
        
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>No orders yet</h3>
                <p>Your completed orders will appear here</p>
                <a href="marketplace.php" class="btn">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <span>Order #<?= $order['id'] ?></span>
                        <span class="order-status status-<?= strtolower($order['status']) ?>">
                            <?= $order['status'] ?>
                        </span>
                    </div>
                    
                    <div class="order-product">
                        <img src="../assets/images/products/<?= htmlspecialchars($order['image_path']) ?>" 
                             alt="<?= htmlspecialchars($order['product_name']) ?>" width="80">
                        <div>
                            <h3><?= htmlspecialchars($order['product_name']) ?></h3>
                            <p>Sold by: <?= htmlspecialchars($order['seller_username']) ?></p>
                            <p>KSh <?= number_format($order['price'], 2) ?> × <?= $order['quantity'] ?></p>
                            <p>Total: KSh <?= number_format($order['price'] * $order['quantity'], 2) ?></p>
                        </div>
                    </div>
                    
                    <div class="order-footer">
                        <small>Ordered on <?= date('M j, Y', strtotime($order['order_date'])) ?></small>
                        
                        <div class="order-actions">
                            <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn">View Details</a>
                            
                            <?php if ($order['status'] === 'Pending' || $order['status'] === 'Processing'): ?>
                                <button class="btn btn-danger cancel-order" 
                                        data-order-id="<?= $order['id'] ?>">
                                    Cancel Order
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($order['status'] === 'Completed'): ?>
                                <button class="btn btn-primary leave-review" 
                                        data-product-id="<?= $order['product_id'] ?>"
                                        data-order-id="<?= $order['id'] ?>">
                                    Leave Review
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // Cancel order functionality
    document.querySelectorAll('.cancel-order').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to cancel this order?')) {
                const orderId = this.dataset.orderId;
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
                        alert('Order cancelled successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        });
    });

    // Leave review functionality
    document.querySelectorAll('.leave-review').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const orderId = this.dataset.orderId;
            window.location.href = `leave_review.php?product_id=${productId}&order_id=${orderId}`;
        });
    });
    </script>
</body>
</html>