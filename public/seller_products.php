<?php
session_start();

// Verify seller is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'seller') {
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

// Get seller's products
$seller_id = $_SESSION['user_id'];
$query = "SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">StrathConnect</div>
        <ul class="nav-links">
            <li><a href="../public/index.php">Home</a></li>
            <li><a href="seller_dashboard.php">Dashboard</a></li>
            <li><a href="seller_products.php" class="active">My Products</a></li>
            <li><a href="seller_services.php">My Services</a></li>
            <li><a href="seller_orders.php">Orders</a></li>
            <li><a href="../public/login.php">Logout</a></li>
        </ul>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="profile-summary">
                <img src="../assets/images/profile-placeholder.png" alt="Profile" class="profile-pic">
                <h3><?php echo htmlspecialchars($_SESSION['username'] ?? 'Seller'); ?></h3>
                <p>@<?php echo htmlspecialchars($_SESSION['username'] ?? 'username'); ?></p>
            </div>
            <nav class="sidebar-nav">
                <a href="seller_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="seller_products.php" class="active"><i class="fas fa-box-open"></i> My Products</a>
                <a href="seller_services.php"><i class="fas fa-concierge-bell"></i> My Services</a>
                <a href="seller_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a href="seller_messages.php"><i class="fas fa-envelope"></i> Messages</a>
                <a href="seller_settings.php"><i class="fas fa-cog"></i> Settings</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <div class="dashboard-header">
                <h1>My Products</h1>
                <a href="../database/add_product.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <div class="products-grid">
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h3>No products found</h3>
                        <p>You haven't added any products yet. Get started by adding your first product!</p>
                        <a href="add_product.php" class="btn btn-primary">Add Product</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="../assets/images/products/<?php echo htmlspecialchars($product['image_path'] ?? 'placeholder.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <div class="product-status <?php echo $product['is_approved'] ? 'approved' : 'pending'; ?>">
                                    <?php echo $product['is_approved'] ? 'Approved' : 'Pending Approval'; ?>
                                </div>
                            </div>
                            <div class="product-details">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-category"><?php echo htmlspecialchars($product['category']); ?></p>
                                <p class="product-price">KSh <?php echo number_format($product['price'], 2); ?></p>
                                <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
                                <div class="product-actions">
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="delete_product.php?id=<?php echo $product['id']; ?>" class="btn btn-delete" 
                                       onclick="return confirm('Are you sure you want to delete this product?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> StrathConnect. All rights reserved.</p>
    </footer>
</body>
</html>
<?php
$conn->close();
?>