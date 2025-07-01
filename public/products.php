<?php
session_start();

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'strathconnect';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all approved products
$query = "SELECT p.*, u.username FROM products p 
          JOIN users u ON p.seller_id = u.id 
          WHERE p.is_approved = 1 
          ORDER BY p.created_at DESC";
$products = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Get distinct categories
$categories_query = "SELECT DISTINCT category FROM products WHERE is_approved = 1";
$categories = $conn->query($categories_query)->fetch_all(MYSQLI_ASSOC);

// Handle search and filter
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

$filtered_products = array_filter($products, function($product) use ($search, $category_filter) {
    $matches_search = empty($search) || 
                     stripos($product['name'], $search) !== false || 
                     stripos($product['description'], $search) !== false;
    $matches_category = empty($category_filter) || $product['category'] === $category_filter;
    return $matches_search && $matches_category;
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #003366;
            --secondary: #FFCC00;
            --light: #f8f9fa;
            --dark: #212529;
            --white: #ffffff;
        }
        
        body {
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        header {
            background-color: var(--primary);
            color: var(--white);
            padding: 1rem 2rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--white);
        }
        
        .logo span {
            color: var(--secondary);
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 1.5rem;
        }
        
        .nav-links a {
            color: var(--white);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            background-color: var(--secondary);
            color: var(--dark);
        }
        
        .login-btn {
            background-color: var(--secondary);
            color: var(--dark) !important;
            font-weight: bold !important;
        }
        
        .products-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .filter-section {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex: 1;
            min-width: 300px;
        }
        
        .search-box input, .category-filter select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .category-filter {
            min-width: 200px;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .product-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
        }
        
        .product-image {
            height: 200px;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
        }
        
        .product-details {
            padding: 1.5rem;
        }
        
        .product-title {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }
        
        .product-price {
            font-weight: bold;
            color: var(--primary);
            font-size: 1.2rem;
            margin: 0.5rem 0;
        }
        
        .product-category {
            display: inline-block;
            background: #f0f5ff;
            color: var(--primary);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
        }
        
        .product-seller {
            font-size: 0.9rem;
            color: #666;
        }
        
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        footer {
            background-color: var(--primary);
            color: var(--white);
            text-align: center;
            padding: 2rem;
            margin-top: 4rem;
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-links {
                flex-direction: column;
                align-items: center;
                gap: 0.5rem;
            }
            
            .filter-section {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">Strath<span>Connect</span></div>
            <ul class="nav-links">
                <li><a href="../public/index.php">Home</a></li>
                <li><a href="../public/products.php" class="active">Products</a></li>
                <li><a href="../public/services.php">Services</a></li>
                <li><a href="../public/login.php" class="login-btn">Login</a></li>
            </ul>
        </nav>
    </header>

    <div class="products-container">
        <h1>Available Products</h1>
        
        <div class="filter-section">
            <div class="search-box">
                <form method="GET">
                    <input type="text" name="search" placeholder="Search products..." 
                           value="<?= htmlspecialchars($search) ?>">
                </form>
            </div>
            
            <div class="category-filter">
                <form method="GET">
                    <select name="category" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['category']) ?>" 
                                <?= $category_filter === $cat['category'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <div class="products-grid">
            <?php if (empty($filtered_products)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>No products found</h3>
                    <p>Try adjusting your search or filter criteria</p>
                </div>
            <?php else: ?>
                <?php foreach ($filtered_products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <i class="fas fa-box-open fa-3x"></i>
                        </div>
                        <div class="product-details">
                            <span class="product-category"><?= htmlspecialchars($product['category']) ?></span>
                            <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                            <div class="product-price">KSh <?= number_format($product['price'], 2) ?></div>
                            <p class="product-description"><?= htmlspecialchars(substr($product['description'], 0, 100)) ?>...</p>
                            <p class="product-seller">Sold by: <?= htmlspecialchars($product['username']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> StrathConnect. All rights reserved.</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>