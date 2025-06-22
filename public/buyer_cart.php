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
            <h1>Shopping Cart</h1>
            
            <?php if (empty($cart_items)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your cart is empty</h3>
                    <p>Add some products to get started</p>
                    <a href="marketplace.php" class="btn btn-primary">Browse Marketplace</a>
                </div>
            <?php else: ?>
                <div class="cart-items">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td>
                                    <img src="../assets/images/products/<?= htmlspecialchars($item['image_path'] ?? 'placeholder.jpg') ?>" 
                                         alt="<?= htmlspecialchars($item['name']) ?>" width="80">
                                    <div>
                                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                                        <p>Sold by: <?= htmlspecialchars($item['seller_username']) ?></p>
                                    </div>
                                </td>
                                <td>KSh <?= number_format($item['price'], 2) ?></td>
                                <td>
                                    <input type="number" class="quantity-input" 
                                           value="<?= $item['quantity'] ?>" min="1" 
                                           data-cart-id="<?= $item['id'] ?>">
                                </td>
                                <td>KSh <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                <td>
                                    <button class="btn-remove" data-cart-id="<?= $item['id'] ?>">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="cart-summary">
                        <div class="summary-card">
                            <h3>Order Summary</h3>
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
                            <button id="checkout-btn" class="btn btn-primary">
                                Proceed to Checkout
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
    // Update quantity
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
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

    // Remove item
    document.querySelectorAll('.btn-remove').forEach(button => {
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