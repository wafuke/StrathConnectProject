<?php
session_start();

// Verify user is logged in and is a seller
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seller_id = $_SESSION['user_id'];
    $title = $conn->real_escape_string($_POST['title']);
    $category = $conn->real_escape_string($_POST['category']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = floatval($_POST['price']);
    $rate_type = $conn->real_escape_string($_POST['rate_type']);
    
    // Insert service
    $sql = "INSERT INTO services (seller_id, title, category, description, price, rate_type)
            VALUES ('$seller_id', '$title', '$category', '$description', $price, '$rate_type')";
    
    if ($conn->query($sql) === TRUE) {
        $_SESSION['message'] = "Service added successfully!";
        header("Location: seller_dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Error: " . $conn->error;
        header("Location: add_service.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Service - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
    
    <div class="form-container">
        <h1>Add New Service</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="success-message">
                <?= $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="add_service.php">
            <div class="form-group">
                <label for="title">Service Title*</label>
                <input type="text" id="title" name="title" required>
            </div>
            
            <div class="form-group">
                <label for="category">Category*</label>
                <select id="category" name="category" required>
                    <option value="">Select a category</option>
                    <option value="Tutoring">Tutoring</option>
                    <option value="Design">Design</option>
                    <option value="Programming">Programming</option>
                    <option value="Writing">Writing</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="description">Description*</label>
                <textarea id="description" name="description" rows="4" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="price">Price (KSh)*</label>
                <input type="number" id="price" name="price" min="0" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="rate_type">Rate Type*</label>
                <select id="rate_type" name="rate_type" required>
                    <option value="">Select rate type</option>
                    <option value="hourly">Hourly</option>
                    <option value="fixed">Fixed Price</option>
                    <option value="per_project">Per Project</option>
                </select>
            </div>
            
            <button type="submit" class="submit-btn">Add Service</button>
        </form>
    </div>
    
    
</body>
</html>