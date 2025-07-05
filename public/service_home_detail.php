<?php
// DB connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'strathconnect';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get service ID
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch service details
$query = "SELECT s.*, u.username, u.profile_pic 
          FROM services s 
          JOIN users u ON s.seller_id = u.id 
          WHERE s.id = ? AND s.is_approved = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Service not found or not approved.");
}

$service = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($service['title']) ?> - StrathConnect</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
      --primary: #003366;
      --secondary: #FFCC00;
      --light: #f8f9fa;
      --dark: #212529;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: var(--light);
      color: var(--dark);
      margin: 0;
    }

    header {
      background: var(--primary);
      color: white;
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

    .content {
      max-width: 1000px;
      margin: 2rem auto;
      padding: 1rem;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .service-title {
      font-size: 2rem;
      color: var(--primary);
      margin-bottom: 0.5rem;
    }

    .service-price {
      font-size: 1.5rem;
      color: var(--secondary);
      font-weight: bold;
      margin-bottom: 1rem;
    }

    .service-description {
      margin-bottom: 1.5rem;
      line-height: 1.6;
    }

    .seller-info {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #f1f1f1;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 1.5rem;
    }

    .seller-info img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      object-fit: cover;
    }

    .checkout-btn {
      display: inline-block;
      background: var(--secondary);
      color: var(--dark);
      padding: 0.75rem 1.5rem;
      border-radius: 4px;
      font-weight: bold;
      text-decoration: none;
      transition: background 0.3s ease;
    }

    .checkout-btn:hover {
      background: #e6b800;
    }

    footer {
      background: var(--primary);
      color: white;
      text-align: center;
      padding: 1rem;
      margin-top: 2rem;
    }
  </style>
</head>
<body>
  <header>
    <div class="navbar">
      <div class="logo">Strath<span>Connect</span></div>
      <a href="login.php" style="color: var(--secondary); font-weight: bold;">Login</a>
    </div>
  </header>

  <div class="content">
    <h1 class="service-title"><?= htmlspecialchars($service['title']) ?></h1>
    <div class="service-price">KSh <?= number_format($service['price'], 2) ?></div>

    <div class="seller-info">
      <img src="<?= htmlspecialchars($service['profile_pic'] ?? '../assets/images/profile-placeholder.png') ?>" alt="Seller">
      <div>@<?= htmlspecialchars($service['username']) ?></div>
    </div>

    <div class="service-description">
      <?= nl2br(htmlspecialchars($service['description'])) ?>
    </div>

    <a href="login.php" class="checkout-btn">
      <i class="fas fa-credit-card"></i> Checkout
    </a>
  </div>

  <footer>
    &copy; <?= date('Y') ?> StrathConnect. All rights reserved.
  </footer>
</body>
</html>
