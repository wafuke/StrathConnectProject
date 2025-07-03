<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'seller') {
    header("Location: ../public/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Add Product</title>
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    body {
      background: #f5f5f5;
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
    }
    .navbar {
      background: #003366;
      padding: 10px 20px;
      color: #fff;
    }
    .navbar .logo {
      font-weight: bold;
      font-size: 20px;
    }
    .navbar .nav-links {
      list-style: none;
      display: flex;
      gap: 15px;
      margin: 0;
      padding: 0;
    }
    .navbar .nav-links li {
      display: inline;
    }
    .navbar .nav-links a {
      color: #fff;
      text-decoration: none;
      font-size: 14px;
    }
    .navbar .nav-links a.active,
    .navbar .nav-links a:hover {
      text-decoration: underline;
    }

    form {
      background: #fff;
      max-width: 600px;
      margin: 40px auto;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-group label {
      display: block;
      margin-bottom: 6px;
      font-weight: bold;
      color: #333;
    }
    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group input[type="file"],
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 14px;
    }
    .form-group textarea {
      resize: vertical;
    }
    .form-group small {
      display: block;
      margin-top: 4px;
      color: #777;
    }
    button[type="submit"] {
      background: #FF9900;
      color: #fff;
      padding: 12px 20px;
      border: none;
      border-radius: 6px;
      font-size: 14px;
      cursor: pointer;
      transition: background 0.3s;
    }
    button[type="submit"]:hover {
      background: #e68a00;
    }
    .error, .success {
      max-width: 600px;
      margin: 20px auto;
      padding: 12px;
      border-radius: 6px;
      font-size: 14px;
    }
    .error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    .success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="logo">StrathConnect</div>
    <ul class="nav-links">
      <li><a href="seller_dashboard.php" class="active">Dashboard</a></li>
      <li><a href="seller_products.php">My Products</a></li>
      <li><a href="seller_services.php">My Services</a></li>
      <li><a href="seller_orders.php">Orders</a></li>
      <li><a href="../public/login.php">Logout</a></li>
    </ul>
  </nav>

  <?php if (isset($_SESSION['error'])): ?>
    <div class="error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
  <?php endif; ?>

  <?php if (isset($_SESSION['message'])): ?>
    <div class="success"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
  <?php endif; ?>

  <form action="process_add_product.php" method="POST" enctype="multipart/form-data">
    <div class="form-group">
      <label>Product Name*</label>
      <input type="text" name="name" required>
    </div>

    <div class="form-group">
      <label>Category*</label>
      <select name="category" required>
        <option value="Books">Books</option>
        <option value="Electronics">Electronics</option>
        <option value="Clothing">Clothing</option>
        <option value="Other">Other</option>
      </select>
    </div>

    <div class="form-group">
      <label>Price (KSh)*</label>
      <input type="number" name="price" min="0" step="0.01" required>
    </div>

    <div class="form-group">
      <label>Description</label>
      <textarea name="description" rows="4"></textarea>
    </div>

    <div class="form-group">
      <label>Product Image*</label>
      <input type="file" name="image" accept="image/*" required>
      <small>Max 2MB (JPEG/PNG only)</small>
    </div>

    <button type="submit">Add Product</button>
  </form>
</body>
</html>
