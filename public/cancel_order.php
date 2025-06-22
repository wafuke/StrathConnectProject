<?php
session_start();
// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'strathconnect';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Verify buyer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get order ID
$order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
if (!$order_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Check if order belongs to buyer and is cancellable
    $check_query = "SELECT status FROM orders 
                    WHERE id = ? AND buyer_id = ? 
                    AND status IN ('Pending', 'Processing')
                    FOR UPDATE";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Order cannot be cancelled");
    }
    
    // Update order status
    $update_query = "UPDATE orders SET status = 'Cancelled' WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Order cancelled']);
    
} catch (Exception $e) {
    $conn->rollback();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}