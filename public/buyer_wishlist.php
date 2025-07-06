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

// Get buyer's wishlist with additional product details
$buyer_id = $_SESSION['user_id'];
$query = "SELECT w.id as wishlist_id, p.*, u.username as seller_username, 
          (SELECT COUNT(*) FROM cart WHERE buyer_id = ? AND product_id = p.id) as in_cart
          FROM wishlist w
          JOIN products p ON w.product_id = p.id
          JOIN users u ON p.seller_id = u.id
          WHERE w.buyer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $buyer_id, $buyer_id);
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
    <style>
        .wishlist-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .wishlist-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        
        .wishlist-title {
            font-size: 28px;
            font-weight: bold;
            color: #111;
        }
        
        .wishlist-count {
            color: #565959;
            font-size: 14px;
        }
        
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .wishlist-item {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px;
            transition: all 0.3s;
            position: relative;
        }
        
        .wishlist-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .wishlist-item-img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            margin-bottom: 15px;
        }
        
        .wishlist-item-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #111;
            font-size: 16px;
            height: 40px;
            overflow: hidden;
        }
        
        .wishlist-item-price {
            color: #B12704;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .wishlist-item-seller {
            color: #565959;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .wishlist-item-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .btn-add-to-cart {
            background: #FFD814;
            border: none;
            border-radius: 8px;
            padding: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-add-to-cart:hover {
            background: #F7CA00;
        }
        
        .btn-add-to-cart.added {
            background: #cccccc;
            cursor: not-allowed;
        }
        
        .btn-remove {
            background: none;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .btn-remove:hover {
            background: #f0f0f0;
        }
        
        .empty-wishlist {
            text-align: center;
            padding: 50px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .empty-icon {
            font-size: 50px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-title {
            font-size: 24px;
            margin-bottom: 10px;
            color: #111;
        }
        
        .empty-message {
            color: #565959;
            margin-bottom: 20px;
        }
        
        .btn-browse {
            background: #FFD814;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #111;
            display: inline-block;
        }
        
        .btn-browse:hover {
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
                
                <a href="buyer_wishlist.php" class="active"><i class="fas fa-heart"></i> Wishlist</a>
                <a href="buyer_settings.php"><i class="fas fa-cog"></i> Settings</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <div class="wishlist-container">
                <div class="wishlist-header">
                    <div>
                        <h1 class="wishlist-title">My Wishlist</h1>
                        <p class="wishlist-count"><?php echo count($wishlist); ?> item(s)</p>
                    </div>
                    <a href="marketplace.php" class="btn-browse">Continue Shopping</a>
                </div>
                
                <?php if (empty($wishlist)): ?>
                    <div class="empty-wishlist">
                        <div class="empty-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3 class="empty-title">Your Wishlist is Empty</h3>
                        <p class="empty-message">Save your favorite items here to view or buy them later</p>
                        <a href="marketplace.php" class="btn-browse">Browse Marketplace</a>
                    </div>
                <?php else: ?>
                    <div class="wishlist-grid">
                        <?php foreach ($wishlist as $item): ?>
                            <div class="wishlist-item">
                                <img src="../assets/images/products/<?php echo htmlspecialchars($item['image_path'] ?? 'placeholder.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" class="wishlist-item-img">
                                <h3 class="wishlist-item-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <div class="wishlist-item-price">KSh <?php echo number_format($item['price'], 2); ?></div>
                                <div class="wishlist-item-seller">Sold by: <?php echo htmlspecialchars($item['seller_username']); ?></div>
                                <div class="wishlist-item-actions">
                                    <button class="btn-add-to-cart <?php echo $item['in_cart'] ? 'added' : ''; ?>" 
                                            data-product-id="<?php echo $item['id']; ?>"
                                            <?php echo $item['in_cart'] ? 'disabled' : ''; ?>>
                                        <?php echo $item['in_cart'] ? 'Added to Cart' : 'Add to Cart'; ?>
                                    </button>
                                    <button class="btn-remove" data-wishlist-id="<?php echo $item['wishlist_id']; ?>">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> StrathConnect. All rights reserved.</p>
    </footer>

    <script>
    // Add to cart from wishlist
    document.querySelectorAll('.btn-add-to-cart:not(.added)').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const button = this;
            
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            
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
                    button.classList.add('added');
                    button.innerHTML = 'Added to Cart';
                    alert('Product added to cart!');
                } else {
                    button.disabled = false;
                    button.innerHTML = 'Add to Cart';
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                button.disabled = false;
                button.innerHTML = 'Add to Cart';
                alert('An error occurred. Please try again.');
            });
        });
    });

    // Remove from wishlist
    document.querySelectorAll('.btn-remove').forEach(button => {
        button.addEventListener('click', function() {
            const wishlistId = this.getAttribute('data-wishlist-id');
            const item = this.closest('.wishlist-item');
            
            if (confirm('Remove this item from your wishlist?')) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';
                
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
                        // Add fade out animation before removing
                        item.style.opacity = '0';
                        setTimeout(() => {
                            item.remove();
                            // Update item count
                            const countElement = document.querySelector('.wishlist-count');
                            const currentCount = parseInt(countElement.textContent);
                            countElement.textContent = (currentCount - 1) + ' item(s)';
                            
                            // Show empty state if last item
                            if (currentCount - 1 === 0) {
                                document.querySelector('.wishlist-grid').innerHTML = `
                                    <div class="empty-wishlist">
                                        <div class="empty-icon">
                                            <i class="fas fa-heart"></i>
                                        </div>
                                        <h3 class="empty-title">Your Wishlist is Empty</h3>
                                        <p class="empty-message">Save your favorite items here to view or buy them later</p>
                                        <a href="marketplace.php" class="btn-browse">Browse Marketplace</a>
                                    </div>
                                `;
                            }
                        }, 300);
                    } else {
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-trash"></i> Remove';
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-trash"></i> Remove';
                    alert('An error occurred. Please try again.');
                });
            }
        });
    });
    </script>
</body>
</html>