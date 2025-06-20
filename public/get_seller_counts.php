<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'seller') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'strathconnect';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$seller_id = $_SESSION['user_id'];
$counts = [];

// Get product count
$product_query = "SELECT COUNT(*) as count FROM products WHERE seller_id = ?";
$stmt = $conn->prepare($product_query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$product_result = $stmt->get_result();
$counts['product_count'] = $product_result->fetch_assoc()['count'];
$stmt->close();

// Get service count
$service_query = "SELECT COUNT(*) as count FROM services WHERE seller_id = ?";
$stmt = $conn->prepare($service_query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$service_result = $stmt->get_result();
$counts['service_count'] = $service_result->fetch_assoc()['count'];
$stmt->close();

// Get pending orders count
$order_query = "SELECT COUNT(*) as count FROM orders WHERE seller_id = ? AND status = 'pending'";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$order_result = $stmt->get_result();
$counts['order_count'] = $order_result->fetch_assoc()['count'];
$stmt->close();

$conn->close();
echo json_encode($counts);
?>