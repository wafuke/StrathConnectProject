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
</head>
<body>
    <nav class="navbar">
        <div class="logo">StrathConnect</div>
        <ul class="nav-links">
            <li><a href="../public/index.php">Home</a></li>
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