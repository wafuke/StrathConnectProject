<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    header("Location: ../public/login.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'strathconnect');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get search/filter parameters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query
$query = "SELECT s.*, u.username as seller_username 
          FROM services s
          JOIN users u ON s.seller_id = u.id
          WHERE s.is_approved = 1";

if (!empty($search)) {
    $query .= " AND (s.title LIKE '%$search%' OR s.description LIKE '%$search%')";
}

if (!empty($category) && $category !== 'all') {
    $query .= " AND s.category = '$category'";
}

// Sorting
switch ($sort) {
    case 'price_low': $query .= " ORDER BY s.price ASC"; break;
    case 'price_high': $query .= " ORDER BY s.price DESC"; break;
    case 'rating': $query .= " ORDER BY s.rating DESC"; break;
    default: $query .= " ORDER BY s.created_at DESC";
}

$services = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Get distinct categories
$categories = $conn->query("SELECT DISTINCT category FROM services")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">StrathConnect</div>
        <ul class="nav-links">
            
            <li><a href="marketplace.php">Marketplace</a></li>
            <li><a href="services.php" class="active">Services</a></li>
            <li><a href="buyer_orders.php">My Orders</a></li>
            <li><a href="../public/login.php">Logout</a></li>
        </ul>
    </nav>

    <div class="dashboard-container">
        <main class="dashboard-content">
            <h1>Available Services</h1>
            
            <section class="search-filters">
                <form method="GET" class="filter-form">
                    <input type="text" name="search" placeholder="Search services..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    
                    <select name="category">
                        <option value="all">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>"
                                <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="sort">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Top Rated</option>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </form>
            </section>

            <div class="services-grid">
                <?php if (empty($services)): ?>
                    <div class="empty-state">
                        <i class="fas fa-concierge-bell"></i>
                        <h3>No services found</h3>
                        <p>Try adjusting your search filters</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($services as $service): ?>
                        <div class="service-card">
                            <img src="../assets/images/services/<?php echo htmlspecialchars($service['image_path'] ?? 'service-placeholder.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($service['title']); ?>">
                            <div class="service-details">
                                <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                                <div class="service-meta">
                                    <div class="price">KSh <?php echo number_format($service['price'], 2); ?></div>
                                    <div class="seller">@<?php echo htmlspecialchars($service['seller_username']); ?></div>
                                    <div class="rating">
                                        <?php for ($i = 0; $i < 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i < floor($service['rating'] ?? 0) ? '' : '-empty'; ?>"></i>
                                        <?php endfor; ?>
                                        <span>(<?php echo $service['review_count'] ?? 0; ?>)</span>
                                    </div>
                                </div>
                                <a href="service_detail.php?id=<?php echo $service['id']; ?>" class="btn btn-primary">
                                    View Details
                                </a>
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