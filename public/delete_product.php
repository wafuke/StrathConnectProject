<?php
session_start();

// Verify seller is logged in
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

// Verify product ID was provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No product specified";
    header("Location: seller_products.php");
    exit();
}

$product_id = intval($_GET['id']);
$seller_id = $_SESSION['user_id'];

// Get product details to delete associated image
$query = "SELECT image_path FROM products WHERE id = ? AND seller_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $product_id, $seller_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Product not found or you don't have permission to delete it";
    header("Location: seller_products.php");
    exit();
}

$product = $result->fetch_assoc();
$stmt->close();

// Delete the product from database
$query = "DELETE FROM products WHERE id = ? AND seller_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $product_id, $seller_id);

if ($stmt->execute()) {
    // Delete the associated image if it exists and isn't the placeholder
    if (!empty($product['image_path']) && $product['image_path'] !== 'placeholder.jpg') {
        $image_path = "../assets/images/products/" . $product['image_path'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    $_SESSION['success'] = "Product deleted successfully";
} else {
    $_SESSION['error'] = "Error deleting product: " . $stmt->error;
}

$stmt->close();
$conn->close();

header("Location: seller_products.php");
exit();
?>