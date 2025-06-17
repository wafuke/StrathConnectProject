<?php
// Start session and check auth
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'seller') {
    header("Location: ../public/login.php");
    exit();
}

// Database connection (directly in file)
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
    $name = $conn->real_escape_string($_POST['name']);
    $category = $conn->real_escape_string($_POST['category']);
    $price = floatval($_POST['price']);
    $description = $conn->real_escape_string($_POST['description']);
    
    // File upload handling
    $image_path = '';
    if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../assets/images/products/";
        $image_name = uniqid() . '_' . basename($_FILES['image']['name']);
        $target_file = $target_dir . $image_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = $image_name;
        }
    }
    
    // Insert product
    $sql = "INSERT INTO products (seller_id, name, category, description, price, image_path)
            VALUES ('$seller_id', '$name', '$category', '$description', $price, '$image_path')";
    
    if ($conn->query($sql) === TRUE) {
        $_SESSION['message'] = "Product added successfully!";
        header("Location: seller_dashboard.php");
    } else {
        $_SESSION['error'] = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <!-- Your existing HTML form here -->
    <form method="POST" enctype="multipart/form-data">
        <!-- Form fields remain the same -->
    </form>
</body>
</html>