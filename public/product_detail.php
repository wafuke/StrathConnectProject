<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Verify buyer is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'strathconnect');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get product ID
if (!isset($_GET['id'])) {
    header("Location: marketplace.php");
    exit();
}

$product_id = intval($_GET['id']);
$buyer_id = $_SESSION['user_id'];

// Get product details
$query = "SELECT p.*, u.username as seller_username, 
          (SELECT COUNT(*) FROM wishlist WHERE product_id = p.id AND buyer_id = ?) as in_wishlist
          FROM products p
          JOIN users u ON p.seller_id = u.id
          WHERE p.id = ? AND p.is_approved = 1";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("ii", $buyer_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: marketplace.php");
    exit();
}

$product = $result->fetch_assoc();
$stmt->close();

// Get related products
$related_query = "SELECT p.* FROM products p 
                 WHERE p.category = ? AND p.id != ? AND p.is_approved = 1 
                 LIMIT 4";
$stmt = $conn->prepare($related_query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("si", $product['category'], $product_id);
$stmt->execute();
$related_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Your existing CSS styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .product-header {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .product-images {
            flex: 1;
        }
        .product-info {
            flex: 1;
        }
        .main-image {
            width: 100%;
            max-height: 500px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .price {
            font-size: 24px;
            color: #B12704;
            font-weight: bold;
            margin: 15px 0;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            margin: 25px 0;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #FFD700;
            color: #000;
            border: none;
        }
        .btn-primary:hover {
            background: #FFC000;
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #000;
            border: none;
        }
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        .related-products {
            margin-top: 50px;
        }
        .related-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .product-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .product-card-body {
            padding: 15px;
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
            <li><a href="buyer_orders.php" class="active">My Orders</a></li>
            <li><a href="buyer_messages.php">Messages</a></li>
            <li><a href="../public/login.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="product-header">
            <div class="product-images">
                <img src="../assets/images/products/<?php echo htmlspecialchars($product['image_path'] ?? 'default.jpg'); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     class="main-image">
            </div>
            
            <div class="product-info">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="seller-info">
                    Sold by: <strong><?php echo htmlspecialchars($product['seller_username']); ?></strong>
                </div>
                <div class="price">KSh <?php echo number_format($product['price'], 2); ?></div>
                
                <div class="product-description">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-primary add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                    <button class="btn btn-secondary <?php echo $product['in_wishlist'] ? 'remove-from-wishlist' : 'add-to-wishlist'; ?>" 
                            data-product-id="<?php echo $product['id']; ?>">
                        <i class="fas fa-heart"></i> 
                        <?php echo $product['in_wishlist'] ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>
                    </button>
                </div>
            </div>
        </div>

        <?php if (!empty($related_products)): ?>
        <div class="related-products">
            <h2>Related Products</h2>
            <div class="related-grid">
                <?php foreach ($related_products as $related): ?>
                    <div class="product-card">
                        <img src="../assets/images/products/<?php echo htmlspecialchars($related['image_path'] ?? 'default.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($related['name']); ?>">
                        <div class="product-card-body">
                            <h3><?php echo htmlspecialchars($related['name']); ?></h3>
                            <div class="price">KSh <?php echo number_format($related['price'], 2); ?></div>
                            <a href="product_detail.php?id=<?php echo $related['id']; ?>" class="btn btn-secondary">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> StrathConnect. All rights reserved.</p>
    </footer>


    <script>
    // Your existing JavaScript code
    document.querySelector('.add-to-cart')?.addEventListener('click', function() {
        const button = this;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        button.disabled = true;
        
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${button.dataset.productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.innerHTML = '<i class="fas fa-check"></i> Added!';
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 1500);
            } else {
                alert('Error: ' + data.message);
                button.innerHTML = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to add to cart. Check console for details.');
            button.innerHTML = originalText;
            button.disabled = false;
        });
    });

    document.querySelector('.add-to-wishlist, .remove-from-wishlist')?.addEventListener('click', function() {
        const button = this;
        const isAdd = button.classList.contains('add-to-wishlist');
        const action = isAdd ? 'add_to_wishlist' : 'remove_from_wishlist';
        
        fetch(action + '.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${button.dataset.productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to update wishlist. Check console for details.');
        });
    });
    </script>
</body>
</html>