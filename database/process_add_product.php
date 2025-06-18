<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'seller') {
    header("Location: ../public/login.php");
    exit();
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
$seller_id = $_SESSION['user_id'];
$name = $conn->real_escape_string($_POST['name']);
$category = $conn->real_escape_string($_POST['category']);
$price = floatval($_POST['price']);
$description = $conn->real_escape_string($_POST['description']);

// File upload handling
$image_path = '';
if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $target_dir = "../assets/images/products/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png'];
    $file_type = $_FILES['image']['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        $_SESSION['error'] = "Only JPEG and PNG images are allowed";
        header("Location: add_product.php");
        exit();
    }
    
    // Validate file size (2MB max)
    if ($_FILES['image']['size'] > 2097152) {
        $_SESSION['error'] = "Image size must be less than 2MB";
        header("Location: add_product.php");
        exit();
    }
    
    // Generate unique filename
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $image_name = uniqid('product_') . '.' . $ext;
    $target_file = $target_dir . $image_name;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        $image_path = $image_name;
    } else {
        $_SESSION['error'] = "Failed to upload image";
        header("Location: add_product.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Product image is required";
    header("Location: add_product.php");
    exit();
}

// Insert product
$sql = "INSERT INTO products (seller_id, name, category, description, price, image_path)
        VALUES ('$seller_id', '$name', '$category', '$description', $price, '$image_path')";

if ($conn->query($sql) === TRUE) {
    $_SESSION['message'] = "Product added successfully!";
    header("Location: ../public/seller_dashboard.php");
} else {
    // Delete uploaded image if DB insert failed
    if (!empty($image_path)) {
        unlink($target_dir . $image_path);
    }
    $_SESSION['error'] = "Error: " . $conn->error;
    header("Location: add_product.php");
}

$conn->close();
exit();
?>