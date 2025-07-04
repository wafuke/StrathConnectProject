<?php
session_start();

// Verify buyer is logged in
if (!isset($_SESSION['user_id'])) {
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

// Get user profile data
$user_id = $_SESSION['user_id'];
$user_query = "SELECT username, profile_pic FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

// Get search and filter parameters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query
$query = "SELECT p.*, u.username as seller_username 
          FROM products p 
          JOIN users u ON p.seller_id = u.id 
          WHERE p.is_approved = 1";

if (!empty($search)) {
    $query .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%')";
}

if (!empty($category) && $category !== 'all') {
    $query .= " AND p.category = '$category'";
}

switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'popular':
        $query .= " ORDER BY p.created_at DESC";
        break;
    default:
        $query .= " ORDER BY p.created_at DESC";
}

$result = $conn->query($query);
$products = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$categories = [];
$cat_query = "SELECT DISTINCT category FROM products WHERE is_approved = 1";
$cat_result = $conn->query($cat_query);
if ($cat_result) {
    $categories = $cat_result->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Marketplace - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .add-to-cart {
        background-color: #003366;
        color: #fff;
        border: none;
        padding: 10px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        transition: background 0.3s ease, transform 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .add-to-cart i {
        font-size: 16px;
    }
    .add-to-cart:hover {
        background-color: #004080;
        transform: translateY(-2px);
    }
    .sidebar .add-to-cart {
        width: 100%;
        padding: 12px;
        font-size: 16px;
    }
    .form-control {
        padding: 8px 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        width: 100%;
    }
    .search-filters {
        margin-bottom: 20px;
    }
    .filter-form {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .search-bar-row {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .search-bar-row input[type="text"] {
        flex-grow: 1;
    }
    .cart-summary {
        margin-left: auto;
    }
    .filter-options-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .seller-info img {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 6px;
    }
    .seller-info {
        display: flex;
        align-items: center;
        margin-top: 5px;
    }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">StrathConnect</div>
        <ul class="nav-links">
            <li><a href="marketplace.php" class="active">Marketplace</a></li>
            <li><a href="services.php">Services</a></li>
            <li><a href="buyer_orders.php">My Orders</a></li>
            <li><a href="../public/login.php">Logout</a></li>
        </ul>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="profile-summary">
                <img src="<?php echo htmlspecialchars($user_data['profile_pic'] ?? '../assets/images/profile-placeholder.png'); ?>" alt="Profile" class="profile-pic">
                <h3><?php echo htmlspecialchars($user_data['username'] ?? 'Buyer'); ?></h3>
                <p>@<?php echo htmlspecialchars($user_data['username'] ?? 'username'); ?></p>
            </div>
            <nav class="sidebar-nav">
                <a href="buyer_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="marketplace.php" class="active"><i class="fas fa-store"></i> Marketplace</a>
                <a href="services.php"><i class="fas fa-concierge-bell"></i> Services</a>
                <a href="buyer_orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a>
                <a href="buyer_wishlist.php"><i class="fas fa-heart"></i> Wishlist</a>
                <a href="buyer_settings.php"><i class="fas fa-cog"></i> Settings</a>
            </nav>
            
        </aside>

        <main class="dashboard-content">
            <h1>Marketplace</h1>

            <section class="search-filters">
                <form method="GET" class="filter-form">
                    <div class="search-bar-row">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products..." class="form-control">
                        <div class="cart-summary">
                            <a href="buyer_cart.php" class="cart-link">
                                <i class="fas fa-shopping-cart"></i>
                                <span class="cart-count">0</span>
                            </a>
                        </div>
                    </div>
                    <div class="filter-options-row">
                        <div class="filter-group">
                            <label for="category">Category:</label>
                            <select name="category" id="category" class="form-control">
                                <option value="all">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category']); ?>"
                                        <?php echo ($category === $cat['category']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="sort">Sort By:</label>
                            <select name="sort" id="sort" class="form-control">
                                <option value="newest" <?php echo ($sort === 'newest') ? 'selected' : ''; ?>>Newest</option>
                                <option value="price_low" <?php echo ($sort === 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo ($sort === 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="popular" <?php echo ($sort === 'popular') ? 'selected' : ''; ?>>Most Popular</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="btn-apply">Apply Filters</button>
                        </div>
                    </div>
                </form>
            </section>

            <section class="products-list">
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h3>No products found</h3>
                        <p>Try adjusting your search or filters</p>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <img src="../assets/images/products/<?php echo htmlspecialchars($product['image_path'] ?? 'placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <div class="price">KSh <?php echo number_format($product['price'], 2); ?></div>
                                <div class="seller-info">
                                    <img src="<?php echo htmlspecialchars($user_data['profile_pic'] ?? '../assets/images/profile-placeholder.png'); ?>" alt="Seller">
                                    <span>@<?php echo htmlspecialchars($product['seller_username']); ?></span>
                                </div>
                                <button class="add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                                <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn-view-details">View Details</a>
                                <button class="add-to-wishlist" data-product-id="<?php echo $product['id']; ?>">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> StrathConnect. All rights reserved.</p>
    </footer>

    <script>
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            if (!productId) return; // Ignore sidebar button
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product added to cart!');
                    updateCartCount(data.cart_count);
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });
    });

    document.querySelectorAll('.add-to-wishlist').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            fetch('add_to_wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product added to wishlist!');
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });
    });

    function updateCartCount(count) {
        document.querySelector('.cart-count').textContent = count;
    }

    fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartCount(data.count);
            }
        });
    </script>
</body>
</html>
