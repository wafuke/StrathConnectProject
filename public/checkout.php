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
$query = "SELECT c.*, p.name, p.price, p.seller_id, p.image_path 
          FROM cart c
          JOIN products p ON c.product_id = p.id
          WHERE c.buyer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$subtotal = 0;
$item_count = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $item_count += $item['quantity'];
}
$shipping = $item_count > 0 ? 150 : 0; // Flat shipping rate
$total = $subtotal + $shipping;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .checkout-container {
            display: flex;
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .checkout-main {
            flex: 2;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .checkout-sidebar {
            flex: 1;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            align-self: flex-start;
        }
        
        .checkout-step {
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 20px;
        }
        
        .checkout-step-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .step-number {
            background: #FF9900;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 14px;
        }
        
        .step-title {
            font-size: 18px;
            font-weight: bold;
            color: #111;
        }
        
        .address-box {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .address-box:hover {
            border-color: #FF9900;
            background: #F7FEFF;
        }
        
        .address-box.selected {
            border-color: #FF9900;
            background: #F7FEFF;
        }
        
        .payment-method {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-method:hover {
            border-color: #FF9900;
        }
        
        .payment-method.selected {
            border-color: #FF9900;
            background: #F7FEFF;
        }
        
        .payment-logo {
            height: 30px;
            margin-right: 10px;
        }
        
        .order-summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .order-summary-total {
            font-weight: bold;
            font-size: 18px;
            color: #B12704;
        }
        
        .place-order-btn {
            background: #FFD814;
            border: none;
            border-radius: 8px;
            padding: 10px;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }
        
        .place-order-btn:hover {
            background: #F7CA00;
        }
        
        .cart-item {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item-img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-right: 15px;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .cart-item-price {
            color: #B12704;
            font-weight: bold;
        }
        
        .cart-item-seller {
            color: #565959;
            font-size: 14px;
        }
        
        .cart-item-quantity {
            margin-top: 5px;
        }
        
        .gift-option {
            margin-top: 10px;
            font-size: 14px;
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
            <li><a href="buyer_messages.php">Messages</a></li>
            <li><a href="../public/login.php">Logout</a></li>
        </ul>
    </nav>

    <div class="dashboard-container">
        <main class="dashboard-content">
            <div class="checkout-container">
                <div class="checkout-main">
                    <div class="checkout-step">
                        <div class="checkout-step-header">
                            <div class="step-number">1</div>
                            <div class="step-title">Shipping Address</div>
                        </div>
                        <div class="address-box selected">
                            <h4>John Doe</h4>
                            <p>123 University Way</p>
                            <p>Nairobi, Nairobi County 00100</p>
                            <p>Kenya</p>
                            <p>Phone: 254712345678</p>
                        </div>
                        <button class="btn btn-outline">Add new address</button>
                    </div>
                    
                    <div class="checkout-step">
                        <div class="checkout-step-header">
                            <div class="step-number">2</div>
                            <div class="step-title">Payment Method</div>
                        </div>
                        <div class="payment-method selected">
                            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                <img src="../assets/images/mpesa-logo.png" alt="M-Pesa" class="payment-logo">
                                <h4>M-Pesa Mobile Payment</h4>
                            </div>
                            <p>You'll receive an M-Pesa push notification to complete your payment</p>
                            <div class="form-group" style="margin-top: 15px;">
                                <label for="phone">M-Pesa Phone Number</label>
                                <input type="tel" id="phone" name="phone" placeholder="e.g. 254712345678" required
                                       pattern="^254[17]\d{8}$" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="checkout-step">
                        <div class="checkout-step-header">
                            <div class="step-number">3</div>
                            <div class="step-title">Review items</div>
                        </div>
                        <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <img src="../assets/images/products/<?= htmlspecialchars($item['image_path'] ?? 'placeholder.jpg') ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>" class="cart-item-img">
                            <div class="cart-item-details">
                                <div class="cart-item-title"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="cart-item-price">KSh <?= number_format($item['price'], 2) ?></div>
                                <div class="cart-item-seller">Sold by: <?= htmlspecialchars($item['seller_id']) ?></div>
                                <div class="cart-item-quantity">Quantity: <?= $item['quantity'] ?></div>
                                <div class="gift-option">
                                    <input type="checkbox" id="gift-<?= $item['id'] ?>">
                                    <label for="gift-<?= $item['id'] ?>">This is a gift</label>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="checkout-sidebar">
                    <h3>Order Summary</h3>
                    <div class="order-summary-item">
                        <span>Items (<?= $item_count ?>):</span>
                        <span>KSh <?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="order-summary-item">
                        <span>Shipping:</span>
                        <span>KSh <?= number_format($shipping, 2) ?></span>
                    </div>
                    <div class="order-summary-item order-summary-total">
                        <span>Order Total:</span>
                        <span>KSh <?= number_format($total, 2) ?></span>
                    </div>
                    
                    <button id="place-order-btn" class="place-order-btn">
                        Place your order
                    </button>
                    
                    <div style="margin-top: 20px; font-size: 14px; color: #565959;">
                        <p>By placing your order, you agree to StrathConnect's <a href="#">Conditions of Use</a> and <a href="#">Privacy Notice</a>.</p>
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
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing your order...';
        
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
                this.innerHTML = 'Place your order';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            this.disabled = false;
            this.innerHTML = 'Place your order';
        });
    });
    
    // Add click handlers for address and payment method selection
    document.querySelectorAll('.address-box').forEach(box => {
        box.addEventListener('click', function() {
            document.querySelectorAll('.address-box').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
        });
    });
    
    document.querySelectorAll('.payment-method').forEach(method => {
        method.addEventListener('click', function() {
            document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
            this.classList.add('selected');
        });
    });
    </script>
</body>
</html>