<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Debugging
file_put_contents('cart_debug.log', print_r($_POST, true), FILE_APPEND);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'strathconnect');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

try {
    $conn->begin_transaction();
    
    // Check product availability
    $check = $conn->prepare("SELECT id, price FROM products WHERE id = ? AND is_approved = 1 FOR UPDATE");
    if (!$check) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $check->bind_param("i", $product_id);
    $check->execute();
    
    if ($check->get_result()->num_rows === 0) {
        throw new Exception("Product unavailable or not approved");
    }
    
    // Check existing cart item
    $buyer_id = $_SESSION['user_id'];
    $cart_check = $conn->prepare("SELECT id, quantity FROM cart WHERE buyer_id = ? AND product_id = ? FOR UPDATE");
    if (!$cart_check) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $cart_check->bind_param("ii", $buyer_id, $product_id);
    $cart_check->execute();
    $cart_result = $cart_check->get_result();
    
    if ($cart_result->num_rows > 0) {
        // Update quantity
        $item = $cart_result->fetch_assoc();
        $update = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
        if (!$update) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $update->bind_param("i", $item['id']);
        $update->execute();
    } else {
        // Insert new item
        $insert = $conn->prepare("INSERT INTO cart (buyer_id, product_id, quantity) VALUES (?, ?, 1)");
        if (!$insert) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $insert->bind_param("ii", $buyer_id, $product_id);
        $insert->execute();
    }
    
    // Get updated count
    $count_result = $conn->query("SELECT COALESCE(SUM(quantity), 0) as count FROM cart WHERE buyer_id = $buyer_id");
    if (!$count_result) {
        throw new Exception("Count query failed: " . $conn->error);
    }
    $count = $count_result->fetch_assoc()['count'];
    
    $conn->commit();
    echo json_encode(['success' => true, 'cart_count' => $count]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    file_put_contents('cart_errors.log', $e->getMessage(), FILE_APPEND);
}

$conn->close();
?>