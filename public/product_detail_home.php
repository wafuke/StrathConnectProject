<?php
// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'strathconnect';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch product details
$query = "SELECT p.*, u.username 
          FROM products p 
          JOIN users u ON p.seller_id = u.id 
          WHERE p.id = ? AND p.is_approved = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: products.php");
    exit();
}

$product = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($product['name']) ?> - StrathConnect</title>
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
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: var(--light);
      color: var(--dark);
    }

    header {
      background: var(--primary);
      color: var(--white);
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .logo {
      font-size: 1.5rem;
      font-weight: bold;
    }

    .logo span {
      color: var(--secondary);
    }

    .product-container {
      max-width: 1000px;
      margin: 2rem auto;
      background: var(--white);
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .product-header {
      display: flex;
      gap: 2rem;
      flex-wrap: wrap;
    }

    .product-image {
      flex: 1;
      min-width: 300px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f0f0f0;
      border-radius: 8px;
      padding: 1rem;
    }

    .product-image img {
      max-width: 100%;
      max-height: 400px;
      object-fit: contain;
    }

    .product-info {
      flex: 2;
    }

    .product-title {
      font-size: 2rem;
      margin-bottom: 0.5rem;
      color: var(--primary);
    }

    .product-price {
      font-size: 1.5rem;
      font-weight: bold;
      color: var(--secondary);
      margin-bottom: 1rem;
    }

    .product-category {
      display: inline-block;
      background: #e6f0ff;
      color: var(--primary);
      padding: 0.25rem 0.5rem;
      border-radius: 4px;
      font-size: 0.9rem;
      margin-bottom: 1rem;
    }

    .product-description {
      line-height: 1.6;
      margin-bottom: 1.5rem;
    }

    .product-seller {
      font-style: italic;
      color: #555;
      margin-bottom: 1.5rem;
    }

    .checkout-btn {
      background: var(--secondary);
      color: var(--dark);
      padding: 0.75rem 1.5rem;
      border: none;
      border-radius: 4px;
      font-weight: bold;
      font-size: 1rem;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      transition: background 0.3s ease;
    }

    .checkout-btn:hover {
      background: #e6b800;
    }

    footer {
      background: var(--primary);
      color: var(--white);
      text-align: center;
      padding: 1.5rem;
      margin-top: 2rem;
    }

    @media(max-width: 768px) {
      .product-header {
        flex-direction: column;
      }

      .product-image {
        margin-bottom: 1rem;
      }
    }
  </style>
</head>
<body>
  <header>
    <div class="logo">Strath<span>Connect</span></div>
    <a href="login.php" class="checkout-btn">Login</a>
  </header>

  <div class="product-container">
    <div class="product-header">
      <div class="product-image">
        <img src="../assets/images/products/<?= htmlspecialchars($product['image_path'] ?? 'placeholder.jpg') ?>" 
             alt="<?= htmlspecialchars($product['name']) ?>">
      </div>

      <div class="product-info">
        <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
        <div class="product-price">KSh <?= number_format($product['price'], 2) ?></div>
        <div class="product-category"><?= htmlspecialchars($product['category']) ?></div>
        <div class="product-description"><?= nl2br(htmlspecialchars($product['description'])) ?></div>
        <div class="product-seller">Sold by: @<?= htmlspecialchars($product['username']) ?></div>

        <a href="login.php" class="checkout-btn">
          <i class="fas fa-shopping-cart"></i> Checkout
        </a>
      </div>
    </div>
  </div>

  <footer>
    <p>&copy; <?= date('Y') ?> StrathConnect. All rights reserved.</p>
  </footer>
</body>
</html>
