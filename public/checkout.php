<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'strathconnect');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get cart items with product details
$buyer_id = $_SESSION['user_id'];
$query = "SELECT c.*, p.name, p.price, p.seller_id 
          FROM cart c
          JOIN products p ON c.product_id = p.id
          WHERE c.buyer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = 0; // Could be calculated based on location
$total = $subtotal + $shipping;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - StrathConnect</title>
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
        <main class="dashboard-content">
            <h1>Checkout</h1>
            
            <div class="checkout-container">
                <div class="checkout-form">
                    <h2>Payment Method</h2>
                    
                    <div class="payment-methods">
                        <div class="payment-method active" data-method="mpesa">
                            <i class="fas fa-mobile-alt"></i>
                            <span>M-Pesa</span>
                        </div>
                    </div>
                    
                    <div class="payment-details mpesa-details">
                        <p>You will receive an M-Pesa push notification to complete payment</p>
                        <div class="form-group">
                            <label for="phone">M-Pesa Phone Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="e.g. 254712345678" required>
                        </div>
                    </div>
                    
                    <button id="place-order-btn" class="btn btn-primary">
                        Complete Order (KSh <?= number_format($total, 2) ?>)
                    </button>
                </div>
                
                <div class="order-summary">
                    <h2>Order Summary</h2>
                    <div class="summary-items">
                        <?php foreach ($cart_items as $item): ?>
                        <div class="summary-item">
                            <span><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></span>
                            <span>KSh <?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="summary-totals">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span>KSh <?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping:</span>
                            <span>KSh <?= number_format($shipping, 2) ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span>KSh <?= number_format($total, 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.getElementById('place-order-btn').addEventListener('click', function() {
        const phone = document.getElementById('phone').value;
        
        if (!phone || !phone.match(/^254[17]\d{8}$/)) {
            alert('Please enter a valid M-Pesa phone number in format 2547XXXXXX');
            return;
        }
        
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        fetch('process_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `phone=${encodeURIComponent(phone)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'order_success.php?order_id=' + data.order_id;
            } else {
                alert('Error: ' + data.message);
                this.disabled = false;
                this.innerHTML = 'Complete Order (KSh <?= number_format($total, 2) ?>)';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            this.disabled = false;
            this.innerHTML = 'Complete Order (KSh <?= number_format($total, 2) ?>)';
        });
    });
    </script>
</body>
</html>