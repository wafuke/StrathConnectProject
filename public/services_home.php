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

// Get all approved services
$query = "SELECT s.*, u.username FROM services s 
          JOIN users u ON s.seller_id = u.id 
          WHERE s.is_approved = 1 
          ORDER BY s.created_at DESC";
$services = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Get distinct categories
$categories_query = "SELECT DISTINCT category FROM services WHERE is_approved = 1";
$categories = $conn->query($categories_query)->fetch_all(MYSQLI_ASSOC);

// Handle search and filter
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

$filtered_services = array_filter($services, function($service) use ($search, $category_filter) {
    $matches_search = empty($search) || 
                     stripos($service['title'], $search) !== false || 
                     stripos($service['description'], $search) !== false;
    $matches_category = empty($category_filter) || $service['category'] === $category_filter;
    return $matches_search && $matches_category;
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Services - StrathConnect</title>
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
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 0;
    }

    header {
      background-color: var(--primary);
      color: var(--white);
      padding: 1rem 2rem;
    }

    .navbar {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .logo {
      font-size: 1.8rem;
      font-weight: bold;
    }

    .logo span {
      color: var(--secondary);
    }

    .nav-links {
      display: flex;
      gap: 1.5rem;
      list-style: none;
    }

    .nav-links a {
      color: var(--white);
      text-decoration: none;
      padding: 0.5rem 1rem;
      border-radius: 4px;
      transition: background 0.3s;
    }

    .nav-links a:hover {
      background: var(--secondary);
      color: var(--dark);
    }

    .login-btn {
      background: var(--secondary);
      color: var(--dark) !important;
      font-weight: bold;
    }

    .services-container {
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

    .services-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 1.5rem;
    }

    .service-card {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      overflow: hidden;
      transition: transform 0.3s;
      display: flex;
      flex-direction: column;
    }

    .service-card:hover {
      transform: translateY(-5px);
    }

    .service-image {
      height: 150px;
      background: #f5f5f5;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ccc;
    }

    .service-details {
      padding: 1rem;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .service-title {
      font-size: 1.1rem;
      color: var(--primary);
      margin-bottom: 0.5rem;
    }

    .service-price {
      font-weight: bold;
      color: var(--primary);
      font-size: 1.2rem;
      margin: 0.5rem 0;
    }

    .service-category {
      display: inline-block;
      background: #f0f5ff;
      color: var(--primary);
      padding: 0.2rem 0.5rem;
      border-radius: 4px;
      font-size: 0.8rem;
      margin-bottom: 0.5rem;
    }

    .service-seller, .rate-type {
      font-size: 0.9rem;
      color: #666;
    }

    .view-details-btn {
      margin-top: auto;
      display: inline-block;
      background: var(--secondary);
      color: var(--dark);
      padding: 0.5rem 1rem;
      border-radius: 4px;
      text-align: center;
      text-decoration: none;
      font-weight: bold;
      transition: background 0.3s;
    }

    .view-details-btn:hover {
      background: #e6b800;
    }

    .empty-state {
      grid-column: 1 / -1;
      text-align: center;
      padding: 3rem;
      background: #f9f9f9;
      border-radius: 8px;
    }

    footer {
      background: var(--primary);
      color: #fff;
      text-align: center;
      padding: 2rem;
      margin-top: 3rem;
    }

    @media (max-width: 768px) {
      .navbar {
        flex-direction: column;
        gap: 0.5rem;
      }

      .nav-links {
        flex-direction: column;
        align-items: center;
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
        <li><a href="../public/products.php">Products</a></li>
        <li><a href="../public/services.php" class="active">Services</a></li>
        <li><a href="../public/login.php" class="login-btn">Login</a></li>
      </ul>
    </nav>
  </header>

  <div class="services-container">
    <h1>Available Services</h1>

    <div class="filter-section">
      <div class="search-box">
        <form method="GET">
          <input type="text" name="search" placeholder="Search services..." value="<?= htmlspecialchars($search) ?>">
        </form>
      </div>

      <div class="category-filter">
        <form method="GET">
          <select name="category" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $category_filter === $cat['category'] ? 'selected' : '' ?>>
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

    <div class="services-grid">
      <?php if (empty($filtered_services)): ?>
        <div class="empty-state">
          <i class="fas fa-concierge-bell fa-3x"></i>
          <h3>No services found</h3>
          <p>Try adjusting your search or filter criteria</p>
        </div>
      <?php else: ?>
        <?php foreach ($filtered_services as $service): ?>
          <div class="service-card">
            <div class="service-image">
              <i class="fas fa-concierge-bell fa-2x"></i>
            </div>
            <div class="service-details">
              <span class="service-category"><?= htmlspecialchars($service['category']) ?></span>
              <h3 class="service-title"><?= htmlspecialchars($service['title']) ?></h3>
              <div class="service-price">KSh <?= number_format($service['price'], 2) ?></div>
              <span class="rate-type">(<?= htmlspecialchars($service['rate_type']) ?> rate)</span>
              <p class="service-description"><?= htmlspecialchars(substr($service['description'], 0, 100)) ?>...</p>
              <p class="service-seller">Offered by: <?= htmlspecialchars($service['username']) ?></p>
              <a href="service_home_detail.php?id=<?= $service['id'] ?>" class="view-details-btn">View Details</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <footer>
    <p>&copy; <?= date('Y') ?> StrathConnect. All rights reserved.</p>
  </footer>
</body>
</html>
<?php $conn->close(); ?>
