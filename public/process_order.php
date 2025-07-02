<?php
session_start();


// Verify buyer is logged in
if (!isset($_SESSION['user_id'])) {
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

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    header("HTTP/1.1 403 Forbidden");
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (empty($data['phone']) || !preg_match('/^254[17]\d{8}$/', $data['phone'])) {
    die(json_encode(['success' => false, 'message' => 'Invalid phone number']));
}

if (empty($data['address_id'])) {
    die(json_encode(['success' => false, 'message' => 'Address not selected']));
}

$conn = new mysqli('localhost', 'root', '', 'strathconnect');
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Begin transaction
$conn->begin_transaction();

try {
    // 1. Get cart items and calculate total
    $buyer_id = $_SESSION['user_id'];
    $cart_query = "SELECT c.product_id, c.quantity, p.price 
                   FROM cart c JOIN products p ON c.product_id = p.id 
                   WHERE c.buyer_id = ?";
    $stmt = $conn->prepare($cart_query);
    $stmt->bind_param("i", $buyer_id);
    $stmt->execute();
    $cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    $total += 150; // Shipping
    
    // 2. Create order
    $order_query = "INSERT INTO orders (buyer_id, address_id, payment_method, total_amount) 
                    VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("iisd", $buyer_id, $data['address_id'], $data['payment_method'], $total);
    $stmt->execute();
    $order_id = $conn->insert_id;
    
    // 3. Add order items
    $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price, is_gift) 
                   VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($item_query);
    
    foreach ($cart_items as $item) {
        $is_gift = in_array($item['product_id'], $data['gift_options'] ?? []) ? 1 : 0;
        $stmt->bind_param("iiidi", $order_id, $item['product_id'], $item['quantity'], $item['price'], $is_gift);
        $stmt->execute();
    }
    
    // 4. Clear cart
    $delete_query = "DELETE FROM cart WHERE buyer_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $buyer_id);
    $stmt->execute();
    
    // 5. Simulate M-Pesa payment (in a real app, integrate with M-Pesa API)
    $mpesa_code = 'MPESA' . strtoupper(bin2hex(random_bytes(4)));
    
    $update_query = "UPDATE orders SET mpesa_code = ?, status = 'completed' WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $mpesa_code, $order_id);
    $stmt->execute();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'mpesa_code' => $mpesa_code
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    die(json_encode(['success' => false, 'message' => 'Order processing failed: ' . $e->getMessage()]));
}

$conn->close();
?>