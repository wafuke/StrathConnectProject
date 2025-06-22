<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../includes/daraja_api.php'; // We'll create this next

$phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
if (!$phone || !preg_match('/^254[17]\d{8}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'strathconnect');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    $conn->begin_transaction();
    
    // Get cart items and calculate total
    $buyer_id = $_SESSION['user_id'];
    $query = "SELECT c.product_id, c.quantity, p.price, p.seller_id 
              FROM cart c
              JOIN products p ON c.product_id = p.id
              WHERE c.buyer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $buyer_id);
    $stmt->execute();
    $cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($cart_items)) {
        throw new Exception("Your cart is empty");
    }
    
    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    // Initiate M-Pesa payment
    $payment_response = initiateMpesaPayment($phone, $total, "StrathConnect Order");
    
    if (!$payment_response || !isset($payment_response['ResponseCode']) || $payment_response['ResponseCode'] !== "0") {
        throw new Exception("Payment initiation failed. Please try again.");
    }
    
    // Create order records for each item
    $order_ids = [];
    foreach ($cart_items as $item) {
        $insert = $conn->prepare("INSERT INTO orders 
                                 (buyer_id, seller_id, product_id, price, quantity, status)
                                 VALUES (?, ?, ?, ?, ?, 'Pending')");
        $insert->bind_param("iiidi", $buyer_id, $item['seller_id'], $item['product_id'], 
                          $item['price'], $item['quantity']);
        $insert->execute();
        $order_ids[] = $conn->insert_id;
    }
    
    // Clear the cart
    $delete = $conn->prepare("DELETE FROM cart WHERE buyer_id = ?");
    $delete->bind_param("i", $buyer_id);
    $delete->execute();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'order_id' => implode(',', $order_ids),
        'mpesa_response' => $payment_response
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>