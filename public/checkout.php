<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    header("Location: ../public/login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'strathconnect');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get buyer details
$buyer_id = $_SESSION['user_id'];
$buyer_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($buyer_query);
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$buyer_result = $stmt->get_result();
$buyer = $buyer_result->fetch_assoc();

// Get buyer addresses
$addresses = [];
$address_query = "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC";
$stmt = $conn->prepare($address_query);
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$address_result = $stmt->get_result();
$addresses = $address_result->fetch_all(MYSQLI_ASSOC);

// Get cart items with product details
$cart_query = "SELECT c.*, p.name, p.price, p.seller_id, p.image_path, u.username as seller_name 
               FROM cart c
               JOIN products p ON c.product_id = p.id
               JOIN users u ON p.seller_id = u.id
               WHERE c.buyer_id = ?";
$stmt = $conn->prepare($cart_query);
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
        :root {
            --primary: #FF9900;
            --primary-hover: #e68a00;
            --secondary: #f8f9fa;
            --dark: #343a40;
            --light: #f8f9fa;
            --danger: #dc3545;
            --success: #28a745;
            --border: #dee2e6;
            --text-muted: #6c757d;
        }
        
        .checkout-container {
            display: flex;
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            flex-wrap: wrap;
        }
        
        .checkout-main {
            flex: 2;
            min-width: 300px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            padding: 25px;
        }
        
        .checkout-sidebar {
            flex: 1;
            min-width: 300px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            padding: 25px;
            align-self: flex-start;
            position: sticky;
            top: 20px;
        }
        
        .checkout-step {
            margin-bottom: 40px;
            padding-bottom: 25px;
            border-bottom: 1px solid var(--border);
        }
        
        .checkout-step:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .checkout-step-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .step-number {
            background: var(--primary);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .step-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .address-box {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .address-box:hover {
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(255, 153, 0, 0.1);
        }
        
        .address-box.selected {
            border-color: var(--primary);
            background-color: rgba(255, 153, 0, 0.05);
        }
        
        .address-box .default-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--success);
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .payment-method {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-method:hover {
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(255, 153, 0, 0.1);
        }
        
        .payment-method.selected {
            border-color: var(--primary);
            background-color: rgba(255, 153, 0, 0.05);
        }
        
        .payment-logo {
            height: 30px;
            margin-right: 15px;
            object-fit: contain;
        }
        
        .order-summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }
        
        .order-summary-total {
            font-weight: bold;
            font-size: 18px;
            color: var(--danger);
            margin-top: 15px;
        }
        
        .place-order-btn {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 14px;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .place-order-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 153, 0, 0.3);
        }
        
        .place-order-btn:disabled {
            background: var(--text-muted);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .cart-item {
            display: flex;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
            gap: 15px;
        }
        
        .cart-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .cart-item-img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid var(--border);
            padding: 5px;
            background: white;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .cart-item-price {
            color: var(--danger);
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .cart-item-seller {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .cart-item-quantity {
            color: var(--text-muted);
            font-size: 14px;
        }
        
        .gift-option {
            margin-top: 10px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .empty-cart-icon {
            font-size: 80px;
            color: var(--border);
            margin-bottom: 20px;
        }
        
        .empty-cart-message {
            font-size: 20px;
            margin-bottom: 25px;
            color: var(--dark);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeeba;
            color: #856404;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 153, 0, 0.2);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            gap: 8px;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 153, 0, 0.3);
        }
        
        @media (max-width: 768px) {
            .checkout-container {
                flex-direction: column;
                gap: 20px;
            }
            
            .checkout-sidebar {
                position: static;
                order: -1;
            }
            
            .address-box, .payment-method {
                padding: 15px;
            }
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
            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <div class="empty-cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h2 class="empty-cart-message">Your cart is empty</h2>
                    <a href="marketplace.php" class="btn btn-primary">
                        <i class="fas fa-store"></i> Continue Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="checkout-container">
                    <div class="checkout-main">
                        <div class="checkout-step">
                            <div class="checkout-step-header">
                                <div class="step-number">1</div>
                                <div class="step-title">Shipping Address</div>
                            </div>
                            
                            <?php if (!empty($addresses)): ?>
                                <?php foreach ($addresses as $address): ?>
                                    <div class="address-box <?= $address['is_default'] ? 'selected' : '' ?>" 
                                         data-address-id="<?= $address['id'] ?>">
                                        <?php if ($address['is_default']): ?>
                                            <span class="default-badge">Default</span>
                                        <?php endif; ?>
                                        <h4><?= htmlspecialchars($address['full_name']) ?></h4>
                                        <p><?= htmlspecialchars($address['address_line1']) ?></p>
                                        <?php if (!empty($address['address_line2'])): ?>
                                            <p><?= htmlspecialchars($address['address_line2']) ?></p>
                                        <?php endif; ?>
                                        <p><?= htmlspecialchars($address['city']) ?>, <?= htmlspecialchars($address['county']) ?> <?= htmlspecialchars($address['postal_code']) ?></p>
                                        <p><?= htmlspecialchars($address['country']) ?></p>
                                        <p><i class="fas fa-phone"></i> <?= htmlspecialchars($address['phone']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-circle"></i> You don't have any saved addresses. Please add one to continue.
                                </div>
                            <?php endif; ?>
                            
                            <a href="manage_addresses.php" class="btn btn-outline">
                                <i class="fas fa-plus"></i> Manage Addresses
                            </a>
                        </div>
                        
                        <div class="checkout-step">
                            <div class="checkout-step-header">
                                <div class="step-number">2</div>
                                <div class="step-title">Payment Method</div>
                            </div>
                            <div class="payment-method selected" data-method="mpesa">
                                <div style="display: flex; align-items: center; margin-bottom: 15px;">
                                    <img src="../assets/images/mpesa-logo.png" alt="M-Pesa" class="payment-logo">
                                    <h4>M-Pesa Mobile Payment</h4>
                                </div>
                                <p>You'll receive an M-Pesa push notification to complete your payment</p>
                                <div class="form-group" style="margin-top: 15px;">
                                    <label for="phone">M-Pesa Phone Number</label>
                                    <input type="tel" id="phone" name="phone" 
                                           value="<?= htmlspecialchars($buyer['contact_number'] ?? '') ?>" 
                                           placeholder="e.g. 254712345678" required
                                           pattern="^254[17]\d{8}$" class="form-control">
                                    <small class="text-muted">Format: 2547XXXXXXXX</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="checkout-step">
                            <div class="checkout-step-header">
                                <div class="step-number">3</div>
                                <div class="step-title">Review Items</div>
                            </div>
                            <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <img src="../assets/images/products/<?= htmlspecialchars($item['image_path'] ?? 'placeholder.jpg') ?>" 
                                     alt="<?= htmlspecialchars($item['name']) ?>" class="cart-item-img">
                                <div class="cart-item-details">
                                    <div class="cart-item-title"><?= htmlspecialchars($item['name']) ?></div>
                                    <div class="cart-item-price">KSh <?= number_format($item['price'], 2) ?></div>
                                    <div class="cart-item-seller">Sold by: <?= htmlspecialchars($item['seller_name']) ?></div>
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
                        <h3 style="margin-bottom: 20px;">Order Summary</h3>
                        <div class="order-summary-item">
                            <span>Items (<?= $item_count ?>):</span>
                            <span>KSh <?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="order-summary-item">
                            <span>Shipping:</span>
                            <span>KSh <?= number_format($shipping, 2) ?></span>
                        </div>
                        <div class="order-summary-item" style="margin-top: 20px;">
                            <span><strong>Total:</strong></span>
                            <span class="order-summary-total">KSh <?= number_format($total, 2) ?></span>
                        </div>
                        
                        <button id="place-order-btn" class="place-order-btn" <?= empty($addresses) ? 'disabled' : '' ?>>
                            <i class="fas fa-lock"></i> Place Your Order
                        </button>
                        
                        <div style="margin-top: 25px; font-size: 14px; color: var(--text-muted);">
                            <p><i class="fas fa-lock"></i> Secure checkout</p>
                            <p>By placing your order, you agree to StrathConnect's <a href="#" style="color: var(--primary);">Conditions of Use</a> and <a href="#" style="color: var(--primary);">Privacy Notice</a>.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <footer class="footer">
        <p>&copy; <?= date('Y') ?> StrathConnect. All rights reserved.</p>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const placeOrderBtn = document.getElementById('place-order-btn');
        
        if (placeOrderBtn) {
            placeOrderBtn.addEventListener('click', function() {
                const phone = document.getElementById('phone').value;
                const selectedAddress = document.querySelector('.address-box.selected');
                
                if (!selectedAddress) {
                    alert('Please select a shipping address');
                    return;
                }
                
                if (!phone || !phone.match(/^254[17]\d{8}$/)) {
                    alert('Please enter a valid M-Pesa phone number in format 2547XXXXXX');
                    return;
                }
                
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing your order...';
                
                const addressId = selectedAddress.dataset.addressId;
                const paymentMethod = document.querySelector('.payment-method.selected').dataset.method;
                
                // Get gift options
                const giftOptions = [];
                document.querySelectorAll('.gift-option input[type="checkbox"]').forEach(checkbox => {
                    if (checkbox.checked) {
                        const itemId = checkbox.id.replace('gift-', '');
                        giftOptions.push(itemId);
                    }
                });
                
                fetch('process_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        phone: phone,
                        address_id: addressId,
                        payment_method: paymentMethod,
                        gift_options: giftOptions
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'order_success.php?order_id=' + data.order_id;
                    } else {
                        alert('Error: ' + data.message);
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-lock"></i> Place Your Order';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-lock"></i> Place Your Order';
                });
            });
        }
        
        // Add click handlers for address and payment method selection
        document.querySelectorAll('.address-box').forEach(box => {
            box.addEventListener('click', function() {
                document.querySelectorAll('.address-box').forEach(b => b.classList.remove('selected'));
                this.classList.add('selected');
                if (placeOrderBtn) placeOrderBtn.disabled = false;
            });
        });
        
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                this.classList.add('selected');
            });
        });
    });
    </script>
</body>
</html>