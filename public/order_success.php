<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header("Location: marketplace.php");
    exit();
}

$order_id = intval($_GET['order_id']);

// Database connection
$conn = new mysqli('localhost', 'root', '', 'strathconnect');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Verify order belongs to user
$query = "SELECT o.*, a.full_name, a.address_line1, a.address_line2, a.city, a.county, a.postal_code 
          FROM orders o
          JOIN user_addresses a ON o.address_id = a.id
          WHERE o.id = ? AND o.buyer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: marketplace.php");
    exit();
}

$order = $result->fetch_assoc();

// Get order items
$items_query = "SELECT oi.*, p.name, p.image_path 
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .order-success-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .success-icon {
            color: #28a745;
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .success-title {
            font-size: 28px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .success-message {
            font-size: 18px;
            margin-bottom: 30px;
            color: #555;
        }
        
        .order-details {
            text-align: left;
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .order-item {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            gap: 15px;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item-img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border: 1px solid #eee;
            border-radius: 4px;
        }
        
        .order-summary {
            margin-top: 30px;
            font-size: 18px;
        }
        
        .btn-container {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
    </style>
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
        <main class="dashboard-content">
            <div class="order-success-container">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 class="success-title">Order Confirmed!</h1>
                <p class="success-message">Thank you for your purchase. Your order #<?= $order_id ?> has been received.</p>
                
                <div class="order-details">
                    <h3>Order Details</h3>
                    <p><strong>Order Number:</strong> #<?= $order_id ?></p>
                    <p><strong>Payment Method:</strong> M-Pesa (<?= $order['mpesa_code'] ?>)</p>
                    <p><strong>Total Amount:</strong> KSh <?= number_format($order['total_amount'], 2) ?></p>
                    
                    <h4 style="margin-top: 20px;">Shipping Address:</h4>
                    <p><?= htmlspecialchars($order['full_name']) ?></p>
                    <p><?= htmlspecialchars($order['address_line1']) ?></p>
                    <?php if (!empty($order['address_line2'])): ?>
                        <p><?= htmlspecialchars($order['address_line2']) ?></p>
                    <?php endif; ?>
                    <p><?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['county']) ?> <?= htmlspecialchars($order['postal_code']) ?></p>
                    
                    <h4 style="margin-top: 20px;">Items Ordered:</h4>
                    <?php foreach ($items as $item): ?>
                        <div class="order-item">
                            <img src="../assets/images/products/<?= htmlspecialchars($item['image_path'] ?? 'placeholder.jpg') ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>" class="order-item-img">
                            <div>
                                <p><strong><?= htmlspecialchars($item['name']) ?></strong></p>
                                <p>KSh <?= number_format($item['price'], 2) ?> × <?= $item['quantity'] ?></p>
                                <?php if ($item['is_gift']): ?>
                                    <p><i class="fas fa-gift"></i> Gift wrapped</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="order-summary">
                    <p><strong>Order Total:</strong> KSh <?= number_format($order['total_amount'], 2) ?></p>
                </div>
                
                <div class="btn-container">
                    <a href="buyer_orders.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> View All Orders
                    </a>
                    <a href="marketplace.php" class="btn btn-outline">
                        <i class="fas fa-store"></i> Continue Shopping
                    </a>
                </div>
            </div>
        </main>
    </div>

    <footer class="footer">
        <p>&copy; <?= date('Y') ?> StrathConnect. All rights reserved.</p>
    </footer>
</body>
</html>