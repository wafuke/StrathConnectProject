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

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

$query = "SELECT s.*, u.username as seller_username, u.profile_pic
          FROM services s
          JOIN users u ON s.seller_id = u.id
          WHERE s.is_approved = 1";

if (!empty($search)) {
    $query .= " AND (s.title LIKE '%$search%' OR s.description LIKE '%$search%')";
}

if (!empty($category) && $category !== 'all') {
    $query .= " AND s.category = '$category'";
}

switch ($sort) {
    case 'price_low': $query .= " ORDER BY s.price ASC"; break;
    case 'price_high': $query .= " ORDER BY s.price DESC"; break;
    case 'rating': $query .= " ORDER BY s.rating DESC"; break;
    default: $query .= " ORDER BY s.created_at DESC";
}

$services = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

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
  <style>
    .search-filters {
      margin-bottom: 20px;
    }
    .filter-form {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }
    .filter-form input[type="text"] {
      flex: 2;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .filter-form select, .filter-form button {
      flex: 1;
      padding: 8px;
      border-radius: 4px;
      border: 1px solid #ccc;
    }
    .services-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 15px;
    }
    .service-card {
      background: #fff;
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .service-details h3 {
      margin: 0;
      font-size: 18px;
      color: #003366;
    }
    .service-description {
      font-size: 14px;
      color: #555;
      min-height: 40px;
    }
    .service-meta {
      font-size: 14px;
      color: #555;
      display: flex;
      flex-direction: column;
      gap: 5px;
    }
    .price {
      font-weight: bold;
      color: #dc3545;
    }
    .seller-info {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .seller-info img {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      object-fit: cover;
    }
    .btn-primary {
      background: #003366;
      color: #fff;
      padding: 8px;
      text-align: center;
      border-radius: 4px;
      text-decoration: none;
      transition: background 0.3s ease;
    }
    .btn-primary:hover {
      background: #004080;
    }
    .empty-state {
      text-align: center;
      padding: 50px 20px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
  </style>
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
            <i class="fas fa-filter"></i> Apply
          </button>
        </form>
      </section>

      <div class="services-grid">
        <?php if (empty($services)): ?>
          <div class="empty-state">
            <i class="fas fa-concierge-bell fa-2x"></i>
            <h3>No services found</h3>
            <p>Try adjusting your search filters</p>
          </div>
        <?php else: ?>
          <?php foreach ($services as $service): ?>
            <div class="service-card">
              <div class="service-details">
                <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                <div class="service-description">
                  <?php echo htmlspecialchars(mb_strimwidth($service['description'], 0, 100, '...')); ?>
                </div>
                <div class="service-meta">
                  <div class="price">KSh <?php echo number_format($service['price'], 2); ?></div>
                  <div class="seller-info">
                    <img src="<?php echo htmlspecialchars($service['profile_pic'] ?? '../assets/images/profile-placeholder.png'); ?>"
                      alt="@<?php echo htmlspecialchars($service['seller_username']); ?>">
                    <span>@<?php echo htmlspecialchars($service['seller_username']); ?></span>
                  </div>
                  <div class="rating">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                      <i class="fas fa-star<?php echo $i < floor($service['rating'] ?? 0) ? '' : '-empty'; ?>"></i>
                    <?php endfor; ?>
                  </div>
                </div>
                <a href="service_detail.php?id=<?php echo $service['id']; ?>" class="btn-primary">
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
