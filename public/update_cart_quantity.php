<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'strathconnect');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$cart_id = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$cart_id || !$quantity) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

// Verify the cart item belongs to the user
$check = $conn->prepare("SELECT 1 FROM cart WHERE id = ? AND buyer_id = ?");
$check->bind_param("ii", $cart_id, $_SESSION['user_id']);
$check->execute();

if ($check->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
    exit();
}

$update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
$update->bind_param("ii", $quantity, $cart_id);

if ($update->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}

$conn->close();
?>