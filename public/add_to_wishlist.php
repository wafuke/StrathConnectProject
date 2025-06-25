<?php
session_start();

// Verify buyer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'strathconnect';

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get product ID from POST data
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

if ($product_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

$buyer_id = $_SESSION['user_id'];

// Check if product exists
$product_check = $conn->prepare("SELECT id FROM products WHERE id = ?");
$product_check->bind_param("i", $product_id);
$product_check->execute();
$product_result = $product_check->get_result();

if ($product_result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

// Check if product is already in wishlist
$wishlist_check = $conn->prepare("SELECT id FROM wishlist WHERE buyer_id = ? AND product_id = ?");
$wishlist_check->bind_param("ii", $buyer_id, $product_id);
$wishlist_check->execute();
$wishlist_result = $wishlist_check->get_result();

if ($wishlist_result->num_rows > 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Product already in wishlist']);
    exit();
}

// Add to wishlist
$insert_stmt = $conn->prepare("INSERT INTO wishlist (buyer_id, product_id) VALUES (?, ?)");
$insert_stmt->bind_param("ii", $buyer_id, $product_id);

if ($insert_stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Product added to wishlist']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to add to wishlist']);
}

// Close connections
$product_check->close();
$wishlist_check->close();
$insert_stmt->close();
$conn->close();
?>