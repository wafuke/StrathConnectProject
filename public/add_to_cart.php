<?php
session_start();

// Verify buyer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'strathconnect';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get product ID from POST data
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$buyer_id = $_SESSION['user_id'];

// Check if product exists and is approved
$product_check = $conn->prepare("SELECT id FROM products WHERE id = ? AND is_approved = 1");
$product_check->bind_param("i", $product_id);
$product_check->execute();
$product_result = $product_check->get_result();

if ($product_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not available']);
    exit();
}

// Check if product is already in cart
$cart_check = $conn->prepare("SELECT id, quantity FROM cart WHERE buyer_id = ? AND product_id = ?");
$cart_check->bind_param("ii", $buyer_id, $product_id);
$cart_check->execute();
$cart_result = $cart_check->get_result();

if ($cart_result->num_rows > 0) {
    // Update quantity if already in cart
    $cart_item = $cart_result->fetch_assoc();
    $new_quantity = $cart_item['quantity'] + 1;
    $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $update->bind_param("ii", $new_quantity, $cart_item['id']);
    $update->execute();
} else {
    // Add new item to cart
    $insert = $conn->prepare("INSERT INTO cart (buyer_id, product_id, quantity) VALUES (?, ?, 1)");
    $insert->bind_param("ii", $buyer_id, $product_id);
    $insert->execute();
}

// Get updated cart count
$count_query = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE buyer_id = ?");
$count_query->bind_param("i", $buyer_id);
$count_query->execute();
$count_result = $count_query->get_result();
$cart_count = $count_result->fetch_assoc()['count'] ?? 0;

$conn->close();

echo json_encode(['success' => true, 'cart_count' => $cart_count]);
?>