<?php
session_start();

if (!isset($_SESSION['user_id'])){
    header("Location: ../public/login.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'strathconnect');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get cart items with product details
$buyer_id = $_SESSION['user_id'];
$query = "SELECT c.*, p.name, p.price, p.image_path, p.seller_id, u.username as seller_username 
          FROM cart c
          JOIN products p ON c.product_id = p.id
          JOIN users u ON p.seller_id = u.id
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
    <title>Shopping Cart - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .cart-title {
            font-size: 28px;
            font-weight: 400;
            color: #0F1111;
        }
        .price-notice {
            color: #565959;
            font-size: 14px;
        }
        .cart-layout {
            display: flex;
            gap: 20px;
        }
        .cart-items-container {
            flex-grow: 1;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .cart-item {
            display: flex;
            padding: 20px 0;
            border-bottom: 1px solid #ddd;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .cart-item-image {
            width: 180px;
            height: 180px;
            object-fit: contain;
            margin-right: 20px;
        }
        .cart-item-details {
            flex-grow: 1;
        }
        .cart-item-title {
            font-size: 18px;
            color: #0066c0;
            margin-bottom: 5px;
        }
        .cart-item-title:hover {
            text-decoration: underline;
            color: #c45500;
        }
        .cart-item-seller {
            font-size: 14px;
            color: #555;
            margin-bottom: 10px;
        }
        .cart-item-price {
            font-size: 18px;
            font-weight: bold;
            color: #B12704;
            margin-bottom: 10px;
        }
        .cart-item-stock {
            color: #007600;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .cart-item-actions {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
        }
        .quantity-select {
            width: 80px;
            padding: 6px;
            border-radius: 4px;
            border: 1px solid #ddd;
            background: #F0F2F2;
        }
        .action-link {
            color: #0066c0;
            cursor: pointer;
            font-size: 14px;
        }
        .action-link:hover {
            text-decoration: underline;
            color: #c45500;
        }
        .action-divider {
            color: #ddd;
        }
        .cart-item-subtotal {
            font-weight: bold;
            min-width: 100px;
            text-align: right;
        }
        .cart-summary-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            padding: 20px;
            width: 300px;
            height: fit-content;
        }
        .summary-title {
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .summary-total {
            font-weight: bold;
            font-size: 18px;
            border-top: 1px solid #ddd;
            padding-top: 15px;
            margin-top: 15px;
        }
        .checkout-btn {
            width: 100%;
            padding: 10px;
            background: #FFD814;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 15px;
        }
        .checkout-btn:hover {
            background: #F7CA00;
        }
        .secure-checkout {
            font-size: 12px;
            color: #555;
            margin-top: 15px;
            text-align: center;
        }
        .empty-cart {
            text-align: center;
            padding: 50px 0;
        }
        .empty-cart-icon {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }
        .empty-cart-title {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .empty-cart-message {
            margin-bottom: 20px;
            color: #555;
        }
        .browse-btn {
            background: #FFD814;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            color: #0F1111;
            font-weight: bold;
            display: inline-block;
        }
        .browse-btn:hover {
            background: #F7CA00;
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

    <div class="cart-container">
        <div class="cart-header">
            <h1 class="cart-title">Shopping Cart</h1>
            <div class="price-notice">Price</div>
        </div>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h2 class="empty-cart-title">Your StrathConnect Cart is empty</h2>
                <p class="empty-cart-message">Your shopping cart is waiting. Give it purpose!</p>
                <a href="marketplace.php" class="browse-btn">Browse Marketplace</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-items-container">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <img src="../assets/images/products/<?= htmlspecialchars($item['image_path'] ?? 'placeholder.jpg') ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                 class="cart-item-image">
                            
                            <div class="cart-item-details">
                                <h3 class="cart-item-title"><?= htmlspecialchars($item['name']) ?></h3>
                                <div class="cart-item-seller">Sold by: <?= htmlspecialchars($item['seller_username']) ?></div>
                                <div class="cart-item-price">KSh <?= number_format($item['price'], 2) ?></div>
                                <div class="cart-item-stock">In Stock</div>
                                
                                <div class="cart-item-actions">
                                    <select class="quantity-select" data-cart-id="<?= $item['id'] ?>">
                                        <?php for ($i = 1; $i <= 10; $i++): ?>
                                            <option value="<?= $i ?>" <?= $i == $item['quantity'] ? 'selected' : '' ?>>
                                                Qty: <?= $i ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                    
                                    <span class="action-divider">|</span>
                                    <span class="action-link delete-btn" data-cart-id="<?= $item['id'] ?>">Delete</span>
                                    <span class="action-divider">|</span>
                                    <span class="action-link save-btn">Save for later</span>
                                    <span class="action-divider">|</span>
                                    <span class="action-link compare-btn">Compare with similar items</span>
                                </div>
                            </div>
                            
                            <div class="cart-item-subtotal">
                                KSh <?= number_format($item['price'] * $item['quantity'], 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary-container">
                    <h3 class="summary-title">Order Summary</h3>
                    <div class="summary-row">
                        <span>Subtotal (<?= count($cart_items) ?> item<?= count($cart_items) > 1 ? 's' : '' ?>):</span>
                        <span>KSh <?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span>KSh <?= number_format($shipping, 2) ?></span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Order Total:</span>
                        <span>KSh <?= number_format($total, 2) ?></span>
                    </div>
                    
                    <button id="checkout-btn" class="checkout-btn">
                        Proceed to Checkout
                    </button>
                    
                    <div class="secure-checkout">
                        <i class="fas fa-lock"></i> Secure checkout
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; <?= date('Y') ?> StrathConnect. All rights reserved.</p>
    </footer>

    <script>
    // Update quantity
    document.querySelectorAll('.quantity-select').forEach(select => {
        select.addEventListener('change', function() {
            const cartId = this.dataset.cartId;
            const quantity = this.value;
            
            fetch('update_cart_quantity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `cart_id=${cartId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });
    });

    // Delete item
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Remove this item from your cart?')) {
                const cartId = this.dataset.cartId;
                
                fetch('remove_from_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `cart_id=${cartId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        });
    });

    // Checkout button
    document.getElementById('checkout-btn')?.addEventListener('click', function() {
        window.location.href = 'checkout.php';
    });
    </script>
</body>
</html>